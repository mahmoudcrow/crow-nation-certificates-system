<?php
/**
 * قائمة إدارة الشهادات - صفحة مستقلة في قائمة الإدارة
 */

function crow_certificates_list_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('ليس لديك صلاحيات كافية', 'crow-certificates'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'crow_certificates';

    /* ================================================================
        HANDLE DELETE ACTION FROM LIST
    ================================================================ */
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $nonce = $_GET['_wpnonce'] ?? '';

        if (wp_verify_nonce($nonce, 'crow_delete_' . $id)) {
            // حذف الصورة المرتبطة
            $cert = $wpdb->get_row($wpdb->prepare("SELECT certificate_image FROM $table WHERE id=%d", $id));
            if ($cert && !empty($cert->certificate_image)) {
                $attachment_id = attachment_url_to_postid($cert->certificate_image);
                if ($attachment_id) {
                    wp_delete_attachment($attachment_id, true);
                }
            }

            // حذف السجل
            $wpdb->delete($table, ['id' => $id], ['%d']);
            echo '<div class="notice notice-success is-dismissible"><p>✅ ' . __('تم حذف الشهادة بنجاح', 'crow-certificates') . '</p></div>';
        }
    }

    /* ================================================================
        LOAD CERTIFICATES
    ================================================================ */
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;

    $where = '';
    $params = [];

    if (!empty($search)) {
        $where = 'WHERE name LIKE %s OR serial LIKE %s OR title LIKE %s';
        $like = '%' . $wpdb->esc_like($search) . '%';
        $params = [$like, $like, $like];
    }

    $query = "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $params[] = $per_page;
    $params[] = $offset;

    $certs = $wpdb->get_results($wpdb->prepare($query, $params));

    $total_query = "SELECT COUNT(*) FROM $table $where";
    $total = $wpdb->get_var($where ? $wpdb->prepare($total_query, array_slice($params, 0, count($params) - 2)) : $total_query);
    $total_pages = ceil($total / $per_page);
    ?>

    <div class="wrap">
        <h1>
            🎓 <?php _e('إدارة الشهادات', 'crow-certificates'); ?>
            <a href="<?php echo admin_url('admin.php?page=crow-certificates'); ?>" class="page-title-action">
                ➕ <?php _e('إضافة شهادة جديدة', 'crow-certificates'); ?>
            </a>
        </h1>

        <style>
            .crow-list-wrapper {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            .crow-search-box {
                margin-bottom: 20px;
                display: flex;
                gap: 10px;
            }

            .crow-search-box input {
                flex: 1;
                padding: 10px 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }

            .crow-search-box button {
                padding: 10px 20px;
                background: #0099CC;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 500;
            }

            .crow-search-box button:hover {
                background: #0077AA;
            }

            .crow-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            .crow-table thead {
                background: #f5f5f5;
                border-bottom: 2px solid #ddd;
            }

            .crow-table th {
                padding: 12px;
                text-align: right;
                font-weight: 600;
                color: #333;
            }

            .crow-table td {
                padding: 12px;
                border-bottom: 1px solid #eee;
                color: #666;
            }

            .crow-table tr:hover {
                background: #f9f9f9;
            }

            .crow-table img {
                max-width: 50px;
                height: auto;
                border-radius: 4px;
            }

            .status-badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }

            .status-active {
                background: #d4edda;
                color: #155724;
            }

            .status-expired {
                background: #fff3cd;
                color: #856404;
            }

            .status-revoked {
                background: #f8d7da;
                color: #721c24;
            }

            .action-buttons {
                display: flex;
                gap: 8px;
            }

            .action-buttons a {
                padding: 6px 12px;
                border-radius: 4px;
                text-decoration: none;
                font-size: 12px;
                border: 1px solid #ddd;
                transition: all 0.3s;
            }

            .action-buttons .edit-btn {
                color: #0099CC;
                border-color: #0099CC;
            }

            .action-buttons .edit-btn:hover {
                background: #0099CC;
                color: white;
            }

            .action-buttons .delete-btn {
                color: #dc3545;
                border-color: #dc3545;
            }

            .action-buttons .delete-btn:hover {
                background: #dc3545;
                color: white;
            }

            .pagination {
                margin-top: 20px;
                text-align: center;
            }

            .pagination a,
            .pagination span {
                display: inline-block;
                padding: 8px 12px;
                margin: 0 2px;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-decoration: none;
                color: #0099CC;
            }

            .pagination .current {
                background: #0099CC;
                color: white;
                border-color: #0099CC;
            }

            .empty-state {
                text-align: center;
                padding: 40px 20px;
                color: #666;
            }

            .empty-state-icon {
                font-size: 48px;
                margin-bottom: 20px;
            }
        </style>

        <div class="crow-list-wrapper">
            <!-- Search Bar -->
            <div class="crow-search-box">
                <form method="get" style="display: flex; gap: 10px; flex: 1;">
                    <input type="hidden" name="page" value="crow-certificates-list">
                    <input type="text" name="s" placeholder="🔍 البحث بالاسم أو السيريال أو العنوان..."
                        value="<?php echo esc_attr($search); ?>">
                    <button type="submit"><?php _e('بحث', 'crow-certificates'); ?></button>
                    <?php if (!empty($search)): ?>
                        <a href="<?php echo admin_url('admin.php?page=crow-certificates-list'); ?>" class="crow-search-box"
                            style="padding:10px 20px; background:#f0f0f0; color:#333; border-radius:4px; text-decoration:none;">❌
                            <?php _e('مسح', 'crow-certificates'); ?></a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Statistics -->
            <div
                style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:20px;">
                <div
                    style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding:20px; border-radius:8px; text-align:center;">
                    <div style="font-size:32px; font-weight:bold;"><?php echo count($certs); ?></div>
                    <div style="font-size:14px; opacity:0.9;"><?php _e('شهادات في هذه الصفحة', 'crow-certificates'); ?>
                    </div>
                </div>
                <div
                    style="background:linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color:white; padding:20px; border-radius:8px; text-align:center;">
                    <div style="font-size:32px; font-weight:bold;"><?php echo $total; ?></div>
                    <div style="font-size:14px; opacity:0.9;"><?php _e('إجمالي الشهادات', 'crow-certificates'); ?></div>
                </div>
            </div>

            <?php if (empty($certs)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📜</div>
                    <p><?php _e('لا توجد شهادات للعرض', 'crow-certificates'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=crow-admin'); ?>" class="button button-primary"
                        style="margin-top:15px;">
                        ➕ <?php _e('إضافة شهادة جديدة', 'crow-certificates'); ?>
                    </a>
                </div>
            <?php else: ?>
                <table class="crow-table">
                    <thead>
                        <tr>
                            <th><?php _e('الصورة', 'crow-certificates'); ?></th>
                            <th><?php _e('الاسم', 'crow-certificates'); ?></th>
                            <th><?php _e('السيريال', 'crow-certificates'); ?></th>
                            <th><?php _e('العنوان', 'crow-certificates'); ?></th>
                            <th><?php _e('الحالة', 'crow-certificates'); ?></th>
                            <th><?php _e('تاريخ الإنشاء', 'crow-certificates'); ?></th>
                            <th><?php _e('الإجراءات', 'crow-certificates'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certs as $cert): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($cert->certificate_image)): ?>
                                        <img src="<?php echo esc_url($cert->certificate_image); ?>"
                                            alt="<?php echo esc_attr($cert->name); ?>">
                                    <?php else: ?>
                                        <div
                                            style="width:50px; height:50px; background:#f0f0f0; border-radius:4px; display:flex; align-items:center; justify-content:center; color:#ccc;">
                                            📄</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($cert->name); ?></strong>
                                    <br>
                                    <small style="color:#999;"><?php echo esc_html($cert->email); ?></small>
                                </td>
                                <td>
                                    <code style="background:#f5f5f5; padding:4px 8px; border-radius:4px; color:#d63384;">
                                                                            <?php echo esc_html($cert->serial); ?>
                                                                        </code>
                                </td>
                                <td><?php echo esc_html(substr($cert->title, 0, 30)); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($cert->status); ?>">
                                        <?php
                                        $status_text = [
                                            'active' => '✅ نشط',
                                            'expired' => '⏰ منتهي',
                                            'revoked' => '❌ ملغى'
                                        ];
                                        echo $status_text[$cert->status] ?? 'غير معروف';
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($cert->created_at))); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo admin_url('admin.php?page=crow-admin&action=edit&id=' . $cert->id); ?>"
                                            class="edit-btn">
                                            ✏️ <?php _e('تعديل', 'crow-certificates'); ?>
                                        </a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=crow-certificates-list&action=delete&id=' . $cert->id), 'crow_delete_' . $cert->id); ?>"
                                            class="delete-btn"
                                            onclick="return confirm('<?php _e('هل أنت متأكد من الحذف؟', 'crow-certificates'); ?>');">
                                            🗑️ <?php _e('حذف', 'crow-certificates'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $url = admin_url('admin.php?page=crow-certificates-list&paged=' . $i);
                            if (!empty($search)) {
                                $url .= '&s=' . urlencode($search);
                            }

                            if ($i === $paged) {
                                echo '<span class="current">' . $i . '</span>';
                            } else {
                                echo '<a href="' . esc_url($url) . '">' . $i . '</a>';
                            }
                        }
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php
}
