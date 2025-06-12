<?php
namespace Stackable\DynamicContent\Sources;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stackable Dynamic Content Metabox
 * integration
 */

 class Metabox {

	const EXCLUDED_FIELD_TYPE = [
		'oembed',
		'background',
		'button',
		'button_group',
		'sidebar',
		'switch',
		'map',
		'image_upload',
		'key_value',
		'osm',
		'divider',
		'heading',
		'hidden',
		'password',
	];

	function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_meta_box_endpoint' ) );

		add_filter( "stackable_dynamic_content/current-page/fields", array( $this, 'initialize_fields' ), 2, 3 );
		add_filter( "stackable_dynamic_content/other-posts/fields", array( $this, 'initialize_fields' ), 2, 3 );
		add_filter( "stackable_dynamic_content/latest-post/fields", array( $this, 'initialize_fields' ), 2, 3 );

		add_filter( "stackable_dynamic_content/site/fields", array( $this, 'initialize_fields_settings' ), 2, 1 );
		add_filter( 'stackable_dynamic_content/site/content', array( $this, 'get_content' ), 2, 3 );

		add_filter( "stackable_dynamic_content/current-page/content", array( $this, 'get_content' ), 2, 3 );
		add_filter( "stackable_dynamic_content/other-posts/content", array( $this, 'get_content' ), 2, 3);
		add_filter( "stackable_dynamic_content/latest-post/content", array( $this, 'get_content' ), 2, 3 );
	}

	function get_metabox_fields( $fields = null ) {

		if ( ! function_exists( 'rwmb_meta' ) && ! function_exists( 'rwmb_get_registry' ) ) {
			return $output;
		}

		$meta_box_registry = rwmb_get_registry( 'meta_box' );
		$meta_box_field_groups = $meta_box_registry->all();

		$output = array();

		foreach ( $meta_box_field_groups as $field_group ) {
			$field_group_fields = $field_group->meta_box;

			if ( get_class( $field_group ) !== 'RW_Meta_Box' ) {
				continue;
			}

			foreach ( $field_group_fields['fields'] as $field ) {
				if ( ! empty( $fields ) && ! in_array( $field, $fields ) ) {
					continue;
				}

				if ( ! empty( $field['name'] ) && ! in_array( $field['type'], self::EXCLUDED_FIELD_TYPE ) ) {
					$field_metadata = array(
						'title' => $field_group_fields['title'] . ' - ' . $field['name'],
						'group' => __( 'Meta Box' , STACKABLE_I18N ),
						'data' => array(
							'field_type' => 'meta_box',
							'type' => $field['type'],
						),
					);

					if ( in_array( $field['type'], [ 'file', 'file_advanced'] ) ) {
						$field_metadata['data']['type'] = 'file';
					}


					if ( in_array( $field['type'], [ 'image', 'image_advanced', 'single_image'] ) ) {
						$field_metadata['data']['type'] = 'image';
					}

					$field_name = ( strrpos( $field['field_name'], '[]' ) !== false ) ? substr ($field['field_name'], 0, -2 ) : $field['field_name'];

					$output[ $field_name ] = $field_metadata;
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

		if ( ! function_exists( 'rwmb_get_object_fields' ) ) {
			return $output;
		}

		$entity_id_array = explode( '-', $id );

		if ( count( $entity_id_array ) < 2 ) {
			if ( count( $entity_id_array ) === 1 ) {
				if ( $is_editor_content ) {
					return array_merge(
						$output,
						$this->get_metabox_fields()
					);
				}

				return array_merge(
					$output,
					$this->get_metabox_fields()
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
			$this->get_metabox_fields()
		);
	}

	/**
	 * Function for initializing the fields of Metabox Settings.
	 *
	 * @param string previous generated output
	 * @return array generated fields.
	 */
	function initialize_fields_settings( $output ) {

		if ( ! function_exists( 'rwmb_meta' ) ) {
			return $output;
		}

		$meta_box_registry = rwmb_get_registry( 'meta_box' );
		$meta_box_field_groups = $meta_box_registry->all();

		$metabox_settings = array();

		$settings_pages = apply_filters( 'mb_settings_pages', [] );

		// Shall be used to match the id and option_name of a Metabox settings
		foreach ( $settings_pages as $sp ) {
			$metabox_settings[ $sp['id'] ] = array_key_exists( 'option_name', $sp ) ? $sp['option_name'] : $sp['id'];
		}

		// var_dump($metabox_settings);

		$meta_box_setting_output = array();

		foreach ( $meta_box_field_groups as $field_group ) {
			$field_group_fields = $field_group->meta_box;

			if ( get_class( $field_group ) !== 'MBSP\MetaBox' && ! isset( $field_group->settings_pages ) ) {
				continue;
			}

			foreach ( $field_group_fields['fields'] as $field ) {
				if ( ! empty( $fields ) && ! in_array( $field, $fields ) ) {
					continue;
				}

				if ( ! empty( $field['name'] ) && ! in_array( $field['type'], self::EXCLUDED_FIELD_TYPE ) ) {

					foreach ( $field_group_fields['settings_pages'] as $setting ) {
						$post = get_page_by_path( $setting, OBJECT, 'mb-settings-page' );
						$title = $post->post_title ?? $setting;

						$field_metadata = array(
							'title' => $field_group_fields['title'] . ' - ' . $field['name'],
							'group' => __( 'Meta Box | ' . $title , STACKABLE_I18N ),
							'data' => array(
								'field_type' => 'meta_box',
								'type' => $field['type'],
								'source' => $setting,
								'option_name' => array_key_exists( $setting, $metabox_settings ) ? $metabox_settings[ $setting ] : $setting // For some reason it cannot exist
							),
						);

						if ( in_array( $field['type'], [ 'file', 'file_advanced'] ) ) {
							$field_metadata['data']['type'] = 'file';
						}


						if ( in_array( $field['type'], [ 'image', 'image_advanced', 'single_image'] ) ) {
							$field_metadata['data']['type'] = 'image';
						}

						$field_name = ( strrpos( $field['field_name'], '[]' ) !== false ) ? substr ($field['field_name'], 0, -2 ) : $field['field_name'];

						$meta_box_setting_output[ $field_name . $setting ] = $field_metadata;
					}
				}

			}

		}

		return array_merge(
			$output,
			$meta_box_setting_output
		);
	}

	function get_content( $output, $args, $is_editor_content ) {
		if ( ! array_key_exists( 'field_data', $args ) || ! array_key_exists( 'field_type', $args['field_data'] ) ) {
		  return $output;
		}

		if ( $args['field_data']['field_type'] !== 'meta_box' ) {
			return $output;
		}

		if ( ! function_exists( 'rwmb_get_field_settings' ) || ! function_exists( 'rwmb_get_value' ) ) {
			return $output;
		}

		$field_settings = rwmb_get_field_settings( $args['field'], [], $args['id'] );
		if ( is_array( $field_settings ) && isset( $field_settings['multiple'] ) && $field_settings['multiple'] ) {
			$excluded_multiple_fields = array( 'image_select' );

			if ( in_array( $args['field_data']['type'], $excluded_multiple_fields ) ) {
					return array(
					'error' => __( 'Multiple select not supported in this field.', STACKABLE_I18N )
				);
			}

		}

		switch ( $args['field_data']['type'] ) {
			case 'textarea':
				return $this->render_text_area( $args, $is_editor_content );
			case 'text':
			case 'number':
			case 'email':
			case 'radio':
			case 'range':
			case 'slider':
			case 'time':
			case 'color':
				return $this->render_general_content( $args, $is_editor_content );
			case 'wysiwyg':
				return $this->render_general_content_with_placeholder( $args, $is_editor_content );
			case 'video':
				return $this->render_file( $args, $is_editor_content );
			case 'file':
				return $this->render_file( $args, $is_editor_content );
			case 'file_input':
				return $this->render_file( $args, $is_editor_content );
			case 'checkbox_list':
				return $this->render_checkbox( $args, $is_editor_content );
			case 'checkbox':
				return $this->render_true_false( $args );
			case 'select':
				return $this->render_select( $args, $is_editor_content );
			case 'select_advanced':
				return $this->render_select( $args, $is_editor_content );
			case 'fieldset_text':
				return $this->render_fieldset_text( $args );
			case 'text_list':
				return $this->render_fieldset_text( $args );
			case 'url':
				return $this->render_general_link( $args, $is_editor_content );
			case 'autocomplete':
				return $this->render_autocomplete( $args );
			case 'user':
				return $this->render_user( $args, $is_editor_content );
			case 'date':
				return $this->render_date( $args, $is_editor_content );
			case 'datetime':
				return $this->render_date( $args, $is_editor_content );
			case 'taxonomy':
				return $this->render_taxonomy( $args, $is_editor_content );
			case 'image':
				return $this->render_image( $args, $is_editor_content );
			case 'image_select':
				return $this->render_image_select( $args, $is_editor_content );
			case 'taxonomy_advanced':
				return $this->render_taxonomy( $args, $is_editor_content );
			case 'post':
				return $this->render_post( $args );
			default: return array(
				'error' => __( 'The field type provided is not valid.', STACKABLE_I18N )
			);
		}
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
		if ( $args['source'] === 'site' ) {
			$output = rwmb_get_value( str_replace( $args['field_data']['source'], '', $args['field'] ), ['object_type' => 'setting'], $args['field_data']['option_name'] );
		} else {
			$output = get_post_field( $args['field'], $args['id'] );
		}
		return $output;
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
		if ( $args['source'] === 'site' ) {
			$text = rwmb_get_value( str_replace( $args['field_data']['source'], '', $args['field'] ), ['object_type' => 'setting'], $args['field_data']['option_name'] );
		} else {
			$text = get_post_field( $args['field'], $args['id'] );
		}

		if ( $is_editor_content ) {
			return $text;
		}

		return str_replace( "\n", "<br>", trim( $text ) );
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
		if ( $args['source'] === 'site' ) {
			$href = rwmb_meta( str_replace( $args['field_data']['source'], '', $args['field'] ), ['object_type' => 'setting'], $args['field_data']['option_name'] );
		} else {
			$href = get_post_field( $args['field'], $args['id'] );
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
		if ( $args['source'] === 'site' ) {
			$author_id = rwmb_meta( str_replace( $args['field_data']['source'], '', $args['field'] ), ['object_type' => 'setting'], $args['field_data']['option_name'] );
		} else {
			$author_id = get_post_field( $args['field'], $args['id'] );
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
		if ( $args['source'] === 'site' ) {
			$date = rwmb_meta( str_replace( $args['field_data']['source'], '', $args['field'] ), ['object_type' => 'setting'], $args['field_data']['option_name'] );
		} else {
			$date = get_post_field( $args['field'], $args['id'] );
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
		if ( $args['source'] === 'site' ) {
			$true_false = rwmb_get_value( str_replace( $args['field_data']['source'], '', $args['field'] ), ['object_type' => 'setting'], $args['field_data']['option_name'] );
		} else {
			$true_false = get_post_field( $args['field'], $args['id'] );
		}

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
	 * Function for handling checkbox field
	 *
	 * @param array arguments
	 * @return string generated content.
	 */
	function render_checkbox( $args, $is_editor_content ) {
		if ( $args['source'] === 'site' ) {
			$selected_checkboxes = rwmb_get_value( str_replace( $args['field_data']['source'], '', $args['field'] ), ['object_type' => 'setting'], $args['field_data']['option_name'] );
		} else {
			$selected_checkboxes = rwmb_get_value($args['field'], [], $args['id']);
		}

		return is_array( $selected_checkboxes ) ? implode( ', ', $selected_checkboxes ) : '';
	}

	/**
	 * Function for handling the taxonomy field.
	 *
	 * @param array arguments
	 * @return string generated content.
	 */
	function render_taxonomy( $args ) {
		if ( $args['source'] === 'site' ) {
			$taxonomy = rwmb_get_value( str_replace( $args['field_data']['source'], '', $args['field'] ), ['object_type' => 'setting'], $args['field_data']['option_name'] );
		} else {
			$taxonomy = rwmb_get_value( $args['field'], [], $args['id'] );
		}

		// Taxonomy not found
		if ( is_bool( $taxonomy ) ) {
			return '';
		}


		if ( ! is_array( $taxonomy ) ) {
			return $taxonomy->name;
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
		if ( $args['source'] === 'site' ) {
			$image_id = rwmb_get_value( str_replace( $args['field_data']['source'], '', $args['field'] ), ['object_type' => 'setting', 'limit' => 1], $args['field_data']['option_name'] );
			if ( $image_id && is_array( $image_id ) && ! array_key_exists( 'ID', $image_id ) ) {
				$image_id = $image_id[0]['ID'];
			} else if ( is_array( $image_id ) && array_key_exists( 'ID', $image_id ) ) {
				$image_id = $image_id['ID'];
			}
		} else {
			$image_id = get_post_field( $args['field'], $args['id'] );
		}

		$image_quality = isset( $args['args']['image_quality'] ) ? $args['args']['image_quality'] : 'large';

		$image_data = wp_get_attachment_image_src( $image_id, $image_quality );

		if ( is_array( $image_data ) && count( $image_data ) > 0 ) {
			return $image_data[0];
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
		if ( $args['source'] === 'site' ) {
			$file_id = rwmb_get_value( str_replace( $args['field_data']['source'], '', $args['field'] ), [ 'object_type' => 'setting', 'limit' => 1 ], $args['field_data']['option_name'] );
			if ( ! is_bool( $file_id ) && is_array( $file_id ) ) {
				$file_id = $file_id[0]['ID'];
			}
			return ''; // If file is bool, field is empty
		} else {
			$file_id = get_post_field( $args['field'], $args['id'] );
		}

		$href = wp_get_attachment_url( $file_id );
		$output = $href;

		if ( $file_id === '' ) {
			return ''; // $file_id is empty,
		}

		if( $args['field_data']['type']  == 'file_input' ) {
			$href = $file_id;
			$output = $file_id;
		}

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
	 * Function for handling the autocomplete field.
	 *
	 * @param array arguments
	 * @return string generated content.
	 */
	function render_autocomplete( $args ) {
		if ( $args['source'] === 'site' ) {
			$selected_autocomplete = rwmb_get_value( str_replace( $args['field_data']['source'], '', $args['field'] ), [ 'object_type' => 'setting' ], $args['field_data']['option_name'] );
		} else {
			$selected_autocomplete = rwmb_get_value( $args['field'], [], $args['id'] );
		}

		return is_array( $selected_autocomplete ) ? implode( ', ', $selected_autocomplete ) : '';
	}

	/**
	 * Function for handling the image select field.
	 *
	 * @param array arguments
	 * @return string generated content.
	 */
	function render_image_select( $args, $is_editor_content ) {
		if ( $args['source'] === 'site' ) {
			$selected_image = rwmb_get_value( str_replace( $args['field_data']['source'], '', $args['field'] ), [ 'object_type' => 'setting' ], $args['field_data']['option_name'] );
			$field_settings = rwmb_get_field_settings( str_replace( $args['field_data']['source'], '', $args['field'] ), [ 'object_type' => 'setting' ], $args['field_data']['option_name'] );
		} else {
			$selected_image = rwmb_get_value( $args['field'], [], $args['id'] );
			$field_settings = rwmb_get_field_settings( $args['field'], [], $args['id'] );
		}

		if ( is_bool( $selected_image ) && is_bool( $field_settings ) ) { // Field is empty
			return '';
		}

		if ( ! $selected_image ) {
			return array(
				'error' => __( 'You have not selected an image.', STACKABLE_I18N )
			);
		}

		if ( ! array_key_exists( 'with_link', $args['args'] ) || $args['args']['with_link'] === 'false' ) {
			if( array_key_exists( $selected_image , $field_settings['options'] ) ) {
				return $field_settings['options'][ $selected_image ];
			}
		}

		if ( ! array_key_exists( 'text', $args['args'] ) || empty( $args['args']['text'] ) ) {
			return array(
				'error' => __( 'Text input is empty', STACKABLE_I18N )
			);
		}

		$output = $args['args']['text'];

		$new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
		return Util::make_output_link( $output, $field_settings['options'][ $selected_image ], $new_tab, $args['is_editor_content'] );
	}

	/**
	 * Function for handling the fieldset text field.
	 *
	 * @param array arguments
	 * @return string generated content.
	 */
	function render_fieldset_text($args) {
		if ( ! array_key_exists( 'textField', $args['args'] ) ) {
			return array(
				'error' => __( 'Text fields are empty', STACKABLE_I18N )
			);
		}

		if ( $args['source'] === 'site' ) {
			$field = rwmb_meta( str_replace( $args['field_data']['source'], '', $args['field'] ), [ 'object_type' => 'setting' ], $args['field_data']['option_name'] );
		} else {
			$field = rwmb_get_value( $args['field'], [], $args['id'] );
		}

		$output = $field[ $args['args']['textField'] ];

		return $output;
	}

	/**
	 * Function for handling the post field.
	 *
	 * @param array arguments
	 * @return string generated content.
	 */
	function render_post( $args ) {
		if ( $args['source'] === 'site' ) {
			$field = rwmb_meta( str_replace( $args['field_data']['source'], '', $args['field'] ), [ 'object_type' => 'setting' ], $args['field_data']['option_name'] );
		} else {
			$field = rwmb_get_value( $args['field'], [], $args['id'] );
		}

		$post_title = get_the_title( $field );

		return $post_title;
	}


	/**
	 * Function for handling the fields that are to be
	 * returned when the user chooses textlist or fieldset.
	 *
	 * @param array arguments
	 * @return string text field's labels or placeholders.
	 */
	function get_text_fields( $args ) {
		if ( ! function_exists( 'rwmb_get_value' ) && ! function_exists( 'rwmb_get_field_settings' ) ) {
			return '';
		}

		$output = array();
		$fields = rwmb_get_value( $args['meta_key'], [], $args['post_id'] );
		$field_settings = rwmb_get_field_settings( $args['meta_key'], [], $args['post_id'] );

		if ( $args['source'] !== 'undefined' ) {
			$fields = rwmb_get_value( $args['meta_key'], [ 'object_type' => 'setting' ], $args['source'] );
			$field_settings = rwmb_get_field_settings( $args['meta_key'], [ 'object_type' => 'setting' ], $args['source'] );
		}

		$index = 0;

		if( ! array_key_exists( 'options', $field_settings ) ) {
			return '';
		}

		if( $field_settings['type'] === 'fieldset_text' ) {
			foreach ( $field_settings['options'] as $key => $value ) {
				array_push( $output, array( 'value' => $key, 'label' => $value ) );
			}

			return $output;
		}

		foreach ( $field_settings['options'] as $key => $value ) {
			$label = ( $value === '' ) ? $key : $value;
			array_push( $output, array( 'value' => $index, 'label' => $label ) );
			$index++;
		}

		return $output;
	}

	/**
	 * Function for handling the select field.
	 *
	 * @param array arguments
	 * @return string generated content.
	 */
	function render_select( $args ) {
		if ( $args['source'] === 'site' ) {
			$output = rwmb_meta( str_replace( $args['field_data']['source'], '', $args['field'] ), ['object_type' => 'setting'], $args['field_data']['option_name'] );
			$field_settings = rwmb_get_field_settings( str_replace( $args['field_data']['source'], '', $args['field'] ), ['object_type' => 'setting'], $args['field_data']['option_name'] );
		} else {
			$output = rwmb_get_value( $args['field'], [], $args['id'] );
			$field_settings= rwmb_get_field_settings( $args['field'], [], $args['id'] );
		}

		if ( is_bool( $output ) && is_bool( $field_settings ) ) { // Field is empty
			return '';
		}

		if ( is_array( $field_settings ) && isset( $field_settings['multiple'] ) && $field_settings['multiple'] ) { // If multiple is true, return a comma delimited output.
			return is_array( $output ) ? implode( ', ', $output ) : '';
		}

		return $output;
	}

	/**
	 * Function for registering a custom endpoint.
	 *
	 * @param array arguments
	 * @return string generated content.
	 */
	function register_meta_box_endpoint() {
		register_rest_route( 'stackable/v3', '/metabox/fieldset_text/(?P<meta_key>[\S]+)/(?P<post_id>[\d]+)/(?P<source>[\S]+)', array(
			'methods' => 'GET',
			'permission_callback' => function() {
				return current_user_can( 'edit_others_posts' );
			},
			'callback' => array( $this, 'get_text_fields' ),
			'args' => array(
				'meta_key' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return $param;
					}
				),
				'post_id' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					}
				),
				'source' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return $param;
					}
				)
			 )
		) );
	}

 }


new Metabox();
