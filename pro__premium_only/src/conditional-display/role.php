<?php
/**
 * Conditional logic of the condition type User role.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'stackable_conditional_display/role', function( $condition_is_met, $condition, $block_content, $block ) {
    $options = isset( $condition['options'] ) ? $condition['options'] : null;

    if ( $options ) {
        $selected_values = $options['selectedValues'];

        if ( $selected_values ) {
            if ( is_user_logged_in() ) {
                // Get the roles of the current user
                $current_user = wp_get_current_user();
                $user_roles = $current_user->roles;

                // Convert selectedValues to lowercase to compare with the user role
                $values = array_map( 'strtolower', $selected_values );
                $matched_roles = array_intersect( $user_roles, $values );

                $condition_is_met = ! empty( $matched_roles );
            } else {
                // For non logged in users
				return false;
            }
        }
    }

	return $condition_is_met;
}, 10, 5 );


if ( ! function_exists( 'stackable_get_role_names' ) ) {
	function stackable_get_role_names() {
		global $wp_roles;
		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();
		return $wp_roles->get_names();
	}
}


if ( ! function_exists( 'stackable_register_get_all_roles_endpoint' ) ) {
	function stackable_register_get_all_roles_endpoint() {
		register_rest_route( 'stackable/v3', '/get_roles/', array(
			'methods' => 'GET',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'callback' => 'stackable_get_role_names'
		) );
	}
	add_action( 'rest_api_init', 'stackable_register_get_all_roles_endpoint', 2, 0 );
}
