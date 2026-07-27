<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

require_once BASEPATH . 'libraries/Migration.php';

/**
 * Easy!Appointments migration.
 *
 * @property EA_Benchmark $benchmark
 * @property EA_Cache $cache
 * @property EA_Calendar $calendar
 * @property EA_Config $config
 * @property EA_DB_forge $dbforge
 * @property EA_DB_query_builder $db
 * @property EA_DB_utility $dbutil
 * @property EA_Email $email
 * @property EA_Encrypt $encrypt
 * @property EA_Encryption $encryption
 * @property EA_Exceptions $exceptions
 * @property EA_Hooks $hooks
 * @property EA_Input $input
 * @property EA_Lang $lang
 * @property EA_Loader $load
 * @property EA_Log $log
 * @property EA_Migration $migration
 * @property EA_Output $output
 * @property EA_Profiler $profiler
 * @property EA_Router $router
 * @property EA_Security $security
 * @property EA_Session $session
 * @property EA_Upload $upload
 * @property EA_URI $uri
 */
class EA_Migration extends CI_Migration
{
    /**
     * Get the current migration version.
     *
     * @return int
     */
    public function current_version(): int
    {
        return $this->_get_version();
    }

    /**
     * Run every "lnu_*" migration's up() method.
     *
     * These migrations aren't tracked by version number like the sequential
     * ones - every "lnu_*" file found is run every time this is called. This
     * only works because every migration in this codebase already guards its
     * own changes (checking field_exists()/table_exists()/row existence
     * before applying anything), so re-running an already-applied migration
     * is a safe no-op. This lets LNU-specific migrations be added, removed,
     * or reordered independently of the base application's sequential
     * migration numbering.
     */
    public function run_lnu_migrations(): void
    {
        foreach ($this->_find_lnu_migrations() as $file) {
            $this->_run_lnu_migration_file($file, 'up');
        }
    }

    /**
     * Roll back every "lnu_*" migration's down() method, in reverse order.
     */
    public function run_lnu_migrations_down(): void
    {
        $files = $this->_find_lnu_migrations();
        krsort($files);

        foreach ($files as $file) {
            $this->_run_lnu_migration_file($file, 'down');
        }
    }

    /**
     * Roll back a single "lnu_*" migration by name, without affecting any
     * other lnu migration.
     *
     * @param string $name The migration name, e.g. "provider_colour"
     * (matching the "lnu_<name>.php" filename, without the "lnu_" prefix or
     * the ".php" extension).
     */
    public function run_lnu_migration_down(string $name): void
    {
        $file = $this->_migration_path . 'lnu_' . $name . '.php';

        if (!is_file($file)) {
            show_error('LNU migration not found: ' . $name);
        }

        $this->_run_lnu_migration_file($file, 'down');
    }

    /**
     * Find every "lnu_*.php" migration file.
     *
     * @return array<string, string> Migration file paths, keyed by filename.
     */
    private function _find_lnu_migrations(): array
    {
        $files = [];

        foreach (glob($this->_migration_path . 'lnu_*.php') as $file) {
            $files[basename($file, '.php')] = $file;
        }

        return $files;
    }

    /**
     * Include and run a single lnu migration file's up() or down() method,
     * echoing whether it actually changed anything or was a no-op.
     *
     * @param string $file Full path to the migration file.
     * @param string $method Either "up" or "down".
     */
    private function _run_lnu_migration_file(string $file, string $method): void
    {
        require_once $file;

        $name = basename($file, '.php');

        $class = 'Migration_' . ucfirst(strtolower($this->_get_migration_name($name)));

        if (!class_exists($class, false)) {
            show_error('LNU migration class does not exist: ' . $class);
        }

        $changed = (new $class())->$method();

        if ($changed) {
            echo '  [applied] ' . $name . ' (' . $method . ')' . PHP_EOL;
        } else {
            $already = $method === 'up' ? 'already migrated' : 'already reverted';
            echo '  [no action] ' . $name . ' (' . $already . ')' . PHP_EOL;
        }
    }
}
