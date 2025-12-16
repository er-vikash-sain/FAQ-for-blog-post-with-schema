# Testing the WordPress Plugin on macOS

Step‑by‑step guide to spin up a local WordPress site, run the plugin, and execute checks on macOS.

## Prerequisites
- Homebrew installed.
- A clone of your plugin (instructions below).
- Packages: `php`, `mysql`, `wp-cli`, `composer` (and `phpunit` if you want to run tests).
  ```bash
  brew install php mysql wp-cli composer
  brew services start mysql   # start MySQL
  ```

## Clone the Plugin (starting from zero)
Pick a workspace directory, then clone:
```bash
mkdir -p ~/Documents/temp
cd ~/Documents/temp
git clone <REPO_URL> faq-plugin
cd faq-plugin
```

## Set Up a Throwaway WordPress Site
Pick a scratch directory (adjust paths as needed):
```bash
SITE_DIR=/tmp/wp-test-site
PLUGIN_DIR="$HOME/Documents/temp/faq-plugin"
mkdir -p "$SITE_DIR"
cd "$SITE_DIR"

# Download WordPress
wp core download

# Create wp-config.php (update DB creds if your MySQL setup differs)
wp config create --dbname=wp_test --dbuser=root --dbpass='' --dbhost=127.0.0.1

# Create database
wp db create

# Install WordPress
wp core install --url=http://localhost:8080 --title="FAQ Plugin Test" --admin_user=admin --admin_password=admin --admin_email=admin@example.com

# Link the plugin into this site
ln -s "$PLUGIN_DIR" wp-content/plugins/faq-plugin

# Activate the plugin
wp plugin activate faq-plugin
```

## Run the Site
Serve locally and visit http://localhost:8080:
```bash
wp server --host=localhost --port=8080
```

## Manual Verification (quick checklist)
- Settings: visit Settings → FAQ Settings, toggle Display Position and save.
- Post edit screen: add FAQs in the meta box; reorder/remove; save; confirm values persist.
- Frontend (singular post): confirm FAQs render after content (or via `[faq_accordion]` when set to shortcode), accordion toggles with keyboard + click, IDs are unique, and nothing renders when no FAQs exist.
- Source check: in the footer, confirm a single `<script type="application/ld+json">` with FAQPage JSON-LD; verify nothing appears on archives/feeds/REST.
- i18n: switch site language and verify strings are translated.

## Coding Standards (PHPCS)
From the plugin directory:
```bash
cd "$PLUGIN_DIR"
composer install              # if vendor is not present
vendor/bin/phpcs .
```
If you prefer using Homebrew’s PHPCS with WPCS, set it up once:
```bash
git clone https://github.com/WordPress/WordPress-Coding-Standards.git ~/.wpcs
phpcs --config-set installed_paths ~/.wpcs
phpcs -i   # verify WordPress standards appear
```

## Automated Test (PHPUnit)
WordPress tests library required (one-time):
```bash
cd "$PLUGIN_DIR"
wp scaffold plugin-tests faq-plugin   # downloads WP test suite installer into bin/
bash bin/install-wp-tests.sh wp_test root '' 127.0.0.1 latest
```
Then run the schema test:
```bash
WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit
```

## Clean Up
Stop MySQL if you started it just for testing:
```bash
brew services stop mysql
```
Remove the scratch site when done:
```bash
rm -rf /tmp/wp-test-site
```
