# Auto Composer Update

A plugin that uses WordPress Automatic Updater to send data to an API that updates the "composer.json", add changes and commit. It works for plugins and core.

## Installation and usage

1. Install the plugin with composer :

```
{
      "type": "package",
      "package": {
        "name": "yeswedev-team/auto-composer-update",
        "version": "3.0.0",
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
  
2. Add the `WP_CURRENT_PATH`, `GIT_REPOSITORY`, `GIT_BRANCH` and `API_UPDATE_WORDPRESS` environment variable, which points to the path of the WordPress project
3. In your WordPress configuration, check the presence of these lines on the environment you want
   1. `Config::define('AUTOMATIC_UPDATER_DISABLED', false);`
   2. `Config::define('FS_METHOD', 'direct');`
   3. `Config::define('WP_AUTO_UPDATE_CORE', true);`
   4. `Config::define('DISALLOW_FILE_MODS', false);`
4. Go to back-office and enable the plugin.
5. Wait for WordPress Automatic Updater or use CLI to trigger it : `wp eval 'do_action("wp_maybe_auto_update");'`

## Help

When the update fail, WordPress may add a '.lock' into the wp_options table, normally it will expire before the next trigger of the WordPress Automatic Updater, but you can also remove it manually : `DELETE FROM 'wp_options' WHERE 'option_name' LIKE '%.lock%';` to be sure.
