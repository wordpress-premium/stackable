<?php
/**
 * Loads the Stackable Premium settings.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( plugin_dir_path( __FILE__ ) . 'icons.php' );
require_once( plugin_dir_path( __FILE__ ) . 'editor-mode.php' );
require_once( plugin_dir_path( __FILE__ ) . 'custom-fields/custom-fields.php' );

if ( ! class_exists( 'Stackable_Premium_Welcome_Screen' ) ) {

	/**
	 * Premium items in our welcome and settings pages.
	 */
    class Stackable_Premium_Welcome_Screen {

		/**
		 * Initialize
		 */
        function __construct() {
			add_action( 'stackable_settings_admin_enqueue_scripts', array( $this, 'enqueue_dashboard_script' ) );

			add_action( 'stackable_settings_admin_enqueue_styles', array( $this, 'enqueue_dashboard_style' ) );
		}

		/**
		 * Enqueue our Premium scripts
		 *
		 * @return void
		 */
		public function enqueue_dashboard_script() {
			// Enqueue our admin settings script.
			wp_enqueue_script( 'stackable-welcome-premium', plugins_url( 'dist/admin_welcome__premium_only.js', STACKABLE_FILE ), array( 'stackable-welcome', 'wp-editor' ), STACKABLE_VERSION );

			// Add translations.
			wp_set_script_translations( 'stackable-welcome-premium', STACKABLE_I18N );
		}

		/**
		 * Enqueue our Premium styles
		 *
		 * @return void
		 */
		public function enqueue_dashboard_style() {
			// Enqueue our admin settings styles.
			wp_enqueue_style( 'stackable-welcome-premium', plugins_url( 'dist/admin_welcome__premium_only.css', STACKABLE_FILE ), array( 'stackable-welcome' ) );
		}
	}

	new Stackable_Premium_Welcome_Screen();
}
