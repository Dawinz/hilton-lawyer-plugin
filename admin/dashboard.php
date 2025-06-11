<?php
global $wpdb;
$table_lawyers = $wpdb->prefix . 'hilton_lawyers';
$table_meetings = $wpdb->prefix . 'hilton_meetings';

// Handle lawyer addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lawyer_name'])) {
    $wpdb->insert($table_lawyers, [
        'name' => sanitize_text_field($_POST['lawyer_name']),
        'email' => sanitize_email($_POST['lawyer_email']),
        'hourly_rate' => floatval($_POST['lawyer_rate'])
    ]);
    echo '<div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">Lawyer added successfully.</div>';
}

// Handle meeting scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_name'])) {
    $has_graph = get_option('hilton_graph_client_id') && get_option('hilton_graph_client_secret') && get_option('hilton_graph_tenant_id');

    $start_utc = date('c', strtotime($_POST['meeting_datetime']));
    $end_utc = date('c', strtotime($_POST['meeting_datetime'] . ' +1 hour'));
    $link = $has_graph
        ? hilton_create_teams_meeting($start_utc, $end_utc, 'Meeting with ' . sanitize_text_field($_POST['client_name']))
        : esc_url_raw($_POST['meeting_link']);

    $wpdb->insert($table_meetings, [
        'client_name' => sanitize_text_field($_POST['client_name']),
        'client_email' => sanitize_email($_POST['client_email']),
        'lawyer_id' => intval($_POST['lawyer_id']),
        'meeting_datetime' => sanitize_text_field($_POST['meeting_datetime']),
        'meeting_link' => $link,
        'hourly_rate' => floatval($_POST['hourly_rate']),
        'invoice_status' => 'pending'
    ]);

    $lawyer = $wpdb->get_row("SELECT * FROM $table_lawyers WHERE id = " . intval($_POST['lawyer_id']));
    $meeting_data = [
        'client_name' => $_POST['client_name'],
        'datetime' => $_POST['meeting_datetime'],
        'meeting_link' => $link
    ];
    hilton_send_meeting_email($_POST['client_email'], $lawyer->email, $meeting_data);
    echo '<div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">Meeting scheduled and emails sent.</div>';
}

// Get lawyers
$lawyers = $wpdb->get_results("SELECT * FROM $table_lawyers ORDER BY created_at DESC");
$has_graph = get_option('hilton_graph_client_id') && get_option('hilton_graph_client_secret') && get_option('hilton_graph_tenant_id');

// Begin HTML
echo '<div class="max-w-4xl mx-auto px-4 py-6">';
echo '<h1 class="text-3xl font-bold mb-6">Hilton Lawyer Scheduler</h1>';

// Tabs
echo '
<div class="mb-6">
  <div class="flex space-x-4 border-b pb-2">
    <button onclick="showTab(\'add\')" class="tab-btn text-blue-600 font-medium" id="tab-add">Add Lawyer</button>
    <button onclick="showTab(\'list\')" class="tab-btn text-gray-600" id="tab-list">Existing Lawyers</button>
    <button onclick="showTab(\'meeting\')" class="tab-btn text-gray-600" id="tab-meeting">Schedule Meeting</button>
  </div>
</div>
';

// Add Lawyer Tab
echo '<div id="tab-content-add" class="tab-content">';
echo '<div class="bg-white p-6 rounded-lg shadow mb-6">';
echo '<h2 class="text-xl font-semibold mb-4">Add New Lawyer</h2>';
echo '<form method="post" class="grid gap-4">';
echo '<div><label class="block font-medium">Name</label><input type="text" name="lawyer_name" required class="w-full border px-3 py-2 rounded" /></div>';
echo '<div><label class="block font-medium">Email</label><input type="email" name="lawyer_email" required class="w-full border px-3 py-2 rounded" /></div>';
echo '<div><label class="block font-medium">Hourly Rate</label><input type="number" step="0.01" name="lawyer_rate" required class="w-full border px-3 py-2 rounded" /></div>';
echo '<div><input type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" value="Add Lawyer" /></div>';
echo '</form></div></div>';

// Lawyer List Tab
echo '<div id="tab-content-list" class="tab-content hidden">';
echo '<div class="bg-white p-6 rounded-lg shadow mb-6">';
echo '<h2 class="text-xl font-semibold mb-4">Existing Lawyers</h2>';
echo '<table class="min-w-full border divide-y divide-gray-200">';
echo '<thead><tr class="bg-gray-100 text-left text-sm font-medium text-gray-700">';
echo '<th class="py-2 px-4">Name</th><th class="py-2 px-4">Email</th><th class="py-2 px-4">Rate</th><th class="py-2 px-4">Added</th></tr></thead><tbody>';
foreach ($lawyers as $lawyer) {
    echo '<tr class="border-t text-sm">';
    echo '<td class="py-2 px-4">' . esc_html($lawyer->name) . '</td>';
    echo '<td class="py-2 px-4">' . esc_html($lawyer->email) . '</td>';
    echo '<td class="py-2 px-4">' . esc_html($lawyer->hourly_rate) . '</td>';
    echo '<td class="py-2 px-4">' . esc_html($lawyer->created_at) . '</td>';
    echo '</tr>';
}
echo '</tbody></table></div></div>';

// Schedule Meeting Tab
echo '<div id="tab-content-meeting" class="tab-content hidden">';
echo '<div class="bg-white p-6 rounded-lg shadow mb-6">';
echo '<h2 class="text-xl font-semibold mb-4">Schedule a Meeting</h2>';
echo '<form method="post" class="grid gap-4">';
echo '<div><label class="block font-medium">Client Name</label><input type="text" name="client_name" required class="w-full border px-3 py-2 rounded" /></div>';
echo '<div><label class="block font-medium">Client Email</label><input type="email" name="client_email" required class="w-full border px-3 py-2 rounded" /></div>';
echo '<div><label class="block font-medium">Lawyer</label><select name="lawyer_id" class="w-full border px-3 py-2 rounded">';
foreach ($lawyers as $lawyer) {
    echo '<option value="' . esc_attr($lawyer->id) . '">' . esc_html($lawyer->name) . '</option>';
}
echo '</select></div>';
echo '<div><label class="block font-medium">Meeting Date & Time</label><input type="datetime-local" name="meeting_datetime" required class="w-full border px-3 py-2 rounded" /></div>';
if (!$has_graph) {
    echo '<div><label class="block font-medium">Meeting Link</label><input type="url" name="meeting_link" required class="w-full border px-3 py-2 rounded" /></div>';
}
echo '<div><label class="block font-medium">Hourly Rate</label><input type="number" step="0.01" name="hourly_rate" required class="w-full border px-3 py-2 rounded" /></div>';
echo '<div><input type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700" value="Schedule Meeting" /></div>';
echo '</form></div></div>';

echo '</div>'; // end wrap

// JavaScript for tab switching
echo '<script>
function showTab(tab) {
    document.querySelectorAll(".tab-content").forEach(c => c.classList.add("hidden"));
    document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("text-blue-600", "font-medium"));
    document.getElementById("tab-content-" + tab).classList.remove("hidden");
    document.getElementById("tab-" + tab).classList.add("text-blue-600", "font-medium");
}
showTab("add"); // default tab
</script>';
