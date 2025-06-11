<?php
function hilton_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $lawyers = $wpdb->prefix . 'hilton_lawyers';
    $meetings = $wpdb->prefix . 'hilton_meetings';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql1 = "CREATE TABLE $lawyers (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        email VARCHAR(255),
        hourly_rate DECIMAL(10,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset;";

    $sql2 = "CREATE TABLE $meetings (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(255),
        client_email VARCHAR(255),
        lawyer_id BIGINT,
        meeting_datetime DATETIME,
        meeting_link TEXT,
        hourly_rate DECIMAL(10,2),
        start_time DATETIME,
        end_time DATETIME,
        duration INT,
        total DECIMAL(10,2),
        invoice_status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset;";

    dbDelta($sql1);
    dbDelta($sql2);

    add_option('hilton_graph_client_id', '');
    add_option('hilton_graph_client_secret', '');
    add_option('hilton_graph_tenant_id', '');
}
