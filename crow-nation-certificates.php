<?php
/**
 * Plugin Name: Crow Nation Certificates System
 * Description: Certificate verification system by Mahmoud Moustafa.
 * Version: 1.0.8
 * Author: Mahmoud Moustafa
 * Text Domain: crow-certificates
 * Domain Path: /languages
 */

if (!defined('ABSPATH'))
    exit;

// تحديد مسار الإضافة الرئيسي
define('CROW_PLUGIN_FILE', __FILE__);
define('CROW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CROW_PLUGIN_URL', plugin_dir_url(__FILE__));

// تحديد نسخة قاعدة البيانات
define('CROW_DB_VERSION', '1.0.4');

// تحميل الترجمات
load_plugin_textdomain('crow-certificates', false, dirname(plugin_basename(__FILE__)) . '/languages/');

require_once plugin_dir_path(__FILE__) . 'includes/create-table.php';
require_once plugin_dir_path(__FILE__) . 'includes/database-migrations.php';
require_once plugin_dir_path(__FILE__) . 'includes/qrcode-library.php';
require_once plugin_dir_path(__FILE__) . 'includes/certificate-functions.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/certificates-list.php';
require_once plugin_dir_path(__FILE__) . 'admin/analytics-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'public/shortcode-display-new.php';
require_once plugin_dir_path(__FILE__) . 'public/shortcode-certificates-list.php';
require_once plugin_dir_path(__FILE__) . 'includes/api.php';
require_once plugin_dir_path(__FILE__) . 'includes/github-updater.php';

/**
 * Enqueue admin assets for plugin admin pages
 */
function crow_admin_assets_enqueue($hook)
{
    // only load on our plugin pages
    if (strpos($hook, 'crow-certificates') === false) {
        return;
    }

    wp_enqueue_style('crow-admin-style', plugin_dir_url(__FILE__) . 'assets/admin-style.css', [], '1.0.0');
}

add_action('admin_enqueue_scripts', 'crow_admin_assets_enqueue');

// تهيئة GitHub Updater - تأكد من تعديل البيانات:
global $crow_updater_instance;
$crow_updater_instance = new Crow_GitHub_Updater(
    __FILE__,
    'mahmoudcrow',              // ✏️ عدّل: ضع اسم حسابك على GitHub
    'crow-nation-certificates-system'   // ✏️ عدّل: ضع اسم الريبو على GitHub
);

register_activation_hook(__FILE__, function () {
    crow_create_certificates_table();
    crow_run_migrations();
});

// تشغيل الـ Migrations عند كل تحميل
add_action('init', 'crow_run_migrations');

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

    // تسجيل صفحة الإعدادات
    add_submenu_page(
        'crow-certificates',
        __('الإعدادات', 'crow-certificates'),
        __('⚙️ الإعدادات', 'crow-certificates'),
        'manage_options',
        'crow-certificates-settings',
        'crow_settings_page_html'
    );
}
