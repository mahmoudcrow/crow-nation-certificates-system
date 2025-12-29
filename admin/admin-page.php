<?php

function crow_admin_page_html()
{
    if (!current_user_can('manage_options')) return;

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
            $wpdb->delete($table, ['id' => $edit_id]);
            echo '<div class="updated"><p>تم حذف الشهادة</p></div>';
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

        $data = [
            'serial'      => sanitize_text_field($_POST['serial']),
            'name'        => sanitize_text_field($_POST['name']),
            'title'       => sanitize_text_field($_POST['title']),
            'reason'      => sanitize_textarea_field($_POST['reason']),
            'issue_date'  => sanitize_text_field($_POST['issue_date']),
            'expiry_date' => sanitize_text_field($_POST['expiry_date']),
            'status'      => sanitize_text_field($_POST['status']),
        ];

        /* ---- QR CODE AUTO GENERATION ---- */
        $verify_url = home_url('/?crow_verify=' . urlencode($data['serial']));
        $qr_url = 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' . urlencode($verify_url);
        $data['qr_code_url'] = $qr_url;

        /* ---- IMAGE UPLOAD ---- */
        if (!empty($_FILES['certificate_image']['name'])) {
            $attachment_id = media_handle_upload('certificate_image', 0);
            if (!is_wp_error($attachment_id)) {
                $data['certificate_image'] = wp_get_attachment_url($attachment_id);
            }
        }

        /* ---- UPDATE ---- */
        if (!empty($_POST['cert_id'])) {
            $wpdb->update($table, $data, ['id' => intval($_POST['cert_id'])]);
            echo '<div class="updated"><p>تم تحديث الشهادة بنجاح</p></div>';
        }
        /* ---- INSERT ---- */
        else {
            $wpdb->insert($table, $data);
            echo '<div class="updated"><p>تم إضافة الشهادة بنجاح</p></div>';
        }
    }

    /* -----------------------------------------------------------
        EXPORT CSV
    ------------------------------------------------------------ */
    if (isset($_POST['crow_export_csv'])) {
        $rows = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=crow-certificates-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        if (!empty($rows)) {
            fputcsv($output, array_keys($rows[0]));
            foreach ($rows as $row) fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /* -----------------------------------------------------------
        IMPORT CSV
    ------------------------------------------------------------ */
    if (isset($_POST['crow_import_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);
            if (!empty($data['serial'])) {
                $wpdb->replace($table, $data);
            }
        }

        fclose($file);
        echo '<div class="updated"><p>تم استيراد البيانات بنجاح</p></div>';
    }

    /* -----------------------------------------------------------
        FETCH ALL CERTIFICATES
    ------------------------------------------------------------ */
    $certificates = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");

    ?>

    <div class="wrap">
        <h1>إدارة شهادات Crow Nation</h1>

        <!-- EXPORT / IMPORT -->
        <form method="post" style="margin-top:20px;">
            <button name="crow_export_csv" class="button">Export CSV</button>
        </form>

        <form method="post" enctype="multipart/form-data" style="margin-top:10px;">
