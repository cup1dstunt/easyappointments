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
 * Whats_new library.
 *
 * LNU: "What's new" window - release notes of this fork (application/config/whats_new.php) and the per-user
 * "already seen" bookkeeping.
 *
 * @package Libraries
 */
class Whats_new
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Whats_new constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->config->load('whats_new', true);
    }

    /**
     * Get the releases (newest first) with the entries in the current language.
     *
     * @return array
     */
    public function get_releases(): array
    {
        $language = config('language') === 'german' ? 'de' : 'en';

        return array_map(
            static fn(array $release): array => [
                'id' => $release['id'],
                'date' => $release['date'],
                'entries' => $release['entries'][$language] ?? ($release['entries']['en'] ?? []),
            ],
            $this->CI->config->item('whats_new', 'whats_new') ?? [],
        );
    }

    /**
     * Get the URL that lists all changes of this fork compared to the original.
     *
     * @return string
     */
    public function get_compare_url(): string
    {
        return (string) $this->CI->config->item('whats_new_compare_url', 'whats_new');
    }

    /**
     * Get the id of the newest release.
     *
     * @return string|null
     */
    public function get_current_release_id(): ?string
    {
        return $this->get_releases()[0]['id'] ?? null;
    }

    /**
     * Check whether the user has not seen the newest release yet.
     *
     * @param int|null $user_id
     *
     * @return bool
     */
    public function is_unseen(?int $user_id): bool
    {
        $current = $this->get_current_release_id();

        return $user_id && $current && setting($this->get_setting_name($user_id)) !== $current;
    }

    /**
     * Remember that the user has seen the newest release.
     *
     * @param int $user_id
     */
    public function mark_seen(int $user_id): void
    {
        $current = $this->get_current_release_id();

        if ($current) {
            setting([$this->get_setting_name($user_id) => $current]);
        }
    }

    /**
     * Get the name of the setting that stores the last release the user has seen.
     *
     * @param int $user_id
     *
     * @return string
     */
    private function get_setting_name(int $user_id): string
    {
        return 'whats_new_seen_' . $user_id;
    }
}
