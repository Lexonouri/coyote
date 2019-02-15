<?php

namespace Coyote\Providers;

use Coyote\Services\Parser\Factories\JobCommentFactory;
use Coyote\Services\Parser\Factories\WikiFactory;
use Illuminate\Support\ServiceProvider;
use Coyote\Services\Parser\Factories\MicroblogFactory;
use Coyote\Services\Parser\Factories\SigFactory;
use Coyote\Services\Parser\Factories\PmFactory;
use Coyote\Services\Parser\Factories\PostFactory;
use Coyote\Services\Parser\Factories\CommentFactory;
use Coyote\Services\Parser\Factories\JobFactory;

class ParserServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('parser.microblog', function ($app) {
            return new MicroblogFactory($app);
        });

        $this->app->singleton('parser.sig', function ($app) {
            return new SigFactory($app);
        });

        $this->app->singleton('parser.pm', function ($app) {
            return new PmFactory($app);
        });

        $this->app->singleton('parser.post', function ($app) {
            return new PostFactory($app);
        });

        $this->app->singleton('parser.comment', function ($app) {
            return new CommentFactory($app);
        });

        $this->app->singleton('parser.job', function ($app) {
            return new JobFactory($app);
        });

        $this->app->singleton('parser.wiki', function ($app) {
            return new WikiFactory($app);
        });

        $this->app->singleton('parser.job.comment', function ($app) {
            return new JobCommentFactory($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        /*
         * UWAGA! Po dodaniu nowego elementu do tablicy trzeba wykonac php artisan clear-compiled
         */
        return [
            'parser.microblog',
            'parser.sig',
            'parser.pm',
            'parser.post',
            'parser.comment',
            'parser.job',
            'parser.job.comment',
            'parser.wiki'
        ];
    }
}
