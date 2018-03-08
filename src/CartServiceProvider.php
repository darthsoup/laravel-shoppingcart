<?php

namespace DarthSoup\Cart;

use Illuminate\Support\ServiceProvider;

/**
 * Class CartServiceProvider.
 */
class CartServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            dirname(__DIR__).'/config/cart.php' => config_path('cart.php'),
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/cart.php', 'cart');

        $this->app->singleton('cart.hash', function ($app) {
            $config = $app['config'];
            $class = $config->get('cart')['hasher'];

            return new $class;
        });

        $this->app->singleton('cart', function ($app) {
            return new Cart($app['session'], $app['events'], $app['cart.hash']);
        });
    }
}
