<?php 
! defined( 'ABSPATH' ) and exit;
// load the modules managers class
$module_class_path = $module['folder_path'] . 'init.php';

if ( is_file($module_class_path) ) {

	require_once( 'init.php' );
		
	$WooZoneLiteMultipleAmazonKeys = new WooZoneLiteMultipleAmazonKeys($this->cfg, $module);
	
	$__module_is_setup_valid = $WooZoneLiteMultipleAmazonKeys->moduleValidation();
	$__module_is_setup_valid = (bool) $__module_is_setup_valid['status'];
		
	// print the lists interface
	//$provider_status = WooZoneLite()->provider_action_controller( 'can_import_products', 'amazon', array('msg_type' => 'box_demo') );
	//if ( 'invalid' == $provider_status['status'] ) {
	//	echo $provider_status['msg_html'];
	//}
	//else {
		echo $WooZoneLiteMultipleAmazonKeys->printSearchInterface();
	//}
}