<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * LNU: Editable confirmation email.
 *
 * Renders the admin-defined subject and text of the customer confirmation email (Settings > Email templates).
 * The texts are stored in the "settings" table (email_confirmation_subject / email_confirmation_body). Empty values
 * fall back to the translations of the customer's language, so an untouched installation behaves as before.
 *
 * @package Libraries
 */
class Confirmation_email
{
    /**
     * All editable emails and their template variables. Each email has a "email_<key>_subject" and a
     * "email_<key>_body" setting; the confirmation email keeps its original setting names.
     */
    public const TEMPLATES = [
        'confirmation' => [
            'variables' => [
                'customer_name',
                'customer_first_name',
                'customer_last_name',
                'appointment_date',
                'appointment_time',
                'appointment_end_time',
                'service_name',
                'provider_name',
                'location',
                'meeting_link',
                'appointment_link',
            ],
        ],
        'deleted' => [
            'variables' => [
                'customer_name',
                'customer_first_name',
                'customer_last_name',
                'appointment_date',
                'appointment_time',
                'appointment_end_time',
                'service_name',
                'provider_name',
                'location',
                'meeting_link',
                'reason',
            ],
        ],
        'password_reset' => [
            'variables' => ['reset_link'],
        ],
        'recovery' => [
            'variables' => ['password'],
        ],
    ];

    /**
     * Template variables that can be used in the subject and the text, e.g. {customer_name}.
     */
    public const VARIABLES = [
        'customer_name',
        'customer_first_name',
        'customer_last_name',
        'appointment_date',
        'appointment_time',
        'appointment_end_time',
        'service_name',
        'provider_name',
        'location',
        'meeting_link',
        'appointment_link',
    ];

    /**
     * Variables that are inserted as HTML (sprintf pattern, applied to the escaped value) instead of plain text.
     */
    public const HTML_VALUES = [
        'meeting_link' => '<a href="%1$s">%1$s</a>',
    ];

    /**
     * Render the confirmation email subject and message.
     *
     * @param array $appointment Appointment data (already converted to the recipient timezone).
     * @param array $provider Provider data.
     * @param array $service Service data.
     * @param array $customer Customer data.
     * @param string $appointment_link Customer appointment URL.
     * @param string $default_subject Fallback subject.
     * @param string $default_message Fallback message.
     *
     * @return array{subject: string, message: string} The message is HTML safe (line breaks are converted).
     */
    public function render(
        array $appointment,
        array $provider,
        array $service,
        array $customer,
        string $appointment_link,
        string $default_subject,
        string $default_message,
    ): array {
        $values = [
            'customer_name' => trim($customer['first_name'] . ' ' . $customer['last_name']),
            'customer_first_name' => $customer['first_name'],
            'customer_last_name' => $customer['last_name'],
            'appointment_date' => format_date($appointment['start_datetime']),
            'appointment_time' => format_time($appointment['start_datetime']),
            'appointment_end_time' => format_time($appointment['end_datetime']),
            'service_name' => $service['name'],
            'provider_name' => trim($provider['first_name'] . ' ' . $provider['last_name']),
            'location' => $appointment['location'] ?? '',
            'meeting_link' => $appointment['meeting_link'] ?? '',
            'appointment_link' => $appointment_link,
        ];

        return $this->render_template(
            'confirmation',
            $values,
            $default_subject,
            $default_message,
            self::HTML_VALUES,
        );
    }

    /**
     * Whether the appointment and customer details are shown below the confirmation text.
     *
     * The details are only hidden if a custom text is set and the switch (Settings > Email templates) is off, so an
     * untouched installation keeps its mail unchanged.
     */
    public function show_details(): bool
    {
        return trim((string) setting('email_confirmation_body', '')) === '' ||
            (string) setting('email_confirmation_show_details', '1') !== '0';
    }

    /**
     * Setting names (subject and text) of all editable emails.
     *
     * @return string[]
     */
    public static function setting_names(): array
    {
        $names = [];

        foreach (array_keys(self::TEMPLATES) as $key) {
            $names[] = 'email_' . $key . '_subject';
            $names[] = 'email_' . $key . '_body';
        }

        $names[] = 'email_confirmation_show_details';

        return $names;
    }

    /**
     * Render any editable email (other than the confirmation email, see render()).
     *
     * @param string $key Template key, e.g. "deleted".
     * @param array $values Plain text values for the {variables}.
     * @param string $default_subject Fallback subject.
     * @param string $default_message Fallback message (HTML).
     * @param array $html_values Variables whose (escaped) value is wrapped, e.g. ['password' => '<strong>%s</strong>'].
     *
     * @return array{subject: string, message: string}
     */
    public function render_template(
        string $key,
        array $values,
        string $default_subject,
        string $default_message,
        array $html_values = [],
    ): array {
        $subject_template = trim((string) setting('email_' . $key . '_subject', ''));
        $body_template = trim((string) setting('email_' . $key . '_body', ''));

        $subject = $subject_template === '' ? $default_subject : $this->replace($subject_template, $values);

        if ($body_template === '') {
            return ['subject' => $subject, 'message' => $default_message];
        }

        $escaped = [];

        foreach ($values as $name => $value) {
            $escaped[$name] = e((string) $value);

            if (isset($html_values[$name]) && (string) $value !== '') {
                $escaped[$name] = sprintf($html_values[$name], $escaped[$name]);
            }
        }

        return [
            'subject' => $subject,
            'message' => nl2br($this->replace(e($body_template), $escaped)),
        ];
    }

    /**
     * Replace the {variables} of a template with their values. Unknown placeholders are left untouched.
     *
     * @param string $template
     * @param array $values
     *
     * @return string
     */
    public function replace(string $template, array $values): string
    {
        $replacements = [];

        foreach ($values as $name => $value) {
            $replacements['{' . $name . '}'] = (string) $value;
        }

        return strtr($template, $replacements);
    }
}
