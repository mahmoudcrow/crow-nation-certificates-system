<?php

// Create global updater instance for use in API
global $crow_updater_instance;

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

// AJAX endpoint for checking updates
add_action('wp_ajax_crow_check_updates', 'crow_ajax_check_updates');
function crow_ajax_check_updates()
{
    check_ajax_referer('crow_check_updates', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    // Get the global updater instance or create a temporary one
    global $crow_updater_instance;

    if (!isset($crow_updater_instance)) {
        // Create temporary updater instance for checking
        require_once plugin_dir_path(__FILE__) . 'github-updater.php';
        $crow_updater_instance = new Crow_GitHub_Updater(
            __FILE__,
            'mahmoudcrow',
            'crow-nation-certificates-system'
        );
    }

    $info = $crow_updater_instance->get_update_info();

    if ($info['error']) {
        wp_send_json_error($info);
    } else {
        wp_send_json_success($info);
    }
}

// AJAX endpoint for clearing cache
add_action('wp_ajax_crow_clear_update_cache', 'crow_ajax_clear_update_cache');
function crow_ajax_clear_update_cache()
{
    check_ajax_referer('crow_clear_cache', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    delete_transient('crow_github_update_check');
    delete_option('crow_last_update_check');
    delete_option('crow_last_update_error');
    wp_cache_delete('update_plugins');

    wp_send_json_success(['message' => 'Cache cleared']);
}
