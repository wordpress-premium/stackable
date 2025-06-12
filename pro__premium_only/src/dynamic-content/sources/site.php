<?php
namespace Stackable\DynamicContent\Sources;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Site {

    private $source_slug = 'site';

    function __construct() {
        add_filter( "stackable_dynamic_content/sources", array( $this, 'initialize_source' ), 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/fields", array( $this, 'initialize_fields' ), 1 );
        add_filter( "stackable_dynamic_content/$this->source_slug/content", array( $this, 'get_content' ), 1, 2 );
    }

    /**
     * Function for registering the source.
     *
     * @param array previous sources object.
     * @return array newly generated $sources object.
     */
    public function initialize_source( $sources ) {
        $sources[ $this->source_slug ] = array(
            'title' => __( 'Site', STACKABLE_I18N ),
        );

        return $sources;
    }

    /**
     * Function for initializing the fields.
     *
     * @param array previous field values.
     * @return array generated fields.
     */
    public function initialize_fields( $output ) {
        return array_merge(
            $output,
            array(
                'site-tagline' => array(
                    'title' => __( 'Site Tagline', STACKABLE_I18N ),
                    'group' => __( 'Site', STACKABLE_I18N ),
                ),
                'site-title' => array(
                    'title' => __( 'Site Title', STACKABLE_I18N ),
                    'group' => __( 'Site', STACKABLE_I18N ),
                ),
                'site-url' => array(
                    'title' => __( 'Site URL', STACKABLE_I18N ),
                    'group' => __( 'Site', STACKABLE_I18N ),
                    'type' => 'link'
				),
            )
        );
    }

    /**
     * Function for getting the content values.
     *
     * @param any previous output
     * @param array parsed args
     * @return string generated value.
     */
    public function get_content( $output, $args ) {
        if ( Util::is_valid_output( $output ) ) {
            return $output;
        }

        switch ( $args['field'] ) {
            case 'site-tagline': return self::render_site_tagline( $args );
            case 'site-title': return self::render_site_title( $args );
            case 'site-url': return self::render_site_url( $args );
            default: return array(
                'error' => __( 'The field type provided is not valid.', STACKABLE_I18N )
            );
        }
    }

    /**
     * Function for displaying the site-tagline content.
     *
     * @param array parsed args
     * @return string generated output
     */
    public static function render_site_tagline( $args ) {
        return wp_kses_post( get_bloginfo( 'description' ) );
    }

    /**
     * Function for displaying the site-title content.
     *
     * @param array parsed args
     * @return string generated output
     */
    public static function render_site_title( $args ) {
        $output = wp_kses_post( get_bloginfo( 'title' ) );

        if ( ! array_key_exists( 'with_link', $args['args'] ) || $args['args']['with_link'] === 'false' ) {
            return $output;
        }

        $href = Site::get_site_url();
        $new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
        return Util::make_output_link( $output, $href, $new_tab );
    }

    /**
     * Function for getting the site URL.
     *
     * @return string site url
     */
    public static function get_site_url() {
        return get_bloginfo( 'url' );
    }

    /**
     * Function for displaying the site-url content.
     *
     * @param array parsed args
     * @return string generated output
     */
    public static function render_site_url( $args ) {
        $output = Site::get_site_url();

        if ( ! array_key_exists( 'with_link', $args['args'] ) || $args['args']['with_link'] === 'false' ) {
            return $output;
        }

        if ( ! array_key_exists( 'text', $args['args'] ) || empty( $args['args']['text'] ) ) {
            return array(
                'error' => __( 'Text input is empty', STACKABLE_I18N )
            );
        }

        $href = $output;
        $output = $args['args']['text'];
        $new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
        return Util::make_output_link( $output, $href, $new_tab );
    }
}

new Site();
