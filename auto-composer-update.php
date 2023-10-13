<?php
/**
 * Plugin Name: Auto Composer Update
 * Description: A plugin that automatically updates the composer when a WordPress plugin is updated.
 * Version: 1.0
 * Author: Yes We Dev
 * Author URI: https://yeswedev.bzh/
 */

function on_upgrader_process_complete($upgrader_object, $options) {
    if ($options['action'] == 'update' && $options['type'] === 'plugin' && wp_get_environment_type() == 'production') {
        $plugins = $options['plugins'];

        chdir(env('WP_CURRENT_PATH'));

        /** @var array $composer_json */
        $composer_json = json_decode(file_get_contents('composer.json'), true);

        foreach ($plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugin_version = $plugin_data['Version'];
            $plugin_name = explode('/', $plugin)[0];

            foreach ($composer_json['require'] as $name => $version) {
                if (str_contains($name, $plugin_name)) {
                    shell_exec('composer require ' . $name . ' ' . $plugin_version);
                }
            }
        }

        shell_exec('
            git add composer.json composer.lock &&
            git commit -m "Update plugins" &&
            git push
        ');
    }
}
add_action('upgrader_process_complete', 'on_upgrader_process_complete', 10, 2);