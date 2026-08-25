<?php

namespace VanDmade\Cuztomisable\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use VanDmade\Cuztomisable\Database\Seeders\AdminUserSeeder;
use VanDmade\Cuztomisable\Database\Seeders\PermissionSeeder;
use VanDmade\Cuztomisable\Database\Seeders\RolePermissionSeeder;
use VanDmade\Cuztomisable\Database\Seeders\RoleSeeder;

class InstallCommand extends Command
{

    protected $signature = 'cuztomisable:install';

    protected $description = 'Publish Cuztomisable into this app, then migrate and seed a default admin login';

    public function handle(Filesystem $files): int
    {
        $this->publishPackageFiles();
        $this->ensureSessionsTable($files);
        $this->ensureVariablesScss($files);
        $this->migrateAndSeed();
        // This is the defaults unless the user setup their own environment variables
        $email = env('CUZTOMISABLE_ADMIN', 'admin@cuztomisable.com');
        $password = 'password';
        $this->newLine();
        $this->components->info('Cuztomisable installed - migrated, seeded, ready to log in.');
        $this->components->twoColumnDetail('Default admin', "{$email} / {$password} (change required on first login)");
        $this->newLine();
        return self::SUCCESS;
    }

    protected function publishPackageFiles(): void
    {
        $this->components->task('Publishing config', fn() => Artisan::call('vendor:publish', ['--tag' => 'cuztomisable-config']) === 0);
        $this->components->task('Publishing migrations', fn() => Artisan::call('vendor:publish', ['--tag' => 'cuztomisable-migrations']) === 0);
        $this->components->task('Publishing framework files', fn() => Artisan::call('vendor:publish', [
            '--tag' => 'cuztomisable-framework',
            '--force' => true,
        ]) === 0);
        $this->components->task('Publishing pages', fn() => Artisan::call('vendor:publish', ['--tag' => 'cuztomisable-pages']) === 0);
        $this->components->task('Publishing branding', fn() => Artisan::call('vendor:publish', ['--tag' => 'cuztomisable-branding']) === 0);
    }

    protected function ensureSessionsTable(Filesystem $files): void
    {
        $exists = collect($files->glob(database_path('migrations/*_create_sessions_table.php')))->isNotEmpty();
        if ($exists) {
            $this->components->twoColumnDetail('sessions table migration', '<fg=yellow>already exists, skipped</>');
            return;
        }
        Artisan::call('session:table');
        $this->components->twoColumnDetail('sessions table migration', '<fg=green;options=bold>created</>');
    }

    protected function ensureVariablesScss(Filesystem $files): void
    {
        $target = resource_path('sass/variables.scss');
        if ($files->exists($target)) {
            $this->components->twoColumnDetail('resources/sass/variables.scss', '<fg=yellow>already exists, skipped</>');
            return;
        }
        $example = resource_path('sass/variables.example.scss');
        if (! $files->exists($example)) {
            $this->components->warn('resources/sass/variables.example.scss not found - run this command again after publishing succeeds.');
            return;
        }
        $files->copy($example, $target);
        $this->components->twoColumnDetail('resources/sass/variables.scss', '<fg=green;options=bold>created</>');
    }

    protected function migrateAndSeed(): void
    {
        $this->components->task('Running migrations', fn () => Artisan::call('migrate', ['--force' => true]) === 0);
        $this->components->task('Seeding roles, permissions, and default admin', function () {
            foreach ([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class, AdminUserSeeder::class] as $seeder) {
                if (Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]) !== 0) {
                    return false;
                }
            }
            return true;
        });
    }

}
