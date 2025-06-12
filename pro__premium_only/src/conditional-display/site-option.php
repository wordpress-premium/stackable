<?php
/**
 * Conditional logic of the condition type Site Option.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'stackable_conditional_display/site-option', function( $condition_is_met, $condition, $block_content, $block ) {
    $options = isset( $condition['options'] ) ? $condition['options'] : null;

    if ( $options ) {
        $name = isset( $options['name'] ) ? $options['name'] : '';
        $operator = isset( $options['operator'] ) ? $options['operator'] : '';
        $expected = isset( $options['expectedVal'] ) ? $options['expectedVal'] : '';

        $value = get_option( $name );
		if ( $value === false ) {
			$value = '';
		}

        if ( $name && $operator || $name && $operator && $expected ) {

            if ( $operator === 'equal' ) {
                return $expected === $value;
            }

            if ( $operator === 'not-equal' ) {
                return $expected !== $value;
            }

            if ( $operator === 'true' ) {
                // Falsy values should be displayed.
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
                return strpos( $value, $expected ) !== false;
            }
            if ( $operator === 'does-not-contain' ) {
                return strpos( $value, $expected ) === false;
            }
            if ( $operator === 'regex' ) {
                return preg_match( $expected, $value );
            }
        }
    }

	return $condition_is_met;
}, 10, 5 );
