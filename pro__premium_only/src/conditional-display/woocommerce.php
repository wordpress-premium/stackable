<?php
/**
 * Conditional logic of the condition type User role.
 *
 * @package Stackable
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'stackable_conditional_display/woocommerce', function( $condition_is_met, $condition, $block_content, $block, $instance ) {
    $options = isset( $condition['options'] ) ? $condition['options'] : null;

	if ( ! function_exists( 'wc_get_product' ) ) {
		return $condition_is_met;
	}

    if ( $options && array_key_exists( 'product', $options ) && array_key_exists( 'product_operator', $options ) ) {
		$is_in_query_loop = ! empty( $instance ) && property_exists(  $instance, 'context' ) && array_key_exists( 'postId', $instance->context );
		$product_id = $is_in_query_loop ? $instance->context['postId'] : get_the_ID();
		$product = $options['product'] != 'current-post' ? wc_get_product( $options['product'] ) : wc_get_product( $product_id );
		$product_operator = $options['product_operator'] === 'true' ? true : false ;

		if ( ! $product ) {
			return $condition_is_met;
		}

		switch( $options['product_property'] ) {
			case 'downloadable': return $product->is_downloadable() === $product_operator;
			case 'featured': return $product->is_featured() === $product_operator;
			case 'in-stock': return $product->is_in_stock() === $product_operator;
			case 'backorder': return $product->is_on_backorder() === $product_operator;
			case 'sale': return $product->is_on_sale() === $product_operator;
			case 'purchasable': return $product->is_purchasable() === $product_operator;
			case 'shipping-taxable': return $product->is_shipping_taxable() === $product_operator;
			case 'sold-individually': return $product->is_sold_individually() === $product_operator;
			case 'taxable': return $product->is_taxable() === $product_operator;
			case 'sales':
			case 'stock-quantity':
				if ( ! array_key_exists( 'expected_value', $options ) ) {
					return $condition_is_met;
				}

				$expected_value = (int) $options['expected_value'];
				$wc_compared_value = $options['product_property'] === 'sales' ? $product->get_total_sales() : $product->get_stock_quantity();

				switch( $options['product_operator'] ) {
					case 'equal': return $wc_compared_value === $expected_value;
					case 'not-equal': return $wc_compared_value != $expected_value;
					case 'less-than': return $wc_compared_value < $expected_value;
					case 'less-than-equal': return $wc_compared_value <= $expected_value;
					case 'greater-than': return $wc_compared_value > $expected_value;
					case 'greater-than-equal': return $wc_compared_value >= $expected_value;
					default: return $condition_is_met;
				}
			default: return $condition_is_met;
		}
    }

	return $condition_is_met;
}, 10, 5 );


if ( ! function_exists( 'stackable_get_product_names' ) ) {
	function stackable_get_woocommerce_product_names( $product_id ) {
		$product = wc_get_product( $product_id )->get_name() . ' - ' . $product_id ;

		return array(
			'label' => __( $product, STACKABLE_I18N ),
			'value' => $product_id
		);
	}
}

if ( ! function_exists( 'stackable_get_woocommerce_products' ) ) {
	function stackable_get_woocommerce_products() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$products = wc_get_products( array(
			'orderby' => 'date',
			'order' => 'DESC',
			'return' => 'ids',
		) );

		$products = array_map( "stackable_get_woocommerce_product_names", $products );
		return $products;
	}
}


if ( ! function_exists( 'stackable_register_get_all_woocommerce_products_endpoint' ) ) {
	function stackable_register_get_all_woocommerce_products_endpoint() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			register_rest_route( 'stackable/v3', '/get_woocommerce_products/', array(
				'methods' => 'GET',
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'callback' => 'stackable_get_woocommerce_products'
			) );
		}
	}
	add_action( 'rest_api_init', 'stackable_register_get_all_woocommerce_products_endpoint', 2, 0 );
}
