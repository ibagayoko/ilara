<?php

namespace App\Providers;

use Blueprint\Blueprint;
use Illuminate\Support\ServiceProvider;

class BlueprintServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->extend(Blueprint::class, function ($blue, $app) {

            $blue->registerGenerator(new \App\Blueprint\Generators\GraphQLTypeGenerator($app['files']));
            $blue->registerGenerator(new \App\Blueprint\Generators\GraphQLQueryGenerator($app['files']));
            $blue->registerGenerator(new \App\Blueprint\Generators\GraphQLQueryPlurialGenerator($app['files']));
            $blue->registerGenerator(new \App\Blueprint\Generators\GraphQLSchemaGenerator($app['files']));

    
            return $blue;
        });
      
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
