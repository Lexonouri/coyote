<?php

namespace Coyote\Topic;

use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_id', 'forum_id', 'marked_at', 'guest_id'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'topic_track';

    /**
     * @var array
     */
    public $timestamps = false;
}
