<?php

namespace Coyote\Http\Controllers\Adm;

use Coyote\Http\Controllers\Controller;
use Lavary\Menu\Menu;

/**
 * Class BaseController
 * @package Coyote\Http\Controllers\Adm
 */
class BaseController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->breadcrumb->push('Panel administracyjny', route('adm.home'));
    }

    /**
     * @return \Lavary\Menu\Builder
     */
    protected function buildMenu()
    {
        return $this->getMenuFactory()->make('adm', function ($menu) {
            $html = app('html');
            $fa = function ($icon) use ($html) {
                return $html->tag('i', '', ['class' => "fa $icon"]);
            };

            /** @var \Lavary\Menu\Builder $menu */
            $menu->add('Strona główna', ['route' => 'adm.dashboard'])->prepend($fa('fa-desktop fa-fw'));
            $menu->add('Użytkownicy', ['route' => 'adm.users'])->prepend($fa('fa-user fa-fw'));
            $menu->add('Grupy', ['route' => 'adm.groups'])->prepend($fa('fa-users fa-fw'))->data('permission', 'adm-group');
            $menu->add('Bany', ['route' => 'adm.firewall'])->prepend($fa('fa-ban fa-fw'));
            $menu->add('Kto jest online', ['route' => 'adm.sessions'])->prepend($fa('fa-eye fa-fw'));

            $forum = $menu->add('Forum', []);
            $forum->link->attr(['data-toggle' => "collapse", 'aria-expanded' => "false", 'aria-controls' => "menu-forum"]);
            $forum->link->href('#menu-forum');

            $forum->prepend($fa('fa-comments fa-fw'));
            $forum->append($html->tag('i', '', ['class' => 'arrow fa fa-angle-left pull-right']));

            $forum->add('Kategorie', ['route' => 'adm.forum.categories']);
            $forum->add('Uprawnienia', ['route' => 'adm.forum.permissions'])->data('permission', 'adm-group');
            $forum->add('Powody moderacji', ['route' => 'adm.forum.reasons']);

            $menu->add('Dziennik zdarzeń', ['route' => 'adm.stream'])->prepend($fa('fa-newspaper-o fa-fw'));
            $menu->add('Raporty', ['route' => 'adm.flag'])->prepend($fa('fa-flag fa-fw'));
            $menu->add('Cenzura', ['route' => 'adm.words'])->prepend($fa('fa-flash fa-fw'));
            $menu->add('Bloki statyczne', ['route' => 'adm.blocks'])->prepend($fa('fa-columns fa-fw'));

            $log = $menu->add('Logi', ['route' => 'adm.log'])->prepend($fa('fa-file-o fa-fw'));
            $log->link->attr(['data-toggle' => "collapse", 'aria-expanded' => "false", 'aria-controls' => "menu-log"]);
            $log->link->href('#menu-log');

            $logViewer = $this->getLogViewer();
            $files = $logViewer->getFiles();

            foreach ($files as $file) {
                $log->add($file, route('adm.log', ['file' => $file]));
            }

            $menu->add('Faktury i płatności', ['route' => 'adm.payments'])->prepend($fa('fa-shopping-cart fa-fw'))->data('permission', 'adm-payment');
            $menu->add('Tagi', ['route' => 'adm.tags'])->prepend($fa('fa-tag fa-fw'));
            $menu->add('Mailing', ['route' => 'adm.mailing'])->prepend($fa('fa-envelope fa-fw'));
        })
        ->filter(function ($item) {
            if ($item->data('permission')) {
                return auth()->user()->can($item->data('permission'));
            }

            return true;
        });
    }

    /**
     * @inheritdoc
     */
    protected function view($view = null, $data = [])
    {
        return parent::view($view, array_merge($data, ['menu' => $this->buildMenu()]));
    }

    /**
     * @return Menu
     */
    protected function getMenuFactory()
    {
        return app(Menu::class);
    }

    /**
     * @return \Coyote\Services\LogViewer\LogViewer
     */
    protected function getLogViewer()
    {
        return app('log-viewer');
    }

    /**
     * Clear users cache permission after updating groups etc.
     */
    protected function flushPermission()
    {
        $this->getCacheFactory()->tags('permissions')->flush();
    }
}
