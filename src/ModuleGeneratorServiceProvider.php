<?php
namespace Webduo\LaravelModuleGenerator;

use Illuminate\Support\ServiceProvider;
use Webduo\LaravelModuleGenerator\Commands\MakeModuleCommand;

class ModuleGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            MakeModuleCommand::class,
        ]);
    }
}