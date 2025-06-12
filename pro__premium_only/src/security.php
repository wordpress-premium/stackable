<?php
/**
 * Security Functions
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'stk_salt' ) ) {
	/**
	 * Get a salt for hashing.
	 *
	 * @return string
	 */
	function stk_salt() {
		$salt = get_option( 'stk_salt' );
		if ( ! $salt ) {
			$salt = wp_salt();
			update_option( 'stk_salt', $salt, 'no' );
		}
		return $salt;
	}
}
