<?php
/**
 * Dummy module return as json_encode
 * http://www.aa-team.com
 * =======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		1.0
 */

global $WooZoneLite;

echo json_encode(
	array(
		$tryed_module['db_alias'] => array(

			/* define the form_messages box */
			'module_box' => ($WooZoneLite->get_plugin_status() != 'valid_hash' ? array(
					'title' 	=> __('Unlock - Premium SEO Pack', 'WooZoneLite'),
					'icon' 		=> '{plugin_folder_uri}images/validation_icon.png',
					'size' 		=> 'grid_4', // grid_1|grid_2|grid_3|grid_4
					'header' 	=> true, // true|false
					'toggler' 	=> false, // true|false
					'buttons' 	=> false, // true|false
					'style' 	=> 'panel', // panel|panel-widget

					// create the box elements array
					'elements'	=> array(
						array(
							'type' 		=> 'message',
							'status' 	=> 'info',
							'html' 		=> __('You need to log into your CodeCanyon account and go to your “Downloads” page. Locate this plugin you purchased in your “Downloads” list and click on the “License Certificate” link next to the download link. After you have downloaded the certificate you can open it in a text editor such as Notepad and copy the Item Purchase Code.', 'WooZoneLite'),
							//How to image: <a href="{plugin_folder_uri}images/howto-cc.jpg" target="_blank">link</a>
						)
						,'productKey' => array(
							'type' 		=> 'text',
							'std' 		=> '',
							'size' 		=> 'small',
							'title' 	=> __('Item Purchase Code', 'WooZoneLite'),
							'desc' 		=> __('Get it from CodeCanyon account and go to your “Downloads�? page.', 'WooZoneLite')
						)
						
						,'yourEmail' => array(
							'type' 		=> 'text',
							'std' 		=> get_option('admin_email'),
							'size' 		=> 'small',
							'title' 	=> __('Your Email', 'WooZoneLite'),
							'desc' 		=> __('We will notify you via this email about this product update and bug fix.', 'WooZoneLite')
						)
						
						,'sendActions' => array(
							'type' 		=> 'buttons',
							'options'	=> array(
								array(
									'action' 	=> 'WooZoneLite_activate_product',
									'width'		=> '100px',
									'type'		=> 'submit',
									'color'		=> 'green',
									'pos'		=> 'left',
									'value'		=> 'Activate now'
								)
							)
						)
					)
				) 
				: array(
					'title' 	=> __('Modules Manager', 'WooZoneLite'),
					'icon' 		=> '{plugin_folder_uri}images/menu_icon.png',
					'size' 		=> 'grid_4', // grid_1|grid_2|grid_3|grid_4
					'header' 	=> true, // true|false
					'toggler' 	=> false, // true|false
					'buttons' 	=> false, // true|false
					'style' 	=> 'panel', // panel|panel-widget

					// create the box elements array
					'elements'	=> array(
						array(
							'type' 		=> 'app',
							'path' 		=> '{plugin_folder_path}panel.php',
						)
					)
				)
			)
		)
	)
);