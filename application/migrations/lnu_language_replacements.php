<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.6.0
 * ---------------------------------------------------------------------------- */

/**
 * LNU: Configurable Terminology (README.md #17).
 *
 * Seeds the "language_replacements" setting - semicolon-separated "word=replacement" pairs, plus optional
 * "!phrase" exceptions to leave untouched (see apply_language_replacements() in language_helper.php), shared
 * across every language. This is a no-op unless config.php sets Config::LANGUAGE_REPLACEMENTS (or an admin
 * configures the setting afterward) - defaults to empty, matching every other config()-seeded setting.
 */
class Migration_Language_replacements extends EA_Migration
{
    private const FIELD_NAME = 'language_replacements';

    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        if ($this->db->get_where('settings', ['name' => self::FIELD_NAME])->num_rows()) {
            return false;
        }

        $this->db->insert('settings', [
            'name' => self::FIELD_NAME,
            'value' => config(self::FIELD_NAME, ''),
        ]);

        return true;
    }

    /**
     * Downgrade method.
     *
     * @return bool True if any change was applied, false if everything was already reverted.
     */
    public function down(): bool
    {
        if (!$this->db->get_where('settings', ['name' => self::FIELD_NAME])->num_rows()) {
            return false;
        }

        $this->db->delete('settings', ['name' => self::FIELD_NAME]);

        return true;
    }
}
