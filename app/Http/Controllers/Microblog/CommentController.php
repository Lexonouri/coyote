<?php

namespace Coyote\Http\Controllers\Microblog;

use Coyote\Http\Controllers\Controller;
use Coyote\Http\Requests\MicroblogRequest;
use Coyote\Notifications\Microblog\UserMentionedNotification;
use Coyote\Notifications\Microblog\SubmittedNotification;
use Coyote\Services\Parser\Helpers\Login as LoginHelper;
use Coyote\Services\Parser\Helpers\Hash as HashHelper;
use Coyote\Repositories\Contracts\MicroblogRepositoryInterface as MicroblogRepository;
use Coyote\Repositories\Contracts\UserRepositoryInterface as UserRepository;
use Coyote\Services\Stream\Activities\Create as Stream_Create;
use Coyote\Services\Stream\Activities\Update as Stream_Update;
use Coyote\Services\Stream\Activities\Delete as Stream_Delete;
use Coyote\Services\Stream\Objects\Microblog as Stream_Microblog;
use Coyote\Services\Stream\Objects\Comment as Stream_Comment;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * @var MicroblogRepository
     */
    private $microblog;

    /**
     * @var UserRepository
     */
    private $user;

    /**
     * @param MicroblogRepository $microblog
     * @param UserRepository $user
     */
    public function __construct(MicroblogRepository $microblog, UserRepository $user)
    {
        parent::__construct();

        $this->microblog = $microblog;
        $this->user = $user;
    }

    /**
     * Publikowanie komentarza na mikroblogu
     *
     * @param MicroblogRequest $request
     * @param Dispatcher $dispatcher
     * @param \Coyote\Microblog $microblog
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(MicroblogRequest $request, Dispatcher $dispatcher, $microblog)
    {
        if (!$microblog->exists) {
            $user = $this->auth;
            $data = $request->only(['text', 'parent_id']) + ['user_id' => $user->id];
        } else {
            $this->authorize('update', $microblog);

            $user = $this->user->find($microblog->user_id, ['id', 'name', 'is_blocked', 'is_active', 'photo']);
            $data = $request->only(['text']);
        }

        $microblog->fill($data);
        $isSubscribed = false;

        $this->transaction(function () use ($microblog, $user, $dispatcher, &$isSubscribed) {
            $microblog->save();

            // we need to get parent entry only for notification
            $parent = $microblog->parent;

            $helper = new HashHelper();
            $microblog->setTags($helper->grab($microblog->text));

            // map microblog object into stream activity object
            $object = (new Stream_Comment())->map($microblog);
            $target = (new Stream_Microblog())->map($parent);

            if ($microblog->wasRecentlyCreated) {
                // now we can add user to subscribers list (if he's not in there yet)
                // after that he will receive notification about other users comments
                if (!$parent->subscribers()->forUser($user->id)->exists()) {
                    $count = $this->microblog->where('parent_id', $parent->id)->where('user_id', $user->id)->count();

                    if ($count == 1) {
                        $parent->subscribers()->create(['user_id' => $user->id]);
                        $isSubscribed = true;
                    }
                } else {
                    $isSubscribed = true;
                }
            }

            // put item into stream activity
            stream($microblog->wasRecentlyCreated ? Stream_Create::class : Stream_Update::class, $object, $target);
        });

        if ($microblog->wasRecentlyCreated) {
            $subscribers = $microblog->parent
                ->subscribers()
                ->with('user')
                ->get()
                ->pluck('user')
                ->exceptUser($this->auth);

            $dispatcher->send($subscribers, new SubmittedNotification($microblog));

            $helper = new LoginHelper();
            // get id of users that were mentioned in the text
            $usersId = $helper->grab($microblog->html);

            if (!empty($usersId)) {
                $dispatcher->send(
                    $this->user->findMany($usersId)->exceptUser($this->auth)->exceptUsers($subscribers),
                    new UserMentionedNotification($microblog)
                );
            }
        }

        foreach (['name', 'is_blocked', 'is_active', 'photo'] as $key) {
            $microblog->{$key} = $user->{$key};
        }

        $view = view('microblog.partials.comment', ['comment' => $microblog, 'microblog' => ['id' => $microblog->parent_id]]);

        return response()->json([
            'html' => $view->render(),
            'subscribe' => (int) $isSubscribed
        ]);
    }

    /**
     * Usuniecie komentarza z mikrobloga
     *
     * @param \Coyote\Microblog $microblog
     */
    public function delete($microblog)
    {
        $this->authorize('delete', $microblog);

        $this->transaction(function () use ($microblog) {
            $microblog->delete();

            $parent = $microblog->parent()->first();
            $object = (new Stream_Comment())->map($microblog);
            $target = (new Stream_Microblog())->map($parent);

            stream(Stream_Delete::class, $object, $target);
        });
    }

    /**
     * Pokaz pozostale komentarze do wpisu
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show($id)
    {
        $comments = $this->microblog->getComments([$id])->slice(0, -2);

        return view('microblog.partials.comments', ['microblog' => ['id' => $id], 'comments' => $comments]);
    }
}
