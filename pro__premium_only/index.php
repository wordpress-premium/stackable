<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Premium Block Features Initializer.
 */
require_once( plugin_dir_path( __FILE__ ) . 'src/init.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/stk-block-types.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/blocks.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/security.php' );
// TODO: v3 block
require_once( plugin_dir_path( __FILE__ ) . 'src/block/posts/index.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/block/load-more/index.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/block/pagination/index.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/components/panel-design-user-library/ajax.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/custom-fields.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/block-components/effects-animations/init.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/welcome/index.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/icons.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/editor-mode.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/conditional-display/index.php' );

/**
 * Dynamic Content
 */
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/sources/util.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/sources/other-posts.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/sources/current-page.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/sources/custom-fields.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/sources/acf.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/sources/metabox.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/sources/jetengine.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/sources/woocommerce.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/sources/site.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/sources/latest-post.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/sources/user.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/dynamic-content/init.php' );

/**
 * Plugins
 */
require_once( plugin_dir_path( __FILE__ ) . 'src/plugins/icon-library/api.php' );
require_once( plugin_dir_path( __FILE__ ) . 'src/plugins/preset-controls/index.php' );

/**
 * Color Schemes
 */
require_once( plugin_dir_path( __FILE__ ) . 'src/plugins/color-schemes/index.php' );

/**
 * V2 Deprecated
 */
require_once( plugin_dir_path( __FILE__ ) . 'src/deprecated/v2/init.php' );
