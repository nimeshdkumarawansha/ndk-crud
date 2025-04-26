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

        // Create Facade
        $this->createFacade($model);

        // Create Service
        $this->createService($model);

        // Add routes
        $this->addRoutes($model);

        // Update composer.json and run dump-autoload
        $this->updateComposerAutoload();

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
                    'string',
                    'integer',
                    'bigInteger',
                    'boolean',
                    'text',
                    'date',
                    'dateTime',
                    'decimal',
                    'float',
                    'json'
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

        $fillableColumns = array_map(function ($column) {
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
     * Create Facade file.
     */
    protected function createFacade($model)
    {
        $facadeName = "{$model}Facade";
        $facadePath = "domain/Facades/{$facadeName}/{$facadeName}.php";

        // Create Facades directory if it doesn't exist
        if (!File::isDirectory('domain/Facades/'. $facadeName)) {
            File::makeDirectory('domain/Facades/'. $facadeName, 0755, true);
        }

        $stub = file_get_contents(__DIR__ . '/../stubs/facade.stub');

        $replacements = [
            '{{ modelNameCapitalized }}' => ucfirst(strtolower(Str::singular($model))),
            '{{ facadeName }}' => $facadeName,
        ];

        $facadeContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        File::put($facadePath, $facadeContent);

        $this->info("Facade created: {$facadeName}.php");
    }

    /**
     * Create Service file.
     */
    protected function createService($model)
    {
        $serviceName = "{$model}Service";
        $servicePath = "domain/Services/{$serviceName}/{$serviceName}.php";

        // Create Services directory if it doesn't exist
        if (!File::isDirectory('domain/Services/'.$serviceName)) {
            File::makeDirectory('domain/Services/'.$serviceName, 0755, true);
        }

        $stub = file_get_contents(__DIR__ . '/../stubs/service.stub');

        $replacements = [
            '{{ modelName }}' => $model,
            '{{ modelNameCapitalized }}' => ucfirst(strtolower(Str::singular($model))),
            '{{ modelNameLowerCase }}' => Str::camel($model),
            '{{ serviceName }}' => $serviceName,
        ];

        $serviceContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        File::put($servicePath, $serviceContent);

        $this->info("Service created: {$serviceName}.php");
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
    /**
     * Update composer.json to add domain directory to autoload
     */
    protected function updateComposerAutoload()
    {
        $composerJsonPath = base_path('composer.json');

        if (!File::exists($composerJsonPath)) {
            $this->error("composer.json not found!");
            return false;
        }

        $composerJson = json_decode(File::get($composerJsonPath), true);

        // Check if autoload section exists
        if (!isset($composerJson['autoload'])) {
            $composerJson['autoload'] = [];
        }

        // Check if psr-4 section exists
        if (!isset($composerJson['autoload']['psr-4'])) {
            $composerJson['autoload']['psr-4'] = [];
        }

        // Check if Domain namespace is already configured
        if (!isset($composerJson['autoload']['psr-4']['domain\\'])) {
            $composerJson['autoload']['psr-4']['domain\\'] = 'domain/';

            // Save the updated composer.json
            File::put(
                $composerJsonPath,
                json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $this->info("Updated composer.json with Domain namespace");
            $this->info("Running composer dump-autoload...");

            // Run composer dump-autoload
            exec('composer dump-autoload', $output, $returnCode);

            if ($returnCode === 0) {
                $this->info("Autoloader updated successfully!");
                return true;
            } else {
                $this->error("Failed to update autoloader. Please run 'composer dump-autoload' manually.");
                return false;
            }
        }

        return true; // Domain namespace already exists
    }
}
