<?php
/**
 * Database Migrations - تحديث قاعدة البيانات
 * 
 * هذا الملف يتعامل مع تحديثات الجدول
 */

function crow_update_database_schema()
{
    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';
    $charset = $wpdb->get_charset_collate();

    // تحقق من وجود الجدول
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
        // الجدول غير موجود، أنشئه
        crow_create_certificates_table();
        return true;
    }

    // التحقق من وجود الأعمدة الناقصة وإضافتها
    $columns_to_add = [
        'email' => "ALTER TABLE $table ADD COLUMN email varchar(255) AFTER name",
        'qr_code_url' => "ALTER TABLE $table ADD COLUMN qr_code_url varchar(500) AFTER certificate_image",
        'created_at' => "ALTER TABLE $table ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP AFTER qr_code_url",
        'updated_at' => "ALTER TABLE $table ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
    ];

    foreach ($columns_to_add as $column_name => $alter_query) {
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE '$column_name'");

        if (empty($column_exists)) {
            $wpdb->query($alter_query);
        }
    }

    // إضافة الفهارس
    $wpdb->query("ALTER TABLE $table ADD KEY IF NOT EXISTS status (status)");
    $wpdb->query("ALTER TABLE $table ADD KEY IF NOT EXISTS created_at (created_at)");

    // تحديث الحقول الفارغة في الأعمدة الجديدة
    $wpdb->query("UPDATE $table SET status = 'active' WHERE status IS NULL OR status = ''");
    $wpdb->query("UPDATE $table SET created_at = NOW() WHERE created_at IS NULL");

    return true;
}

// تشغيل التحديث عند تفعيل الإضافة
function crow_run_migrations()
{
    if (get_option('crow_db_version') !== CROW_DB_VERSION) {
        crow_update_database_schema();
        update_option('crow_db_version', CROW_DB_VERSION);
    }
}

add_action('admin_init', 'crow_run_migrations');
