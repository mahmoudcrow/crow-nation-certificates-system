<?php

add_action('rest_api_init', function () {
    register_rest_route('crow-certificates/v1', '/verify', [
        'methods' => 'GET',
        'callback' => 'crow_api_verify_certificate',
        'args' => [
            'serial' => [
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ],
        ],
        'permission_callback' => '__return_true' // لو عايز تخليها public
    ]);
});

function crow_api_verify_certificate($request)
{
    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';
    $serial = $request['serial'];

    $cert = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE serial = %s", $serial));

    if (!$cert) {
        return new WP_REST_Response([
            'found' => false,
            'message' => 'Certificate not found'
        ], 404);
    }

    return new WP_REST_Response([
        'found' => true,
        'serial' => $cert->serial,
        'name' => $cert->name,
        'title' => $cert->title,
        'reason' => $cert->reason,
        'issue_date' => $cert->issue_date,
        'expiry_date' => $cert->expiry_date,
        'status' => $cert->status,
        'certificate_image' => $cert->certificate_image,
        'qr_code_url' => $cert->qr_code_url
    ], 200);
}