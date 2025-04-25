<?php

namespace Ndkumarawansha\NdkCrud;

use Illuminate\Support\ServiceProvider;
use Ndkumarawansha\NdkCrud\Commands\MakeNdkCrudCommand;

class NdkCrudServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the command if we are using the application via the CLI
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeNdkCrudCommand::class,
            ]);
        }
    }
}