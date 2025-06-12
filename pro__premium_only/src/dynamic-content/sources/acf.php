<?php
namespace Stackable\DynamicContent\Sources;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stackable Dynamic Content ACF
 * integration
 */

class ACF {
    function __construct() {
        add_filter( "stackable_dynamic_content/current-page/fields", array( $this, 'initialize_fields' ), 2, 3 );
        add_filter( "stackable_dynamic_content/other-posts/fields", array( $this, 'initialize_fields' ), 2, 3 );
        add_filter( "stackable_dynamic_content/latest-post/fields", array( $this, 'initialize_fields' ), 2, 3 );

        add_filter( "stackable_dynamic_content/current-page/content", array( $this, 'get_content' ), 2, 3 );
        add_filter( "stackable_dynamic_content/other-posts/content", array( $this, 'get_content' ), 2, 3);
        add_filter( "stackable_dynamic_content/latest-post/content", array( $this, 'get_content' ), 2, 3 );

        add_filter( "stackable_dynamic_content/site/fields", array( $this, 'initialize_option_fields' ), 2, 2 );
        add_filter( "stackable_dynamic_content/site/content", array( $this, 'get_option_content' ), 2, 3 );
    }

    function get_acf_fields_by_field_groups( $field_groups ) {
        $output = array();

        if ( ! function_exists( 'acf_get_fields' ) ) {
            return $output;
        }

        $excluded_field_type = [
            'oembed',
            'gallery',
            'post_object',
            'relationship',
            'google_map',
            'message',
            'accordion',
            'tab',
            'group',
            'repeater',
            'flexible_content',
            'clone',
        ];

        foreach ( $field_groups as $field_group ) {
            $key = $field_group['key'];
            $title = $field_group['title'];
            foreach( acf_get_fields( $key ) as $acf_field ) {
                if ( ! empty( $acf_field['name'] ) && ! in_array( $acf_field['type'], $excluded_field_type ) ) {
                    $field_metadata = array(
                        'title' => $title . ' - ' . $acf_field['label'],
                        'group' => __( 'ACF' , STACKABLE_I18N ),
                        'data' => array(
                            'field_type' => 'acf',
                            'type' => $acf_field['type'],
                        ),
                    );

                    /**
                     * Populate `type` properties for ACF link and image url fields.
                     */
                    if ( in_array( $acf_field['type'], [ 'page_link', 'url', 'link', 'file' ] ) ) {
                        $field_metadata['type'] = 'link';
                    }

                    if ( in_array( $acf_field['type'], [ 'image' ] ) ) {
                        $field_metadata['type'] = 'image-url';
                    }

                    $output[ $acf_field['name'] ] = $field_metadata;
                }
            }
        }

        return $output;
    }

    /**
     * Function for initializing the fields.
     *
     * @param string previous generated output
     * @param string post/page ID
     * @return array generated fields.
     */
    function initialize_fields( $output, $id, $is_editor_content ) {
        if ( ! function_exists( 'acf_get_field_groups' ) ) {
            return $output;
        }

        $entity_id_array = explode( '-', $id );

        if ( count( $entity_id_array ) < 2 ) {
            if ( count( $entity_id_array ) === 1 ) {
                if ( $is_editor_content ) {
                    return array_merge(
                        $output,
                        $this->get_acf_fields_by_field_groups( acf_get_field_groups() )
                    );
                }

                return array_merge(
                    $output,
                    $this->get_acf_fields_by_field_groups( acf_get_field_groups() )
                );
            }

            return $output;
        }

        $entity_slug = $entity_id_array[0];
        $id = end( $entity_id_array );

        if ( count( $entity_id_array ) > 2 ) {
            $entity_slug = implode( '-', array_splice( $entity_id_array, 0, count( $entity_id_array ) - 1 ) );
        }

        return array_merge(
            $output,
            $this->get_acf_fields_by_field_groups( acf_get_field_groups() )
        );
    }

    /**
     * Function for initializing the option fields.
     *
     * @param string previous generated output
     * @param string post/page ID
     * @return array generated fields.
     */
    function initialize_option_fields( $output ) {
        if ( ! function_exists( 'acf_get_field_groups' ) ) {
            return $output;
        }

        return array_merge(
            $output,
            $this->get_acf_fields_by_field_groups( acf_get_field_groups( array( 'options_page' => true ) ) )
        );
    }

    /**
     * Function for getting the content values.
     *
     * @param string previous output.
     * @param array field arguments.
     *
     * @return string generated value.
     */
    function get_content( $output, $args, $is_editor_content ) {
        if ( ! array_key_exists( 'field_data', $args ) || ! array_key_exists( 'field_type', $args['field_data'] ) ) {
          return $output;
        }

        if ( $args['field_data']['field_type'] !== 'acf' ) {
            return $output;
        }

        switch ( $args['field_data']['type'] ) {
            case 'textarea':
                return $this->render_text_area( $args, $is_editor_content );
            case 'text':
            case 'number':
            case 'email':
            case 'password':
            case 'message':
            case 'color_picker':
                return $this->render_general_content( $args, $is_editor_content );
            case 'wysiwyg':
                return $this->render_general_content_with_placeholder( $args, $is_editor_content );
            case 'page_link':
            case 'url':
                return $this->render_general_link( $args, $is_editor_content );
            case 'select':
            case 'radio':
            case 'button_group':
                return $this->render_choice( $args, $is_editor_content );
            case 'checkbox':
                return $this->render_checkbox( $args, $is_editor_content );
            case 'user':
                return $this->render_user( $args, $is_editor_content );
            case 'date_picker':
            case 'date_time_picker':
            case 'time_picker':
                return $this->render_date( $args, $is_editor_content );
            case 'true_false':
                return $this->render_true_false( $args, $is_editor_content );
            case 'link':
                return $this->render_link( $args, $is_editor_content );
            case 'taxonomy':
                return $this->render_taxonomy( $args, $is_editor_content );
            case 'image':
                return $this->render_image( $args, $is_editor_content );
            case 'file':
                return $this->render_file( $args, $is_editor_content );
            default: return array(
                'error' => __( 'The field type provided is not valid.', STACKABLE_I18N )
            );
        }
    }

    function get_option_content( $output, $args, $is_editor_content ) {
        if ( ! array_key_exists( 'field_data', $args ) || ! array_key_exists( 'field_type', $args['field_data'] ) ) {
          return $output;
        }

        if ( $args['field_data']['field_type'] !== 'acf' ) {
            return $output;
        }

        if ( ! function_exists( 'get_fields' ) ) {
            return $output;
        }

        $fields = get_fields( 'option' );

        if ( is_array( $fields ) && array_key_exists( $args['field'], $fields ) ) {
            $args['option_value'] = $fields[ $args['field'] ];
            $args['is_option_field'] = true;
            $args['id'] = 'option';
            return $this->get_content( $output, $args, $is_editor_content );
        }

        return array(
            'error' => __( 'The field type provided is not valid.', STACKABLE_I18N )
        );
    }

    /**
     * Function for handling fields
     * to display placeholder inside the editor
     * inside of the actual value.
     *
     * This function only displays a placeholder
     * inside the editor to avoid possible block errors.
     *
     * @param array arguments
     * @param boolean is_editor_content
     * @return string generated content.
     */
    function render_general_content_with_placeholder( $args, $is_editor_content ) {
        if ( $is_editor_content ) {
            $fields = \Stackable\DynamicContent\Stackable_Dynamic_Content::get_fields_data( $args['source'], $args['id'], true );
            $field = $fields[ $args['field'] ];
            return sprintf( __( '%s Placeholder', STACKABLE_I18N ), $field['title'] );
        }

		if ( $args['field_data']['type'] === 'wysiwyg' ) {
			return $this->render_text_area( $args, $is_editor_content );
		}

        return $this->render_general_content( $args, $is_editor_content );
    }

    /**
     * Function for handling fields
     * without custom options.
     *
     * This function only gets the field content.
     *
     * Only use this if the field does not have
     * any custom options rendered in the
     * editor.
     *
     * @param array arguments
     * @return string generated content.
     */
    function render_general_content( $args ) {
        if ( array_key_exists( 'is_option_field', $args ) ) {
            return $args['option_value'];
        }

        return get_post_field( $args['field'], $args['id'] );
    }

	/**
	 * Function for handling text area.
	 *
	 * This function helps detect line breaks.
	 *
	 * Do not render line breaks in backend.
	 *
	 * @param array arguments
	 * @return string generated content.
	 */
	function render_text_area( $args, $is_editor_content ) {
		if ( array_key_exists( 'is_option_field', $args ) ) {
			$text = $args['option_value'];
		} else {
			$text = get_post_field( $args['field'], $args['id'] );
		}

		if ( ! $text ) {
			$text = '';
		}

		if ( $is_editor_content ) {
			return $text;
		}

		if ( $args['field_data']['type'] === 'wysiwyg' ) {
			return wpautop( trim( $text ) );
		}

		return str_replace( "\n", "<br>", trim( $text ) );
	}

    /**
     * Function for handling choice field type subgroup.
     *
     * @param array arguments
     * @return string generated content.
     */
    function render_choice( $args ) {
        if ( ! function_exists( 'get_field_object' ) ) {
            return $this->render_general_content( $args );
        }

        $field_object = get_field_object( $args['field'], $args['id'] );

        if ( ! is_array( $field_object ) || ! array_key_exists( 'choices', $field_object ) ) {
            return $this->render_general_content( $args );
        }

        return $field_object['choices'][ $field_object['value'] ];
    }

    /**
     * Function for handling fields
     * related to links.
     *
     * This function only gets the field content,
     * make it as the href of the anchor tag.
     *
     * Only use this if the content is a url, and
     * designed to be rendered as a link.
     *
     * @param array arguments
     * @return string generated content.
     */
    function render_general_link( $args ) {
        $href = array_key_exists( 'is_option_field', $args ) ? $args['option_value'] : get_post_field( $args['field'], $args['id'] );
		if ( ! is_string( $href ) ) {
			$href = '';
		}

        $output = $href;

        if ( ! array_key_exists( 'with_link', $args['args'] ) || $args['args']['with_link'] === 'false' ) {
            return $output;
        }

        if ( ! array_key_exists( 'text', $args['args'] ) || empty( $args['args']['text'] ) ) {
            return array(
                'error' => __( 'Text input is empty', STACKABLE_I18N )
            );
        }

        $output = $args['args']['text'];

        $new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
        return Util::make_output_link( $output, $href, $new_tab, $args['is_editor_content'] );
    }

    /**
     * Function for getting the user name.
     *
     * @param array arguments
     * @return string generated content.
     */
    function render_user( $args ) {
        $author_id = array_key_exists( 'is_option_field', $args ) ? $args['option_value']['ID'] : get_post_field( $args['field'], $args['id'] );
		if ( ! $author_id ) {
			return '';
		}

        return get_the_author_meta( 'display_name', $author_id );
    }

    /**
     * Function for getting date types
     *
     * @param array arguments
     * @return string generated content.
     */
    function render_date( $args ) {
        $date = array_key_exists( 'is_option_field', $args ) ? $args['option_value'] : get_post_field( $args['field'], $args['id'] );
		if ( ! $date ) {
			return '';
		}

        if ( array_key_exists( 'format', $args['args'] ) ) {
            if ( $args['args']['format'] === 'custom' && array_key_exists( 'custom_format', $args['args'] ) ) {
                return Util::format_date( $date, $args['args']['custom_format'] );
            }
            return Util::format_date( $date, $args['args']['format'] );
        }

        return $date;
    }

    /**
     * Function for getting true_false content.
     *
     * @param array arguments
     * @return string generated content.
     */
    function render_true_false( $args ) {
        $true_false = array_key_exists( 'is_option_field', $args ) ? $args['option_value'] : get_post_field( $args['field'], $args['id'] );

        if ( ! array_key_exists( 'whenTrueText', $args['args'] ) || ! array_key_exists( 'whenFalseText', $args['args'] ) ) {
            return array(
                'error' => __( '`whenTrueText` and `whenFalseText` arguments are required.', STACKABLE_I18N )
            );
        }

        if ( $true_false ) {
            return $args['args']['whenTrueText'];
        }

        return $args['args']['whenFalseText'];
    }

    /**
     * Function for getting the link content.
     *
     * @param array arguments
     * @return string generated content.
     */
    function render_link( $args ) {
        $link_field = array_key_exists( 'is_option_field', $args ) ? $args['option_value'] : get_post_field( $args['field'], $args['id'] );

        if ( ! is_array( $link_field ) ) {
            return '';
        }

        $output = '';

        if ( ! is_array( $link_field ) || ! array_key_exists( 'url', $link_field ) ) {
            return $output;
        }

        if ( array_key_exists( 'title', $link_field ) ) {
            $output = empty( $link_field['title'] ) ? $link_field['url'] : $link_field['title'];
        }

        if ( ! array_key_exists( 'with_link', $args['args'] ) || $args['args']['with_link'] === 'false' ) {
            return $link_field['url'];
        }

        $href = $link_field['url'];
        $new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
        $output = Util::make_output_link( $output, $href, $new_tab, $args['is_editor_content'] );

        return $output;
    }

    /**
     * Function for handling checkbox field
     *
     * @param array arguments
     * @return string generated content.
     */
    function render_checkbox( $args ) {
        $selected_choices = array_key_exists( 'is_option_field', $args ) ? $args['option_value'] : get_post_field( $args['field'], $args['id'] );

        if ( ! is_array( $selected_choices ) ) {
            return '';
        }

        if ( function_exists( 'get_field_object' ) ) {
            $field_object = get_field_object( $args['field'], $args['id'] );
            if ( is_array( $field_object ) && array_key_exists( 'choices', $field_object ) ) {
                $choices = $field_object['choices'];
                $new_selected_choices = array();
                foreach ( $selected_choices as $selected_choice ) {
                    array_push( $new_selected_choices, $choices[ $selected_choice ] );
                }
                $selected_choices = $new_selected_choices;
            }
        }

        return implode( ', ', $selected_choices );
    }

    /**
     * Function for handling the taxonomy field.
     *
     * @param array arguments
     * @return string generated content.
     */
    function render_taxonomy( $args ) {
        $taxonomy = array_key_exists( 'is_option_field', $args ) ? $args['option_value'] : get_post_field( $args['field'], $args['id'] );

        if ( ! is_array( $taxonomy ) ) {
            return '';
        }

        $output = array();
        foreach ( $taxonomy as $taxonomy_id ) {
            array_push( $output, get_term( $taxonomy_id )->name );
        }

        return implode( ', ', $output ) ;
    }

    /**
     * Function for handling the image field.
     *
     * @param array arguments
     * @return string generated content.
     */
    function render_image( $args ) {
        if ( array_key_exists( 'is_option_field', $args ) ) {
            return $args['option_value']['url'];
        }

	$image_quality = isset( $args['args']['image_quality'] ) ? $args['args']['image_quality'] : 'large';

        $image_id = get_post_field( $args['field'], $args['id'] );
        $image_data = wp_get_attachment_image_src( $image_id, $image_quality );

        if ( is_array( $image_data ) && count( $image_data ) > 0 ) {
            return $image_data[ 0 ];
        }

        return '';
    }

    /**
     * Function for handling the file field.
     *
     * @param array arguments
     * @return string generated content.
     */
    function render_file( $args ) {
        $file_id = array_key_exists( 'is_option_field', $args ) ? $args['option_value']['id'] : get_post_field( $args['field'], $args['id'] );
        $href = wp_get_attachment_url( $file_id );
		if ( ! $href ) {
			$href = '';
		}

        $output = $href;

        if ( ! array_key_exists( 'with_link', $args['args'] ) || $args['args']['with_link'] === 'false' ) {
            return $output;
        }

        if ( ! array_key_exists( 'text', $args['args'] ) || empty( $args['args']['text'] ) ) {
            return array(
                'error' => __( 'Text input is empty', STACKABLE_I18N )
            );
        }

        $output = $args['args']['text'];

        $new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
        return Util::make_output_link( $output, $href, $new_tab, $args['is_editor_content'] );
    }
}

new ACF();
