<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'stackable_register_global_custom_preset_controls' ) ) {
	// Register the settings we need for global custom preset controls.
	function stackable_register_global_custom_preset_controls() {
        $item_schema = array(
            'type'       => 'object',
            'properties' => array(
                'name' => array(
                    'type' => 'string',
                ),
                'size' => array(
                    'type' => 'string',
                ),
                'slug' => array(
                    'type' => 'string',
                ),
                'isDiscarded' => array(
                    'type' => 'boolean'
                ),
            ),
        );

		register_setting(
			'stackable_global_settings',
			'stackable_global_custom_preset_controls',
			array(
                'type' => 'object',
                'description' => __( 'Stackable Custom Preset Controls', STACKABLE_I18N ),
                'sanitize_callback' => array( 'Stackable_Size_And_Spacing_Preset_Controls', 'sanitize_array_setting' ),
                'show_in_rest' => array(
                    'schema' => array(
                        'type' => 'object',
                        'properties' => array(
                            'fontSizes' => array(
                                'type'  => 'array',
                                'items' => $item_schema,
                            ),
                            'spacingSizes' => array(
                                'type'  => 'array',
                                'items' => $item_schema,
                            ),
                            'blockHeights' => array(
                                'type'  => 'array',
                                'items' => $item_schema,
                            ),
                            'borderRadius' => array(
                                'type'  => 'array',
                                'items' => $item_schema,
                            ),
                        ),
                    ),
                ),
				'default' => '',
			)
		);
	}

	add_action( 'register_stackable_global_settings', 'stackable_register_global_custom_preset_controls' );
}
