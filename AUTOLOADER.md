# Jetpack Autoloader

This addon uses the [Jetpack Autoloader](https://packagist.org/packages/automattic/jetpack-autoloader) to handle class loading.

## Why Jetpack Autoloader?

The Jetpack Autoloader solves a common problem in WordPress plugins: **class conflicts between multiple plugins**.

### The Problem

When multiple Ultimate Multisite addons include the same shared class (like `WP_Ultimo\Multisite_Ultimate_Updater`), PHP throws a fatal error:

```
PHP Fatal error: Cannot redeclare class WP_Ultimo\Multisite_Ultimate_Updater
```

### The Solution

The Jetpack Autoloader:
1. **Prevents duplicate class declarations** by tracking which classes are already loaded
2. **Uses the latest version** of a class when multiple versions exist
3. **Works across multiple plugins** automatically
4. **Requires no manual intervention** - just composer install

## How It Works

1. **composer.json** declares the autoloader as a dependency:
```json
{
    "require": {
        "automattic/jetpack-autoloader": "^3.0"
    },
    "autoload": {
        "classmap": ["inc"]
    }
}
```

2. **Main plugin file** loads only the autoloader:

```php
if (file_exists(ULTIMATE_MULTISITE_MAILSTER_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once ULTIMATE_MULTISITE_MAILSTER_PLUGIN_DIR . 'vendor/autoload.php';
}
```

3. **Jetpack handles the rest**:
   - Scans all installed addons
   - Builds a master classmap with version numbers
   - Loads the latest version of each class
   - Prevents fatal errors from duplicate declarations

## Setup

Install the autoloader:
```bash
composer install --no-dev
```

Or update if already installed:
```bash
composer update --no-dev
```

## Best Practices

1. **Never use `require_once` for classes** - let the autoloader handle it
2. **Always run composer install** after cloning or updating
3. **Keep Jetpack autoloader updated** for bug fixes and improvements
4. **Use semantic versioning** in your classes so the latest version is used

## Autoload Manifests

After running composer, check these files:
- `vendor/composer/jetpack_autoload_classmap.php` - Class to file mappings
- `vendor/composer/jetpack_autoload_psr4.php` - PSR-4 namespaces
- `vendor/jetpack-autoloader/` - Autoloader classes

## Troubleshooting

If you see class redeclaration errors:

1. **Check autoloader is loaded first**:
```php
require_once __DIR__ . '/vendor/autoload.php';
```

2. **Verify classmap is generated**:
```bash
composer dump-autoload
```

3. **Check all addons use Jetpack**:
All addons should use the same autoloader to work properly.

## References

- [Jetpack Autoloader Documentation](https://github.com/Automattic/jetpack-autoloader)
- [Composer Autoloading](https://getcomposer.org/doc/04-schema.md#autoload)
