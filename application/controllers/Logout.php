<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Logout controller.
 *
 * Handles the logout page functionality.
 *
 * @package Controllers
 */
class Logout extends EA_Controller
{
    /**
     * Render the logout page.
     */
    public function index(): void
    {
        method('get');

        $this->session->sess_destroy();

        // LNU: Settings Text Translatability - company_name may be set to a translation key instead of
        // literal text, resolved here via lang()'s existing fallback-to-literal behavior.
        $company_name = lang(setting('company_name'));

        html_vars([
            'page_title' => lang('log_out'),
            'company_name' => $company_name,
            'company_logo' => setting('company_logo'),
        ]);

        $this->load->view('pages/logout');
    }
}
