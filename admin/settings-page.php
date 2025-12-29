<?php
/**
 * صفحة الإعدادات - Settings Page
 * تسمح للمسؤولين بتخصيص ألوان الواجهة
 */

function crow_settings_page_html()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('ليس لديك صلاحيات كافية للوصول إلى هذه الصفحة', 'crow-certificates'));
    }

    // حفظ الإعدادات
    if (isset($_POST['crow_save_settings'])) {
        if (!isset($_POST['crow_settings_nonce']) || !wp_verify_nonce($_POST['crow_settings_nonce'], 'crow_settings_action')) {
            wp_die('الطلب غير آمن');
        }

        // حفظ الألوان
        update_option('crow_search_container_bg', sanitize_text_field($_POST['crow_search_container_bg'] ?? '#f8f9fa'));
        update_option('crow_search_button_bg', sanitize_text_field($_POST['crow_search_button_bg'] ?? '#0099CC'));
        update_option('crow_search_button_text', sanitize_text_field($_POST['crow_search_button_text'] ?? '#ffffff'));
        update_option('crow_search_input_border', sanitize_text_field($_POST['crow_search_input_border'] ?? '#ddd'));
        update_option('crow_header_bg', sanitize_text_field($_POST['crow_header_bg'] ?? '#0099CC'));
        update_option('crow_header_text', sanitize_text_field($_POST['crow_header_text'] ?? '#ffffff'));
        update_option('crow_success_bg', sanitize_text_field($_POST['crow_success_bg'] ?? '#1BC47D'));
        update_option('crow_error_bg', sanitize_text_field($_POST['crow_error_bg'] ?? '#DC3545'));

        echo '<div class="notice notice-success is-dismissible"><p>✅ تم حفظ الإعدادات بنجاح</p></div>';
    }

    // الحصول على الإعدادات المحفوظة
    $search_container_bg = get_option('crow_search_container_bg', '#f8f9fa');
    $search_button_bg = get_option('crow_search_button_bg', '#0099CC');
    $search_button_text = get_option('crow_search_button_text', '#ffffff');
    $search_input_border = get_option('crow_search_input_border', '#ddd');
    $header_bg = get_option('crow_header_bg', '#0099CC');
    $header_text = get_option('crow_header_text', '#ffffff');
    $success_bg = get_option('crow_success_bg', '#1BC47D');
    $error_bg = get_option('crow_error_bg', '#DC3545');

    ?>

    <div class="wrap">
        <h1>⚙️ إعدادات نظام التحقق من الشهادات</h1>

        <!-- Preview Section -->
        <div
            style="background: white; padding: 30px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; color: #0099CC;">👁️ معاينة البحث الحي</h2>
            <p style="color: #666; margin-bottom: 20px;">هذا كيف سيبدو مربع البحث بالألوان المختارة:</p>

            <!-- Live Preview -->
            <div
                style="background: <?= esc_attr($search_container_bg) ?>; padding: 20px; border-radius: 8px; display: inline-block; min-width: 400px;">
                <div style="text-align: center; margin-bottom: 15px;">
                    <h3
                        style="margin: 0; color: <?= esc_attr($header_text) ?>; background: <?= esc_attr($header_bg) ?>; padding: 10px; border-radius: 4px;">
                        🎓 التحقق من الشهادة
                    </h3>
                </div>
                <form style="display: flex; gap: 10px;">
                    <input type="text" placeholder="أدخل رقم السيريال..."
                        style="flex: 1; padding: 12px; border: 2px solid <?= esc_attr($search_input_border) ?>; border-radius: 4px; font-size: 14px;">
                    <button type="button"
                        style="background: <?= esc_attr($search_button_bg) ?>; color: <?= esc_attr($search_button_text) ?>; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.3s;">
                        🔎 بحث
                    </button>
                </form>
            </div>
        </div>

        <!-- Settings Form -->
        <form method="post" style="background: white; padding: 30px; border-radius: 8px; border: 1px solid #ddd;">
            <?php wp_nonce_field('crow_settings_action', 'crow_settings_nonce'); ?>

            <!-- Search Container Color -->
            <fieldset
                style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <legend style="padding: 0 10px; font-weight: bold; color: #0099CC;">🎨 ألوان مربع البحث</legend>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <!-- Search Container Background -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">
                            🏙️ خلفية المربع
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" name="crow_search_container_bg"
                                value="<?= esc_attr($search_container_bg) ?>"
                                style="width: 50px; height: 50px; cursor: pointer; border: none; border-radius: 4px;">
                            <input type="text" value="<?= esc_attr($search_container_bg) ?>" readonly
                                style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f0f0f0;">
                        </div>
                    </div>

                    <!-- Search Button Background -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">
                            🔵 لون زر البحث
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" name="crow_search_button_bg" value="<?= esc_attr($search_button_bg) ?>"
                                style="width: 50px; height: 50px; cursor: pointer; border: none; border-radius: 4px;">
                            <input type="text" value="<?= esc_attr($search_button_bg) ?>" readonly
                                style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f0f0f0;">
                        </div>
                    </div>

                    <!-- Search Button Text Color -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">
                            ✍️ لون نص الزر
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" name="crow_search_button_text" value="<?= esc_attr($search_button_text) ?>"
                                style="width: 50px; height: 50px; cursor: pointer; border: none; border-radius: 4px;">
                            <input type="text" value="<?= esc_attr($search_button_text) ?>" readonly
                                style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f0f0f0;">
                        </div>
                    </div>

                    <!-- Search Input Border -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">
                            📝 لون حدود الإدخال
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" name="crow_search_input_border"
                                value="<?= esc_attr($search_input_border) ?>"
                                style="width: 50px; height: 50px; cursor: pointer; border: none; border-radius: 4px;">
                            <input type="text" value="<?= esc_attr($search_input_border) ?>" readonly
                                style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f0f0f0;">
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Header Colors -->
            <fieldset
                style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <legend style="padding: 0 10px; font-weight: bold; color: #0099CC;">📋 ألوان الرأس</legend>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <!-- Header Background -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">
                            🎯 خلفية الرأس
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" name="crow_header_bg" value="<?= esc_attr($header_bg) ?>"
                                style="width: 50px; height: 50px; cursor: pointer; border: none; border-radius: 4px;">
                            <input type="text" value="<?= esc_attr($header_bg) ?>" readonly
                                style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f0f0f0;">
                        </div>
                    </div>

                    <!-- Header Text Color -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">
                            📄 لون نص الرأس
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" name="crow_header_text" value="<?= esc_attr($header_text) ?>"
                                style="width: 50px; height: 50px; cursor: pointer; border: none; border-radius: 4px;">
                            <input type="text" value="<?= esc_attr($header_text) ?>" readonly
                                style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f0f0f0;">
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Status Colors -->
            <fieldset
                style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <legend style="padding: 0 10px; font-weight: bold; color: #0099CC;">✅ ألوان الحالات</legend>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <!-- Success Color -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">
                            ✅ لون النجاح
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" name="crow_success_bg" value="<?= esc_attr($success_bg) ?>"
                                style="width: 50px; height: 50px; cursor: pointer; border: none; border-radius: 4px;">
                            <input type="text" value="<?= esc_attr($success_bg) ?>" readonly
                                style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f0f0f0;">
                        </div>
                    </div>

                    <!-- Error Color -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">
                            ❌ لون الخطأ
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" name="crow_error_bg" value="<?= esc_attr($error_bg) ?>"
                                style="width: 50px; height: 50px; cursor: pointer; border: none; border-radius: 4px;">
                            <input type="text" value="<?= esc_attr($error_bg) ?>" readonly
                                style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f0f0f0;">
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Save Button -->
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="submit" name="crow_save_settings" class="button button-primary"
                    style="padding: 12px 30px; font-size: 16px; font-weight: bold; cursor: pointer;">
                    💾 حفظ الإعدادات
                </button>
                <button type="reset" class="button button-secondary"
                    style="padding: 12px 30px; font-size: 16px; font-weight: bold; cursor: pointer;">
                    ↩️ إعادة تعيين
                </button>
            </div>

            <p style="color: #666; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center;">
                💡 <strong>نصيحة:</strong> الألوان ستنعكس تلقائياً على الشورتكود <code>[crow_certificate_checker]</code>
            </p>
        </form>
    </div>

    <?php
}

// دالة للحصول على الألوان المحفوظة
function crow_get_color_settings()
{
    return [
        'search_container_bg' => get_option('crow_search_container_bg', '#f8f9fa'),
        'search_button_bg' => get_option('crow_search_button_bg', '#0099CC'),
        'search_button_text' => get_option('crow_search_button_text', '#ffffff'),
        'search_input_border' => get_option('crow_search_input_border', '#ddd'),
        'header_bg' => get_option('crow_header_bg', '#0099CC'),
        'header_text' => get_option('crow_header_text', '#ffffff'),
        'success_bg' => get_option('crow_success_bg', '#1BC47D'),
        'error_bg' => get_option('crow_error_bg', '#DC3545'),
    ];
}
