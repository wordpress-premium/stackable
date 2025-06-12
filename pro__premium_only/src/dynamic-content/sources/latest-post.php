<?php
namespace Stackable\DynamicContent\Sources;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Latest_Post {

    private $source_slug = 'latest-post';
    private $post_types = array();

    function __construct() {
        add_filter( "stackable_dynamic_content/sources", array( $this, 'initialize_source' ), 2 );

        $this->get_post_types();
        add_filter( "stackable_dynamic_content/$this->source_slug/search", array( $this, 'search_entities' ), 1, 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/entity", array( $this, 'get_entity' ), 1, 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/custom_id", array( $this, 'get_custom_id' ), 1, 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/fields", array( __NAMESPACE__ . '\Other_Posts', 'initialize_fields' ), 1, 3 );
        add_filter( "stackable_dynamic_content/$this->source_slug/fields", array( __NAMESPACE__ . '\Other_Posts', 'initialize_other_fields' ), 100, 3 );
        add_filter( "stackable_dynamic_content/$this->source_slug/content", array( __NAMESPACE__ . '\Other_Posts', 'get_content' ), 1, 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/content", array( __NAMESPACE__ . '\Other_Posts', 'get_custom_field_content' ), 1, 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/content", array( $this, 'get_content' ), 100, 3 );
        add_filter( "stackable_dynamic_content/$this->source_slug/block_content", array( __NAMESPACE__ . '\Other_Posts', 'get_block_content' ), 1, 2 );
    }

    /**
     * Initializes the $post_types
     */
    public function get_post_types() {
        global $wp_post_types;
        foreach ( Util::get_supported_post_type_slugs() as $slug ) {
            $plural_name = &$wp_post_types[ $slug ]->labels->name;
            $singular_name = &$wp_post_types[ $slug ]->labels->singular_name;
            array_push( $this->post_types, array(
                'slug' => $slug,
                'singular_name' => $singular_name,
                'plural_name' => $plural_name,
            ) );
        }
    }

    /**
     * Function for registering the source.
     *
     * @param array previous sources object.
     * @return array newly generated $sources object.
     */
    public function initialize_source( $sources ) {
        $sources[ $this->source_slug ] = array(
            'title' => __( 'Latest Post', STACKABLE_I18N ),
            'with_input_box' => true,
            'with_search' => true,
            // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
            'input_label' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( 'Nth', STACKABLE_I18N ), __( 'Post', STACKABLE_I18N ) ),
            // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
            'input_placeholder' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '1st', STACKABLE_I18N ), __( 'Post', STACKABLE_I18N ) ),
        );

        return $sources;
    }

    /**
     * Function for getting the entity ID by nth index.
     *
     * @param string nth latest entity
     * @return string entity ID
     */
    public function get_entity_id( $entity_slug, $id ) {
        $entity_index = (int)( $id ) - 1;
        $recent_entities = wp_get_recent_posts(
            array(
                'post_type' => $entity_slug,
                'numberposts' => 10,
                'post_status' => 'publish',
            )
        );

        if ( count( $recent_entities ) <= $entity_index ) {
            return -1;
        }

        return $recent_entities[ $entity_index ]['ID'];
    }

    /**
     * Function for handling the search query of
     * latest entities
     *
     * @param string entity slug
     * @param string singular name of the entity
     * @param string plural name of the entity
     * @param string search term
     * @return array generated search result
     */
    public function generate_entity_search_list( $entity_slug, $singular_name, $plural_name, $s ) {
        $search_result = array(
            $entity_slug . '-1' => array(
                // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
                'title' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '1st', STACKABLE_I18N ), $singular_name ),
                'group' => $plural_name,
            ),
            $entity_slug . '-2' => array(
                // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
                'title' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '2nd', STACKABLE_I18N ), $singular_name ),
                'group' => $plural_name,
            ),
            $entity_slug . '-3' => array(
                // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
                'title' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '3rd', STACKABLE_I18N ), $singular_name ),
                'group' => $plural_name,
            ),
            $entity_slug . '-4' => array(
                // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
                'title' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '4th', STACKABLE_I18N ), $singular_name ),
                'group' => $plural_name,
            ),
            $entity_slug . '-5' => array(
                // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
                'title' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '5th', STACKABLE_I18N ), $singular_name ),
                'group' => $plural_name,
            ),
            $entity_slug . '-6' => array(
                // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
                'title' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '6th', STACKABLE_I18N ), $singular_name ),
                'group' => $plural_name,
            ),
            $entity_slug . '-7' => array(
                // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
                'title' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '7th', STACKABLE_I18N ), $singular_name ),
                'group' => $plural_name,
            ),
            $entity_slug . '-8' => array(
                // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
                'title' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '8th', STACKABLE_I18N ), $singular_name ),
                'group' => $plural_name,
            ),
            $entity_slug . '-9' => array(
                // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
                'title' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '9th', STACKABLE_I18N ), $singular_name ),
                'group' => $plural_name,
            ),
            $entity_slug . '-10' => array(
                // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
                'title' => sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '10th', STACKABLE_I18N ), $singular_name ),
                'group' => $plural_name,
            ),
        );

        if ( empty( $s ) ) {
            return $search_result;
        }

        $output = array();

        foreach ( $search_result as $key => $string ) {
            if ( strpos( $string['title'], $s ) !== FALSE ) {
                $output[ $key ] = $string;
            }
        }

        return $output;
    }

    /**
     * Function for handling the search post field.
     *
     * @param array previous output value
     * @param string keyword
     * @return array post data object
     */
    public function search_entities( $output, $s ) {
        $search_result = array();
        foreach ( $this->post_types as $post_type ) {
            $search_result = array_merge( $search_result, $this->generate_entity_search_list( $post_type['slug'], $post_type['singular_name'], $post_type['plural_name'], $s ) );
        }

        return $search_result;
    }

    /**
     * Gets the singular name of a post type by entity slug
     *
     * @param string entity slug
     * @return string singular name
     */
    public function get_singular_name_by_slug( $entity_slug ) {
        foreach ( $this->post_types as $post_type ) {
            if ( $post_type['slug'] === $entity_slug ) {
                return $post_type['singular_name'];
            }
        }

        return '';
    }

    /**
     * Function for handling a single entity title.
     * Mainly used for displaying the title
     * of the entity on load.
     *
     * @param string previous output value
     * @param string id
     * @return array entity info.
     */
    public function get_entity( $output, $id ) {
        $entity_id_array = explode( '-', $id );

        if ( count( $entity_id_array ) < 2 ) {
            return '';
        }

        $entity_slug = $entity_id_array[ 0 ];
        $id = end( $entity_id_array );

        if ( count( $entity_id_array ) > 2 ) {
            $entity_slug = implode( '-', array_splice( $entity_id_array, 0, count( $entity_id_array ) - 1 ) );
        }

        $singular_name = $this->get_singular_name_by_slug( $entity_slug );

        if ( empty( $singular_name ) ) {
            return '';
        }

        switch ( $id ) {
            // translators: first %s is an ordinal number (e.g. 1st, 2nd), second %s is the name of the entity (e.g. Post, Page)
            case '1': return sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '1st', STACKABLE_I18N ), $singular_name );
            case '2': return sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '2nd', STACKABLE_I18N ), $singular_name );
            case '3': return sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '3rd', STACKABLE_I18N ), $singular_name );
            case '4': return sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '4th', STACKABLE_I18N ), $singular_name );
            case '5': return sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '5th', STACKABLE_I18N ), $singular_name );
            case '6': return sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '6th', STACKABLE_I18N ), $singular_name );
            case '7': return sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '7th', STACKABLE_I18N ), $singular_name );
            case '8': return sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '8th', STACKABLE_I18N ), $singular_name );
            case '9': return sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '9th', STACKABLE_I18N ), $singular_name );
            case '10': return sprintf( __( '%s Latest %s', STACKABLE_I18N ), __( '10th', STACKABLE_I18N ), $singular_name );
            default: return '';
        }
    }

    /**
     * Overwrites the passed id.
     * Since latest entity source uses
     * nth index as id, change the id into a real
     * entity id.
     *
     * @param string id.
     * @return new id.
     */
    public function get_custom_id( $id, $is_editor_content ) {
        $entity_id_array = explode( '-', $id );

        if ( count( $entity_id_array ) < 2 ) {
            return $id;
        }

        $entity_slug = $entity_id_array[ 0 ];
        $id = end( $entity_id_array );

        if ( count( $entity_id_array ) > 2 ) {
            $entity_slug = implode( '-', array_splice( $entity_id_array, 0, count( $entity_id_array ) - 1 ) );
        }

        return $this->get_entity_id( $entity_slug, $id ) !== -1 ? $this->get_entity_id( $entity_slug, $id ) : $id;
    }

    /**
     * Function for getting the content values.
     * This is an editor content handler which
     * only displays the field slug and `Placeholder`.
     *
     * @param any previous output
     * @param array parsed args
     * @param boolean is_editor_content
     * @return string generated value.
     */
    public function get_content( $output, $args, $is_editor_content ) {
        if ( $is_editor_content && Util::is_valid_output( $output ) && empty( $output ) ) {
            $fields = \Stackable\DynamicContent\Stackable_Dynamic_Content::get_fields_data( $args['source'], $args['id'], $is_editor_content );
            $field = $fields[ $args['field'] ];
            return sprintf( __( '%s Placeholder', STACKABLE_I18N ), $field['title'] );
        }

        return $output;
    }
}

new Latest_Post();
