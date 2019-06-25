<?php

namespace Dcat\Admin;

use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\SectionManager;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $commands = [
        Console\AdminCommand::class,
        Console\InstallCommand::class,
        Console\PublishCommand::class,
        Console\UninstallCommand::class,
        Console\ImportCommand::class,
        Console\CreateUserCommand::class,
        Console\ResetPasswordCommand::class,
        Console\ExtendCommand::class,
        Console\ExportSeedCommand::class,
        Console\IdeHelperCommand::class
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'admin.auth'       => Middleware\Authenticate::class,
        'admin.pjax'       => Middleware\Pjax::class,
        'admin.log'        => Middleware\LogOperation::class,
        'admin.permission' => Middleware\Permission::class,
        'admin.bootstrap'  => Middleware\Bootstrap::class,
        'admin.session'    => Middleware\Session::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'admin' => [
            'admin.auth',
            'admin.pjax',
            'admin.log',
            'admin.bootstrap',
            'admin.permission',
            'admin.session',
        ],
    ];

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'admin');

        if (config('admin.https') || config('admin.secure')) {
            \URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', true);
        }

        if (is_file($routes = admin_path('routes.php'))) {
            $this->loadRoutesFrom($routes);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config' => config_path()], 'dcat-admin-config');
            $this->publishes([__DIR__.'/../resources/lang' => resource_path('lang')], 'dcat-admin-lang');
            $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'dcat-admin-migrations');
            $this->publishes([__DIR__.'/../resources/assets' => public_path('vendor/dcat-admin')], 'dcat-admin-assets');
        }

        //remove default feature of double encoding enable in laravel 5.6 or later.
        $bladeReflectionClass = new \ReflectionClass('\Illuminate\View\Compilers\BladeCompiler');
        if ($bladeReflectionClass->hasMethod('withoutDoubleEncoding')) {
            Blade::withoutDoubleEncoding();
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        require_once __DIR__ . '/Support/AdminSection.php';

        $this->registerExtensionProviders();
        $this->loadAdminAuthConfig();
        $this->registerRouteMiddleware();
        $this->registerService();
        $this->registerDefaultSections();

        $this->commands($this->commands);
    }

    /**
     * Register the service provider of extensions.
     */
    public function registerExtensionProviders()
    {
        foreach (Admin::getAvailableExtensions() as $extension) {
            if ($provider = $extension->serviceProvider()) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * Setup auth configuration.
     *
     * @return void
     */
    protected function loadAdminAuthConfig()
    {
        config(Arr::dot(config('admin.auth', []), 'auth.'));
    }

    /**
     * Register default sections.
     */
    protected function registerDefaultSections()
    {
        Content::composing(function () {
            if (!admin_has_default_section(\AdminSection::NAVBAR_USER_PANEL)) {
                admin_inject_default_section(\AdminSection::NAVBAR_USER_PANEL, view('admin::partials.navbar-user-panel'));
            }

            if (!admin_has_default_section(\AdminSection::LEFT_SIDEBAR_USER_PANEL)) {
                admin_inject_default_section(\AdminSection::LEFT_SIDEBAR_USER_PANEL, view('admin::partials.sidebar-user-panel'));
            }

            // Register menu
            Admin::menu()->register();

        }, true);
    }

    protected function registerService()
    {
        $this->app->singleton('sectionManager', SectionManager::class);
    }

    /**
     * Register the route middleware.
     *
     * @return void
     */
    protected function registerRouteMiddleware()
    {
        $router = $this->app->make('router');

        // register route middleware.
        foreach ($this->routeMiddleware as $key => $middleware) {
            $router->aliasMiddleware($key, $middleware);
        }

        $disablePermission = !config('admin.permission.enable');

        // register middleware group.
        foreach ($this->middlewareGroups as $key => $middleware) {
            if ($disablePermission && $middleware == 'admin.permission') {
                continue;
            }
            $router->middlewareGroup($key, $middleware);
        }
    }
}