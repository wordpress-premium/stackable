<?php
/**
 * Conditional logic of the condition type Custom PHP.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'stackable_conditional_display/custom-php', function( $condition_is_met, $condition, $block_content, $block ) {
    $options = isset( $condition['options'] ) ? $condition['options'] : null;

    if ( $options ) {
        $custom_php = $options['customPHP'];
        // Skip checking if custom PHP is empty.
        if ( empty( $custom_php ) ) {
            return true; // Show the block if there is no custom php entered
        }

		$signature = hash_hmac( 'sha256', $custom_php, stk_salt() );

		// If the signature is not present in the options, then it means this
		// custom PHP was created before the signature checking was implemented.
		// In this case, we need to check the signature against the stored
		// signatures which were generated upon Stackable version upgrade.
		if ( ! array_key_exists( 'signature', $options ) ) {
			$signatures = get_option( 'stackable_disp_cond_custom_php_sigs' );
			if ( empty( $signatures ) ) {
				$signatures = array();
			}

			// If the signature failed to match any stored signatures, then just exit.
			$unique_id = $block['attrs']['uniqueId'];
			if ( ! array_key_exists( $unique_id, $signatures ) ) {
				return $condition_is_met;
			} else if ( ! hash_equals( $signatures[ $unique_id ], $signature ) )  {
				return $condition_is_met;
			}
		}

		// If the signature included in the options does not match the generated
		// signature, then exit.
		if ( array_key_exists( 'signature', $options ) && ! hash_equals( $options['signature'], $signature ) ) {
			return $condition_is_met;
		}

        if ( stripos( $custom_php, 'return' ) === false ) {
            $custom_php = 'return ' . $custom_php;
        }
        $code = urldecode( $custom_php );

        if ( ! is_admin() ) {
            try {
                ob_start();
                $condition_is_met = eval( $code . ';' );
                ob_end_clean();
            }
            catch ( Error $e ) {
                trigger_error( $e->getMessage(), E_USER_WARNING );
            }
        }
    }

	return $condition_is_met;
}, 10, 5 );

if ( ! function_exists( 'stackable_generate_custom_php' ) ) {
	/**
	 * Function for registering a custom endpoint.
	 *
	 * @param array arguments
	 * @return string generated content.
	 */
	function stackable_generate_custom_php() {
		register_rest_route( 'stackable/v3', '/generate_hash/', array(
			'methods' => 'POST',
			'permission_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
			'callback' => function( $request ) {
				return hash_hmac( 'sha256', $request->get_param( 'custom_php' ), stk_salt() );
			},
			'args' => array(
				'custom_php' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_string( $param );
					}
				),
			)
		) );
	}

	add_action( 'rest_api_init', 'stackable_generate_custom_php', 2, 0 );
}

/**
 * Generate temporary signature for all stackable blocks that contain custom php attribute on update
 * @since 3.12.16
 */
if ( ! function_exists( 'stackable_generate_all_signatures' ) ) {
	function stackable_generate_all_signatures( $old_version, $new_version ) {

		if ( ! empty( $old_version ) && version_compare( $old_version, "3.12.17", "<" ) ) {
			// Do only once.
			if ( get_option( 'stackable_disp_cond_custom_php_sigs' ) ) {
				return;
			}

			global $wpdb;

			$results = $wpdb->get_results( "SELECT post_content FROM {$wpdb->posts} WHERE post_status IN ('publish', 'draft', 'pending', 'future') AND post_content LIKE '%custom-php%' AND post_content LIKE '%displayCondition%' AND post_content LIKE '%wp:stackable/%'" );

			if ( ! empty( $results ) ) {
				// Holds all signatures.
				$signatures = array();

				foreach ( $results as $result ) {
					$blocks = parse_blocks( $result->post_content );
					foreach ( $blocks as $block ) {
						stackable_generate_signature( $block, $signatures );
					}
				}

				update_option( 'stackable_disp_cond_custom_php_sigs', $signatures );
			}
		}

	}

	add_action( 'stackable_early_version_upgraded_frontend', 'stackable_generate_all_signatures', 10, 2 );
}

if ( ! function_exists( 'stackable_generate_signature' ) ) {
	function stackable_generate_signature( $block, &$signatures ) {

		// Generate signatures for nested blocks
		if ( ! empty( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner_block ) {
				stackable_generate_signature( $inner_block, $signatures );
			}
		}

		if ( ! is_null( $block['blockName'] ) && stripos( $block['blockName'], 'stackable/' ) === false ) {
			return;
		}

		if ( ! array_key_exists( 'uniqueId', $block['attrs'] ) ) {
			return;
		}

		$unique_id = $block['attrs']['uniqueId'];

		// Generate a signature if the block has "display condition -> custom php" attribute
		if ( array_key_exists( 'displayCondition', $block['attrs']) ) {
			if ( ! array_key_exists( 'conditions', $block['attrs']['displayCondition'] ) || empty( $block['attrs']['displayCondition']['conditions'] ) ) {
				return;
			}

			foreach ( $block['attrs']['displayCondition']['conditions'] as $condition ) {
				if ( ! array_key_exists( 'type', $condition ) || $condition['type'] !== 'custom-php' ) {
					continue;
				}

				if ( ! array_key_exists( 'options', $condition ) || empty( $condition['options'] ) ) {
					continue;
				}

				if ( ! array_key_exists( 'customPHP', $condition['options'] ) ) {
					continue;
				}

				$signatures[ $unique_id ] = hash_hmac( 'sha256', $condition['options']['customPHP'], stk_salt() );
			}
		}
	}
}
