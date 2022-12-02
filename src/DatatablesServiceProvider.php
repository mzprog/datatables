<?php

namespace Mzprog\Datatables;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class DatatablesServiceProvider extends ServiceProvider{

    public function boot()
    {
        
        $this->publishes([
            __DIR__.'/../config/datatables.php' => App::configPath('datatables.php'),
        ]);
        $this->mergeConfigFrom(
            __DIR__.'/../config/datatables.php', 'datatables'
        );

        $this->loadViewsFrom(__DIR__.'/../views', 'datatables');
        $this->publishes([
            __DIR__.'/../views' => App::resourcePath('views/vendor/datatables'),
        ]);
    }
}