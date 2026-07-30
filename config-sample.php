<?php
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
 * Easy!Appointments Configuration File
 *
 * Set your installation BASE_URL * without the trailing slash * and the database
 * credentials in order to connect to the database. You can enable the DEBUG_MODE
 * while developing the application.
 *
 * Set the default language by changing the LANGUAGE constant. For a full list of
 * available languages look at the /application/config/config.php file.
 *
 * IMPORTANT:
 * If you are updating from version 1.0 you will have to create a new "config.php"
 * file because the old "configuration.php" is not used anymore.
 */
class Config
{
    // ------------------------------------------------------------------------
    // GENERAL SETTINGS
    // ------------------------------------------------------------------------

    const BASE_URL = 'http://localhost';
    const LANGUAGE = 'english';
    const DEBUG_MODE = false;

    // ------------------------------------------------------------------------
    // DATABASE SETTINGS
    // ------------------------------------------------------------------------

    const DB_HOST = 'mysql';
    const DB_NAME = 'easyappointments';
    const DB_USERNAME = 'user';
    const DB_PASSWORD = 'password';

    // ------------------------------------------------------------------------
    // GOOGLE CALENDAR SYNC (Optional - can also be configured via UI)
    // ------------------------------------------------------------------------
    // These settings are optional and can be configured through the admin UI
    // at Settings > Integrations > Google Calendar. If configured here, they
    // will be used as fallback values.
    //
    // const GOOGLE_SYNC_FEATURE = false;
    // const GOOGLE_CLIENT_ID = '';
    // const GOOGLE_CLIENT_SECRET = '';

    // ------------------------------------------------------------------------
    // LNU SETTINGS
    // ------------------------------------------------------------------------
    // Settings related to the Lnu features

    // Custom Fields (Optional)
    // ------------------------------------------------------------------------
    // The maximum number of custom fields available for customers and for
    // appointments. Used only the first time the migration runs, to seed the
    // settings table - after that, change the active number of fields via
    // Settings > Booking > Custom Fields in the admin UI instead.
    //
    // const MAX_CUSTOM_FIELDS = 5;
    // const MAX_APPOINTMENT_CUSTOM_FIELDS = 5;

    // Attached Files (Optional)
    // ------------------------------------------------------------------------
    // MAX_ATTACHED_FILES is a ceiling, used only the first time the migration
    // runs, to seed the settings table - after that, change the active limit
    // via Settings > Booking > Attached Files in the admin UI instead.
    //
    // ATTACHED_FILES_MAX_SIZE is the maximum size of a single file, in bytes.
    //
    // ATTACHED_FILES_ALLOWED_TYPES is a comma-separated list of accepted file
    // extensions and/or MIME types.
    //
    // ATTACHED_FILES_ALLOWED_TYPES_HINT is shown to the user next to the
    // upload control, describing the above allowed types. It can be either
    // plain text or the name of a translations_lang.php key (used to look up
    // the text shown to the user).
    //
    // const MAX_ATTACHED_FILES = 10;
    // const ATTACHED_FILES_MAX_SIZE = 8000000;
    // const ATTACHED_FILES_ALLOWED_TYPES = '.doc,.docx,application/msword';
    // const ATTACHED_FILES_ALLOWED_TYPES_HINT = 'attached_files_user_allowed_types_hint';
}
