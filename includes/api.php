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
    // Verify nonce - try both with and without parameter name for compatibility
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';

    if (!$nonce || !wp_verify_nonce($nonce, 'crow_check_updates')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    // Get the global updater instance or create a temporary one
    global $crow_updater_instance;

    if (!isset($crow_updater_instance)) {

        // Create temporary updater instance for checking
        // Use the main plugin file path, not this file
        $plugin_file = dirname(dirname(__FILE__)) . '/crow-nation-certificates.php';

        require_once dirname(__FILE__) . '/github-updater.php';
        $crow_updater_instance = new Crow_GitHub_Updater(
            $plugin_file,
            'mahmoudcrow',
            'crow-nation-certificates-system'
        );
    }
    $info = $crow_updater_instance->get_update_info();

    // Debug logging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Crow Update Info: ' . print_r($info, true));
    }

    if (!empty($info['error'])) {
        wp_send_json_error(['message' => $info['message'] ?? 'Unknown error occurred']);
    } else {
        wp_send_json_success($info);
    }
}

// AJAX endpoint for clearing cache
add_action('wp_ajax_crow_clear_update_cache', 'crow_ajax_clear_update_cache');
function crow_ajax_clear_update_cache()
{
    // Verify nonce
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';

    if (!$nonce || !wp_verify_nonce($nonce, 'crow_clear_cache')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    delete_transient('crow_github_update_check');
    delete_option('crow_last_update_check');
    delete_option('crow_last_update_error');
    wp_cache_delete('update_plugins');

    wp_send_json_success(['message' => 'Cache cleared']);
}

// AJAX endpoint for testing GitHub API connection
add_action('wp_ajax_crow_test_github', 'crow_ajax_test_github');
function crow_ajax_test_github()
{
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';

    if (!$nonce || !wp_verify_nonce($nonce, 'crow_check_updates')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $api_url = 'https://api.github.com/repos/mahmoudcrow/crow-nation-certificates-system/releases/latest';

    $response = wp_remote_get($api_url, [
        'timeout' => 10,
        'sslverify' => apply_filters('https_local_ssl_verify', false),
        'headers' => [
            'User-Agent' => 'WordPress'
        ]
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
    }

    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($status !== 200) {
        wp_send_json_error(['message' => 'GitHub API returned status ' . $status]);
    }

    $data = json_decode($body, true);

    wp_send_json_success([
        'status' => $status,
        'has_data' => !empty($data),
        'tag' => $data['tag_name'] ?? 'N/A',
        'api_url' => $api_url
    ]);
}
