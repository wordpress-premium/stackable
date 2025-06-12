<?php
namespace Stackable\DynamicContent\Sources;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Current_Page {

    private $source_slug = 'current-page';

    function __construct() {
        add_filter( "stackable_dynamic_content/sources", array( $this, 'initialize_source' ), 1 );
        add_filter( "stackable_dynamic_content/$this->source_slug/fields", array( __NAMESPACE__ . '\Other_Posts', 'initialize_fields' ), 1, 3 );
        add_filter( "stackable_dynamic_content/$this->source_slug/fields", array( __NAMESPACE__ . '\Other_Posts', 'initialize_other_fields' ), 100, 3 );
        add_filter( "stackable_dynamic_content/$this->source_slug/entity", array( __NAMESPACE__ . '\Other_Posts', 'get_entity' ), 1, 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/content", array( __NAMESPACE__ . '\Other_Posts', 'get_content' ), 1, 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/content", array( __NAMESPACE__ . '\Other_Posts', 'get_custom_field_content' ), 50, 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/block_content", array( __NAMESPACE__ . '\Other_Posts', 'get_block_content' ), 1, 2 );
    }

    /**
     * Function for registering the source.
     *
     * @param array previous sources object.
     * @return array newly generated $sources object.
     */
    function initialize_source( $sources ) {
        $sources[ $this->source_slug ] = array(
            'title' => __( 'Current Post', STACKABLE_I18N ),
        );

        return $sources;
    }
}

new Current_Page();
