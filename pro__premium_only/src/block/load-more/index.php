<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Stackable_Load_More_Block_API' ) ) {
	class Stackable_Load_More_Block_API {

		function __construct() {
			// Register the rest route to get the succeeding pages.
			add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
		}

		/**
		 * Register our load more API endpoin
		 */
		public function register_rest_route() {
			register_rest_route( 'stackable/v3', '/load-more', array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_next_entries' ),
				'permission_callback' => '__return_true',
				'args' => array(
					'stkQueryId' => array(
						'validate_callback' => __CLASS__ . '::validate_string'
					),
					'page' => array(
						'sanitize_callback' => 'absint',
					),
					'num' => array(
						'sanitize_callback' => 'absint',
					),
				)
			) );
		}

		/**
		 * Checks whether the argument value is a valid string.
		 *
		 * @param * value
		 * @param WP_Request $request
		 * @param string $param
		 *
		 * @return boolean, if true, the value is valid. Otherwise, false
		 */
		public static function validate_string( $value, $request, $param ) {
			if ( ! is_string( $value ) ) {
				return new WP_Error( 'invalid_param', sprintf( esc_html__( '%s must be a string.', STACKABLE_I18N ), $param ) );
			}

			return true;
		}

		/**
		 * Response handler for getting the next entries in load
		 * more button.
		 *
		 * @param WP_Request $request
		 * @return string the API response
		 */
		public function get_next_entries( $request ) {
			$stkQueryId = $request->get_param( 'stkQueryId' );
			$page = absint( $request->get_param( 'page' ) );
			$num = absint( $request->get_param( 'num' ) );

			$posts_data = get_transient( 'stackable.posts.' . $stkQueryId );
			if ( empty( $posts_data ) ) {
				return '';
			}

			$posts_data = json_decode( $posts_data );
			$attributes = (array) $posts_data->attributes;
			$template = (string) $posts_data->template;
			$query = new WP_Query( generate_post_query_from_stackable_posts_block( $attributes ) );

			$total_posts = $query->found_posts - $attributes['postOffset'];

			$num = $num ? $num : $attributes['numberOfItems'];

			// Get the next posts.
			$new_attrs = $attributes;
			$new_attrs['postOffset'] = $attributes['postOffset'] + $attributes['numberOfItems'] + $num * ( $page - 2 );
			$new_attrs['numberOfItems'] = $num;
			$new_query = generate_post_query_from_stackable_posts_block( $new_attrs );
			$new_posts = wp_get_recent_posts( $new_query );
			// Manually slice the array based on the number of posts per page.
			if ( is_array( $new_posts ) && count( $new_posts ) > (int) $new_query['numberposts'] ) {
				$new_posts = array_slice( $new_posts, 0, (int) $new_query['numberposts'] );
			}

			$posts = '';
			foreach ( $new_posts as $post ) {
				$posts .= generate_render_item_from_stackable_posts_block( $post, $attributes, $template );
			}

			$response = new WP_REST_Response( $posts, 200 );
			$response->set_headers( [ 'X-STK-Total_Posts' => $total_posts ] );
			return $response;
		}
	}

	new Stackable_Load_More_Block_API();
}

if ( ! class_exists( 'Stackable_Load_More_Block_Premium' ) ) {
	class Stackable_Load_More_Block_Premium {

		function __construct() {
			add_filter( 'stackable.register-blocks.options', array( $this, 'register_block_type' ), 1, 3 );
			add_filter( 'stackable.posts.output', array( $this, 'register_template_parser' ), 1, 4 );
			add_filter( 'stackable/load-more/enqueue_scripts', array( $this, 'load_frontend_script' ), 1 );
		}

		public function load_frontend_script() {
			if ( ! is_admin() ) {
				wp_enqueue_script(
					'stk-frontend-load-more',
					plugins_url( 'dist/frontend_load_more__premium_only.js', STACKABLE_FILE ),
					array(),
					STACKABLE_VERSION,
					true
				);
			}
		}

		/**
		 * Modify the register_options of the
		 * load more button block.
		 *
		 * @param array $register_options
		 * @param string $name
		 * @param array $metadata
		 *
		 * @return array new register options.
		 */
		public function register_block_type( $register_options, $name, $metadata ) {
			if ( $name !== 'stackable/load-more' ) {
				return $register_options;
			}

			$register_options['render_callback'] = array( $this, 'render_callback' );

			return $register_options;
		}

		/**
		 * The dynamic render method of the load more block.
		 *
		 * @param array $attributes
		 * @param string $content
		 * @param array $block
		 *
		 * @param string new content.
		 */
		public function render_callback( $attributes, $content, $block ) {
			if ( ! isset( $block->parsed_block['innerBlocks'][0] ) ) {
				return $content;
			}

			$stkQueryId = array_key_exists( 'stkQueryId', $block->context ) ? $block->context['stkQueryId'] : '1';
			$numberItems = isset( $attributes['numberItems'] ) ? $attributes['numberItems'] : 2;

			preg_match( '/<[^c]*class="(.*?stk-block-load-more[^"]*)"/', $content, $match );
			$content = str_replace(
				$match[0],
				$match[0] . "data-query-id=\"$stkQueryId\" data-load-items=\"$numberItems\"",
				$content
			);

			return $content;
		}

		/**
		 * Filter function for calling set_transient when a posts block
		 * is rendered.
		 *
		 * We are doing this so we can access the
		 * post templates which will be used when showing
		 * more posts.
		 */
		public function register_template_parser( $content, $attributes, $block, $template ) {
			set_transient( 'stackable.posts.' . $attributes['stkQueryId'], json_encode(
				array(
					'attributes' => $attributes,
					'template' => $template
				)
			), DAY_IN_SECONDS );

			return $content;
		}
	}

	new Stackable_Load_More_Block_Premium();
}

if ( ! function_exists( 'stackable_load_more_register_callback' ) ) {
	function stackable_load_more_register_callback( $block_content,  $block ) {
		add_filter( 'stackable_localize_frontend_script', 'stackable_load_more_add_args', 10, 1 );
		return $block_content;
	}

	add_filter( 'render_block_stackable/load-more', 'stackable_load_more_register_callback', 10, 2 );
}

if ( ! function_exists( 'stackable_load_more_add_args' ) ) {
	function stackable_load_more_add_args( $array ) {
		$array['restUrl'] = get_rest_url();
		return $array;
	}
}
