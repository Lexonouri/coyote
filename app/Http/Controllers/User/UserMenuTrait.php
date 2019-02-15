<?php

namespace Coyote\Http\Controllers\User;

use Lavary\Menu\Menu;

/**
 * This trait is being shared by user controllers and profile controller
 *
 * @package Coyote\Http\Controllers\User
 */
trait UserMenuTrait
{
    /**
     * @return mixed
     */
    public function getUserMenu()
    {
        return app(Menu::class)->make('user.top', function ($menu) {
            if (auth()->check()) {
                $menu->add('Moje konto', ['route' => 'user.home'])->nickname('user.home');
                $menu->add('Ustawienia', ['route' => 'user.settings'])->nickname('user.settings');
                $menu->add('Profil', ['route' => ['profile', auth()->user()->id]])->nickname('profile');
            }
        });
    }
}
