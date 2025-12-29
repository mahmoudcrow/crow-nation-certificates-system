<?php

function crow_admin_page_html()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('ليس لديك صلاحيات كافية للوصول إلى هذه الصفحة', 'crow-certificates'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';

    /* -----------------------------------------------------------
        LOAD WORDPRESS MEDIA FUNCTIONS (FOR IMAGE UPLOAD)
    ------------------------------------------------------------ */
    if (!function_exists('media_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }

    /* -----------------------------------------------------------
        ACTION HANDLING (EDIT / DELETE)
    ------------------------------------------------------------ */
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
    $edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $edit_cert = null;

    // DELETE
    if ($action === 'delete' && $edit_id) {
        if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'crow_delete_' . $edit_id)) {
            // حذف الصورة المرتبطة
            $cert = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $edit_id));
            if ($cert && !empty($cert->certificate_image)) {
                $attachment_id = attachment_url_to_postid($cert->certificate_image);
                if ($attachment_id) {
                    wp_delete_attachment($attachment_id, true);
                }
            }

            $wpdb->delete($table, ['id' => $edit_id]);
            echo '<div class="notice notice-success is-dismissible"><p>✅ تم حذف الشهادة</p></div>';
        }
    }

    // EDIT (LOAD DATA)
    if ($action === 'edit' && $edit_id) {
        $edit_cert = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $edit_id));
    }

    /* -----------------------------------------------------------
        SAVE (ADD / UPDATE)
    ------------------------------------------------------------ */
    if (isset($_POST['crow_save_certificate'])) {
        if (!isset($_POST['crow_nonce']) || !wp_verify_nonce($_POST['crow_nonce'], 'crow_certificate_action')) {
            wp_die('الطلب غير آمن');
        }

        $data = [
            'serial' => sanitize_text_field($_POST['serial']),
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'title' => sanitize_text_field($_POST['title']),
            'reason' => sanitize_textarea_field($_POST['reason']),
            'issue_date' => sanitize_text_field($_POST['issue_date']),
            'expiry_date' => sanitize_text_field($_POST['expiry_date']),
            'status' => sanitize_text_field($_POST['status']),
        ];

        /* ---- QR CODE AUTO GENERATION ---- */
        $verify_url = home_url('/?crow_verify=' . urlencode($data['serial']));
        $qr_url = 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' . urlencode($verify_url);
        $data['qr_code_url'] = $qr_url;

        /* ---- IMAGE UPLOAD ---- */
        if (!empty($_FILES['certificate_image']['name'])) {
            $cert_id = intval($_POST['cert_id'] ?? 0);

            // حذف الصورة القديمة إذا كانت موجودة
            if ($cert_id) {
                $old_cert = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $cert_id));
                if ($old_cert && !empty($old_cert->certificate_image)) {
                    $attachment_id = attachment_url_to_postid($old_cert->certificate_image);
                    if ($attachment_id) {
                        wp_delete_attachment($attachment_id, true);
                    }
                }
            }

            $attachment_id = media_handle_upload('certificate_image', 0);
            if (!is_wp_error($attachment_id)) {
                $data['certificate_image'] = wp_get_attachment_url($attachment_id);
            }
        }

        /* ---- UPDATE ---- */
        if (!empty($_POST['cert_id'])) {
            $cert_id = intval($_POST['cert_id']);
            $wpdb->update($table, $data, ['id' => $cert_id], null, ['%d']);
            echo '<div class="notice notice-success is-dismissible"><p>✅ تم تحديث الشهادة بنجاح</p></div>';
        }
        /* ---- INSERT ---- */ else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table, $data);
            echo '<div class="notice notice-success is-dismissible"><p>✅ تم إضافة الشهادة بنجاح</p></div>';
        }
    }

    /* -----------------------------------------------------------
        EXPORT CSV
    ------------------------------------------------------------ */
    if (isset($_POST['crow_export_csv'])) {
        if (!isset($_POST['crow_nonce']) || !wp_verify_nonce($_POST['crow_nonce'], 'crow_certificate_action')) {
            wp_die('الطلب غير آمن');
        }

        $rows = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=crow-certificates-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        if (!empty($rows)) {
            fputcsv($output, array_keys($rows[0]));
            foreach ($rows as $row)
                fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /* -----------------------------------------------------------
        IMPORT CSV
    ------------------------------------------------------------ */
    if (isset($_POST['crow_import_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {
        if (!isset($_POST['crow_nonce']) || !wp_verify_nonce($_POST['crow_nonce'], 'crow_certificate_action')) {
            wp_die('الطلب غير آمن');
        }

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);
            if (!empty($data['serial'])) {
                $wpdb->replace($table, $data);
            }
        }

        fclose($file);
        echo '<div class="notice notice-success is-dismissible"><p>✅ تم استيراد البيانات بنجاح</p></div>';
    }

    /* -----------------------------------------------------------
        FETCH ALL CERTIFICATES (WITH SEARCH)
    ------------------------------------------------------------ */
    $search = isset($_POST['crow_search']) ? sanitize_text_field($_POST['crow_search']) : '';

    if (!empty($search)) {
        $search_like = '%' . $wpdb->esc_like($search) . '%';
        $certificates = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE serial LIKE %s OR name LIKE %s OR title LIKE %s ORDER BY id DESC",
                $search_like,
                $search_like,
                $search_like
            )
        );
    } else {
        $certificates = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    }

    ?>

    <div class="wrap crow-admin-wrap">
        <h1>🎓 إدارة شهادات Crow Nation</h1>

        <!-- SHORTCODE DISPLAY -->
        <div class="crow-card shortcode"
            style="background: linear-gradient(135deg, #0099CC 0%, #00A8D8 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; color: white;">📋 الشورتكود - انسخه والصقه في صفحتك</h2>
            <div style="background: rgba(255,255,255,0.1); 
                        padding: 15px; 
                        border-radius: 6px; 
                        border-left: 4px solid #fff;
                        margin: 10px 0;">
                <code style="font-size: 16px; 
                            font-weight: bold; 
                            color: #fff; 
                            word-break: break-all;
                            display: block;
                            margin-bottom: 15px;">
                            [crow_certificate_checker]
                        </code>
                <button type="button" onclick="copyToClipboard('[crow_certificate_checker]')" style="background: white; 
                               color: #0099CC; 
                               border: none; 
                               padding: 10px 20px; 
                               border-radius: 4px; 
                               cursor: pointer; 
                               font-weight: bold;
                               font-size: 14px;
                               transition: all 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'"
                    onmouseout="this.style.transform='scale(1)'">
                    📋 نسخ الشورتكود
                </button>
            </div>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">
                ✨ استخدم هذا الشورتكود في أي صفحة لإضافة نموذج البحث عن الشهادات
            </p>
        </div>

        <!-- EXPORT / IMPORT SECTION -->
        <div
            style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #0099CC;">
            <h3 style="margin-top: 0; color: #0099CC;">📊 استيراد وتصدير البيانات</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Export -->
                <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #ddd;">
                    <h4 style="margin-top: 0;">📥 تصدير إلى CSV</h4>
                    <p style="color: #666; margin: 5px 0;">قم بتنزيل جميع الشهادات كملف CSV</p>
                    <form method="post" style="margin: 0;">
                        <?php wp_nonce_field('crow_certificate_action', 'crow_nonce'); ?>
                        <button name="crow_export_csv" class="button button-primary" style="width: 100%;">
                            📥 تحميل الملف
                        </button>
                    </form>
                </div>

                <!-- Import -->
                <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #ddd;">
                    <h4 style="margin-top: 0;">📤 استيراد من CSV</h4>
                    <p style="color: #666; margin: 5px 0;">اختر ملف CSV لإضافة شهادات جماعية</p>
                    <form method="post" enctype="multipart/form-data" style="margin: 0;">
                        <?php wp_nonce_field('crow_certificate_action', 'crow_nonce'); ?>
                        <div style="display: flex; gap: 10px;">
                            <input type="file" name="csv_file" accept=".csv" required
                                style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <button name="crow_import_csv" class="button button-primary"
                                style="flex-shrink: 0;">رفع</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ADD/EDIT FORM SECTION -->
        <div class="crow-card crow-form-card"
            style="background: white; padding: 30px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 30px;">
            <h2 style="margin-top: 0; color: #0099CC;"><?= $edit_cert ? "✏️ تعديل الشهادة" : "➕ إضافة شهادة جديدة" ?>
            </h2>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('crow_certificate_action', 'crow_nonce'); ?>
                <input type="hidden" name="cert_id" value="<?= esc_attr($edit_cert->id ?? '') ?>">

                <!-- معلومات أساسية -->
                <fieldset
                    style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                    <legend style="padding: 0 10px; font-weight: bold; color: #0099CC;">📝 معلومات أساسية</legend>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">
                                🔤 السيريال <span style="color: red;">*</span>
                            </label>
                            <input type="text" name="serial" value="<?= esc_attr($edit_cert->serial ?? '') ?>" required
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">
                                👤 اسم المتدرب <span style="color: red;">*</span>
                            </label>
                            <input type="text" name="name" value="<?= esc_attr($edit_cert->name ?? '') ?>" required
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">
                                ✉️ البريد الإلكتروني
                            </label>
                            <input type="email" name="email" value="<?= esc_attr($edit_cert->email ?? '') ?>"
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">
                                🎓 عنوان الشهادة <span style="color: red;">*</span>
                            </label>
                            <input type="text" name="title" value="<?= esc_attr($edit_cert->title ?? '') ?>" required
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">
                            📌 السبب / البرنامج
                        </label>
                        <textarea name="reason"
                            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; min-height: 100px;"><?= esc_textarea($edit_cert->reason ?? '') ?></textarea>
                    </div>
                </fieldset>

                <!-- التواريخ -->
                <fieldset
                    style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                    <legend style="padding: 0 10px; font-weight: bold; color: #0099CC;">📅 التواريخ</legend>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">
                                ✅ تاريخ الإصدار
                            </label>
                            <input type="date" name="issue_date" value="<?= esc_attr($edit_cert->issue_date ?? '') ?>"
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">
                                ⏰ تاريخ الانتهاء (اختياري)
                            </label>
                            <input type="date" name="expiry_date" value="<?= esc_attr($edit_cert->expiry_date ?? '') ?>"
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
                        </div>
                    </div>
                </fieldset>

                <!-- الحالة والصورة -->
                <fieldset
                    style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                    <legend style="padding: 0 10px; font-weight: bold; color: #0099CC;">⚙️ الحالة والصورة</legend>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">
                                🔔 حالة الشهادة
                            </label>
                            <select name="status"
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
                                <option value="active" <?= isset($edit_cert) && $edit_cert->status == 'active' ? 'selected' : '' ?>>✅ نشطة</option>
                                <option value="expired" <?= isset($edit_cert) && $edit_cert->status == 'expired' ? 'selected' : '' ?>>⏰ منتهية</option>
                                <option value="revoked" <?= isset($edit_cert) && $edit_cert->status == 'revoked' ? 'selected' : '' ?>>❌ ملغاة</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">
                                🖼️ صورة الشهادة
                            </label>
                            <input type="file" name="certificate_image" accept="image/*"
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
                        </div>
                    </div>

                    <?php if (!empty($edit_cert->certificate_image)): ?>
                        <div style="margin-top: 15px;">
                            <p style="margin: 5px 0; font-weight: bold;">صورة حالية:</p>
                            <img src="<?= esc_url($edit_cert->certificate_image) ?>"
                                style="max-width: 250px; height: auto; border-radius: 6px; border: 1px solid #ddd;">
                        </div>
                    <?php endif; ?>
                </fieldset>

                <!-- زر الحفظ -->
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="submit" name="crow_save_certificate" class="button button-primary"
                        style="padding: 12px 30px; font-size: 16px; font-weight: bold; cursor: pointer; min-width: 200px;">
                        <?= $edit_cert ? '✅ تحديث الشهادة' : '➕ إضافة الشهادة' ?>
                    </button>
                    <?php if ($edit_cert): ?>
                        <a href="<?php echo admin_url('admin.php?page=crow-certificates'); ?>" class="button button-secondary"
                            style="padding: 12px 30px; font-size: 16px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center;">
                            ❌ إلغاء
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- جدول جميع الشهادات -->
        <div class="crow-card crow-table-card"
            style="background: white; padding: 30px; border-radius: 8px; border: 1px solid #ddd; margin-top: 30px;">
            <h2 style="margin-top: 0; color: #0099CC;">📊 جميع الشهادات</h2>

            <form method="post" class="crow-search-box" style="margin-bottom: 20px;">
                <?php wp_nonce_field('crow_certificate_action', 'crow_nonce'); ?>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="crow_search" placeholder="🔍 ابحث عن سيريال أو اسم أو عنوان..."
                        value="<?= isset($_POST['crow_search']) ? esc_attr($_POST['crow_search']) : '' ?>"
                        style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <button type="submit" class="button button-primary" style="flex-shrink:0;">بحث</button>
                </div>
            </form>

            <div style="overflow-x: auto;">
                <table class="widefat fixed striped" style="margin: 0;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #0099CC;">
                            <th style="padding: 12px; text-align: center; color: #0099CC; font-weight: bold;">رقم</th>
                            <th style="padding: 12px; color: #0099CC; font-weight: bold;">السيريال</th>
                            <th style="padding: 12px; color: #0099CC; font-weight: bold;">الاسم</th>
                            <th style="padding: 12px; color: #0099CC; font-weight: bold;">العنوان</th>
                            <th style="padding: 12px; color: #0099CC; font-weight: bold;">تاريخ الإصدار</th>
                            <th style="padding: 12px; color: #0099CC; font-weight: bold;">الحالة</th>
                            <th style="padding: 12px; text-align: center; color: #0099CC; font-weight: bold;">QR</th>
                            <th style="padding: 12px; text-align: center; color: #0099CC; font-weight: bold;">الإجراءات
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($certificates)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px; color: #999;">
                                    📭 لا توجد شهادات حالياً
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($certificates as $cert): ?>
                                <tr style="border-bottom: 1px solid #e0e0e0; transition: background 0.2s;"
                                    onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background=''">
                                    <td style="padding: 12px; text-align: center; font-weight: bold;"><?= $cert->id ?></td>
                                    <td style="padding: 12px;">
                                        <code style="background: #f0f0f0; padding: 4px 8px; border-radius: 3px; font-size: 12px;">
                                                                    <?= esc_html($cert->serial) ?>
                                                                </code>
                                    </td>
                                    <td style="padding: 12px;"><?= esc_html($cert->name) ?></td>
                                    <td
                                        style="padding: 12px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= esc_html($cert->title) ?>
                                    </td>
                                    <td style="padding: 12px;"><?= esc_html(date_i18n('d/m/Y', strtotime($cert->issue_date))) ?>
                                    </td>
                                    <td style="padding: 12px;">
                                        <?php
                                        $status_badges = [
                                            'active' => '<span style="background: #1BC47D; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">✅ نشطة</span>',
                                            'expired' => '<span style="background: #FFC107; color: #333; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">⏰ منتهية</span>',
                                            'revoked' => '<span style="background: #DC3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">❌ ملغاة</span>'
                                        ];
                                        echo $status_badges[$cert->status] ?? $cert->status;
                                        ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <?php if (!empty($cert->qr_code_url)): ?>
                                            <a href="<?= esc_url($cert->qr_code_url) ?>" target="_blank" title="فتح QR Code">
                                                <img src="<?= esc_url($cert->qr_code_url) ?>"
                                                    style="width: 50px; height: 50px; cursor: pointer; border-radius: 4px;">
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #ccc;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <div style="display: flex; gap: 5px; justify-content: center;">
                                            <a href="<?php echo admin_url('admin.php?page=crow-certificates&action=edit&id=' . $cert->id); ?>"
                                                class="button button-small" style="padding: 5px 10px; font-size: 12px;">✏️</a>

                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=crow-certificates&action=delete&id=' . $cert->id), 'crow_delete_' . $cert->id)); ?>"
                                                class="button button-small button-danger"
                                                onclick="return confirm('هل تريد حذف هذه الشهادة؟');"
                                                style="padding: 5px 10px; font-size: 12px;">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        function copyToClipboard(text) {
            // استخدام Clipboard API الحديث
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    showCopyNotification();
                }).catch(function (err) {
                    // fallback للمتصفحات القديمة
                    fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        }

        function fallbackCopyToClipboard(text) {
            var textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                showCopyNotification();
            } catch (err) {
                console.error('خطأ في النسخ:', err);
            }
            document.body.removeChild(textArea);
        }

        function showCopyNotification() {
            var notification = document.createElement('div');
            notification.innerHTML = '✅ تم نسخ الشورتكود بنجاح!';
            notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1BC47D;
            color: white;
            padding: 15px 25px;
            border-radius: 4px;
            font-weight: bold;
            z-index: 9999;
            animation: slideIn 0.3s ease, slideOut 0.3s ease 2.7s forwards;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        `;

            var style = document.createElement('style');
            style.innerHTML = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
            document.head.appendChild(style);
            document.body.appendChild(notification);

            setTimeout(function () {
                notification.remove();
            }, 3000);
        }
    </script>

    <?php
}
?>