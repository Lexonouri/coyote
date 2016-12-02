<?php

namespace Coyote\Events;

use Illuminate\Queue\SerializesModels;

class UserWasSaved extends Event
{
    use SerializesModels;

    public $user;

    /**
     * Create a new event instance.
     *
     * @param \Coyote\User $user
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
