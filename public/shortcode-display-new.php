<?php
/**
 * Shortcode محسّن لعرض مربع البحث والنتائج
 * [crow_certificate_checker]
 */

function crow_certificate_shortcode()
{
    ob_start();
    wp_enqueue_style('crow-style', plugin_dir_url(__FILE__) . '../assets/style.css');

    // تحديد الوضع: البحث أو الإضافة
    $search_performed = !empty($_POST['crow_serial']) && wp_verify_nonce($_POST['crow_search_nonce'] ?? '', 'crow_certificate_search');
    $result = null;

    if ($search_performed) {
        global $wpdb;
        $table = $wpdb->prefix . 'crow_certificates';
        $serial = sanitize_text_field($_POST['crow_serial']);
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE serial=%s", $serial));
    }

    ?>
    <div class="crow-wrapper">
        <!-- Logo/Header -->
        <div class="crow-header">
            <div class="crow-header-content">
                <h1>🎓 <?php _e('Certificate Verification', 'crow-certificates'); ?></h1>
                <p><?php _e('Verify your certificate by entering the serial number', 'crow-certificates'); ?></p>
            </div>
        </div>

        <!-- Search Form -->
        <div class="crow-search-container">
            <form method="post" class="crow-search-form">
                <?php wp_nonce_field('crow_certificate_search', 'crow_search_nonce'); ?>

                <div class="crow-search-input-group">
                    <input type="text" name="crow_serial" class="crow-search-input"
                        placeholder="<?php esc_attr_e('Enter certificate serial number...', 'crow-certificates'); ?>"
                        required autocomplete="off" autofocus>
                    <button type="submit" class="crow-search-button">
                        <span class="icon">🔎</span>
                        <span class="text"><?php _e('Verify', 'crow-certificates'); ?></span>
                    </button>
                </div>

                <?php if ($search_performed && empty($result)): ?>
                    <div class="crow-search-hint">
                        <?php _e('No results found. Please check the serial number and try again.', 'crow-certificates'); ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Search Results -->
        <?php if ($search_performed && $result): ?>
            <div class="crow-results-container">
                <!-- Success Message -->
                <div class="crow-success-banner">
                    <div class="banner-icon">✅</div>
                    <div class="banner-text">
                        <h2><?php _e('Certificate Verified', 'crow-certificates'); ?></h2>
                        <p><?php _e('This certificate is valid and authentic', 'crow-certificates'); ?></p>
                    </div>
                </div>

                <!-- Certificate Details Card -->
                <div class="crow-certificate-card">
                    <!-- Left Side: Certificate Details -->
                    <div class="crow-details-section">
                        <h3><?php _e('Certificate Details', 'crow-certificates'); ?></h3>

                        <!-- Detail Items -->
                        <div class="crow-detail-item">
                            <div class="detail-label"><?php _e('Holder Name', 'crow-certificates'); ?></div>
                            <div class="detail-value"><?php echo esc_html($result->name); ?></div>
                        </div>

                        <div class="crow-detail-item">
                            <div class="detail-label"><?php _e('Certificate Title', 'crow-certificates'); ?></div>
                            <div class="detail-value"><?php echo esc_html($result->title); ?></div>
                        </div>

                        <div class="crow-detail-item">
                            <div class="detail-label"><?php _e('Program/Course', 'crow-certificates'); ?></div>
                            <div class="detail-value"><?php echo esc_html($result->reason); ?></div>
                        </div>

                        <div class="crow-detail-item">
                            <div class="detail-label"><?php _e('Serial Number', 'crow-certificates'); ?></div>
                            <div class="detail-value detail-serial">
                                <code><?php echo esc_html($result->serial); ?></code>
                            </div>
                        </div>

                        <div class="crow-detail-item">
                            <div class="detail-label"><?php _e('Issue Date', 'crow-certificates'); ?></div>
                            <div class="detail-value">
                                <?php echo esc_html(date_i18n('d F Y', strtotime($result->issue_date))); ?></div>
                        </div>

                        <?php if (!empty($result->expiry_date)): ?>
                            <div class="crow-detail-item">
                                <div class="detail-label"><?php _e('Expiry Date', 'crow-certificates'); ?></div>
                                <div class="detail-value">
                                    <?php echo esc_html(date_i18n('d F Y', strtotime($result->expiry_date))); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="crow-detail-item">
                            <div class="detail-label"><?php _e('Status', 'crow-certificates'); ?></div>
                            <div class="detail-value">
                                <span class="status-badge status-<?php echo esc_attr($result->status); ?>">
                                    <?php
                                    $status_text = [
                                        'active' => '✅ ' . __('Active', 'crow-certificates'),
                                        'expired' => '⏰ ' . __('Expired', 'crow-certificates'),
                                        'revoked' => '❌ ' . __('Revoked', 'crow-certificates')
                                    ];
                                    echo $status_text[$result->status] ?? __('Unknown', 'crow-certificates');
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Images -->
                    <div class="crow-images-section">
                        <?php if (!empty($result->certificate_image)): ?>
                            <div class="crow-image-container">
                                <h3><?php _e('Certificate Image', 'crow-certificates'); ?></h3>
                                <img src="<?php echo esc_url($result->certificate_image); ?>"
                                    alt="<?php echo esc_attr($result->name); ?>" class="crow-certificate-image" loading="lazy">
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($result->qr_code_url)): ?>
                            <div class="crow-qr-container">
                                <h3><?php _e('QR Code', 'crow-certificates'); ?></h3>
                                <img src="<?php echo esc_url($result->qr_code_url); ?>" alt="QR Code" class="crow-qr-code"
                                    loading="lazy">
                                <p class="qr-hint"><?php _e('Scan to verify online', 'crow-certificates'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Search Again Button -->
                <div class="crow-actions">
                    <button class="crow-search-again-btn" onclick="location.reload()">
                        🔄 <?php _e('Search Another Certificate', 'crow-certificates'); ?>
                    </button>
                </div>
            </div>
        <?php elseif ($search_performed && empty($result)): ?>
            <!-- Error Message -->
            <div class="crow-error-container">
                <div class="error-icon">❌</div>
                <h3><?php _e('Certificate Not Found', 'crow-certificates'); ?></h3>
                <p><?php _e('The serial number you entered could not be found in our system.', 'crow-certificates'); ?></p>
                <p class="error-hint"><?php _e('Please double-check the serial number and try again.', 'crow-certificates'); ?>
                </p>

                <button class="crow-search-again-btn" onclick="location.reload()">
                    🔄 <?php _e('Try Again', 'crow-certificates'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php
    return ob_get_clean();
}

// التسجيل المحسّن
function crow_register_certificate_shortcode()
{
    add_shortcode('crow_certificate_checker', 'crow_certificate_shortcode');
}

add_action('init', 'crow_register_certificate_shortcode', 20);
