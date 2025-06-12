<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Stackable_Pagination_Block_Premium' ) ) {
	class Stackable_Pagination_Block_Premium {

		function __construct() {
			add_filter( 'stackable.register-blocks.options', array( $this, 'register_block_type' ), 1, 3 );
		}

		/**
		 * Modify the register_options of the
		 * pagination block.
		 *
		 * @param array $register_options
		 * @param string $name
		 * @param array $metadata
		 *
		 * @return array new register options.
		 */
		public function register_block_type( $register_options, $name, $metadata ) {
			if ( $name !== 'stackable/pagination' ) {
				return $register_options;
			}

			$register_options['render_callback' ] = array( $this, 'render_callback' );

			return $register_options;
		}

		/**
		 * The dynamic render method of the pagination block.
		 *
		 * @param array $attributes
		 * @param string $content
		 * @param array $block
		 *
		 * @param string new content.
		 */
		public function render_callback( $attributes, $content, $block ) {
			preg_match( '/!#content!#/', $content, $match );

			if ( ! isset( $match[ 0 ] ) ) {
				return $content;
			}

			$attributes = $this->generate_defaults( $attributes );
			$context = Stackable_Posts_Block::generate_defaults( $block->context );

			$content = $this->render_pagination(
				$attributes,
				$content,
				$context,
				$block
			);
			return $content;
		}

		/**
		 * Some of the attribute keys are not defined,
		 * especially when those attributes are not modified.
		 * Give them default values.
		 *
		 * @param array $attributes
		 * @return array $attributes with default values
		 */
		public function generate_defaults( $attributes ) {
			$default_attributes = array(
				'previousLabel' => __( 'Previous', STACKABLE_I18N ),
				'nextLabel' => __( 'Next', STACKABLE_I18N ),
				'showNextPrevious' => true,
			);

			$out = array();
			foreach ( $attributes as $name => $value ) {
				$out[ $name ] = $value;
			}
			foreach ( $default_attributes as $name => $default ) {
				if ( array_key_exists( $name, $out ) ) {
					if ( $out[ $name ] === '' ) {
						$out[ $name ] = $default;
					}
				} else {
					$out[ $name ] = $default;
				}
			}
			return $out;
		}

		/**
		 * Function used to create pagination array to avoid crowded buttons.
		 * e.g. ( [1,2,3,"...", 11] )
		 *
		 * @param number $paged
		 * @param number $total_posts
		 *
		 * @return array generated pagination array
		 */
		public function generate_pagination_array( $paged, $total_posts ) {
			if ( $total_posts === 0 ) {
				return array();
			}

			$offset_in_between = 2;
			$left_offset = max( 1, min( (int) $total_posts, $paged - $offset_in_between ) );
			$right_offset = max( 1, min( (int) $total_posts, $paged + $offset_in_between ) );
			$ret = [ 1, '...' ];
			for ( $count = $left_offset; $count <= $right_offset; $count++ ) {
				array_push( $ret, $count );
			}
			array_push( $ret, '...', $total_posts );

			if ( $left_offset === 1 ) {
				array_splice( $ret, 0, 2 );
			}

			if ( $left_offset === 2 ) {
				array_splice( $ret, 1, 1 );
			}

			if ( $right_offset === (int) $total_posts ) {
				array_splice( $ret, count( $ret ) - 3, 2 );
			}

			if ( $right_offset === (int) ( $total_posts ) - 1 ) {
				array_splice( $ret, count( $ret ) - 3, 1 );
			}

			return $ret;
		}

		/**
		 * Function for generating the link node markup
		 *
		 * @param array link node properties
		 *
		 * @return string generated link markup
		 */
		public function generate_link_node( $link_node ) {
			$link_attributes = array();

			foreach ( $link_node['attributes'] as $key => $value ) {
				array_push( $link_attributes, $key . '="' . $value . '"' );
			}

			return sprintf(
				'<%s%s%s><span class="stk-button__inner-text">%s</span></%s>',
				$link_node['tagName'],
				count( $link_attributes ) === 0 ? '' : ' ' . implode( ' ', $link_attributes ),
				count( $link_node['classes'] ) === 0 ? '' : ' class="' . implode( ' ', $link_node['classes'] ) . '"',
				$link_node['text'],
				$link_node['tagName']
			);
		}

		/***
		 * Query generator. Given an object of
		 * attributes, create a post query.
		 *
		 * @param array $attributes
		 * @return array post query
		 */
		public function generate_query( $context, $block ) {
			/**
			 * Check if the pagination block is inside of the core/query block.
			 */
			if ( isset( $context['query'] ) ) {
				/**
				 * Generate the query from the query loop block.
				 * @see https://github.com/WordPress/gutenberg/blob/3da717b8d0ac7d7821fc6d0475695ccf3ae2829f/packages/block-library/src/query-pagination-numbers/index.php
				 */
				if ( isset( $context['query']['inherit'] ) && $context['query']['inherit'] ) {
					global $wp_query;
					return $wp_query;
				} else {
					$page_key = isset( $context['queryId'] ) ? 'query-' . $context['queryId'] . '-page' : 'query-page';
					$page_key = isset( $context['queryString'] ) ? $context['queryString'] : $page_key;
					$page = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ];
					return new WP_Query( build_query_vars_from_query_block( $block, $page ) );
				}
			}

			$post_query = generate_post_query_from_stackable_posts_block( $block );
			return new WP_Query( $post_query );
		}

		public function get_query_info( $query, $context ) {
			$query_info = array();
			if ( isset( $context['query'] ) ) {
				/**
				 * @see https://github.com/WordPress/gutenberg/blob/3da717b8d0ac7d7821fc6d0475695ccf3ae2829f/packages/block-library/src/query-pagination-numbers/index.php
				 */
				$page_key = isset( $context['queryId'] ) ? 'query-' . $context['queryId'] . '-page' : 'query-page';
				$page_key = isset( $context['queryString'] ) ? $context['queryString'] : $page_key;
				$page = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ];
				$max_page = isset( $block->context['query']['pages'] ) ? (int) $block->context['query']['pages'] : 0;
				$query_info['paged'] = $page;
				$query_info['total_posts'] = $query->found_posts;
				$query_info['total_pages'] = ! $max_page || $max_page > $query->max_num_pages ? $query->max_num_pages : $max_page;
				return $query_info;
			}

			$page_key = isset( $context['stkQueryId'] ) ? 'stk-query-' . $context['stkQueryId'] . '-page' : 'query-page';
			$page_key = isset( $context['queryString'] ) ? $context['queryString'] : $page_key;
			$page = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ];
			$query_info['paged'] = $page;
			$query_info['total_posts'] = $query->found_posts - $context[ 'postOffset' ];
			$query_info['total_pages'] = ceil( $query_info['total_posts'] / $context[ 'numberOfItems' ] );

			return $query_info;
		}

		/**
		 * Helper function for generating
		 * page numbers.
		 *
		 * This is necessary to also handle
		 * pagination links for query block
		 *
		 * @param number $paged
		 * @param array $context
		 * @return string href
		 */
		public function get_pagenum_link( $paged, $context, $query ) {
			if ( isset( $context['query'] ) && ! ( isset( $context['query']['inherit'] ) && $context['query']['inherit'] ) ) {
				/**
				 * @see https://github.com/WordPress/gutenberg/blob/3da717b8d0ac7d7821fc6d0475695ccf3ae2829f/packages/block-library/src/query-pagination-numbers/index.php
				 */
				$max_page = isset( $context['query']['pages'] ) ? (int) $context['query']['pages'] : 0;
				$page_key = isset( $context['queryId'] ) ? 'query-' . $context['queryId'] . '-page' : 'query-page';
				$page = empty( $_GET[ $page_key ] ) ? 1 : (int) $_GET[ $page_key ];
				$total  = ! $max_page || $max_page > $query->max_num_pages ? $query->max_num_pages : $max_page;
				$links = paginate_links( array(
					'base'      => '%_%',
					'format'    => "?$page_key=%#%",
					'current'   => max( 1, $page ),
					'total'     => $total,
					'prev_next' => false,
				) );

				preg_match( "/href=\"([^\"]*)\"[^>]*>$paged<\/a>/", $links, $match );
				return $match[ 1 ];
			} else {
				return generate_page_link_from_stackable_posts_block( $context, $paged, $query );
			}

			return get_pagenum_link( $paged - 1 );
		}

		public function render_pagination( $attributes, $content, $context, $block ) {
			$context = array_merge( $attributes, $context );
			$pagination_query = $this->generate_query( $context, $block );
			$page_info = $this->get_query_info( $pagination_query, $context );
			$paged = $page_info['paged'];
			$total_posts = $page_info['total_posts'];
			$total_pages = $page_info['total_pages'];

			$previous_text = $attributes[ 'previousLabel' ];
			$next_text = $attributes[ 'nextLabel' ];
			$show_next_previous_text = $attributes[ 'showNextPrevious' ];
			$button_classes = array( 'stk-button' );
			$pagination_links = '';

			if ( $paged > 1 && ! empty( $show_next_previous_text ) ) {
				// Add previous button.
				$pagination_links .= $this->generate_link_node( array(
					'classes' => array_merge(
						$button_classes,
						[ 'prev' ]
					),
					'tagName' => 'a',
					'text' => $previous_text,
					'attributes' => array(
						'title' => __( 'Previous page', STACKABLE_I18N ),
						'rel' => 'prev',
						'href' => $this->get_pagenum_link( $paged - 1, $context, $pagination_query )
					)
				) );
			}

			foreach ( $this->generate_pagination_array( $paged, $total_pages ) as $text ) {
				$link_node = array(
					'classes' => $button_classes,
					'attributes' => array(),
					'text' => $text,
					'tagName' => 'a',
				);

				if ( $text === '...' ) {
					// Handle dots links
					$link_node['attributes']['aria-hidden'] = "true";
					array_push( $link_node['classes'], 'dots' );
				} else {
					// Add title attributes for non-dots links
					$link_node['attributes']['title'] = sprintf( __( 'Page %s', STACKABLE_I18N ), $text );
					if ( (int) $text === (int) $paged ) {
						// Handle active buttons.
						$link_node['attributes']['aria-current'] = "page";
						$link_node['tagName'] = 'span';
						array_push( $link_node['classes'], 'is-active' );
					} else {
						// Handle ordinary links.
						$link_node['attributes']['href'] = $this->get_pagenum_link( $text, $context, $pagination_query );
					}
				}

				$pagination_links .= $this->generate_link_node( $link_node );
			}

			if ( $paged < (int) $total_pages && ! empty( $show_next_previous_text ) ) {
				// Add next button.
				$pagination_links .= $this->generate_link_node( array(
					'classes' => array_merge(
						$button_classes,
						[ 'next' ]
					),
					'tagName' => 'a',
					'text' => $next_text,
					'attributes' => array(
						'title' => __( 'Next page', STACKABLE_I18N ),
						'rel' => 'prev',
						'href' => $this->get_pagenum_link( $paged + 1, $context, $pagination_query )
					)
				) );
			}

			return str_replace( '!#content!#', $pagination_links, $content );
		}
	}

	new Stackable_Pagination_Block_Premium();
}
