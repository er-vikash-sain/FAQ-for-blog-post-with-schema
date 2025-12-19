<?php
/**
 * Admin functionality for FAQ plugin.
 *
 * @package FAQ_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'FAQ_Plugin_Admin' ) ) {
	/**
	 * Admin handler.
	 */
	class FAQ_Plugin_Admin {
		/**
		 * Singleton instance.
		 *
		 * @var FAQ_Plugin_Admin|null
		 */
		private static $instance = null;

		/**
		 * Get instance.
		 *
		 * @return FAQ_Plugin_Admin
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
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		}

		/**
		 * Add settings page.
		 *
		 * @return void
		 */
		public function add_settings_page() {
			add_options_page(
				__( 'FAQ Settings', 'faq-plugin' ),
				__( 'FAQ Settings', 'faq-plugin' ),
				'manage_options',
				'faq-plugin',
				array( $this, 'render_settings_page' )
			);
		}

		/**
		 * Register plugin settings.
		 *
		 * @return void
		 */
		public function register_settings() {
			register_setting(
				'faq_plugin_settings',
				FAQ_PLUGIN_OPTION_DISPLAY_POSITION,
				array(
					'type'              => 'string',
					'sanitize_callback' => array( $this, 'sanitize_display_position' ),
					'default'           => 'after_content',
				)
			);

			register_setting(
				'faq_plugin_settings',
				FAQ_PLUGIN_OPTION_DELETE_DATA,
				array(
					'type'              => 'boolean',
					'sanitize_callback' => array( $this, 'sanitize_delete_data' ),
					'default'           => 0,
				)
			);

			register_setting(
				'faq_plugin_settings',
				FAQ_PLUGIN_OPTION_FIRST_ITEM_OPEN,
				array(
					'type'              => 'boolean',
					'sanitize_callback' => array( $this, 'sanitize_first_item_open' ),
					'default'           => 1,
				)
			);

			register_setting(
				'faq_plugin_settings',
				FAQ_PLUGIN_OPTION_Q_ICON_COLOR,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_hex_color',
					'default'           => '#6366F1',
				)
			);

			register_setting(
				'faq_plugin_settings',
				FAQ_PLUGIN_OPTION_Q_BG_COLOR,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_hex_color',
					'default'           => '#E0E7FF',
				)
			);

			add_settings_section(
				'faq_plugin_display_section',
				__( 'Display Settings', 'faq-plugin' ),
				'__return_false',
				'faq-plugin'
			);

			add_settings_field(
				FAQ_PLUGIN_OPTION_DISPLAY_POSITION,
				__( 'Display Position', 'faq-plugin' ),
				array( $this, 'render_display_position_field' ),
				'faq-plugin',
				'faq_plugin_display_section'
			);

			add_settings_field(
				FAQ_PLUGIN_OPTION_DELETE_DATA,
				__( 'Delete Data on Uninstall', 'faq-plugin' ),
				array( $this, 'render_delete_data_field' ),
				'faq-plugin',
				'faq_plugin_display_section'
			);

			add_settings_field(
				FAQ_PLUGIN_OPTION_FIRST_ITEM_OPEN,
				__( 'First Item Open', 'faq-plugin' ),
				array( $this, 'render_first_item_open_field' ),
				'faq-plugin',
				'faq_plugin_display_section'
			);

			add_settings_field(
				FAQ_PLUGIN_OPTION_Q_ICON_COLOR,
				__( 'Q & Arrow Color', 'faq-plugin' ),
				array( $this, 'render_q_icon_color_field' ),
				'faq-plugin',
				'faq_plugin_display_section'
			);

			add_settings_field(
				FAQ_PLUGIN_OPTION_Q_BG_COLOR,
				__( 'Q Background Color', 'faq-plugin' ),
				array( $this, 'render_q_bg_color_field' ),
				'faq-plugin',
				'faq_plugin_display_section'
			);
		}

		/**
		 * Sanitize display position setting.
		 *
		 * @param string $value Submitted value.
		 * @return string
		 */
		public function sanitize_display_position( $value ) {
			$allowed = array( 'after_content', 'shortcode' );
			if ( ! in_array( $value, $allowed, true ) ) {
				return 'after_content';
			}

			return $value;
		}

		/**
		 * Sanitize delete data setting.
		 *
		 * @param mixed $value Submitted value.
		 * @return int
		 */
		public function sanitize_delete_data( $value ) {
			return ( isset( $value ) && '1' === (string) $value ) ? 1 : 0;
		}

		/**
		 * Sanitize first item open setting.
		 *
		 * @param mixed $value Submitted value.
		 * @return int
		 */
		public function sanitize_first_item_open( $value ) {
			return ( isset( $value ) && '1' === (string) $value ) ? 1 : 0;
		}

		/**
		 * Render display position field.
		 *
		 * @return void
		 */
		public function render_display_position_field() {
			$value = get_option( FAQ_PLUGIN_OPTION_DISPLAY_POSITION, 'after_content' );
			?>
			<select name="<?php echo esc_attr( FAQ_PLUGIN_OPTION_DISPLAY_POSITION ); ?>">
				<option value="after_content" <?php selected( $value, 'after_content' ); ?>>
					<?php esc_html_e( 'After Content', 'faq-plugin' ); ?>
				</option>
				<option value="shortcode" <?php selected( $value, 'shortcode' ); ?>>
					<?php esc_html_e( 'Shortcode Only', 'faq-plugin' ); ?>
				</option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Choose how FAQs render by default. Shortcode: use [faq_accordion].', 'faq-plugin' ); ?>
			</p>
			<?php
		}

		/**
		 * Render delete data field.
		 *
		 * @return void
		 */
		public function render_delete_data_field() {
			$value = (int) get_option( FAQ_PLUGIN_OPTION_DELETE_DATA, 0 );
			?>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( FAQ_PLUGIN_OPTION_DELETE_DATA ); ?>" value="1" <?php checked( $value, 1 ); ?> />
				<?php esc_html_e( 'Remove plugin data (settings + FAQ meta) when uninstalling.', 'faq-plugin' ); ?>
			</label>
			<?php
		}

		/**
		 * Render first item open field.
		 */
		public function render_first_item_open_field() {
			$value = (int) get_option( FAQ_PLUGIN_OPTION_FIRST_ITEM_OPEN, 1 );
			?>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( FAQ_PLUGIN_OPTION_FIRST_ITEM_OPEN ); ?>" value="1" <?php checked( $value, 1 ); ?> />
				<?php esc_html_e( 'Open the first accordion item on page load.', 'faq-plugin' ); ?>
			</label>
			<?php
		}

		/**
		 * Render Q & arrow color field.
		 */
		public function render_q_icon_color_field() {
			$value = get_option( FAQ_PLUGIN_OPTION_Q_ICON_COLOR, '#6366F1' );
			?>
			<input type="color" name="<?php echo esc_attr( FAQ_PLUGIN_OPTION_Q_ICON_COLOR ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<p class="description">
				<?php esc_html_e( 'Sets the color for the "Q" badge and arrow icon.', 'faq-plugin' ); ?>
			</p>
			<?php
		}

		/**
		 * Render Q background color field.
		 */
		public function render_q_bg_color_field() {
			$value = get_option( FAQ_PLUGIN_OPTION_Q_BG_COLOR, '#E0E7FF' );
			?>
			<input type="color" name="<?php echo esc_attr( FAQ_PLUGIN_OPTION_Q_BG_COLOR ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<p class="description">
				<?php esc_html_e( 'Sets the background color for the "Q" badge.', 'faq-plugin' ); ?>
			</p>
			<?php
		}

		/**
		 * Render settings page.
		 *
		 * @return void
		 */
		public function render_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'FAQ Settings', 'faq-plugin' ); ?></h1>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'faq_plugin_settings' );
					do_settings_sections( 'faq-plugin' );
					submit_button();
					?>
				</form>
			</div>
			<?php
		}

		/**
		 * Register meta box.
		 *
		 * @return void
		 */
		public function register_meta_box() {
			add_meta_box(
				'faq_plugin_meta_box',
				__( 'FAQ Entries', 'faq-plugin' ),
				array( $this, 'render_meta_box' ),
				'post',
				'normal',
				'default'
			);
		}

		/**
		 * Render meta box HTML.
		 *
		 * @param WP_Post $post Post object.
		 * @return void
		 */
		public function render_meta_box( $post ) {
			wp_nonce_field( 'faq_plugin_save_meta', 'faq_plugin_meta_nonce' );
			$faqs = get_post_meta( $post->ID, FAQ_PLUGIN_META_KEY, true );

			if ( ! is_array( $faqs ) ) {
				$faqs = array();
			}
			?>
			<div id="faq-plugin-meta-wrap" class="faq-plugin-meta-wrap">
				<div class="faq-plugin-rows">
					<?php
					if ( empty( $faqs ) ) {
						$faqs[] = array(
							'question' => '',
							'answer'   => '',
						);
					}

					foreach ( $faqs as $index => $faq ) {
						$this->render_row( $index, $faq );
					}
					?>
				</div>
				<button type="button" class="button faq-plugin-add-row">
					<?php esc_html_e( 'Add FAQ', 'faq-plugin' ); ?>
				</button>
				<script type="text/html" id="tmpl-faq-plugin-row">
					<?php
						// Template with placeholders for replacement.
						$this->render_row(
							'{{index}}',
							array(
								'question' => '',
								'answer'   => '',
							),
							true
						);
					?>
				</script>
			</div>
			<?php
		}

		/**
		 * Render single row.
		 *
		 * @param int|string $index Index.
		 * @param array      $faq   FAQ data.
		 * @param bool       $is_template Is template output.
		 * @return void
		 */
		private function render_row( $index, $faq, $is_template = false ) {
			$question   = isset( $faq['question'] ) ? $faq['question'] : '';
			$answer     = isset( $faq['answer'] ) ? $faq['answer'] : '';
			$index_attr = $is_template ? '{{{ data.index }}}' : esc_attr( $index );
			?>
			<div class="faq-plugin-row" data-index="<?php echo esc_attr( $index_attr ); ?>">
				<p>
					<label>
						<?php esc_html_e( 'Question', 'faq-plugin' ); ?>
						<input type="text" class="widefat" name="faq_plugin_faqs[<?php echo esc_attr( $index_attr ); ?>][question]" value="<?php echo esc_attr( $question ); ?>" />
					</label>
				</p>
				<p>
					<label>
						<?php esc_html_e( 'Answer', 'faq-plugin' ); ?>
						<textarea class="widefat" rows="4" name="faq_plugin_faqs[<?php echo esc_attr( $index_attr ); ?>][answer]"><?php echo esc_textarea( $answer ); ?></textarea>
					</label>
				</p>
				<p class="faq-plugin-row-actions">
					<button type="button" class="button faq-plugin-move-up"><?php esc_html_e( 'Move Up', 'faq-plugin' ); ?></button>
					<button type="button" class="button faq-plugin-move-down"><?php esc_html_e( 'Move Down', 'faq-plugin' ); ?></button>
					<button type="button" class="button button-link-delete faq-plugin-remove-row"><?php esc_html_e( 'Remove', 'faq-plugin' ); ?></button>
				</p>
				<hr />
			</div>
			<?php
		}

		/**
		 * Save meta box data.
		 *
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 * @return void
		 */
		public function save_meta_box( $post_id, $post ) {
			if ( ! isset( $_POST['faq_plugin_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['faq_plugin_meta_nonce'] ) ), 'faq_plugin_save_meta' ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( 'revision' === $post->post_type ) {
				return;
			}

			if ( 'post' !== $post->post_type ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			if ( ! isset( $_POST['faq_plugin_faqs'] ) || ! is_array( $_POST['faq_plugin_faqs'] ) ) {
				delete_post_meta( $post_id, FAQ_PLUGIN_META_KEY );
				return;
			}

			$raw_faqs = wp_unslash( $_POST['faq_plugin_faqs'] );
			$faqs     = array();

			foreach ( $raw_faqs as $raw_faq ) {
				if ( empty( $raw_faq['question'] ) && empty( $raw_faq['answer'] ) ) {
					continue;
				}

				$question = isset( $raw_faq['question'] ) ? sanitize_text_field( $raw_faq['question'] ) : '';
				$answer   = isset( $raw_faq['answer'] ) ? wp_kses_post( $raw_faq['answer'] ) : '';

				if ( '' === $question && '' === $answer ) {
					continue;
				}

				$faqs[] = array(
					'question' => $question,
					'answer'   => $answer,
				);
			}

			if ( empty( $faqs ) ) {
				delete_post_meta( $post_id, FAQ_PLUGIN_META_KEY );
				return;
			}

			update_post_meta( $post_id, FAQ_PLUGIN_META_KEY, $faqs );
		}

		/**
		 * Enqueue admin assets on edit screens.
		 *
		 * @param string $hook Current admin page hook.
		 * @return void
		 */
		public function enqueue_admin_assets( $hook ) {
			if ( ! in_array( $hook, array( 'post-new.php', 'post.php' ), true ) ) {
				return;
			}

			$screen = get_current_screen();
			if ( empty( $screen ) || 'post' !== $screen->post_type ) {
				return;
			}

			wp_enqueue_style(
				'faq-plugin-admin',
				FAQ_PLUGIN_URL . 'assets/css/faq-admin.css',
				array(),
				FAQ_PLUGIN_VERSION
			);

			wp_enqueue_script(
				'faq-plugin-admin',
				FAQ_PLUGIN_URL . 'assets/js/faq-admin.js',
				array( 'jquery', 'wp-util' ),
				FAQ_PLUGIN_VERSION,
				true
			);
		}

		/**
		 * Ensure assets load in block editor.
		 *
		 * @return void
		 */
		public function enqueue_block_editor_assets() {
			$this->enqueue_admin_assets( 'post.php' );
		}
	}
}
