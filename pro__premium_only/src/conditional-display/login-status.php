<?php
/**
 * Conditional logic of the condition type Login status.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'stackable_conditional_display/login-status', function( $condition_is_met, $condition, $block_content, $block ) {
    $options = isset( $condition['options'] ) ? $condition['options'] : null;

    if ( $options ) {
        $status = $options['status'];

        if ( $status === 'logged-in' ) {
			return is_user_logged_in();
        } else if ( $status === 'logged-out' ) {
			return ! is_user_logged_in();
        }
    }

	return $condition_is_met;
}, 10, 5 );
