<?php
/**
 * Post query changes for the `ugb/blog-posts` block.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( dirname( __FILE__ ) . '/designs.php' );
require_once( dirname( __FILE__ ) . '/pagination.php' );

if ( ! function_exists( 'sbppq_map' ) ) {
	function sbppq_map( $s ) {
		return (int) $s;
	}
}
if ( ! function_exists( 'sbppq_filter' ) ) {
	function sbppq_filter( $s ) {
		return !! $s;
	}
}

if ( ! function_exists( 'stackable_blog_post_post_query_premium_v2' ) ) {
	function stackable_blog_post_post_query_premium_v2( $post_query, $attributes ) {
		$post_query['offset'] = $attributes['postOffset'];
		$post_query['exclude'] = array_filter( array_map( 'sbppq_map', explode( ',', $attributes['postExclude'] ) ), 'sbppq_filter' );
		$post_query['include'] = array_filter( array_map( 'sbppq_map', explode( ',', $attributes['postInclude'] ) ), 'sbppq_filter' );

		// Taxonomy for CPTs.
		$isCPT = $attributes['postType'] !== 'post' && $attributes['postType'] !== 'page';
		if ( $isCPT && ! empty( $attributes['taxonomyType'] ) && ! empty( $attributes['taxonomy'] ) ) {
			$post_query['tax_query'] = array(
				array(
					'taxonomy' => $attributes['taxonomyType'],
					'field' => 'term_id',
					'terms' => $attributes['taxonomy'],
					'operator' => 'IN',
				),
			);
		}

		// Paginate the query.
		if ( $attributes['showPagination'] ) {
			$post_query['paged'] = max( 1, ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1 );
			$post_query['posts_per_page'] = $post_query['numberposts'];
			$offset = ( $post_query['paged'] - 1 ) * $post_query['numberposts'] + $post_query['offset'];
			$post_query['offset'] = $offset;
			$post_query['post__not_in'] = $post_query['exclude'];
			$post_query['post__in'] = $post_query['include'];
		}

		return $post_query;
	}
	if ( has_stackable_v2_frontend_compatibility() || has_stackable_v2_editor_compatibility() ) {
		add_filter( 'stackable/blog-post/v2/post_query', 'stackable_blog_post_post_query_premium_v2', 10, 2 );
	}
}


if ( ! function_exists( 'stackable_render_blog_posts_block_premium_v2' ) ) {
	function stackable_render_blog_posts_block_premium_v2( $post_content, $attributes ) {
		// Add pagination.
		if ( $attributes['showPagination'] ) {
			$pagination_query = new WP_Query( stackable_blog_posts_post_query_v2( $attributes ) );
			$post_content.= Stackable_Blog_Posts_Block_Pagination_V2::render_pagination( $pagination_query, $attributes );
		}

		return $post_content;
	}
	if ( has_stackable_v2_frontend_compatibility() || has_stackable_v2_editor_compatibility() ) {
		add_filter( 'stackable/blog-posts/v2/edit.output.markup', 'stackable_render_blog_posts_block_premium_v2', 10, 2 );
	}
}
