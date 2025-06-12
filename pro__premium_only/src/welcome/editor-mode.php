<?php
/**
 * Loads the Font Editor Mode
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Stackable_Premium_Settings_Editor_Mode' ) ) {

	/**
	 * Premium icon settings page.
	 */
    class Stackable_Premium_Settings_Editor_Mode {

		/**
		 * Initialize
		 */
        function __construct() {
			if ( sugb_fs()->can_use_premium_code() ) {
				// Add our settings JS variables.
				add_action( 'stackable_localize_settings_script', array( $this, 'localize_settings' ) );
			}
		}

		/**
		 * Add the JS variables needed by our icon settings.
		 *
		 * @param array $args
		 * @return array
		 */
		public function localize_settings( $args ) {

			$roles_obj = new WP_Roles();
			$roles = array();
			foreach ( $roles_obj->roles as $role => $role_data ) {
				$roles[ $role ] = translate_user_role( $role_data['name'] );
			}

			$settings = get_option( 'stackable_editor_roles_content_only' );

			return array_merge( $args, array(
				'editorRoles' => $roles,
				'editorRoleSettings' => $settings ? $settings : array(),
			) );
			return $args;
		}
	}

	new Stackable_Premium_Settings_Editor_Mode();
}
