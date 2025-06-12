<?php
namespace Stackable\DynamicContent;
use \Stackable\DynamicContent\Sources\Util;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Stackable_Dynamic_Content {
    const STK_NAMESPACE = 'stackable_dynamic_content';
    const STK_ROUTE_NAMESPACE = 'stackable/v3';

	/**
	 * Keep track of all uniqueIds rendered so we can prevent duplicate ids.
	 *
	 * @var array
	 */
	public $rendered_unique_ids = array();

    function __construct() {
		// Override the render_callback of all registered blocks, so we can run the stackable_render_block_dynamic_content filter below.
		// This is required to make things work in the native query loop.
		// add_filter( 'register_block_type_args', array( $this, 'add_render_block_filter_in_core' ), 11 );
		add_filter( 'render_block', array( $this, 'render_block_dynamic_content' ), 10, 3 );

        // Register a render filter responsible for parsing `data-stk-dynamic` contents.
        add_filter( 'stackable_render_block_dynamic_content', array( $this, 'render_block_dynamic_content' ), 10, 3 );

        // Initialize Dynamic Content API for Gutenberg.
        add_action( 'rest_api_init', array( $this, 'initialize_api_routes' ) );
    }

	/**
	 * Adds the stackable_render_block_dynamic_content filter when the render_callback function is called
	 *
	 * @param Array $metadata Meta data of the block
	 * @return Array
	 */
	public function add_render_block_filter_in_core( $metadata ) {
		if ( ! is_array( $metadata ) ) {
			return $metadata;
		}

		// TODO: find a way to only do this to applicable blocks.
		$is_supported_block = true;

		// Important: Do not do this across ALL available blocks because it will slow down the site.
		if ( strpos( $metadata['name'], 'stackable/' ) !== false ) {
			$is_supported_block = true;
		}

		if ( ! $is_supported_block ) {
			return $metadata;
		}

		// Get the original render callback.
		$original_render_callback = $metadata['render_callback'];

		// Override the render callback with our own.
		$metadata['render_callback'] = function( $attributes, $content, $block = null ) use( $original_render_callback ) {
			// Call the original render callback.
			if ( is_callable( $original_render_callback ) ) {
				$content = call_user_func( $original_render_callback, $attributes, $content, $block );
			}

			// Apply our own render block filter.
			return apply_filters( 'stackable_render_block_dynamic_content', $content, $attributes, $block );
		};

		return $metadata;
	}

    /**
     * Function used for initializing all API routes.
     *
     *   API Endpoints
     *  `stackable_dynamic_content/sources`
     *  Method: GET
     *  Description: API for fetching the list of registered sources.
     *
     *  `stackable_dynamic_content/fields/`
     *  Method: GET
     *  Description: API for fetching the list of fields by source and id.
     *  Arguments:
     *  source - selected source id
     *  id - selected id
     *
     *  `stackable_dynamic_content/search/`
     *  Method: GET
     *  Description: API for fetching the list of posts by source and search term.
     *  Arguments:
     *  source - selected source id
     *  search_term - search keyword
     *
     *  `stackable_dynamic_content/content/`
     *  Method: GET
     *  Description: API for fetching the dynamic content by data-stk-dynamic content
     *  Arguments:
     *  field_data - generated data-stk-dynamic content
     *
     *  `stackable_dynamic_content/entity/`
     *  Method: GET
     *  Description: API for fetching the entity title by source and id
     *  Arguments:
     *  source - selected source id
     *  id - selected id
     *
     * @return void
     */
    public function initialize_api_routes() {
        $this->initialize_content_api();
        $this->initialize_fields_api();
        $this->initialize_sources_api();
        $this->initialize_search_api();
        $this->initialize_entity_api();
    }

    /**
     * Function for initializing the Content API.
     *
     * @return void
     */
    public function initialize_content_api() {
        $route = 'content';
        $args = array(
            'field_data' => array(
                'required' => true,
            ),
        );

        register_rest_route(
            self::STK_ROUTE_NAMESPACE,
            $route,
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'get_content' ),
                'permission_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
                'args' => $args,
            )
        );
    }

    /**
     * Function for initializing the Fields API.
     *
     * @return void
     */
    function initialize_fields_api() {
        $route = 'fields';
        $args = array(
            'source' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'id' => array(
                'sanitize_callback' => 'sanitize_text_field'
            )
        );

        register_rest_route(
            self::STK_ROUTE_NAMESPACE,
            $route,
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'get_fields' ),
                'permission_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
                'args' => $args,
            )
        );
    }

    /**
     * Function for initializing the Sources API.
     *
     * @return void
     */
    function initialize_sources_api() {
        $route = 'sources';
        $args = array();

        register_rest_route(
            self::STK_ROUTE_NAMESPACE,
            $route,
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'get_sources' ),
                'permission_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
                'args' => $args,
            )
        );
    }

    /**
     * Function for initializing the Search API.
     *
     * @return void
     */
    function initialize_search_api() {
        $route = 'search';
        $args = array(
            'search_term' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'source' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            )
        );

        register_rest_route(
            self::STK_ROUTE_NAMESPACE,
            $route,
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'get_search' ),
                'permission_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
                'args' => $args,
            )
        );
    }

    /**
     * Function for initializing the Entity API
     *
     * @return void
     */
    function initialize_entity_api() {
        $route = 'entity';
        $args = array(
            'id' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'source' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            )
        );

        register_rest_route(
            self::STK_ROUTE_NAMESPACE,
            $route,
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'get_entity' ),
                'permission_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
                'args' => $args,
            )
        );
    }

    /**
     * Function for getting the dynamic content by passed
     * field_data
     *
     * @param string $field_data
     * @param boolean if true, the content will be passed inside the editor. otherwise, in the frontend.
     * @param array|null block context
     * @return string content
     */
    public static function get_dynamic_content( $field_data, $is_editor_content = false, $context = null, $block_name = null, $block_attributes = null ) {

        $args = Util::parse_field_data( $field_data );
		if ( isset( $context ) && isset( $context[ 'postId' ] ) && $args['source'] === 'current-page' ) {
            $args[ 'id' ] = $context[ 'postId' ];
        }

		// Allow plugins to modify the id before getting the dynamic content.
		$args['id'] = apply_filters( self::STK_NAMESPACE . '/get_dynamic_content/id', $args['id'], $args, $field_data, $is_editor_content, $context, $block_name, $block_attributes );

        $args['id'] = apply_filters( self::STK_NAMESPACE . '/' . $args['source'] . '/custom_id', $args['id'], $is_editor_content, $block_name, $block_attributes );

        $fields_data = Stackable_Dynamic_Content::get_fields_data( $args['source'], $args['id'], $is_editor_content, $block_name, $block_attributes );

        $args['field_data'] = array();

        if ( array_key_exists( $args['field'], $fields_data ) && array_key_exists( 'data', $fields_data[ $args['field'] ] ) ) {
          $args['field_data'] = $fields_data[ $args['field'] ]['data'];
        }

        $args['is_editor_content'] = $is_editor_content;
        $output = null;
        $output = apply_filters( self::STK_NAMESPACE . '/' . $args['source'] . '/content', $output, $args, $is_editor_content, $block_name, $block_attributes );
        $output = apply_filters( self::STK_NAMESPACE . '/' . $args['source'] . '/' . $args['field'], $output, $args, $is_editor_content, $block_name, $block_attributes );
        $output = apply_filters( self::STK_NAMESPACE . '/' . $args['source'] . '/' . $args['field'] . '/' . $args['id'], $output, $args, $is_editor_content, $block_name, $block_attributes );

        if ( is_array( $output ) && array_key_exists( 'error', $output ) ) {
            return $output;
        }

        if ( $output === null ) {
            return array(
                'error' => __( 'Invalid parameters. Please try again.', STACKABLE_I18N ),
            );
        }

        return Util::is_valid_output( $output ) ? wp_kses_post( $output ) : '';
    }

    /**
     * Function for handling the API callback.
     *
     * @param WPRequest request object
     * @return string generated content
     */
    public function get_content( $request ) {
		$field_data = $request->get_param( 'field_data' );
		$block_name = $request->get_param( 'block_name' );
		$block_attributes = $request->get_param( 'block_attributes' );
		return Stackable_Dynamic_Content::get_dynamic_content( $field_data , true, null, $block_name, $block_attributes );
    }

    /**
     * Function for getting the fields data.
     *
     * @param string $source the selected source
     * @param string $id the selected id
     * @param boolean $is_editor_content
     *
     * @return array all fields.
     */
    public static function get_fields_data( $source, $id, $is_editor_content = false, $block_name = null, $block_attributes = null) {
        $id = apply_filters( self::STK_NAMESPACE . '/' . $source . '/custom_id', $id, $is_editor_content, $block_name, $block_attributes );
        $output = apply_filters( self::STK_NAMESPACE . '/' . $source . '/fields', array(), $id, $is_editor_content, $block_name, $block_attributes );

        return $output;
    }

    /**
     * Function for handling the API callback.
     *
     * @param WPRequest request object
     * @return array fields
     */
    function get_fields( $request ) {
        $source = $request->get_param( 'source' );
        $id = $request->get_param( 'id' );
        $block_name = $request->get_param( 'block_name' );
        $block_attributes = $request->get_param( 'block_attributes' );
        return Stackable_Dynamic_Content::get_fields_data( $source, $id, true, $block_name, $block_attributes );
    }

    /**
     * Function for handling the API callback.
     *
     * @return array sources
     */
    function get_sources($request) {
        $block_name = $request->get_param( 'block_name' );
        $block_attributes = $request->get_param( 'block_attributes' );
        return apply_filters( self::STK_NAMESPACE . '/sources', array(), $block_name, $block_attributes );
    }

    /**
     * Function for handling the API callback.
     *
     * @param WPRequest request object
     * @return array search result
     */
    function get_search( $request ) {
        $search_term = $request->get_param( 'search_term' );
        $source = $request->get_param( 'source' );
        $block_name = $request->get_param( 'block_name' );
        $block_attributes = $request->get_param( 'block_attributes' );
        $search = apply_filters( self::STK_NAMESPACE . '/' . $source . '/search', null, $search_term, $block_name, $block_attributes );

        return ( ! is_array( $search ) ) ? array() : $search;
    }

    /**
     * Function for handling the API callback.
     *
     * @param WPRequest request object
     * @return string entity title
     */
    function get_entity( $request ) {
        $source = $request->get_param( 'source' );
        $block_name = $request->get_param( 'block_name' );
        $block_attributes = $request->get_param( 'block_attributes' );
        $id = apply_filters( 'stackable_dynamic_content/' . $source . '/custom_id', $request->get_param( 'id' ), true, $block_name, $block_attributes );
        $entity = apply_filters( self::STK_NAMESPACE . '/' . $source . '/entity', '', $id, $block_name, $block_attributes );

        return $entity;
    }

    /**
     * Function for handling render_block filter in Frontend.
     *
     * @param string $block_content stringified block content.
     * @return string new block content.
     */
    function render_block_dynamic_content( $block_content, $attributes, $block = null ) {
		if ( $block_content === null ) {
			return $block_content;
		}

        /**
         * Only do this when `stk-dynamic-content` string matches the block content or
         * `!#stk_dynamic/(.*)!#` is present.
         */
        if (
            strpos( $block_content, 'class="stk-dynamic-content"' ) === false &&
            strpos( $block_content, '!#stk_dynamic/' ) === false
        ) {
            return $block_content;
        }

		/**
		 * Query loops generate duplicate blocks with duplicate uniqueIds, this
		 * will lead our blocks to generate clashing CSS selectors. Generate a
		 * new uniqueId for duplicated blocks.
		 *
		 * Duplicated uniqueIds only matters in dynamic content so that blocks
		 * can generate unique styles based on the dynamic field used.
		 */
		if ( array_key_exists( 'uniqueId', $attributes ) ) {
			$unique_id = $new_unique_id = $attributes['uniqueId'];
			$i = 2;
			while ( in_array( $new_unique_id, $this->rendered_unique_ids ) ) {
				$new_unique_id = $unique_id . '-' . $i++;
			}
			$this->rendered_unique_ids[] = $new_unique_id;

			if ( $unique_id !== $new_unique_id ) {
				$block_content = str_replace( "data-block-id=\"$unique_id\"", "data-block-id=\"$new_unique_id\"", $block_content );
				$block_content = str_replace( "stk-$unique_id", "stk-$new_unique_id", $block_content );
			}
		}

        /**
         * Dynamic Content Parsers.
         *
         * the `markup_regexp` is a regular expression for getting the entire markup string which will be
         * replaced by the dynamic content.
         *
         * the `field_data_regexp` is a regular expression for getting the field data.
         */
        $parse_handlers = array(
            /**
             * Check `stk-dynamic-content` instances.
             */
            array(
                'markup_regexp' => '/<span data-stk-dynamic="[^"]*"[^>]*>(.*?)<\/span>/',
                'field_data_regexp' => '/data-stk-dynamic="(.*?(?="))"/',
            ),
            /**
             * Check `!#stk_dynamic/` instances.
             */
            array(
                'markup_regexp' => '/\!#stk_dynamic\/(.*?)\!#/',
                'field_data_regexp' => '/\!#stk_dynamic\/(.*?)\!#/'
            )
        );

        foreach ( $parse_handlers as $parse_handler ) {
            preg_match_all( $parse_handler['markup_regexp'], $block_content, $matches );
            $stk_dynamic_content_instances = $matches[0];
            if ( ! is_array( $stk_dynamic_content_instances ) ) {
                continue;
            }
            foreach ( $stk_dynamic_content_instances as $dynamic_entry ) {
                preg_match( $parse_handler['field_data_regexp'], $dynamic_entry, $route );
                $args = Util::parse_field_data( $route[1] );
				$args['id'] = apply_filters( 'stackable_dynamic_content/' . $args['source'] . '/custom_id', $args['id'], false, $block->name, $attributes );
                $context = isset( $block->context ) ? $block->context : null;
                $output = Stackable_Dynamic_Content::get_dynamic_content( $route[1], false, $context, $block->name, $attributes );
                if ( Util::is_valid_output( $output ) ) {
                    $block_content = str_replace( $dynamic_entry, $output, $block_content );
					$block_content = apply_filters( 'stackable_dynamic_content/' . $args['source'] . '/block_content', $block_content, $args, $block->name, $attributes );
                    $block_content = apply_filters( 'stackable_dynamic_content/' . $args['source'] . '/' . $args['field'] . '/block_content', $block_content, $args, $block->name, $attributes );
                } else {
                    $block_content = str_replace( $dynamic_entry, '', $block_content );
                }
            }
        }

        return $block_content;
    }
}

new Stackable_Dynamic_Content();
