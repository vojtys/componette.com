<?php

namespace App\Model\Routing;

use App\Model\Routing\Helpers\AddonsHelper;
use Nette\Application\IRouter;
use Nette\Application\Routers\CliRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Http\Request;

final class RouterFactory
{

    /** @var AddonsHelper @inject */
    public $addonsHelper;

    /** @var Request @inject */
    public $httpRequest;

    /**
     * @return IRouter
     */
    public function create()
    {
        if (PHP_SAPI === 'cli') {
            return $this->createCli();
        } else {
            return $this->createWeb();
        }
    }

    /**
     * @return RouteList
     */
    protected function createCli()
    {
        $router = new RouteList('Cli');
        $router[] = new CliRouter(['action' => 'Cli:hi']);

        return $router;
    }

    /**
     * @return RouteList
     */
    protected function createWeb()
    {
        $router = new RouteList();

        if ($this->httpRequest->isSecured()) {
            Route::$defaultFlags = Route::SECURED;
        }

        // ADMIN ===========================================

        $router[] = $admin = new RouteList('Admin');
        $admin[] = new Route('admin/<presenter>/<action>[/<id>]', 'Home:default');

        // FRONT ===========================================

        $router[] = $front = new RouteList('Front');

        // FRONT.API =======================================

        $front[] = $api = new RouteList('Api');
        $api[] = new Route('api/v1/opensearch/suggest', 'OpenSearch:suggest');

        // FRONT.PORTAL ====================================

        $front[] = $portal = new RouteList('Portal');
        $portal[] = new Route('sitemap.xml', 'Generator:sitemap');
        $portal[] = new Route('opensearch.xml', 'Generator:opensearch');
        $portal[] = new Route('rss/new.xml', 'Rss:newest');

        $portal[] = new Route('<slug [a-zA-Z0-9\-\.]+/[a-zA-Z0-9\-\.]+>/', [
            'presenter' => 'Addon',
            'action' => 'detail',
            'slug' => [
                Route::FILTER_IN => [$this->addonsHelper, 'addonIn'],
                Route::FILTER_OUT => [$this->addonsHelper, 'addonOut'],
            ],
        ]);
        $portal[] = new Route('<slug [a-zA-Z0-9\-\.]+>/', [
            'presenter' => 'List',
            'action' => 'owner',
            'slug' => [
                Route::FILTER_IN => [$this->addonsHelper, 'ownerIn'],
                Route::FILTER_OUT => [$this->addonsHelper, 'ownerOut'],
            ],
        ]);
        $portal[] = new Route('', 'Home:default');
        $portal[] = new Route('all/', 'List:default');
        $portal[] = new Route('all/<by>/', 'List:sorted');
        $portal[] = new Route('search/', 'List:search');
        $portal[] = new Route('search/<tag>', 'List:tag');
        $portal[] = new Route('status/', 'Status:default');

        $portal[] = new Route('imgs/<action>/<owner [\w\-\/]+>.[!<ext=png>]', [
            'presenter' => 'WebImage',
            'action' => 'default',
            'owner' => [
                Route::FILTER_OUT => function ($owner) {
                    return strtolower($owner);
                },
                Route::FILTER_IN => function ($owner) {
                    return strtolower($owner);
                },
            ],
        ]);

        // COMMON SCHEME
        $front[] = new Route('<presenter>/<action>', 'Home:default');

        return $router;
    }
}
