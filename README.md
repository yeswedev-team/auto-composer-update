# Auto Composer Update

A plugin that automatically updates the composer when a WordPress plugin is updated.

## Installation and usage

- Install the plugin with composer :
```
{
      "type": "package",
      "package": {
        "name": "yeswedev-team/auto-composer-update",
        "version": "2.0.0",
        "type": "wordpress-plugin",
        "dist": {
          "type": "zip",
          "url": "https://github.com/yeswedev-team/auto-composer-update/archive/refs/tags/{%VERSION}.zip"
        },
        "require": {
          "guzzlehttp/guzzle": "^7.8"
        }
      }
    }
```
  
- Add the 'WP_CURRENT_PATH', 'GIT_REPOSITORY', 'GIT_BRANCH' and 'API_UPDATE_WORDPRESS' environment variable, which points to the path of the WordPress project
- Ensure that the machine's SSH key has permission to push onto the branch.
- In your WordPress configuration, please ensure that these 2 lines are present.

`Config::define('AUTOMATIC_UPDATER_DISABLED', false);`
`Config::define('FS_METHOD', 'direct');`
