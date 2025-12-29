<?php

function crow_certificate_shortcode()
{
    ob_start();
    wp_enqueue_style('crow-style', plugin_dir_url(__FILE__) . '../assets/style.css');
    ?>

    <div class="crow-wrapper">
        <h2 style="color:#0099CC; margin-bottom:24px; font-size:24px; font-weight:700;">
            🔍 التحقق من الشهادة
        </h2>

        <form method="post" class="crow-form">
            <?php wp_nonce_field('crow_certificate_search', 'crow_search_nonce'); ?>
            <input type="text" name="crow_serial" placeholder="🎓 أدخل رقم الشهادة..." required autocomplete="off">
            <button type="submit" style="margin-top:8px;">
                🔎 بحث عن الشهادة
            </button>
        </form>

        <?php
        if (!empty($_POST['crow_serial'])) {
            // التحقق من الـ nonce
            if (!isset($_POST['crow_search_nonce']) || !wp_verify_nonce($_POST['crow_search_nonce'], 'crow_certificate_search')) {
                echo "<div class='crow-error'>❌ خطأ في الحماية. حاول مرة أخرى.</div>";
                return;
            }

            global $wpdb;
            $table = $wpdb->prefix . 'crow_certificates';
            $serial = sanitize_text_field($_POST['crow_serial']);

            $cert = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE serial=%s", $serial));

            if ($cert) {
                $status_class = 'status-' . esc_attr($cert->status);
                echo "<div class='crow-success'>
                        <div style='display:flex; align-items:center; gap:12px; margin-bottom:16px;'>
                            <h3 style='margin:0; font-size:22px;'>✅ الشهادة معتمدة</h3>
                            <span class='$status_class'>" . crow_get_status_badge_text($cert->status) . "</span>
                        </div>
                        
                        <div style='background:rgba(255,255,255,0.5); padding:16px; border-radius:6px; margin-bottom:16px;'>
                            <p><strong>👤 الاسم:</strong> " . esc_html($cert->name) . "</p>
                            <p><strong>🏆 العنوان:</strong> " . esc_html($cert->title) . "</p>
                            <p><strong>📝 السبب/البرنامج:</strong> " . esc_html($cert->reason) . "</p>
                            <p><strong>📅 تاريخ الإصدار:</strong> " . esc_html($cert->issue_date) . "</p>";

                if (!empty($cert->expiry_date)) {
                    echo "<p><strong>⏰ تاريخ الانتهاء:</strong> " . esc_html($cert->expiry_date) . "</p>";
                }

                echo "<p style='margin-bottom:0;'><strong>🔐 الحالة:</strong> <span class='$status_class'>" . crow_get_status_badge_text($cert->status) . "</span></p>
                        </div>";

                if (!empty($cert->certificate_image)) {
                    echo "<img src='" . esc_url($cert->certificate_image) . "' 
                                    style='max-width:100%; height:auto; margin-top:16px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.12);'>";
                }

                if (!empty($cert->qr_code_url)) {
                    echo "<div style='margin-top:24px; padding-top:16px; border-top:1px solid rgba(255,255,255,0.3); text-align:center;'>
                                    <p style='color:rgba(0,0,0,0.6); font-size:12px; margin-bottom:12px;'>رمز QR:</p>
                                    <img src='" . esc_url($cert->qr_code_url) . "' style='width:150px; height:150px;'>
                                  </div>";
                }

                echo "</div>";
            } else {
                echo "<div class='crow-error' style='font-size:16px; padding:28px;'>
                        <p style='margin:0;'>❌ عذراً، السيريال المدخل غير صحيح أو غير موجود</p>
                        <p style='margin:12px 0 0 0; font-size:13px; opacity:0.8;'>يرجى التحقق من رقم الشهادة وحاول مجدداً</p>
                      </div>";
            }
        }
        ?>
    </div>

    <?php
    return ob_get_clean();
}

/**
 * Helper function to get status badge text
 */
function crow_get_status_badge_text($status)
{
    $badges = [
        'active' => '✅ نشط',
        'expired' => '⏰ منتهي',
        'revoked' => '❌ ملغى'
    ];

    return $badges[$status] ?? 'غير معروف';
}