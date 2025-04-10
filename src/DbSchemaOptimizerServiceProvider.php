<?php

namespace Nikunj\DbSchemaOptimizer;

use Illuminate\Support\ServiceProvider;
use Nikunj\DbSchemaOptimizer\Console\Commands\DbScanCommand;

class DbSchemaOptimizerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish configuration (optional for now)
    }

    public function register()
    {
        $this->app->singleton(SchemaOptimizer::class, function () {
            return new SchemaOptimizer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([DbScanCommand::class]);
        }
    }
}
