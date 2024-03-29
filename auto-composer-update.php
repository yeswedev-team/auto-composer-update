<?php

use GuzzleHttp\Client;

/**
 * Plugin Name: Auto Composer Update
 * Description: A plugin that automatically updates the composer when a WordPress plugin is updated.
 * Version: 4.5.1
 * Author: Yes We Dev
 * Author URI: https://yeswedev.bzh/
 */

function get_composer_json(string $path): mixed
{
    $composer_json_path = $path . '/composer.json';

    $composer_json = file_get_contents($composer_json_path);

    if (!$composer_json) {
        error_log('Error : Can\'t read composer.json.');

        return false;
    }

    return json_decode($composer_json, true) ?: false;
}

function update_composer(array $body): bool
{
    $client = new Client();

    try {
        $response = $client->post(
            getenv('API_UPDATE_WORDPRESS'),
            ['form_params' => $body]
        );

        if ($response->getStatusCode() === 500) {
            error_log('Error : The WordPress and plugins update on the remote repository (' . getenv('GIT_BRANCH') . ') has failed .');
            error_log('Error: ' . $response->getBody());

            return false;
        }

        error_log('Info : The WordPress and plugins update on the remote repository (' . getenv('GIT_BRANCH') . ') was successful.');

        return true;
    } catch (\GuzzleHttp\Exception\GuzzleException $exception) {
        error_log('Error : The WordPress and plugins update on the remote repository (' . getenv('GIT_BRANCH') . ') has failed .');
        error_log('Error : ' . print_r($exception, true));

        return false;
    }
}

function on_upgrader_process_complete(array $update_results): void
{
    if (empty($update_results)) {
        return;
    }

    $body = [
        'git' => getenv('GIT_REPOSITORY'),
        'branch' => getenv('GIT_BRANCH'),
        'wordpressVersion' => get_bloginfo('version'),
        'updateRepository' => getenv('UPDATE_REPOSITORY'),
        'plugins' => []
    ];

    /**
     * @var string $key
     * @var array $value
     */
    foreach ($update_results as $key => $value) {
        switch ($key) {
            case 'core':
                if (empty($value)) {
                    error_log('Error : No WordPress update has been found.');

                    break;
                }

                /** @var string $version */
                $version = $value[0]->item->version;
                $body['wordpressVersion'] = $version;

                error_log('Info : A new version of WordPress has been installed locally (' . $version . ').');
                break;
            case 'plugin':
                $composer_json = get_composer_json(getenv('WP_CURRENT_PATH'));

                foreach ($value as $plugin) {
                    foreach ($composer_json['require'] as $name => $version) {
                        $parts = explode('/', $name);
                        $packageSlug = end($parts);
                        if ($packageSlug === $plugin->item->slug) {
                            $body['plugins'][] = [
                                'name' => $name,
                                'version' => $plugin->item->new_version
                            ];

                            error_log('Info : A new update of ' . $name . ' has been installed locally (' . $plugin->item->new_version . ').');
                        }
                    }
                }
                break;
        }
    }

    $update_result = update_composer($body);

    if (!$update_result) {
        error_log('Error while updating composer.json');
    }

    $plugins_to_activate = [
        'advanced-custom-fields-pro/acf.php',
        'gravityforms/gravityforms.php'
    ];

    foreach ($plugins_to_activate as $plugin) {
        if (!is_plugin_active($plugin)) {
            $result = activate_plugin($plugin);

            if (is_wp_error($result)) {
                error_log('Error: Error while activating ' . $plugin . ' : ' . $result->get_error_message());
            } else {
                error_log('Info : The plugin ' . $plugin . ' has been reactivated.');
            }
        }
    }
}

add_filter('automatic_updates_is_vcs_checkout', '__return_false', 1);
add_filter('auto_update_plugin', '__return_true', 1);
add_filter('auto_update_core', '__return_true', 1);
add_action('automatic_updates_complete', 'on_upgrader_process_complete', 10, 2);
