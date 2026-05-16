<?php
namespace Webduo\LaravelModuleGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeModuleCommand extends Command
{
    protected $signature = '
        make:module
        {name}
        --create=* : Create migration tables}
    ';
    
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
            'Controllers/Apis',
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
        $this->createControllers($parts, $modulePath);
        $this->createMigrations($modulePath);

        $this->info("Module {$name} created successfully.");
    }

    protected function createServiceProvider($parts, $modulePath)
    {
        $moduleName = end($parts);
        $namespace = 'App\\Modules\\' . implode('\\', $parts) . '\\Providers';

        $module_slug = strtolower($moduleName);

        $content = "<?php
namespace {$namespace};

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class {$moduleName}ServiceProvider extends ServiceProvider{

    public function register(){

        if(file_exists(__DIR__ . '/../Config/config.php')){

            \$this->mergeConfigFrom(
                __DIR__ . '/../Config/config.php',
                'modules.$module_slug'
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

        \$this->loadMigrationsFrom(
            __DIR__ . '/../Database/Migrations'
        );
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

    protected function createControllers($parts, $modulePath){

        $moduleName = end($parts) . 'Controller';
        $namespace = 'App\\Modules\\' . implode('\\', $parts) . '\\Controllers\Apis';

        $content = "<?php
namespace {$namespace};

use App\Modules\Base\Controllers\Apis\BaseController;
use Illuminate\Http\Request;

class {$moduleName} extends BaseController{

    function __construct(){

    }
}";

        File::put($modulePath . "/Controllers/Apis/{$moduleName}.php", $content);
    }

    protected function createRoutes($modulePath){

        File::put($modulePath . '/Routes/api.php', "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n");
    }

    protected function createConfig($modulePath){

        File::put($modulePath . '/Config/config.php', "<?php\n\nreturn [];\n");
        File::put($modulePath . '/Config/app_handle.php', "<?php\n\nreturn [];\n");
    }

    protected function createMigrations($modulePath){

        $tables = $this->option('create');

        if(empty($tables)){
        
            return;
        }

        $migrationPath = $modulePath . '/Database/Migrations';

        foreach($tables as $table){

            $migrationName = 'create_' . $table . '_table';

            $timestamp = now()->format('Y_m_d_His');

            $filename = $timestamp . '_' . $migrationName . '.php';

            sleep(1);

            $content = $this->getMigrationStub($table);

            file_put_contents(
                $migrationPath . '/' . $filename,
                $content
            );
        }
    }

    protected function getMigrationStub($table){

        return "<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('row_id');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
";
    }
}