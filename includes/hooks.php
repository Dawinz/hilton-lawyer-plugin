<?php
add_action('admin_menu', 'hilton_register_admin_menu');

function hilton_register_admin_menu() {
    add_menu_page(
        'Hilton Scheduler',
        'Hilton Scheduler',
        'manage_options',
        'hilton-scheduler',
        'hilton_render_dashboard',
        'dashicons-calendar-alt',
        6
    );

    add_submenu_page(
        'hilton-scheduler',
        'Graph API Settings',
        'Graph API Settings',
        'manage_options',
        'hilton-graph-settings',
        'hilton_render_graph_settings'
    );
}

function hilton_render_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    include HILTON_PLUGIN_DIR . 'admin/dashboard.php';
}

function hilton_render_graph_settings() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('hilton_graph_settings')) {
        update_option('hilton_graph_client_id', sanitize_text_field($_POST['client_id']));
        update_option('hilton_graph_client_secret', sanitize_text_field($_POST['client_secret']));
        update_option('hilton_graph_tenant_id', sanitize_text_field($_POST['tenant_id']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $client_id = get_option('hilton_graph_client_id', '');
    $client_secret = get_option('hilton_graph_client_secret', '');
    $tenant_id = get_option('hilton_graph_tenant_id', '');

    echo '<div class="wrap"><h1>Microsoft Graph API Settings</h1>';
    echo '<form method="post"><table class="form-table">';
    wp_nonce_field('hilton_graph_settings');
    echo '<tr><th>Client ID</th><td><input type="text" name="client_id" value="' . esc_attr($client_id) . '" class="regular-text" required></td></tr>';
    echo '<tr><th>Client Secret</th><td><input type="text" name="client_secret" value="' . esc_attr($client_secret) . '" class="regular-text" required></td></tr>';
    echo '<tr><th>Tenant ID</th><td><input type="text" name="tenant_id" value="' . esc_attr($tenant_id) . '" class="regular-text" required></td></tr>';
    echo '</table><p><input type="submit" class="button button-primary" value="Save Settings"></p></form></div>';
}

add_action('admin_enqueue_scripts', 'hilton_enqueue_admin_styles');
function hilton_enqueue_admin_styles($hook) {
    if (strpos($hook, 'hilton') !== false) {
        wp_enqueue_style('tailwindcss', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');
    }
}
