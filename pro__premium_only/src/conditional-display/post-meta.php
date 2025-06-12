<?php
/**
 * Conditional logic of the condition type Post Meta Data.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'stackable_conditional_display/post-meta', function( $condition_is_met, $condition, $block_content, $block ) {
    $options = isset( $condition['options'] ) ? $condition['options'] : null;

    if ( $options ) {
        $key = isset( $options['key'] ) ? $options['key'] : '';
        $operator = isset( $options['operator'] ) ? $options['operator'] : '';
        $expected = isset( $options['expectedVal'] ) ? $options['expectedVal'] : '';

        $value = get_post_meta( get_the_ID(), $key, true );
		if ( $value === false ) {
			$value = '';
		}

		// If we somehow got an array, only check against the first value.
		if ( is_array( $value ) ) {
			if ( count( $value ) > 0 ) {
				$value = $value[0];
			} else {
				$value = '';
			}
		}

        if ( $key && $operator || $key && $operator && $expected ) {
            if ( $operator === 'equal' ) {
                return $expected === $value;
            }

            if ( $operator === 'not-equal' ) {
                return $expected !== $value;
            }

            if ( $operator === 'true' ) {
                // Falsy values should be considered.
                if ( strtolower( $value ) === 'false' ||
					 strtolower( $value ) === 'null' ||
					 strtolower( $value ) === 'undefined' ||
					 $value === 'NaN' ||
					 trim( $value ) === ''
				) {
					return false;
                }

				return !! $value;
            }

            if ( $operator === 'false' ) {
                // Check falsy values.
                if ( strtolower( $value ) === 'false' ||
					 strtolower( $value ) === 'null' ||
					 strtolower( $value ) === 'undefined' ||
					 $value === 'NaN' ||
					 trim( $value ) === ''
				) {
					return true;
				}

				return ! $value;
            }

            if ( $operator === 'less-than' ) {
                return $expected < $value;
            }
            if ( $operator === 'less-than-equal' ) {
                return $expected <= $value;
            }
            if ( $operator === 'greater-than' ) {
                return $expected > $value;
            }
            if ( $operator === 'greater-than-equal' ) {
                return $expected >= $value;
            }
            if ( $operator === 'contains' ) {
                return stripos( $value, $expected ) !== false;
            }
            if ( $operator === 'does-not-contain' ) {
                return stripos( $value, $expected ) === false;
            }
            if ( $operator === 'regex' ) {
                return preg_match( $expected, $value );
            }
        }
    }

	return $condition_is_met;
}, 10, 5 );
