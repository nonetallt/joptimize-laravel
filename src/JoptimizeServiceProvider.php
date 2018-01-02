<?php

namespace Nonetallt\Joptimize\Laravel;

use Illuminate\Support\ServiceProvider;

class JoptimizeServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/joptimize.php' => config_path('joptimize.php'),
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/joptimize.php', 'joptimize');

        if($this->app->runningInConsole()) {
            $this->commands([
                JoptimizeCommand::class
            ]);
        }
    }
}
