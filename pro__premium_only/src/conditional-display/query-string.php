<?php
/**
 * Conditional logic of the condition type Query string.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'stackable_conditional_display/query-string', function( $condition_is_met, $condition, $block_content, $block ) {
    $options = isset( $condition['options'] ) ? $condition['options'] : null;

    if ( $options ) {
        $queries = isset( $options['queries'] ) ? $options['queries'] : null;

        if ( $queries ) {
            // Convert the queries into an array
            $query_arr = explode( "\n", $queries );

            $query_strings = array();
            foreach ( $query_arr as $query ) {
                parse_str( $query, $parsed_query );
                $query_strings = array_merge( $query_strings, $parsed_query );
            }

			foreach ( $query_strings as $param => $value ) {
				if ( isset( $_REQUEST[ $param ] ) ) { // phpcs:ignore
					// If param only or param=
					if ( ! $value ) {
						return true;
					} else if ( $_REQUEST[ $param ] === $value ) { // phpcs:ignore
						return true;
					}
				}
			}
        }
    }

	return $condition_is_met;
}, 10, 5 );
