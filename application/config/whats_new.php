<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| What's new (LNU)
|--------------------------------------------------------------------------
|
| Release notes of this fork, newest first. The newest entry is shown in the "What's new" window the first time a
| user opens the backend after the 'fork_version' (see app.php) changed. Each item has an English and a German text.
|
*/

$config['whats_new'] = [
    [
        'version' => '1.1.0',
        'date' => '2026-10-05',
        'items' => [
            [
                'en' => 'E-mail settings: configure the SMTP server in Settings > E-Mail and send a test e-mail.',
                'de' => 'E-Mail-Einstellungen: SMTP-Server unter Einstellungen > E-Mail konfigurieren und eine Test-E-Mail senden.',
            ],
            [
                'en' => 'This "What\'s new" window after updates, and a footer link to all changes compared to the original.',
                'de' => 'Dieses „Was ist neu“-Fenster nach Updates sowie ein Footer-Link zu allen Änderungen gegenüber dem Original.',
            ],
        ],
    ],
    [
        'version' => '1.0.0',
        'date' => '2026-10-04',
        'items' => [
            [
                'en' => 'Customer confirmation e-mail is editable in the admin settings.',
                'de' => 'Die Bestätigungs-E-Mail an Kunden ist in den Admin-Einstellungen bearbeitbar.',
            ],
            [
                'en' => 'Custom booking step order, custom logo everywhere (also in e-mails).',
                'de' => 'Eigene Reihenfolge der Buchungsschritte, eigenes Logo überall (auch in E-Mails).',
            ],
            [
                'en' => 'Provider booking e-mail note, and replies to the provider e-mail go straight to the customer.',
                'de' => 'Hinweis für Anbieter in der Buchungs-E-Mail; Antworten auf die Anbieter-E-Mail gehen direkt an den Kunden.',
            ],
            [
                'en' => 'Production Docker image and Unraid stack, German translations for the new features.',
                'de' => 'Docker-Image für den Produktivbetrieb und Unraid-Stack, deutsche Übersetzungen der neuen Funktionen.',
            ],
        ],
    ],
];
