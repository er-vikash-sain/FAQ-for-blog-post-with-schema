<?php
/**
 * Main plugin bootstrap class.
 *
 * @package FAQ_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'FAQ_Plugin' ) ) {
	/**
	 * Main plugin bootstrap.
	 */
	class FAQ_Plugin {
		/**
		 * Singleton instance.
		 *
		 * @var FAQ_Plugin|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return FAQ_Plugin
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			$this->includes();
			add_action( 'init', array( $this, 'init_components' ) );
		}

		/**
		 * Load plugin text domain.
		 *
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain(
				FAQ_PLUGIN_TEXT_DOMAIN,
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages'
			);
		}

		/**
		 * Include required files.
		 *
		 * @return void
		 */
		private function includes() {
			require_once FAQ_PLUGIN_DIR . 'includes/class-faq-plugin-display.php';
			require_once FAQ_PLUGIN_DIR . 'includes/class-faq-plugin-schema.php';
			require_once FAQ_PLUGIN_DIR . 'admin/class-faq-plugin-admin.php';
		}

		/**
		 * Initialize plugin components.
		 *
		 * @return void
		 */
		public function init_components() {
			if ( is_admin() ) {
				FAQ_Plugin_Admin::get_instance();
			}

			FAQ_Plugin_Display::get_instance();
			FAQ_Plugin_Schema::get_instance();
		}
	}
}
