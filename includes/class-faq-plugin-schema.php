<?php
/**
 * Schema output for FAQ plugin.
 *
 * @package FAQ_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'FAQ_Plugin_Schema' ) ) {
	/**
	 * Handles FAQPage schema output.
	 */
	class FAQ_Plugin_Schema {
		/**
		 * Singleton instance.
		 *
		 * @var FAQ_Plugin_Schema|null
		 */
		private static $instance = null;

		/**
		 * Track posts already printed.
		 *
		 * @var array
		 */
		private $printed_posts = array();

		/**
		 * Get instance.
		 *
		 * @return FAQ_Plugin_Schema
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
			add_action( 'wp_footer', array( $this, 'print_schema' ), 20 );
		}

		/**
		 * Print schema in footer.
		 *
		 * @return void
		 */
		public function print_schema() {
			if ( is_admin() || $this->in_blocked_context() ) {
				return;
			}

			if ( ! is_singular( 'post' ) ) {
				return;
			}

			$post = get_post();
			if ( ! $post || 'post' !== $post->post_type ) {
				return;
			}

			if ( $this->has_printed_for_post( $post->ID ) ) {
				return;
			}

			$faqs = get_post_meta( $post->ID, FAQ_PLUGIN_META_KEY, true );
			if ( ! is_array( $faqs ) || empty( $faqs ) ) {
				return;
			}

			$schema = $this->build_schema_array( $faqs );
			if ( empty( $schema ) ) {
				return;
			}

			/**
			 * Filter schema data before output.
			 *
			 * @param array $schema Schema data.
			 * @param int   $post_id Post ID.
			 * @param array $faqs Raw FAQ data.
			 */
			$schema = apply_filters( 'faq_plugin_schema', $schema, $post->ID, $faqs );

			if ( empty( $schema ) || ! is_array( $schema ) ) {
				return;
			}

			$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			if ( ! $json ) {
				return;
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe JSON-LD output.
			echo "\n" . '<script type="application/ld+json">' . $json . '</script>' . "\n";

			$this->mark_printed( $post->ID );
		}

		/**
		 * Build schema array from FAQs.
		 *
		 * @param array $faqs FAQ data.
		 * @return array
		 */
		public function build_schema_array( $faqs ) {
			if ( ! is_array( $faqs ) || empty( $faqs ) ) {
				return array();
			}

			$entities = array();
			foreach ( $faqs as $faq ) {
				$question = isset( $faq['question'] ) ? $this->prepare_text( $faq['question'] ) : '';
				$answer   = isset( $faq['answer'] ) ? $this->prepare_text( $faq['answer'] ) : '';

				if ( '' === $question || '' === $answer ) {
					continue;
				}

				$entities[] = array(
					'@type'          => 'Question',
					'name'           => $question,
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $answer,
					),
				);
			}

			if ( empty( $entities ) ) {
				return array();
			}

			return array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $entities,
			);
		}

		/**
		 * Prepare text for schema (plain text only).
		 *
		 * @param string $value Text value.
		 * @return string
		 */
		private function prepare_text( $value ) {
			$value = (string) $value;
			$value = strip_shortcodes( $value );
			$value = wp_strip_all_tags( $value );
			$value = trim( $value );

			return $value;
		}

		/**
		 * Detect blocked contexts.
		 *
		 * @return bool
		 */
		private function in_blocked_context() {
			if ( is_feed() || is_embed() || wp_doing_ajax() || wp_doing_cron() ) {
				return true;
			}

			if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
				return true;
			}

			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				return true;
			}

			return false;
		}

		/**
		 * Has schema printed for post.
		 *
		 * @param int $post_id Post ID.
		 * @return bool
		 */
		private function has_printed_for_post( $post_id ) {
			return in_array( (int) $post_id, $this->printed_posts, true );
		}

		/**
		 * Mark schema printed.
		 *
		 * @param int $post_id Post ID.
		 * @return void
		 */
		private function mark_printed( $post_id ) {
			$this->printed_posts[] = (int) $post_id;
		}
	}
}
