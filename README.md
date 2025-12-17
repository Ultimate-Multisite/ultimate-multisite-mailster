# Mailster Integration - Ultimate Multisite Addon

Automatically subscribe customers to Mailster email lists during checkout with product-based segmentation and flexible opt-in options.

## Features

- **Product-based Segmentation**: Assign customers to different lists based on products purchased
- **Flexible Timing**: Subscribe on order creation (immediate) or payment complete
- **Compliance-Friendly**: Automatic or checkbox opt-in modes for GDPR compliance
- **Double Opt-in**: Optional email confirmation before subscription
- **Field Mapping**: Automatically sync customer data (name, email, address) to Mailster
- **Multiple Lists**: Support for assigning customers to multiple lists per product
- **Graceful Error Handling**: Never blocks checkout if Mailster API fails
- **Comprehensive Logging**: All operations logged for debugging

## Requirements

- WordPress 5.3 or higher
- PHP 7.4 or higher
- Ultimate Multisite plugin (active)
- Mailster plugin (active)

## Installation

1. Upload the addon files to `/wp-content/plugins/` directory
2. Run `composer install --no-dev` to install dependencies
3. Network activate the plugin in WordPress
4. Configure settings at WP Ultimo > Settings > Mailster Integration

## Configuration

### Global Settings

Navigate to **WP Ultimo > Settings > Mailster Integration**:

The integration is automatically active when Mailster is installed on the main site. Configure these settings to control behavior:

1. **Subscription Timing**:
   - Order Creation (Immediate) - Subscribe when customer is created
   - Payment Complete - Subscribe after payment is confirmed
2. **Opt-in Mode**:
   - Automatic - Subscribe all customers automatically
   - Checkbox - Require explicit consent via checkout field
3. **Double Opt-in**: Enable email confirmation before subscription
4. **Default Lists**: Select global default lists for all customers
5. **Update Existing**: Choose whether to update existing subscribers
6. **Map Fields**: Enable/disable customer field mapping to Mailster

### Product Settings

Edit any product and navigate to the **Mailster** tab:

**By default, all products use the global default lists.** No configuration needed.

To customize lists for a specific product:
1. Enable **Override Global Lists**
2. Select product-specific lists (multiple allowed)

To disable Mailster for a specific product:
1. Enable **Override Global Lists**
2. Leave all lists unchecked

Customers who purchase products will be added to either:
- The product-specific lists (if override is enabled)
- The global default lists (if override is disabled)

### Checkout Field (Optional)

If using "Checkbox" opt-in mode:

1. Edit your checkout form
2. Add the **Mailster Opt-in Checkbox** field
3. Customize the label text
4. Configure default checked state
5. Save the form

The checkbox will only appear when opt-in mode is set to "Requires Checkbox Confirmation".

## Architecture

### Core Classes

- **Mailster_Main** - Main logic, hook registration, subscription flow
- **Subscriber_Manager** - Mailster API wrapper with error handling
- **Settings_Manager** - Settings registration and management
- **Product_Integration** - Product page extensions
- **Mailster_Optin_Field** - Custom checkout field

### File Structure

```
ultimate-multisite-mailster/
├── ultimate-multisite-mailster.php    # Main plugin file
├── inc/
│   ├── class-mailster-main.php        # Main logic & hooks
│   ├── class-subscriber-manager.php   # Mailster API wrapper
│   ├── class-settings-manager.php     # Settings registration
│   ├── class-product-integration.php  # Product page extension
│   ├── class-multisite-ultimate-updater.php # Auto-updater
│   └── checkout/
│       └── class-mailster-optin-field.php # Custom checkout field
├── vendor/                             # Composer dependencies
├── composer.json                       # PHP dependencies
├── AUTOLOADER.md                       # Autoloader documentation
└── README.md                           # This file
```

### Autoloading

This addon uses the **Jetpack Autoloader** to prevent class conflicts between multiple Ultimate Multisite addons.

See [AUTOLOADER.md](AUTOLOADER.md) for detailed information about how autoloading works.

**Important**: Always run `composer install --no-dev` after installing or updating the addon.

## Development

### Setup

```bash
# Install dependencies
composer install
npm install

# Run tests
vendor/bin/phpunit

# Run code standards checks
vendor/bin/phpcs
vendor/bin/phpstan

# Fix code style issues
vendor/bin/phpcbf

# Build for production
npm run build
```

### Testing

The addon includes comprehensive testing:

```bash
# Run all tests
vendor/bin/phpunit

# Run with detailed output
vendor/bin/phpunit --testdox

# Run specific test class
vendor/bin/phpunit tests/class-mailster-test.php
```

### Code Standards

- WordPress Coding Standards (enforced via PHPCS)
- PHP 7.4+ with type declarations
- PHPStan static analysis
- Rector for code modernization

## Hooks and Filters

### Actions Used

- `wu_customer_post_create` - Subscribe on customer creation
- `wu_payment_status_changed` - Subscribe on payment complete
- `wu_register_gateways` - Register custom fields
- `init` - Register checkout field type

### Filters Used

- `wu_product_options_sections` - Add Mailster section to products

### Custom Hooks

This addon doesn't add custom hooks, but respects all Ultimate Multisite core hooks.

## Troubleshooting

### Class Redeclaration Errors

If you see:
```
PHP Fatal error: Cannot redeclare class WP_Ultimo\Multisite_Ultimate_Updater
```

**Solution**: Make sure you've run `composer install --no-dev`. The Jetpack Autoloader prevents this error.

### Customers Not Being Subscribed

Check the following:

1. **Mailster active**: Verify Mailster plugin is active on the main site
2. **Lists configured**: Check global default lists or product-specific lists
3. **Opt-in checked**: If using checkbox mode, verify customer checked the box
4. **Logs**: Check WP Ultimo > Logs (type: mailster) for error messages
5. **Timing setting**: Verify subscription timing matches your workflow

### Subscribers Missing Data

If subscriber fields are empty in Mailster:

1. **Field mapping enabled**: Check "Map Customer Fields" setting is ON
2. **Customer data exists**: Verify customer has filled in billing details
3. **Mailster fields**: Ensure Mailster has matching custom fields created

## Data Flow

### Order Creation Flow

```
Customer completes checkout
↓
Hook: wu_customer_post_create
↓
Check: Timing=order_creation? Customer opted in?
↓
Get product lists (or fallback to global defaults)
↓
Map customer fields to Mailster subscriber data
↓
Add subscriber to Mailster (with double opt-in setting)
↓
Assign subscriber to lists
↓
Log result (success or error)
```

### Payment Complete Flow

```
Payment status changes to 'completed'
↓
Hook: wu_payment_status_changed
↓
Check: Timing=payment_complete? Status=completed?
↓
Get customer from payment
↓
Check: Customer opted in?
↓
[Same as Order Creation Flow from step 4]
```

## License

This addon is licensed under GPL v3 or later.

## Support

For support and documentation:
- Visit [MultisiteUltimate.com](https://multisiteultimate.com)
- Check Ultimate Multisite documentation
- Review Mailster integration guides

## Changelog

### Version 1.0.0 (2025-12-16)

- Initial release
- Product-based list segmentation
- Flexible timing options (order creation/payment complete)
- Opt-in modes (automatic/checkbox)
- Double opt-in support
- Customer field mapping
- Jetpack Autoloader integration
- Comprehensive error handling and logging
