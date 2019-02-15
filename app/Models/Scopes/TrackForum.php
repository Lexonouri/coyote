<?php

namespace Coyote\Models\Scopes;

use Coyote\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;

trait TrackForum
{
    /**
     * Scope a query to only given user id.
     *
     * @param Builder $builder
     * @param string $guestId
     * @return Builder
     */
    public function scopeTrackForum(Builder $builder, string $guestId)
    {
        return $builder
            ->addSelect(['forum_track.marked_at AS forum_marked_at'])
            ->leftJoin('forum_track', function (JoinClause $join) use ($guestId) {
                $join
                    ->on('forum_track.forum_id', '=', 'forums.id')
                    ->on('forum_track.guest_id', '=', new Str($guestId));
            });
    }
}
