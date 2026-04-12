<?php
namespace Webduo\LaravelModuleGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name}';
    protected $description = 'Create a new module';

    public function handle()
    {
        $name = $this->argument('name');

        $parts = explode('/', $name);
        $modulePath = app_path('Modules/' . implode('/', $parts));

        if(File::exists($modulePath)){

            $this->error('Module already exists!');
            return;
        }

        $folders = [
            'Controllers',
            'Services',
            'Models',
            'Registry',
            'Config',
            'Routes',
            'Providers',
            'Database/Migrations',
            'Database/Seeders',
        ];

        foreach($folders as $folder){

            File::makeDirectory($modulePath . '/' . $folder, 0755, true);
        }

        $this->createServiceProvider($parts, $modulePath);
        $this->createRoutes($modulePath);
        $this->createConfig($modulePath);
        $this->createModels($parts, $modulePath);

        $this->info("Module {$name} created successfully.");
    }

    protected function createServiceProvider($parts, $modulePath)
    {
        $moduleName = end($parts);
        $namespace = 'App\\Modules\\' . implode('\\', $parts) . '\\Providers';

        $content = "<?php
namespace {$namespace};

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class {$moduleName}ServiceProvider extends ServiceProvider{

    public function register(){

        if(file_exists(__DIR__ . '/../Config/config.php')){

            \$this->mergeConfigFrom(
                __DIR__ . '/../Config/config.php',
                'modules.cms'
            );
        }

        if(file_exists(__DIR__ . '/../Config/app_handle.php')){

            \$moduleConfig = require __DIR__ . '/../Config/app_handle.php';

            \$existing = config('app_handle', []);
            
            config([
                'app_handle' => array_merge(\$existing, \$moduleConfig)
            ]);
        }
    }

    public function boot(){

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}";

        File::put($modulePath . "/Providers/{$moduleName}ServiceProvider.php", $content);
    }

    protected function createModels($parts, $modulePath){

        $moduleName = end($parts) . 'Model';
        $namespace = 'App\\Modules\\' . implode('\\', $parts) . '\\Models';

        $content = "<?php
namespace {$namespace};

use App\Modules\Base\Models\BaseModel;

class {$moduleName} extends BaseModel{

    protected \$table = 'TABLE_NAME';

    function __construct(){
        
        \$this->setTableIndex('row_id');
		\$this->setTableName('TABLE_NAME');
    }

    public function format(\$row = false){
    
        if(\$row){

            \$row->added_on_formatted = \$this->format_date(\$row->added_on, 'datetime');
        }

        return \$row;
    }
}";

        File::put($modulePath . "/Models/{$moduleName}.php", $content);
    }

    protected function createRoutes($modulePath){

        File::put($modulePath . '/Routes/api.php', "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n");
    }

    protected function createConfig($modulePath){

        File::put($modulePath . '/Config/config.php', "<?php\n\nreturn [];\n");
        File::put($modulePath . '/Config/app_handle.php', "<?php\n\nreturn [];\n");
    }
}