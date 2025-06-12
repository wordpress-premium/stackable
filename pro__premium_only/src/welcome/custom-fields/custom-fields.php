<?php
/**
 * Main File for custom fields admin page
 *
 * @author Carlo Acosta
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists('Stackable_Custom_Fields_Admin_Page' ) ){
	/**
	 * Class for custom fields admin page
	 */
	class Stackable_Custom_Fields_Admin_Page {
		function __construct() {
			// Add settings to handle custom fields
			add_action( 'admin_init', array( $this, 'register_custom_fields_settings' ) );
			add_action( 'rest_api_init', array( $this, 'register_custom_fields_settings' ) );

				// Only initialize if enabled
			if ( sugb_fs()->can_use_premium_code() && stackable_is_custom_fields_enabled() ) {

				// Add admin menu
				add_action( 'admin_menu', array($this, 'add_custom_fields_admin') );

				// Add Javascript & CSS files
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_custom_fields_script' ) );

				// Add action for ajax call to save custom fields
				add_action( 'wp_ajax_stackable_save_custom_fields_ajax', array( $this, 'save_custom_fields_ajax' ) );
			}
		}

		/**
		 * Register the settings where we store custom fields values
		 *
		 * @return void
		 */
		public function register_custom_fields_settings() {
			register_setting(
				'stackable_custom_fields_settings',
				'stackable_custom_fields',
				array(
					'type' => 'array',
					'description' => __( 'Data from Stackable custom fields', STACKABLE_I18N ),
					'sanitize_callback' => array( $this, 'sanitize_array_field' ),
					'show_in_rest' => array(
						'schema' => array(
							'type' => 'array',
							'items' => array(
								'type' => 'object',
								'properties'=> array(
									'type' => array(
										'type' => 'string',
									),
									'name' => array(
										'type' => 'string',
									),
									'slug' => array(
										'type' => 'string',
									),
									'description' => array(
										'type' => 'string',
									),
									'value' => array(
										'type' => array( 'string', 'object' ),
									),
								),
							),
						),
					),
					'default' => array(),
				)
			);
		}

		/**
		 * Add admin page and admin panel for custom fields
		 *
		 * @return void
		 */
		public function add_custom_fields_admin() {
			// Only load if user has the permissions
			add_menu_page(
				__( 'Stackable Custom Fields', STACKABLE_I18N ), // Title of Page
				__( 'Fields', STACKABLE_I18N ), // Menu Title
				'manage_stackable_custom_fields', // Permissions
				'stk-custom-fields', // Slug
				array( $this, 'create_custom_fields_page' ), // Callback Function
				'dashicons-list-view', // Icon Path
				76 // Position
			);
		}

		/**
		 * Enqueue scripts and styles for custom fields page
		 *
		 * @return void
		 */
		public function enqueue_custom_fields_script() {
			// Only load scripts if in Custom Fields page
			$screen = get_current_screen();
			if ( $screen->base !== 'toplevel_page_stk-custom-fields' ) {
				return;
			}

			wp_enqueue_script( 'stackable-custom-fields-premium', plugins_url( 'dist/admin_custom_fields__premium_only.js', STACKABLE_FILE ), array( 'wp-i18n', 'wp-element', 'wp-hooks', 'wp-util', 'wp-components' ), STACKABLE_VERSION );
			wp_enqueue_style( 'stackable-custom-fields-premium', plugins_url( 'dist/admin_welcome.css', STACKABLE_FILE ) );

			// Translations.
			wp_set_script_translations( 'stackable-custom-fields-premium', STACKABLE_I18N );
			stackable_load_js_translations(); // This is needed for the translation strings to be loaded.

			// Add dependencies for scripts
			$args = apply_filters( 'stackable_localize_settings_script', array(
				'i18n' => STACKABLE_I18N,
				'isPro' => sugb_fs()->can_use_premium_code(),
				'initialCustomFields' => get_option( 'stackable_custom_fields' ),
				'adminPermission' => current_user_can( 'manage_options' ),
			) );
			wp_localize_script( 'stackable-custom-fields-premium', 'stackable', $args );
		}

		/**
		 * Create custom fields admin page content
		 *
		 * @return void
		 */
		public function create_custom_fields_page() {
			$screen = get_current_screen();
			?>
            <div class="wrap">
				<div class="s-header-wrap">
					<?php Stackable_Welcome_Screen::print_header( __( 'Stackable Custom Fields', STACKABLE_I18N ), 'icon' ) ?>
					<?php if ( current_user_can( 'manage_options' ) ) {
						echo Stackable_Welcome_Screen::print_tabs();
					} ?>
				</div>
				<h1 aria-hidden="true" class="s-admin-notice-marker"></h1>
				<section class="s-body-container s-body-container-grid">
                    <div class="s-body">
						<article class="s-box" id="custom fields">
							<h2><?php _e( '📋 Custom Fields', STACKABLE_I18N ) ?></h2>
							<?php if ( sugb_fs()->can_use_premium_code() ) : ?>
								<p class="s-settings-subtitle">
									<?php
										printf(
											__( 'You can add small pieces of content here which you can use across your website - things like your contact email or the number of customers you\'ve served. You can find these fields under the "Site source" area when placing "Dynamic Content" in your blocks. %sLearn more%s.' , STACKABLE_I18N ),
											'<a href="https://docs.wpstackable.com/article/463-how-to-use-stackable-custom-fields/?utm_source=wp-custom-fields-manager&utm_campaign=learnmore&utm_medium=wp-dashboard" target="_docs">',
											'</a>'
										);
									?>
								</p>
							<?php endif; ?>
							<div class="s-custom-fields"></div>
							<?php if ( ! sugb_fs()->can_use_premium_code() ) : ?>
								<p class="s-settings-pro"><?php _e( 'This is only available in Stackable Premium.', STACKABLE_I18N ) ?> <a href="https://wpstackable.com/premium/?utm_source=wp-custom-fields-manager&utm_campaign=gopremium&utm_medium=wp-dashboard" target="_premium"><?php _e( 'Go Premium', STACKABLE_I18N ) ?></a></p>
							<?php endif; ?>
						</article>
                    </div>
                </section>
            </div>
            <?php
		}

		/**
		 * Function called by ajax to save custom fields
		 *
		 * @return void
		 */
		public function save_custom_fields_ajax() {
			$custom_fields = json_decode( stripslashes( $_POST[ 'custom_fields' ] ), true );
			if ( current_user_can( 'manage_stackable_custom_fields' ) || current_user_can( 'manage_options' ) ) {
				update_option( 'stackable_custom_fields', $custom_fields, 'no' );
				wp_send_json_success();
			}
			wp_send_json_error();
		}

		public function sanitize_array_field( $input ) {
			return ! is_array( $input ) ? array( array() ) : $input;
		}
	}

	new Stackable_Custom_Fields_Admin_Page();
}
