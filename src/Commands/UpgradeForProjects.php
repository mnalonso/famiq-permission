<?php

namespace Spatie\Permission\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class UpgradeForProjects extends Command
{
    protected $signature = 'permission:setup-projects';

    protected $description = 'Setup the projects feature by generating the associated migration.';

    protected $migrationSuffix = 'add_projects_fields.php';

    public function handle()
    {
        if (! Config::get('permission.projects')) {
            $this->error('Projects feature is disabled in your permission.php file.');
            $this->warn('Please enable the projects setting in your configuration.');

            return;
        }

        $this->line('');
        $this->info('The projects feature setup is going to add a migration and a model');

        $existingMigrations = $this->alreadyExistingMigrations();

        if ($existingMigrations) {
            $this->line('');

            $this->warn($this->getExistingMigrationsWarning($existingMigrations));
        }

        $this->line('');

        if (! $this->confirm('Proceed with the migration creation?', true)) {
            return;
        }

        $this->line('');

        $this->line('Creating migration');

        if ($this->createMigration()) {
            $this->info('Migration created successfully.');
        } else {
            $this->error(
                "Couldn't create migration.\n".
                'Check the write permissions within the database/migrations directory.'
            );
        }

        $this->line('');
    }

    /**
     * Create the migration.
     *
     * @return bool
     */
    protected function createMigration()
    {
        try {
            $migrationStub = __DIR__."/../../database/migrations/{$this->migrationSuffix}.stub";
            copy($migrationStub, $this->getMigrationPath());

            return true;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return false;
        }
    }

    /**
     * Build a warning regarding possible duplication
     * due to already existing migrations.
     *
     * @return string
     */
    protected function getExistingMigrationsWarning(array $existingMigrations)
    {
        if (count($existingMigrations) > 1) {
            $base = "Setup projects migrations already exist.\nFollowing files were found: ";
        } else {
            $base = "Setup projects migration already exists.\nFollowing file was found: ";
        }

        return $base.array_reduce($existingMigrations, fn ($carry, $fileName) => $carry."\n - ".$fileName);
    }

    /**
     * Check if there is another migration
     * with the same suffix.
     *
     * @return array
     */
    protected function alreadyExistingMigrations()
    {
        $matchingFiles = glob($this->getMigrationPath('*'));

        return array_map(fn ($path) => basename($path), $matchingFiles);
    }

    /**
     * Get the migration path.
     *
     * The date parameter is optional for ability
     * to provide a custom value or a wildcard.
     *
     * @param  string|null  $date
     * @return string
     */
    protected function getMigrationPath($date = null)
    {
        $date = $date ?: date('Y_m_d_His');

        return database_path("migrations/{$date}_{$this->migrationSuffix}");
    }
}
