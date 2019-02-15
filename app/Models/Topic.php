<?php

namespace Coyote;

use Carbon\Carbon;
use Coyote\Models\Scopes\TrackForum;
use Coyote\Models\Scopes\TrackTopic;
use Coyote\Services\Elasticsearch\CharFilters\TopicFilter;
use Coyote\Topic\Subscriber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Coyote\Models\Scopes\Sortable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;

/**
 * @property int $id
 * @property string $slug
 * @property int $replies
 * @property int $replies_real
 * @property \Carbon\Carbon $last_post_created_at
 * @property int $last_post_id
 * @property int $first_post_id
 * @property int $is_locked
 * @property int $is_sticky
 * @property int $views
 * @property int $forum_id
 * @property int $prev_forum_id
 * @property int $poll_id
 * @property int $score
 * @property float $rank
 * @property string $subject
 * @property Forum $forum
 * @property Post\Accept $accept
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property Tag[] $tags
 * @property int $mover_id
 * @property int $locker_id
 * @property \Carbon\Carbon $moved_at
 * @property \Carbon\Carbon $locked_at
 */
class Topic extends Model
{
    use SoftDeletes, Sortable, Taggable, TrackTopic, TrackForum;
    use Searchable{
        getIndexBody as parentGetIndexBody;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['subject', 'slug', 'forum_id', 'is_sticky', 'is_announcement', 'poll_id'];

    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:se';

    /**
     * Hide tags from JSON or/and array. Tag contain closure that can't be serialized. We need to serialize post
     * in PostWasDeleted() class.
     *
     * @var array
     */
    protected $hidden = ['tags'];

    /**
     * Elasticsearch type mapping
     *
     * @var array
     */
    protected $mapping = [
        "posts" => [
            "properties" => [
                "text" => [
                    "type" => "string",
                    "analyzer" => "stopwords_analyzer"
                ]
            ]
        ],
        "subject" => [
            "type" => "string",
            "analyzer" => "default_analyzer"
        ],
        "created_at" => [
            "type" => "date",
            "format" => "yyyy-MM-dd HH:mm:ss"
        ],
        "updated_at" => [
            "type" => "date",
            "format" => "yyyy-MM-dd HH:mm:ss"
        ],
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'last_post_created_at', 'moved_at', 'locked_at'];

    public static function boot()
    {
        parent::boot();

        static::saving(function (Topic $model) {
            $model->rank = $model->getRank();
        });
    }

    /**
     * Scope used in topic filtering.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return mixed
     */
    public function scopeForUser($query, $userId)
    {
        return $this->buildWhereIn($query, $userId, 'topic_users');
    }

    /**
     * Scope used in topic filtering.
     *
     * @param \Illuminate\Database\Eloquent\Builder$query
     * @param int $userId
     * @return mixed
     */
    public function scopeSubscribes($query, $userId)
    {
        return $this->buildWhereIn($query, $userId, 'topic_subscribers');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @param string $table
     * @return mixed
     */
    private function buildWhereIn($query, $userId, $table)
    {
        return $query->whereIn('topics.id', function (Builder $sub) use ($userId, $table) {
            return $sub->select('topic_id')
                ->from($table)
                ->where('user_id', $userId);
        });
    }

    /**
     * @param $subject
     */
    public function setSubjectAttribute($subject)
    {
        $this->attributes['subject'] = trim($subject);
        $this->attributes['slug'] = str_slug($subject, '_');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany('Coyote\Tag', 'topic_tags');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscribers()
    {
        return $this->hasMany(Subscriber::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany('Coyote\Topic\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function forum()
    {
        return $this->belongsTo('Coyote\Forum');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function page()
    {
        return $this->morphOne('Coyote\Page', 'content');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function accept()
    {
        return $this->hasOne('Coyote\Post\Accept');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tracks()
    {
        return $this->hasMany('Coyote\Topic\Track');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany('Coyote\Post');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function firstPost()
    {
        return $this->hasOne('Coyote\Post', 'id', 'first_post_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function poll()
    {
        return $this->belongsTo('Coyote\Poll');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prevForum()
    {
        return $this->belongsTo(Forum::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mover()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function locker()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Subscribe/unsubscribe to topic
     *
     * @param int $userId
     * @param bool $flag
     */
    public function subscribe($userId, $flag)
    {
        if (!$flag) {
            $this->subscribers()->where('user_id', $userId)->delete();
        } else {
            $this->subscribers()->firstOrCreate(['topic_id' => $this->id, 'user_id' => $userId]);
        }
    }

    /**
     * @param string $guestId
     * @return mixed
     */
    public function markTime($guestId)
    {
        return $this->tracks()->select('marked_at')->where('guest_id', $guestId)->value('marked_at');
    }

    /**
     * Mark topic as read
     *
     * @param string $markTime
     * @param string $guestId
     */
    public function markAsRead($markTime, $guestId)
    {
        // builds data to update
        $attributes = ['guest_id' => $guestId];
        // execute a query...

        try {
            $this->tracks()->updateOrCreate(
                $attributes,
                array_merge($attributes, ['marked_at' => $markTime, 'forum_id' => $this->forum_id])
            );
        } catch (QueryException $e) {
            // bardzo rzadko zdarza sie, ze wystapi tutaj wyjatek duplicated entry. uzytkownik moze otworzyc
            // dana strone 2x i zostanie wygenerowane 2x zapytanie INSERT
            logger()->debug($e);
        }
    }

    /**
     * @param int $userId
     * Lock/unlock topic
     */
    public function lock(int $userId)
    {
        $this->is_locked = !$this->is_locked;
        $this->locked_at = $this->is_locked ? $this->freshTimestamp() : null;
        $this->locker_id = $this->is_locked ? $userId : null;

        $this->save();
    }

    /**
     * @return float
     */
    public function getRank()
    {
        if ($this->id === null) {
            $this->last_post_created_at = $this->created_at = Carbon::now();
        }

        return min(1000, 200 * (int) $this->score)
            + min(1000, 100 * (int) $this->replies)
                + min(1000, 15 * (int) $this->views)
                    - ((time() - $this->last_post_created_at->timestamp) / 4500)
                        - ((time() - $this->created_at->timestamp) / 1000);
    }

    /**
     * @param string $column
     * @param int $amount
     * @param array $extra
     * @return bool
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        return $this->forceFill(['rank' => $this->getRank(), 'views' => $this->views + $amount])->save();
    }

    /**
     * Return data to index in elasticsearch
     *
     * @return array
     */
    protected function getIndexBody()
    {
        $this->setCharFilter(TopicFilter::class);
        $body = $this->parentGetIndexBody();

        // we need to index every field from topics except:
        $body = array_except(
            $body,
            ['deleted_at', 'first_post_id', 'last_post_id', 'is_sticky', 'is_announcement', 'poll_id', 'prev_forum_id', 'moved_at', 'locked_at', 'moved_by', 'locked_by']
        );

        $posts = [];

        foreach ($this->posts()->get(['text']) as $post) {
            $posts[] = ['text' => $post->text];
        }

        return array_merge($body, [
            'posts' => $posts,
            'forum' => $this->forum()->first(['name', 'slug'])
        ]);
    }
}
