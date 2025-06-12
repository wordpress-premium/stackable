<?php
/**
 * Pagination for the `ugb/blog-posts` block.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Stackable_Blog_Posts_Block_Pagination_V2' ) ) {
	class Stackable_Blog_Posts_Block_Pagination_V2 {
        function __construct() {
			// Register the rest route to get the succeeding pages.
			add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );

			// Keep note of the block attributes since these will be used to generate the next page.
			add_action( 'stackable/blog-posts/v2/render', array( $this, 'remember_block_attributes' ), 10, 2 );
		}

		/**
		 * Validate string used by rest endpoint.
		 */
		public static function validate_string( $value, $request, $param ) {
			if ( ! is_string( $value ) ) {
				return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be a string.', STACKABLE_I18N ), $param ) );
			}
			return true;
		}

		/**
		 * Save the attributes of the blog post block, we'll reference this during the ajax call.
		 */
		public function remember_block_attributes( $attributes, $content ) {
			set_transient( 'stackable_posts_' . $attributes[ 'uniqueClass' ], json_encode( $attributes ), DAY_IN_SECONDS );
		}

		/**
		 * Register our pagination endpoint.
		 */
		public function register_rest_route() {
			register_rest_route( 'stackable/v2', '/blog_posts_pagination', array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_next_page' ),
				'permission_callback' => '__return_true',
				'args' => array(
					'id' => array(
						'validate_callback' => __CLASS__ . '::validate_string',
					),
					'page' => array(
						'sanitize_callback' => 'absint',
					),
					'num' => array(
						'sanitize_callback' => 'absint',
					),
				),
			) );
		}

		/**
		 * Get the next set of posts.
		 */
		public function get_next_page( $request ) {
			$id = $request->get_param( 'id' );
			$page = absint( $request->get_param( 'page' ) );
			$num = absint( $request->get_param( 'num' ) );

			// Get the block's attributes.
			$attributes = get_transient( 'stackable_posts_' . $id );
			if ( empty( $attributes ) ) {
				return '';
			}
			$attributes = json_decode( $attributes );

			// Don't remember the block attributes to save render.
			remove_action( 'stackable/blog-posts/v2/render', array( $this, 'remember_block_attributes' ) );

			// Get total number of posts. (Do this first)
			$post_query = stackable_blog_posts_post_query_v2( (array) $attributes );
			$the_query = new WP_Query( $post_query );
			$total_posts = $the_query->found_posts - $attributes->postOffset;

			// Default number of posts to get is the same as num of items.
			$num = $num ? $num : $attributes->numberOfItems;

			// Get the next posts.
			$new_attrs = $attributes;
			$new_attrs->postOffset = $attributes->postOffset + $attributes->numberOfItems + $num * ( $page - 2 );
			$new_attrs->numberOfItems = $num;
			$posts = stackable_render_blog_posts_block_v2( $new_attrs, true );

			$response = new WP_REST_Response( $posts, 200 );
			$response->set_headers( [ 'X-UGB-Total-Posts' => $total_posts ] );
			return $response;
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
		public static function generate_pagination_array( $paged, $total_posts ) {
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
		public static function generate_link_node( $link_node ) {
			$link_attributes = array();

			foreach ( $link_node['attributes'] as $key => $value ) {
				array_push( $link_attributes, $key . '="' . $value . '"' );
			}

			return sprintf(
				'<%s%s%s><span class="ugb-button--inner">%s</span></%s>',
				$link_node['tagName'],
				count( $link_attributes ) === 0 ? '' : ' ' . implode( ' ', $link_attributes ),
				count( $link_node['classes'] ) === 0 ? '' : ' class="' . implode( ' ', $link_node['classes'] ) . '"',
				$link_node['text'],
				$link_node['tagName']
			);
		}

		/**
		 * Function for rendering pagination.
		 */
		public static function render_pagination( $pagination_query, $attributes ) {
			$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			$total_posts = $pagination_query->found_posts - $attributes[ 'postOffset' ];
			$total_pages = ceil( $total_posts / $attributes[ 'numberOfItems' ] );
			$previous_text = $attributes[ 'previousLabel' ];
			$next_text = $attributes[ 'nextLabel' ];
			$pagination_links = '<div class="ugb-button-container ugb-blog-posts__pagination">';
			$show_next_previous_text = $attributes[ 'showNextPrevious' ];

			$button_classes = array( 'ugb-button' );
			$button_classes[] = 'ugb-button--size-' .( $attributes[ 'paginationSize' ] ? $attributes[ 'paginationSize' ] : 'normal' );
			if ( $attributes[ 'paginationHoverGhostToNormal' ] ) {
				$button_classes[] = 'ugb-button--ghost-to-normal-effect';
			}
			if ( $attributes[ 'paginationDesign' ] != 'link' && $attributes[ 'paginationHoverEffect' ] ) {
				$button_classes[] = 'ugb--hover-effect-' .$attributes[ 'paginationHoverEffect' ];
			}
			if ( ( $attributes[ 'paginationDesign' ] ? $attributes[ 'paginationDesign' ] : 'basic' ) == 'basic' && $attributes[ 'paginationShadow' ] ) {
				$button_classes[] = 'ugb--shadow-' .$attributes['paginationShadow'];
			}
			if ( ( $attributes[ 'paginationDesign' ] ? $attributes[ 'paginationDesign' ] : 'basic' ) != 'basic' ) {
				$button_classes[] = 'ugb-button--design-' .$attributes[ 'paginationDesign' ];
			}

			if ( $paged > 1 && ! empty( $show_next_previous_text ) ) {
				// Add previous button.
				$pagination_links .= Stackable_Blog_Posts_Block_Pagination_V2::generate_link_node( array(
					'classes' => array_merge(
						$button_classes,
						[ 'prev' ]
					),
					'tagName' => 'a',
					'text' => $previous_text,
					'attributes' => array(
						'title' => __( 'Previous page', STACKABLE_I18N ),
						'rel' => 'prev',
						'href' => get_pagenum_link( $paged - 1 )
					)
				) );
			}

			foreach ( Stackable_Blog_Posts_Block_Pagination_V2::generate_pagination_array( $paged, $total_pages ) as $text ) {
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
						$link_node['attributes']['href'] = get_pagenum_link( $text );
					}
				}

				$pagination_links .= Stackable_Blog_Posts_Block_Pagination_V2::generate_link_node( $link_node );
			}

			if ( $paged < (int) $total_pages && ! empty( $show_next_previous_text ) ) {
				// Add next button.
				$pagination_links .= Stackable_Blog_Posts_Block_Pagination_V2::generate_link_node( array(
					'classes' => array_merge(
						$button_classes,
						[ 'next' ]
					),
					'tagName' => 'a',
					'text' => $next_text,
					'attributes' => array(
						'title' => __( 'Next page', STACKABLE_I18N ),
						'rel' => 'prev',
						'href' => get_pagenum_link( $paged + 1 )
					)
				) );
			}

			$pagination_links .= '</div>';

			return $pagination_links;
		}
	}

	if ( has_stackable_v2_frontend_compatibility() || has_stackable_v2_editor_compatibility() ) {
		new Stackable_Blog_Posts_Block_Pagination_V2();
	}
}

