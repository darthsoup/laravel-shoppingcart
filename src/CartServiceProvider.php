<?php

namespace DarthSoup\Cart;

use Illuminate\Support\ServiceProvider;

/**
 * Class CartServiceProvider
 */
class CartServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cart', function ($app) {
            return new Cart($app['session'], $app['events']);
        });
    }
}
