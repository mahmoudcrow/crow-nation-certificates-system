<?php

/**
 * Get certificate by serial number
 */
function crow_get_certificate_by_serial($serial)
{
    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE serial=%s", $serial));
}

/**
 * Get certificate by ID
 */
function crow_get_certificate_by_id($id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
}

/**
 * Check if certificate is valid
 */
function crow_is_certificate_valid($cert)
{
    if (!$cert) {
        return false;
    }

    if ($cert->status !== 'active') {
        return false;
    }

    if (!empty($cert->expiry_date)) {
        $expiry = strtotime($cert->expiry_date);
        if ($expiry < time()) {
            return false;
        }
    }

    return true;
}

/**
 * Generate QR code URL
 */
function crow_generate_qr_code_url($serial)
{
    $verify_url = home_url('/?crow_verify=' . urlencode($serial));
    return 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' . urlencode($verify_url);
}

/**
 * Format certificate data for display
 */
function crow_format_certificate($cert)
{
    if (!$cert) {
        return null;
    }

    return [
        'id' => intval($cert->id),
        'serial' => esc_html($cert->serial),
        'name' => esc_html($cert->name),
        'title' => esc_html($cert->title),
        'reason' => esc_html($cert->reason),
        'issue_date' => esc_html($cert->issue_date),
        'expiry_date' => esc_html($cert->expiry_date),
        'status' => esc_html($cert->status),
        'certificate_image' => esc_url($cert->certificate_image),
        'qr_code_url' => esc_url($cert->qr_code_url),
        'is_valid' => crow_is_certificate_valid($cert),
        'status_badge' => crow_get_status_badge($cert->status)
    ];
}

/**
 * Get status badge HTML
 */
function crow_get_status_badge($status)
{
    $badges = [
        'active' => '<span style="background:#e8fff3; color:#0abb87; padding:5px 10px; border-radius:5px; font-size:12px;">✅ نشط</span>',
        'expired' => '<span style="background:#fff7e6; color:#ff9800; padding:5px 10px; border-radius:5px; font-size:12px;">⏰ منتهي</span>',
        'revoked' => '<span style="background:#ffe8e8; color:#ff4d4d; padding:5px 10px; border-radius:5px; font-size:12px;">❌ ملغى</span>'
    ];

    return $badges[$status] ?? '<span style="padding:5px 10px;">غير معروف</span>';
}

/**
 * Get all certificates with pagination
 */
function crow_get_certificates($page = 1, $per_page = 20)
{
    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';

    $offset = ($page - 1) * $per_page;

    $certs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table ORDER BY id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );

    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");

    return [
        'certificates' => $certs,
        'total' => intval($total),
        'pages' => ceil($total / $per_page),
        'current_page' => intval($page)
    ];
}

/**
 * Search certificates
 */
function crow_search_certificates($search_term)
{
    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';
    $search_term = '%' . $wpdb->esc_like($search_term) . '%';

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE serial LIKE %s OR name LIKE %s OR title LIKE %s ORDER BY id DESC",
            $search_term,
            $search_term,
            $search_term
        )
    );
}

/**
 * Get certificate statistics
 */
function crow_get_certificate_stats()
{
    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';

    return [
        'total' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table")),
        'active' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='active'")),
        'expired' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='expired'")),
        'revoked' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='revoked'"))
    ];
}

/**
 * Export certificates to CSV
 */
function crow_export_certificates_csv()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('غير مصرح'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';
    $rows = $wpdb->get_results("SELECT id, serial, name, title, reason, issue_date, expiry_date, status, certificate_image, qr_code_url FROM $table", ARRAY_A);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=crow-certificates-' . date('Y-m-d-H-i-s') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Serial', 'Name', 'Title', 'Reason', 'Issue Date', 'Expiry Date', 'Status', 'Image URL', 'QR Code URL']);

    foreach ($rows as $row) {
        // Ensure proper escaping for CSV
        $row['id'] = intval($row['id']);
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Get certificate image URL with cache-busting parameter
 * Prevents browser caching issues when image is updated
 */
function crow_get_certificate_image_url($image_url)
{
    if (empty($image_url)) {
        return '';
    }

    $attachment_id = attachment_url_to_postid($image_url);
    if ($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            $cache_buster = filemtime($file_path);
            return $image_url . '?v=' . $cache_buster;
        }
    }

    // Fallback: use current time as cache buster
    return $image_url . '?v=' . time();
}
?>