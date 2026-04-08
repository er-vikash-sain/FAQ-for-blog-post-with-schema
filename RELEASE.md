# Release & Packaging Guide

How to keep dev assets in the repo while shipping a clean production ZIP.

## Keep in Repo (for development)
- `composer.json` (dev tools only)
- `phpcs.xml.dist` (coding standards)
- `phpunit.xml.dist`, `tests/` (automated tests)
- `doc/` (plans, notes)
- `vendor/` (only if you need a local install of dev tools; otherwise add to .gitignore)

## Exclude from Release ZIP
When packaging for install, exclude:
- `doc/`
- `tests/`
- `phpunit.xml.dist`
- `phpcs.xml.dist`
- `composer.json`, `composer.lock`
- `vendor/` (dev tools)
- `.gitignore`, `.git/`
- Any other local build/test artifacts (logs, tmp files)

## Recommended Packaging Steps
```bash
PLUGIN_SLUG=faq-plugin
BUILD_DIR=/tmp/${PLUGIN_SLUG}-release

rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

# Copy only needed files
rsync -av --exclude '.git' \
  --exclude '.gitignore' \
  --exclude 'doc' \
  --exclude 'tests' \
  --exclude 'phpunit.xml.dist' \
  --exclude 'phpcs.xml.dist' \
  --exclude 'composer.json' \
  --exclude 'composer.lock' \
  --exclude 'vendor' \
  ./ "$BUILD_DIR/$PLUGIN_SLUG"

cd "$BUILD_DIR"
zip -r "${PLUGIN_SLUG}.zip" "$PLUGIN_SLUG"
```

Result: `faq-plugin.zip` with only runtime files (`faq.php`, `includes/`, `admin/`, `assets/`, `uninstall.php`). Use this ZIP to install on WordPress. Keep dev files in the repo for ongoing work.***
