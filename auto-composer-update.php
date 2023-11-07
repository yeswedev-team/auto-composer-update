<?php

use GuzzleHttp\Client;

/**
* Plugin Name: Auto Composer Update
* Description: A plugin that automatically updates the composer when a WordPress plugin is updated.
* Version: 1.0
* Author: Yes We Dev
* Author URI: https://yeswedev.bzh/
*/

function write_log($log) {
    if ( ! function_exists('write_log')) {
        function write_log ( $log )  {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}

function get_composer_json(string $path) {
    $composer_json_path = $path . '/composer.json';

    $composer_json = file_get_contents($composer_json_path);
    if (!$composer_json) {
        write_log('Can\'t read composer.json.');
        return;
    }
    
    return json_decode($composer_json, true) ?: false;
}

function update_composer(array $body) {
    $client = new Client();
    try {
        $response = $client->post(
            env('API_UPDATE_WORDPRESS'),
            ['form_params' => $body]
        );

        if ($response->getStatusCode() === 500) {
            write_log('Error: ' . $response->getBody());
            return false;
        }
        
        return true;
    } catch (\GuzzleHttp\Exception\GuzzleException $e) {
        write_log($e);
        return false;
    }
}

function on_upgrader_process_complete(array $update_results) {
    global $wp_version;

    $body = [
        'git' => env('GIT_REPOSITORY'),
        'branch' => env('GIT_BRANCH'),
        'wordpressVersion' => $wp_version,
        'plugins' => []
    ];

    $composer_json = get_composer_json(env('WP_CURRENT_PATH'));

    foreach ($update_results['plugin'] as $plugin) {
        foreach ($composer_json['require'] as $name => $version) {
            if (str_contains($name, $plugin->item->slug)) {
                $body['plugins'][] = [
                    'name' => $name,
                    'version' => $plugin->item->new_version
                ];
            }
        }
    }

    $update_result = update_composer($body);

    if (!$update_result) {
        write_log('Error while updating composer.json');
    }
}

add_filter( 'automatic_updates_is_vcs_checkout', '__return_false', 1 );
add_action('automatic_updates_complete', 'on_upgrader_process_complete', 10, 2);
