<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'stackable_premium_block_assets' ) ) {

	/**
	* Register block assets for frontend.
	*
	* @since 0.1
	*/
	function stackable_premium_block_assets() {
		wp_register_style(
			'ugb-style-css-premium',
			plugins_url( 'dist/frontend_blocks__premium_only.css', STACKABLE_FILE ),
			array( 'ugb-style-css' ),
			STACKABLE_VERSION
		);

		if ( ! is_admin() ) {
			// Comment this out for now since we aren't using any JS in premium for now.
			// wp_register_script(
			// 	'ugb-block-frontend-js-premium',
			// 	plugins_url( 'dist/frontend_blocks__premium_only.js', STACKABLE_FILE ),
			// 	array( 'ugb-block-frontend-js' ),
			// 	STACKABLE_VERSION
			// );
		}
	}
	add_action( 'stackable_block_enqueue_frontend_assets', 'stackable_premium_block_assets' );
}

if ( ! function_exists( 'stackable_premium_block_editor_assets' ) ) {

	/**
	 * Register block assets for backend editor.
	 *
	 * @since 0.1
	 */
	function stackable_premium_block_editor_assets() {
		// Register block assets for the backend.
		stackable_premium_block_assets();

		// This should enqueue BEFORE the main Stackable block script.
		wp_register_script(
			'ugb-block-js-premium',
			plugins_url( 'dist/editor_blocks__premium_only.js', STACKABLE_FILE ),
			array( 'ugb-stk' ), // The window.stk API.
			STACKABLE_VERSION
		);

		// Add translations.
		wp_set_script_translations( 'ugb-block-js-premium', STACKABLE_I18N );

		wp_register_style(
			'ugb-block-editor-css-premium',
			plugins_url( 'dist/editor_blocks__premium_only.css', STACKABLE_FILE ),
			array( 'ugb-block-editor-css' ),
			STACKABLE_VERSION
		);
	}

	if ( is_admin() ) {
		add_action( 'enqueue_block_editor_assets', 'stackable_premium_block_editor_assets' );
	}
}

/**
 * Load the premium block assets, they will load the free version as dependencies.
 *
 * @since 2.17.4
 */
if ( ! function_exists( 'stackable_premium_enqueue_scripts' ) ) {
	function stackable_premium_enqueue_scripts( $options, $block_name, $meta_data ) {
		$options['style'] = 'ugb-style-css-premium'; // Frontend styles.
		$options['script'] = 'ugb-block-frontend-js-premium'; // Frontend scripts.
		$options['editor_style'] = 'ugb-block-editor-css-premium'; // Editor styles.
		return $options;
	}
	add_filter( 'stackable.register-blocks.options', 'stackable_premium_enqueue_scripts', 10, 3 );
}

/**
 * Load the premium editor script before the free one since the premium one adds hooks.
 *
 * @since 2.17.4
 */
if ( ! function_exists( 'stackable_premium_editor_enqueue_script' ) ) {
	function stackable_premium_editor_enqueue_script( $dependencies ) {
		$dependencies[] = 'ugb-block-js-premium';
		return $dependencies;
	}
	add_filter( 'stackable_editor_js_dependencies', 'stackable_premium_editor_enqueue_script' );
}

