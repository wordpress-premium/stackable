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

class Woocommerce {

    function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_woocommerce_endpoint' ) );

		add_filter( "stackable_dynamic_content/current-page/fields", array( $this, 'initialize_fields' ), 2, 3 );
		add_filter( "stackable_dynamic_content/other-posts/fields", array( $this, 'initialize_fields' ), 2, 3 );
		add_filter( "stackable_dynamic_content/latest-post/fields", array( $this, 'initialize_fields' ), 2, 3 );

		add_filter( "stackable_dynamic_content/current-page/content", array( $this, 'get_content' ), 2, 3 );
		add_filter( "stackable_dynamic_content/other-posts/content", array( $this, 'get_content' ), 2, 3);
		add_filter( "stackable_dynamic_content/latest-post/content", array( $this, 'get_content' ), 2, 3 );
    }

	function get_content( $output, $args, $is_editor_content ) {
        if ( Util::is_valid_output( $output ) ) {
            return $output;
        }

		$product = '';
		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $args['id'] );
		}

		switch ( $args['field'] ) {
            case 'product-name': return $product ? $product->get_name() : '';
			case 'product-description': return $product ? $this->render_description( $args, $product, $is_editor_content ) : ''; // Sanitize because some product editors use gutenberg and may bring unwanted behavior
			case 'product-short-description': return $product ? $product->get_short_description() : '';
			case 'product-purchase-note': return $product ? $product->get_purchase_note() : '';
			case 'product-image': return $product ? $this->render_image( $args, $product ) : '';
            case 'product-price': return $product ? $product->get_price() : '';
			case 'product-price-regular': return $product ? $product->get_regular_price() : '';
            case 'product-price-no-tax': return $product ? $product->get_price_excluding_tax() : '';
			case 'product-price-sale': return $product ? $product->get_sale_price() : '';
			case 'product-date-created': return $product ? $this->render_date( $args, $product ) : '';
			case 'product-date-sale-from': return $product ? $this->render_date( $args, $product ) : '';
			case 'product-date-sale-to': return $product ? $this->render_date( $args, $product ) : '';
			case 'product-add-to-cart-url': return $product ? $product->add_to_cart_url() : '';
			case 'product-sku': return $product ? $product->get_sku() : '';
			case 'product-total-sales': return $product ? $product->get_total_sales() : '';
			case 'product-total-stock': return $product ? $product->get_total_stock() : '';
			case 'product-low-stock': return $product ? $product->get_low_stock_amount() : '';
			case 'product-weight': return $product ? $product->get_weight() : '';
			case 'product-width': return $product ? $product->get_width() : '';
			case 'product-length': return $product ? $product->get_length() : '';
			case 'product-height': return $product ? $product->get_height() : '';
			case 'product-review-count': return $product ? $product->get_review_count() : '';
			case 'product-tax-status': return $product ? $this->render_tax_status( $args, $product ) : '';
			case 'product-tax-class': return $product ? $this->render_tax_class( $args, $product ) : '';
			case 'product-url': return $product ? $product->get_product_url() : '';
			case 'product-tags': return $product ? $product->get_tags() : '';
			case 'product-attributes': return $product ? $this->render_attributes( $args, $product ) : '';
            default: return array(
                'error' => __( 'The field type provided is not valid.', STACKABLE_I18N )
            );
        }
    }

	/**
	 * Function for initializing the fields.
	 *
	 * @param string previous generated output
	 * @param string post/page ID
	 * @return array generated fields
	 */
	public static function initialize_fields( $output, $id ) {

		$product_fields = array();

		if ( ! function_exists( 'wc_get_product' ) ) {
			return $output;
		}

		$product = wc_get_product( $id );

		if ( $product && get_class( $product ) === 'WC_Product_External' ) {
			$product_fields['product-url'] = array(
				'title' => __( 'Product Url', STACKABLE_I18N ),
				'group' => __( 'WooCommerce', STACKABLE_I18N ),
				'type' => 'link',
			);
		}

        return array_merge(
            $output,
            array(
                'product-name' => array(
                    'title' => __( 'Product Name', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-description' => array(
                    'title' => __( 'Product Description', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-short-description' => array(
                    'title' => __( 'Product Short Description', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-purchase-note' => array(
                    'title' => __( 'Product Purchase Note', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-image' => array(
                    'title' => __( 'Product Image', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
                'product-price' => array(
                    'title' => __( 'Product Price', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-price-regular' => array(
                    'title' => __( 'Product Price (Regular)', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
                'product-price-no-tax' => array(
                    'title' => __( 'Product Price (No Tax)', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
                'product-price-sale' => array(
                    'title' => __( 'Produce Price (Sale)', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-date-created' => array(
                    'title' => __( 'Product Date Created', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-date-sale-from' => array(
                    'title' => __( 'Product Sale Date From', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-date-sale-to' => array(
                    'title' => __( 'Product Sale Date To', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
                'product-add-to-cart-url' => array(
                    'title' => __( 'Product Add to Cart URL', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'type' => 'link',
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-sku' => array(
                    'title' => __( 'Product SKU', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-total-sales' => array(
                    'title' => __( 'Product Total Sales', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-total-stock' => array(
                    'title' => __( 'Product Total Stock', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-low-stock' => array(
                    'title' => __( 'Product Low Stock', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-weight' => array(
                    'title' => __( 'Product Weight', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-width' => array(
                    'title' => __( 'Product Width', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-length' => array(
                    'title' => __( 'Product Length', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-height' => array(
                    'title' => __( 'Product Height', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-review-count' => array(
                    'title' => __( 'Product Review Count', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-tax-status' => array(
                    'title' => __( 'Product Tax Status', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-tax-class' => array(
                    'title' => __( 'Product Tax Class', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-tags' => array(
                    'title' => __( 'Product Tags', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
				'product-attributes' => array(
                    'title' => __( 'Product Attributes', STACKABLE_I18N ),
                    'group' => __( 'WooCommerce', STACKABLE_I18N ),
					'data' => array(
						'field_type' => 'woocommerce',
					),
                ),
            ),
			$product_fields
        );
    }

	function render_description( $args, $product, $is_editor_content ) {
		if ( ! array_key_exists( 'strip_html', $args['args'] )  ) {
			return wp_kses_post( sanitize_text_field( $product->get_description() ) );
		}

        if ( array_key_exists( 'strip_html', $args['args'] ) && $args['args']['strip_html'] === 'true' ) {
			return wp_kses_post( sanitize_text_field( $product->get_description() ) );
        }

		// If strip html is false, return stripped content in the editor to prevent errors
		if ( array_key_exists( 'strip_html', $args['args'] ) && $args['args']['strip_html'] === 'false' && $is_editor_content ) {
			return wp_kses_post( sanitize_text_field( $product->get_description() ) );
		}

		return wp_kses_post( $product->get_description() );
	}


	function render_tax_class( $args, $product ) {

		$tax_class = $product->get_tax_class();
		switch ( $tax_class ) {
			case 'reduced-rate': return 'Reduced rate';
			case 'zero-rate': return 'Zero rate';
			default: return 'Standard';
		}

	}

	function render_tax_status( $args, $product ) {

		$tax_status = $product->get_tax_status();
		switch ( $tax_status ) {
			case 'none': return 'None';
			case 'taxable': return 'Taxable';
			case 'shipping': return 'Shipping Only';
		}

	}

	function render_image( $args, $product ) {

		$id = $product->get_image_id();

		$image_quality = isset( $args['args']['image_quality'] ) ? $args['args']['image_quality'] : 'large';

		$image_url = wp_get_attachment_image_url( $id, $image_quality );

		return $image_url;
	}

	function render_date( $args, $product ) {
		$date = '';

		if ( $args['field'] === 'product-date-created' ) {
			$date = $product->get_date_created() ? $product->get_date_created()->__toString() : '';
		}

		if ( $args['field'] === 'product-date-sale-from' ) {
			$date = $product->get_date_on_sale_from() ? $product->get_date_on_sale_from()->__toString() : '';
		}

		if ( $args['field'] === 'product-date-sale-to' ) {
			$date = $product->get_date_on_sale_to() ? $product->get_date_on_sale_to()->__toString() : '';
		}

		if ( array_key_exists( 'format', $args['args'] ) && $date !== '' ) {
			if ( $args['args']['format'] === 'custom' && array_key_exists( 'custom_format', $args['args'] ) ) {
				return Util::format_date( $date, $args['args']['custom_format'] );
			}
			return Util::format_date( $date, $args['args']['format'] );
		}

		return $date;
	}

	function render_attributes( $args, $product ) {
		$product_attributes = $product->get_attributes();
		$output = array();
		$delimiter = '';
		$query_loop_output = '';

		foreach ( $product_attributes as $attribute ) {
			if ( ! array_key_exists( 'attribute', $args['args'] ) ) {
				$temp_attribute = array();
				$terms = $attribute->get_terms();

				if ( ! $terms ) {
					$query_loop_output .= wc_attribute_label( $attribute->get_name(), $product ) . ": " . implode( ', ', $attribute->get_options() ) . " <br\>";
				} else {
					foreach( $terms as $term ) {
						array_push( $temp_attribute, $term->name );
					}
					$query_loop_output .= wc_attribute_label( $attribute->get_name(), $product ) . ": " . implode( ', ', $temp_attribute ) . " <br\>";
				}
			}

			if( array_key_exists( 'attribute', $args['args'] ) && $attribute->get_name() === $args['args']['attribute'] ) {
				// $terms will contain premade attributes from the attribute adder of woocommerce
				$terms = $attribute->get_terms();
				if ( ! $terms ) {
					$output = $attribute->get_options();
				} else {
					foreach( $terms as $term ) {
						array_push( $output, $term->name );
					}
				}
			}
		}

		if ( ! array_key_exists( 'attribute', $args['args'] ) ) {
			return $query_loop_output;
		}

		if ( array_key_exists( 'delimiter', $args['args'] ) ) {
			$delimiter = $args['args']['delimiter'];
		}

		return implode( $delimiter, $output );
	}


	function register_woocommerce_endpoint() {
		register_rest_route( 'stackable/v3', '/woocommerce/(?P<post_id>[\d]+)', array(
			'methods' => 'GET',
			'permission_callback' => function () {
				return current_user_can( 'edit_others_posts' );
			},
			'callback' => array( $this, 'get_attributes' ),
			'args' => array(
				'post_id' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					}
				)
			 )
		) );
	}

	function get_attributes( $args ) {

		if ( ! function_exists( 'wc_get_product' ) ) {
			return array();
		}

		$product = wc_get_product( $args['post_id'] );

		if ( ! $product ) {
			return array();
		}

		$product_attributes = $product->get_attributes();
		$output = array();

		foreach ( $product_attributes as $attribute ) {
			array_push( $output, array(
				'label' => wc_attribute_label( $attribute->get_name(), $product ),
				'value' => $attribute->get_name()
			) );
		}

		return $output;

	}


}

new Woocommerce();
