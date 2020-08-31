<?php
/**
 *	Author: AA-Team
 *	Name: 	http://codecanyon.net/user/AA-Team/portfolio
 *	
**/
! defined( 'ABSPATH' ) and exit;

if ( class_exists('WooZoneLiteEbayHelper') != true ) { class WooZoneLiteEbayHelper extends WooZoneLite {

	public $the_plugin = null;
	public $aaWooZoneEbayWS = null;
	public $amz_settings = array();
	
	static protected $_instance;
	
	const MSG_SEP = '—'; // messages html bullet // '&#8212;'; // messages html separator
	
	private static $provider = 'ebay';
	private static $variation_id = '%s/var-%s';

	public $current_country = array(
		'key'	=> '',
		'name'	=> '',
	);

	public $current_aws_settings = array();



	//================================================
	//=== SETUP

	public function __construct( $the_plugin=array(), $params=array() )
	{
		$this->the_plugin = $the_plugin;
		$this->the_plugin->cur_provider = self::$provider;

		// get all amazon settings options
		$this->amz_settings = $this->the_plugin->amz_settings;

		// setup amazon api class
		$this->setupEbayWS( $params );

		// ajax actions
		add_action('wp_ajax_WooZoneLiteCheckKeysEbay', array( $this, 'check_keys'), 10, 2);
		add_action('wp_ajax_WooZoneLiteImportProductEbay', array( $this, 'getProductDataFromAmazon' ), 10, 2);

		add_action('wp_ajax_WooZoneLiteStressTestEbay', array( $this, 'stress_test' ));

		// TESTING
		if (0) { // search products by categoryId and/or keywords
			$aaWooZoneEbayWS = $this->aaWooZoneEbayWS;
			//$aaWooZoneEbayWS->country('EBAY-US');
			$aaWooZoneEbayWS->setPerPage(10)->setPage(1);
			$aaWooZoneEbayWS->setCategory(11450); // 11450 = Clothing, Shoes & Accessories; 11233 = Music
			$keywords = isset($_REQUEST['k']) ? $_REQUEST['k'] : '';
			//$keywords = '';
			$aaWooZoneEbayWS->setKeywords($keywords); 
			$results = $aaWooZoneEbayWS->search();
			var_dump('<pre>',$results ,'</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}
		if (0) { // lookup product id
			$aaWooZoneEbayWS = $this->aaWooZoneEbayWS;
			//$aaWooZoneEbayWS->country('EBAY-US');
			//$results = $aaWooZoneEbayWS->setItemIds('161853590094,381354919870,281794935811')->lookup();
			$results = $aaWooZoneEbayWS->setItemIds('351544063108')->lookup();
			var_dump('<pre>',$results ,'</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}
		if (0) { // get category hierarchy	
			//63855 = "Clothing, Shoes & Accessories:Women's Clothing:Intimates & Sleep:Sleepwear & Robes" ; LeafCategory
			//11233 = root category
			$aaWooZoneEbayWS = $this->aaWooZoneEbayWS;
			//$aaWooZoneEbayWS->country('EBAY-US');
			$results = $aaWooZoneEbayWS->setBrowseNodeIds('63855')->browseNodeLookup();
			var_dump('<pre>',$results ,'</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}
		if (0) {
			$results = $this->getAmazonCategs();
			var_dump('<pre>', $results , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}
	}

	static public function getInstance( $the_plugin=array() )
	{
		if (!self::$_instance) {
			self::$_instance = new self( $the_plugin );
		}

		return self::$_instance;
	}

	/**
	 * setup amazon ws api
	 * input(params): array(
	 * 		ebay_DEVID				: string
	 * 		ebay_AppID				: string
	 * 		ebay_CertID				: string
	 * 		ebay_country			: string
	 * 		ebay_main_aff_id		: string
	 * 		overwrite_settings		: true | false
	 * )
	 * return: true | false
	 */
	public function setupEbayWS( $params=array() ) {

		$params = array_replace_recursive(array(
			'overwrite_settings' 	=> false,
		), $params);

		//:: GET SETTINGS
		$settings = $this->amz_settings;

		//:: SETUP
		$params_new = array();

		$mainoptions = array( 'ebay_DEVID', 'ebay_AppID', 'ebay_CertID', 'ebay_country', 'ebay_main_aff_id' );
		foreach ( $mainoptions as $mainopt ) {
			if ( isset($params["$mainopt"]) ) {
				$params_new["$mainopt"] = (string) $params["$mainopt"];
			}
			else if ( isset($this->current_aws_settings["$mainopt"]) ) {
				$params_new["$mainopt"] = (string) $this->current_aws_settings["$mainopt"];
			}
			else if ( isset($settings["$mainopt"]) ) {
				$params_new["$mainopt"] = (string) $settings["$mainopt"];
			}
		}

		//$_status = $this->the_plugin->verify_amazon_keys( array(
		//	'settings' 		=> $params_new
		//));
		//$params_new = $_status['settings'];

		$this->current_aws_settings = $params_new;

		//$this->using_aateam_demo_keys = 'demo' == $_status['status'] ? true : false;

		//:: overwrite amazon helper main settings
		if ( $params['overwrite_settings'] ) {
			$settings = array_replace_recursive( $settings, $params_new );

			// get all amazon settings options
			$this->amz_settings = $settings;
		}

		$this->aaWooZoneEbayWS = $this->the_plugin->get_ws_object_new( self::$provider, 'new_ws', array(
			'the_plugin' 		=> $this->the_plugin,
			'settings' 			=> $settings,
			'params_new' 		=> $params_new,
		));

		//:: current country
		if ( is_object($this->aaWooZoneEbayWS) ) {
			$countries = $this->get_countries();
			$ckey = $params_new['ebay_country']; //isset($params['country']) ? $params['country'] : $settings['country'];
			$cname = isset($countries["$ckey"]) ? $countries["$ckey"] : '';
			$this->current_country = array(
				'key'	=> $ckey,
				'name'	=> $cname,
			);
		}

		return is_object($this->aaWooZoneEbayWS) ? true : false;
	}



	//================================================
	//=== API RESPONSE - BUILD PRODUCT DATA

	// verify if amazon response is valid!
	public function is_amazon_valid_response( $response, $operation='' ) {

		if ( ! is_object($this->aaWooZoneEbayWS) ) {

			$msg = self::$provider . ' : unknown!';
			if ( isset($this->the_plugin->wsStatus[self::$provider], $this->the_plugin->wsStatus[self::$provider]['msg']) ) {
				$msg = $this->the_plugin->wsStatus[self::$provider]['msg'];
			}

			$respStatus = array(
				'status'	=> 'invalid',
				'code'      => 1,
				'amz_code' 	=> '',
				'msg'       => $msg,
			);
			$respStatus['html'] = $respStatus['msg'];
			return $respStatus;
		}

		$respStatus = $this->aaWooZoneEbayWS->getResponseStatus($response);
		//respStatus: array( 'status', 'code', 'msg' )

		if ( !isset($respStatus['html']) ) {
			$respStatus['html'] = $respStatus['msg'];
		}

		if ( 'invalid' == $respStatus['status'] ) {
			return $respStatus;
		}

		// valid till here, but verify more!
		if ( 'search' == $operation ) {
			if (
				isset($response['searchResult'], $response['searchResult']['item'])
				&& !empty($response['searchResult'])
				&& !empty($response['searchResult']['item'])
			) ;
			else {
				$respStatus = array_merge($respStatus, array(
					'status'	=> 'invalid',
					'code'      => 3,
					'amz_code' 	=> 'notfound',
					'msg'       => 'api response has invalid result or ( searchResult, item ) pair doesn\'t exist!',
				));
			}
		}
		else {
			if ( isset($response['Item']) && !empty($response['Item']) ) ;
			else {
				$respStatus = array_merge($respStatus, array(
					'status'	=> 'invalid',
					'code'      => 3,
					'amz_code' 	=> 'notfound',
					'msg'       => 'api response has invalid result or Item element doesn\'t exist!',
				));
			}
		}

		$respStatus['html'] = $respStatus['msg'];
		return $respStatus;
	}

	// product data is valid
	public function is_valid_product_data( $product=array(), $from='details' ) {
		if ( empty($product) || !is_array($product) ) return false;
		
		$rules = isset($product['ASIN']) && !empty($product['ASIN']);
		$rules2 = false; //isset($product['ItemID']) && !empty($product['ItemID']); //itemId | ItemID
		
		$rules3 = isset($product['__isfrom'])
			&& ( (in_array($product['__isfrom'], array('details', 'details-only'))) || $from == $product['__isfrom'] );
		
		$rules = ($rules || $rules2) && $rules3;
		return $rules ? true : false;
	}

	// build single product data based on amazon request array
	public function build_product_data( $item=array(), $old_item=array() ) {

		// 3 = Apparel & Accessories
		$category = '';
		$category_id = '0';

		$retProd = array();
		if ( isset($item['__isfrom']) ) {
			$retProd['__isfrom'] = $item['__isfrom'];
		}
		$isc = isset($item['__isfrom']) ? $item['__isfrom'] : 'details-only';

		if ( isset($item['is_variation']) ) {
			$retProd['is_variation'] = $item['is_variation'];
		}

		// summarize product details
		// from product details request
		if ( in_array($isc, array('details', 'details-only')) ) {
			$retProd = array_merge($retProd, array(
				'ASIN'                  => isset($item['ItemID']) ? $item['ItemID'] : '',
				'ParentASIN'            => '',

				//A SKU is not required to be unique. A seller can specify a particular SKU on one item or on multiple items. Different sellers can use the same SKUs.
				//'SKU'                   => isset($item['SKU']) ? $item['SKU'] : '', // it's not sent in here!

				'Brand'					=> '',
				'BrowseNodes'           => array(),
				'SmallImage'            => '',
				'LargeImage'            => '',

				'ItemAttributes'        => isset($item['ItemSpecifics'], $item['ItemSpecifics']['NameValueList']) ? $item['ItemSpecifics']['NameValueList'] : array(),
				'Feature'               => '',
				'EditorialReviews'		=> '',
				'Description'			=> isset($item['Description']) ? $item['Description'] : '',
				'Summary'				=> '',
				'Title'					=> isset($item['Title']) ? $item['Title'] : '',
				
				'Variations'            => isset($item['Variations']) ? $item['Variations'] : array(),
				
				'hasGallery'			=> 'false',
			));
			//$retProd['Feature'] = array($retProd['Summary']);
			
			if ( !empty($retProd['Variations']) ) {
				$variations = isset($retProd['Variations']['Variation']) ? (array) $retProd['Variations']['Variation'] : array();
				$retProd['Variations']['TotalVariations'] = 1;
				if (isset($variations['VariationSpecifics']) || isset($variations['StartPrice'])
					|| isset($variations['SKU'])) { // --fix 2015.03.19
					//$retProd['Variations']['TotalVariations'] = 1;
				} else {
					$retProd['Variations']['TotalVariations'] = (int) count($retProd['Variations']['Variation']);
				}
			}

			if ( isset($item['PrimaryCategoryName']) && !empty($item['PrimaryCategoryName']) ) {
				$classification = explode(':', $item['PrimaryCategoryName']);
				$classification = array_map('trim', $classification);
				$classification = array_filter($classification);
				$retProd['BrowseNodes'] = $classification;
			}
			
			$retProd['DetailPageURL'] = isset($item['ViewItemURLForNaturalSearch']) ? $item['ViewItemURLForNaturalSearch'] : '';

			// other fields
			if ( isset($item['__extra']) && is_array($item['__extra']) ) {
				$retProd['__extra'] = $item['__extra'];
			}
			$metas = array('BestOfferEnabled', 'EndTime', 'StartTime', 'ViewItemURLForNaturalSearch', 'ListingType', 'Location', 'PaymentMethods', 'PrimaryCategoryID', 'PrimaryCategoryName', 'Quantity', 'Seller', 'BidCount', 'ConvertedCurrentPrice', 'CurrentPrice', 'ListingStatus', 'QuantitySold', 'ShipToLocations', 'Site', 'TimeLeft', 'HitCount', 'PrimaryCategoryIDPath', 'Storefront', 'Country', 'ReturnPolicy', 'AutoPay', 'IntegratedMerchantCreditCardEnabled', 'HandlingTime', 'ConditionID', 'ConditionDisplayName', 'QuantityAvailableHint', 'QuantityThreshold', 'ExcludeShipToLocation', 'GlobalShipping', 'ConditionDescription', 'QuantitySoldByPickupInStore', 'NewBestOffer', 'DiscountPriceInfo');
			foreach ($metas as $key) {
				if ( isset($item["$key"]) && !empty($item["$key"]) ) {
					if ( ! isset($retProd['__extra']) ) {
						$retProd['__extra'] = array();
					}
					$retProd['__extra']["$key"] = isset($item["$key"]) ? $item["$key"] : '';
				}
			}
		}
		// from search pages request
		else {
			$retProd = array_merge($retProd, array(
				'ASIN'                  => isset($item['itemId']) ? $item['itemId'] : '',
				'ParentASIN'            => '',

				//A SKU is not required to be unique. A seller can specify a particular SKU on one item or on multiple items. Different sellers can use the same SKUs.
				'SKU'                   => isset($item['SKU']) ? $item['SKU'] : '',

				'SmallImage'            => '', //isset($item['GalleryURL']) ? trim( $item['GalleryURL'] ) : '',
				'LargeImage'            => '', //isset($item['PictureURLSuperSize']) ? trim( $item['PictureURLSuperSize'] ) : isset($item['PictureURLLarge']) ? trim( $item['PictureURLLarge'] ) : '',
				
				//'Tags'					=> '',
				'Title'					=> isset($item['title']) ? $item['title'] : '',
			));

			$retProd['DetailPageURL'] = isset($item['viewItemURL']) ? $item['viewItemURL'] : '';

			// other fields
			if ( isset($item['__extra']) && is_array($item['__extra']) ) {
				$retProd['__extra'] = $item['__extra'];
			}
			$metas = array('globalId', 'primaryCategory', 'viewItemURL', 'paymentMethod', 'autoPay', 'location', 'country', 'shippingInfo', 'sellingStatus', 'listingInfo', 'returnsAccepted', 'isMultiVariationListing', 'topRatedListing');
			foreach ($metas as $key) {
				if ( isset($item["$key"]) && !empty($item["$key"]) ) {
					if ( ! isset($retProd['__extra']) ) {
						$retProd['__extra'] = array();
					}
					$retProd['__extra']["$key"] = isset($item["$key"]) ? $item["$key"] : '';
				}
			}
		}

		// added by jimmy /2018-10-17
		$retProd['country'] = isset($this->amz_settings['ebay_country']) ? $this->amz_settings['ebay_country'] : '';
		if ( isset($item['country']) ) {
			$retProd['country'] = $item['country'];
		}

		if ( ! empty($retProd['DetailPageURL']) ) {
			$country = $this->the_plugin->get_country_from_url( $retProd['DetailPageURL'], 'ebay' );

			if ( ! empty($country) ) {
				if ( is_object($this->the_plugin->ebay_utils) ) {
					$country = $this->the_plugin->ebay_utils->get_location( $country, 'siteroot', 'globalid' );
					$retProd['country'] = $country;
				}
			}
		}
		/*
		$country = '';
		if ( isset($retProd['__extra']['globalId']) && ! empty($retProd['__extra']['globalId']) ) {
			$country = strtoupper($retProd['__extra']['globalId']);
		}
		else if ( isset($retProd['__extra']['country']) && ! empty($retProd['__extra']['country']) ) {
			$country = 'EBAY-' . strtoupper($retProd['__extra']['country']);
		}
		else if ( isset($retProd['__extra']['Country']) && ! empty($retProd['__extra']['Country']) ) {
			$country = 'EBAY-' . strtoupper($retProd['__extra']['Country']);
		}
		if ( ! empty($country) ) {
			$retProd['country'] = $country;
		}
		*/
		//var_dump('<pre>', $retProd['__extra'], $country, $retProd['country'], $retProd['DetailPageURL'], '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		// Images
		if ( isset($item['images']) && ! empty($item['images']) ) {
			$retProd['images'] = $item['images'];
		}
		else {
			$retProd['images'] = $this->build_images_data( $item );
		}

		if ( empty($retProd['images']['large']) ) {
			// no images found - if has variations, try to find first image from variations
			$retProd['images'] = $this->get_first_variation_image(
				isset($item['Variations'], $item['Variations']['Pictures']) ? $item['Variations']['Pictures'] : array()
			);
		}
		if ( empty($retProd['SmallImage']) ) {
			if ( isset($retProd['images']['small']) && !empty($retProd['images']['small']) ) {
				$retProd['SmallImage'] = $retProd['images']['small'][0];
			}
		}
		if ( empty($retProd['LargeImage']) ) {
			if ( isset($retProd['images']['large']) && !empty($retProd['images']['large']) ) {
				$retProd['LargeImage'] = $retProd['images']['large'][0];
			}
		}
		if ( empty($retProd['SmallImage']) ) {
			$retProd['SmallImage'] = $retProd['LargeImage'];
		}
		//var_dump('<pre>', $retProd['images'] , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		// new/current content will overwrite old content
		if ( !empty($old_item) && is_array($old_item) ) {

			if ( in_array($isc, array('details', 'details-only')) ) {
				if ( isset($old_item['SmallImage']) && !empty($old_item['SmallImage']) ) {
					unset( $retProd['SmallImage'] ); // search sends better images
				}
				if ( isset($old_item['LargeImage']) && !empty($old_item['LargeImage']) ) {
					unset( $retProd['LargeImage'] ); // search sends better images
				}
				if ( isset($old_item['images']['large']) && !empty($old_item['images']['large']) ) {
					$old_item['images']['large'] = array_merge(
						$old_item['images']['large'],
						$retProd['images']['large']
					);
					$old_item['images']['small'] = array_merge(
						$old_item['images']['small'],
						$retProd['images']['small']
					);
					$old_item['images']['large'] = @array_unique($old_item['images']['large']);
					$old_item['images']['small'] = @array_unique($old_item['images']['small']);
					unset( $retProd['images'] );
				}
				
				if ( !empty($old_item['DetailPageURL']) && empty($retProd['DetailPageURL']) ) {
					unset( $retProd['DetailPageURL'] );	
				}
			}

			$retProd = array_replace_recursive($old_item, $retProd);
		}
		//var_dump('<pre>', $retProd['images'] , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( !empty($retProd['images']['large']) && (count($retProd['images']['large']) > 1) ) {
			$retProd['hasGallery'] = 'true';
		}
		//var_dump('<pre>', $retProd , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $retProd;
	}

	public function build_images_data( $item=array(), $nb_images='all' ) {
		$retProd = array( 'large' => array(), 'small' => array(), 'sizes' => array() );

		if ( ! is_array($item) || empty($item) ) {
			return $retProd;
		}

		// product large image
		if ( isset($item['PictureURLSuperSize']) ) {
		   $retProd['large'][] = $item['PictureURLSuperSize'];
		}
		else if ( isset($item['PictureURLLarge']) ) {
			$retProd['large'][] = $item['PictureURLLarge'];
		}

		if ( isset($retProd['large'][0]) ) {
			if ( isset($item['GalleryURL']) ) {
			   $retProd['small'][] = $item['GalleryURL'];
			} else {
				$retProd['small'][] = $retProd['large'][0];
			}
		}

		// get gallery images
		if (isset($item['PictureURL'])) {

			if ( !is_array($item['PictureURL']) ) {
				$item['PictureURL'] = array($item['PictureURL']);
			}

			$count = 0;
			foreach ($item['PictureURL'] as $key => $value) {
				
				$value_ = trim($value);
				$value_ = $this->the_plugin->imagesfix->ebay_image_clean_url( $value_ );

				if ( !empty($value_) ) {
					$size_ = $this->the_plugin->imagesfix->ebay_image_get_size( $value_ );

					$retProd['large'][] = $value_;
					$retProd['small'][] = $value_;
					$retProd['sizes'][] = $size_;
				}
				$count++;
			}
		}

		$retProd['large'] = @array_unique($retProd['large']);
		$retProd['small'] = @array_unique($retProd['small']);

		// remove empty array elements!
		$retProd['large'] = @array_filter($retProd['large']);
		$retProd['small'] = @array_filter($retProd['small']);
		
		return $retProd;
	}
	
	// if product is variation parent, get first variation child image as product image
	public function get_first_variation_image( $retProd, $filter=array() ) {
		$images = array( 'large' => array(), 'small' => array() );

		if ( isset($retProd['VariationSpecificPictureSet']) ) {

			$name = $retProd['VariationSpecificName'];
			$variations = $retProd['VariationSpecificPictureSet'];
			
			if ( !isset($variations[0]) ) {
				$variations = array($variations);
			}

			// Loop through the variation
			$pics = array();
			foreach ($variations as $variation_item) {
				
				$name_item = $variation_item['VariationSpecificValue'];

				if ( empty($filter) ) {
					// get first pictures set found!
					$pics = $variation_item;
					$images = $this->build_images_data( $pics );
					if ( !empty($images['large']) ) {
						return $images;
					}
				}
				else {
					// find the image for current variation
					if ( isset($filter["$name"]) && !empty($filter["$name"]) ) {
						if (in_array($name_item, $filter["$name"])) {
							$pics = $variation_item;
							$images = $this->build_images_data( $pics );
							break;
						}
					}
				}

			} // end foreach
		}
		return $images;
	}



	//================================================
	//=== OTHERS

	public function stress_test()
	{
		$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';
		$return = array();

		$start = microtime(true);

		//header('HTTP/1.1 500 Internal Server Error');
		//exit();
		
		WooZoneLite_session_start();

		$product_id = isset($_SESSION["WooZoneLite_test_product_local_id"])
			? $_SESSION["WooZoneLite_test_product_local_id"] : 0;

		die( json_encode($return) );   
	}
	
	public function check_keys( $retType='die', $pms=array() )
	{
		$pms = array_replace_recursive(array(
			'extra_msg' 			=> '',
			'extra_msg_pos' 		=> 'top', // top | bottom
		), $pms);

		$provider = self::$provider;

		$status = 'valid';
		$msg = '';
		try {
			// Do a test connection
			$rsp = $this->api_main_request(array(
				'what_func' 			=> 'api_make_request',
				'method'				=> 'search',
				'amz_settings'			=> $this->the_plugin->amz_settings,
				'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
				'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
				'requestData'			=> array(
					'category'					=> 99,
					'page'						=> 1,
					'keyword'					=> 'music',
					'perPage' 					=> 3,
				),
				//'optionalParameters'	=> array(),
			));
			$tryRequest = $rsp['response'];
			
			$respStatus = $this->is_amazon_valid_response( $tryRequest, 'search' );
			if ( $respStatus['status'] != 'valid' ) { // error occured!

				$msg = 'Ebay Error: ' . $respStatus['code'] . ' - ' . $respStatus['msg']; 
				$status = 'invalid';
			}

		}
		catch (Exception $e) {

			$msg = WooZoneLiteGetExceptionMsg( $e );
			$status = 'invalid';
		}

		if ( ! empty($this->current_country['name']) ) {
			$msg_country = '** Country: ' . $this->current_country['name'];
			$msg_country .= ('' != $msg ? '<br /><br />' : '');

			$msg = $msg_country . $msg;
		}

		//:: build message
		//$msg = '<p>' . $msg . '<p>';

		// success
		if ( 'valid' == $status ) {
			$msg .= '<p>WooCommerce Amazon Affiliates was able to connect to Ebay with the specified Keys and Associate ID</p>';
		}
		// error
		else {
			$msg .= '<p>WooCommerce Amazon Affiliates was not able to connect to Ebay with the specified Keys and Associate ID. Please triple-check your Keys and Associate ID.</p>';

			//if ( false !== strpos( $msg, 'aws:Client.AWS.InvalidAssociate' ) ) {
			//	$msg .= '<p><strong>Don\'t panic</strong>, this error is easy to fix, please follow the instructions from ';
			//	$msg .= 	'<a href="http://support.aa-team.com/knowledgebase-details/198" target="_blank">here</a>.';
			//	$msg .= '</p>';
			//}
		}

		if ( isset($pms['extra_msg']) && ! empty($pms['extra_msg']) ) {
			if ( 'top' == $pms['extra_msg_pos'] ) {
				$msg = $pms['extra_msg_pos'] . $msg;
			}
			else {
				$msg = $msg . $pms['extra_msg_pos'];
			}
		}
		//:: end build message

		$ret = array(
			'status' 	=> $status,
			'msg' 		=> $msg,
		);

		if ( $retType == 'return' ) { return $ret; }
		else { die( json_encode( $ret ) ); }
	}
	
	private function convertMainAffIdInCountry( $main_add_id='' )
	{
		if( $main_add_id == 'com' ) return 'US';
		
		return strtoupper( $main_add_id );
	}
	
	public function getAmazonCategs()
	{
		$categs = $this->getBrowseNodesList('-1');
		if ( empty($categs) ) return array();
		//var_dump('<pre>', $categs , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		$ret = array();
		foreach ($categs as $key => $val) {
			$id = $val['CategoryID'];
			$name = $val['CategoryName'];
			$ret["$name"] = $id;
		}
		//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $ret;
	}

	public function getAmazonItemSearchParameters()
	{
		return array();
	}
	
	public function getAmazonSortValues()
	{
		return array();
	}

	public function getBrowseNodesList( $nodeid=0 ) {

		$provider = self::$provider;
		if( !is_numeric($nodeid) ){
			return array(
				'status'    => 'invalid',
				'msg'       => 'The $nodeid is not numeric: ' . $nodeid
			);
		}

		$prefix_opt = '_'.$provider;
		$country = $this->amz_settings[$provider.'_'.'country'];
		$prefix_opt = $prefix_opt . '_' . $country;

		$optname = $this->the_plugin->alias . $prefix_opt . '_node_children_' . $nodeid;
		$nodes = get_option( $optname, false );

		// unable to find the node into cache, get live data
		if ( !isset($nodes) || $nodes == false || count($nodes) == 0 ) {

			//$nodes = $this->aaWooZoneEbayWS->setBrowseNodeIds( $nodeid )->browseNodeLookup();
			$nodes = $this->browseNodeLookup( $nodeid );

			$childs = array();
			$lev1 = isset($nodes['CategoryArray']) ? $nodes['CategoryArray'] : array();
			if( !empty($lev1) ) {
				
				$lev2 = isset($lev1['Category']) ? $lev1['Category'] : array();
				if( !empty($lev2) ) {
					$lev3 = isset($lev2['CategoryID']) ? array($lev2) : $lev2;
					
					if ( !empty($lev3) ) {
						foreach ($lev3 as $key => $val) {
							// don't count the current nodeid / category
							if ($nodeid == $val['CategoryID']) continue 1;
							
							$childs[] = $val;
						}
					} // end lev3
				} // end lev2
			} // end lev1
			
			if ( !empty($childs) ) {
				// store the cache into DB
				$nodes = $childs;
				update_option( $optname, $nodes );
			}
			// error occured!
			else {
				if ( isset($nodes['Errors']) && ! empty($nodes['Errors']) && is_array($nodes['Errors']) ) {
					$nodes = array_column($nodes['Errors'], 'LongMessage');
					$nodes = array( 0 => array(
						'CategoryID' => '-999',
						'CategoryName' => '-- ERROR: '.implode(' | ', $nodes)
					));
				}
			}
		}
		//var_dump('<pre>', $nodes , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $nodes;
	}

	public function browseNodeLookup( $nodeid )
	{
		$rsp = $this->api_main_request(array(
			'what_func' 			=> 'api_make_request',
			'method'				=> 'browseNodeLookup',
			'amz_settings'			=> $this->the_plugin->amz_settings,
			'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
			'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
			'requestData'			=> array(
				'nodeid'					=> $nodeid,
			),
			//'optionalParameters'	=> array(),
		));
		$ret = $rsp['response'];
		return $ret;
	}
	
	public function updateProductReviews( $post_id=0 )
	{
		return false;
	}
	
	// Get Product From WebService
	public function getProductDataFromAmazon( $retType='die', $pms=array() ) {
		// require_once( $this->the_plugin->cfg['paths']["scripts_dir_path"] . '/shutdown-scheduler/shutdown-scheduler.php' );
		// $scheduler = new aateamShutdownScheduler();

		$this->the_plugin->timer_start(); // Start Timer

		$cross_selling = (isset($this->amz_settings["cross_selling"]) && $this->amz_settings["cross_selling"] == 'yes' ? true : false);

		$_msg = array();
		$ret = array(
			'status'                    => 'invalid',
			'msg'                       => '',
			'product_data'              => array(),
			'show_download_lightbox'    => false,
			'download_lightbox_html'    => '',
			'product_id'				=> 0,
			'do_import'					=> true,
		);
		
		//$asin = isset($_REQUEST['asin']) ? htmlentities($_REQUEST['asin']) : '';
		//$category = isset($_REQUEST['category']) ? htmlentities($_REQUEST['category']) : 'All';
		
		// build method parameters
		$requestData = array_merge( array(), $this->the_plugin->importProdDefaultParams( $this->amz_settings ) );
		$requestData = array_merge( $requestData, array(
			'debug_level'           => isset($_REQUEST['debug_level']) ? (int) $_REQUEST['debug_level'] : 0,
			'do_import_product'     => 'yes',
			'from_cache'            => array(),
			'from_module'           => 'default',
			'import_type'           => isset($this->amz_settings['import_type'])
				&& $this->amz_settings['import_type'] == 'asynchronous' ? 'asynchronous' : 'default',
			'country' 				=> isset($_REQUEST['country']) ? htmlentities($_REQUEST['country']) : '',

			//:: bellow parameters are used in framework addNewProduct method
			'ws'					=> self::$provider,
			'asin'                  => isset($_REQUEST['asin']) ? htmlentities($_REQUEST['asin']) : '',
			'from_op' 				=> isset($_REQUEST['from_op']) ? htmlentities($_REQUEST['from_op']) : '',
			'import_to_category'    => isset($_REQUEST['to-category']) ? trim($_REQUEST['to-category']) : 0,
		));

		foreach ($requestData as $rk => $rv) {
			//empty($rv) || ( isset($pms["$rk"]) && !empty($pms["$rk"]) )
			if ( 1 ) {
				if ( isset($pms["$rk"]) ) {
					$new_val = $pms["$rk"];
					$requestData["$rk"] = $new_val;
				}
			}
		}
		$requestData['asin'] = trim( $requestData['asin'] );
		
		// Import To Category
		if ( empty($requestData['import_to_category']) || ( (int) $requestData['import_to_category'] <= 0 ) ) {
			$requestData['import_to_category'] = 'amz';
		}

		// NOT using category from amazon!
		if ( (int) $requestData['import_to_category'] > 0 ) {
			$__categ = get_term( $requestData['import_to_category'], 'product_cat' );
			if ( isset($__categ->term_id) && !empty($__categ->term_id) ) {
				$requestData['import_to_category'] = $__categ->term_id;
			} else {
				$requestData['import_to_category'] = 'amz';
			}
			//$requestData['import_to_category'] = $__categ->name ? $__categ->name : 'Untitled';

			//$__categ2 = get_term_by('name', $requestData['import_to_category'], 'product_cat');
			//$requestData['import_to_category'] = $__categ2->term_id;
		}

		extract($requestData);

		// provided ASIN in invalid
		if( empty($asin) ){
			$ret = array_merge($ret, array(
				'msg'           => self::MSG_SEP . ' <u>Import Product ASIN</u> : is invalid (empty)!',
			));
			if ( $retType == 'return' ) { return $ret; }
			else { die( json_encode( $ret ) ); }
		}
		
		// check if product already imported 
		$your_products = $this->the_plugin->getAllProductsMeta('array', '_amzASIN', true, 'all');
		//var_dump('<pre>', $asin, $your_products , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		if( isset($your_products) && count($your_products) > 0 ){
			if( in_array($asin, $your_products) ){
				
				$ret = array_merge($ret, array(
					'msg'           => self::MSG_SEP . ' <u>Import Product ASIN</u> <strong>'.$asin.'</strong> : already imported!',
					'product_id'	=> -1,
				));
				if ( $retType == 'return' ) { return $ret; }
				else { die( json_encode( $ret ) ); }
			}
		}

		$isValidProduct = false;
		$_msg[] = self::MSG_SEP . ' <u>Import Product ASIN</u> <strong>'.$asin.'</strong>';

		// from cache
		if ( isset($from_cache) && $this->is_valid_product_data($from_cache) ) {
			$retProd = $from_cache;
			$isValidProduct = true;
			
			$_msg[] = self::MSG_SEP . ' product data returned from Cache';

			if ( 1 ) {
				$this->the_plugin->add_last_imports('request_cache', array(
					'duration'      => $this->the_plugin->timer_end(),
				)); // End Timer & Add Report
			}
		}

		// from amazon
		if ( !$isValidProduct ) {
			try {

				$rsp = $this->api_main_request(array(
					'what_func' 			=> 'api_make_request',
					'method'				=> 'lookup',
					'amz_settings'			=> $this->the_plugin->amz_settings,
					'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
					'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
					'requestData'			=> array(
						'asin'					=> $this->the_plugin->prodid_get_asin( $asin ),
					),
					'optionalParameters'	=> array(),
				));
				$product = $rsp['response'];

				$respStatus = $this->is_amazon_valid_response( $product );
				if ( $respStatus['status'] != 'valid' ) { // error occured!
					
					$_msg[] = 'Invalid '.self::$provider.' response ( ' . $respStatus['code'] . ' - ' . $respStatus['msg'] . ' )';
					
					$ret = array_merge($ret, array('msg' => implode('<br />', $_msg)));
					if ( $retType == 'return' ) { return $ret; }
					else { die( json_encode( $ret ) ); }
			
				} else { // success!

					$thisProd = isset($product['Item']) && !empty($product['Item']) ? $product['Item'] : array();
					//var_dump('<pre>', $thisProd , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
					if ( !empty($thisProd) ) {
						//$thisProd['__isfrom'] = 'details-only';

						// build product data array
						$retProd = array();
						$thisProd['__isfrom'] = 'details-only';
						$retProd = $this->build_product_data( $thisProd );
						if ( $this->is_valid_product_data($retProd) ) {
							$isValidProduct = true;
							$_msg[] = 'Valid '.self::$provider.' response';
						}
						//var_dump('<pre>', $this->is_valid_product_data($retProd), $retProd, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
	
						// DEBUG
						if( $debug_level > 0 ) {
							ob_start();
	
							if( $debug_level == 1) var_dump('<pre>', $retProd,'</pre>');
							if( $debug_level == 2) var_dump('<pre>', $product ,'</pre>');
	
							$ret = array_merge($ret, array('msg' => ob_get_clean()));
							if ( $retType == 'return' ) { return $ret; }
							else { die( json_encode( $ret ) ); }
						}
					}
				}

			}
			catch (Exception $e) {

				$excmsg = WooZoneLiteGetExceptionMsg( $e );

				//ob_start();
				//var_dump('<pre>', 'Invalid '.self::$provider.' response (exception)', $e,'</pre>');
				//$_msg[] = ob_get_clean();

				$_msg[] = 'Invalid '.self::$provider.' resp : ' . $excmsg;

				$ret = array_merge($ret, array('msg' => implode('<br />', $_msg)));
				if ( $retType == 'return' ) { return $ret; }
				else { die( json_encode( $ret ) ); }
			} // end try
		} // end from amazon
		
		// If valid product data retrieved -> Try to Import Product in Database
		if ( $isValidProduct ) {

			if ( 1 ) {
				$this->the_plugin->add_last_imports('request_amazon', array(
					'duration'      => $this->the_plugin->timer_end(),
				)); // End Timer & Add Report
			}

			$ret['product_data'] = $retProd;

			// do not import product - just return the product data array
			if( !isset($do_import_product) || $do_import_product != 'yes' ){
				$ret = array_merge($ret, array(
					'status'        => 'valid',
					//'product_data'  => $retProd,
					'msg'           => implode('<br />', $_msg))
				);
				if ( $retType == 'return' ) { return $ret; }
				else { die( json_encode( $ret ) ); }
			}
	
			// add product in database
			$args_add = $requestData;
			$addNewProductStat = $this->the_plugin->addNewProduct( $retProd, $args_add );
			$insert_id = (int) $addNewProductStat['insert_id'];
			$opStatusMsg = $this->the_plugin->opStatusMsgGet();

			// Successfully adding product in database
			if ( $insert_id ) {

				$_msg[] = self::MSG_SEP . ' Successfully Adding product in database (with ID: <strong>'.$insert_id.'</strong>).';
				$ret['status'] = 'valid';
				$ret['product_id'] = $insert_id;

				$do_lightbox = !empty($import_type) && $import_type=='default' && !$this->the_plugin->is_remote_images;
				if ( $addNewProductStat['nb_remote_err'] ) {
					$do_lightbox = true;
				}
				//var_dump('<pre>', $addNewProductStat['nb_remote_err'] , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				if ( $do_lightbox ) {
					$ret = array_merge($ret, array(
						'show_download_lightbox'     => true,
						'download_lightbox_html'     => $this->the_plugin->download_asset_lightbox( $insert_id, $from_module, 'html' ),
					));
				}
			}
			// Error when trying to insert product in database
			else {
				$_msg[] = self::MSG_SEP . ' Error Adding product in database.';
			}
			
			// detailed status from adding operation: successfull or with errors
			$_msg[] = $opStatusMsg['msg'];
			
			$ret = array_merge($ret, array('msg' => implode('<br />', $_msg)));
			if ( $retType == 'return' ) { return $ret; }
			else { die( json_encode( $ret ) ); }

		} else {

			$_msg[] = self::MSG_SEP . ' product data (from cache or '.self::$provider.') is not valid!';

			$ret = array_merge($ret, array('msg' => implode('<br />', $_msg)));
			if ( $retType == 'return' ) { return $ret; }
			else { die( json_encode( $ret ) ); }
		}

		// $scheduler->registerShutdownEvent(array($scheduler, 'getLastError'), true);
	}

	// Create the tags for the product
	public function set_product_tags( $Tags='' )
	{
		return array();
	}

	// Create the categories for the product & the attributes
	public function set_product_categories( $browseNodes=array() )
	{
		// The woocommerce product taxonomy
		$wooTaxonomy = "product_cat";
		
		// Categories for the product
		$createdCategories = array();
		
		// Category container
		$categories = array();

		// browseNode
		if( is_array( $browseNodes ) && !empty($browseNodes) ) {
			
			// Create a clone
			$currentNode = $browseNodes;

			// Always true unless proven
			$validCat = true;
			
			foreach ($currentNode as $key => $value) {
				// Replace html entities
				$dmCatName = str_replace( array('&amp;', '&'), 'and', $value );
				$dmCatSlug = sanitize_title( $dmCatName );
				
				// Check if we will make the cat
				if( $validCat ) {
					$categories[] = array(
						'name' => $dmCatName,
						'slug' => $dmCatSlug
					);
				}
			}
		}
		
		if ( 1 ) {
			// Import only parent category from Amazon
			if( isset( $this->amz_settings["create_only_parent_category"] ) && $this->amz_settings["create_only_parent_category"] != '' && $this->amz_settings["create_only_parent_category"] == 'yes') {
				$categories = array( $categories[0] );
			}

			// The current node
			$nodeCounter = 0;
			// Loop through the array of the current browsenode
			foreach( $categories as $node )
			{
				// Check if we're NOT at parent
				if( $nodeCounter > 0 )
				{
					// The parent of the current node
					$parentNode = $categories[$nodeCounter - 1];
					// Get the term id of the parent
					$parent = term_exists( str_replace( '&', 'and', $parentNode['slug'] ), $wooTaxonomy );
				}

				if( 1 )
				{
					// Check if term exists
					$checkTerm = term_exists( str_replace( array('&amp;', '&'), 'and', $node['slug'] ), $wooTaxonomy );
					if( empty( $checkTerm ) )
					{
						if( $nodeCounter > 0 )
						{
							// Create the new category
							$newCat = wp_insert_term( $node['name'], $wooTaxonomy, array( 'slug' => $node['slug'], 'parent' => $parent['term_id'] ) );
						}
						else {
							// Create the new category
							$newCat = wp_insert_term( $node['name'], $wooTaxonomy, array( 'slug' => $node['slug'] ) );								
						}
					   
						// Add the created category in the createdCategories
						// Only run when the $newCat is an error
						if( gettype($newCat) != 'object' ) {
							$createdCategories[] = $newCat['term_id'];
						}
					}
					else
					{
						// if term already exists add it on the createdCategories
						$createdCategories[] = $checkTerm['term_id'];
					}
				}

				$nodeCounter++;
			}
		}
		
		// Delete the product_cat_children
		// This is to force the creation of a fresh product_cat_children
		delete_option( 'product_cat_children' );
		
		$returnCat = array_unique($createdCategories);
	 
		// return an array of term id where the post will be assigned to
		return $returnCat;
	}

	public function set_woocommerce_attributes( $itemAttributes=array(), $post_id ) 
	{
		global $wpdb;
		global $woocommerce;
 
		// convert Alibaba attributes into woocommerce attributes
		$_product_attributes = array();
		$position = 0;
		
		$allowedAttributes = 'all';

		//if ( isset($this->amz_settings['selected_attributes'])
		//	&& !empty($this->amz_settings['selected_attributes'])
		//	&& is_array($this->amz_settings['selected_attributes']) )
		//	$allowedAttributes = (array) $this->amz_settings['selected_attributes'];

		$_attr = array(); 
		foreach( $itemAttributes as $_key => $_value )
		{
			if (is_array($_value)) 
			{
				$key = strtolower($_value['Name']);
				$value = $_value['Value'];
				
				if ( empty($value) ) continue 1;
				
				if ( !is_array($value) ) {
					$value = array( $value );
				}
				foreach ($value as $_key2 => $_value2)
				{
					$value2 = $_value2;
					if ( isset($_attr["$key"]) )
					{
						if ( !in_array($value2, $_attr["$key"]) ) {
							$_attr["$key"][] = $value2;
						}
					} else {
						$_attr["$key"][0] = $value2;
					}
				}
			}
		}
		foreach( $_attr as $_key => $_value ) {
			if ( count($_value) > 1 ) ;
			else {
				$_attr["$_key"] = $_value[0];
			}
		}

		foreach( $_attr as $key => $value )
		{
			if (1) 
			{
				//if ( is_array($allowedAttributes) ) {
				//	if ( !in_array($key, $allowedAttributes) ) {
				//		continue 1;
				//	}
				//}
				
				// don't add these into attributes
				//if( in_array($key, array('ListPrice', 'Feature', 'Title') ) ) continue;
				
				$value_orig = $value;
				
				// change dimension name as woocommerce attribute name
				$attribute_name = $this->the_plugin->cleanTaxonomyName(strtolower($key));

				// convert value into imploded array
				if( is_array($value) ) {
					$value = $this->the_plugin->multi_implode( $value, ', ' ); 
				}
				
				// Clean
				$value = $this->the_plugin->cleanValue( $value );
				 
				// if is empty attribute don't import
				if( trim($value) == "" ) continue;
				
				$_product_attributes[$attribute_name] = array(
					'name' => $attribute_name,
					'value' => $value,
					'position' => $position++,
					'is_visible' => 1,
					'is_variation' => 0,
					'is_taxonomy' => 1
				);

				$this->add_attribute( $post_id, $key, $value_orig );
			}
		}
		
		// update product attribute
		update_post_meta($post_id, '_product_attributes', $_product_attributes);
		
		//$this->the_plugin->get_ws_object( 'generic' )->attrclean_clean_all( 'array' ); // delete duplicate attributes
		
		// refresh attribute cache
		$dmtransient_name = 'wc_attribute_taxonomies';
		$dmattribute_taxonomies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies");
		set_transient($dmtransient_name, $dmattribute_taxonomies);
	}

	// add woocommrce attribute values
	public function add_attribute($post_id, $key, $value) 
	{ 
		global $wpdb;
		global $woocommerce;
		 
		// get attribute name, label
		if ( isset($this->amz_settings['attr_title_normalize']) && $this->amz_settings['attr_title_normalize'] == 'yes' )
			$attribute_label = $this->attrclean_splitTitle( $key );
		else
			$attribute_label = $key;   
		$attribute_name = $this->the_plugin->cleanTaxonomyName($key, false);

		// set attribute type
		$attribute_type = 'select';
		
		// check for duplicates
		$attribute_taxonomies = $wpdb->get_var("SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = '".esc_sql($attribute_name)."'");
		
		if ($attribute_taxonomies) {
			// update existing attribute
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_attribute_taxonomies', array(
					'attribute_label' => $attribute_label,
					'attribute_name' => $attribute_name,
					'attribute_type' => $attribute_type,
					'attribute_orderby' => 'name'
				), array('attribute_name' => $attribute_name)
			);
		} else {
			// add new attribute
			$wpdb->insert(
				$wpdb->prefix . 'woocommerce_attribute_taxonomies', array(
					'attribute_label' => $attribute_label,
					'attribute_name' => $attribute_name,
					'attribute_type' => $attribute_type,
					'attribute_orderby' => 'name'
				)
			);
		}

		// avoid object to be inserted in terms
		if (is_object($value))
			return;

		// add attribute values if not exist
		$taxonomy = $this->the_plugin->cleanTaxonomyName($attribute_name);
		
		if( is_array( $value ) )
		{
			$values = $value;
		}
		else
		{
			$values = array($value);
		}

		// check taxonomy
		if( !taxonomy_exists( $taxonomy ) ) 
		{
			// add attribute value
			foreach ($values as $attribute_value) {
				$attribute_value = (string) $attribute_value;
				if(is_string($attribute_value)) {
					// add term
					//$name = stripslashes($attribute_value);
					$name = $this->the_plugin->cleanValue( $attribute_value ); // 2015, october 28 - attributes bug update!
					$slug = sanitize_title($name);
					
					if( !term_exists($name) ) {
						if( trim($slug) != '' && trim($name) != '' ) {
							$this->the_plugin->db_custom_insert(
								$wpdb->terms,
								array(
									'values' => array(
										'name' => $name,
										'slug' => $slug
									),
									'format' => array(
										'%s', '%s'
									)
								),
								true
							);
							//$wpdb->insert(
							//	$wpdb->terms, array(
							//		'name' => $name,
							//		'slug' => $slug
							//	)
							//);

							// add term taxonomy
							$term_id = $wpdb->insert_id;
							$this->the_plugin->db_custom_insert(
								$wpdb->term_taxonomy,
								array(
									'values' => array(
										'term_id' => $term_id,
										'taxonomy' => $taxonomy
									),
									'format' => array(
										'%d', '%s'
									)
								),
								true
							);
							//$wpdb->insert(
							//	$wpdb->term_taxonomy, array(
							//		'term_id' => $term_id,
							//		'taxonomy' => $taxonomy
							//	)
							//);
							$term_taxonomy_id = $wpdb->insert_id;
							$__dbg = compact('taxonomy', 'attribute_value', 'term_id', 'term_taxonomy_id');
							//var_dump('<pre>1: ',$__dbg,'</pre>');
						}
					} else {
						// add term taxonomy
						$term_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->terms} WHERE name = '".esc_sql($name)."'");
						$this->the_plugin->db_custom_insert(
							$wpdb->term_taxonomy,
							array(
								'values' => array(
									'term_id' => $term_id,
									'taxonomy' => $taxonomy
								),
								'format' => array(
									'%d', '%s'
								)
							),
							true
						);
						//$wpdb->insert(
						//	$wpdb->term_taxonomy, array(
						//		'term_id' => $term_id,
						//		'taxonomy' => $taxonomy
						//	)
						//);
						$term_taxonomy_id = $wpdb->insert_id;
						$__dbg = compact('taxonomy', 'attribute_value', 'term_id', 'term_taxonomy_id');
						//var_dump('<pre>1c: ',$__dbg,'</pre>');
					}
				}
			}
		}
		else 
		{
			// get already existing attribute values
			$attribute_values = array();
			//$terms = get_terms($taxonomy, array('hide_empty' => true));
			//if( !is_wp_error( $terms ) ) {
			//	foreach ($terms as $term) {
			//		$attribute_values[] = $term->name;
			//	}
			//} else {
			//	$error_string = $terms->get_error_message();
			//	var_dump('<pre>',$error_string,'</pre>');  
			//}
			$terms = $this->the_plugin->load_terms($taxonomy);
			foreach ($terms as $term) {
				$attribute_values[] = $term->name;
			}
			
			// Check if $attribute_value is not empty
			if( !empty( $attribute_values ) )
			{
				foreach( $values as $attribute_value ) 
				{
					$attribute_value = (string) $attribute_value;
					$attribute_value = $this->the_plugin->cleanValue( $attribute_value ); // 2015, october 28 - attributes bug update!
					if( !in_array( $attribute_value, $attribute_values ) ) 
					{
						// add new attribute value
						$__term_and_tax = wp_insert_term($attribute_value, $taxonomy);
						$__dbg = compact('taxonomy', 'attribute_value', '__term_and_tax');
						//var_dump('<pre>1b: ',$__dbg,'</pre>');
					}
				}
			}
		}

		// Add terms
		if( is_array( $value ) )
		{
			foreach( $value as $dm_v )
			{
				$dm_v = (string) $dm_v;
				if( !is_array($dm_v) && is_string($dm_v)) {
					$dm_v = $this->the_plugin->cleanValue( $dm_v ); // 2015, october 28 - attributes bug update!
					$__term_and_tax = wp_insert_term( $dm_v, $taxonomy );
					$__dbg = compact('taxonomy', 'dm_v', '__term_and_tax');
					//var_dump('<pre>2: ',$__dbg,'</pre>');
				}
			}
		}
		else
		{
			$value = (string) $value;
			if( !is_array($value) && is_string($value) ) {
				$value = $this->the_plugin->cleanValue( $value ); // 2015, october 28 - attributes bug update!
				$__term_and_tax = wp_insert_term( $value, $taxonomy );
				$__dbg = compact('taxonomy', 'value', '__term_and_tax');
				//var_dump('<pre>2b: ',$__dbg,'</pre>');
			}
		}
		
		// link to woocommerce attribute values
		if( !empty( $values ) )
		{
			foreach( $values as $term )
			{
				
				if( !is_array($term) && !is_object( $term ) )
				{ 
					$term = sanitize_title($term);
					
					$term_taxonomy_id = $wpdb->get_var( "SELECT tt.term_taxonomy_id FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} as tt ON tt.term_id = t.term_id WHERE t.slug = '".esc_sql($term)."' AND tt.taxonomy = '".esc_sql($taxonomy)."'" );

					if( $term_taxonomy_id ) 
					{
						$checkSql = "SELECT * FROM {$wpdb->term_relationships} WHERE object_id = {$post_id} AND term_taxonomy_id = {$term_taxonomy_id}";
						if( !$wpdb->get_var($checkSql) ) {
							$wpdb->insert(
									$wpdb->term_relationships, array(
										'object_id' => $post_id,
										'term_taxonomy_id' => $term_taxonomy_id
									)
							);
						}
					}
				}
			}
		}
	}

	// Product Price
	public function is_product_price_zero( $thisProd ) {
		$product_meta = array('product' => array('regular_price' => ''));

		if ( isset($thisProd['__extra'], $thisProd['__extra']['ConvertedCurrentPrice']) ) {
			$product_meta['product']['regular_price'] = $thisProd['__extra']['ConvertedCurrentPrice'];
		} else if ( isset($thisProd['__extra'], $thisProd['__extra']['CurrentPrice']) ) {
			$product_meta['product']['regular_price'] = $thisProd['__extra']['CurrentPrice'];
		}
		if ( isset($thisProd['is_variation'], $thisProd['__extra']['StartPrice'])
			&& $thisProd['is_variation'] ) {
			$product_meta['product']['regular_price'] = $thisProd['__extra']['StartPrice'];
		}
		$product_meta['product']['price'] = $product_meta['product']['regular_price'];
		
		$prodprice = $product_meta['product'];

		if ( empty($prodprice['regular_price']) || (int)$prodprice['regular_price'] <= 0 ) return true;
		return false;
	}

	public function get_product_price( $thisProd, $post_id=null, $pms=array() ) {
		$pms = array_replace_recursive(array(
			'do_update' => false,
		), $pms);
		extract( $pms );

		$ret = array(
			'status'                => 'valid',
			'_price'                => '',
			'_sale_price'           => '',
			'_regular_price'        => '',
			'_price_update_date'    => '',
			'_currency'				=> '',
		);

		// get current product meta, update the values of prices and update it back
		if ( $do_update ) {
			$product_meta = get_post_meta( $post_id, '_product_meta', true );
			$product_meta = ! is_array($product_meta) ? array('product' => array('regular_price' => '')) : $product_meta;
		}
		else {
			$product_meta = array('product' => array('regular_price' => ''));
		}

		if ( isset($thisProd['__extra'], $thisProd['__extra']['ConvertedCurrentPrice']) ) {
			$product_meta['product']['regular_price'] = $thisProd['__extra']['ConvertedCurrentPrice'];
		} else if ( isset($thisProd['__extra'], $thisProd['__extra']['CurrentPrice']) ) {
			$product_meta['product']['regular_price'] = $thisProd['__extra']['CurrentPrice'];
		}
		if ( isset($thisProd['is_variation'], $thisProd['__extra']['StartPrice'])
			&& $thisProd['is_variation']
		) {
			$product_meta['product']['regular_price'] = $thisProd['__extra']['StartPrice'];
		}
		$product_meta['product']['price'] = $product_meta['product']['regular_price'];

		//update in 2019-may-22
		if ( isset($thisProd['__extra']['DiscountPriceInfo']) ) {

			$DiscountPriceInfo = ! empty($thisProd['__extra']['DiscountPriceInfo']) && is_array($thisProd['__extra']['DiscountPriceInfo']) ? $thisProd['__extra']['DiscountPriceInfo'] : array();
			$DiscountPriceInfo = isset($DiscountPriceInfo['OriginalRetailPrice']) ? (string) $DiscountPriceInfo['OriginalRetailPrice'] : '';

			if ( '' !== $DiscountPriceInfo ) {
				$product_meta['product']['price'] = $product_meta['product']['regular_price'];
				$product_meta['product']['sale_price'] = $product_meta['product']['regular_price'];
				$product_meta['product']['regular_price'] = $DiscountPriceInfo;
			}
		}

		$current_time = time();

		// set product price metas!
		if ( isset($product_meta['product']['sale_price']) && !empty($product_meta['product']['sale_price']) ) {
			$ret['_sale_price'] = $product_meta['product']['sale_price'];
		} else { // new sale price is 0
			$ret['_sale_price'] = '';
		}

		$ret['_price_update_date'] = $current_time;
		$ret['_regular_price'] = $product_meta['product']['regular_price'];
		$ret['_price'] = $product_meta['product']['price'];

		if ( $do_update ) {
			update_post_meta($post_id, '_price_update_date', $current_time);
			update_post_meta($post_id, '_regular_price', $ret['_regular_price']);
			update_post_meta($post_id, '_price', $ret['_price']);

			update_post_meta($post_id, '_sale_price', $ret['_sale_price']);

			// set product price extra metas!
			$this->productPriceSetMeta( $thisProd, $post_id, 'return' );
		}

		return $ret;
	}

	// Product Variations
	public function set_woocommerce_variations( $retProd, $parent_id, $pms=array() )
	{
		global $woocommerce;

		$def_var_max_allowed = isset($this->amz_settings['product_variation'])
			? $this->amz_settings['product_variation'] : 'yes_5';
		$def_var_max_allowed = $this->convert_variation_number_to_number( $def_var_max_allowed );

		$pms = array_replace_recursive(array(
			// maximum number of variations to import
			'var_max_allowed' 	=> $def_var_max_allowed,

			// number of variations already existing in database for this product
			'var_exist'			=> 0,

			// number of new variations found in amazon response
			'var_new'			=> 0,
		), $pms);
		extract( $pms );


		//:: init
		$ret = array(
			'status'        => 'valid',
			'msg'           => '',
			'nb_found'      => 0,
			'nb_parsed'     => 0,
			'nb_items' 		=> 0,
		);

		//$var_mode = '';
		$VariationDimensions = array();
		$status = 'valid';

		$_max_nb_setting = $var_max_allowed == $this->the_plugin->ss['max_per_product_variations'] ? 'all' : $var_max_allowed;


		//:: validation
		if ( empty($var_max_allowed) ) {
			$status = 'invalid';
			$msg = sprintf( $status . ': no variations imported (number of variations setting in config: %s).', $_max_nb_setting );
			return array_merge($ret, array(
				'status' 	=> $status,
				'msg' 		=> $msg,
			));
		}
		if ( ! isset($retProd['Variations'], $retProd['Variations']['Variation'])
			|| empty($retProd['Variations']['Variation'])
			|| ! is_array($retProd['Variations']['Variation'])
		) {
			$status = 'invalid';
			$msg = sprintf( $status . ': no (new) variations found in amazon response (number of variations setting in config: %s).', $_max_nb_setting );
			return array_merge($ret, array(
				'status' 	=> $status,
				'msg' 		=> $msg,
			));
		}
		if ( $var_exist && ( $var_exist >= $var_max_allowed ) ) {
			$status = 'invalid';
			$msg = sprintf( $status . ': %s new variations found and another %s already exists in database (number of variations setting in config: %s).', $var_new, $var_exist, $_max_nb_setting );
			return array_merge($ret, array(
				'status' 	=> $status,
				'msg' 		=> $msg,
			));
		}


		//:: parse variations
		$nb_current = $var_exist;
		$nb_parsed = 0;
		$msg_parsed = array();

		$this->the_plugin->timer_start(); // Start Timer

		// its not a simple product, it is a variable product
		wp_set_post_terms($parent_id, 'variable', 'product_type', false);

		// initialize the variation dimensions array
		$varspecs = isset($retProd['Variations']['VariationSpecificsSet'],
			$retProd['Variations']['VariationSpecificsSet']['NameValueList'])
			? $retProd['Variations']['VariationSpecificsSet']['NameValueList'] : array();
		if ( !isset($varspecs[0]) ) { // array with 1 element
			$varspecs = array( $varspecs );
		}
		foreach ($varspecs as $kk => $vv) {
			$dim = $vv['Name'];
			$VariationDimensions["$dim"] = array();
		}

		//$retProd['Variations']['TotalVariations']
		$total = $this->the_plugin->get_amazon_variations_nb( $retProd['Variations']['Variation'], 'ebay' );
		$ret['nb_found'] = $total;

		$variations = array();
		if ($total <= 1) {
			$variations[] = $retProd['Variations']['Variation'];
		} else {
			$variations = (array) $retProd['Variations']['Variation'];
		}

		$compatVariations = $this->ebay_product_compatible_variations( $retProd, $variations );
		$variations = $compatVariations['variations'];

		// Loop through the variation
		// only keep the first max allwed variations
		$offset = 0;
		foreach ($variations as $variation_item) {
			
			// check if there are still variations in amazon response?
			if ( $offset > ( $total - 1 ) ) {
				break;
			}

			// is max allowed number of variations per product reached?
			if ( $nb_current >= $var_max_allowed ) {
				break;
			}

			$stat_variation_post = $this->variation_post( $variation_item, $parent_id, $VariationDimensions );
			$VariationDimensions = $stat_variation_post['VariationDimensions'];
			if ( 'valid' == $stat_variation_post['status'] ) {
				$nb_parsed++;
				$nb_current++;
			}
			else {
				$msg_parsed[] = $stat_variation_post['msg'];
			}
			//$___ = get_post( $stat_variation_post['variation_id'], ARRAY_A ); var_dump('<pre>',$___ ,'</pre>');

			$offset++;

		} // end foreach


		$tempProdAttr = get_post_meta( $parent_id, '_product_attributes', true );

		foreach ( $VariationDimensions as $name => $values ) {
			if ( $name != '' ) {
				$dimension_name = $this->the_plugin->cleanTaxonomyName(strtolower($name));

				// convert value into imploded array
				if( is_array($values) ) {
					$values = $this->the_plugin->multi_implode( $values, ', ' ); 
				}

				// Clean
				$values = $this->the_plugin->cleanValue( $values );

				$tempProdAttr[$dimension_name] = array(
					'name' => $dimension_name,
					'value' => '', //$values, // 2015, october 28 - attributes bug update!
					'position' => 0,
					'is_visible' => 1,
					'is_variation' => 1,
					'is_taxonomy' => 1,
				);

				//$this->add_attribute( $parent_id, $name, $values );
			}
		}

		//update_post_meta($parent_id, '_product_attributes', serialize($tempProdAttr));
		// 2015-08-26 fix/ remove double serialize
		
		update_post_meta($parent_id, '_product_attributes', $tempProdAttr);
		
		if ( $offset ) {
			$this->the_plugin->add_last_imports('last_import_variations', array(
				'duration'      => $this->the_plugin->timer_end(),
				'nb_items'      => $offset,
			)); // End Timer & Add Report
		}

		$ret['nb_items'] = $offset;


		// status
		$ret['nb_parsed'] = $nb_parsed;

		$status = array();
		$status[] = $var_max_allowed > 0;
		$status[] = empty($ret['nb_found']) || empty($ret['nb_parsed']);
		$status = $status[0] && $status[1] ? 'invalid' : 'valid';

		$msg = array();
		if ( $var_exist ) {
			$msg[] = sprintf( $status . ': %s product variations added from %s total variations found, %s variations already existent in database (number of variations setting in config: %s).', $ret['nb_parsed'], $ret['nb_found'], $var_exist, $_max_nb_setting );
		}
		else {
			$msg[] = sprintf( $status . ': %s product variations added from %s total variations found (number of variations setting in config: %s).', $ret['nb_parsed'], $ret['nb_found'], $_max_nb_setting );
		}
		if ( ! empty($msg_parsed) ) {
			$msg[] = implode('<br/>', $msg_parsed);
		}
		$msg = implode('<br/>', $msg);

		return array_merge($ret, array(
			'status'    => $status,
			'msg'       => $msg,
		));
	}
	
	public function variation_post( $variation_item, $parent_id, $VariationDimensions )
	{
		global $woocommerce, $wpdb;

		$ret = array(
			'status'        => 'invalid',
			'msg'           => '',
			'VariationDimensions' => $VariationDimensions,
			'variation_id' 	=> 0,
		);

		if ( ! is_array($variation_item) || empty($variation_item) ) {
			$variation_item = array();
		}

		$variation_asin = isset($variation_item['ASIN']) ? $variation_item['ASIN'] : '';

		$variation_post = get_post( $parent_id, ARRAY_A );

		/*
		$variation_item__ = array_merge_recursive($variation_item, array(
			'ws' => 'ebay',
			'__parent_asin' => isset($variation_item['ParentASIN']) ? $variation_item['ParentASIN'] : '',
			'__parent_content' => $variation_post['post_content'],
		));
		$product_desc = $this->the_plugin->product_build_desc($variation_item__, false);
		$excerpt = isset($product_desc['short']) ? $product_desc['short'] : '';
		$desc = isset($product_desc['desc']) ? $product_desc['desc'] : '';

		// :: update variation parent with desc,excerpt from variation child if found!
		$desc_used = array();
		$args_update = array();
		$args_update['ID'] = $parent_id;

		$desc_used = array(
			'date_done'				=> date("Y-m-d H:i:s"), // only for debug purpose
		);

		if ( !empty($desc) ) {
			$__post_content = $variation_post['post_content'];
			$__post_content = $this->product_clean_desc( $__post_content );

			if ( $__post_content == '' ) {
				$args_update['post_content'] = $desc;

				$child_asin = isset($variation_item['ASIN']) ? $variation_item['ASIN'] : '';
				$child_asin = $this->the_plugin->prodid_set($child_asin, 'ebay', 'add');
				$desc_used = array(
					'child_asin' => $child_asin,
				);
			}
		}
		if ( !empty($excerpt) ) {
			$__post_content = $variation_post['post_excerpt'];
			$__post_content = trim( $__post_content );

			if ( $__post_content == '' ) {
				$args_update['post_excerpt'] = $excerpt;

				//$child_asin = isset($variation_item['ASIN']) ? $variation_item['ASIN'] : '';
				//$child_asin = $this->the_plugin->prodid_set($child_asin, 'ebay', 'add');
				//$desc_used = array(
				//	'child_asin'					=> isset($variation_item['ASIN']) ? $variation_item['ASIN'] : '',
				//);
			}
		}

		// update the post if needed
		if(count($args_update) > 1) { // because ID is allways the same!
			wp_update_post( $args_update );

			if ( !empty($desc_used) && isset($desc_used['child_asin']) ) {
				update_post_meta( $parent_id, '_amzaff_desc_used', $desc_used );
			}
		}
		*/

		// :: insert variation child
		//$variation_post['post_title'] = isset($variation_item['Title']) ? $variation_item['Title'] : '';
		if ( isset($variation_item['Title']) ) {
			$variation_post['post_title'] = $variation_item['Title'];
		}
		//$variation_post['post_content'] = $desc;
		//$variation_post['post_excerpt'] = $excerpt;
		$variation_post['post_status'] = 'publish';
		$variation_post['post_type'] = 'product_variation';
		$variation_post['post_parent'] = $parent_id;
		
		$__torem = array('ID', 'post_name', 'guid'); //, 'post_modified', 'post_modified_gmt'
		foreach ( $__torem as $vv) {
			if ( isset($variation_post["$vv"]) ) {
				unset( $variation_post["$vv"] );
			}
		}

		//var_dump('<pre>', $variation_post , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		$variation_post_id = wp_insert_post( $variation_post );
		//$___ = get_post( $variation_post_id, ARRAY_A ); var_dump('<pre>', $___ , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		// variation dimensions
		$variation_dimensions = $this->ebay_variation_get_dimensions( $variation_item );

		// variation images
		$images = array();
		$images['Title'] = isset($variation_item['Title']) ? $variation_item['Title'] : uniqid();
		$images['images'] = $variation_item['images'];

		$this->set_product_images( $images, $variation_post_id, $parent_id, 1 );
		
		// set the product price
		$this->get_product_price(
			$variation_item,
			$variation_post_id,
			array( 'do_update' => true )
		);
		
		// than update the metapost
		$this->set_product_meta_options( $variation_item, $variation_post_id, true );
		
		// Compile all the possible variation dimensions
		foreach ($variation_dimensions as $attr_name => $attr_vals) {
			foreach ($attr_vals as $attr_val) {
				// Clean
				$attr_val_ = $this->the_plugin->cleanValue( $attr_val );

				$this->add_attribute( $parent_id, $attr_name, $attr_val_ );

				if ( !isset($VariationDimensions["$attr_name"]) ) {
					$VariationDimensions["$attr_name"] = array();
				}
				if ( !in_array($attr_val_, $VariationDimensions["$attr_name"]) ) {
					$VariationDimensions["$attr_name"][] = $attr_val_;
				}
		
				$dimension_name = $this->the_plugin->cleanTaxonomyName(strtolower($attr_name));
				update_post_meta($variation_post_id, 'attribute_' . $dimension_name, sanitize_title($attr_val_)); 
			}
		}
		 
		// refresh attribute cache
		$dmtransient_name = 'wc_attribute_taxonomies';
		$dmattribute_taxonomies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies");
		set_transient($dmtransient_name, $dmattribute_taxonomies);
		
		// status messages
		$msg = sprintf( '- variation %s inserted with ID # %s', $variation_asin, $variation_post_id );
		$this->the_plugin->opStatusMsgSet(array(
			'msg'       => $msg,
		));

		return array_replace_recursive($ret, array(
			'status' 				=> 'valid',
			'msg' 					=> $msg,
			'VariationDimensions' 	=> $VariationDimensions,
			'variation_id' 			=> $variation_post_id,
		));
	}
	
	// Product Images
	public function set_product_images( $retProd, $post_id, $parent_id=0, $number_of_images='all' )
	{
		$ret = array(
			'status'        => 'valid',
			'msg'           => '',
			'nb_found'      => 0,
			'nb_parsed'     => 0,
		);

		$_max_nb_setting = $number_of_images;

		$max_images_per_variation = 1;
		if ( $this->the_plugin->is_plugin_avi_active() ) {
			$max_images_per_variation = $this->the_plugin->ss['max_images_per_variation'];
		}

		$retProd["images"]['large'] = @array_unique($retProd["images"]['large']);
		$retProd["images"]['large'] = @array_filter($retProd["images"]['large']); // remove empty array elements!

		$status = 'valid';
		if ( empty($retProd["images"]['large']) ) {
			$status = 'invalid';
			return array_merge($ret, array(
				'status'    => $status,
				'msg'       => sprintf( $status . ': no images found (number of images setting: %s).', $_max_nb_setting ),
			));
		}
		$ret['nb_found'] = count($retProd["images"]['large']);
		
		if ( (int) $number_of_images > 0 ){
			$retProd['images']['large'] = array_slice($retProd['images']['large'], 0, (int) $number_of_images);
		}

		// variation child: ONLY MAX IMAGES PER VARIATION
		if ( $parent_id > 0 ) {
			$retProd["images"]['large'] = array_slice($retProd["images"]['large'], 0, $max_images_per_variation);
		}

		$productImages = array();
		
		$step = 0;
		// try to download the images
		//if ( 1 ) {
		//    $this->the_plugin->timer_start(); // Start Timer
		//}
		
		// insert the product into db if is not duplicate
		$amz_prod_status = $this->the_plugin->db_custom_insert(
			$this->the_plugin->db->prefix . 'amz_products',
			array(
				'values' => array(
					'post_id' => $post_id, 
					'post_parent' => $parent_id,
					'title' => isset($retProd["Title"]) ? $retProd["Title"] : 'untitled',
					'type' => (int) $parent_id > 0 ? 'variation' : 'post',
					'nb_assets' => count($retProd["images"]['large'])
				),
				'format' => array(
					'%d',
					'%d',
					'%s',
					'%s',
					'%d' 
				)
			),
			true
		);
		//$amz_prod_status = $this->the_plugin->db->insert( 
		//	$this->the_plugin->db->prefix . 'amz_products', 
		//	array( 
		//		'post_id' => $post_id, 
		//		'post_parent' => $parent_id,
		//		'title' => isset($retProd["Title"]) ? $retProd["Title"] : 'untitled',
		//		'type' => (int) $parent_id > 0 ? 'variation' : 'post',
		//		'nb_assets' => count($retProd["images"]['large'])
		//	), 
		//	array( 
		//		'%d',
		//		'%d',
		//		'%s',
		//		'%s',
		//		'%d' 
		//	) 
		//);
	
		foreach ($retProd["images"]['large'] as $key => $value){

			$thumb = isset($retProd["images"]['small'][$key]) ? $retProd["images"]['small'][$key] : $value;
			$image_sizes = isset($retProd["images"]['sizes'][$key]) ? $retProd["images"]['sizes'][$key] : array();
			$this->the_plugin->db_custom_insert(
				$this->the_plugin->db->prefix . 'amz_assets',
				array(
					'values' => array(
						'post_id' => $post_id,
						'asset' => $value,
						'thumb' => $retProd["images"]['small'][$key],
						'date_added' => date( "Y-m-d H:i:s" ),
						'image_sizes'	=> serialize($image_sizes)
					), 
					'format' => array( 
						'%d',
						'%s',
						'%s',
						'%s',
						'%s'
					)
				),
				true
			);
			//$this->the_plugin->db->insert( 
			//	$this->the_plugin->db->prefix . 'amz_assets', 
			//	array(
			//		'post_id' => $post_id,
			//		'asset' => $value,
			//		'thumb' => $retProd["images"]['small'][$key],
			//		'date_added' => date( "Y-m-d H:i:s" )
			//	), 
			//	array( 
			//		'%d',
			//		'%s',
			//		'%s',
			//		'%s'
			//	) 
			//);
			
			//$ret = $this->the_plugin->download_image($value, $post_id, 'insert', $retProd['Title'], $step);
			//if(count($ret) > 0){
			//	$productImages[] = $ret;
			//}
			$step++;
		}
		
		// execute only for product, not for a variation child
		//if ( $parent_id <= 0 && count($retProd["images"]['large']) > 0 ) {
		//    $this->the_plugin->add_last_imports('last_import_images', array(
		//        'duration'      => $this->the_plugin->timer_end(),
		//        'nb_items'      => isset($retProd["images"]['large']) ? (int) count($retProd["images"]['large']) : 0,
		//    )); // End Timer & Add Report
		//}

		// status
		$ret['nb_parsed'] = $step;

		$status = array();
		$status[] = ( (string) $number_of_images === 'all' ) || ( (int) $number_of_images > 0 );
		$status[] = empty($ret['nb_found']) || empty($ret['nb_parsed']);
		$status = $status[0] && $status[1] ? 'invalid' : 'valid';

		//if ( $this->the_plugin->is_remote_images ) {
		//	$setRemoteImgStatus = $this->build_remote_images( $post_id );
		//}

		return array_merge($ret, array(
			'status'    => $status,
			'msg'       => sprintf( $status . ': %s product assets prepared in database from %s images found (number of images setting in config: %s).', $ret['nb_parsed'], $ret['nb_found'], $_max_nb_setting ),
		));

		// add gallery to product
		//$productImages = array(); // remade in assets module!
		//if(count($productImages) > 0){
		//	$the_ids = array();
		//	foreach ($productImages as $key => $value){
		//		$the_ids[] = $value['attach_id'];
		//	}
			
		//	// Add the media gallery image as a featured image for this post
		//	update_post_meta($post_id, "_thumbnail_id", $productImages[0]['attach_id']);
		//	update_post_meta($post_id, "_product_image_gallery", implode(',', $the_ids));
		//}
	}

	// Product Metas
	public function set_product_meta_options( $retProd, $post_id, $is_variation=true )
	{
		//if ( $is_variation == false ){
		//	$tab_data = array();
		//	$tab_data[] = array(
		//		'id' => 'amzAff-customer-review',
		//		'content' => '<iframe src="' . ( isset($retProd['CustomerReviewsURL']) ? urldecode($retProd['CustomerReviewsURL']) : '' ) . '" width="100%" height="450" frameborder="0"></iframe>'
		//	);	
		//}

		// update the metapost
		//update_post_meta($post_id, '_amzASIN', $retProd['ASIN']);
		update_post_meta($post_id, '_visibility', 'visible');
		update_post_meta($post_id, '_downloadable', 'no');
		update_post_meta($post_id, '_virtual', 'no');
		update_post_meta($post_id, '_stock_status', 'instock');
		update_post_meta($post_id, '_backorders', 'no');
		update_post_meta($post_id, '_manage_stock', 'no');
		update_post_meta($post_id, '_amzaff_country', $retProd['country']);
		//update_post_meta($post_id, '_product_url', home_url('/?redirectAmzASIN=' . $retProd['ASIN'] ));

		if ( isset($retProd['SalesRank']) ) {
			update_post_meta($post_id, '_sales_rank', $retProd['SalesRank']);
		}
		if ( isset($retProd['SKU']) ) {
			update_post_meta($post_id, '_sku', $retProd['SKU']);
		}

		if ( $is_variation == false ) {
			update_post_meta($post_id, '_product_version', $this->the_plugin->get_woocommerce_version()); // 2015, october 28 - attributes bug repaired!

			delete_transient( "wc_product_type_$post_id" );
			set_transient( "wc_product_type_$post_id", 'external');
			
			wp_set_object_terms( $post_id, 'external', 'product_type' );
		}
		
		//:: 2018-aug-27
		update_post_meta($post_id, '_ebay_asin', $this->the_plugin->prodid_set($retProd['ASIN'], self::$provider, 'sub'));
		update_post_meta($post_id, '_amzaff_prodid', $this->the_plugin->prodid_set($retProd['ASIN'], self::$provider, 'add'));
		update_post_meta($post_id, '_amzaff_prodtype', self::$provider);
		update_post_meta($post_id, '_product_url', home_url(sprintf(
			'/?redirect_prodid=%s',
			$this->the_plugin->prodid_set($retProd['ASIN'], self::$provider, 'add')
		)));
		if ( isset($retProd['DetailPageURL']) ) {
			update_post_meta($post_id, '_amzaff_product_url', $retProd['DetailPageURL']);
		}
		
		if ( isset($retProd['__extra']) && !empty($retProd['__extra']) ) {
			update_post_meta($post_id, '_amzaff_extra', $retProd['__extra']);
		}

		//:: compatibility with wooaffiliates
		//update_post_meta($post_id, '_aiowaff_prodid', $this->the_plugin->prodid_set($retProd['ASIN'], self::$provider, 'add'));
		//update_post_meta($post_id, '_aiowaff_prodtype', self::$provider);
		//if ( isset($retProd['DetailPageURL']) ) {
		//	update_post_meta($post_id, '_aiowaff_product_url', $retProd['DetailPageURL']);
		//}
		//if ( isset($retProd['__extra']) && !empty($retProd['__extra']) ) {
		//	update_post_meta($post_id, '_aiowaff_extra', $retProd['__extra']);
		//}

		//:: compatibility with wooebay
		//update_post_meta($post_id, '_wwcEbyAff_prodid', $this->the_plugin->prodid_set($retProd['ASIN'], self::$provider, 'add'));
		//update_post_meta($post_id, '_wwcEbyAff_prodtype', self::$provider);
		//if ( isset($retProd['DetailPageURL']) ) {
		//	update_post_meta($post_id, '_wwcEbyAff_product_url', $retProd['DetailPageURL']);
		//}
		//if ( isset($retProd['__extra']) && !empty($retProd['__extra']) ) {
		//	update_post_meta($post_id, '_wwcEbyAff_extra', $retProd['__extra']);
		//}
	}

	public function attrclean_splitTitle($title) {
		return $title;
	}
	

	// Product Price - Update november 2014
	public function productPriceSetMeta( $thisProd, $post_id='', $return=true ) {
		return array();
	}

	public function productPriceSetRegularSaleMeta( $post_id, $type, $newMetas=array() ) {
		return array();
	}

	public function productPriceGetRegularSaleStatus( $post_id, $type='both' ) {
		return array();
	}


	// Octomber 2015 - new plugin functions
	//
	// pms: array(
	// 		prod_id		: (string) ebay product id
	// 		prod_link	: (string) ebay product page url
	// )
	//
	public function get_product_link( $pms=array() ) {
		extract($pms);

		$__ = $this->the_plugin->cur_provider;
		$this->the_plugin->cur_provider = 'ebay';

		if ( is_object($this->the_plugin->ebay_utils) ) {
			$ret = $this->the_plugin->ebay_utils->get_product_link( array(
				'prod_id'			=> $prod_id,
				'prod_link'			=> $prod_link,
				'globalid'			=> $this->the_plugin->main_aff_site(), //(ex: EBAY-US)
				'affid'				=> $this->the_plugin->main_aff_id(), //(ex: 01234)
			));
		}
		else {
			$ret = '';
		}

		$this->the_plugin->cur_provider = $__;

		return $ret;
	}

	// key: parameter only defined for compatibility with other providers helper
	public function get_countries( $key='country' ) {
		if ( is_object($this->the_plugin->ebay_utils) ) {
			return $this->the_plugin->ebay_utils->get_countries();
		}
		else {
			return array();
		}
	}
	
	// key: country || main_aff_id
	// same results not matter the key value; made like this for compatibility with amazon helper!
	public function get_country_name( $country, $key='country' ) {
		$_country = $country;
		if ( is_object($this->the_plugin->ebay_utils) ) {
			$_country = $this->the_plugin->ebay_utils->get_location( strtoupper($country), 'globalid', 'sitename' );
		}
		return $_country;
	}

	public function get_product_extra( $post_id ) {
		$extra = array_merge(array('_amzaff_prodtype'), array(
			'_sales_rank', '_amzaff_extra',
		));
		
		$ret = array();
		foreach ($extra as $meta) {
			$ret["$meta"] = get_post_meta($post_id, $meta, true);
		}
		return $ret;
	}


	/**
	 * search products by pages
	 * input(pms): array(
	 * 		requestData			: array,
	 * 		parameters			: array,
	 * 		_optionalParameters	: array,
	 * 		page				: int
	 * )
	 * return: array(
	 * 		response						: array
	 * 		status							: string ( valid | invalid )
	 *      msg								: string
	 * 		code							: int
	 * 		amz_code 						: string
	 * )
	 */
	public function api_search_bypages( $pms=array() ) {

		extract($pms);

			try {

		$this->aaWooZoneEbayWS->setCategory(
			$requestData['category_id']
		);
		$this->aaWooZoneEbayWS->setKeywords(
			isset($parameters['keyword']) ? $parameters['keyword'] : ''
		);
		$this->aaWooZoneEbayWS->setPerPage(20)->setPage( $page );

		if( isset($_optionalParameters) && count($_optionalParameters) > 0 ){
			//if ( isset($_optionalParameters['BrowseNode']) ) {
			//	unset( $_optionalParameters['BrowseNode'] );
			//}
			foreach ($_optionalParameters as $key => $val) {
				if ( in_array($val, array('true', 'false')) ) {
					$_optionalParameters["$key"] = (bool) $val;
				}
			}
			$this->aaWooZoneEbayWS->setOptionalParameters( $_optionalParameters );
		}
		//var_dump('<pre>',$this->aaWooZoneEbayWS,'</pre>');
		
		$response = $this->aaWooZoneEbayWS->search();
		//var_dump('<pre>',$response,'</pre>'); die;

		}
		catch (Exception $e) {

			$msg = WooZoneLiteGetExceptionMsg( $e );

			// unlock current amazon key - aateam keys
			// moved from here in 2018-feb

			$response = array('status' => 'invalid', 'msg' => $msg, 'code' => 1, 'amz_code' => strtolower(WooZoneLiteGetExceptionCode( $e )));
			return array_merge(array('response' => $response), $response);
		}

		$request_status = $this->is_amazon_valid_response( $response );
		
		return array(
			'status'			=> $request_status['status'],
			'msg'				=> $request_status['msg'],
			'code'				=> $request_status['code'],
			'amz_code' 			=> $request_status['amz_code'],
			'response' 			=> $response,
		);
	}

	/**
	 * search products by asins list
	 * input(pms): array(
	 * 		asins				: array,
	 * )
	 * return: array(
	 * 		response						: array
	 * 		status							: string ( valid | invalid )
	 *      msg								: string
	 * 		code							: int
	 * 		amz_code 						: string
	 * )
	 */
	public function api_search_byasin( $pms=array() ) {

		extract($pms);

		try {

		$response = $this->aaWooZoneEbayWS->setItemIds( implode(",", $asins) )->lookup();
		//var_dump('<pre>',$response,'</pre>'); die;

		}
		catch (Exception $e) {

			$msg = WooZoneLiteGetExceptionMsg( $e );

			// unlock current amazon key - aateam keys
			// moved from here in 2018-feb

			$response = array('status' => 'invalid', 'msg' => $msg, 'code' => 1, 'amz_code' => strtolower(WooZoneLiteGetExceptionCode( $e )));
			return array_merge(array('response' => $response), $response);
		}

		$request_status = $this->is_amazon_valid_response( $response );
				
		return array(
			'status'			=> $request_status['status'],
			'msg'				=> $request_status['msg'],
			'code'				=> $request_status['code'],
			'amz_code' 			=> $request_status['amz_code'],
			'response' 			=> $response,
		);
	}

	/**
	 * format api response results
	 * input(pms): array(
	 * 		requestData			: array,
	 * 		response			: array,
	 * )
	 * return: array(
	 * 		requestData			: array,
	 * 		response			: array,
	 * )
	 */
	public function api_format_results( $pms=array() ) {
		extract($pms);
		
		$operation = '';
		if ( isset($response['searchResult'], $response['searchResult']['item'])
			&& !empty($response['searchResult'])
			&& !empty($response['searchResult']['item']) ) {

			$operation = 'search';
		}

		if ( 'search' == $operation ) {
			$rsp = $this->api_search_set_stats(array(
				'requestData'				=> $requestData,
				'response'					=> $response,
			));
			$requestData = $rsp['requestData'];

			$_response = array();
			foreach ( $response['searchResult']['item'] as $key => $value){
				$value['__isfrom'] = 'search';
				$_response["$key"] = $value;
			}
		}
		else {
			// verify array of Items or array of Item elements
			if ( isset($response['Item']['ItemID']) ) {
				$response['Item'] = array( $response['Item'] );
			}

			$_response = array();
			foreach ( $response['Item'] as $key => $value){
				//unset($value['Description'], $value['ShipToLocations'], $value['ExcludeShipToLocation']);
				$_response["$key"] = $value;
				$_response["$key"]['__isfrom'] = 'details';
				
				//if ( isset($value['ItemID']) && '281829771857' == $value['ItemID'] ) {
				//	var_dump('<pre>',$value,'</pre>'); 
				//}
			}
		}
		//var_dump('<pre>', $_response, '</pre>'); die('debug...');

		return array(
			'requestData'	=> $requestData,
			'response'		=> $_response,
		);
	}
	
	/**
	 * search results validation
	 * input(pms): array(
	 * 		results				: array,
	 * )
	 * return: array(
	 * 		status				: boolean,
	 * 		nbpages				: int,
	 * )
	 */
	public function api_search_validation( $pms=array() ) {
		extract($pms);

		$status = true;
		$nbpages = 0;
		if ( !isset($results['result'], $results['result']['TotalResults'], $results['result']['NbPagesSelected'])
			|| count($results) < 2 ) {
			$status = false;
		} else {
			$nbpages = (int) $results['result']['NbPagesSelected'];
		}
		
		return array(
			'status'		=> $status,
			'nbpages'		=> $nbpages,
		);
	}
	
	/**
	 * search products by pages: get search stats!
	 * input(pms): array(
	 * 		results				: array,
	 * )
	 * return: array(
	 * 		stats				: array,
	 * )
	 */
	public function api_search_get_stats( $pms=array() ) {
		extract($pms);

		return array(
			'stats'	=> array(
				'TotalResults'			=> $results['result']['TotalResults'],
				'NbPagesSelected'		=> $results['result']['NbPagesSelected'],
				'TotalPages'			=> $results['result']['TotalPages'],
			)
		);
	}
	
	/**
	 * search products by pages: set search stats!
	 * input(pms): array(
	 * 		requestData			: array, 
	 * 		response			: array,
	 * )
	 * return: array(
	 * 		requestData			: array, 
	 * 		stats				: array,
	 * )
	 */
	public function api_search_set_stats( $pms=array() ) {
		extract($pms);

		{
			$totalItems = 0; $totalPages = 0;
			if ( isset($response['paginationOutput'])
				&& !empty($response['paginationOutput']) ) {
					
				$pg = $response['paginationOutput'];

				$nbitems_per_page = isset($pg['entriesPerPage']) && !empty($pg['entriesPerPage'])
					? (int) $pg['entriesPerPage'] : 20;
				
				$totalPages = isset($pg['totalPages']) ? $pg['totalPages'] : 0;
				$totalItems = isset($pg['totalEntries']) ? $pg['totalEntries'] : 0;

				if ( !empty($totalItems) && empty($totalPages) ) {
					$totalPages = $totalItems > 0 ? ceil( $totalItems / $nbitems_per_page ) : 0;
				}
				if ( !empty($totalPages) && empty($totalItems) ) {
					$totalItems = (int) ($totalPages * $nbitems_per_page);
				}
			}

			if ( isset($totalPages, $requestData['nbpages'])
				&& $totalPages > 0
				&& (int) $totalPages < $requestData['nbpages'] ) {

				$requestData['nbpages'] = (int) $totalPages;
				// don't put this validated nbpages in $__cacheSearchPms, because the cache file could not be recognized then!
			}
		}
		
		return array(
			'requestData'	=> $requestData,
			'stats'			=> array(
				'TotalResults'			=> $totalItems,
				'TotalPages'			=> $totalPages,
			)
		);
	}
	
	/**
	 * search products by pages: get page asins list from cache file! 
	 * input(pms): array(
	 * 		page_content		: array,
	 * )
	 * return: array(
	 * 		asins				: int,
	 * )
	 */
	public function api_cache_get_page_asins( $pms=array() ) {
		extract($pms);
		
		$asins = $page_content['result']['items'];
		return array(
			'asins'		=> $asins,
		);
	}
	
	/**
	 * search products by pages: set page content as list of asins! 
	 * input(pms): array(
	 * 		requestData			: array, 
	 * 		content				: array,
	 * 		old_content			: array,
	 * 		cachename			: object,
	 * 		page				: int,
	 * )
	 * return: array(
	 * 		dataToSave			: array,
	 * )
	 */
	public function api_cache_set_page_content( $pms=array() ) {
		extract($pms);

		$response = $content;
			
		$dataToSave = array();
		if ( !empty($old_content) ) {
			$dataToSave = $old_content;
		} else {

			$rsp = $this->api_search_set_stats(array(
				'requestData'				=> $requestData,
				'response'					=> $response,
			));
			$stats = $rsp['stats'];

			$dataToSave['result']['TotalResults'] = $stats['TotalResults'];
			$dataToSave['result']['TotalPages'] = $stats['TotalPages'];
			$dataToSave['result']['NbPagesSelected'] = $cachename->params['nbpages'];
		}

		if ( is_array($content) && !isset($content['__notused__']) ) {

			$rsp = $this->api_format_results(array(
				'requestData'			=> $requestData,
				'response'				=> $response,
			));

			$dataToSave["$page"] = array();
			
			// 1 item found only
			if ( $dataToSave['result']['TotalResults'] == 1 && !isset($rsp['response'][0]) ) {
				$rsp['response'] = array($rsp['response']);
			}

			foreach ($rsp['response'] as $key => $value) {
				$product = $this->build_product_data( $value );
				if ( !empty($product['ASIN']) ) {
					$dataToSave["$page"]['result']['items']["$key"] = $product['ASIN'];
				}
			}
		}			

		return array(
			'dataToSave'		=> $dataToSave,
		);
	}


	/**
	 * general request to amazon
	 * input(pms): array(
	 * 		requestData						: array( // posible parameters bellow
	 * 			category						: string
	 * 			page							: int
	 * 			keyword							: string
	 * 			asin							: string | array
	 * 			perPage 						: int
	 * 			nodeid 							: string
	 * 		)
	 * 		optionalParameters				: array
	 * 		method							: '' //ex.: lookup | search
	 * )
	 * return: array(
	 * 		response						: array
	 * 		status							: string ( valid | invalid )
	 *      msg								: string
	 * 		code							: int
	 * 		amz_code 						: string
	 * )
	 */
	public function api_make_request( $pms=array() ) {

		extract($pms);
		if ( isset($requestData) ) {
			extract($requestData);
		}
		
		// lock current amazon key - aateam keys
		// moved from here in 2018-feb

		$optionalParameters = isset($optionalParameters) && !empty($optionalParameters) ? $optionalParameters : array();

		if( isset($optionalParameters) && count($optionalParameters) > 0 ){
			//if ( isset($optionalParameters['BrowseNode']) ) {
			//	unset( $optionalParameters['BrowseNode'] );
			//}
			foreach ($optionalParameters as $key => $val) {
				if ( in_array($val, array('true', 'false')) ) {
					$optionalParameters["$key"] = (bool) $val;
				}
			}
		}

		if ( isset($asin) && is_array($asin) ) {
			$asin = implode(",", $asin);
		}
		
		$category			= isset($category) ? $category : 99;
		$page				= isset($page) ? $page : 1;
		$keyword			= isset($keyword) ? $keyword : 'music';
		$nodeid				= isset($nodeid) ? $nodeid : 0;
		$perPage 			= isset($perPage) ? $perPage : 20;

		try {

		$method = isset($pms['method']) ? $pms['method'] : '';
		switch ( $method ) {

			case 'lookup':
				$response = $this->aaWooZoneEbayWS->setOptionalParameters( $optionalParameters )
					->setItemIds( $asin )
					->lookup();
				break;

			case 'search':
				$response = $this->aaWooZoneEbayWS->setCategory( $category )->setKeywords( $keyword )
					->setPerPage( $perPage )->setPage( $page )
					->search();
				break;

			case 'browseNodeLookup':
				$response = $this->aaWooZoneEbayWS
					->setBrowseNodeIds( $nodeid )
					->browseNodeLookup();
				break;

			default:
				$response = array('status' => 'invalid', 'msg' => 'you need to provide a valid method!', 'code' => 1, 'amz_code' => 'ebay:aws.init.issue');
				return array_merge(array('response' => $response), $response);
				//break;
		}
		
		//var_dump('<pre>', $response , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		}
		catch (Exception $e) {

			$msg = WooZoneLiteGetExceptionMsg( $e );

			// unlock current amazon key - aateam keys
			// moved from here in 2018-feb

			$response = array('status' => 'invalid', 'msg' => $msg, 'code' => 1, 'amz_code' => strtolower(WooZoneLiteGetExceptionCode( $e )));
			return array_merge(array('response' => $response), $response);
		}
		//var_dump('<pre>', $response , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		$request_status = $this->is_amazon_valid_response( $response, $method );

		return array(
			'status'			=> $request_status['status'],
			'msg'				=> $request_status['msg'],
			'code'				=> $request_status['code'],
			'amz_code' 			=> $request_status['amz_code'],
			'response' 			=> $response,
		);
	}

	// main method to make requests to amazon api (all others go through it)
	public function api_main_request( $pms=array() ) {

		//:: some init
		$DEBUG = false;

		$method = isset($pms['method']) ? (string) $pms['method'] : '';
		$what_func = isset($pms['what_func']) ? (string) $pms['what_func'] : '';
		$from_func = isset($pms['from_func']) ? (string) $pms['from_func'] : '';
		$from_file = isset($pms['from_file']) ? (string) $pms['from_file'] : '';

		//:: return
		$response = array(
			'status'			=> 'invalid',
			'msg'				=> '',
			'code'				=> -2,
			'amz_code' 			=> '',
			'req_link' 			=> '',
		);

		//:: validation
		$_allowed_func = array( 'api_search_bypages', 'api_search_byasin', 'api_make_request' );
		if ( empty($what_func)
			|| ! in_array($what_func, $_allowed_func)
			|| ! is_callable( array($this, $what_func) )
		) {
			$response = array_replace_recursive($response, array(
				'msg' 	=> 'Invalid what func!',
			));
			return array_merge(array('response' => $response), $response);
		}

		//:: try to make the request
		$cc = 0;
		do {

			$cc++;

   			// validate main object
			if ( ! is_object($this->aaWooZoneEbayWS) ) {
				$msg = $this->the_plugin->wsStatus['ebay']['msg'];
				$response = array('status' => 'invalid', 'msg' => $msg, 'code' => 1, 'amz_code' => 'ebay:aws.init.issue', 'req_link' => '');
				$response = array_merge(array('response' => $response), $response);

				if ( $DEBUG ) {
					echo __FILE__ . ":" . __LINE__. PHP_EOL;
					var_dump('<pre>','response', $response, '</pre>');
				}

				continue 1;
			}

			// debug
			if ( $DEBUG ) {
				echo __FILE__ . ":" . __LINE__. PHP_EOL;
				var_dump('<pre>', 'aaWooZoneEbayWS cfg', $this->aaWooZoneEbayWS->getcfg(), '</pre>');
			}

			// try to make the request
			// HERE WE CALL THE METHOD WHICH MAKE THE REQUEST TO AMAZON API
			$response = $this->$what_func( $pms );

			if ( $DEBUG ) {
				$response_dg = $response; unset($response_dg['response']);
				echo __FILE__ . ":" . __LINE__. PHP_EOL;
				var_dump('<pre>', 'response', $response_dg, '</pre>');
			}
		}
		while (
			$cc == 0
		);

		return $response;
	}



	// get a list of all possible dimensions for a variation child
	public function ebay_variation_get_dimensions( $variation_item=array() ) {
		$newarr = array();
		if ( isset($variation_item['VariationSpecifics']) ) {
			if ( !isset($variation_item['VariationSpecifics'][0]) ) {
				$variation_item['VariationSpecifics'] = array( $variation_item['VariationSpecifics'] );
			}
			foreach ($variation_item['VariationSpecifics'] as $k => $v) {

				if ( isset($v['NameValueList']) ) {
					if ( !isset($v['NameValueList'][0]) ) {
						$v['NameValueList'] = array( $v['NameValueList'] );
					}
					foreach ($v['NameValueList'] as $kk => $vv) {

						$name_ = $vv['Name'];
						if ( !isset($newarr["$name_"]) ) $newarr["$name_"] = array();
						
						if ( !is_array($vv['Value']) ) $vv['Value'] = array( $vv['Value'] );
						foreach ($vv['Value'] as $kkk => $vvv) {

							if ( !in_array($vvv, $newarr["$name_"]) ) {
								$newarr["$name_"][] = $vvv;
							}								
						}
					}
				}
			}
		}
		return $newarr;
	}

	// verify if a variation child is valid!
	public function ebay_variation_is_valid( $variation_item=array() ) {
		if (
			isset($variation_item['VariationSpecifics'])
			|| isset($variation_item['StartPrice'])
			|| isset($variation_item['SKU'])
		) {
			return true;
		}
		return false;
	}

	// build a valid list (with necessary fields) of variations childrens for a variable product
	public function ebay_product_compatible_variations( $retProd, $variations=array() ) {

		// Loop through the variation
		// only keep the first max allwed variations
		$offset = 0;
		foreach ($variations as $idx => $variation_item) {

			$_vid_def = $offset;
			$_vid_def = md5( serialize( $variation_item['VariationSpecifics'] ) );

			// variation identifier
			$variation_id = sprintf(
				self::$variation_id,
				$retProd['ASIN'],
				$_vid_def //isset($variation_item['SKU']) ? $variation_item['SKU'] : $_vid_def
			);
			$variation_id = $this->the_plugin->prodid_set($variation_id, self::$provider, 'add');

			$variation_item = array_merge($variation_item, array(
				'is_variation'	=> true,

				'ASIN'			=> $variation_id,
				'ItemID' 		=> $variation_id,

				'Title'			=> $retProd['Title'] . ' - ' . str_replace( '/', ' ', $variation_id ),
			));

			// variation dimensions
			$variation_dimensions = $this->ebay_variation_get_dimensions( $variation_item );

			// variation images
			$images = $this->get_first_variation_image(
				isset($retProd['Variations'], $retProd['Variations']['Pictures'])
					? $retProd['Variations']['Pictures'] : array(),
				$variation_dimensions
			);

			$variation_item['images'] = $images;

			// other fields
			$variation_item['__extra'] = array();
			$metas = array('StartPrice', 'Quantity', 'SellingStatus');
			foreach ($metas as $key) {
				if ( isset($variation_item["$key"]) && !empty($variation_item["$key"]) ) {
					if ( ! isset($variation_item['__extra']) ) {
						$variation_item['__extra'] = array();
					}
					$variation_item['__extra']["$key"] = isset($variation_item["$key"]) ? $variation_item["$key"] : '';
				}
			}

			// country (of import)
			$variation_item['country'] = $retProd['country'];

			$variations["$idx"] = $variation_item;

			$offset++;

		} // end foreach
		//var_dump('<pre>',$variations ,'</pre>');

		return array(
			'variations' => $variations,
		);
	}
} }