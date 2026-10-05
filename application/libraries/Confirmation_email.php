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
        'appointment_link',
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
        $subject_template = trim((string) setting('email_confirmation_subject', ''));
        $body_template = trim((string) setting('email_confirmation_body', ''));

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
            'appointment_link' => $appointment_link,
        ];

        $subject = $subject_template === '' ? $default_subject : $this->replace($subject_template, $values);

        $message = $body_template === '' ? $default_message : nl2br(e($this->replace($body_template, $values)));

        return [
            'subject' => $subject,
            'message' => $message,
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
