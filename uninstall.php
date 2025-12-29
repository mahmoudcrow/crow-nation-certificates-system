<?php

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Table name
$table = $wpdb->prefix . 'crow_certificates';

// Delete table
$wpdb->query("DROP TABLE IF EXISTS $table");

// Optional: delete plugin options (لو استخدمت أي Options)
// delete_option('crow_certificates_settings');
// delete_option('crow_certificates_version');
