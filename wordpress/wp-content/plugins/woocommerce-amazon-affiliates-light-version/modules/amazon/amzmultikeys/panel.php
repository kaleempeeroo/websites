<?php 
! defined( 'ABSPATH' ) and exit;
// load the modules managers class
$module_class_path = $module['folder_path'] . 'amzmultikeys/init.php';

if ( is_file($module_class_path) ) {

	require_once( $module_class_path );
		
	$WooZoneLiteMultipleAmazonKeys = new WooZoneLiteMultipleAmazonKeys($module);

	// print the lists interface
	echo $WooZoneLiteMultipleAmazonKeys->printSearchInterface();
}