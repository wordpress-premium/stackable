<?php
/**
 * Loads the Custom Fields default settings
 *
 * @author Carlo Acosta
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'stackable_get_custom_fields_settings' ) ) {
	/**
	 * Retrieves admin settings for custom fields from database
	 *
	 * @return array
	 */
	function stackable_get_custom_fields_settings() {
		$options = get_option( 'stackable_custom_fields_admin' );
		if ( $options === false ) {
			return array(
				'manager' => array( 'administrator' ),
				'enabled' => true,
			);
		}
		return $options;
	}
}

if ( ! function_exists( 'stackable_is_custom_fields_enabled' ) ) {
	/**
	 * Returns whether custom fields should be enabled
	 *
	 * @return boolean
	 */
	function stackable_is_custom_fields_enabled() {
		return sugb_fs()->can_use_premium_code() && stackable_get_custom_fields_settings()['enabled'];
	}
}

if ( ! function_exists( 'stackable_add_custom_fields_admin_cap' ) ) {
	/**
	 * Adds the default capability for the administrator for Custom Fields
	 *
	 * @return void
	 */
	function stackable_add_custom_fields_admin_cap() {
		$options = get_option( 'stackable_custom_fields_admin' );
		if ( $options === false ) {
			$role = get_role( 'administrator' );
			if ( ! $role->has_cap( 'manage_stackable_custom_fields' ) ) {
				$role->add_cap( 'manage_stackable_custom_fields' );
			}
		}
	}
	add_action( 'admin_init', 'stackable_add_custom_fields_admin_cap' );
}

if ( ! class_exists( 'Stackable_Premium_Settings_Custom_Fields' ) ) {

	/**
	 * Premium settings for custom fields
	 */
    class Stackable_Premium_Settings_Custom_Fields {
		/**
		 * Initialize
		 */
        function __construct() {
			if ( sugb_fs()->can_use_premium_code() ) {
				// Register our settings.
				add_action( 'admin_init', array( $this, 'register_custom_fields_settings_admin' ) );
				add_action( 'rest_api_init', array( $this, 'register_custom_fields_settings_admin' ) );
			}
		}

		/**
		 * Register the settings we need to load icons
		 *
		 * @return void
		 */
		public function register_custom_fields_settings_admin() {
			register_setting(
				'stackable_custom_fields_settings_admin',
				'stackable_custom_fields_admin',
				array(
					'type' => 'object',
					'description' => __( 'Settings that control custom fields functionality and permissions.', STACKABLE_I18N ),
					'sanitize_callback' => array( $this, 'sanitize_custom_fields_admin' ),
					'show_in_rest' => array(
						'schema' => array(
							'type' => 'object',
							'properties'=> array(
								'enabled' => array(
									'type' => 'boolean'
								),
								'manager' => array(
									'type'  => 'array',
									'items' => array(
										'type' => 'string',
									),
								)
							)
						),
					),
					'default' => array(
						'manager' => array( 'administrator' ),
						'enabled' => true,
					),
				)
			);
		}

		public function sanitize_custom_fields_admin( $input ) {
			$sanitized_array = ! is_array( $input ) ? array() : $input;

			if ( empty( $sanitized_array ) ) {
				return $sanitized_array;
			}

			// Update role's manager capabilities
			$managers = array();
			if ( array_key_exists( 'manager' , $sanitized_array ) ) {
				$managers = $sanitized_array[ 'manager' ];
			}
			$roles_obj = new WP_Roles();
			foreach ( $roles_obj->roles as $role => $role_data ) {
				$current_role = get_role( $role );
				if( in_array( $role, $managers ) ) {
					$current_role->add_cap( 'manage_stackable_custom_fields' );
				}
				else {
					$current_role->remove_cap( 'manage_stackable_custom_fields' );
				}
			}

			return $sanitized_array;
		}
	}

	new Stackable_Premium_Settings_Custom_Fields();
}
