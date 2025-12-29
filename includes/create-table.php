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
        title varchar(255) NOT NULL,
        reason text,
        issue_date date,
        expiry_date date,
        status varchar(50),
        certificate_image varchar(255),
        PRIMARY KEY (id),
        UNIQUE KEY serial (serial)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}