<?php
/**
 * Frontend display for FAQ plugin.
 *
 * @package FAQ_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'FAQ_Plugin_Display' ) ) {
	/**
	 * Handles rendering FAQ output.
	 */
	class FAQ_Plugin_Display {
		/**
		 * Singleton instance.
		 *
		 * @var FAQ_Plugin_Display|null
		 */
		private static $instance = null;

		/**
		 * Track posts that have rendered to prevent duplicates.
		 *
		 * @var array
		 */
		private $rendered_posts = array();

		/**
		 * Counter for unique IDs.
		 *
		 * @var int
		 */
		private $render_count = 0;

		/**
		 * Get instance.
		 *
		 * @return FAQ_Plugin_Display
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
			add_shortcode( 'faq_accordion', array( $this, 'render_shortcode' ) );
			add_filter( 'the_content', array( $this, 'maybe_append_to_content' ) );
		}

		/**
		 * Append FAQs to the content when setting is after-content.
		 *
		 * @param string $content Post content.
		 * @return string
		 */
		public function maybe_append_to_content( $content ) {
			if ( is_admin() || ! $this->is_main_query_frontend() ) {
				return $content;
			}

			if ( $this->in_blocked_context() ) {
				return $content;
			}

			$post = get_post();
			if ( ! $post || 'post' !== $post->post_type || ! is_singular( 'post' ) ) {
				return $content;
			}

			$display = get_option( FAQ_PLUGIN_OPTION_DISPLAY_POSITION, 'after_content' );
			if ( 'after_content' !== $display ) {
				return $content;
			}

			if ( $this->has_rendered_for_post( $post->ID ) ) {
				return $content;
			}

			$faqs = $this->get_faqs( $post->ID );
			if ( empty( $faqs ) ) {
				return $content;
			}

			$html = $this->build_output( $faqs, $post );
			if ( '' === $html ) {
				return $content;
			}

			$this->mark_rendered( $post->ID );

			return $content . $html;
		}

		/**
		 * Shortcode handler.
		 *
		 * @param array  $atts Shortcode attributes.
		 * @param string $content Content (unused).
		 * @return string
		 */
		public function render_shortcode( $atts, $content = '' ) {
			unset( $atts, $content );

			if ( is_admin() || $this->in_blocked_context() ) {
				return '';
			}

			$post = get_post();
			if ( ! $post || 'post' !== $post->post_type || ! is_singular( 'post' ) ) {
				return '';
			}

			$faqs = $this->get_faqs( $post->ID );
			if ( empty( $faqs ) ) {
				return '';
			}

			if ( $this->has_rendered_for_post( $post->ID ) ) {
				return '';
			}

			$html = $this->build_output( $faqs, $post );
			if ( '' === $html ) {
				return '';
			}

			$this->mark_rendered( $post->ID );

			return $html;
		}

		/**
		 * Build HTML output.
		 *
		 * @param array   $faqs Array of FAQs.
		 * @param WP_Post $post Post object.
		 * @return string
		 */
		private function build_output( $faqs, $post ) {
			$prefix = 'faq-plugin-' . absint( $post->ID ) . '-' . $this->render_count;
			++$this->render_count;

			ob_start();

			/**
			 * Fires before FAQ accordion render.
			 */
			do_action( 'faq_accordion_before_render', $post->ID );

			// Ensure assets are loaded for both default and theme override templates.
			$this->enqueue_assets();

			$template = locate_template( 'faq-plugin/accordion.php' );
			if ( $template ) {
				$faqs_for_template = $faqs;
				$faq_prefix        = $prefix;
				$faq_post_id       = $post->ID;
				include $template;
			} else {
				echo '<h2 class="faq-plugin-heading">' . esc_html__( 'Related FAQ', 'faq-plugin' ) . '</h2>';
				echo '<div class="faq-plugin-accordion" id="' . esc_attr( $prefix ) . '">';

				foreach ( $faqs as $index => $faq ) {
					$question  = isset( $faq['question'] ) ? $faq['question'] : '';
					$answer    = isset( $faq['answer'] ) ? $faq['answer'] : '';
					$item_id   = $prefix . '-item-' . $index;
					$button_id = $item_id . '-button';
					$panel_id  = $item_id . '-panel';
					?>
					<div class="faq-plugin-item">
						<h3 class="faq-plugin-question">
							<button
								type="button"
								class="faq-plugin-toggle"
								aria-expanded="false"
								aria-controls="<?php echo esc_attr( $panel_id ); ?>"
								id="<?php echo esc_attr( $button_id ); ?>"
							>
								<span class="faq-plugin-question__text">
									<span class="faq-plugin-question__number"><?php echo esc_html( $index + 1 ); ?></span>
									<span><?php echo esc_html( $question ); ?></span>
								</span>
								<span class="faq-plugin-icon" aria-hidden="true"></span>
							</button>
						</h3>
						<div
							id="<?php echo esc_attr( $panel_id ); ?>"
							class="faq-plugin-answer"
							role="region"
							aria-labelledby="<?php echo esc_attr( $button_id ); ?>"
							hidden
						>
							<div class="faq-plugin-answer__content">
								<?php echo wp_kses_post( wpautop( $answer ) ); ?>
							</div>
						</div>
					</div>
					<?php
				}

				echo '</div>';
			}

			/**
			 * Fires after FAQ accordion render.
			 */
			do_action( 'faq_accordion_after_render', $post->ID );

			$html = ob_get_clean();

			/**
			 * Filter the FAQ accordion HTML output.
			 *
			 * @param string $html HTML output.
			 * @param int    $post_id Post ID.
			 * @param array  $faqs FAQ data.
			 */
			return apply_filters( 'faq_accordion_output_html', $html, $post->ID, $faqs );
		}

		/**
		 * Enqueue frontend assets.
		 *
		 * @return void
		 */
		private function enqueue_assets() {
			wp_enqueue_style(
				'faq-plugin-frontend',
				FAQ_PLUGIN_URL . 'assets/css/faq-frontend.css',
				array(),
				FAQ_PLUGIN_VERSION
			);

			wp_enqueue_script(
				'faq-plugin-frontend',
				FAQ_PLUGIN_URL . 'assets/js/faq-frontend.js',
				array(),
				FAQ_PLUGIN_VERSION,
				true
			);
		}

		/**
		 * Get FAQs for a post.
		 *
		 * @param int $post_id Post ID.
		 * @return array
		 */
		private function get_faqs( $post_id ) {
			$faqs = get_post_meta( $post_id, FAQ_PLUGIN_META_KEY, true );
			if ( ! is_array( $faqs ) || empty( $faqs ) ) {
				return array();
			}

			return $faqs;
		}

		/**
		 * Check blocked contexts.
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
		 * Ensure main query and frontend.
		 *
		 * @return bool
		 */
		private function is_main_query_frontend() {
			return ( ! is_admin() ) && is_main_query();
		}

		/**
		 * Has post rendered.
		 *
		 * @param int $post_id Post ID.
		 * @return bool
		 */
		private function has_rendered_for_post( $post_id ) {
			return in_array( (int) $post_id, $this->rendered_posts, true );
		}

		/**
		 * Mark post as rendered.
		 *
		 * @param int $post_id Post ID.
		 * @return void
		 */
		private function mark_rendered( $post_id ) {
			$this->rendered_posts[] = (int) $post_id;
		}
	}
}
