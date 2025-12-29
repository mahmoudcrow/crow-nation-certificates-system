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

    <div class="wrap">
        <h1>🎓 إدارة شهادات Crow Nation</h1>

        <!-- SHORTCODE DISPLAY -->
        <div style="background: linear-gradient(135deg, #0099CC 0%, #00A8D8 100%); 
                    color: white; 
                    padding: 20px; 
                    border-radius: 8px; 
                    margin-bottom: 30px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
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
                <button type="button" 
                        onclick="copyToClipboard('[crow_certificate_checker]')"
                        style="background: white; 
                               color: #0099CC; 
                               border: none; 
                               padding: 10px 20px; 
                               border-radius: 4px; 
                               cursor: pointer; 
                               font-weight: bold;
                               font-size: 14px;
                               transition: all 0.3s ease;"
                        onmouseover="this.style.transform='scale(1.05)'"
                        onmouseout="this.style.transform='scale(1)'">
                    📋 نسخ الشورتكود
                </button>
            </div>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">
                ✨ استخدم هذا الشورتكود في أي صفحة لإضافة نموذج البحث عن الشهادات
            </p>
        </div>

        <!-- EXPORT / IMPORT -->
        <div class="crow-search-box">
            <form method="post" style="display:flex; gap:12px; flex:1;">
                <?php wp_nonce_field('crow_certificate_action', 'crow_nonce'); ?>
                <button name="crow_export_csv" class="button button-secondary" style="flex-shrink:0;">
                    📥 تصدير CSV
                </button>
            </form>

            <form method="post" enctype="multipart/form-data" style="display:flex; gap:12px; flex:1;">
                <?php wp_nonce_field('crow_certificate_action', 'crow_nonce'); ?>
                <input type="file" name="csv_file" accept=".csv" required style="flex:1; max-width:none;">
                <button name="crow_import_csv" class="button button-secondary" style="flex-shrink:0;">
                    📤 استيراد CSV
                </button>
            </form>
        </div>

        <h2><?= $edit_cert ? "✏️ تعديل الشهادة" : "➕ إضافة شهادة جديدة" ?></h2>

        <form method="post" class="crow-admin-form" enctype="multipart/form-data">
            <?php wp_nonce_field('crow_certificate_action', 'crow_nonce'); ?>
            <input type="hidden" name="cert_id" value="<?= esc_attr($edit_cert->id ?? '') ?>">

            <table class="form-table">
                <tr>
                    <th>السيريال</th>
                    <td><input type="text" name="serial" value="<?= esc_attr($edit_cert->serial ?? '') ?>" required></td>
                </tr>
                <tr>
                    <th>اسم المتدرب</th>
                    <td><input type="text" name="name" value="<?= esc_attr($edit_cert->name ?? '') ?>" required></td>
                </tr>
                <tr>
                    <th>البريد الإلكتروني</th>
                    <td><input type="email" name="email" value="<?= esc_attr($edit_cert->email ?? '') ?>"></td>
                </tr>
                <tr>
                    <th>عنوان الشهادة</th>
                    <td><input type="text" name="title" value="<?= esc_attr($edit_cert->title ?? '') ?>" required></td>
                </tr>
                <tr>
                    <th>السبب / البرنامج</th>
                    <td><textarea name="reason"><?= esc_textarea($edit_cert->reason ?? '') ?></textarea></td>
                </tr>
                <tr>
                    <th>تاريخ الإصدار</th>
                    <td><input type="date" name="issue_date" value="<?= esc_attr($edit_cert->issue_date ?? '') ?>"></td>
                </tr>
                <tr>
                    <th>تاريخ الانتهاء</th>
                    <td><input type="date" name="expiry_date" value="<?= esc_attr($edit_cert->expiry_date ?? '') ?>"></td>
                </tr>
                <tr>
                    <th>الحالة</th>
                    <td>
                        <select name="status">
                            <option value="active" <?= isset($edit_cert) && $edit_cert->status == 'active' ? 'selected' : '' ?>>✅ نشط</option>
                            <option value="expired" <?= isset($edit_cert) && $edit_cert->status == 'expired' ? 'selected' : '' ?>>⏰ منتهي</option>
                            <option value="revoked" <?= isset($edit_cert) && $edit_cert->status == 'revoked' ? 'selected' : '' ?>>❌ ملغى</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th>صورة الشهادة</th>
                    <td>
                        <input type="file" name="certificate_image" accept="image/*">
                        <?php if (!empty($edit_cert->certificate_image)): ?>
                            <div style="margin-top:10px;">
                                <img src="<?= esc_url($edit_cert->certificate_image) ?>" style="max-width:200px;">
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <input type="submit" name="crow_save_certificate" class="button button-primary" style="margin-top:20px;"
                   value="<?= $edit_cert ? '✅ تحديث الشهادة' : '➕ إضافة الشهادة' ?>">
        </form>

        <hr>

        <h2>📊 جميع الشهادات</h2>

        <form method="post" class="crow-search-box">
            <?php wp_nonce_field('crow_certificate_action', 'crow_nonce'); ?>
            <input type="text" name="crow_search" placeholder="🔍 ابحث عن سيريال أو اسم أو عنوان..."
                value="<?= isset($_POST['crow_search']) ? esc_attr($_POST['crow_search']) : '' ?>" style="max-width:none;">
            <button type="submit" class="button button-primary" style="flex-shrink:0;">بحث</button>
        </form>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>رقم</th>
                    <th>السيريال</th>
                    <th>الاسم</th>
                    <th>العنوان</th>
                    <th>تاريخ الإصدار</th>
                    <th>الحالة</th>
                    <th>QR</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($certificates as $cert): ?>
                    <tr>
                        <td><?= $cert->id ?></td>
                        <td><code><?= esc_html($cert->serial) ?></code></td>
                        <td><?= esc_html($cert->name) ?></td>
                        <td><?= esc_html($cert->title) ?></td>
                        <td><?= esc_html(date_i18n('d/m/Y', strtotime($cert->issue_date))) ?></td>
                        <td>
                            <?php 
                            $status_badges = [
                                'active' => '✅ نشط',
                                'expired' => '⏰ منتهي',
                                'revoked' => '❌ ملغى'
                            ];
                            echo $status_badges[$cert->status] ?? $cert->status;
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($cert->qr_code_url)): ?>
                                <img src="<?= esc_url($cert->qr_code_url) ?>" style="width:60px; cursor:pointer;" title="QR Code">
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=crow-certificates&action=edit&id=' . $cert->id); ?>"
                                class="button button-small">✏️ تعديل</a>

                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=crow-certificates&action=delete&id=' . $cert->id), 'crow_delete_' . $cert->id)); ?>"
                                class="button button-small button-danger"
                                onclick="return confirm('هل تريد حذف هذه الشهادة؟');">🗑️ حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>

    <script>
    function copyToClipboard(text) {
        // استخدام Clipboard API الحديث
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showCopyNotification();
            }).catch(function(err) {
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

        setTimeout(function() {
            notification.remove();
        }, 3000);
    }
    </script>

    <?php
}
?>