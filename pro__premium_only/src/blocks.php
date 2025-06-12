<?php
/**
 * Premium Blocks Loader
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since 	3.0.0
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'stackable_register_blocks_premium' ) ) {
	function stackable_register_blocks_premium() {
		$blocks_array = apply_filters( 'stackable.blocks-premium', array() );

		$registry = WP_Block_Type_Registry::get_instance();
		foreach ( $blocks_array as $name => $metadata ) {
			if ( $registry->is_registered( $name ) ) {
				$registry->unregister( $name );
			}

			$register_options = apply_filters( 'stackable.register-blocks.options',
				// This automatically enqueues all our styles and scripts.
				array(
					'style' => 'ugb-style-css-premium', // Frontend styles.
					'script' => 'ugb-block-frontend-js-premium', // Frontend scripts.
					'editor_script' => 'ugb-block-js-premium', // Editor scripts.
					'editor_style' => 'ugb-block-editor-css-premium', // Editor styles.
				),
				$metadata['name'],
				$metadata
			);

			$block_args = array_merge( $metadata, $register_options );
			register_block_type( $metadata['name'], $block_args );
		}
	}
	add_action( 'init', 'stackable_register_blocks_premium' );
}
