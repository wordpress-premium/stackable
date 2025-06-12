<?php
/**
 * Loads the Font Awesome Premium Integration
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Stackable_Premium_Editor_Mode' ) ) {

	/**
	 * Our Premium Icons
	 */
    class Stackable_Premium_Editor_Mode {

		/**
		 * Initialize icons
		 */
        function __construct() {
			// Register our settings.
			add_action( 'admin_init', array( $this, 'register_editor_mode_settings' ) );
			add_action( 'rest_api_init', array( $this, 'register_editor_mode_settings' ) );

			// This is our setting that will be included in the editor.
			add_filter( 'stackable_editor_role_is_content_only', array( $this, 'editor_role_is_content_only' ) );
		}

		/**
		 * Checks whether the user is only allowed to edit content.
		 *
		 * @param Array $roles
		 * @return Array
		 */
		public function editor_role_is_content_only( $is_content_only ) {
			if ( ! is_user_logged_in() ) {
				return $is_content_only;
			}

			$editor_only_roles = get_option( 'stackable_editor_roles_content_only' );
			if ( ! $editor_only_roles ) {
				return $is_content_only;
			}

			$user = wp_get_current_user();
			foreach ( $user->roles as $user_role ) {
				if ( in_array( $user_role, $editor_only_roles ) ) {
					return true;
				}
			}

			return $is_content_only;
		}


		/**
		 * Register the settings we need to load icons
		 *
		 * @return void
		 */
		public function register_editor_mode_settings() {
			register_setting(
				'stackable_editor_roles',
				'stackable_editor_roles_content_only',
				array(
					'type' => 'array',
					'description' => __( 'Roles which only allow content only editing.', STACKABLE_I18N ),
					'sanitize_callback' => array( $this, 'sanitize_editor_role_setting' ),
					'show_in_rest' => array(
						'schema' => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'string',
							),
						),
					),
					'default' => '',
				)
			);
		}

		public function sanitize_editor_role_setting( $input ) {
			return ! is_array( $input ) ? array() : $input;
		}
	}

	new Stackable_Premium_Editor_Mode();
}
