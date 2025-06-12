<?php
/**
 * Rendering of the blocks based on the display condition.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


require_once( dirname( __FILE__ ) . '/role.php' );
require_once( dirname( __FILE__ ) . '/date-time.php' );
require_once( dirname( __FILE__ ) . '/conditional-tag.php' );
require_once( dirname( __FILE__ ) . '/custom-php.php' );
require_once( dirname( __FILE__ ) . '/query-string.php' );
require_once( dirname( __FILE__ ) . '/post-meta.php' );
require_once( dirname( __FILE__ ) . '/site-option.php' );
require_once( dirname( __FILE__ ) . '/post-id.php' );
require_once( dirname( __FILE__ ) . '/login-status.php' );
require_once( dirname( __FILE__ ) . '/post-type.php' );
require_once( dirname( __FILE__ ) . '/post-taxonomy.php' );
require_once( dirname( __FILE__ ) . '/woocommerce.php' );
/**
 * Render block logic.
 *
 * @param string $block_content Block frontend output.
 * @param array $block Block attributes and info.
 * @return mixed Return either $block_content or nothing depending on the settings.
 */
function conditional_display( $block_content, $block, $instance ) {
	// Some themes can produce a null $block.
	if ( empty( $block ) || ! isset( $block['attrs'] ) || ! is_array( $block['attrs'] ) ) {
		return $block_content;
	}

    // If no display condition, show the block.
    if ( ! array_key_exists( 'displayCondition', $block['attrs'] ) ) {
        return $block_content;
    }

    $display_condition = array();
    if ( array_key_exists( 'displayCondition', $block['attrs'] ) && is_array( $block['attrs']['displayCondition'] ) ) {
        $display_condition = $block['attrs']['displayCondition'];
    }
    $conditions = array_key_exists( 'conditions', $display_condition ) ? $display_condition['conditions'] : array();

    // Show is the default value for display
    $display = array_key_exists( 'display', $display_condition ) ? $display_condition['display'] : 'show';
    $trigger = array_key_exists( 'trigger', $display_condition ) ? $display_condition['trigger'] : '';

    if ( ! empty( $conditions ) ) {
        $results_arr = array();

        foreach ( $conditions as $condition ) {

            // Execute only if there are options set.
            // Not checking this adds a default true in $results_arr
            if ( ! empty( $condition['options'] ) ) {
                // Display condition logic API.
                array_push( $results_arr, apply_filters(
                    'stackable_conditional_display/' . $condition['type'],
                    false, // Is the condition met?
                    $condition, // The current condition to test.
                    $block_content,
                    $block,
					$instance
                ) );
            }
        }

		/**
		 * $results_arr now contains whether or not all conditions are met.
		 */

		$condition_passed = false;

        if ( count( $results_arr ) >= 2 ) {
			// At least one condition should pass.
            if ( $trigger === 'any' && in_array( true, $results_arr ) ) {
				$condition_passed = true;
			// ALL conditions should pass.
            } else if ( $trigger === 'all' && ! in_array( false, $results_arr ) ) {
				$condition_passed = true;
            } else {
				$condition_passed = false;
			}

        } else if ( count( $results_arr ) === 1 ) {
            $condition_passed = $results_arr[0];
        } else {
            $condition_passed = true;
        }

		$show_on_match = $display !== 'hide';
		$should_display = $condition_passed ? $show_on_match : ! $show_on_match;

		if ( ! $should_display ) {
			return '';
		}
    }

    return $block_content;
}

if ( ! is_admin() ) {
	add_filter( 'render_block', 'conditional_display', 10, 3 );
}
