<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'stackable_auto_compatibility_v2' ) ) {

	/**
	 * When upgrading from v2 to v3, turn on v2 compatibility, and let the user know about how to turn it off.
	 *
	 * @since 3.0.0
	 */
	function stackable_auto_compatibility_v2( $old_version, $new_version ) {
		if ( ! empty( $old_version ) && version_compare( $old_version, "3.0", "<" ) ) {
			// Only do this if we have never set the compatibility options before.
			if ( get_option( 'stackable_v2_editor_compatibility' ) === false &&
				get_option( 'stackable_v2_editor_compatibility_usage' ) === false
			) {
				update_option( 'stackable_v2_editor_compatibility_usage', '1', 'no' ); // Load version 2 blocks in the editor
				update_option( 'stackable_v2_disabled_blocks', get_option( 'stackable_disabled_blocks' ), 'no' ); // Migrate the disabled blocks.
			}

			// Always enable frontend compatibility when updating so that the frontend will always look okay.
			update_option( 'stackable_v2_frontend_compatibility', '1' ); // Load version 2 blocks in the editor
		}
	}
	add_action( 'stackable_version_upgraded', 'stackable_auto_compatibility_v2', 10, 2 );
}

if ( ! function_exists( 'has_stackable_v2_frontend_compatibility' ) ) {

	/**
	 * Should we load v2 frontend
	 *
	 * @return Boolean
	 *
	 * @since 3.0.0
	 */
	function has_stackable_v2_frontend_compatibility() {
		// In case the plugin was auto-updated from v2 to v3, run auto-compatibility cehck.
		if ( ! is_admin() && get_option( 'stackable_current_version_installed' ) !== STACKABLE_VERSION ) {
			stackable_auto_compatibility_v2( get_option( 'stackable_current_version_installed' ), STACKABLE_VERSION );
		}
		return get_option( 'stackable_v2_frontend_compatibility' ) === '1';
	}

	/**
	 * Should we load v2 blocks
	 *
	 * @return Boolean
	 *
	 * @since 3.0.0
	 */
	function has_stackable_v2_editor_compatibility() {
		return get_option( 'stackable_v2_editor_compatibility' ) === '1' || get_option( 'stackable_v2_editor_compatibility_usage' ) === '1';
	}

	/**
	 * Should we load v2 blocks only when they are used in the editor
	 *
	 * @return Boolean
	 *
	 * @since 3.0.0
	 */
	function has_stackable_v2_editor_compatibility_usage() {
		return get_option( 'stackable_v2_editor_compatibility_usage' ) === '1';
	}
}

if ( ! function_exists( 'stackable_premium_block_assets_v2' ) ) {

	/**
	* Enqueue block assets for both frontend + backend.
	*
	* @since 0.1
	*/
	function stackable_premium_block_assets_v2() {
		wp_register_style(
			'ugb-style-css-premium-v2',
			plugins_url( 'dist/deprecated/frontend_blocks_deprecated_v2__premium_only.css', STACKABLE_FILE ),
			array( 'ugb-style-css-v2' ),
			STACKABLE_VERSION
		);

		if ( ! is_admin() ) {
			wp_register_script(
				'ugb-block-frontend-js-premium-v2',
				plugins_url( 'dist/deprecated/frontend_blocks_deprecated_v2__premium_only.js', STACKABLE_FILE ),
				array( 'ugb-block-frontend-js-v2'),
				STACKABLE_VERSION
			);
		}
	}

	if ( has_stackable_v2_frontend_compatibility() || has_stackable_v2_editor_compatibility() ) {
		add_action( 'init', 'stackable_premium_block_assets_v2' );
	}
}

if ( ! function_exists( 'stackable_premium_block_editor_assets_v2' ) ) {

	/**
	 * Enqueue block assets for backend editor.
	 *
	 * @since 0.1
	 */
	function stackable_premium_block_editor_assets_v2() {
		// This should enqueue BEFORE the main Stackable block script.
		wp_register_script(
			'ugb-block-js-premium-v2',
			plugins_url( 'dist/deprecated/editor_blocks_deprecated_v2__premium_only.js', STACKABLE_FILE ),
			array(),
			STACKABLE_VERSION
		);

		// Add translations.
		wp_set_script_translations( 'ugb-block-js-premium-v2', STACKABLE_I18N );

		wp_register_style(
			'ugb-block-editor-css-premium-v2',
			plugins_url( 'dist/deprecated/editor_blocks_deprecated_v2__premium_only.css', STACKABLE_FILE ),
			array( 'ugb-block-editor-css-v2' ),
			STACKABLE_VERSION
		);
	}

	if ( is_admin() ) {
		if ( has_stackable_v2_frontend_compatibility() || has_stackable_v2_editor_compatibility() ) {
			add_action( 'init', 'stackable_premium_block_editor_assets_v2' );
		}
	}
}

require_once( plugin_dir_path( __FILE__ ) . 'block/blog-posts/index.php' );

/**
 * Load the premium block assets, they will load the free version as dependencies.
 *
 * @since 3.0.0
 */
if ( ! function_exists( 'stackable_premium_enqueue_scripts_v2' ) ) {
	function stackable_premium_enqueue_scripts_v2( $options, $block_name, $meta_data ) {
		$options['style'] = 'ugb-style-css-premium-v2'; // Frontend styles.
		$options['script'] = 'ugb-block-frontend-js-premium-v2'; // Frontend scripts.
		$options['editor_style'] = 'ugb-block-editor-css-premium-v2'; // Editor styles.
		return $options;
	}

	if ( has_stackable_v2_frontend_compatibility() || has_stackable_v2_editor_compatibility() ) {
		add_filter( 'stackable.v2.register-blocks.options', 'stackable_premium_enqueue_scripts_v2', 10, 3 );
	}
}


/**
 * Load the premium editor script before the free one since the premium one adds hooks.
 *
 * @since 3.0.0
 */
if ( ! function_exists( 'stackable_premium_editor_enqueue_script_v2' ) ) {
	function stackable_premium_editor_enqueue_script_v2( $dependencies ) {
		$dependencies[] = 'ugb-block-js-premium-v2';
		return $dependencies;
	}

	if ( has_stackable_v2_frontend_compatibility() || has_stackable_v2_editor_compatibility() ) {
		add_filter( 'stackable_editor_js_dependencies_v2', 'stackable_premium_editor_enqueue_script_v2' );
	}
}
