<?php
/**
 * GitHub Auto-Updater لـ Crow Nation Certificates System
 * 
 * يتحقق من وجود نسخ جديدة على GitHub تلقائياً
 * ويعلم المستخدم عند توفر تحديث جديد
 */

class Crow_GitHub_Updater
{
    private $plugin_file;
    private $plugin_slug;
    private $github_user;
    private $github_repo;
    private $github_api;
    private $cache_key = 'crow_github_update_check';
    private $cache_time = 12 * HOUR_IN_SECONDS; // فحص كل 12 ساعة

    public function __construct($plugin_file, $github_user, $github_repo)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->github_user = $github_user;
        $this->github_repo = $github_repo;
        $this->github_api = "https://api.github.com/repos/{$github_user}/{$github_repo}/releases/latest";

        // تسجيل الـ Hooks
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);

        // فحص يومي للتحديثات
        add_action('init', [$this, 'schedule_update_check']);
    }

    /**
     * جدولة الفحص اليومي
     */
    public function schedule_update_check()
    {
        if (!wp_next_scheduled('crow_check_github_updates')) {
            wp_schedule_event(time(), 'daily', 'crow_check_github_updates');
        }
        add_action('crow_check_github_updates', [$this, 'force_update_check']);
    }

    /**
     * فرض فحص التحديثات
     */
    public function force_update_check()
    {
        delete_transient($this->cache_key);
        wp_cache_delete('update_plugins');
    }

    /**
     * التحقق من التحديثات المتاحة
     */
    public function check_for_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        // تحقق من الكاش أولاً
        $cached_response = get_transient($this->cache_key);
        if ($cached_response !== false) {
            return $cached_response;
        }

        // احصل على أحدث نسخة من GitHub
        $remote_response = wp_remote_get(
            $this->github_api,
            [
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'WordPress/' . get_bloginfo('version')
                ]
            ]
        );

        if (is_wp_error($remote_response)) {
            // في حالة الخطأ، أرجع الـ transient كما هي
            return $transient;
        }

        $data = json_decode(wp_remote_retrieve_body($remote_response), true);

        if (!isset($data['tag_name'])) {
            set_transient($this->cache_key, $transient, $this->cache_time);
            return $transient;
        }

        $new_version = $this->normalize_version($data['tag_name']);
        $current_version = $this->get_current_version();

        // إذا كانت نسخة جديدة متاحة
        if (version_compare($current_version, $new_version, '<')) {
            $transient->response[$this->plugin_slug] = (object) [
                'slug' => $this->plugin_slug,
                'new_version' => $new_version,
                'url' => $data['html_url'],
                'package' => $data['zipball_url'],
                'tested' => get_bloginfo('version'),
                'requires_php' => '7.4',
                'requires_wp' => '5.0'
            ];
        }

        // احفظ النتيجة في الكاش
        set_transient($this->cache_key, $transient, $this->cache_time);

        return $transient;
    }

    /**
     * معلومات الـ Plugin للصفحة التفصيلية
     */
    public function plugin_info($res, $action, $args)
    {
        if ($action !== 'plugin_information' || $args->slug !== $this->plugin_slug) {
            return $res;
        }

        $response = wp_remote_get(
            $this->github_api,
            [
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'WordPress/' . get_bloginfo('version')
                ]
            ]
        );

        if (is_wp_error($response)) {
            return $res;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        $res = (object) [
            'name' => 'Crow Nation Certificates System',
            'slug' => $this->plugin_slug,
            'version' => $this->normalize_version($data['tag_name'] ?? '1.0'),
            'author' => 'Mahmoud Moustafa',
            'author_profile' => 'https://github.com/' . $this->github_user,
            'homepage' => $data['html_url'] ?? '',
            'download_link' => $data['zipball_url'] ?? '',
            'requires' => '5.0',
            'requires_php' => '7.4',
            'tested' => get_bloginfo('version'),
            'rating' => 100,
            'num_ratings' => 1,
            'sections' => [
                'description' => $data['body'] ?? 'نظام إدارة شهادات مع تحديثات تلقائية من GitHub',
                'changelog' => $data['body'] ?? 'تم تحديث الإضافة'
            ]
        ];

        return $res;
    }

    /**
     * تطبيع رقم الإصدار (إزالة v من البداية)
     */
    private function normalize_version($version)
    {
        return ltrim($version, 'vV');
    }

    /**
     * الحصول على النسخة الحالية من الإضافة
     */
    private function get_current_version()
    {
        $plugin_data = get_plugin_data($this->plugin_file);
        return $plugin_data['Version'] ?? '1.0';
    }
}
?>