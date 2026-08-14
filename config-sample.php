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
    // SECURITY SETTINGS
    // ------------------------------------------------------------------------
    // Used by CodeIgniter's Encryption class to encrypt values at rest that the app must later decrypt and
    // reuse (currently: the Zoom client secret, Settings > Integrations > Zoom). Without this, CodeIgniter
    // falls back to a key derived only from the install path and server hostname - not a real secret, since
    // neither is normally confidential. Generate a real one with, e.g.:
    //   php -r "echo bin2hex(random_bytes(32));"
    //
    // const ENCRYPTION_KEY = '';

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
    // ZOOM MEETING LINKS (Optional - can also be configured via UI)
    // ------------------------------------------------------------------------
    // These settings are optional and can be configured through the admin UI
    // at Settings > Integrations > Zoom. If configured here, they will be
    // used as fallback values, seeded into the settings table the first
    // time the migration runs.
    //
    // Credentials come from a Server-to-Server OAuth app in the Zoom App
    // Marketplace. Whether a specific provider actually gets Zoom links is a
    // separate, per-provider opt-in, set under Users > Providers.
    //
    // const ZOOM_CLIENT_ID = '';
    // const ZOOM_CLIENT_SECRET = '';
    // const ZOOM_ACCOUNT_ID = '';

    // ------------------------------------------------------------------------
    // OIDC BOOKING LOGIN (Optional - can also be configured via UI)
    // ------------------------------------------------------------------------
    // These settings are optional and can be configured through the admin UI
    // at Settings > Integrations > OIDC. If configured here, they
    // will be used as fallback values, seeded into the settings table the
    // first time the migration runs.
    //
    // Gates the entire public booking wizard behind a login with an external
    // identity provider. Currently only booking login is supported.
    //
    // const OIDC_ENABLED_BOOKING = false;
    // const OIDC_CLIENT_ID = '';
    // const OIDC_CLIENT_SECRET = '';
    // const OIDC_IDP_URL = ''; // Identity provider's base/issuer URL.
    //
    // Semicolon-separated list of "claim=value1|value2" rules, e.g.
    // "affiliation=student|employee;department=ub|it" - all rules must pass
    // (AND) for a customer to be let through. Leave empty to allow anyone who
    // can log in.
    //
    // const OIDC_BOOKING_USER_PARAM_RESTRICTIONS = '';
    //
    // Shown to a customer who logs in successfully but does not meet the
    // restrictions above - each can be a translation key or literal text.
    //
    // const OIDC_BOOKING_USER_PARAM_DISALLOWED_TITLE = 'default_oidc_booking_user_param_disallowed_title';
    // const OIDC_BOOKING_USER_PARAM_DISALLOWED_MESSAGE = 'default_oidc_booking_user_param_disallowed_message';
    //
    // Automatically log the customer out after they complete a booking or
    // edit an existing one.
    //
    // const OIDC_BOOKING_LOGOUT_AFTER_REGISTER = false;

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

    // Unit Selection for Booking Advance Timeout (Optional)
    // ------------------------------------------------------------------------
    // The default unit for the "Book Advance Timeout" setting, used only the
    // first time the migration runs, to seed the settings table - after that,
    // change the active unit via Settings > Business Logic in the admin UI
    // instead. One of: 'minutes', 'hours', 'days', 'weekdays'.
    //
    // const DEFAULT_BOOK_ADVANCE_TIMEOUT_UNIT = 'minutes';

    // Customer Booking Limits (Optional)
    // ------------------------------------------------------------------------
    // Semicolon-separated list of email addresses that are always exempt from
    // the customer booking limits (Settings > Business Logic), eg. for testing.
    //
    // const TEST_EMAIL_ADDRESSES = 'test@example.com;qa@example.com';

    // Provider Colour in Appointments (Optional)
    // ------------------------------------------------------------------------
    // The list of colors selectable for a provider's calendar-marking color,
    // shown under Users > Providers. A "no colour" option is always shown in
    // addition to this list (and is the default for new providers), so this
    // list only needs to contain real colors.
    //
    // const PROVIDER_COLORS = ['#000000', '#bb3333', '#33bb33', '#3333bb', '#bb33bb', '#bbbb33', '#33bbbb'];
    //
    // The lists of colors selectable for a service's or an appointment's own
    // color, shown under Services and in the appointment editing modal
    // respectively. Both default to the same pastel palette, distinct from
    // the provider color list above.
    //
    // const SERVICE_COLORS = ['#b2d3ec', '#d2dcfd', '#b0e4e9', '#a8ecd2', '#c6e6c2', '#e9e4b8', '#f5d8b7', '#f7d7d4', '#f0baba', '#f0d9ff', '#f5f3f3'];
    // const APPOINTMENT_COLORS = ['#b2d3ec', '#d2dcfd', '#b0e4e9', '#a8ecd2', '#c6e6c2', '#e9e4b8', '#f5d8b7', '#f7d7d4', '#f0baba', '#f0d9ff', '#f5f3f3'];

    // Configurable Order for Booking Wizard Steps (Optional)
    // ------------------------------------------------------------------------
    // The default order of the booking wizard's steps, used to seed the
    // "Booking Step Order" setting (Settings > Booking) the first time the
    // migration runs, and as the fallback if that setting is ever left in an
    // invalid state - after that, change the active order via the admin UI
    // instead. A '>'-separated list of step names: service, time, info,
    // confirmation. All four must be present, "confirmation" must be last,
    // and "service" must come before "time".
    //
    // const DEFAULT_BOOKING_STEP_ORDER = 'service>time>info>confirmation';
}
