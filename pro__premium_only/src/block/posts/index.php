<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stk_paginate_links_cache = array();

if ( ! function_exists( 'generate_page_link_from_stackable_posts_block' ) ) {
	/**
	 * Given a WP_Block object and
	 * a page number, generate the
	 * page link of the post.
	 *
	 * @since 3.0.0
	 * @param WP_Block | array $blockOrAttribute
	 * @param number | string $paged
	 * @param WP_Query | null $query
	 *
	 * @return string link
	 */
	function generate_page_link_from_stackable_posts_block( $blockOrAttribute, $paged, $query = null ) {
		$is_wp_block = ! is_array( $blockOrAttribute ) && get_class( $blockOrAttribute ) === 'WP_Query';

		$context = Stackable_Posts_Block::generate_defaults( $is_wp_block ? $blockOrAttribute->context : $blockOrAttribute );
		$page_key = isset( $context['stkQueryId'] ) ? 'stk-query-' . $context['stkQueryId'] . '-page' : 'page';
		$page_key = isset( $context['queryString'] ) ? $context['queryString'] : $page_key;
		$page = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ];

		// Access paginate links cache.
		global $stk_paginate_links_cache;
		$cache_key = $page_key . '-' . $page ;
		$links = '';
		if ( isset( $stk_paginate_links_cache[ $cache_key ] ) ) {
			$links = $stk_paginate_links_cache[ $cache_key ];
			preg_match( "/href=\"([^\"]*)\"[^>]*>$paged<\/a>/", $links, $match );
			if ( is_array( $match ) && count( $match ) >= 2 ) {
				if ( $paged === 1 && $match[ 1 ] === '' ) { // Prevent 1st page button having empty href
					return "?" . $page_key . "=" . $paged;
				}
				return $match[ 1 ];
			}
		}

		if ( $query === null ) {
			$query = new WP_Query( generate_post_query_from_stackable_posts_block( $context ) );
		}

		$total_pages = ceil( ( $query->found_posts - $context['postOffset'] ) / $context['numberOfItems'] );
		$links = paginate_links( array(
			'base'      => '%_%',
			'format'    => "?$page_key=%#%",
			'current'   => max( 1, $page ),
			'total'     => $total_pages,
			'prev_next' => false,
		) );

		$stk_paginate_links_cache[ $cache_key ] = $links;

		preg_match( "/href=\"([^\"]*)\"[^>]*>$paged<\/a>/", $links, $match );
		if ( empty( $match ) ) {
			return '';
		}

		if ( is_array( $match ) && count( $match ) >= 2 ) {
			if ( $paged === 1 && $match[ 1 ] === '' ) { // Prevent 1st page button having empty href
				return "?" . $page_key . "=" . $paged;
			}
			return $match[ 1 ];
		}
	}
}

if ( ! class_exists( 'Stackable_Posts_Block_Premium' ) ) {
	class Stackable_Posts_Block_Premium {
		function __construct() {
			add_filter( 'stackable/posts/post_query', array( $this, 'generate_query' ), 10, 3 );
		}
		/**
		 * Query generator for premium options.
		 *
		 * @param array $post_query
		 * @param attributes $attributes
		 *
		 * @return array post query
		 */
		public function generate_query( $post_query, $attributes, $query_string = '' ) {
			$post_query['offset'] = $attributes['postOffset'];
			$post_query['exclude'] = array_filter( array_map( 'sbppq_map', explode( ',', $attributes['postExclude'] ) ), 'sbppq_filter' );
			if ( $attributes['excludeCurrentPost'] ) {
				$post_query['exclude'][] = get_the_ID();
			}

			$post_query['include'] = array_filter( array_map( 'sbppq_map', explode( ',', $attributes['postInclude'] ) ), 'sbppq_filter' );

			// Taxonomy for CPTs.
			$isCPT = $attributes['type'] !== 'post' && $attributes['type'] !== 'page';
			if ( $isCPT && ! empty( $attributes['taxonomyType'] ) && ! empty( $attributes['taxonomy'] ) ) {
				$post_query['tax_query'] = array(
					array(
						'taxonomy' => $attributes['taxonomyType'],
						'field' => 'term_id',
						'terms' => explode( ',', is_string( $attributes['taxonomy'] ?? '' ) ? $attributes['taxonomy'] : '' ),
						'operator' => 'IN',
					),
				);
			}

			/**
			 * Use our own query parameters to avoid pagination conflict with other posts block.
			 */
			$page_key = isset( $attributes['stkQueryId'] ) ? 'stk-query-' . $attributes['stkQueryId'] . '-page' : 'query-page';
			$page_key = ! empty( $query_string ) ? $query_string : $page_key;
			$page = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ];
			$post_query['paged'] = $page;
			$post_query['posts_per_page'] = $post_query['numberposts'];
			$offset = ( $post_query['paged'] - 1 ) * $post_query['numberposts'] + $post_query['offset'];
			$post_query['offset'] = $offset;
			$post_query['post__not_in'] = $post_query['exclude'];
			$post_query['post__in'] = $post_query['include'];

			return $post_query;
		}
	}

	new Stackable_Posts_Block_Premium();
}
