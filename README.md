# Auto Composer Update

A plugin that automatically updates the composer when a WordPress plugin is updated.

## Installation and usage

- Install the plugin with composer
- Add the 'WP_CURRENT_PATH', 'GIT_REPOSITORY', 'GIT_BRANCH' and 'API_UPDATE_WORDPRESS' environment variable, which points to the path of the WordPress project
- In your WordPress configuration, please ensure that these 2 lines are present.

`Config::define('AUTOMATIC_UPDATER_DISABLED', false);`

`Config::define('FS_METHOD', 'direct');`
