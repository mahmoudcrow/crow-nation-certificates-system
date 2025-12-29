<?php

class Crow_GitHub_Updater
{

    private $plugin_file;
    private $plugin_slug;
    private $github_user;
    private $github_repo;
    private $github_api;

    public function __construct($plugin_file, $github_user, $github_repo)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->github_user = $github_user;
        $this->github_repo = $github_repo;
        $this->github_api = "https://api.github.com/repos/{$github_user}/{$github_repo}/releases/latest";

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
    }

    public function check_for_update($transient)
    {
        if (empty($transient->checked))
            return $transient;

        $response = wp_remote_get($this->github_api);

        if (is_wp_error($response))
            return $transient;

        $data = json_decode(wp_remote_retrieve_body($response));

        if (!isset($data->tag_name))
            return $transient;

        $new_version = $data->tag_name;
        $current_version = get_plugin_data($this->plugin_file)['Version'];

        if (version_compare($current_version, $new_version, '<')) {
            $transient->response[$this->plugin_slug] = (object) [
                'slug' => $this->plugin_slug,
                'new_version' => $new_version,
                'url' => $data->html_url,
                'package' => $data->zipball_url
            ];
        }

        return $transient;
    }

    public function plugin_info($res, $action, $args)
    {
        if ($action !== 'plugin_information' || $args->slug !== $this->plugin_slug)
            return $res;

        $response = wp_remote_get($this->github_api);
        if (is_wp_error($response))
            return $res;

        $data = json_decode(wp_remote_retrieve_body($response));

        $res = (object) [
            'name' => 'Crow Nation Certificates System',
            'slug' => $this->plugin_slug,
            'version' => $data->tag_name,
            'author' => 'Mahmoud Moustafa',
            'homepage' => $data->html_url,
            'download_link' => $data->zipball_url,
            'sections' => [
                'description' => 'Certificate verification system with GitHub auto-updates.'
            ]
        ];

        return $res;
    }
}