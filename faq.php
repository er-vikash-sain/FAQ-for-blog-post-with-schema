<?php
/**
 * Plugin Name: FAQ for blog post with schema
 * Description: Add FAQs to blog posts, render as an accessible accordion, and output FAQPage JSON-LD schema.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: faq-plugin
 * Domain Path: /languages
 *
 * @package FAQ_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FAQ_PLUGIN_VERSION', '1.0.0' );
define( 'FAQ_PLUGIN_TEXT_DOMAIN', 'faq-plugin' );
define( 'FAQ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FAQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FAQ_PLUGIN_OPTION_DISPLAY_POSITION', 'faq_plugin_display_position' );
define( 'FAQ_PLUGIN_OPTION_DELETE_DATA', 'faq_plugin_delete_data_on_uninstall' );
define( 'FAQ_PLUGIN_META_KEY', 'faq_plugin_faqs' );

require_once FAQ_PLUGIN_DIR . 'includes/class-faq-plugin.php';

/**
 * Set default options on activation.
 *
 * @return void
 */
function faq_plugin_activate() {
	if ( ! get_option( FAQ_PLUGIN_OPTION_DISPLAY_POSITION ) ) {
		update_option( FAQ_PLUGIN_OPTION_DISPLAY_POSITION, 'after_content' );
	}

	if ( false === get_option( FAQ_PLUGIN_OPTION_DELETE_DATA ) ) {
		update_option( FAQ_PLUGIN_OPTION_DELETE_DATA, 0 );
	}
}
register_activation_hook( __FILE__, 'faq_plugin_activate' );

// Bootstrap the plugin.
FAQ_Plugin::get_instance();
