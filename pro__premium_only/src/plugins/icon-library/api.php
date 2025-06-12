<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Stackable_Icon_Library_API' ) ) {
	/**
	 * Stackable Icon Library API
	 */
    class Stackable_Icon_Library_API {

		/**
		 * Initialize
		 */
		function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_route' ) );

		}

		public function register_route() {
			register_rest_route( 'stackable/v3', '/get_icon_library', array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_icon_library' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			) );

			register_rest_route( 'stackable/v3', '/update_icon_library', array(
				'methods' => 'POST',
				'callback' => array( $this, 'update_icon_library' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args' => array(
					'name' => array(
						'validate_callback' => __CLASS__ . '::validate_string',
					),
					'key' => array(
						'validate_callback' => __CLASS__ . '::validate_string',
					),
					'icon' => array(
						'validate_callback' => __CLASS__ . '::validate_string',
					),
				),
			) );

			register_rest_route( 'stackable/v3', '/delete_from_icon_library', array(
				'methods' => 'POST',
				'callback' => array( $this, 'delete_from_icon_library' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args' => array(
					'key' => array(
						'validate_callback' => __CLASS__ . '::validate_string',
					),
				),
			) );

			register_rest_route( 'stackable/v3', '/sort_icon_library', array(
				'methods' => 'POST',
				'callback' => array( $this, 'sort_icon_library' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args' => array(
					'oldIndex' => array(
						'validate_callback' => __CLASS__ . '::validate_int',
					),
					'newIndex' => array(
						'validate_callback' => __CLASS__ . '::validate_int',
					),
				),
			) );

		}

		public static function validate_string( $value, $request, $param ) {
			if ( ! is_string( $value ) ) {
				return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be a string.', STACKABLE_I18N ), $param ) );
			}
			return true;
		}

		public static function validate_int( $value, $request, $param ) {
			if ( ! is_int( $value ) ) {
				return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be an integer.', STACKABLE_I18N ), $param ) );
			}
			return true;
		}

		/**
		 * Get all icons in the icon library.
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return void
		 */
		public function get_icon_library( $request ) {
			// Get all icons in the icon library.
			$icon_library = get_option( 'stackable_icon_library', array() );

			return new WP_REST_Response( $icon_library, 200 );
		}


		/**
		 * Adds or updates an icon in the icon library.
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return void
		 */
		public function update_icon_library( $request ) {
			$name = $request->get_param( 'name' );
			$key = $request->get_param( 'key' );
			$icon = $request->get_param( 'icon' );

			// All icons in the icon library.
			$icon_library = get_option( 'stackable_icon_library', array() );

			// Find the icon.
			$icon_index = false;
			foreach ( $icon_library as $i => $icon_data ) {
				if ( $icon_data['key'] === $key ) {
					$icon_index = $i;
					break;
				}
			}

			// Add icon if it doesn't exist.
			if ( $icon_index === false ) {
				$icon_data = array(
					'name' => $name,
					'key' => $key,
					'icon' => $icon
				);
				$icon_library[] = $icon_data;

			// Update icon if it exists.
			} else {
				$icon_library[ $icon_index ] = array(
					'name' => $name,
					'key' => $key,
					'icon' => $icon
				);
			}

			update_option( 'stackable_icon_library', $icon_library, 'no' );

			return new WP_REST_Response( $icon_library, 200 );
		}

		/**
		 * Deletes an icon from the icon library.
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return void
		 */
		public function delete_from_icon_library( $request ) {
			$key = $request->get_param( 'key' );

			// All icons in the icon library.
			$icon_library = get_option( 'stackable_icon_library', array() );

			// Find the icon.
			$icon_index = false;
			foreach ( $icon_library as $i => $icon_data ) {
				if ( $icon_data['key'] === $key ) {
					$icon_index = $i;
					break;
				}
			}

			// Delete icon if it exists.
			if ( $icon_index !== false ) {
				array_splice( $icon_library, $icon_index, 1 );
				update_option( 'stackable_icon_library', $icon_library, 'no' );
			}

			return new WP_REST_Response( $icon_library, 200 );
		}

		/**
		 * Sorts the icon library.
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return void
		 */
		public function sort_icon_library( $request ) {
			$old_index = $request->get_param( 'oldIndex' );
			$new_index = $request->get_param( 'newIndex' );

			// All icons in the icon library.
			$icon_library = get_option( 'stackable_icon_library', array() );

			$moved_icon = array_splice( $icon_library, $old_index, 1);
			array_splice( $icon_library, $new_index, 0, $moved_icon );

			update_option( 'stackable_icon_library', $icon_library, 'no' );

			return new WP_REST_Response( $icon_library, 200 );
		}

	}

	new Stackable_Icon_Library_API();
}
