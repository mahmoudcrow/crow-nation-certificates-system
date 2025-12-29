<?php

function crow_create_certificates_table()
{
    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';

    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        serial varchar(100) NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255),
        title varchar(255) NOT NULL,
        reason text,
        issue_date date,
        expiry_date date,
        status varchar(50) DEFAULT 'active',
        certificate_image varchar(500),
        qr_code_url varchar(500),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY serial (serial),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}