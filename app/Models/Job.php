<?php

namespace Coyote;

use Carbon\Carbon;
use Coyote\Job\Comment;
use Coyote\Job\Location;
use Coyote\Job\Subscriber;
use Coyote\Models\Job\Refer;
use Coyote\Models\Scopes\ForUser;
use Coyote\Services\Elasticsearch\CharFilters\JobFilter;
use Coyote\Services\Eloquent\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\RoutesNotifications;

/**
 * @property int $id
 * @property int $user_id
 * @property int $firm_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $deadline_at
 * @property \Carbon\Carbon $boost_at
 * @property bool $is_expired
 * @property int $salary_from
 * @property int $salary_to
 * @property int $country_id
 * @property int $currency_id
 * @property int $is_remote
 * @property int $remote_range
 * @property int $enable_apply
 * @property int $visits
 * @property int $rate_id
 * @property bool $is_gross
 * @property int $employment_id
 * @property int $views
 * @property float $score
 * @property float $rank
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property string $recruitment
 * @property string $requirements
 * @property string $email
 * @property string $phone
 * @property User $user
 * @property Firm $firm
 * @property Tag[] $tags
 * @property Location[] $locations
 * @property Currency[] $currency
 * @property Feature[] $features
 * @property Refer[] $refers
 * @property int $plan_id
 * @property bool $is_boost
 * @property bool $is_publish
 * @property bool $is_ads
 * @property bool $is_highlight
 * @property bool $is_on_top
 * @property Plan $plan
 * @property Comment[] $comments
 */
class Job extends Model
{
    use SoftDeletes, ForUser, RoutesNotifications;
    use Searchable {
        getIndexBody as parentGetIndexBody;
    }

    const MONTH           = 1;
    const YEAR            = 2;
    const WEEK            = 3;
    const HOUR            = 4;

    const STUDENT         = 1;
    const JUNIOR          = 2;
    const MID             = 3;
    const SENIOR          = 4;
    const LEAD            = 5;
    const MANAGER         = 6;

    const NET             = 0;
    const GROSS           = 1;

    /**
     * Filling each field adds points to job offer score.
     */
    const SCORE_CONFIG = [
        'job'             => ['salary_from' => 25, 'salary_to' => 25, 'city' => 15, 'seniority_id' => 5],
        'firm'            => ['name' => 15, 'logo' => 5, 'website' => 1, 'description' => 5]
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'requirements',
        'recruitment',
        'is_remote',
        'is_gross',
        'remote_range',
        'country_id',
        'salary_from',
        'salary_to',
        'currency_id',
        'rate_id',
        'employment_id',
        'deadline_at',
        'email',
        'phone',
        'enable_apply',
        'seniority_id',
        'plan_id'
    ];

    /**
     * Default fields values.
     *
     * @var array
     */
    protected $attributes = [
        'enable_apply'      => true,
        'is_remote'         => false,
        'title'             => ''
    ];

    /**
     * Cast to when calling toArray() (for example before index in elasticsearch).
     *
     * @var array
     */
    protected $casts = [
        'is_remote'         => 'boolean',
        'is_boost'          => 'boolean',
        'is_gross'          => 'boolean',
        'is_publish'        => 'boolean',
        'is_ads'            => 'boolean',
        'is_highlight'      => 'boolean',
        'is_on_top'         => 'boolean',
        'plan_id'           => 'int'
    ];

    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:se';

    /**
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deadline_at', 'boost_at'];

    /**
     * Elasticsearch type mapping
     *
     * @var array
     */
    protected $mapping = [
        "id" => [
            "type" => "long"
        ],
        "locations" => [
            "type" => "nested",
            "properties" => [
                "city" => [
                    "type" => "string",
                    "analyzer" => "keyword_asciifolding_analyzer",
                    "fields" => [
                        "original" => ["type" => "text", "analyzer" => "keyword_analyzer", "fielddata" => true]
                    ]
                ],
                "coordinates" => [
                    "type" => "geo_point"
                ]
            ]
        ],
        "title" => [
            "type" => "text",
            "analyzer" => "default_analyzer"
        ],
        "description" => [
            "type" => "text",
            "analyzer" => "default_analyzer"
        ],
        "requirements" => [
            "type" => "text",
            "analyzer" => "default_analyzer"
        ],
        "is_remote" => [
            "type" => "boolean"
        ],
        "remote_range" => [
            "type" => "integer"
        ],
        "tags" => [
            "type" => "text",
            "fields" => [
                "original" => ["type" => "keyword"]
            ]
        ],
        "firm" => [
            "type" => "object",
            "properties" => [
                "name" => [
                    "type" => "text",
                    "analyzer" => "default_analyzer",
                    "fields" => [
                        // filtrujemy firmy po tym polu
                        "original" => ["type" => "text", "analyzer" => "keyword_analyzer", "fielddata" => true]
                    ]
                ],
                "slug" => [
                    "type" => "text",
                    "analyzer" => "keyword_analyzer"
                ]
            ]
        ],
        "created_at" => [
            "type" => "date",
            "format" => "yyyy-MM-dd HH:mm:ss"
        ],
        "updated_at" => [
            "type" => "date",
            "format" => "yyyy-MM-dd HH:mm:ss"
        ],
        "deadline_at" => [
            "type" => "date",
            "format" => "yyyy-MM-dd HH:mm:ss"
        ],
        "boost_at" => [
            "type" => "date",
            "format" => "yyyy-MM-dd HH:mm:ss"
        ],
        "salary" => [
            "type" => "float"
        ],
        "referral_bonus" => [
            "type" => "long"
        ],
        "score" => [
            "type" => "long"
        ],
        "is_boost" => [
            "type" => "boolean"
        ],
        "is_publish" => [
            "type" => "boolean"
        ],
        "is_ads" => [
            "type" => "boolean"
        ],
        "is_on_top" => [
            "type" => "boolean"
        ],
        "is_highlight" => [
            "type" => "boolean"
        ]
    ];

    /**
     * We need to set firm id to null offer is private
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function (Job $model) {
            // nullable column
            foreach (['firm_id', 'salary_from', 'salary_to', 'remote_range', 'seniority_id'] as $column) {
                if (empty($model->{$column})) {
                    $model->{$column} = null;
                }
            }

            $model->score = $model->getScore();

            // field must not be null
            $model->is_remote = (int) $model->is_remote;
        });

        static::creating(function (Job $model) {
            $model->boost_at = $model->freshTimestamp();
        });
    }

    /**
     * @return string[]
     */
    public static function getRatesList()
    {
        return [self::MONTH => 'miesięcznie', self::YEAR => 'rocznie', self::WEEK => 'tygodniowo', self::HOUR => 'godzinowo'];
    }

    /**
     * @return string[]
     */
    public static function getTaxList()
    {
        return [self::NET => 'netto', self::GROSS => 'brutto'];
    }

    /**
     * @return string[]
     */
    public static function getEmploymentList()
    {
        return [1 => 'Umowa o pracę', 2 => 'Umowa zlecenie', 3 => 'Umowa o dzieło', 4 => 'Kontrakt'];
    }

    /**
     * @return string[]
     */
    public static function getSeniorityList()
    {
        return [self::STUDENT => 'Stażysta', self::JUNIOR => 'Junior', self::MID => 'Mid-Level', self::SENIOR => 'Senior', self::LEAD => 'Lead', self::MANAGER => 'Manager'];
    }

    /**
     * @return array
     */
    public static function getRemoteRangeList()
    {
        $list = [];

        for ($i = 100; $i > 0; $i -= 10) {
            $list[$i] = "$i%";
        }

        return $list;
    }

    /**
     * @return int
     */
    public function getScore()
    {
        $score = 0;

        // 70 points maximum...
        foreach (self::SCORE_CONFIG['job'] as $column => $point) {
            if (!empty($this->{$column})) {
                $score += $point;
            }
        }

        // 30 points maximum...
        $score += min(30, (count($this->tags()->get()) * 10));
        // 50 points maximum
        $score += min(50, count($this->features()->wherePivot('checked', true)->get()) * 5);

        if ($this->firm_id) {
            $firm = $this->firm;

            // 26 points maximum ...
            foreach (self::SCORE_CONFIG['firm'] as $column => $point) {
                if (!empty($firm->{$column})) {
                    $score += $point;
                }
            }

            // 25 points maximum...
            $score += min(25, $firm->benefits()->count() * 5);
            $score -= ($firm->is_agency * 15);
        } else {
            $score -= 15;
        }

        return max(1, $score); // score can't be negative. 1 point min for elasticsearch algorithm
    }

    /**
     * Scope for currently active job offers
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopePriorDeadline($query)
    {
        return $query->where('deadline_at', '>', date('Y-m-d H:i:s'));
    }

    /**
     * @return HasMany
     */
    public function locations()
    {
        $instance = new Job\Location();

        return new HasMany($instance->newQuery(), $this, $instance->getTable() . '.' . $this->getForeignKey(), $this->getKeyName());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function page()
    {
        return $this->morphOne('Coyote\Page', 'content');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function firm()
    {
        return $this->belongsTo('Coyote\Firm');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo('Coyote\Currency');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function referers()
    {
        return $this->hasMany('Coyote\Job\Referer');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany('Coyote\Tag', 'job_tags')->withPivot(['priority', 'order']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function features()
    {
        return $this->belongsToMany('Coyote\Feature', 'job_features')->orderBy('order')->withPivot(['checked', 'value']);
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
    public function applications()
    {
        return $this->hasMany('Coyote\Job\Application');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function refers()
    {
        return $this->hasMany('Coyote\Job\Refer');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Coyote\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo('Coyote\Country');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()
    {
        return $this->hasMany('Coyote\Payment');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function commentsWithChildren()
    {
        return $this->comments()->whereNull('parent_id')->orderBy('id', 'DESC')->with('children.user:id,name,photo', 'user:id,name,photo');
    }

    /**
     * @param string $title
     */
    public function setTitleAttribute($title)
    {
        $title = trim($title);

        $this->attributes['title'] = $title;
        $this->attributes['slug'] = str_slug($title, '_');
    }

    /**
     * @param string $value
     */
    public function setSalaryFromAttribute($value)
    {
        $this->attributes['salary_from'] = $value === null ? null : (int) trim($value);
    }

    /**
     * @param string $value
     */
    public function setSalaryToAttribute($value)
    {
        $this->attributes['salary_to'] = $value === null ? null : (int) trim($value);
    }

    /**
     * @return int
     */
    public function getDeadlineAttribute()
    {
        return (new Carbon($this->deadline_at))->diff(Carbon::now(), false)->days;
    }

    /**
     * @return bool
     */
    public function getIsExpiredAttribute()
    {
        return (new Carbon($this->deadline_at))->isPast();
    }

    /**
     * @return mixed
     */
    public function getCityAttribute()
    {
        return $this->locations->implode('city', ', ');
    }

    /**
     * @return string
     */
    public function getCurrencySymbolAttribute()
    {
        return $this->currency()->value('symbol');
    }

    /**
     * @param int $userId
     */
    public function setDefaultUserId($userId)
    {
        if (empty($this->user_id)) {
            $this->user_id = $userId;
        }
    }

    /**
     * @param mixed $features
     */
    public function setDefaultFeatures($features)
    {
        if (!count($this->features)) {
            foreach ($features as $feature) {
                $pivot = $this->features()->newPivot(['checked' => $feature->checked, 'value' => $feature->value]);
                $this->features->add($feature->setRelation('pivot', $pivot));
            }
        }
    }

    /**
     * @param int $planId
     */
    public function setDefaultPlanId($planId)
    {
        if (empty($this->plan_id)) {
            $this->plan_id = $planId;
        }
    }

    /**
     * @return Payment
     */
    public function getUnpaidPayment()
    {
        return $this->payments()->where('status_id', Payment::NEW)->orderBy('created_at', 'DESC')->first();
    }

    /**
     * @return string|null
     */
    public function getPaymentUuid()
    {
        return $this->payments()->where('status_id', Payment::NEW)->orderBy('created_at', 'DESC')->first(['id']);
    }

    /**
     * @param string $url
     */
    public function addReferer($url)
    {
        if ($url && mb_strlen($url) < 200) {
            $referer = $this->referers()->firstOrNew(['url' => $url]);

            if (!$referer->id) {
                $referer->save();
            } else {
                $referer->increment('count');
            }
        }
    }

    /**
     * @return string
     */
    public function routeNotificationForTwilio()
    {
        return $this->phone;
    }

    /**
     * @return array
     */
    protected function getIndexBody()
    {
        $this->setCharFilter(JobFilter::class);
        $body = $this->parentGetIndexBody();

        // maximum offered salary
        $salary = $this->monthlySalary(max($this->salary_from, $this->salary_to));
        $body = array_except($body, ['deleted_at', 'features']);

        $locations = [];

        // We need to transform locations to format acceptable by elasticsearch.
        // I'm talking here about the coordinates
        /** @var \Coyote\Job\Location $location */
        foreach ($this->locations()->get(['city', 'longitude', 'latitude']) as $location) {
            $nested = ['city' => $location->city];

            if ($location->latitude && $location->longitude) {
                $nested['coordinates'] = [
                    'lat' => $location->latitude,
                    'lon' => $location->longitude
                ];
            }

            $locations[] = $nested;
        }

        // I don't know why elasticsearch skips documents with empty locations field when we use function_score.
        // That's why I add empty object (workaround).
        if (empty($locations)) {
            $locations[] = (object) [];
        }

        $body = array_merge($body, [
            // score must be int
            'score'             => (int) $body['score'],
            'locations'         => $locations,
            'salary'            => $salary,
            'salary_from'       => $this->monthlySalary($this->salary_from),
            'salary_to'         => $this->monthlySalary($this->salary_to),
            // yes, we index currency name so we don't have to look it up in database during search process
            'currency_symbol'   => $this->currency()->value('symbol'),
            // higher tag's priorities first
            'tags'              => $this->tags()->get(['name', 'priority'])->sortByDesc('pivot.priority')->pluck('name')->toArray(),
            // index null instead of 100 is job is not remote
            'remote_range'      => $this->is_remote ? $this->remote_range : null
        ]);

        if ($this->firm_id) {
            // logo is instance of File object. casting to string returns file name.
            // cast to (array) if firm is empty.
            $body['firm'] = array_map('strval', (array) array_only($this->firm->toArray(), ['name', 'logo', 'slug']));
        }

        return $body;
    }

    /**
     * @param float|null $salary
     * @return float|null
     */
    private function monthlySalary($salary)
    {
        if (empty($salary) || $this->rate_id === self::MONTH) {
            return $salary;
        }

        // we need to calculate monthly salary in order to sorting data by salary
        if ($this->rate_id == self::YEAR) {
            $salary = round($salary / 12);
        } elseif ($this->rate_id == self::WEEK) {
            $salary = round($salary * 4);
        } elseif ($this->rate_id == self::HOUR) {
            $salary = round($salary * 8 * 5 * 4);
        }

        return $salary;
    }
}
