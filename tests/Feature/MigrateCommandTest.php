<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Tests\Feature;

use Esegments\ModularArchitecture\Tests\TestCase;

class MigrateCommandTest extends TestCase
{
    public function test_migrate_command_requires_module_name_or_all_flag(): void
    {
        $this->artisan('modular:migrate')
            ->assertFailed();
    }

    public function test_migrate_command_fails_for_nonexistent_module(): void
    {
        $this->artisan('modular:migrate', ['module' => 'NonExistent'])
            ->assertFailed();
    }

    public function test_migrate_command_handles_module_without_migrations(): void
    {
        $this->createTestModule('NoMigrations');

        $this->artisan('modular:migrate', ['module' => 'NoMigrations'])
            ->assertSuccessful();
    }

    public function test_migrate_command_for_single_module(): void
    {
        $path = $this->createTestModule('Blog');

        // Create migrations directory with a migration file
        $migrationsPath = $path.'/database/migrations';
        $this->app['files']->ensureDirectoryExists($migrationsPath);
        $this->app['files']->put(
            $migrationsPath.'/2024_01_01_000000_create_posts_table.php',
            $this->getMigrationContent()
        );

        $this->artisan('modular:migrate', ['module' => 'Blog', '--force' => true])
            ->assertSuccessful();
    }

    public function test_migrate_all_modules(): void
    {
        $blogPath = $this->createTestModule('Blog');
        $shopPath = $this->createTestModule('Shop');

        // Create migrations for Blog
        $blogMigrationsPath = $blogPath.'/database/migrations';
        $this->app['files']->ensureDirectoryExists($blogMigrationsPath);
        $this->app['files']->put(
            $blogMigrationsPath.'/2024_01_01_000000_create_blog_posts_table.php',
            $this->getMigrationContent('blog_posts')
        );

        // Create migrations for Shop
        $shopMigrationsPath = $shopPath.'/database/migrations';
        $this->app['files']->ensureDirectoryExists($shopMigrationsPath);
        $this->app['files']->put(
            $shopMigrationsPath.'/2024_01_01_000000_create_products_table.php',
            $this->getMigrationContent('products')
        );

        $this->artisan('modular:migrate', ['--all' => true, '--force' => true])
            ->assertSuccessful();
    }

    protected function getMigrationContent(string $table = 'posts'): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('title');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
PHP;
    }
}
