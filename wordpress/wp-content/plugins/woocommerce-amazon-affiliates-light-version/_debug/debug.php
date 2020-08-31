<?php

	$req = array(
		'act'		=> isset($_REQUEST['act']) ? (string) $_REQUEST['act'] : '',
	);
	extract($req);

	$absolute_path = __FILE__;
	$path_to_file = explode( 'wp-content', $absolute_path );
	$path_to_wp = $path_to_file[0];

	/** Set up WordPress environment */
	require_once( $path_to_wp.'/wp-load.php' );
	global $WooZoneLite;

	@ini_set('max_execution_time', 0);
	@set_time_limit(0); // infinte
	//WooZoneLite_SyncProducts_event();

	//var_dump('<pre>', $WooZoneLite , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

	//:: get remote image size by curl
	if ( 'remote_image_size' == $act ) {
		$url = 'https://www.fotoartgeist.pl/ebaytemp/obrazy/c-A-0021-b-p/c-A-0021-b-p-eb10.jpg';
		$ret = $WooZoneLite->u->getimagesize( $url );
		var_dump('<pre>', $url, $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
	}

	//:: EBAY REMOTE IMAGES
	if ( 'ebay_remote_images' == $act ) {
		$product_id = 331035;
		$setRemoteImgStatus = $WooZoneLite->get_ws_object( 'generic' )->build_remote_images( $product_id );
		var_dump('<pre>', $setRemoteImgStatus , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
	}