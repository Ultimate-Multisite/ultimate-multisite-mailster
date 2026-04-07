# Ultimate Multisite: Mailster Integration

Integrate with Mailster email marketing during checkout to subscribe users to mailing lists.

This is an addon for [Ultimate Multisite](https://ultimatemultisite.com), the WordPress multisite management plugin. It requires the main Ultimate Multisite plugin to be active.

## Build Commands

```bash
composer install                        # Install PHP dependencies
npm install                             # Install Node tooling
npm run build                           # Production build (minify + makepot + archive)
npm run makepot                         # Regenerate translation .pot file
```

## Lint / Static Analysis

```bash
vendor/bin/phpcs                     # Run PHP_CodeSniffer (config: .phpcs.xml.dist)
vendor/bin/phpstan analyse              # Run PHPStan static analysis
vendor/bin/rector --dry-run             # Preview Rector refactoring changes
```

## Testing

```bash
npm test                               # Run full PHPUnit test suite (multisite enabled)
vendor/bin/phpunit --filter ClassName   # Run a single test class
```

Tests run inside a WordPress multisite environment (`WP_TESTS_MULTISITE=1`).

## Project Structure

```
ultimate-multisite-mailster.php  # Main plugin bootstrap
inc/                        # PHP classes (autoloaded via Composer classmap)
views/                      # PHP template partials
lang/                       # Translation files (.pot / .po / .mo)
tests/                      # PHPUnit tests
composer.json               # PHP dependencies and autoloading
package.json                # Node tooling (minification, i18n, build scripts)
.phpcs.xml.dist             # PHP_CodeSniffer ruleset
phpstan.neon.dist           # PHPStan configuration
rector.php                  # Rector automated refactoring config
phpunit.xml.dist            # PHPUnit configuration
```

## Code Style

- **Standard**: WordPress Coding Standards (WPCS)
- **Indentation**: Tabs, not spaces
- **PHP Compatibility**: 7.4+
- **Functions**: `snake_case` — e.g. `wu_get_setting()`
- **Classes**: `PascalCase` — e.g. `WP_Ultimo_Captcha`
- **Class files**: `class-kebab-case.php` — e.g. `class-gateway-manager.php`
- **Test files**: `PascalCase_Test.php` — e.g. `GoCardless_Gateway_Test.php`
- **Hooks**: prefixed `wu_` — e.g. `wu_checkout_completed`
- **Yoda conditions**: `if ( 'value' === $var )`
- **Short arrays**: `[]` not `array()`
- **Short ternary**: `$a ?: $b` is allowed
- **Global prefixes**: `wu_`, `wp_ultimo` (legacy; preserved for backward compatibility)
- **Text domain**: `ultimate-multisite-mailster`

## Security

Every PHP file must start with:

```php
defined('ABSPATH') || exit;
```

## i18n

All user-facing strings must be translatable:

```php
__('Text', 'ultimate-multisite-mailster')
_e('Text', 'ultimate-multisite-mailster')
```

## Error Handling

Use the WordPress `WP_Error` pattern — not exceptions:

```php
$result = wu_some_operation();
if (is_wp_error($result)) {
    return $result;
}
```

## Build Artifacts

`*.min.js` and `*.min.css` files are generated during the release build. **Do not commit them on feature branches.**

## Dependencies

The main Ultimate Multisite plugin (`ultimate-multisite`) must be network-activated. This addon hooks into its API and extends its functionality.

## Local Development Environment

The shared WordPress dev install for testing this plugin is at `../../wordpress` (relative to this addon subdir).

- **URL**: http://wordpress.local:8080
- **Admin**: http://wordpress.local:8080/wp-admin — `admin` / `admin`
- **WordPress version**: 7.0-RC2
- **This plugin**: symlinked into `../../wordpress/wp-content/plugins/$(basename $PWD)`
- **Reset to clean state**: `cd ../../wordpress && ./reset.sh`

WP-CLI is configured via `wp-cli.yml` in this addon subdir — run `wp` commands directly from here without specifying `--path`.

```bash
wp plugin activate $(basename $PWD)   # activate this plugin
wp plugin deactivate $(basename $PWD) # deactivate
```
