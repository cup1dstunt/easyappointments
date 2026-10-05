<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| App Configuration
|--------------------------------------------------------------------------
|
| Declare some of the global config values of Easy!Appointments.
|
*/

$config['version'] = '1.6.0'; // This must be changed manually.

// LNU: version of this fork (independent of the upstream version above). Bump it together with a new entry in
// config/whats_new.php to show the "What's new" window once to every user after an update.
$config['fork_version'] = '1.1.0';

// LNU: all changes of this fork compared to the original project.
$config['fork_changes_url'] =
    'https://github.com/alextselegidis/easyappointments/compare/develop...cup1dstunt:easyappointments:main';

$config['url'] = Config::BASE_URL;

$config['debug'] = Config::DEBUG_MODE;

$config['cache_busting_token'] = 'TSJ83';
