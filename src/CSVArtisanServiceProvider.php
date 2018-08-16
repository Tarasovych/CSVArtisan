<?php

namespace Tarasovych\CSVArtisan;

use Illuminate\Support\ServiceProvider;
use Tarasovych\CSVArtisan\Commands\CSVImport;

class CSVArtisanServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CSVImport::class
            ]);
        }
    }
}