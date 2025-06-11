<?php
require_once HILTON_PLUGIN_DIR . 'includes/graph-handler.php';

function hilton_send_meeting_email($client_email, $lawyer_email, $meeting_data) {
    $subject_client = "Your Meeting with Hilton Attorneys";
    $subject_lawyer = "You Have a New Meeting Scheduled";
    $headers = ['Content-Type: text/html; charset=UTF-8', 'From: Hilton Attorneys <info@hiltonlawgroup.co.tz>'];

    // Create Teams meeting if not already created
    if (empty($meeting_data['meeting_link'])) {
        $start_utc = date('c', strtotime($meeting_data['datetime']));
        $end_utc = date('c', strtotime($meeting_data['datetime'] . ' +1 hour'));
        $meeting_link = hilton_create_teams_meeting($start_utc, $end_utc, 'Meeting with ' . $meeting_data['client_name']);
    } else {
        $meeting_link = $meeting_data['meeting_link'];
    }

    $message = file_get_contents(HILTON_PLUGIN_DIR . 'templates/emails/meeting-invite.php');
    $message = str_replace('{{client_name}}', esc_html($meeting_data['client_name']), $message);
    $message = str_replace('{{meeting_time}}', esc_html($meeting_data['datetime']), $message);
    $message = str_replace('{{meeting_link}}', esc_url($meeting_link), $message);

    wp_mail($client_email, $subject_client, $message, $headers);
    wp_mail($lawyer_email, $subject_lawyer, $message, $headers);
}
