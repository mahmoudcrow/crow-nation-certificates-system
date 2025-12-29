<?php

function crow_analytics_page_html()
{
    if (!current_user_can('manage_options'))
        return;

    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';

    // Fetch counts
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $active = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='active'");
    $expired = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='expired'");
    $revoked = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='revoked'");

    ?>

    <div class="wrap">
        <h1>📊 تحليلات شهادات Crow Nation</h1>

        <div style="display:flex; gap:20px; margin-top:30px;">

            <div style="flex:1; background:#fff; padding:25px; border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.08);">
                <h2 style="margin:0;">إجمالي الشهادات</h2>
                <p style="font-size:40px; font-weight:bold; margin-top:10px;"><?= intval($total) ?></p>
            </div>

            <div
                style="flex:1; background:#e8fff3; padding:25px; border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.08);">
                <h2 style="margin:0;">Active</h2>
                <p style="font-size:40px; font-weight:bold; margin-top:10px;"><?= intval($active) ?></p>
            </div>

            <div
                style="flex:1; background:#fff7e6; padding:25px; border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.08);">
                <h2 style="margin:0;">Expired</h2>
                <p style="font-size:40px; font-weight:bold; margin-top:10px;"><?= intval($expired) ?></p>
            </div>

            <div
                style="flex:1; background:#ffe8e8; padding:25px; border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.08);">
                <h2 style="margin:0;">Revoked</h2>
                <p style="font-size:40px; font-weight:bold; margin-top:10px;"><?= intval($revoked) ?></p>
            </div>

        </div>

        <hr style="margin:40px 0;">

        <h2>📈 توزيع الحالات (%)</h2>

        <?php
        $percent_active = $total ? round(($active / $total) * 100) : 0;
        $percent_expired = $total ? round(($expired / $total) * 100) : 0;
        $percent_revoked = $total ? round(($revoked / $total) * 100) : 0;
        ?>

        <div style="margin-top:20px;">
            <p>Active: <?= $percent_active ?>%</p>
            <div style="height:12px; background:#e8fff3; width:<?= $percent_active ?>%; border-radius:6px;"></div>

            <p style="margin-top:20px;">Expired: <?= $percent_expired ?>%</p>
            <div style="height:12px; background:#fff7e6; width:<?= $percent_expired ?>%; border-radius:6px;"></div>

            <p style="margin-top:20px;">Revoked: <?= $percent_revoked ?>%</p>
            <div style="height:12px; background:#ffe8e8; width:<?= $percent_revoked ?>%; border-radius:6px;"></div>
        </div>

    </div>

    <?php
}