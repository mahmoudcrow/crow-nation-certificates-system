<?php
/**
 * Plugin Name: Crow Nation Certificates System
 * Description: Certificate verification system by Mahmoud Moustafa.
 * Version: 1.0.3
 * Author: Mahmoud Moustafa
 * Text Domain: crow-certificates
 * Domain Path: /languages
 */

if (!defined('ABSPATH'))
    exit;

// تحميل الترجمات
load_plugin_textdomain('crow-certificates', false, dirname(plugin_basename(__FILE__)) . '/languages/');

require_once plugin_dir_path(__FILE__) . 'includes/create-table.php';
require_once plugin_dir_path(__FILE__) . 'includes/certificate-functions.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/certificates-list.php';
require_once plugin_dir_path(__FILE__) . 'admin/analytics-page.php';
require_once plugin_dir_path(__FILE__) . 'public/shortcode-display-new.php';
require_once plugin_dir_path(__FILE__) . 'includes/api.php';
require_once plugin_dir_path(__FILE__) . 'includes/github-updater.php';

new Crow_GitHub_Updater(
    __FILE__,
    'mahmoudcrow',
    'YOUR_REPO_NAME'
);

register_activation_hook(__FILE__, 'crow_create_certificates_table');

add_action('admin_menu', 'crow_register_admin_page', 5);

function crow_register_admin_page(): void
{
    add_menu_page(
        __('نظام إدارة الشهادات', 'crow-certificates'),
        __('الشهادات', 'crow-certificates'),
        'manage_options',
        'crow-certificates',
        'crow_admin_page_html',
        'dashicons-awards',
        26
    );

    add_submenu_page(
        'crow-certificates',
        __('التحليلات', 'crow-certificates'),
        __('التحليلات', 'crow-certificates'),
        'manage_options',
        'crow-certificates-analytics',
        'crow_analytics_page_html'
    );

    // تسجيل صفحة الشهادات
    add_submenu_page(
        'crow-certificates',
        __('قائمة الشهادات', 'crow-certificates'),
        __('📊 قائمة الشهادات', 'crow-certificates'),
        'manage_options',
        'crow-certificates-list',
        'crow_certificates_list_page'
    );
}
