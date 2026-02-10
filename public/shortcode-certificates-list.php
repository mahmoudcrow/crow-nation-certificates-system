<?php
/**
 * Shortcode لعرض قائمة الشهادات مع Pagination
 * [crow_certificates_list]
 * 
 * يعرض آخر 10 شهادات نشطة مع نظام ترقيم الصفحات
 */

function crow_certificates_list_shortcode()
{
    ob_start();
    wp_enqueue_style('crow-style', plugin_dir_url(__FILE__) . '../assets/style.css');

    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';

    // الحصول على رقم الصفحة من query parameter
    $paged = isset($_GET['crow_page']) ? intval($_GET['crow_page']) : 1;
    if ($paged < 1) {
        $paged = 1;
    }

    $per_page = 10; // آخر 10 شهادات
    $offset = ($paged - 1) * $per_page;

    // جلب الشهادات النشطة المرتبة من الأحدث
    $certs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE status = 'active' ORDER BY id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );

    // إجمالي عدد الشهادات
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'active'");
    $total_pages = ceil($total / $per_page);

    ?>
    <div class="crow-certificates-list-wrapper">
        <style>
            .crow-certificates-list-wrapper {
                background: #f8f9fa;
                padding: 30px 20px;
                border-radius: 8px;
                max-width: 1200px;
                margin: 0 auto;
            }

            .crow-certs-header {
                text-align: center;
                margin-bottom: 40px;
            }

            .crow-certs-header h2 {
                color: #0099CC;
                font-size: 28px;
                margin: 0 0 10px 0;
                font-weight: 700;
            }

            .crow-certs-header p {
                color: #666;
                font-size: 14px;
                margin: 0;
            }

            .crow-certs-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
                margin-bottom: 40px;
            }

            .crow-cert-card {
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
                cursor: pointer;
                border: 2px solid transparent;
            }

            .crow-cert-card:hover {
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
                border-color: #0099CC;
                transform: translateY(-4px);
            }

            .crow-cert-image {
                width: 100%;
                height: 200px;
                object-fit: cover;
                background: #f0f0f0;
            }

            .crow-cert-body {
                padding: 20px;
            }

            .crow-cert-name {
                font-size: 16px;
                font-weight: 700;
                color: #333;
                margin: 0 0 8px 0;
                display: -webkit-box;
                -webkit-line-clamp: 1;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .crow-cert-title {
                font-size: 13px;
                color: #666;
                margin: 0 0 10px 0;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .crow-cert-serial {
                font-size: 12px;
                color: #999;
                background: #f5f5f5;
                padding: 8px;
                border-radius: 4px;
                margin: 0 0 10px 0;
                word-break: break-all;
                font-family: monospace;
            }

            .crow-cert-date {
                font-size: 12px;
                color: #0099CC;
                font-weight: 600;
                margin: 0;
            }

            .crow-cert-status {
                display: inline-block;
                background: #d4edda;
                color: #155724;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 600;
                margin-top: 10px;
            }

            .crow-pagination-wrapper {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 8px;
                margin-top: 40px;
                flex-wrap: wrap;
            }

            .crow-pagination-wrapper a,
            .crow-pagination-wrapper span {
                display: inline-block;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-decoration: none;
                color: #0099CC;
                font-weight: 500;
                transition: all 0.2s;
            }

            .crow-pagination-wrapper a:hover {
                background: #0099CC;
                color: white;
                border-color: #0099CC;
            }

            .crow-pagination-wrapper .current {
                background: #0099CC;
                color: white;
                border-color: #0099CC;
                cursor: default;
            }

            .crow-pagination-info {
                text-align: center;
                color: #666;
                font-size: 13px;
                margin-bottom: 20px;
            }

            .crow-empty-state {
                text-align: center;
                padding: 60px 20px;
                color: #666;
            }

            .crow-empty-state-icon {
                font-size: 48px;
                margin-bottom: 20px;
            }

            .crow-empty-state h3 {
                color: #333;
                margin: 0 0 10px 0;
            }

            .crow-empty-state p {
                margin: 0;
                font-size: 14px;
            }

            @media (max-width: 768px) {
                .crow-certs-grid {
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    gap: 15px;
                }

                .crow-certs-header h2 {
                    font-size: 22px;
                }

                .crow-pagination-wrapper {
                    gap: 4px;
                }

                .crow-pagination-wrapper a,
                .crow-pagination-wrapper span {
                    padding: 6px 10px;
                    font-size: 12px;
                }
            }
        </style>

        <!-- Header -->
        <div class="crow-certs-header">
            <h2>🎓 <?php _e('الشهادات', 'crow-certificates'); ?></h2>
            <p><?php _e('آخر الشهادات الصادرة', 'crow-certificates'); ?></p>
        </div>

        <!-- Statistics -->
        <?php if ($total > 0): ?>
            <div class="crow-pagination-info">
                <?php
                $start = ($paged - 1) * $per_page + 1;
                $end = min($paged * $per_page, $total);
                printf(
                    __('عرض %d - %d من %d شهادة', 'crow-certificates'),
                    $start,
                    $end,
                    $total
                );
                ?>
            </div>
        <?php endif; ?>

        <!-- Certificates Grid -->
        <?php if (!empty($certs)): ?>
            <div class="crow-certs-grid">
                <?php foreach ($certs as $cert): ?>
                    <div class="crow-cert-card">
                        <!-- Certificate Image -->
                        <?php if (!empty($cert->certificate_image)): ?>
                            <img src="<?php echo esc_url($cert->certificate_image . '?v=' . time()); ?>"
                                alt="<?php echo esc_attr($cert->name); ?>" class="crow-cert-image" loading="lazy">
                        <?php else: ?>
                            <div class="crow-cert-image"
                                style="display:flex; align-items:center; justify-content:center; background:#f0f0f0; color:#ccc; font-size:48px;">
                                📜
                            </div>
                        <?php endif; ?>

                        <!-- Certificate Info -->
                        <div class="crow-cert-body">
                            <h3 class="crow-cert-name"><?php echo esc_html($cert->name); ?></h3>
                            <p class="crow-cert-title">
                                <strong><?php _e('البرنامج:', 'crow-certificates'); ?></strong>
                                <?php echo esc_html($cert->reason); ?>
                            </p>
                            <div class="crow-cert-serial">
                                <?php echo esc_html($cert->serial); ?>
                            </div>
                            <p class="crow-cert-date">
                                📅 <?php echo esc_html(date_i18n('d F Y', strtotime($cert->issue_date))); ?>
                            </p>
                            <span class="crow-cert-status">✅ <?php _e('نشطة', 'crow-certificates'); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="crow-pagination-wrapper">
                    <?php
                    // Previous Page Link
                    if ($paged > 1) {
                        $prev_url = add_query_arg('crow_page', $paged - 1);
                        echo '<a href="' . esc_url($prev_url) . '" class="crow-pagination-prev">← ' . __('السابق', 'crow-certificates') . '</a>';
                    }

                    // Page Numbers with ellipsis
                    for ($i = 1; $i <= $total_pages; $i++) {
                        // Show first page, current page range, and last page
                        if ($i == 1 || $i == $total_pages || ($i >= $paged - 1 && $i <= $paged + 1)) {
                            if ($i === $paged) {
                                echo '<span class="current">' . $i . '</span>';
                            } else {
                                $page_url = add_query_arg('crow_page', $i);
                                echo '<a href="' . esc_url($page_url) . '">' . $i . '</a>';
                            }
                        } elseif (($i == 2 && $paged > 3) || ($i == $total_pages - 1 && $paged < $total_pages - 2)) {
                            // Show ellipsis
                            echo '<span style="cursor:default; border:none; padding:0; color:#999;">...</span>';
                        }
                    }

                    // Next Page Link
                    if ($paged < $total_pages) {
                        $next_url = add_query_arg('crow_page', $paged + 1);
                        echo '<a href="' . esc_url($next_url) . '" class="crow-pagination-next">' . __('التالي', 'crow-certificates') . ' →</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="crow-empty-state">
                <div class="crow-empty-state-icon">📜</div>
                <h3><?php _e('لا توجد شهادات', 'crow-certificates'); ?></h3>
                <p><?php _e('لم يتم العثور على شهادات نشطة للعرض في الوقت الحالي', 'crow-certificates'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <?php
    return ob_get_clean();
}

/**
 * تسجيل الـ Shortcode
 */
function crow_register_certificates_list_shortcode()
{
    add_shortcode('crow_certificates_list', 'crow_certificates_list_shortcode');
}

add_action('init', 'crow_register_certificates_list_shortcode', 20);

?>