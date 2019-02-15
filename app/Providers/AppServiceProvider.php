<?php

namespace Coyote\Providers;

use Coyote\Repositories\Contracts\SettingRepositoryInterface;
use Coyote\Services\FormBuilder\FormBuilder;
use Coyote\Services\FormBuilder\FormInterface;
use Coyote\Services\FormBuilder\ValidatesWhenSubmitted;
use Coyote\Services\Invoice;
use Coyote\User;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Redirector;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // set cloud flare as trusted proxy
        $this->app['request']->setTrustedProxies($this->app['config']->get('cloudflare.ip'), Request::HEADER_X_FORWARDED_ALL);
        // force HTTPS according to cloudflare HTTP_X_FORWARDED_PROTO header
        $this->app['request']->server->set(
            'HTTPS',
            $this->app['request']->server('HTTP_X_FORWARDED_PROTO') === 'https'
        );

        $this->app['validator']->extend('username', 'Coyote\Http\Validators\UserValidator@validateName');
        $this->app['validator']->extend('user_unique', 'Coyote\Http\Validators\UserValidator@validateUnique');
        $this->app['validator']->extend('user_exist', 'Coyote\Http\Validators\UserValidator@validateExist');
        $this->app['validator']->extend('password', 'Coyote\Http\Validators\PasswordValidator@validatePassword');
        $this->app['validator']->extend('reputation', 'Coyote\Http\Validators\ReputationValidator@validateReputation');
        $this->app['validator']->extend('spam_link', 'Coyote\Http\Validators\SpamValidator@validateSpamLink');
        $this->app['validator']->extend('spam_chinese', 'Coyote\Http\Validators\SpamValidator@validateSpamChinese');
        $this->app['validator']->extend('spam_foreign', 'Coyote\Http\Validators\SpamValidator@validateSpamForeignLink');
        $this->app['validator']->extend('spam_blacklist', 'Coyote\Http\Validators\SpamValidator@validateBlacklistHost');
        $this->app['validator']->extend('tag', 'Coyote\Http\Validators\TagValidator@validateTag');
        $this->app['validator']->extend('tag_creation', 'Coyote\Http\Validators\TagValidator@validateTagCreation');
        $this->app['validator']->extend('throttle', 'Coyote\Http\Validators\ThrottleValidator@validateThrottle');
        $this->app['validator']->extend('city', 'Coyote\Http\Validators\CityValidator@validateCity');
        $this->app['validator']->extend('wiki_unique', 'Coyote\Http\Validators\WikiValidator@validateUnique');
        $this->app['validator']->extend('wiki_route', 'Coyote\Http\Validators\WikiValidator@validateRoute');
        $this->app['validator']->extend('email_unique', 'Coyote\Http\Validators\EmailValidator@validateUnique');
        $this->app['validator']->extend('email_confirmed', 'Coyote\Http\Validators\EmailValidator@validateConfirmed');
        $this->app['validator']->extend('cc_number', 'Coyote\Http\Validators\CreditCardValidator@validateNumber');
        $this->app['validator']->extend('cc_cvc', 'Coyote\Http\Validators\CreditCardValidator@validateCvc');
        $this->app['validator']->extend('cc_date', 'Coyote\Http\Validators\CreditCardValidator@validateDate');
        $this->app['validator']->extend('host', 'Coyote\Http\Validators\HostValidator@validateHost');

        $this->app['validator']->replacer('reputation', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':point', $parameters[0], $message);
        });

        $this->app['validator']->replacer('spam_link', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':point', $parameters[0], $message);
        });

        $this->app['validator']->replacer('spam_foreign', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':posts', $parameters[0], $message);
        });

        $this->app['validator']->replacer('tag_creation', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':point', $parameters[0], $message);
        });

        $this->app['validator']->replacer('host', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':host', implode(', ', $parameters), $message);
        });

        $this->registerMacros();
        Paginator::useBootstrapThree();
    }

    /**
     * Register any application services.
     *
     * This service provider is a great spot to register your various container
     * bindings with the application. As you can see, we are registering our
     * "Registrar" implementation here. You can add your own bindings too!
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('setting', function ($app) {
            return new $app[SettingRepositoryInterface::class]($app);
        });

        $this->app->singleton('form.builder', function ($app) {
            return new FormBuilder($app);
        });

        $this->app['events']->listen(RouteMatched::class, function () {
            $this->app->resolving(FormInterface::class, function (FormInterface $form, $app) {
                $form->setContainer($app)
                    ->setRedirector($app->make(Redirector::class))
                    ->setRequest($app->make('request'));

                if ($form instanceof ValidatesWhenSubmitted && $form->isSubmitted()) {
                    $form->buildForm();
                    $form->validate();
                }
            });
        });

        $this->app->resolving(Invoice\Pdf::class, function (Invoice\Pdf $pdf, $app) {
            $pdf->setVendor($app['config']->get('vendor'));
        });
    }

    private function registerMacros()
    {
        Collection::macro('flush', function () {
            $this->items = [];
        });

        Collection::macro('replace', function ($items) {
            $this->items = $items;
        });

        Collection::macro('exceptUser', function (User $auth = null) {
            if ($auth === null) {
                return $this;
            }

            return $this->filter(function (User $user) use ($auth) {
                return $user->id !== $auth->id;
            });
        });

        Collection::macro('exceptUsers', function ($others = []) {
            if (!($others instanceof Collection)) {
                $others = collect($others);
            }

            if (!count($others)) {
                return $this;
            }

            return $this->filter(function (User $user) use ($others) {
                return ! $others->contains('id', $user->id);
            });
        });

        Collection::macro('groupCategory', function () {
            /** @var \Illuminate\Support\Collection $this */
            $collection = $this
                ->sortBy('category.id')
                ->groupBy(function ($item) {
                    return $item->category ? $item->category->name : 'Inne';
                });

            if (isset($collection['Inne'])) {
                // move category at the end
                $collection->put('Inne', $collection->splice(0, 1)['Inne']);
            }

            return $collection;
        });

        Request::macro('getClientHost', function () {
            if (app()->environment() !== 'production') {
                return '';
            }

            if (empty($this->clientHost)) {
                $this->clientHost = gethostbyaddr($this->ip());
            }

            return $this->clientHost;
        });
    }
}
