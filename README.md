# Elementor null settings fix

This repository provides a patch to handle a `TypeError` in the Elementor
`Controls_Stack::sanitize_settings` method. The error occurs when `null`
settings are passed to the method, resulting in an exception:

```
Elementor\Controls_Stack::sanitize_settings(): Argument #1 ($settings) must be of type array, null given
```

The included patch updates the method signature to accept a nullable array and
defaults to an empty array when `null` is provided.

## Applying the patch

1. Copy `patches/elementor-fix.patch` to your WordPress installation.
   The patch assumes the standard plugin path `wp-content/plugins/elementor/`.
   Adjust the paths in the patch if your installation differs.
2. From the WordPress root directory, run:

```bash
patch -p0 < /path/to/elementor-fix.patch
```

3. Clear caches and reload the page to verify that the error is resolved.

## Where to modify

Open `wp-content/plugins/elementor/includes/base/controls-stack.php` and
locate the `sanitize_settings` method (around line 2513 in Elementor 2.1.5).
Replace the PHPDoc and function signature with the following snippet and add the
null check:

```php
/**
 * @param array|null $settings Settings to sanitize.
 * @param array      $controls Optional. An array of controls. Default is an
 *                             empty array.
 *
 * @return array Sanitized settings.
 */
private function sanitize_settings( ?array $settings, array $controls = [] ) {
    if ( null === $settings ) {
        $settings = [];
    }
    if ( ! $controls ) {
        $controls = $this->get_controls();
    }
    // ... rest of the method ...
}
```
