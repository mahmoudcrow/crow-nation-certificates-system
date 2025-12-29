<?php
/**
 * Plugin Name: Crow Nation Certificates System
 * Description: Certificate verification system by Mahmoud Moustafa.
 * Version: 1.0
 * Author: Mahmoud Moustafa
 */

if (!defined('ABSPATH'))
    exit;

require_once plugin_dir_path(__FILE__) . 'includes/create-table.php';
require_once plugin_dir_path(__FILE__) . 'includes/certificate-functions.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/analytics-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/api.php';
require_once plugin_dir_path(__FILE__) . 'includes/github-updater.php';

new Crow_GitHub_Updater(
    __FILE__,
    'mahmoudcrow',
    'YOUR_REPO_NAME'
);

register_activation_hook(__FILE__, 'crow_create_certificates_table');

add_shortcode('crow_certificate_checker', 'crow_certificate_shortcode');

add_action('admin_menu', 'crow_register_admin_page');

function crow_register_admin_page(): void
{
    add_menu_page(
        'Crow Certificates',
        'Certificates',
        'manage_options',
        'crow-certificates',
        'crow_admin_page_html',
        'dashicons-awards',
        26
    );

    add_submenu_page(
        'crow-certificates',
        'Crow Analytics',
        'Analytics',
        'manage_options',
        'crow-certificates-analytics',
        'crow_analytics_page_html'
    );
}
