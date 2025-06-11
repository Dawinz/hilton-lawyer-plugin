<?php
function hilton_get_graph_access_token() {
    $client_id = get_option('hilton_graph_client_id');
    $client_secret = get_option('hilton_graph_client_secret');
    $tenant_id = get_option('hilton_graph_tenant_id');

    $token_url = "https://login.microsoftonline.com/{$tenant_id}/oauth2/v2.0/token";

    $response = wp_remote_post($token_url, [
        'body' => [
            'client_id' => $client_id,
            'scope' => 'https://graph.microsoft.com/.default',
            'client_secret' => $client_secret,
            'grant_type' => 'client_credentials',
        ]
    ]);

    if (is_wp_error($response)) return false;

    $body = json_decode(wp_remote_retrieve_body($response));
    return $body->access_token ?? false;
}

function hilton_create_teams_meeting($startDateTime, $endDateTime, $subject = 'Client Meeting') {
    $access_token = hilton_get_graph_access_token();
    if (!$access_token) return false;

    // You can change this user to the assigned lawyer's Office 365 email
    $user_id = 'me'; // Use 'me' if the app is limited to one user

    $meeting_url = "https://graph.microsoft.com/v1.0/users/{$user_id}/onlineMeetings";

    $response = wp_remote_post($meeting_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'startDateTime' => $startDateTime,
            'endDateTime' => $endDateTime,
            'subject' => $subject
        ])
    ]);

    if (is_wp_error($response)) return false;

    $data = json_decode(wp_remote_retrieve_body($response));
    return $data->joinWebUrl ?? false;
}
?>
