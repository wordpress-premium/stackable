<?php
/**
 * Conditional logic of the condition type Post IDs.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'stackable_conditional_display/post-id', function( $condition_is_met, $condition, $block_content, $block ) {
    $options = isset( $condition['options'] ) ? $condition['options'] : null;

    if ( $options ) {
        $selected_values = $options['selectedValues'];

        if ( $selected_values ) {
            $values = array_map( 'intval', $selected_values );
            return in_array( get_the_ID(), $values );
        }
    }

	return 	$condition_is_met;
}, 10, 5 );
