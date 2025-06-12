<?php
/**
 * Effects and Animations.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Stackable_Effects_Animations' ) ) {

	/**
	 * Stackable Effects and Animations
	 */
    class Stackable_Effects_Animations {

		/**
		 * Initialize
		 */
        function __construct() {
			// Load the scripts only when Stackable effects are detected.
			if ( ! is_admin() ) {
				add_filter( 'render_block', array( $this, 'load_frontend_scripts_conditionally' ), 10, 2 );
			}
		}

		/**
		 * Load the scripts only when Stackable effects are detected.
		 *
		 * @param String $block_content
		 * @param Array $block
		 *
		 * @return void
		 *
		 * @since 3.0.0
		 */
		public function load_frontend_scripts_conditionally( $block_content, $block ) {
			if ( ! isset( $block['blockName'] ) || strpos( $block['blockName'], 'stackable/' ) === false ) {
				return $block_content;
			}

			if ( strpos( $block_content, '--entrance-' ) !== false || // Entrance animations
					strpos( $block_content, 'stk-anim' ) !== false || // Scroll animations
					strpos( $block_content, '--stk-tran' ) !== false || // Transition duration
					strpos( $block_content, 'stk-entrance' ) !== false // Entrance class
			) {
				wp_enqueue_script(
					'ugb-block-frontend-js-effect-premium',
					plugins_url( 'dist/frontend_effects__premium_only.js', STACKABLE_FILE ),
					array(),
					STACKABLE_VERSION
				);

				// Remove this listener.
				remove_filter( 'render_block', array( $this, 'load_frontend_scripts_conditionally' ), 10, 2 );
			}

			return $block_content;
		}
	}

	new Stackable_Effects_Animations();
}
