<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'stackable_register_global_custom_color_schemes' ) ) {

	// Register the settings we need for global custom color schemes.
	function stackable_register_global_custom_color_schemes() {
		$string_properties = Stackable_Global_Settings::get_string_properties();

		register_setting(
			'stackable_global_settings',
			'stackable_global_custom_color_schemes',
			array(
				'type' => 'array',
				'description' => __( 'Stackable Global Color Schemes', STACKABLE_I18N ),
				'sanitize_callback' => array( 'Stackable_Global_Color_Schemes', 'sanitize_array_setting' ),
				'show_in_rest' => array(
					'schema' => array(
						'items' => array(
							'type'=>'object',
							'properties'=> array(
								'name' => array( 'type' => 'string' ),
								'key' => array( 'type' => 'string' ),
								'colorScheme' => array(
									'type' => 'object',
									'properties' => Stackable_Global_Color_Schemes::get_color_scheme_properties( $string_properties )
								),
								'hideInPicker' => array( 'type' => 'boolean' )
							)
						)
					)
				),
				'default' => '',
			)
		);
	}

	add_action( 'register_stackable_global_settings', 'stackable_register_global_custom_color_schemes' );
}

if ( ! function_exists( 'stackable_get_custom_color_schemes' ) ) {

	// returns the color schemes, including the custom color schemes if any
	function stackable_get_custom_color_schemes( $color_schemes ) {
		$custom_color_schemes = get_option( 'stackable_global_custom_color_schemes' );

		if ( $custom_color_schemes && is_array( $custom_color_schemes ) ) {
			return array_merge( $color_schemes, $custom_color_schemes );
		}

		return $color_schemes;
	}

	add_filter( 'stackable_global_color_schemes/get_color_schemes', 'stackable_get_custom_color_schemes' );
}
