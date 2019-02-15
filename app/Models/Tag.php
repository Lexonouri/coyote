<?php

namespace Coyote;

use Coyote\Services\Media\Factory as MediaFactory;
use Coyote\Services\Media\Logo;
use Coyote\Services\Media\MediaInterface;
use Coyote\Services\Media\SerializeClass;
use Coyote\Tag\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $real_name
 * @property int $category_id
 * @property Category $category
 * @property MediaInterface $logo
 */
class Tag extends Model
{
    use SoftDeletes, SerializeClass;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'real_name', 'category_id'];

    /**
     * @var array
     */
    protected $dates = ['created_at', 'deleted_at'];

    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:se';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @param string $value
     * @return \Coyote\Services\Media\MediaInterface
     */
    public function getLogoAttribute($value)
    {
        if (!($value instanceof Logo)) {
            $logo = app(MediaFactory::class)->make('logo', ['file_name' => $value]);
            $this->attributes['logo'] = $logo;
        }

        return $this->attributes['logo'];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
