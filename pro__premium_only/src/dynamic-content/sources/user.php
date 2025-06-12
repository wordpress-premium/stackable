<?php
namespace Stackable\DynamicContent\Sources;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class User {

    private $source_slug = 'user';

    function __construct() {
        add_filter( "stackable_dynamic_content/sources", array( $this, 'initialize_source' ), 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/fields", array( $this, 'initialize_fields' ), 1 );
		add_filter( "stackable_dynamic_content/$this->source_slug/fields", array( __NAMESPACE__ . '\Other_Posts', 'initialize_other_fields' ), 100, 3 );
		add_filter( "stackable_dynamic_content/$this->source_slug/search", array( $this, 'search_users' ), 1, 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/content", array( $this, 'get_content' ), 1, 2 );
        add_filter( "stackable_dynamic_content/$this->source_slug/content", array( __NAMESPACE__ . '\Other_Posts', 'get_custom_field_content' ), 1, 2 );
		add_filter( "stackable_dynamic_content/$this->source_slug/entity", array( $this, 'get_entity' ), 1, 2 );
    }

    /**
     * Function for registering the source.
     *
     * @param array previous sources object.
     * @return array newly generated $sources object.
     */
    public function initialize_source( $sources ) {
        $sources[ $this->source_slug ] = array(
            'title' => __( 'User', STACKABLE_I18N ),
			'with_input_box' => true,
            'with_search' => true,
            'input_label' => __( 'Users', STACKABLE_I18N ),
            'input_placeholder' => __( 'Search for users', STACKABLE_I18N )
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
				'user-id' => array(
                    'title' => __( 'User ID', STACKABLE_I18N ),
                    'group' => __( 'User', STACKABLE_I18N ),
                ),
				'user-profile-picture' => array(
                    'title' => __( 'User Profile Picture URL', STACKABLE_I18N ),
                    'group' => __( 'User', STACKABLE_I18N ),
                    'type' => 'image-url',
                ),
                'user-posts-url' => array(
                    'title' => __( 'User Posts URL', STACKABLE_I18N ),
                    'group' => __( 'User', STACKABLE_I18N ),
                    'type' => 'link',
                ),
               'user-name' => array(
                    'title' => __( 'User Name', STACKABLE_I18N ),
                    'group' => __( 'User', STACKABLE_I18N ),
                ),
                'user-first-name' => array(
                    'title' => __( 'User First Name', STACKABLE_I18N ),
                    'group' => __( 'User', STACKABLE_I18N ),
                ),
                'user-last-name' => array(
                    'title' => __( 'User Last Name', STACKABLE_I18N ),
                    'group' => __( 'User', STACKABLE_I18N ),
                ),
                'user-full-name' => array(
					'title' => __( 'User Full Name', STACKABLE_I18N ),
					'group' => __( 'User', STACKABLE_I18N ),
				),
				'user-nicename' => array(
					'title' => __( 'User Nicename', STACKABLE_I18N ),
					'group' => __( 'User', STACKABLE_I18N ),
				),
				'user-display-name' => array(
					'title' => __( 'User Display Name', STACKABLE_I18N ),
					'group' => __( 'User', STACKABLE_I18N ),
				),
				'user-email' => array(
					'title' => __( 'User Email', STACKABLE_I18N ),
					'group' => __( 'User', STACKABLE_I18N ),
				),
				'user-website' => array(
					'title' => __( 'User Website', STACKABLE_I18N ),
					'group' => __( 'User', STACKABLE_I18N ),
					'type' => 'link',
				),
				'user-biographical-info' => array(
					'title' => __( 'User Biographical Info', STACKABLE_I18N ),
					'group' => __( 'User', STACKABLE_I18N ),
				),
				'user-role' => array(
					'title' => __( 'User Role', STACKABLE_I18N ),
					'group' => __( 'User', STACKABLE_I18N ),
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
            case 'user-id': return self::render_user_id( $args );
            case 'user-profile-picture': return self::render_user_profile_picture( $args );
            case 'user-posts-url': return self::render_user_posts_url( $args );
            case 'user-name': return self::render_user_name( $args );
            case 'user-first-name': return self::render_user_first_name( $args );
            case 'user-last-name': return self::render_user_last_name( $args );
            case 'user-full-name': return self::render_user_full_name( $args );
            case 'user-nicename': return self::render_user_nicename( $args );
            case 'user-display-name': return self::render_user_display_name( $args );
            case 'user-email': return self::render_user_email( $args );
            case 'user-website': return self::render_user_website( $args );
            case 'user-biographical-info': return self::render_user_biographical_info( $args );
            case 'user-role': return self::render_user_role( $args );
            default: return array(
                'error' => __( 'The field type provided is not valid.', STACKABLE_I18N )
            );
        }
    }

    /**
     * Function for displaying the user-id content.
     *
     * @param array parsed args
     * @return string generated output
     */
    public static function render_user_id( $args ) {
		if ( ! array_key_exists( 'has_format', $args['args'] ) || $args['args']['has_format'] === 'false' ) {
            return $args['id'];
        }

        $format = array_key_exists( 'format', $args['args'] ) ? $args['args']['format'] : '';

        return sprintf( $format, strval( $args['id'] ) );
    }

	/**
	 * Function for displaying the user-name content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_name( $args ) {
		$user_info = get_userdata( $args['id'] );
		if ( isset( $args['args']['with_link'] ) ) {
			$output = wp_kses_post( $user_info->user_login );
			$new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
			return Util::make_output_link( $output, Other_Posts::get_author_posts_url( $user_info->ID ), $new_tab, $args['is_editor_content'] );
		}
		return wp_kses_post( $user_info->user_login );
	}

	/**
	 * Function for displaying the user-profile-picture content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_profile_picture( $args ) {
		return get_avatar_url( $args['id'] );
	}

	/**
	 * Function for displaying the user-posts-url content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_posts_url( $args ) {
		$output = Other_Posts::get_author_posts_url( $args['id'] );
		if ( ! isset( $args['args']['with_link'] ) || $args['args']['with_link'] === 'false' ) {
			return $output;
		}

		if ( ! isset( $args['args']['text'] ) || empty( $args['args']['text'] ) ) {
			return array(
				'error' => __( 'Text input is empty', STACKABLE_I18N )
			);
		}

		$output = $args['args']['text'];
		$href = Other_Posts::get_author_posts_url( $args['id'] );
		$new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
		return Util::make_output_link( $output, $href, $new_tab, $args['is_editor_content'] );
	}

	/**
	 * Function for displaying the user-first-name content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_first_name( $args ) {
		$user_info = get_userdata( $args['id'] );
		return wp_kses_post( $user_info->first_name );
	}

	/**
	 * Function for displaying the user-last-name content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_last_name( $args ) {
		$user_info = get_userdata( $args['id'] );
		return wp_kses_post( $user_info->last_name );
	}

	/**
	 * Function for displaying the user-full-name content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_full_name( $args ) {
		$user_info = get_userdata( $args['id'] );
		return wp_kses_post( $user_info->first_name . ' ' . $user_info->last_name );
	}

	/**
	 * Function for displaying the user-nicename content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_nicename( $args ) {
		$user_info = get_userdata( $args['id'] );
		if ( isset( $args['args']['with_link'] ) ) {
			$output = wp_kses_post( $user_info->user_nicename );
			$new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
			return Util::make_output_link( $output, Other_Posts::get_author_posts_url( $user_info->ID ), $new_tab, $args['is_editor_content'] );
		}
		return wp_kses_post( $user_info->user_nicename );
	}

	/**
	 * Function for displaying the user-display-name content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_display_name( $args ) {
		$user_info = get_userdata( $args['id'] );
		if ( isset( $args['args']['with_link'] ) ) {
			$output = wp_kses_post( $user_info->display_name );
			$new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
			return Util::make_output_link( $output, Other_Posts::get_author_posts_url( $user_info->ID ), $new_tab, $args['is_editor_content'] );
		}
		return wp_kses_post( $user_info->display_name );
	}

	/**
	 * Function for displaying the user-email content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_email( $args ) {
		$user_info = get_userdata( $args['id'] );
		return wp_kses_post( $user_info->user_email );
	}

	/**
	 * Function for displaying the user-website content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_website( $args ) {
		$user_info = get_userdata( $args['id'] );


		$output = esc_url($user_info->user_url);
		if ( ! isset( $args['args']['with_link'] ) || $args['args']['with_link'] === 'false' ) {
			return $output;
		}

		if ( ! isset( $args['args']['text'] ) || empty( $args['args']['text'] ) ) {
			return array(
				'error' => __( 'Text input is empty', STACKABLE_I18N )
			);
		}

		$output = $args['args']['text'];
		$href = esc_url($user_info->user_url);
		$new_tab = array_key_exists( 'new_tab', $args['args'] ) && $args['args']['new_tab'];
		return Util::make_output_link( $output, $href, $new_tab, $args['is_editor_content'] );
	}

	/**
	 * Function for displaying the user-biographical-info content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_biographical_info( $args ) {
		$user_meta = get_user_meta( $args['id'] );
		return wp_kses_post( $user_meta['description'][0] );
	}

	/**
	 * Function for displaying the user-role content.
	 *
	 * @param array parsed args
	 * @return string generated output
	 */
	public static function render_user_role( $args ) {
		$user_info = get_userdata( $args['id'] );
		$delimiter = isset( $args['args']['delimiter'] ) ? $args['args']['delimiter'] : ', ';
		return wp_kses_post( implode( $delimiter, $user_info->roles ) );
	}

	/**
     * Function for handling the search user field.
     *
     * @param array previous output value
     * @param string keyword
     * @return array user data object
     */
    public static function search_users( $output, $s ) {
		$users = array();

		$args = array(
			'search' => '*'.esc_attr( $s ).'*',
			'number' => 20,
		);
		$the_query = new \WP_User_Query( $args );

		$query_result = $the_query->get_results();
		if ( ! empty( $query_result ) ) {
			foreach ( $query_result as $user ) {
				$user_data = get_userdata( $user->ID );
				$users[] = array(
					'group' => __( 'User', STACKABLE_I18N ),
					'value' => $user->ID,
					'label' => $user_data->display_name . ' (' . $user_data->user_login . ')'
				);
			}
		}

		return $users;
    }

	/**
	 * Function for getting the entity.
	 *
	 * @param array parsed args
	 * @return array user data object
	 */
	public static function get_entity( $output, $id ) {
		$user_info = get_userdata( $id );
		return $user_info->display_name . ' (' . $user_info->user_login . ')';
	}
}

new User();
