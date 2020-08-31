<?php
! defined( 'ABSPATH' ) and exit;

require_once( dirname(__FILE__) . '/polyfill.php');

if ( !function_exists('amzStore_bulk_wp_exist_post_by_args') ) {
	function amzStore_bulk_wp_exist_post_by_args( $args ) {
		global $WooZoneLite;
		return $WooZoneLite->bulk_wp_exist_post_by_args( $args );
	}
}

if ( !function_exists('WooZoneLite') ) {
	function WooZoneLite() {
		global $WooZoneLite;
		return $WooZoneLite;
	}
}

if ( !function_exists('WooZoneLite_product_by_asin') ) {
	function WooZoneLite_product_by_asin( $asins=array() ) {
		global $WooZoneLite;
		return $WooZoneLite->product_by_asin( $asins );
	}
}

if ( !function_exists('WooZoneLite_asset_path') ) {
	function WooZoneLite_asset_path( $asset_type='css', $path='', $is_wp_enqueue=false, $pms=array() ) {
		global $WooZoneLite;
		return $WooZoneLite->plugin_asset_get_path( $asset_type, $path, $is_wp_enqueue, $pms );
	}
}

if ( !function_exists('WooZoneLite_asset_version') ) {
	function WooZoneLite_asset_version( $asset_type='css', $pms=array() ) {
		global $WooZoneLite;
		return $WooZoneLite->plugin_asset_get_version( $asset_type, $pms );
	}
}

if ( !function_exists('WooZoneLite_debugbar') ) {
	function WooZoneLite_debugbar() {
		global $WooZoneLite;
		return $WooZoneLite->debugbar;
	}
}

if ( !function_exists('WooZoneLite_doing_it_wrong') ) {
	function WooZoneLite_doing_it_wrong( $function, $message, $version ) {
		global $WooZoneLite;
		return $WooZoneLite->doing_it_wrong( $function, $message, $version );
	}
}

if ( !function_exists('WooZoneLite_get_template') ) {
	function WooZoneLite_get_template( $template_name, $pms=array() ) {
		global $WooZoneLite;
		return $WooZoneLite->tplsystem_get_template( $template_name, $pms );
	}
}

if ( !function_exists('WooZoneLite_get_template_html') ) {
	function WooZoneLite_get_template_html( $template_name, $pms=array() ) {
		global $WooZoneLite;
		return $WooZoneLite->tplsystem_get_template_html( $template_name, $pms );
	}
}

if ( !function_exists('WooZoneLite_locate_template') ) {
	function WooZoneLite_locate_template( $template_name, $pms=array() ) {
		global $WooZoneLite;
		return $WooZoneLite->tplsystem_locate_template( $template_name, $pms );
	}
}

if ( !function_exists('WooZoneLite_dropshiptax_is_active') ) {
	function WooZoneLite_dropshiptax_is_active() {
		global $WooZoneLite;
		return $WooZoneLite->dropshiptax_is_active();
	}
}

if ( !function_exists('WooZoneLite_disable_amazon_checkout') ) {
	function WooZoneLite_disable_amazon_checkout() {
		global $WooZoneLite;
		return $WooZoneLite->disable_amazon_checkout;
	}
}

if ( !function_exists('WooZoneLite_get_post_meta') ) {
	function WooZoneLite_get_post_meta( $post_id, $key='', $single=false, $withPrefix=true ) {
		global $WooZoneLite;
		return $WooZoneLite->get_post_meta( $post_id, $key, $single, $withPrefix );
	}
}

if ( !function_exists('WooZoneLiteDirectImport') ) {
	function WooZoneLiteDirectImport() {
		global $WooZoneLite;
		return $WooZoneLite->DirectImport;
	}
}