<?php
/**
 * Conditional logic of the condition type Conditional tag.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'stackable_conditional_display/conditional-tag', function( $condition_is_met, $condition, $block_content, $block ) {
    $options = isset( $condition['options'] ) ? $condition['options'] : null;

    if ( $options ) {
        $selected_values = $options['selectedValues'];

        if ( $selected_values ) {
			foreach ( $selected_values as $index => $value ) {
				if ( strpos( $value, 'is_home' ) && ! is_home() ) {
					return false;
				} else if ( strpos( $value, 'is_front_page' ) && ! is_front_page() ) {
					return false;
				} else if ( strpos( $value, 'is_404' ) && ! is_404() ) {
					return false;
				} else if ( strpos( $value, 'is_single' ) && ! is_single( get_the_ID() ) ) {
					return false;
				} else if ( strpos( $value, 'is_attachment' ) && ! is_attachment() ) {
					return false;
				} else if ( strpos( $value, 'is_preview' ) && ! is_preview() ) {
					return false;
				} else if ( strpos( $value, 'is_page' ) && ! is_page( get_the_ID() ) ) {
					return false;
				} else if ( strpos( $value, 'is_privacy_policy' ) && ! is_privacy_policy() ) {
					return false;
				} else if ( strpos( $value, 'is_archive' ) && ! is_archive() ) {
					return false;
				} else if ( strpos( $value, 'is_category' ) && ! is_category() ) {
					return false;
				} else if ( strpos( $value, 'is_tag' ) && ! is_tag() ) {
					return false;
				} else if ( strpos( $value, 'is_tax' ) && ! is_tax() ) {
					return false;
				} else if ( strpos( $value, 'is_author' ) && ! is_author() ) {
					return false;
				} else if ( strpos( $value, 'is_date' ) && ! is_date() ) {
					return false;
				} else if ( strpos( $value, 'is_year' ) && ! is_year() ) {
					return false;
				} else if ( strpos( $value, 'is_search' ) && ! is_search() ) {
					return false;
				} else if ( strpos( $value, 'is_trackback' ) && ! is_trackback() ) {
					return false;
				} else if ( strpos( $value, 'is_dynamic_sidebar' ) && ! is_dynamic_sidebar() ) {
					return false;
				} else if ( strpos( $value, 'is_rtl' ) && ! is_rtl() ) {
					return false;
				} else if ( strpos( $value, 'is_multisite' ) && ! is_multisite() ) {
					return false;
				} else if ( strpos( $value, 'is_main_site' ) && ! is_main_site() ) {
					return false;
				} else if ( strpos( $value, 'is_child_theme' ) && ! is_child_theme() ) {
					return false;
				} else if ( strpos( $value, 'is_customize_preview' ) && ! is_customize_preview() ) {
					return false;
				} else if ( strpos( $value, 'is_multi_author' ) && ! is_multi_author() ) {
					return false;
				} else if ( strpos( $value, 'is_feed' ) && ! is_feed() ) {
					return false;
				} else if ( strpos( $value, 'is_sticky' ) && ! is_sticky( get_the_ID() ) ) {
					return false;
				} else if ( strpos( $value, 'is_post_type_hierarchical' ) && ! is_post_type_hierarchical( get_post_type( get_the_ID() ) ) ) {
					return false;
				} else if ( strpos( $value, 'is_post_type_archive' ) && ! is_post_type_archive( get_post_type( get_the_ID() ) ) ) {
					return false;
				} else if ( strpos( $value, 'comments_open' ) && ! comments_open( get_the_ID() ) ) {
					return false;
				} else if ( strpos( $value, 'pings_open' ) && ! pings_open( get_the_ID() ) ) {
					return false;
				} else if ( strpos( $value, 'has_excerpt' ) && ! has_excerpt( get_the_ID() ) ) {
					return false;
				} else if ( strpos( $value, 'has_post_thumbnail' ) && ! has_post_thumbnail( get_the_ID() ) ) {
					return false;
				} else if ( strpos( $value, 'has_tag' ) && ! has_tag() ) {
					return false;
				} else if ( strpos( $value, 'has_term' ) && ! has_term() ) {
					return false;
				// Has Primary Nav Menu
				} else if ( strpos( $value, 'has_nav_menu' ) && ! has_nav_menu( 'primary' ) ) {
					return false;
				}
            }

			// If the code reaches here, it means all the conditional tags passed above.
			return true;
        }
    }

	return $condition_is_met;
}, 10, 5 );
