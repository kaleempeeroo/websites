<?php
/**
 * Config file, return as json_encode
 * http://www.aa-team.com
 * =======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		1.0
 */
global $WooZoneLite;
echo json_encode(
	array(
		'addons' => array(
			'version' => '1.0',
			'menu' => array(
				'order' => 2,
				'show_in_menu' => true,
				'title' => 'Addons & Themes',
				'icon' => 'add-on'
			),
			'in_dashboard' => array(
				'icon' 	=> 'add-on',
				'url'	=> admin_url("admin.php?page=WooZoneLite_addons") //admin_url("admin.php?page=WooZoneLite#!/addons")
			),
			'help' => array(
				'type' => 'remote',
				'url' => 'http://docs.aa-team.com/woocommerce-amazon-affiliates/documentation/'
			),
			'description' => "WZoneLite Addons",
			'module_init' => 'init.php',
			'load_in' => array(
				'backend' => array(
					'admin.php?page=WooZoneLite_addons',
					'admin-ajax.php'
				),
				'frontend' => false
			),
			'javascript' => array(
				'admin',
				'hashchange',
				'tipsy'
			),
			'css' => array(
				'admin',
				'tipsy'
			)
		)
	)
);   
