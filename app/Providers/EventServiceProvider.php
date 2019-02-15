<?php

namespace Coyote\Providers;

use Coyote\Events\FirewallWasDeleted;
use Coyote\Events\FirewallWasSaved;
use Coyote\Events\ForumWasSaved;
use Coyote\Events\StreamSaved;
use Coyote\Events\SuccessfulLogin;
use Coyote\Events\UserWasSaved;
use Coyote\Listeners\ActivitySubscriber;
use Coyote\Listeners\ChangeImageUrl;
use Coyote\Listeners\FlushFirewallCache;
use Coyote\Listeners\FlushUserCache;
use Coyote\Listeners\IndexCategory;
use Coyote\Listeners\IndexStream;
use Coyote\Listeners\LogSentMessage;
use Coyote\Listeners\MicroblogListener;
use Coyote\Listeners\SaveLocationsInJobPreferences;
use Coyote\Listeners\SendLockoutEmail;
use Coyote\Listeners\SendSuccessfulLoginEmail;
use Coyote\Listeners\SetupLoginDate;
use Coyote\Listeners\SetupWikiLinks;
use Coyote\Listeners\WikiListener;
use Coyote\Listeners\PageListener;
use Coyote\Listeners\PostListener;
use Coyote\Listeners\TopicListener;
use Coyote\Listeners\JobListener;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        UserWasSaved::class => [FlushUserCache::class, SaveLocationsInJobPreferences::class],
        Lockout::class => [SendLockoutEmail::class],
        FirewallWasSaved::class => [FlushFirewallCache::class],
        FirewallWasDeleted::class => [FlushFirewallCache::class],
        SuccessfulLogin::class => [SendSuccessfulLoginEmail::class],
        Login::class => [SetupLoginDate::class],
        MessageSending::class => [ChangeImageUrl::class, LogSentMessage::class],
        ForumWasSaved::class => [IndexCategory::class],
        StreamSaved::class => [IndexStream::class]
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        PageListener::class,
        PostListener::class,
        TopicListener::class,
        JobListener::class,
        MicroblogListener::class,
        WikiListener::class,
        SetupWikiLinks::class,
        ActivitySubscriber::class
    ];

    /**
     * Register any other events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
