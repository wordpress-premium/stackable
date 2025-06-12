<?php
/**
 * Conditional logic of the condition type Post type.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'stackable_conditional_display/post-type', function( $condition_is_met, $condition, $block_content, $block ) {
    $options = isset( $condition['options'] ) ? $condition['options'] : null;

    if ( $options ) {
        $selected_values = $options['selectedValues'];
        if ( $selected_values ) {
            // Post type of the current page
            $current_post_type = get_post_type( get_the_ID() );

			// If current page is in the selected post types
            if ( in_array( $current_post_type, array_map( 'strtolower', $selected_values ) ) ) {
				return true;
            }
        } else {
            return true;
        }
    }

	return $condition_is_met;
}, 10, 5 );
