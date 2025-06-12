<?php
namespace Stackable\DynamicContent\Sources;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for adding custom fields as a source
 *
 * @author Carlo Acosta
 */
class Custom_Fields {
    function __construct() {
        add_filter( 'stackable_dynamic_content/site/fields', array( $this, 'initialize_fields' ), 2, 1 );
        add_filter( 'stackable_dynamic_content/site/content', array( $this, 'get_content' ), 2, 2 );
    }

    /**
     * Function for initializing the fields.
     *
     * @param string previous generated output
     *
     * @return array generated fields.
     */
    public function initialize_fields( $output ) {
        if ( ! stackable_is_custom_fields_enabled() ) {
            return $output;
        }

        $custom_fields = get_option( 'stackable_custom_fields' );

        if ( empty( $custom_fields ) ) {
            return $output;
        }

        foreach ( $custom_fields as $custom_field ) {
			$field_metadata = array(
				'title' => $custom_field['name'],
				'group' => __( 'Stackable Custom Fields', STACKABLE_I18N ),
				'data' => array(
					'field_type' => 'custom_fields',
					'type' => $custom_field['type'],
				),
			);

			/**
			 * Populate `type` properties for Stackable Fields link and image url fields.
			 */
			if ( $custom_field['type'] === 'url' ) {
				$field_metadata['type'] = 'link';
			}

			$output[ $custom_field['slug'] ] = $field_metadata;
        }

        return $output;
    }

     /**
     * Function for getting the content values.
     *
     * @param string previous output.
     * @param array field arguments.
     *
     * @return string generated value.
     */
    public function get_content( $output, $args ) {
        if ( ! array_key_exists( 'field_data', $args ) || ! array_key_exists( 'field_type', $args['field_data'] ) ) {
			return $output;
		}
        if ( $args['field_data']['field_type'] !== 'custom_fields' ) {
            return $output;
        }

        $current_field = $this->find_custom_field_by_slug( $args['field'] );

        if ( is_null( $current_field ) ) {
            return $output;
        }

        if ( ! array_key_exists( 'value', $current_field ) ) {
            $current_field['value'] = '';
        }

        switch ( $current_field['type'] ) {
            case 'text':
            case 'number':
                return $current_field['value'];
            case 'date':
            case 'time':
                return $this->render_date( $current_field, $args );
            case 'url':
                return $this->render_link( $current_field, $args );
            default: return array(
                'error' => __( 'The field type provided is not valid.', STACKABLE_I18N )
            );
        }
    }

    /**
     * Function for getting date types
     *
     * @param array field data.
     * @param array arguments.
     *
     * @return string generated content.
     */
    public function render_date( $field, $args ) {
        if ( array_key_exists( 'format', $args['args'] ) ) {
            return Util::format_date( $field['value'], $args['args']['format'] );
        }
        return $field['value'];
    }

    /**
     * Function for getting the link content.
     *
     * @param array field data.
     * @param array arguments.
     *
     * @return string generated content.
     */
    public function render_link( $field, $args ) {
        $output = '';

		if ( ! array_key_exists( 'value', $field ) ) {
			return $output;
		}

        if ( ! array_key_exists( 'with_link', $args['args'] ) || $args['args']['with_link'] === 'false' ) {
            return array_key_exists( 'url', $field['value']  ) ? $field['value']['url'] : '';
        }

        if ( array_key_exists( 'title', $field['value'] ) && $field['value']['title'] !== '' ) {
            $output = $field['value']['title'];
        }
        else if ( array_key_exists( 'url', $field['value'] ) && $field['value']['url'] !== '' ) {
            $output = $field['value']['url'];
        }

        if ( ! is_array( $field['value'] ) || ! array_key_exists( 'url', $field['value'] ) ) {
            return $output;
        }

        $href = $field['value']['url'];
        $new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
        $output = Util::make_output_link( $output, $href, $new_tab );

        return $output;
    }

    /**
     * Function for looking for the custom field using its slug.
     * @param string slug.
     *
     * @return array corresponding custom field.
     */
    public function find_custom_field_by_slug( $slug ) {
        $custom_fields = get_option( 'stackable_custom_fields' );

        if ( $custom_fields === false ) {
            return null;
        }

        foreach ( $custom_fields as $custom_field ) {
            if ( $custom_field['slug'] == $slug ) {
                return $custom_field;
            }
        }
        return null;
    }
}

new Custom_Fields();
