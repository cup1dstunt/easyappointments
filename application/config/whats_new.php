<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
 * LNU: "What's new" window.
 *
 * Newest release first. The id of the first entry is the current release: every backend user gets the window
 * once after it changes, and the footer link always shows all entries. Add a new entry on top for each
 * user-visible change of this fork (keep the CHANGELOG.md in sync).
 */

// All changes of this fork compared to the original Easy!Appointments by A. Tselegidis.
$config['whats_new_compare_url'] =
    'https://github.com/alextselegidis/easyappointments/compare/main...cup1dstunt:easyappointments:main';

$config['whats_new'] = [
    [
        'id' => '2026-10-05',
        'date' => '2026-10-05',
        'entries' => [
            'en' => [
                'Settings > Email (SMTP): define the mail server, sender and send a test email. Fixes emails not being sent on Docker/Unraid installs.',
                'This "What\'s new" window, shown once after each update, plus a link to all fork changes in the footer.',
            ],
            'de' => [
                'Einstellungen > E-Mail (SMTP): Mailserver und Absender festlegen und eine Test-E-Mail senden. Behebt den fehlenden E-Mail-Versand bei Docker-/Unraid-Installationen.',
                'Dieses „Was ist neu“-Fenster, das einmalig nach jedem Update erscheint, sowie ein Link zu allen Änderungen dieses Forks im Footer.',
            ],
        ],
    ],
    [
        'id' => '2026-10-04',
        'date' => '2026-10-04',
        'entries' => [
            'en' => [
                'Editable customer confirmation email (Settings > Email templates).',
                'Production Docker image and Unraid stack.',
                'Custom booking step order, provider booking email note, Reply-To customer for provider emails and company logo in emails.',
            ],
            'de' => [
                'Bearbeitbare Bestätigungs-E-Mail für Kunden (Einstellungen > E-Mail-Vorlagen).',
                'Docker-Image für den Produktivbetrieb und Unraid-Stack.',
                'Eigene Reihenfolge der Buchungsschritte, Hinweis des Anbieters in der Buchungs-E-Mail, Antwort an Kunden in Anbieter-E-Mails und Firmenlogo in E-Mails.',
            ],
        ],
    ],
];
