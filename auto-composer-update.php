<?php

use GuzzleHttp\Client;

/**
* Plugin Name: Auto Composer Update
* Description: A plugin that automatically updates the composer when a WordPress plugin is updated.
* Version: 1.0
* Author: Yes We Dev
* Author URI: https://yeswedev.bzh/
*/

function on_upgrader_process_complete(array $results): void
{
    if ( ! function_exists('write_log')) {
        function write_log ( $log )  {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
    
global $wp_version;
    
$body = [];
$body['git'] = env('GIT_REPOSITORY');
$body['branch'] = env('GIT_BRANCH');
$body['wordpressVersion'] = $wp_version;
    
chdir(env('WP_CURRENT_PATH'));
    
/** @var array $composer_json */
$composer_json = json_decode(file_get_contents('composer.json'), true);
    
$plugins = $results['plugin'];
foreach ($plugins as $plugin) {
    foreach ($composer_json['require'] as $name => $version) {
        if (str_contains($name, $plugin->item->slug)) {
            $body['plugins'][] = [
                'name' => $name,
                'version' => $plugin->item->new_version
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
        write_log($e);
        echo '<div class="updated notice is-dismissible">';
        echo '<p>Une erreur est survenue, veuillez remettre les plugins à leur version initiale.</p>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '</div>';

        return;
    }
        
    if ($response->getStatusCode() == 500) {
        write_log($response);
        echo '<div class="updated notice is-dismissible">';
        echo '<p>Une erreur est survenue, veuillez remettre les plugins à leur version initiale.</p>';
        echo '</div>';
    }
}
    
add_filter( 'automatic_updates_is_vcs_checkout', '__return_false', 1 );
add_action('automatic_updates_complete', 'on_upgrader_process_complete', 10, 2);
