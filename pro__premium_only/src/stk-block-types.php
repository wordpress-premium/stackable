<?php
// This is a generated file by gulp generate-stk-premium-block-typesphp

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'stackable_get_premium_blocks_array') ) {
	function stackable_get_premium_blocks_array( $blocks = array() ) {
		$stk_blocks = array(
			'stackable/load-more' => [
				'api_version' => '3',
				'name' => 'stackable/load-more',
				'title' => __( 'Load More Button', STACKABLE_I18N ),
				'description' => __( 'Load more button for your Stackable Posts block', STACKABLE_I18N ),
				'category' => 'stackable',
				'parent' => [
					'stackable/posts'
				],
				'uses_context' => [
					'type',
					'orderBy',
					'order',
					'taxonomyType',
					'taxonomy',
					'taxonomyFilterType',
					'postOffset',
					'postExclude',
					'postInclude',
					'numberOfItems',
					'query',
					'queryId',
					'stkQueryId',
					'stackable/innerBlockOrientation'
				],
				'textdomain' => 'stackable-ultimate-gutenberg-blocks',
				'stk-type' => 'special'
			],
			'stackable/pagination' => [
				'api_version' => '3',
				'name' => 'stackable/pagination',
				'title' => __( 'Pagination', STACKABLE_I18N ),
				'description' => __( 'Pagination for your Stackable Posts block', STACKABLE_I18N ),
				'category' => 'stackable',
				'parent' => [
					'stackable/posts',
					'core/query'
				],
				'uses_context' => [
					'type',
					'orderBy',
					'order',
					'taxonomyType',
					'taxonomy',
					'taxonomyFilterType',
					'postOffset',
					'postExclude',
					'postInclude',
					'numberOfItems',
					'query',
					'queryId',
					'stkQueryId',
					'stackable/innerBlockOrientation'
				],
				'textdomain' => 'stackable-ultimate-gutenberg-blocks',
				'stk-type' => 'special'
			]
		);

		return array_merge( $blocks, $stk_blocks );
	}

	add_filter( 'stackable.blocks-premium', 'stackable_get_premium_blocks_array' );
}
?>