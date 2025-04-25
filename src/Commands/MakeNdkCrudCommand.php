<?php

namespace Ndkumarawansha\NdkCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeNdkCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:ndk-crud {model : The name of the model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CRUD operations for a model (migration, model, routes, controller)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $model = $this->argument('model');
        $modelPath = app_path("Models/{$model}.php");

        if (File::exists($modelPath)) {
            $this->error("Model '{$model}' already exists!");
            return Command::FAILURE;
        }
        
        // Ask for table columns
        $columns = $this->askForColumns();
        
        // Create migration
        $this->createMigration($model, $columns);
        
        // Create model
        $this->createModel($model, $columns);
        
        // Create controller
        $this->createController($model);
        
        // Add routes
        $this->addRoutes($model);
        
        $this->info('CRUD operations created successfully!');
    }
    
    /**
     * Ask for table columns and data types.
     */
    protected function askForColumns()
    {
        $columns = [];
        $this->info('Enter the columns for your table (press enter with empty name to finish):');
        
        while (true) {
            $columnName = $this->ask('Column name (or press enter to finish)');
            
            if (empty($columnName)) {
                break;
            }
            
            $dataType = $this->choice(
                "Data type for '$columnName'",
                [
                    'string', 'integer', 'bigInteger', 'boolean', 'text', 
                    'date', 'dateTime', 'decimal', 'float', 'json'
                ]
            );
            
            $nullable = $this->confirm("Is '$columnName' nullable?", false);
            
            $columns[] = [
                'name' => $columnName,
                'type' => $dataType,
                'nullable' => $nullable
            ];
        }
        
        return $columns;
    }
    
    /**
     * Create migration file.
     */
    protected function createMigration($model, $columns)
    {
        $tableName = Str::plural(Str::snake($model));
        $timestamp = date('Y_m_d_His');
        $migrationName = $timestamp . "_create_{$tableName}_table.php";
        $migrationPath = database_path("migrations/{$migrationName}");
        
        $stub = file_get_contents(__DIR__ . '/../stubs/migration.stub');
        
        $replacements = [
            '{{ tableNamePluralLowerCase }}' => $tableName,
            '{{ tableColumns }}' => $this->getTableColumns($columns),
        ];
        
        $migrationContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );
        
        File::put($migrationPath, $migrationContent);
        
        $this->info("Migration created: {$migrationName}");
    }
    
    /**
     * Get table columns for migration.
     */
    protected function getTableColumns($columns)
    {
        $columnDefinitions = '';
        
        foreach ($columns as $column) {
            $definition = "\$table->{$column['type']}('{$column['name']}')";
            
            if ($column['nullable']) {
                $definition .= "->nullable()";
            }
            
            $columnDefinitions .= "            {$definition};\n";
        }
        
        return $columnDefinitions;
    }
    
    /**
     * Create model file.
     */
    protected function createModel($model, $columns)
    {
        $modelPath = app_path("Models/{$model}.php");
        
        // Create Models directory if it doesn't exist
        if (!File::isDirectory(app_path('Models'))) {
            File::makeDirectory(app_path('Models'), 0755, true);
        }
        
        $stub = file_get_contents(__DIR__ . '/../stubs/model.stub');
        
        $fillableColumns = array_map(function($column) {
            return "'" . $column['name'] . "'";
        }, $columns);
        
        $replacements = [
            '{{ modelName }}' => $model,
            '{{ tableNamePluralLowerCase }}' => Str::plural(Str::snake($model)),
            '{{ fillableColumns }}' => implode(', ', $fillableColumns),
        ];
        
        $modelContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );
        
        File::put($modelPath, $modelContent);
        
        $this->info("Model created: {$model}.php");
    }
    
    /**
     * Create controller file.
     */
    protected function createController($model)
    {
        $controllerName = "{$model}Controller";
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");
        
        // Create Controllers directory if it doesn't exist
        if (!File::isDirectory(app_path('Http/Controllers'))) {
            File::makeDirectory(app_path('Http/Controllers'), 0755, true);
        }
        
        $stub = file_get_contents(__DIR__ . '/../stubs/controller.stub');
        
        $replacements = [
            '{{ modelName }}' => $model,
            '{{ modelNameLowerCase }}' => Str::camel($model),
            '{{ modelNamePluralLowerCase }}' => Str::plural(Str::camel($model)),
            '{{ controllerName }}' => $controllerName,
        ];
        
        $controllerContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );
        
        File::put($controllerPath, $controllerContent);
        
        $this->info("Controller created: {$controllerName}.php");
    }
    
    /**
     * Add routes.
     */
    protected function addRoutes($model)
    {
        $routesPath = base_path('routes/web.php');
        $stub = file_get_contents(__DIR__ . '/../stubs/routes.stub');
        
        $modelNamePluralLowerCase = Str::plural(Str::camel($model));
        $controllerName = "{$model}Controller";
        
        $replacements = [
            '{{ modelNamePluralLowerCase }}' => $modelNamePluralLowerCase,
            '{{ controllerName }}' => $controllerName,
        ];
        
        $routesContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );
        
        // Append the routes to the web.php file
        File::append($routesPath, "\n" . $routesContent);
        
        $this->info("Routes added for {$model}");
    }
}