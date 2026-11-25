<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * Command signature.
     */
    protected $signature = 'module:make
                            {name : The name of the module}';

    /**
     * Command description.
     */
    protected $description = 'Create a complete module structure';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $module = Str::studly(
            $this->argument('name')
        );

        $basePath = app_path(
            "Modules/{$module}"
        );

        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Module
        |--------------------------------------------------------------------------
        */

        if (File::exists($basePath)) {
            $this->error(
                "Module [{$module}] already exists."
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Create Directories
        |--------------------------------------------------------------------------
        */

        $directories = [
            'Controllers',
            'Models',
            'Requests',
            'Resources',
            'Services',
            'Repositories/Contracts',
            'Enums',
            'Database/Migrations',
            'Database/Seeders',
            'Providers',
            'routes',
            'config',
            'Helpers',
        ];

        foreach ($directories as $directory) {
            File::ensureDirectoryExists(
                $basePath . '/' . $directory
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Module Files
        |--------------------------------------------------------------------------
        */

        $this->createFile(
            $basePath . "/Controllers/{$module}Controller.php",
            $this->controllerStub($module)
        );

        $this->createFile(
            $basePath . "/Models/{$module}.php",
            $this->modelStub($module)
        );

        $this->createFile(
            $basePath . "/Services/{$module}Service.php",
            $this->serviceStub($module)
        );

        $this->createFile(
            $basePath . "/Repositories/{$module}Repository.php",
            $this->repositoryStub($module)
        );

        $this->createFile(
            $basePath . "/Repositories/Contracts/{$module}RepositoryInterface.php",
            $this->repositoryInterfaceStub($module)
        );

        $this->createFile(
            $basePath . "/Requests/Store{$module}Request.php",
            $this->requestStub(
                $module,
                "Store{$module}Request"
            )
        );

        $this->createFile(
            $basePath . "/Requests/Update{$module}Request.php",
            $this->requestStub(
                $module,
                "Update{$module}Request"
            )
        );

        $this->createFile(
            $basePath . "/Resources/{$module}Resource.php",
            $this->resourceStub($module)
        );

        $this->createFile(
            $basePath . "/routes/api.php",
            $this->routeStub($module)
        );

        $this->createFile(
            $basePath . "/Providers/{$module}ServiceProvider.php",
            $this->providerStub($module)
        );

        $this->createFile(
            $basePath . "/config/" . Str::snake($module) . ".php",
            "<?php\n\nreturn [];\n"
        );

        $this->createFile(
            $basePath . "/Helpers/helpers.php",
            "<?php\n"
        );

        /*
        |--------------------------------------------------------------------------
        | Create README
        |--------------------------------------------------------------------------
        */

        $this->createFile(
            $basePath . "/README.md",
            "# {$module} Module\n"
        );

        /*
        |--------------------------------------------------------------------------
        | Register Service Provider
        |--------------------------------------------------------------------------
        */

        $this->registerServiceProvider(
            $module
        );

        $this->info(
            "Module [{$module}] created successfully."
        );

        return self::SUCCESS;
    }

    /**
     * Create file if it does not exist.
     */
    private function createFile(
        string $path,
        string $content
    ): void {
        if (File::exists($path)) {
            $this->warn(
                "Skipped: {$path}"
            );

            return;
        }

        File::put(
            $path,
            $content
        );
    }

    /**
     * Register module Service Provider.
     */
    private function registerServiceProvider(
        string $module
    ): void {
        $providersPath = base_path(
            'bootstrap/providers.php'
        );

        if (! File::exists($providersPath)) {
            $this->warn(
                'bootstrap/providers.php not found.'
            );

            return;
        }

        $provider = "App\\Modules\\{$module}\\Providers\\{$module}ServiceProvider::class";

        $content = File::get(
            $providersPath
        );

        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Provider
        |--------------------------------------------------------------------------
        */

        if (str_contains($content, $provider)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Add Provider Before Closing Array
        |--------------------------------------------------------------------------
        */

        $content = preg_replace(
            '/\];\s*$/',
            "    {$provider},\n];",
            $content
        );

        File::put(
            $providersPath,
            $content
        );
    }

    /**
     * Controller stub.
     */
    private function controllerStub(
        string $module
    ): string {
        return <<<PHP
<?php

namespace App\Modules\\{$module}\Controllers;

use App\Http\Controllers\Controller;

class {$module}Controller extends Controller
{

}

PHP;
    }

    /**
     * Model stub.
     */
    private function modelStub(
        string $module
    ): string {
        return <<<PHP
<?php

namespace App\Modules\\{$module}\Models;

use Illuminate\Database\Eloquent\Model;

class {$module} extends Model
{

}

PHP;
    }

    /**
     * Service stub.
     */
    private function serviceStub(
        string $module
    ): string {
        return <<<PHP
<?php

namespace App\Modules\\{$module}\Services;

class {$module}Service
{

}

PHP;
    }

    /**
     * Repository stub.
     */
    private function repositoryStub(
        string $module
    ): string {
        return <<<PHP
<?php

namespace App\Modules\\{$module}\Repositories;

use App\Modules\\{$module}\Repositories\Contracts\\{$module}RepositoryInterface;

class {$module}Repository implements {$module}RepositoryInterface
{

}

PHP;
    }

    /**
     * Repository interface stub.
     */
    private function repositoryInterfaceStub(
        string $module
    ): string {
        return <<<PHP
<?php

namespace App\Modules\\{$module}\Repositories\Contracts;

interface {$module}RepositoryInterface
{

}

PHP;
    }

    /**
     * Form request stub.
     */
    private function requestStub(
        string $module,
        string $class
    ): string {
        return <<<PHP
<?php

namespace App\Modules\\{$module}\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$class} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}

PHP;
    }

    /**
     * Resource stub.
     */
    private function resourceStub(
        string $module
    ): string {
        return <<<PHP
<?php

namespace App\Modules\\{$module}\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$module}Resource extends JsonResource
{
    public function toArray(Request \$request): array
    {
        return parent::toArray(\$request);
    }
}

PHP;
    }

    /**
     * Route stub.
     */
    private function routeStub(
        string $module
    ): string {
        $prefix = Str::kebab($module);

        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| {$module} Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/{$prefix}')
    ->group(function () {
        //
    });

PHP;
    }

    /**
     * Service provider stub.
     */
    private function providerStub(
        string $module
    ): string {
        return <<<PHP
<?php

namespace App\Modules\\{$module}\Providers;

use Illuminate\Support\ServiceProvider;

class {$module}ServiceProvider extends ServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap module services.
     */
    public function boot(): void
    {
        \$this->loadRoutesFrom(
            __DIR__ . '/../routes/api.php'
        );

        \$this->loadMigrationsFrom(
            __DIR__ . '/../Database/Migrations'
        );
    }
}

PHP;
    }
}
