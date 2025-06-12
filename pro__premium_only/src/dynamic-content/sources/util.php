<?php
namespace Stackable\DynamicContent\Sources;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Util {
    /**
     * Function for formatting a date.
     *
     * @param string $date
     * @param string $format
     *
     * @return string generated date.
     */
    public static function format_date( $date, $format ) {
        // Use date_i18n to format according to site language
        return (string) date_i18n( $format, strtotime( $date ) );
    }

    /**
     * Function for parsing the `data-stk-dynamic` content to readable format.
     *
     * @example
     * ```
     * $this->parse_field_data( 'current-post/post-title/123?arg1=value_of_arg1' );
     * ```
     *
     * returns array(
     *      'route' => 'current-post/post-title?arg1=value_of_arg1',
     *      'source' => 'current-post',
     *      'field' => 'post-title',
     *      'id' => '123', 'args' => array(
     *          'arg1' => 'value_of_arg1',
     *      ),
     * )
     *
     * @param string data-stk-dynamic content
     * @return array parsed args
     */
    public static function parse_field_data( $data ) {
        $field_data = parse_url( $data );
        parse_str( html_entity_decode( $data ), $output );
        $route = explode( '/', $field_data['path'] );

        $args = array();
        $args['route'] = $data;
        $args['source'] = $route[0];
        $args['field'] = $route[1];
        $args['args'] = array();

        if ( array_key_exists( 'query', $field_data ) ) {
            parse_str( html_entity_decode( $field_data['query'] ), $route_args );
            $args['args'] = $route_args;
        }

        if ( array_key_exists( 2, $route ) && ( ! empty( $route[2] ) && $route[2] !== 'current' ) ) {
            $args['id'] = $route[2];
        } else {
            $args['id'] = get_the_ID();

			// Sometimes the function get_the_ID() may return 0, so try a different way to retrieve the post ID
			if ( $args['id'] == 0 ) {
				$args['id'] = get_queried_object_id();
			}

			$args['id'] = apply_filters( 'stackable_dynamic_content/parse_field_data/current_post_id', $args['id'], $field_data, $route );
        }

        return $args;
    }

    /**
     * Utility function for converting an output into a link.
     *
     * @param string dynamic content
     * @param string url
     * @param boolean if true, `target="_blank"` attribute will be added.
     * @return string link
     */
    public static function make_output_link( $output, $href, $new_tab ) {
        return sprintf( '<a href="%s"%s>%s</a>', $href, $new_tab ? ' target="_blank" rel="noreferrer noopener"' : '', $output );
    }

    /**
     * Gets supported custom post types.
     *
     * @return array post type slugs.
     */
    public static function get_supported_post_type_slugs() {
        $excluded_post_types = apply_filters( 'stackable_dynamic_content/exclude_post_types', [
            'attachment',
            'revision',
            'wp_block',
            'nav_menu_item',
            'custom_css',
            'customize_changeset',
            'oembed_cache',
            'user_request',
            'acf-field',
            'acf-field-group',
            'wp_template'
        ] );

        $output = array();
        foreach ( get_post_types() as $post_type ) {
            if ( ! in_array( $post_type, $excluded_post_types ) ) {
                array_push( $output, $post_type );
            }
        }

        return $output;
    }

    /**
     * Checks whether the output is a valid output.
     *
     * @param any dynamic content
     * @return boolean if true, the output if valid. otherwise, not valid.
     */
    public static function is_valid_output( $output ) {
        if (
            is_string( $output ) ||
            is_numeric( $output )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Gets the post excerpt.
     *
     * @since 3.0.0
     * @param string post ID
     * @param array post
     *
     * @return string the generated excerpt
     */
    public static function get_excerpt( $post_id, $post = null ) {
        // Remove jetpack sharing button.
        add_filter( 'sharing_show', '__return_false' );
        $excerpt = get_post_field( 'post_excerpt', $post_id, 'display' );
        // We need to check before running the filters since some plugins override it.
        if ( ! empty( $excerpt ) ) {
          $excerpt = apply_filters( 'the_excerpt', $excerpt );
        }

        if ( empty( $excerpt ) ) {
          $max_excerpt = 100; // WP default is 55.

          // If there's post content given to us, trim it and use that.
          if ( ! empty( $post['post_content'] ) ) {
            $excerpt = apply_filters( 'the_excerpt', wp_trim_words( $post['post_content'], $max_excerpt ) );
          } else {
            // If there's no post content given to us, then get the content.
            $post_content = get_post_field( 'post_content', $post_id );
            if ( ! empty( $post_content ) ) {
              // Remove the jetpack sharing button filter.
              $post_content = apply_filters( 'the_content', $post_content );
              $excerpt = apply_filters( 'the_excerpt', wp_trim_words( $post_content, $max_excerpt ) );
            }
          }
        }

        // Remove the jetpack sharing button filter.
        remove_filter( 'sharing_show', '__return_false' );
        return empty( $excerpt ) ? "" : $excerpt;
    }

    /**
     * Trim potential newline (\n) in a string.
     *
     * @param string $string
     *
     * @return string normalized string.
     */
    public static function str_trim_newline( $string ) {
        return trim( preg_replace( '/\s\s+/', ' ', $string ) );
    }
}
