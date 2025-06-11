<?php
require_once HILTON_PLUGIN_DIR . 'includes/graph-handler.php';

function hilton_send_meeting_email($client_emails, $lawyer_emails, $meeting_data) {
    $subject = 'Meeting Scheduled: ' . $meeting_data['title'];
    $headers = ['Content-Type: text/html; charset=UTF-8', 'From: Hilton Attorneys <info@hiltonlawgroup.co.tz>'];

    // Create Teams meeting if not already created
    if ($meeting_data['meeting_type'] === 'online' && empty($meeting_data['meeting_link'])) {
        $start_utc = date('c', strtotime($meeting_data['datetime']));
        $end_utc = date('c', strtotime($meeting_data['datetime'] . ' +1 hour'));
        $meeting_data['meeting_link'] = hilton_create_teams_meeting($start_utc, $end_utc, $meeting_data['subject']);
    }

    $message = file_get_contents(HILTON_PLUGIN_DIR . 'templates/emails/meeting-invite.php');
    foreach ($meeting_data as $key => $value) {
        $message = str_replace('{{' . $key . '}}', esc_html($value), $message);
    }

    $recipients = array_merge((array)$client_emails, (array)$lawyer_emails);
    foreach ($recipients as $email) {
        wp_mail($email, $subject, $message, $headers);
    }
}
