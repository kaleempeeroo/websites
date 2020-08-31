<?php
/**
 * Dummy module return as json_encode
 * http://www.aa-team.com
 * =======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		0.1 - in development mode
 */

include_once( 'options-func.php');
include_once( 'options-func-ebay.php');
global $WooZoneLite;

function WooZoneLite_get_providers_subtabs__( $mainarr ) {
	global $WooZoneLite;

	$tabs =  $mainarr['tabs'];
	$subtabs = $mainarr['subtabs'];
	$elements = $mainarr['elements'];

	foreach ( $subtabs as $tab_id => $tab_elem ) {
		foreach ( $tab_elem as $subtab_id => $subtab_elem ) {
			$key = str_replace('__subtab_', '', $subtab_id);

			$provider_status = $WooZoneLite->provider_action_controller( 'has_addon_activated', $key, array() );

			if ( 'invalid' == $provider_status['status'] ) {
				//unset( $subtabs["$tab_id"]["$subtab_id"] );

				$elem_key = $tab_id . $subtab_id;

				$subtabs["$tab_id"]["$subtab_id"][1] = $elem_key;
				$tabs["$tab_id"][1] .= ", $elem_key";

				$subtab_opt = trim( $subtab_elem[1] );
				$subtab_opt = explode(',', $subtab_opt);
				$subtab_opt = array_map('trim', $subtab_opt);
				if ( ! empty($subtab_opt) ) {
					//var_dump('<pre>', array_keys($elements), $subtab_opt , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
					$elements = array_diff_key( $elements, array_flip($subtab_opt) );
				}

				$elem_css_class = array();
				$elem_css_class[] = 'panel-body WooZoneLite-panel-body WooZoneLite-form-row';
				$elem_css_class[] = $tab_id;
				$elem_css_class[] = $subtab_id;
				$elem_css_class = implode(' ', $elem_css_class);

				$elements["$elem_key"] = array(
					'type' => 'html',
					'std' => '',
					'size' => 'large',
					'title' => 'Affiliate Information',
					'html' => '<div class="' . $elem_css_class . '" style="display: block;">'
						. $WooZoneLite->provider_addon_info_box( $key )
						. '</div>',
				);
			}
		}
	}

	$mainarr['subtabs'] = $subtabs;
	$mainarr['elements'] = $elements;
	return $mainarr;
}

$__ = array(
// tabs
'tabs'	=> array(
	'__tab1'	=> array(
		__('Amazon SETUP', $WooZoneLite->localizationName),
		'amzapi, protocol, country, AccessKeyID, SecretAccessKey, AffiliateId, main_aff_id, buttons, help_required_fields, help_available_countries, amazon_requests_rate'
		. ', '
		. 'ebay_protocol, ebay_country, ebay_DEVID, ebay_AppID, ebay_CertID, ebay_AffiliateId, ebay_main_aff_id, ebay_buttons, ebay_help_required_fields, ebay_help_available_countries'
	),
	'__tab2'	=> array(
		__('Plugin SETUP', $WooZoneLite->localizationName),
		'show_free_shipping_details_link, gdpr_rules_is_activated, products_force_delete, onsite_cart, cross_selling, cross_selling_nbproducts, cross_selling_choose_variation, checkout_type, checkout_email, checkout_email_mandatory, export_checkout_emails, 90day_cookie, remove_gallery, remove_featured_image_from_gallery, show_short_description, redirect_time, show_review_tab, redirect_checkout_msg, product_buy_is_amazon_url, product_url_short, frontend_show_free_shipping, frontend_show_coupon_text, show_availability_icon, charset, services_used_forip, product_buy_text, remote_amazon_images, images_sizes_allowed, productinpost_additional_images, productinpost_extra_css, product_countries, product_countries_main_position, product_countries_maincart, product_countries_countryflags, product_buy_button_open_in, product_buy_custom_classes, asof_font_size, delete_attachments_at_delete_post, cache_remote_images, product_offerlistingid_missing_external, product_offerlistingid_missing_delete'
		. ', '
		. 'show_api_requests'
	),
	'__tab3'	=> array(
		__('Import SETUP', $WooZoneLite->localizationName),
		'price_setup, merchant_setup, product_variation, import_price_zero_products, default_import, import_type, ratio_prod_validate, item_attribute, selected_attributes, attr_title_normalize, cron_number_of_images, number_of_images, rename_image, spin_at_import, spin_max_replacements, create_only_parent_category, variation_force_parent, import_product_offerlistingid_missing, import_product_variation_offerlistingid_missing'
		. ', '
		. 'ebay_product_desc_type'
	),
	'__tab4'	=> array(
		__('BUG Fixes', $WooZoneLite->localizationName),
		'force_disable_images_srcset'
	),
	'__tab5'	=> array(
		__('DEBUG', $WooZoneLite->localizationName),
		'debug_bar_activate, debug_ip'
	),
	'__tab6'	=> array(
		__('String Translation', $WooZoneLite->localizationName),
		'string_trans'
	),
	// '__tab7'	=> array(
	// 	__('Dropshipping', $WooZoneLite->localizationName),
	// 	'disable_amazon_checkout, dropship, nocheckout_show_what'
	// ),
),
// end tabs

// subtabs
'subtabs'	=> array(
	'__tab1'	=> array(
		'__subtab_amazon' => array(
			__('General & Amazon', $WooZoneLite->localizationName), 'amzapi, protocol, country, AccessKeyID, SecretAccessKey, AffiliateId, main_aff_id, buttons, help_required_fields, help_available_countries, amazon_requests_rate'),
		'__subtab_ebay' => array(
			__('eBay', $WooZoneLite->localizationName), 'ebay_protocol, ebay_country, ebay_DEVID, ebay_AppID, ebay_CertID, ebay_AffiliateId, ebay_main_aff_id, ebay_buttons, ebay_help_required_fields, ebay_help_available_countries')
	),
	'__tab2'	=> array(
		'__subtab_amazon' => array(
			__('General & Amazon', $WooZoneLite->localizationName), 'show_free_shipping_details_link, gdpr_rules_is_activated, products_force_delete, onsite_cart, cross_selling, cross_selling_nbproducts, cross_selling_choose_variation, checkout_type, checkout_email, checkout_email_mandatory, export_checkout_emails, 90day_cookie, remove_gallery, remove_featured_image_from_gallery, show_short_description, redirect_time, show_review_tab, redirect_checkout_msg, product_buy_is_amazon_url, product_url_short, frontend_show_free_shipping, frontend_show_coupon_text, show_availability_icon, charset, services_used_forip, product_buy_text, remote_amazon_images, images_sizes_allowed, productinpost_additional_images, productinpost_extra_css, product_countries, product_countries_main_position, product_countries_maincart, product_countries_countryflags, product_buy_button_open_in, product_buy_custom_classes, asof_font_size, delete_attachments_at_delete_post, cache_remote_images, product_offerlistingid_missing_external, product_offerlistingid_missing_delete'),
		'__subtab_ebay' => array(
			__('eBay', $WooZoneLite->localizationName), 'show_api_requests')
	),
	'__tab3'	=> array(
		'__subtab_amazon' => array(
			__('General & Amazon', $WooZoneLite->localizationName), 'price_setup, merchant_setup, product_variation, import_price_zero_products, default_import, import_type, ratio_prod_validate, item_attribute, selected_attributes, attr_title_normalize, cron_number_of_images, number_of_images, rename_image, spin_at_import, spin_max_replacements, create_only_parent_category, variation_force_parent, import_product_offerlistingid_missing, import_product_variation_offerlistingid_missing'),
		'__subtab_ebay' => array(
			__('eBay', $WooZoneLite->localizationName), 'ebay_product_desc_type')
	),
	'__tab4'	=> array(
		'__subtab_amazon' => array(
			__('General & Amazon', $WooZoneLite->localizationName), ''),
		//'__subtab_ebay' => array(
		//	__('EBay', $WooZoneLite->localizationName), '')
	),
	'__tab5'	=> array(
		'__subtab_amazon' => array(
			__('General & Amazon', $WooZoneLite->localizationName), 'debug_bar_activate, debug_ip'),
		//'__subtab_ebay' => array(
		//	__('EBay', $WooZoneLite->localizationName), '')
	),
	'__tab6'	=> array(
		'__subtab_amazon' => array(
			__('General & Amazon', $WooZoneLite->localizationName), 'string_trans'),
		//'__subtab_ebay' => array(
		//	__('EBay', $WooZoneLite->localizationName), '')
	),
	// '__tab7'	=> array(
	// 	'__subtab_amazon' => array(
	// 		__('General & Amazon', $WooZoneLite->localizationName), 'disable_amazon_checkout, dropship, nocheckout_show_what'),
	// 	//'__subtab_ebay' => array(
	// 	//	__('EBay', $WooZoneLite->localizationName), '')
	// ),
),
// end subtabs

// create the box elements array
'elements' => array(

	/*'asof_font_size' => array(
		'type' => 'select',
		'std' => '0.6',
		'size' => 'large',
		'force_width' => '100',
		'title' => '"As Of" text font size',
		'desc' => 'Choose the font size (in em) for "as of" text',
		'options' => WooZoneLite_asof_font_size()
	),*/

	'onsite_cart' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'On-site Cart',
		'desc' => '
			<span style="color: green;">If you set this option to "YES", it will allow your customers to add multiple Woocommerce Amazon Products into your website	Cart and do the final checkout trough Amazon\'s system with all at once.</span>
			<br/>
			<span style="">If you set this option to "NO", all the simple/variable Woocommerce Amazon Products will be set as <strong>external</strong>.</span>
		',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'90day_cookie' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => '90 days cookies',
		'desc' => '
			<span style="color: red;"><span style="font-weight: bold;">In order for this to work: </span>
			<br/> - you need to have the "On-site Cart" option set to NO and the "Show Amazon Url as Buy Url" option set to NO
			<br/> - this option must be set on "YES"
			</span>
			<br/><span style="color: green;">So, if you choose YES and the above points are fulfilled, then the product buy url will redirect you instatly to amazon cart page with your product already added there.</span>
		',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'product_buy_is_amazon_url' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Show Amazon Url as Buy Url',
		'desc' => '
			<span style="color: red;"><span style="font-weight: bold;">In order for this to work: </span> the "On-site Cart" option must be set to "No"</span>
			<br/> <span style="color: green;">- If you choose YES, the product buy url will be the amazon product original url,
			<br/> ex.: https://www.amazon.com/gp/aws/cart/add.html?AssociateTag=&SubscriptionId=AKIAI2SZTIJCPKND45QA&ASIN.1=B073DLZWX7&Quantity.1=1 </span>
			<br/> <span style="">- If you choose NO, the product buy url will go through a wzone script so we can identify this as an amazon redirect,
			<br/> ex.: ' . site_url( '?redirectAmzASIN=B073DLZWX7&redirect_prodid=amz-B073DLZWX7' ) . ' </span>
		',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'product_url_short' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Get Product Short Url',
		'desc' => '
			<span style="color: red;"><span style="font-weight: bold;">In order for this to work: </span>
			<br/> - you must authorize bitly account in module (bottom AUTH section in the bitly module),
			<br/> - you need to have the option "Show Amazon Url as Buy Url" set to YES, and the "On-site Cart" option must be set to NO, so it works when you have external woocommerce amazon products.
			<br/> - this option must be set on "YES"
			</span>
			<br/><span style="color: green;">So, if you choose YES and the above points are fulfilled, then it will generate and use a product short url (using bitly api) when the product details page on frontend is accessed.</span>
		',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'_badges_box' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Badges / Flags',
		'html' => WooZoneLite_optfunc_badges_box( '__tab2', '__subtab_amazon' )
	),


	/*'dropship' => array(
		'type' => '',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '',
		'title' => 'What is Dropshipping?',
		'desc' => '<div style="color: black; font-size: 14px;">Dropshipping is a type of business model which enables your company to operate without maintaining inventory, owning a warehouse to store your products, or even having to ship your products to your customers yourself. </div> ',
	),

	'disable_amazon_checkout' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Disable amazon checkout?',
		'desc' => 'Choose Yes if you want to Disable the Amazon Checkout Feature.<br /><br />
		<div style="color: red;">This is how the Checkout Process will work if you use this feature: </br>
			1. You import Amazon products into your website. <br />
			2. Add a Dropshipping Tax to the products imported from Amazon. <br />
			3. A customer places an order for a product on the your online store.<br />
			4. You manually forward the order and customer details to the dropship supplier (order the products on Amazon).<br />
			5. The dropship supplier (Amazon) packages and ships the order directly to the Customer in the your name.<br />
			6. You get the Dropshipping Tax Difference!<br />
			Important! Some products might have extra shipping costs, so take that in consideration as well.
		</div>',
		'options' => array(
			'no' => 'NO',
			'yes' => 'YES'
		)
	),

	'_dropshiptax_box' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Products Price Dropshipping Tax',
		'html' => WooZoneLite_optfunc_dropshiptax_box( '__tab7', '__subtab_amazon' )
	),

	'nocheckout_show_what' 	=> array(
		'type' 		=> 'multiselect_left2right',
		'std' 		=> array(
			//'checkout_email',
			//'cross_sell',
			//'product_url_short',
			'syncfront_activate',
			//'product_countries',
			'show_review_tab',
			//'show_availability_icon',
		),
		'size' 		=> 'large',
		'rows_visible'	=> 8,
		'force_width'=> '150',
		'title' 	=> __('Select functionalities to be showed on frontend', $WooZoneLite->localizationName),
		'desc' 		=> __('Choose what functionalities to use on frontend.', $WooZoneLite->localizationName),
		'info'		=> array(
			'left' => 'All items list',
			'right' => 'Your chosen items from list'
		),
		'options' 	=> array(
			'checkout_email' => 'Checkout E-mail',
			'cross_sell' => 'Cross selling',
			'product_url_short' => 'Get Product Short Url (Bitly)',
			'syncfront_activate' => 'Sync Products on Frontend',
			'product_countries' => 'Activate Product Availability by Country Box',
			'show_review_tab' => 'Review tab',
			'show_availability_icon' => 'Show availability icon',
		)
	),*/

	'show_free_shipping_details_link' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Display Free Shipping Details Link',
		'desc' => '',
		'options' => array(
			'no' => 'NO',
			'yes' => 'YES'
		)
	),

	'gdpr_rules_is_activated' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Activate GDPR Compliance?',
		'desc' => 'On 25 May 2018, EU’s General Data Protection Regulation (GDPR) will come into force. <div style="color: red; font-weight: bold;">You need to set this option on "NO" if you know that your website needs to be compliant with GDPR rules.</div>',
		'options' => array(
			'no' => 'NO',
			'yes' => 'YES'
		)
	),

	'products_force_delete' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Product : Delete | Move to Trash',
		'desc' => '<strong>Choose YES</strong> if you want to actually <span style="color: red;">remove product (or a variation)</span> when: a) bug fix "Delete all products with price zero", b) synchronization process doesn\'t find a product or a variation. <br />If you <strong>choose NO</strong>, then it will <span style="color: red;">only be moved to trash</span> - depending on the setting for <strong>Put amazon products in trash when syncing after</strong> from Bug FIXES tab.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'services_used_forip' => array(
		'type' => 'select',
		'std' => 'www.geoplugin.net',
		'size' => 'large',
		'force_width' => '380',
		'title' => 'External server country detection or use local:',
		'desc' => 'We use an external server for detecting client country per IP address or you can try local IP detection. ( www.telize.com was shut down on November 15th, 2015 || api.hostip.info not working anymore )',
		'options' => array(
			'local_csv'                 => 'Local IP detection (plugin local csv file with IP range lists)',
			//'api.hostip.info'           => 'api.hostip.info',
			'www.geoplugin.net' 		=> 'www.geoplugin.net',
			//'www.telize.com'			=> 'www.telize.com',
			'ipinfo.io' 				=> 'ipinfo.io',
		)
	),

	'charset' 	=> array(
		'type' 		=> 'text',
		'std' 		=> '',
		'size' 		=> 'large',
		'force_width'=> '400',
		'title' 	=> __('Server Charset:', $WooZoneLite->localizationName),
		'desc' 		=> __('Server Charset (used by php-query class)', $WooZoneLite->localizationName)
	),

	/*'frontend_show_free_shipping' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Show Free Shipping',
		'desc' => 'Show Free Shipping text on frontend.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),*/
	'frontend_show_coupon_text' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Show Coupon',
		'desc' => 'Show Coupon text on frontend.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	/*'checkout_type' => array(
		'type' => 'select',
		'std' => '_self',
		'size' => 'large',
		'force_width' => '200',
		'title' => 'Checkout type:',
		'desc' => 'This option will allow you to setup how the Amazon Checkout process will happen. If you wish to open the amazon products into a new tab, or in the same tab.',
		'options' => array(
			'_self' => 'Self - into same tab',
			'_blank' => 'Blank - open new tab'
		)
	),*/

	'checkout_email' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Checkout E-mail:',
		'desc' => 'Ask the user e-mail address before the checkout process (redirect to amazon) happens and store it for later export in CSV format.',
		'options' => array(
			'no' => 'NO',
			'yes' => 'YES'
		)
	),

	'checkout_email_mandatory' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Checkout E-mail Mandatory:',
		'desc' => 'Make "Checkout E-mail" option above mandatory in order to checkout.',
		'options' => array(
			'no' => 'NO',
			'yes' => 'YES'
		)
	),

	'export_checkout_emails' => array(
		'type' => 'html',
		'html' => '<div class="panel-body WooZoneLite-panel-body WooZoneLite-form-row  __tab2 __subtab_amazon" style="display: block;">
			<label for="export_checkout_emails" class="WooZoneLite-form-label">Export Checkout Emails:</label>
			<div class="WooZoneLite-form-item">
				<a href="'. ( admin_url( 'admin.php?page=' . WooZoneLite()->alias ) ) .'&do=export_emails#!/amazon" id="export_checkout_emails" class="WooZoneLite-form-button-small WooZoneLite-form-button-info">Export Emails</a>
				<span class="WooZoneLite-form-note">Export as CSV checkout emails sent by customers.</span>
			</div>
		</div>',
	),

	'item_attribute' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Import Attributes',
		'desc' => 'This option will allow to import or not the product item attributes.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'selected_attributes' 	=> array(
		'type' 		=> 'multiselect_left2right',
		'std' 		=> array(),
		'size' 		=> 'large',
		'rows_visible'	=> 18,
		'force_width'=> '300',
		'title' 	=> __('Select attributes', $WooZoneLite->localizationName),
		'desc' 		=> __('Choose what attributes to be added on import process.', $WooZoneLite->localizationName),
		'info'		=> array(
			'left' => 'All Amazon Attributes list',
			'right' => 'Your chosen items from list'
		),
		'options' 	=> WooZoneLite_attributesList()
	),

	'attr_title_normalize' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Beautify attribute title',
		'desc' => 'separate attribute title words by space',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'price_setup' => array(
		'type' => 'select',
		'std' => 'only_amazon',
		'size' => 'large',
		'force_width' => '290',
		'title' => 'Prices setup',
		'desc' => 'Get product offer price from Amazon or other Amazon sellers.',
		'options' => array(
			'only_amazon' => 'Only Amazon',
			'amazon_or_sellers' => 'Amazon OR other sellers (get lowest price)'
		)
	),

	'merchant_setup' => array(
		'type' => 'select',
		'std' => 'amazon_or_sellers',
		'size' => 'large',
		'force_width' => '290',
		'title' => 'Import product from merchant',
		'desc' => 'Get products: A. only from Amazon or B. from (Amazon and other sellers).<br /><div style="color: red;">ATTENTION: If you choose "Only Amazon" then only product which have Amazon among their sellers will be imported!</div>',
		'options' => array(
			'only_amazon' => 'Only Amazon',
			'amazon_or_sellers' => 'Amazon and other sellers'
		)
	),

	'import_price_zero_products' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Import products with price 0',
		'desc' => 'Choose Yes if you want to import products with price 0',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'default_import' => array(
		'type' => 'select',
		'std' => 'publish',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Import as',
		'desc' => 'Default import products with status "publish" or "draft"',
		'options' => array(
			'publish' => 'Publish',
			'draft' => 'Draft'
		)
	),

	'import_type' => array(
		'type' => 'select',
		'std' => 'default',
		'size' => 'large',
		'force_width' => '280',
		'title' => 'Image Import type',
		'options' => array(
			'default' => 'Default - download images at import',
			'asynchronous' => 'Asynchronous image download'
		)
	),
	'ratio_prod_validate' 	=> array(
		'type' 		=> 'select',
		'std'		=> 90,
		'size' 		=> 'large',
		'title' 	=> __('Ratio product validation:', $WooZoneLite->localizationName),
		'force_width'=> '100',
		'desc' 		=> __('The minimum percentage of total assets download (product + variations) from which a product is considered valid!', $WooZoneLite->localizationName),
		'options'	=> $WooZoneLite->doRange( range(10, 100, 5) )
	),
	/*'number_of_images_variation' => array(
		'type' => 'text',
		'std' => 'all',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Number of images for variation',
		'desc' => 'How many images to download for each product variation. Default is <code>all</code>'
	),*/
	'rename_image' => array(
		'type' => 'select',
		'std' => 'product_title',
		'size' => 'large',
		'force_width' => '130',
		'title' => 'Image names',
		'options' => array(
			'product_title' => 'Product title',
			'random' => 'Random number'
		)
	),

	'remove_gallery' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Gallery',
		'desc' => 'Show gallery in product description.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),
	 'remove_featured_image_from_gallery' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Remove featured image from product gallery',
		'desc' => 'Remove featured image from product gallery if the theme does not support it',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),
	'show_short_description' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Product Short Description',
		'desc' => 'Show product short description.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),
	'show_review_tab' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Review tab',
		'desc' => 'Show Amazon reviews tab in product description.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),
	'redirect_checkout_msg' => array(
		'type' => 'textarea',
		'std' => 'You will be redirected to {amazon_website} to complete your checkout!',
		'size' => 'large',
		'force_width' => '160',
		'title' => 'Checkout message',
		'desc' => 'Message for checkout redirect box.'
	),
	'redirect_time' => array(
		'type' => 'text',
		'std' => '3',
		'size' => 'large',
		'force_width' => '120',
		'title' => 'Redirect in',
		'desc' => 'How many seconds to wait before redirect to Amazon!'
	),

/*
	'product_buy_text'   => array(
		'type'      => 'text',
		'std'       => '',
		'size'      => 'large',
		'force_width'=> '400',
		'title'     => __('Button buy text', $WooZoneLite->localizationName),
		'desc'      => __('(global) This text will be shown on the button linking to the external product. (global) = all external products; external products = those with "On-site Cart" option value set to "No"', $WooZoneLite->localizationName)
	),

	'product_buy_button_open_in' => array(
		'type' => 'select',
		'std' => '_self',
		'size' => 'large',
		'force_width' => '200',
		'title' => 'Product buy button open in:',
		'desc' => 'This option will allow you to setup how the product buy button will work. You can choose between opening in the same tab or in a new tab.' ,
		'options' => array(
			'_self' => 'Same tab',
			'_blank' => 'New tab'
		)
	),

	'product_buy_custom_classes'   => array(
		'type'      => 'text',
		'std'       => '',
		'size'      => 'large',
		'force_width'=> '400',
		'title'     => __('Buy Button custom classes', $WooZoneLite->localizationName),
		'desc'      => __('This option allows you to add custom classes to your Buy Now button. Normally this is used on custom themes where the Buy Now button layout displays different that it should. Add classes using spaces: Eg: button_class button_class_two', $WooZoneLite->localizationName)
	),
*/
	'_product_buy_box' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Product Buy Button',
		'html' => WooZoneLite_optfunc_product_buy_box( '__tab2', '__subtab_amazon' )
	),

	'spin_at_import' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Spin on Import',
		'desc' => 'Choose YES if you want to auto spin post, page content at amazon import',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),
	'spin_max_replacements' => array(
		'type' => 'select',
		'std' => '10',
		'force_width' => '150',
		'size' => 'large',
		'title' => 'Spin max replacements',
		'desc' => 'Choose the maximum number of replacements for auto spin post, page content at amazon import.',
		'options' => array(
			'10' 		=> '10 replacements',
			'30' 		=> '30 replacements',
			'60' 		=> '60 replacements',
			'80' 		=> '80 replacements',
			'100' 		=> '100 replacements',
			'0' 		=> 'All possible replacements',
		)
	),

	'create_only_parent_category' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Create only parent categories on Import',
		'desc' => 'This option will create only parent categories from Amazon on import instead of the whole category tree',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	/*'selected_category_tree' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Create only selected category tree on Import',
		'desc' => 'This option will create only selected categories based on browsenodes on import instead of the whole category tree',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),*/

	'variation_force_parent' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Force import parent if is variation',
		'desc' => 'This option will force import parent if the product is a variation child.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	/* remote amazon images */
	'remote_amazon_images' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Remote amazon images',
		'desc' => 'Choose YES if you don\'t want to download on your local server the amazon images for products, but use them external.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'images_sizes_allowed' 	=> array(
		'type' 		=> 'multiselect_left2right',
		'std' 		=> array(), //array('thumbnail', 'medium', 'shop_thumbnail', 'shop_catalog'),
		'size' 		=> 'large',
		'rows_visible'	=> 8,
		'force_width'=> '150',
		'title' 	=> __('Select remote image sizes', $WooZoneLite->localizationName),
		'desc' 		=> __('Choose what remote image sizes you want.', $WooZoneLite->localizationName),
		'info'		=> array(
			'left' => 'All image sizes',
			'right' => 'Your chosen image sizes from list'
		),
		'options' 	=> WooZoneLite_imageSizes()
	),

	/*'clean_duplicate_attributes' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Clean duplicate attributes',
		'desc' => 'Clean duplicate attributes.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),*/

	'clean_woozonelite_log_tables_now' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Clean Woozone Logs Tables Now',
		'html' => WooZoneLite_clean_log_tables( '__tab4', '__subtab_amazon' )
	),

	'clean_duplicate_attributes_now' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Clean duplicate attributes Now',
		'html' => WooZoneLite_attributes_clean_duplicate( '__tab4', '__subtab_amazon' )
	),

	'clean_duplicate_category_slug_now' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Clean duplicate category slug Now',
		'html' => WooZoneLite_category_slug_clean_duplicate( '__tab4', '__subtab_amazon' )
	),

	'delete_all_zero_priced_products' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Delete all products with price zero',
		'html' => WooZoneLite_delete_zeropriced_products( '__tab4', '__subtab_amazon' )
	),

	'clean_orphaned_amz_meta' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Clean orphaned Amz meta Now',
		'html' => WooZoneLite_clean_orphaned_amz_meta( '__tab4', '__subtab_amazon' )
	),

	'clean_orphaned_products_assets' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Clean orphaned WooZoneLite Product Assets Now',
		'html' => WooZoneLite_clean_orphaned_prod_assets( '__tab4', '__subtab_amazon' )
	),

	'clean_orphaned_products_assets_wp' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Clean orphaned Wordpress Product Attachments Now',
		'html' => WooZoneLite_clean_orphaned_prod_assets_wp( '__tab4', '__subtab_amazon' )
	),

	'fix_product_attributes_now' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Fix Product Attributes (after woocommerce 2.4 update)',
		'html' => WooZoneLite_fix_product_attributes( '__tab4', '__subtab_amazon' )
	),

	'fix_node_children' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Clear Search old Node Childrens',
		'html' => WooZoneLite_fix_node_childrens( '__tab4', '__subtab_amazon' )
	),

	/* Amazon Config */
	'amzapi' => array(
		'type' => 'select',
		'std' => 'newapi',
		'size' => 'large',
		'force_width' => '400',
		'title' => 'Use Amazon API?',
		'desc' => 'Choose what amazon PA Api you want to use.',
		'options' => array(
			'oldapi' => 'Old API (4.0)',
			'newapi' => 'New API (5.0 - 14 January 2020)',
		)
	),

	'protocol' => array(
		'type' => 'select',
		'std' => '',
		'size' => 'large',
		'force_width' => '300',
		'title' => 'Request Type',
		'desc' => 'How the script should make the request to Amazon API.',
		'options' => array(
			'auto' => 'Auto Detect',
			'soap' => 'SOAP',
			'xml' => 'XML (over cURL, streams, fsockopen)'
		)
	),

	'country' => array(
		'type' => 'select',
		'std' => '',
		'size' => 'large',
		'force_width' => '150',
		'title' => 'Amazon location',
		'desc' => 'All possible amazon stores',
		'options' => WooZoneLite_amazon_countries( '__tab1', '__subtab_amazon', 'country' )
	),

	'help_required_fields' => array(
		'type' => 'message',
		'status' => 'info',
		'html' => 'The following fields are required in order to send requests to Amazon and retrieve data about products and listings. If you do not already have access keys set up, please visit the <a href="https://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&amp;action=access-key#access_credentials" target="_blank">AWS Account Management</a> page to create and retrieve them.'
	),

	'panel_multiple_amazon_keys' => array(
		'type' => 'app',
		'path' => '{plugin_folder_path}amzmultikeys/panel.php',
	),

	/*
	'AccessKeyID' => array(
		'type' => 'text',
		'std' => '',
		'size' => 'large',
		'title' => 'Access Key ID',
		'force_width' => '250',
		'desc' => 'Are required in order to send requests to Amazon API.'
	),
	'SecretAccessKey' => array(
		'type' => 'text',
		'std' => '',
		'size' => 'large',
		'force_width' => '400',
		'title' => 'Secret Access Key',
		'desc' => 'Are required in order to send requests to Amazon API.'
	),
	'buttons' => array(
		'type' => 'buttons',
		'options' => array(
			'check_amz' => array(
				'type' => 'button',
				'value' => 'Check Amazon AWS Keys',
				'color' => 'info',
				'action' => 'WooZoneLiteCheckAmzKeys'
			)
		)
	),
	*/
	'AffiliateId' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Affiliate Information',
		'html' => WooZoneLiteAffIDsHTML( '__tab1', '__subtab_amazon' )
	),
	'main_aff_id' => array(
		'type' => 'select',
		'std' => '',
		'force_width' => '150',
		'size' => 'large',
		'title' => 'Main Affiliate ID',
		'desc' => 'This Affiliate id will be use in API request and if user are not from any of available amazon country.',
		'options' => WooZoneLite_amazon_countries( '__tab1', '__subtab_amazon', 'main_aff_id' )
	),
	'help_available_countries' => array(
		'type' => 'message',
		'status' => 'info',
		'html' => '
				<strong>Available countries: &nbsp;</strong>
				'.WooZoneLite_amazon_countries( '__tab1', '__subtab_amazon', 'string' ).'
			'
	),
	'amazon_requests_rate' => array(
		'type' => 'select',
		'std' => '1',
		'force_width' => '200',
		'size' => 'large',
		'title' => 'Amazon requests rate',
		'desc' => 'The number of <a href="https://affiliate-program.amazon.com/gp/advertising/api/detail/faq.html" target="_blank">amazon requests per second</a> based on 30-day sales for your account.',
		'options' => array(
			'0.10' => '1 req per 10sec',
			'0.20' => '1 req per 5sec',
			'0.25' => '1 req per 4sec',
			'0.5' => '1 req per 2sec',
			'1' => '1 req per sec - till 2299$',
			'2' => '2 req per sec - till 9999$',
			'3' => '3 req per sec - till 19999$',
			'5' => '5 req per sec - from 20000$',
		)
	),

	'fix_issue_request_amazon_now' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Fix Request Amazon Issue',
		'html' => WooZoneLite_fix_issue_request_amazon( '__tab4', '__subtab_amazon' )
	),

	'fix_issue_sync' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Sync Issue',
		'html' => WooZoneLite_fix_issue_sync( '__tab4', '__subtab_amazon' )
	),

	'reset_products_stats_now' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Reset products stats',
		'html' => WooZoneLite_reset_products_stats( '__tab4', '__subtab_amazon' )
	),

	'options_prefix_change_now' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Version 9.0 options prefix change',
		'html' => WooZoneLite_options_prefix_change( '__tab4', '__subtab_amazon' )
	),

	'unblock_cron' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Unblock CRON jobs',
		'html' => WooZoneLite_unblock_cron( '__tab4', '__subtab_amazon' )
	),

	/* Product in post */
	'productinpost_additional_images' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Product in post: Show Additional Images',
		'desc' => 'Product in post: Show Additional Images',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),
	'productinpost_extra_css' => array(
		'type' => 'textarea',
		'std' => '',
		'size' => 'large',
		'force_width' => '560',
		'title' => 'Product in post: Extra CSS',
		'desc' => 'Product in post: Extra CSS for frontend boxes' . PHP_EOL . '<div style="height: 100px; overflow: auto;"><pre>' . WooZoneLite_productinpost_extra_css() . '</pre></div>'
	),

	/* product available countries */
	'product_countries' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Activate Product Availability by Country Box',
		'desc' => 'Choose YES if you want to activate product Availability by countries functionality',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),
	'product_countries_main_position' => array(
		'type' => 'select',
		'std' => 'before_add_to_cart',
		'size' => 'large',
		'force_width' => '500',
		'title' => 'Product Availability by <br/> Country Box',
		'desc' => 'This box will be positioned on product details page. Select where to display it:',
		'options' => array(
			'before_title_and_thumb'			=> 'Before Title and Thumb',
			'before_add_to_cart'					=> 'Before Add to Cart Button',
			'before_woocommerce_tabs'	=> 'Before Woocommerce Tabs',
			'as_woocommerce_tab'			=> 'As New Woocommerce Tab - COUNTRIES AVAILABLITY',
		)
	),
	'product_countries_maincart' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Show Country Flag on Cart Page?',
		'desc' => 'Choose YES if you want to show the current selected country for each product on cart page',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),
	'product_countries_countryflags' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Country Flags as Links?',
		'desc' => 'Choose YES if you want to show the country flags as links, on product details page.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	/*'product_countries_where' 	=> array(
		'type' 		=> 'multiselect_left2right',
		'std' 			=> array('maincart', 'minicart'),
		'size' 		=> 'large',
		'rows_visible'	=> 2,
		'force_width'=> '300',
		'title' 	=> __('Where product current selected country is showed?', $WooZoneLite->localizationName),
		'desc' 		=> __('Choose where you want to have an indicator of product current selected country', $WooZoneLite->localizationName),
		'info'		=> array(
			'left' => 'Extra zones',
			'right' => 'Your chosen extra zones'
		),
		'options' 	=> array(
			'maincart'			=> 'frontend main cart page',
			'minicart'			=> 'frontend mini cart box'
		)
	),*/

	'delete_attachments_at_delete_post' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Delete attachments also when you delete product?',
		'desc' => '<span style="color: red;">ATTENTION: If you choose YES, then all product attachements will be removed from database (and from your hard-drive if don\'t use the "remote images" option). So you must be sure that you\'re product attachments aren\'t used in other posts, without being directly attached to them.</span>',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'cross_selling' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Cross-selling',
		'desc' => 'Show Frequently Bought Together box.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'cross_selling_nbproducts' => array(
		'type' => 'select',
		'std' => '3',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Cross-selling Nb Products',
		'desc' => 'Choose how many products do you want to display in your "Frequently Bought Together box" box.',
		'options' => $WooZoneLite->doRange( range(3, 10, 1) )
	),

	'cross_selling_choose_variation' => array(
		'type' => 'select',
		'std' => 'first',
		'size' => 'large',
		'force_width' => '200',
		'title' => 'Cross-selling Variable Product',
		'desc' => 'If we encounter variable products when we try to build the cross sell box, we must choose one of their coresponding variation children to be, because you cannot buy main variable products, but only one of their variations. We also don\'t take into consideration variations without a valid non-zero price. So choose here which variation should we get for each encountered variable product.',
		'options' => array(
			'first' => 'First variation',
			'lowest_price' => 'Lowest price variation',
			'highest_price' => 'Highest price variation'
		)
	),

	'string_trans' => array(
		'type' => 'translation',
		'std' => '',
		'size' => 'large',
		'force_width' => '160',
		'title' => 'Strings',
		'options' => WooZoneLite()->expressions,
		'desc' => 'Using this option you can translate WooZoneLite strings.'
	),

	//:: offerlistingid related
	'import_product_offerlistingid_missing' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Import products with missing offerListingId',
		'desc' => 'Choose Yes if you want to import amazon products which don\'t have an offerListingId. <br/><span style="color: red;">When importing products, this should filter some of the products existent in amazon stores, but which aren\'t currently available to be bought.</span> <br />According to amazon docs: <a href="https://docs.aws.amazon.com/AWSECommerceService/latest/DG/CheckingforanOfferListingID.html" target="_blank" style="font-weight: bold;">If an item is for sale, it has an offer listing ID</a>',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'import_product_variation_offerlistingid_missing' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Import product variations with missing offerListingId',
		'desc' => 'Choose Yes if you want to import amazon product variations (for variable products) which don\'t have an offerListingId. <br/><span style="color: red;">When importing products, this should filter some of the product variations (for variable products) existent in amazon stores, but which aren\'t currently available to be bought.</span> <br />According to amazon docs: <a href="https://docs.aws.amazon.com/AWSECommerceService/latest/DG/CheckingforanOfferListingID.html" target="_blank" style="font-weight: bold;">If an item is for sale, it has an offer listing ID</a>',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'product_offerlistingid_missing_external' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Products with missing offerListingId => External',
		'desc' => 'Choose Yes if you want to convert all amazon products which don\'t have an offerListingId to product type EXTERNAL. <br/><span style="color: red;">For this to work, you need to have the "SYNCHRONISATION" module activated and SYNCHRONISATION SETTINGS must have Price checked to be synced</span> <br />According to amazon docs: <a href="https://docs.aws.amazon.com/AWSECommerceService/latest/DG/CheckingforanOfferListingID.html" target="_blank" style="font-weight: bold;">If an item is for sale, it has an offer listing ID</a>',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'product_offerlistingid_missing_delete' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Products with missing offerListingId => Delete | Trash',
		'desc' => 'This action is influenced by "Product : Delete | Move to Trash" option /Plugin SETUP tab. <br />Choose Yes if you want to ( remove | put in trash ) an amazon product (or just a variation) which don\'t have an offerListingId, when syncing it. <br/><span style="color: red;">For this to work, you need to have the "SYNCHRONISATION" module activated</span> <br />According to amazon docs: <a href="https://docs.aws.amazon.com/AWSECommerceService/latest/DG/CheckingforanOfferListingID.html" target="_blank" style="font-weight: bold;">If an item is for sale, it has an offer listing ID</a>',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'reset_sync_stats_now' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Reset SYNC stats',
		'html' => WooZoneLite_reset_sync_stats( '__tab4', '__subtab_amazon' )
	),

	'force_disable_images_srcset' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Disable Wordpress Images srcset attributes',
		'desc' => 'Choose Yes if you want to disable Wordpress images srcset attributes. This option can be activated on custom themes when product images are not showing.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	// DEBUG
	'debug_bar_activate' => array(
		'type' => 'select',
		'std' => 'no',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Activate WooZoneLite Debug Bar',
		'desc' => 'Choose Yes if you want to activate the Woozone Debug Bar.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),

	'debug_ip' => array(
		'type' => 'textarea',
		'std' => '',
		'size' => 'large',
		'force_width' => '160',
		'title' => 'Debug IP List',
		'desc' => 'You need to enter the IPs (separated by comma) for which you want to activate the plugin debug mode.<br/><em>For now debug mode only display the amazon response message for "frequently bought togheter" or "cross sell" frontend box.</em>'
	),

	/*'_load_javascript' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => '',
		'html' => "
		<script>
			//WooZoneLite.aateam_tooltip();
		</script>
		",
	),*/

	'show_availability_icon' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Show availability icon',
		'desc' => 'If you choose YES then the a text (regarding product availability to be delivered) similiar to "Usually ships in 24 hours" will be showed on your website frontend product details page, mostly after short descripton (but it depends on the current theme you use).',
		'options' => array(
			'no' => 'NO',
			'yes' => 'YES'
		)
	),

	'_product_price_disclaimer' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Product Price Disclaimer',
		'html' => WooZoneLite_optfunc_product_price_disclaimer( '__tab2', '__subtab_amazon' )
	),




	//=======================================================================
	//== Ebay
	//=======================================================================
	'ebay_protocol' => array(
		'type' => 'select',
		'std' => 'xml',
		'size' => 'large',
		'force_width' => '200',
		'title' => 'Request Type',
		'desc' => 'How the script should make the request to Ebay API.',
		'options' => array(
			//'auto' => 'Auto Detect',
			//'soap' => 'SOAP',
			'xml' => 'XML (over cURL, streams, fsockopen)'
		)
	),
	'ebay_country' => array(
		'type' => 'select',
		'std' => 'EBAY-US',
		'size' => 'large',
		'force_width' => '300',
		'title' => 'Ebay locations',
		'desc' => 'All possible locations.',
		'options' => WooZoneLite_ebay_countries__( '__tab1', '__subtab_ebay' ),
	),
	'ebay_help_required_fields' => array(
		'type' => 'message',
		'status' => 'info',
		'html' => 'The following fields are required in order to send requests to Ebay and retrieve data about products and listings. If you do not already have access keys set up, please visit the <a href="https://developer.ebay.com/DevZone/account/Default.aspx" target="_blank">Ebay WS Account Management</a> page to create and retrieve them.'
	),
	'ebay_DEVID' => array(
		'type' => 'text',
		'std' => '',
		'size' => 'large',
		'title' => 'DEVID',
		'force_width' => '300',
		'desc' => 'The following fields are required in order to send requests to eBay and retrieve data about products and listings. The DEVID, APPID and CERTID you obtain by joining the <a target="_blank" href="http://developer.ebay.com/join" target="_blank">eBay Developers Program</a>.'
	),
	'ebay_AppID' => array(
		'type' => 'text',
		'std' => '',
		'size' => 'large',
		'force_width' => '300',
		'title' => 'AppID',
		'desc' => 'The following fields are required in order to send requests to eBay and retrieve data about products and listings. The DEVID, APPID and CERTID you obtain by joining the <a target="_blank" href="http://developer.ebay.com/join" target="_blank">eBay Developers Program</a>.'
	),
	'ebay_CertID' => array(
		'type' => 'text',
		'std' => '',
		'size' => 'large',
		'force_width' => '300',
		'title' => 'CertID',
		'desc' => 'The following fields are required in order to send requests to eBay and retrieve data about products and listings. The DEVID, APPID and CERTID you obtain by joining the <a target="_blank" href="http://developer.ebay.com/join" target="_blank">eBay Developers Program</a>.'
	),
	'ebay_AffiliateId' => array(
		'type' => 'html',
		'std' => '',
		'size' => 'large',
		'title' => 'Affiliate campid Information',
		'html' => WooZoneLiteAffIDsHTML___ebay( '__tab1', '__subtab_ebay' )
	),
	'ebay_main_aff_id' => array(
		'type' => 'select',
		'std' => 'EBAY-US',
		'force_width' => '300',
		'size' => 'large',
		'title' => 'Main Affiliate ID',
		'desc' => 'This Affiliate id will be use in API request and if user are not from any of available ebay country.',
		'options' => WooZoneLite_ebay_countries__( '__tab1', '__subtab_ebay' ),
	),
	'ebay_buttons' => array(
		'type' => 'buttons',
		'options' => array(
			'check_amz' => array(
				'width' => '162px',
				'type' => 'button',
				'value' => 'Check Ebay AWS Keys',
				'color' => 'blue',
				'action' => 'WooZoneLiteCheckKeysEbay'
			)
		)
	),
	'ebay_help_available_countries' => array(
		'type' => 'message',
		'status' => 'info',
		'html' => '
				<strong>Available countries: &nbsp;</strong>
				'.WooZoneLite_ebay_countries__( '__tab1', '__subtab_ebay', 'string' ).'
			'
	),
	'ebay_product_desc_type' => array(
		'type' => 'select',
		'std' => 'text',
		'size' => 'large',
		'force_width' => '200',
		'title' => 'Product description type',
		'desc' => 'How to import product description: as html or as simple text.',
		'options' => array(
			'text' => 'Simple Text',
			'html' => 'HTML'
		)
	),
	'show_api_requests' => array(
		'type' => 'select',
		'std' => 'yes',
		'size' => 'large',
		'force_width' => '100',
		'title' => 'Show number of Ebay API requests notice',
		'desc' => 'Show number of Ebay API requests notice on the wordpress admin panel.',
		'options' => array(
			'yes' => 'YES',
			'no' => 'NO'
		)
	),
)
// end elements
);

$__ = WooZoneLite_get_providers_subtabs__( $__ );
//var_dump('<pre>', $__ , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

echo json_encode(array(
	$tryed_module['db_alias'] => array(

		/* define the form_sizes  box */
		'amazon' => array(
			'title' => 'Amazon settings',
			'icon' => '{plugin_folder_uri}images/amazon.png',
			'size' => 'grid_4', // grid_1|grid_2|grid_3|grid_4
			'header' => true, // true|false
			'toggler' => false, // true|false
			'buttons' => true, // true|false
			'style' => 'panel', // panel|panel-widget
		) + $__
	)
));
