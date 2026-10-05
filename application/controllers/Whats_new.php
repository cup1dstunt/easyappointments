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
 * Whats_new controller.
 *
 * LNU: "What's new" window - remembers that a user has seen the newest release notes.
 *
 * @package Controllers
 */
class Whats_new extends EA_Controller
{
    /**
     * Whats_new constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('whats_new');
    }

    /**
     * Mark the newest release as seen by the logged-in user.
     */
    public function dismiss(): void
    {
        try {
            method('post');

            $user_id = session('user_id');

            if (!$user_id) {
                abort(403, 'Forbidden');
            }

            $this->whats_new->mark_seen((int) $user_id);

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
