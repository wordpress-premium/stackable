<?php

/**
* Init Freemius.
*/
// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
exit;
}
// Create a helper function for easy SDK access.

class SugbFsNull {

public static function is_registered() {
return true;
}

public static function is__premium_only() {
return true;
}

public function can_use_premium_code() {
return true;
}

public function can_use_premium_code__premium_only() {
return true;
}

public static function get_upgrade_url() {
return false;
}

public static function contact_url() {
return false;
}

public static function get_plan_name() {
return 'business';
}

public static function is_plan( $plan, $exact = false ) {
return false;
}

public static function is_whitelabeled() {
return false;
}

public static function get_user() {
return wp_get_current_user();
}

function get_account_url( $action = false, $params = array(), $add_action_nonce = true ) {
return '';
}

function has_affiliate_program() {
return false;
}

function is_activation_mode( $and_on = true ) {
return false;
}
}

if ( !function_exists( 'sugb_fs' ) ) {
function sugb_fs()
{
global $sugb_fs ;

if ( !isset( $sugb_fs ) ) {
$sugb_fs = new SugbFsNull();
}

return $sugb_fs;
}

// Init Freemius.
sugb_fs();
// Signal that SDK was initiated.
do_action( 'sugb_fs_loaded' );
}