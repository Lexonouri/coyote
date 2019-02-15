<?php

namespace Coyote\Repositories\Eloquent;

use Carbon\Carbon;
use Coyote\Forum;
use Coyote\Http\Forms\Forum\PostForm;
use Coyote\Post;
use Coyote\Repositories\Contracts\PostRepositoryInterface;
use Coyote\Repositories\Criteria\Post\WithTrashed;
use Coyote\Topic;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method string search(array $body)
 * @method void setResponse(string $response)
 * @method $this withTrashed()
 */
class PostRepository extends Repository implements PostRepositoryInterface
{
    /**
     * @return string
     */
    public function model()
    {
        return 'Coyote\Post';
    }

    /**
     * Take first post in thread
     *
     * @param int $postId
     * @return mixed
     */
    public function takeFirst($postId)
    {
        return $this
            ->build(function (Builder $sql) use ($postId) {
                return $sql->where('posts.id', $postId);
            })
            ->first();
    }

    /**
     * Take X posts from topic. IMPORTANT: first post of topic will always be fetched
     *
     * @param int $topicId
     * @param int $postId   First post ID (in thread)
     * @param int $page
     * @param int $perPage
     * @return mixed
     */
    public function takeForTopic($topicId, $postId, $page = 0, $perPage = 10)
    {
        $first = $this->takeFirst($postId);

        $sql = $this
            ->build(function (Builder $builder) use ($topicId, $postId, $page, $perPage) {
                return $builder
                    ->where('posts.topic_id', $topicId)
                    ->where('posts.id', '<>', $postId)
                    ->forPage($page, $perPage);
            })
            ->get()
            ->prepend($first);

        $sql->load(['comments' => function ($sub) {
            $sub->select([
                'post_comments.*', 'name', 'is_active', 'is_blocked'
            ])->join('users', 'users.id', '=', 'user_id')->orderBy('id');
        }]);
        $sql->load('attachments');

        return $sql;
    }

    /**
     * Return page number based on ID of post
     *
     * @param $postId
     * @param $topicId
     * @param int $perPage
     * @return double
     */
    public function getPage($postId, $topicId, $perPage = 10)
    {
        $count = $this->applyCriteria(function () use ($topicId, $postId) {
            return $this->model->where('topic_id', $topicId)->where('posts.id', '<', $postId)->count();
        });

        return max(0, floor(($count - 1) / $perPage)) + 1;
    }

    /**
     * @param $topicId
     * @param $markTime
     * @return mixed
     */
    public function getFirstUnreadPostId($topicId, $markTime)
    {
        return $this
            ->model
            ->select(['id'])
            ->where('topic_id', $topicId)
                ->where('created_at', '>', $markTime)
            ->orderBy('id')
            ->limit(1)
            ->value('id');
    }

    /**
     * Find posts by given ID. We use this method to retrieve quoted posts
     *
     * @param array $postsId
     * @param int $topicId
     * @return mixed
     */
    public function findPosts(array $postsId, $topicId)
    {
        return $this
            ->model
            ->select(['posts.*', 'users.name'])
            ->leftJoin('users', 'users.id', '=', 'posts.user_id')
            ->whereIn('posts.id', array_map('intval', $postsId))
            ->where('topic_id', $topicId) // <-- this condition for extra security
            ->get();
    }

    /**
     * @inheritdoc
     */
    public function save(PostForm $form, $user, Forum $forum, Topic $topic, Post $post, $poll)
    {
        $postId = $post->id;
        $log = new Post\Log();

        /**
         * @var $topic Topic
         */
        $topic->fill($form->all());
        $topic->forum()->associate($forum);
        $topic->poll()->associate($poll);

        $topic->save();
        $tags = array_unique((array) $form->getRequest()->get('tags', []));

        if (is_array($tags) && ($topic->wasRecentlyCreated || $postId == $topic->first_post_id)) {
            // assign tags to topic
            $topic->setTags($tags);
        }

        /**
         * @var $post Post
         */
        $post->fill($form->all());

        if (empty($postId)) {
            if ($user) {
                $post->user()->associate($user);
            }

            $post->ip = $form->getRequest()->ip();
            $post->browser = str_limit($form->getRequest()->browser(), 250);
            $post->host = str_limit($form->getRequest()->getClientHost(), 250);
        }

        $log->fillWithPost($post)->fill(['subject' => $topic->subject, 'tags' => $tags]);
        $isDirty = $log->isDirtyComparedToPrevious();

        if ($isDirty && !empty($postId)) {
            $post->fill([
                'edit_count' => $post->edit_count + 1, 'editor_id' => $user->id
            ]);
        }

        $post->forum()->associate($forum);
        $post->topic()->associate($topic);

        $post->save();

        if ($isDirty) {
            if ($user) {
                $log->user_id = $user->id;
            }
            $post->logs()->save($log);
        }

        $post->syncAttachments(array_pluck($form->getRequest()->get('attachments', []), 'id'));

        if ($user) {
            if (empty($postId)) {
                // automatically subscribe post
                $post->subscribe($user->id, true);
            }

            $topic->subscribe($user->id, $form->getRequest()->get('subscribe'));
        }

        return $post;
    }

    /**
     * @param int $userId
     * @param \Coyote\Post $post
     * @return \Coyote\Post
     */
    public function merge($userId, $post)
    {
        /** @var \Coyote\Post $previous */
        $previous = $post->previous();

        $text = join("\n\n", [$previous->text, $post->text]);

        $data = [
            'text'      => $text,
            'subject'   => $post->topic->subject,
            'tags'      => [],
            'user_id'   => $userId,
            'ip'        => request()->ip(),
            'browser'   => request()->browser(),
            'host'      => request()->getClientHost()
        ];

        if ($previous->id == $post->topic->first_post_id) {
            $data['tags'] = $post->topic->tags->pluck('name')->toArray();
        }

        $previous->update(['text' => $text, 'edit_count' => $previous->edit_count + 1, 'editor_id' => $userId]);
        $previous->logs()->create($data);

        $this->app[Post\Attachment::class]->where('post_id', $post->id)->update(['post_id' => $previous->id]);
        $this->app[Post\Comment::class]->where('post_id', $post->id)->update(['post_id' => $previous->id]);

        $post->votes()->each(function ($vote) use ($previous) {
            /** @var \Coyote\Post\Vote $vote */
            if (!$previous->votes()->forUser($vote->user_id)->exists()) {
                $previous->votes()->create(array_except($vote->toArray(), ['post_id']));
            }
        });

        $post->delete();

        return $previous;
    }

    /**
     * @param int $userId
     * @return mixed
     */
    public function takeRatesForUser($userId)
    {
        return $this
            ->model
            ->select([
                'posts.id AS post_id',
                'subject',
                'posts.topic_id',
                'posts.created_at',
                'post_votes.created_at AS voted_at',
                'topics.slug AS topic_slug',
                'forums.slug AS forum_slug',
                'users.id AS user_id',
                'users.name AS user_name'
            ])
            ->join('post_votes', 'post_votes.post_id', '=', 'posts.id')
            ->join('topics', 'topics.id', '=', 'posts.topic_id')
            ->join('forums', 'forums.id', '=', 'posts.forum_id')
            ->join('users', 'users.id', '=', 'post_votes.user_id')
            ->where('posts.user_id', $userId);
    }

    /**
     * @param int $userId
     * @return mixed
     */
    public function takeAcceptsForUser($userId)
    {
        return $this
            ->model
            ->select([
                'posts.id AS post_id',
                'subject',
                'posts.topic_id',
                'posts.created_at',
                'post_accepts.created_at AS accepted_at',
                'topics.slug AS topic_slug',
                'forums.slug AS forum_slug',
                'users.id AS user_id',
                'users.name AS user_name'
            ])
            ->join('post_accepts', 'post_accepts.post_id', '=', 'posts.id')
            ->join('topics', 'topics.id', '=', 'posts.topic_id')
            ->join('forums', 'forums.id', '=', 'posts.forum_id')
            ->join('users', 'users.id', '=', 'post_accepts.user_id')
            ->where('posts.user_id', $userId);
    }

    /**
     * @param int $userId
     * @return mixed
     */
    public function takeStatsForUser($userId)
    {
        $this->applyCriteria();

        return $this
            ->model
            ->select([
                'posts.forum_id',
                'forums.slug',
                'forums.name',
                $this->raw('COUNT(posts.id) AS posts_count'),
                $this->raw('SUM(score) AS votes_count')
            ])
            ->join('forums', 'forums.id', '=', 'posts.forum_id')
            ->where('posts.user_id', $userId)
            ->groupBy('posts.forum_id')
            ->groupBy('forums.slug')
            ->groupBy('forums.name');
    }

    /**
     * @inheritdoc
     */
    public function pieChart($userId)
    {
        $this->applyCriteria();

        $result = $this
            ->model
            ->select(['forums.name', $this->raw('COUNT(*)')])
            ->where('user_id', $userId)
            ->join('forums', 'forums.id', '=', 'posts.forum_id')
            ->groupBy(['forum_id', 'forums.name'])
            ->orderBy($this->raw('COUNT(*)'), 'DESC')
            ->get()
            ->pluck('count', 'name');

        $this->resetModel();

        if (count($result) > 10) {
            $others = $result->splice(10);
            $result['Pozostałe'] = $others->sum();
        }

        return $result->toArray();
    }

    /**
     * @inheritdoc
     */
    public function lineChart($userId)
    {
        $dt = new Carbon('-6 months');
        $interval = $dt->diffInMonths(new Carbon());

        $sql = $this
            ->model
            ->selectRaw(
                'extract(MONTH FROM created_at) AS month, extract(YEAR FROM created_at) AS year, COUNT(*) AS count'
            )
            ->whereRaw("user_id = $userId")
            ->whereRaw("created_at >= '$dt'")
            ->groupBy('year')
            ->groupBy('month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $result = [];
        foreach ($sql as $row) {
            $result[sprintf('%d-%02d', $row['year'], $row['month'])] = $row->toArray();
        }

        $rowset = [];

        for ($i = 0; $i <= $interval; $i++) {
            $key = $dt->format('Y-m');
            $label = $dt->formatLocalized('%B %Y');

            if (!isset($result[$key])) {
                $rowset[] = ['count' => 0, 'year' => $dt->format('Y'), 'month' => $dt->format('n'), 'label' => $label];
            } else {
                $rowset[] = array_merge($result[$key], ['label' => $label]);
            }

            $dt->addMonth(1);
        }

        return $rowset;
    }

    /**
     * @inheritdoc
     */
    public function countComments($userId)
    {
        return $this
            ->app
            ->make(Post\Comment::class)
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * @inheritdoc
     */
    public function countReceivedVotes($userId)
    {
        return $this
            ->model
            ->selectRaw('SUM(score) AS votes')
            ->where('user_id', $userId)
            ->value('votes');
    }

    /**
     * @inheritdoc
     */
    public function countGivenVotes($userId)
    {
        return $this
            ->app
            ->make(Post\Vote::class)
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * @param callable $callback
     * @return mixed
     */
    private function build(callable $callback)
    {
        $sub = $this->toSql($callback($this->buildSubquery()));

        $this->applyCriteria();

        $sql = $this
            ->model
            ->addSelect([// addSelect() instead of select() to retrieve extra columns in criteria
                'posts.*',
                'author.name AS author_name',
                'author.photo',
                'author.is_active',
                'author.is_blocked',
                'author.is_online',
                'author.sig',
                'author.location',
                'author.posts AS author_posts',
                'author.allow_sig',
                'author.allow_smilies',
                'author.allow_count',
                'author.created_at AS author_created_at',
                'author.visited_at AS author_visited_at',
                'editor.name AS editor_name',
                'editor.is_active AS editor_is_active',
                'editor.is_blocked AS editor_is_blocked',
                'groups.name AS group_name',
                'pa.user_id AS accept_on'
            ])
            ->from($this->raw("($sub) AS posts"))
            ->leftJoin('users AS author', 'author.id', '=', 'posts.user_id')
            ->leftJoin('users AS editor', 'editor.id', '=', 'editor_id')
            ->leftJoin('groups', 'groups.id', '=', 'author.group_id')
            ->leftJoin('post_accepts AS pa', 'pa.post_id', '=', 'posts.id')
            ->orderBy('posts.id'); // <-- make sure that posts are in the right order!

        $this->resetModel();

        return $sql;
    }

    /**
     * Subquery for better performance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildSubquery()
    {
        $sql = clone $this->model;

        foreach ($this->getCriteria() as $criteria) {
            // include only this criteria to fetch deleted posts (only for users with special access)
            if ($criteria instanceof WithTrashed) {
                $sql = $criteria->apply($sql, $this);
            }
        }

        return $sql
            ->selectRaw('posts.*')
            ->orderBy('posts.id');
    }
}
