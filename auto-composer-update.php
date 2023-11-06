<?php

use GuzzleHttp\Client;

/**
 * Plugin Name: Auto Composer Update
 * Description: A plugin that automatically updates the composer when a WordPress plugin is updated.
 * Version: 1.0
 * Author: Yes We Dev
 * Author URI: https://yeswedev.bzh/
 */

function on_upgrader_process_complete($upgrader_object, $options): void
{
    if ($options['action'] == 'update' && $options['type'] === 'plugin') {
        $plugins = $options['plugins'];
        global $wp_version;

        $body = [];
        $body['git'] = env('GIT_REPOSITORY');
        $body['branch'] = env('GIT_BRANCH');
        $body['wordpressVersion'] = $wp_version;

        chdir(env('WP_CURRENT_PATH'));

        /** @var array $composer_json */
        $composer_json = json_decode(file_get_contents('composer.json'), true);

        foreach ($plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugin_version = $plugin_data['Version'];
            $plugin_name = explode('/', $plugin)[0];

            foreach ($composer_json['require'] as $name => $version) {
                if (str_contains($name, $plugin_name)) {
                    $body['plugins'][] = [
                        'name' => $name,
                        'version' => $plugin_version
                    ];
                }
            }
        }

        $client = new Client();

        try {
            $response = $client->post(
                env('API_UPDATE_WORDPRESS'),
                [
                    'form_params' => $body
                ]
            );
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            echo '<div class="updated notice is-dismissible">';
            echo '<p>Une erreur est survenue, veuillez remettre les plugins à leur version initiale.</p>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '</div>';
        }

        if ($response->getStatusCode() == 500) {
            echo '<div class="updated notice is-dismissible">';
            echo '<p>Une erreur est survenue, veuillez remettre les plugins à leur version initiale.</p>';
            echo '</div>';
        }
    }
}

add_filter('automatic_updates_is_vcs_checkout', '__return_false', 1);
add_action('upgrader_process_complete', 'on_upgrader_process_complete', 10, 2);
