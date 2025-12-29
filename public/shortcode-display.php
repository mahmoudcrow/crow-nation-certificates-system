<?php

function crow_certificate_shortcode()
{
    ob_start();
    ?>

    <div class="crow-wrapper">
        <form method="post" class="crow-form">
            <input type="text" name="crow_serial" placeholder="ادخل رقم الشهادة" required>
            <button type="submit">بحث</button>
        </form>

        <?php
        if (!empty($_POST['crow_serial'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'crow_certificates';
            $serial = sanitize_text_field($_POST['crow_serial']);

            $cert = $wpdb->get_row("SELECT * FROM $table WHERE serial='$serial'");

            if ($cert) {
                echo "<div class='crow-success'>
                        <h3>✔ الشهادة معتمدة</h3>
                        <p><strong>الاسم:</strong> $cert->name</p>
                        <p><strong>العنوان:</strong> $cert->title</p>
                        <p><strong>السبب:</strong> $cert->reason</p>
                        <p><strong>تاريخ الإصدار:</strong> $cert->issue_date</p>
                      </div>";
            } else {
                echo "<div class='crow-error'>❌ السيريال غير موجود</div>";
            }
        }
        ?>
    </div>

    <?php
    return ob_get_clean();
}