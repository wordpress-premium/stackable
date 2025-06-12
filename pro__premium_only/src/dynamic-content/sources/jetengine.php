<?php
namespace Stackable\DynamicContent\Sources;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stackable Dynamic Content JetEngine
 * integration
 */

class JetEngine {
	function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_jetengine_endpoint' ) );

		add_filter( "stackable_dynamic_content/current-page/fields", array( $this, 'initialize_fields' ), 2, 3 );
		add_filter( "stackable_dynamic_content/other-posts/fields", array( $this, 'initialize_fields' ), 2, 3 );
		add_filter( "stackable_dynamic_content/latest-page/fields", array( $this, 'initialize_fields' ), 2, 3 );

		add_filter( "stackable_dynamic_content/current-page/content", array( $this, 'get_content' ), 2, 3 );
		add_filter( "stackable_dynamic_content/other-posts/content", array( $this, 'get_content' ), 2, 3);
		add_filter( "stackable_dynamic_content/latest-post/content", array( $this, 'get_content' ), 2, 3 );

		add_filter( "stackable_dynamic_content/render_post_taxonomy", array( $this, 'render_taxonomy' ), 2, 2 );
	}

	/**
	 * Function for getting JetEngine the fields.
	 *
	 * @param string post type
	 */
	function get_jet_engine_fields( $post_type = '' ) {

		$output = array();

		$excluded_field_type = [
			'iconpicker',
			'colorpicker',
			'html',
			'repeater',
			'gallery',
		];

		$excluded_object_type = [
			'tab',
			'accordion',
			'endpoint',
		];

		$field_groups = jet_engine()->meta_boxes->data->raw;
		$unique_field_groups = jet_engine()->meta_boxes->data->db->query_cache;

		$custom_post_type_field_groups = $unique_field_groups['post_types'];

		foreach ( $field_groups as $field_group ) {
			foreach ( $field_group['meta_fields'] as $field ) {
				if ( ! in_array( $field['type'], $excluded_field_type ) && ! in_array( $field['object_type'], $excluded_object_type ) ) {
					$field_metadata = array(
						'title' => $field_group['args']['name'] . ' - ' . $field['title'],
						'group' => __( 'JetEngine' , STACKABLE_I18N ),
						'data' => array(
							'field_type' => 'jet_engine',
							'type' => $field['type'],
						),
					);

					$output[ $field['name'] ] = $field_metadata;
				}
			}
		}

		foreach ( $custom_post_type_field_groups as $field_group ) {
			if ( empty( $post_type ) || $field_group['slug'] === $post_type ) {
				$unserialized_meta_fields = unserialize( $field_group['meta_fields'] );
				$unserialized_labels = unserialize( $field_group['labels'] );
				if ( ! is_array( $unserialized_labels ) ) {
					continue;
				}
				$name = $unserialized_labels['name'];
				foreach ( $unserialized_meta_fields as $field ) {
					if ( array_key_exists( 'type', $field ) && ! in_array( $field['type'], $excluded_field_type ) ) {
						$field_metadata = array(
							'title' => $name . ' - ' . $field['title'],
							'group' => __( 'JetEngine' , STACKABLE_I18N ),
							'data' => array(
								'field_type' => 'jet_engine',
								'type' => $field['type'],
							),
						);

						$output[ $field['name'] ] = $field_metadata;
					}
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
	 * @return array generated fields
	 */
	function initialize_fields(  $output, $id, $is_editor_content ) {
		$entity_id_array = explode( '-', $id );

		if ( ! function_exists( 'jet_engine' ) ) {
			return $output;
		}

		if ( count( $entity_id_array ) < 2 ) {
			if ( count( $entity_id_array ) === 1 ) {
				if ( $is_editor_content ) {
					return array_merge(
						$output,
						$this->get_jet_engine_fields()
					);
				}
				return array_merge(
					$output,
					$this->get_jet_engine_fields()
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
			$this->get_jet_engine_fields()
		);
	}

	function get_content( $output, $args, $is_editor_content ) {
		if ( ! array_key_exists( 'field_data', $args ) || ! array_key_exists( 'field_type', $args['field_data'] ) ) {
		  return $output;
		}

		if ( $args['field_data']['field_type'] !== 'jet_engine' ) {
			return $output;
		}

		switch ( $args['field_data']['type'] ) {
			case 'text':
				return $this->render_general_content( $args );
			case 'textarea':
				return $this->render_text_area( $args, $is_editor_content );
			case 'number':
				 return $this->render_general_content( $args );
			case 'checkbox':
				return $this->render_checkbox( $args );
			case 'radio':
				return $this->render_general_content( $args );
			case 'select':
				return $this->render_select( $args );
			case 'time':
				return $this->render_general_content( $args );
			case 'wysiwyg':
				return $this->render_general_content_with_placeholder( $args, $is_editor_content );
			case 'date':
				return $this->render_date( $args, $is_editor_content );
			case 'switcher':
				return $this->render_true_false( $args );
			case 'datetime-local':
				return $this->render_date( $args, $is_editor_content );
			case 'posts':
				return $this->render_post( $args );
			case 'media':
				return $this->render_media( $args );
			default: return array(
				'error' => __( 'The field type provided is not valid.', STACKABLE_I18N )
			);
		}
	}

	/**
	 * Function for handling fields that
	 * only needs to display the raw output
	 *
	 * @return array arguments
	 */
	function render_general_content( $args ) {
		if ( is_array( $args ) && array_key_exists( 'field_data', $args ) ) {
			$output = get_post_meta( $args['id'], $args['field'], true ); // From the meta boxes
		}

		if ( is_array( $args ) && array_key_exists( 'term_meta_field', $args ) ) {
			$output = $args['term_meta_field']; // From taxonomy meta fields
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
		if ( is_array( $args ) && array_key_exists( 'field_data', $args ) ) {
			$output = get_post_meta( $args['id'], $args['field'], true ); // From the meta boxes
		}

		if ( is_array( $args ) && array_key_exists( 'term_meta_field', $args ) ) {
			$output = $args['term_meta_field']; // From taxonomy meta fields
		}


		if ( $is_editor_content ) {
			return $output;
		}

		return nl2br( trim( $output ) );
	}

	/**
	 * Function for rendering select.
	 *
	 * @param array arguments
	 */
	function render_select( $args ) {
		if ( is_array( $args ) && array_key_exists('field_data', $args) ) {
			$output = get_post_meta( $args['id'], $args['field'], true );
			if( ! is_array( $output ) ) {
				return $output;
			}
			return implode( ', ', $output );
		}

		if ( ! is_array( $args['term_meta_field'] ) && array_key_exists( 'term_meta_field', $args ) ) {
			return $args['term_meta_field'];
		}

		if ( is_array( $args['term_meta_field'] ) && array_key_exists( 'term_meta_field', $args ) ) {
			return implode( ', ', $args['term_meta_field'] );
		}
	}

	/**
	 * Function for rendering post.
	 *
	 * @param array arguments
	 */
	function render_post( $args ) {
		if ( is_array( $args ) && array_key_exists('field_data', $args) ) {
			$post_ids = get_post_meta( $args['id'], $args['field'], true );
		}

		if ( is_array( $args ) && array_key_exists( 'term_meta_field', $args ) ) {
			$post_ids = $args['term_meta_field'];;
		}

		$output = array();

		if( ! is_array( $post_ids ) ) {
			return get_the_title( $post_ids ); // If content is not an array, give post title
		}

		foreach ( $post_ids as $post_id ) {
			array_push( $output, get_the_title( $post_id ) );
		}

		return implode( ', ', $output );
	}

	function render_date( $args ) {
		if ( is_array( $args ) && array_key_exists('field_data', $args) ) {
			$date = get_post_meta( $args['id'], $args['field'], true );
		}

		if ( is_array( $args ) && array_key_exists( 'term_meta_field', $args ) ) {
			$date = $args['term_meta_field'];
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
	 * Function for rendering content with a placeholder.
	 *
	 * @param array arguments
	 * @param boolean is_editor_content
	 */
	function render_general_content_with_placeholder( $args, $is_editor_content ) {
		if ( is_array( $args ) && array_key_exists('field_data', $args) ) {
			$output = get_post_meta( $args['id'], $args['field'], true );

			if ( $is_editor_content ) {
				$fields = \Stackable\DynamicContent\Stackable_Dynamic_Content::get_fields_data( $args['source'], $args['id'], true );
				$field = $fields[ $args['field'] ];
				return sprintf( __( '%s Placeholder', STACKABLE_I18N ), $field['title'] );
			}

			return $output;
		}

		if ( is_array( $args ) && array_key_exists( 'term_meta_field', $args ) ) {
			$output = $args['term_meta_field'];

			if ( $is_editor_content ) {
				return sprintf( __( '%s Placeholder', STACKABLE_I18N ), $args['args']['jet_engine_meta_field_label'] );
			}

			return $output;
		}
	}

	/**
	 * Function for rendering media.
	 *
	 * @param array arguments
	 */
	function render_media( $args ) {
		if ( is_array( $args ) && array_key_exists( 'field_data', $args ) ) {
			$href = get_post_meta( $args['id'], $args['field'], true );
			$output = $href;
		}

		if ( is_array( $args ) && array_key_exists( 'term_meta_field', $args ) ) {
			$href = $args['term_meta_field'];
			$output = $href;
		}

		if ( is_array( $href ) ) { // Only happens when return type is id and url
			$href = $output['url'];
			$output = $href;
		}

		if ( is_numeric( $href )  ) { // Only happens when return type is id
			$href = wp_get_attachment_url( $output );
			$output = $href;
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
	 * Function for rendering switcher.
	 *
	 * @param array arguments
	 */
	function render_true_false( $args ) {
		if ( is_array( $args ) && array_key_exists( 'field_data', $args ) ) {
			$true_false = get_post_meta( $args['id'], $args['field'], true );
		}

		if ( is_array( $args ) && array_key_exists( 'term_meta_field', $args ) ) {
			$true_false = $args['term_meta_field'];
		}

		if ( ! array_key_exists( 'whenTrueText', $args['args'] ) || ! array_key_exists( 'whenFalseText', $args['args'] ) ) {
			return array(
				'error' => __( '`whenTrueText` and `whenFalseText` arguments are required.', STACKABLE_I18N )
			);
		}

		if ( $true_false === 'true' ) {
			return $args['args']['whenTrueText'];
		}

		return $args['args']['whenFalseText'];
	}

	/**
	 * Function for rendering checkbox.
	 *
	 * @param array arguments
	 */
	function render_checkbox( $args ) {
		if ( array_key_exists( 'field_data', $args ) ) {
			$field = get_post_meta( $args['id'], $args['field'], true );
		}

		if ( is_array( $args ) && array_key_exists( 'term_meta_field', $args ) ) {
			$field = $args['term_meta_field'];
		}

		$output = array();

		foreach ( $field as $key => $value ) { // Pushes the label into the array
			if ( $value === 'true' ) {
				array_push( $output, $key );
			}
		}

		return implode( ', ', $output );
	}

	/**
	 * Function for validating if taxonomy has
	 * a JetEngine field and returns the fields
	 * associated to the taxonomy.
	 *
	 * @param array arguments
	 */
	function validate_taxonomy( $args ) {
		if ( ! function_exists( 'jet_engine' ) ) {
			return array('isJetEngineField' => false);
		}

		$excluded_object_type = [
			'tab',
			'accordion',
			'endpoint',
		];

		$unique_field_groups = jet_engine()->meta_boxes->data->db->query_cache;
		$custom_taxonomy_field_groups = $unique_field_groups['taxonomies'];
		$output = array();

		foreach( $custom_taxonomy_field_groups as $index => $taxonomy ) {
			if ( $args['taxonomy'] === $taxonomy['slug'] ) {
				$meta_fields = unserialize( $taxonomy['meta_fields'] );

				error_log(print_r($meta_fields, true));

				if ( count( $meta_fields ) === 0 ) {
					return array( 'isJetEngineField' => false );
				}

				$terms = get_the_terms( $args['post_id'], $args['taxonomy'] );

				if ( ! is_array( $terms ) || count( $terms ) === 0 ) {
					return array( 'isJetEngineField' => false );
				}

				array_push( $output, array( 'label' => 'None', 'value' => 0, 'field' => 'none' ) );

				foreach ( $terms as $props ) {
					foreach ( $meta_fields as $field ) {
						if ( ! in_array( $field['object_type'], $excluded_object_type ) ) { // Checks if object type is not excluded
							$option = array( $props->term_id, $field['name'], $field['type'] );
							array_push( $output, array( 'label' => $props->name . ' - ' . $field['title'], 'value' => $option, ) );
						}
					}
				}

				break;
			}

			if ( $index === array_key_last( $custom_taxonomy_field_groups ) ) {
				return array( 'isJetEngineField' => false ); // Taxonomy not found
			}
		}

		return array( 'isJetEngineField' => true, 'terms' => $output );
	}

	/**
	 * Function for rendering select.
	 *
	 * @param array arguments
	 */
	function register_jetengine_endpoint() {
		register_rest_route( 'stackable/v3', '/jetengine/(?P<taxonomy>[\S]+)/(?P<post_id>[\d]+)', array(
			'methods' => 'GET',
			'permission_callback' => function () {
				return current_user_can( 'edit_others_posts' );
			},
			'callback' => array( $this, 'validate_taxonomy'	),
			'args' => array(
				'taxonomy' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return $param;
					}
				),
				'post_id' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					}
				)
			 )
		) );
	}

	/**
	 * Function for rendering taxonomy.
	 *
	 * @param array arguments
	 */
	function render_taxonomy( $output, $args ) {

		if ( isset($args['args']['jet_engine_meta_field'] ) ) {
			if ( ! function_exists( 'jet_engine' ) ) {
				return '';
			}

			if ( $args['args']['jet_engine_meta_field'] != 0 ) {
				$taxonomy_meta_field = explode( ',', $args['args']['jet_engine_meta_field'] );
				$term_meta_field = get_term_meta( $taxonomy_meta_field[0], $taxonomy_meta_field[1], true );

				$emp_array = array();

				foreach( $args['args'] as $key => $value ) {
					if( str_starts_with( $key, 'amp;' ) ) { // amp; gets appended to the the args of the key
						$temp_array[ substr( $key, 4 ) ] =  $value;
						continue;
					}
					$temp_array[$key] = $value;
				}

				$args = array( 'term_meta_field' => $term_meta_field, 'args' => $temp_array, 'is_editor_content' => $args['is_editor_content'] );
				return $this->get_content_taxonomy( $args, $taxonomy_meta_field[2] );
			}
		}
	}

	/**
	 * Helper function for rendering the content of taxonomy.
	 *
	 * @param array arguments
	 * @param string field type
	 */
	function get_content_taxonomy( $args, $type ) {
		switch ( $type ) {
			case 'text':
				return $this->render_general_content( $args );
			case 'textarea':
				return $this->render_general_content( $args );
			case 'number':
				 return $this->render_general_content( $args );
			case 'checkbox':
				return $this->render_checkbox( $args );
			case 'radio':
				return $this->render_general_content( $args );
			case 'select':
				return $this->render_select( $args );
			case 'time':
				return $this->render_general_content( $args );
			case 'wysiwyg':
				return $this->render_general_content_with_placeholder( $args, $args['is_editor_content'] );
			case 'date':
				return $this->render_date( $args );
			case 'switcher':
				return $this->render_true_false( $args );
			case 'datetime-local':
				return $this->render_date( $args );
			case 'posts':
				return $this->render_post( $args );
			case 'media':
				return $this->render_media( $args );
			default: return array(
				'error' => __( 'The field type provided is not valid.', STACKABLE_I18N )
			);
		}
	}
}

new JetEngine();
