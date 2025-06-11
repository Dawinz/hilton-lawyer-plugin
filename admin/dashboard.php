<?php
global $wpdb;
$table_lawyers = $wpdb->prefix . 'hilton_lawyers';
$table_meetings = $wpdb->prefix . 'hilton_meetings';

// Handle lawyer addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lawyer_name']) && !isset($_POST['edit_lawyer_id'])) {
    $wpdb->insert($table_lawyers, [
        'name' => sanitize_text_field($_POST['lawyer_name']),
        'email' => sanitize_email($_POST['lawyer_email']),
        'hourly_rate' => floatval($_POST['lawyer_rate'])
    ]);
    echo '<div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">Lawyer added successfully.</div>';
}

// Handle lawyer update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_lawyer_id'])) {
    $wpdb->update(
        $table_lawyers,
        [
            'name' => sanitize_text_field($_POST['lawyer_name']),
            'email' => sanitize_email($_POST['lawyer_email']),
            'hourly_rate' => floatval($_POST['lawyer_rate'])
        ],
        ['id' => intval($_POST['edit_lawyer_id'])]
    );
    echo '<div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">Lawyer updated successfully.</div>';
}

// Handle meeting scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_names'])) {
    $has_graph = get_option('hilton_graph_client_id') && get_option('hilton_graph_client_secret') && get_option('hilton_graph_tenant_id');

    $client_names  = array_map('sanitize_text_field', $_POST['client_names']);
    $client_emails = array_map('sanitize_email', $_POST['client_emails']);
    $lawyer_ids    = array_map('intval', $_POST['lawyer_ids']);

    $meeting_type  = sanitize_text_field($_POST['meeting_type']);

    $start_utc = date('c', strtotime($_POST['meeting_datetime']));
    $end_utc   = date('c', strtotime($_POST['meeting_datetime'] . ' +1 hour'));

    $link = '';
    $location = '';
    if ($meeting_type === 'online') {
        $link = $has_graph ? hilton_create_teams_meeting($start_utc, $end_utc, sanitize_text_field($_POST['meeting_subject'])) : esc_url_raw($_POST['meeting_link']);
    } else {
        $location = sanitize_text_field($_POST['meeting_location']);
    }

    $wpdb->insert($table_meetings, [
        'client_name' => $client_names[0],
        'client_email' => $client_emails[0],
        'lawyer_id' => $lawyer_ids[0],
        'meeting_datetime' => sanitize_text_field($_POST['meeting_datetime']),
        'meeting_link' => $link,
        'hourly_rate' => floatval($_POST['hourly_rate']),
        'title' => sanitize_text_field($_POST['meeting_title']),
        'subject' => sanitize_text_field($_POST['meeting_subject']),
        'aim' => sanitize_textarea_field($_POST['meeting_aim']),
        'meeting_type' => $meeting_type,
        'location' => $location,
        'lawyer_ids' => maybe_serialize($lawyer_ids),
        'clients' => maybe_serialize(array_map(null, $client_names, $client_emails)),
        'invoice_status' => 'pending'
    ]);

    $placeholders = implode(',', array_fill(0, count($lawyer_ids), '%d'));
    $lawyer_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_lawyers WHERE id IN ($placeholders)", $lawyer_ids));
    $lawyer_emails = [];
    $lawyer_names  = [];
    foreach ($lawyer_rows as $l) {
        $lawyer_emails[] = $l->email;
        $lawyer_names[]  = $l->name;
    }

    $meeting_data = [
        'title' => $_POST['meeting_title'],
        'subject' => $_POST['meeting_subject'],
        'aim' => $_POST['meeting_aim'],
        'datetime' => $_POST['meeting_datetime'],
        'meeting_link' => $link,
        'location' => $location,
        'meeting_type' => $meeting_type,
        'lawyer_names' => implode(', ', $lawyer_names),
        'client_names' => implode(', ', $client_names)
    ];

    hilton_send_meeting_email($client_emails, $lawyer_emails, $meeting_data);
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
echo '<th class="py-2 px-4">Name</th><th class="py-2 px-4">Email</th><th class="py-2 px-4">Rate</th><th class="py-2 px-4">Added</th><th class="py-2 px-4">Actions</th></tr></thead><tbody>';
foreach ($lawyers as $lawyer) {
    echo '<tr class="border-t text-sm">';
    echo '<td class="py-2 px-4">' . esc_html($lawyer->name) . '</td>';
    echo '<td class="py-2 px-4">' . esc_html($lawyer->email) . '</td>';
    echo '<td class="py-2 px-4">' . esc_html($lawyer->hourly_rate) . '</td>';
    echo '<td class="py-2 px-4">' . esc_html($lawyer->created_at) . '</td>';
    echo '<td class="py-2 px-4"><button onclick="toggleEditForm(' . esc_attr($lawyer->id) . ')" class="text-blue-600">Edit</button></td>';
    echo '</tr>';
    echo '<tr id="edit-row-' . esc_attr($lawyer->id) . '" class="hidden">';
    echo '<td colspan="5">';
    echo '<form method="post" class="grid grid-cols-4 gap-4 items-end">';
    echo '<input type="hidden" name="edit_lawyer_id" value="' . esc_attr($lawyer->id) . '" />';
    echo '<div><label class="block font-medium">Name</label><input type="text" name="lawyer_name" value="' . esc_attr($lawyer->name) . '" class="w-full border px-3 py-2 rounded" required></div>';
    echo '<div><label class="block font-medium">Email</label><input type="email" name="lawyer_email" value="' . esc_attr($lawyer->email) . '" class="w-full border px-3 py-2 rounded" required></div>';
    echo '<div><label class="block font-medium">Rate</label><input type="number" step="0.01" name="lawyer_rate" value="' . esc_attr($lawyer->hourly_rate) . '" class="w-full border px-3 py-2 rounded" required></div>';
    echo '<div><button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button></div>';
    echo '</form>';
    echo '</td></tr>';
}
echo '</tbody></table></div></div>';

// Schedule Meeting Tab
echo '<div id="tab-content-meeting" class="tab-content hidden">';
echo '<div class="bg-white p-6 rounded-lg shadow mb-6">';
echo '<h2 class="text-xl font-semibold mb-4">Schedule a Meeting</h2>';
echo '<form method="post" class="grid gap-6">';

// Participants section
echo '<div class="bg-gray-50 p-4 rounded"><h3 class="font-semibold mb-2">Participants</h3>';
echo '<div id="clients-container" class="space-y-4">';
echo '<div class="grid grid-cols-2 gap-4 client-fields">';
echo '<div><label class="block font-medium">Client Name</label><input type="text" name="client_names[]" required class="w-full border px-3 py-2 rounded" /></div>';
echo '<div><label class="block font-medium">Client Email</label><input type="email" name="client_emails[]" required class="w-full border px-3 py-2 rounded" /></div>';
echo '</div></div>';
echo '<button type="button" onclick="addClientFields()" class="text-blue-600 mt-2">Add Another Client</button>';
echo '<div class="mt-4"><label class="block font-medium">Lawyers</label><select name="lawyer_ids[]" multiple class="w-full border px-3 py-2 rounded">';
foreach ($lawyers as $lawyer) {
    echo '<option value="' . esc_attr($lawyer->id) . '">' . esc_html($lawyer->name) . '</option>';
}
echo '</select><p class="text-xs text-gray-500">Hold Ctrl/Cmd to select multiple</p></div>';
echo '</div>';

// Meeting info section
echo '<div class="bg-gray-50 p-4 rounded"><h3 class="font-semibold mb-2">Meeting Info</h3>';
echo '<div class="grid gap-4">';
echo '<div><label class="block font-medium">Meeting Title</label><input type="text" name="meeting_title" required class="w-full border px-3 py-2 rounded" /></div>';
echo '<div><label class="block font-medium">Subject</label><input type="text" name="meeting_subject" required class="w-full border px-3 py-2 rounded" /></div>';
echo '<div><label class="block font-medium">Aim / Objective</label><textarea name="meeting_aim" required class="w-full border px-3 py-2 rounded"></textarea></div>';
echo '<div><label class="block font-medium">Meeting Date & Time</label><input type="datetime-local" name="meeting_datetime" required class="w-full border px-3 py-2 rounded" /></div>';
echo '<div><label class="block font-medium">Meeting Type</label><select name="meeting_type" id="meeting_type" onchange="toggleMeetingType()" class="w-full border px-3 py-2 rounded"><option value="online">Online</option><option value="offline">Offline</option></select></div>';
echo '<div id="location-field" class="hidden"><label class="block font-medium">Meeting Location</label><input type="text" name="meeting_location" class="w-full border px-3 py-2 rounded" /></div>';
if (!$has_graph) {
    echo '<div id="link-field"><label class="block font-medium">Meeting Link</label><input type="url" name="meeting_link" class="w-full border px-3 py-2 rounded" /></div>';
} else {
    echo '<div id="link-field" class="hidden"></div>';
}
echo '<div><label class="block font-medium">Hourly Rate</label><input type="number" step="0.01" name="hourly_rate" required class="w-full border px-3 py-2 rounded" /></div>';
echo '</div></div>';

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
function toggleEditForm(id) {
    document.getElementById('edit-row-' + id).classList.toggle('hidden');
}
function addClientFields() {
    const container = document.getElementById('clients-container');
    const fields = document.querySelector('.client-fields');
    container.appendChild(fields.cloneNode(true));
}
function toggleMeetingType() {
    const type = document.getElementById('meeting_type').value;
    document.getElementById('location-field').classList.toggle('hidden', type !== 'offline');
    document.getElementById('link-field').classList.toggle('hidden', type !== 'online');
}
showTab("add"); // default tab
</script>';
