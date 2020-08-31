<?php
/**
 *	Author: AA-Team
 *	Name: 	http://codecanyon.net/user/AA-Team/portfolio
 *	
**/
! defined( 'ABSPATH' ) and exit;

// THE ONLY METHODS which deals with requests to amazon api (in entire plugin)
// 		api_main_request(
// 		api_make_request(
// 		api_search_byasin(
// 		api_search_bypages(

if ( class_exists('WooZoneLiteAmazonHelper') != true ) { class WooZoneLiteAmazonHelper extends WooZoneLite {

	public $the_plugin = null;
	public $aaAmazonWS = null;
	public $amz_settings = array();

	static protected $_instance;
	
	const MSG_SEP = '—'; // messages html bullet // '&#8212;'; // messages html separator

	private static $provider = 'amazon';

	public $image_sizes = array(
		'SwatchImage'		=> 'swatch',
		'SmallImage'		=> 'small',
		'ThumbnailImage'	=> 'thumbnail',
		'TinyImage'			=> 'tiny',
		'MediumImage'		=> 'medium',
		'LargeImage'		=> 'large',
	);
	
	public $current_country = array(
		'key'	=> '',
		'name'	=> '',
	);

	//private $demokeysObj = null; // moved to framework main class
	//public $amzkeysObj = null; // moved to framework main class

	// true = we want to use our amazon keys interchangeable
	public $use_multi_keys = true;

	public $using_aateam_demo_keys = false;

	public $current_aws_settings = array();



	//================================================
	//=== SETUP

	public function __construct( $the_plugin=array(), $params=array() )
	{
		$this->the_plugin = $the_plugin;
		$this->the_plugin->cur_provider = self::$provider;

		// verify amazon keys
		$this->the_plugin->verify_amazon_keys();

		// get all amazon settings options
		$this->amz_settings = $this->the_plugin->amz_settings;

		// setup amazon api class
		$this->setupAmazonWS( $params );

		// moved to framework main class
		// aateam amazon keys - when client use aateam demo keys
		//require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '_keys/demokeys.php' );
		//$this->demokeysObj = new aaWoozoneDemoKeysLib( $this->the_plugin, array() );

		// moved to framework main class
		// multiple amazon keys
		//require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '_keys/amzkeys.php' );
		//$this->amzkeysObj = new aaWoozoneAmzKeysLib( $this->the_plugin );

		// ajax actions
		add_action('wp_ajax_WooZoneLiteCheckAmzKeys', array( $this, 'check_amazon' ), 10, 2);
		add_action('wp_ajax_WooZoneLiteImportProduct', array( $this, 'getProductDataFromAmazon' ), 10, 2);
		
		add_action('wp_ajax_WooZoneLiteStressTest', array( $this, 'stress_test' ));

		//var_dump('<pre>', $this->apiv5_getVariations( 'B07ZMF2CM5' ), '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
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
	 * 		AccessKeyID				: string
	 * 		SecretAccessKey			: string
	 * 		country					: string
	 * 		main_aff_id				: string
	 * 		overwrite_settings		: true | false
	 * )
	 * return: true | false
	 */
	public function setupAmazonWS( $params=array() ) {

		$params = array_replace_recursive(array(
			'overwrite_settings' 	=> false,
		), $params);

		//:: GET SETTINGS
		$settings = $this->amz_settings;

		//:: SETUP
		$params_new = array();

		$mainoptions = array( 'AccessKeyID', 'SecretAccessKey', 'country', 'main_aff_id', 'associateTag' );
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

		$_status = $this->the_plugin->verify_amazon_keys( array(
			'settings' 		=> $params_new
		));
		$params_new = $_status['settings'];
		$params_new2 = array_diff_key($params_new, array('associateTag' => 'xyz'));
		//var_dump('<pre>',$params_new, $params_new2 ,'</pre>'); 

		$this->current_aws_settings = $params_new2;

		$this->using_aateam_demo_keys = 'demo' == $_status['status'] ? true : false;

		//:: overwrite amazon helper main settings
		if ( $params['overwrite_settings'] ) {
			$settings = array_replace_recursive( $settings, $params_new2 );

			// get all amazon settings options
			$this->amz_settings = $settings;
		}

		$this->aaAmazonWS = $this->the_plugin->get_ws_object_new( self::$provider, 'new_ws', array(
			'the_plugin' 		=> $this->the_plugin,
			'settings' 			=> $settings,
			'params_new' 		=> $params_new,
		));

		//:: current country
		$countries = $this->get_countries('country');
		$ckey = $params_new['country']; //isset($params['country']) ? $params['country'] : $settings['country'];
		$cname = isset($countries["$ckey"]) ? $countries["$ckey"] : '';
		$this->current_country = array(
			'key'	=> $ckey,
			'name'	=> $cname,
		);

		return is_object($this->aaAmazonWS) ? true : false;
	}



	//================================================
	//=== API RESPONSE - BUILD PRODUCT DATA

	// verify if amazon response is valid!
	public function is_amazon_valid_response( $response, $operation='' ) {
		$ret = array(
			'status'		=> 'invalid',
			'msg'			=> 'unknown message.',
			'html'			=> 'unknown message.',

			// -1 = default | 0 = success | 1 = error | 2 = error | 3 = error - no results/products found
			'code'			=> -1,

			// (amazon | aateam) error code - usefull for synchronization / cross sell
			// sync: aws:client.requestthrottled
			// cross sell: aws.ecommerceservice.nosimilarities
			// woozonelite custom codes:
			// 		woozonelite:aws.init.issue | woozonelite:aws.request.dropped |
			// 		woozonelite:aws5.issue
			'amz_code' 		=> '',
		);

		$amz_code = '';
		$ul = 'Items'; $li = 'Item';
		switch ($operation) {

			case 'browseNodeLookup':
				$ul = 'BrowseNodes'; $li = 'BrowseNode';
				break;

			case 'getVariations':
				$ul = 'Variations'; $li = 'Item';
				break;
				
			// case 'cartThem':
			// 	$ul = 'Cart'; //$ul = 'CartItems'; $li = 'CartItem';
			// 	break;
		}

		//:: response probably set from a try-catch block
		if ( isset($response['status']) && ( 'invalid' == $response['status'] ) ) {

			$msg = isset($response['msg']) ? $response['msg'] : 'error not catched!';
			if ( isset($response['amz_code']) ) {
				$amz_code = strtolower( $response['amz_code'] );
			}
			return array_merge($ret, array(
				'msg'       => $msg,
				'html'      => $msg,
				'code'      => isset($response['code']) ? $response['code'] : 1,
				'amz_code' 	=> $amz_code,
			));
		}

		//:: parse amazon response
		if ( ! isset($response["$ul"]['Request']['IsValid']) ) {

			//probably request to amazon api was dropped
			$msg = self::$provider.' invalid response: Response/Request/IsValid block don\'t exit.';

			if ( isset($response['Error']['Code']) ) {
				$amz_code = strtolower( $response['Error']['Code'] );
				$msg = self::$provider.' error id: <bold>' . ( $response['Error']['Code'] ) . '</bold> = ' . ( $response['Error']['Message'] );
			}
			return array_merge($ret, array(
				'msg'       => $msg,
				'html'      => $msg,
				'code'      => 1,
				'amz_code'  => $amz_code,
			));
		}

		if ( (string) $response["$ul"]['Request']['IsValid'] === 'False' ) {
	
			if ( isset($response["$ul"]['Request']['Errors']['Error']['Code']) ) {

				$amz_code = strtolower( $response["$ul"]['Request']['Errors']['Error']['Code'] );
				$msg = self::$provider.' error id: <bold>' . ( $response["$ul"]['Request']['Errors']['Error']['Code'] ) . '</bold> = ' . ( $response["$ul"]['Request']['Errors']['Error']['Message'] );
			}
			else if (
				isset($response["$ul"]['Request']['Errors']['Error'])
				&& is_array($response["$ul"]['Request']['Errors']['Error'])
			) {
				$_msg = array();
				$_msg[] = self::$provider.' error id:';
				foreach ($response["$ul"]['Request']['Errors']['Error'] as $err_key => $err_val) {
					$_msg[] = '<bold>' . ( $err_val['Code'] ) . '</bold> = ' . ( $err_val['Message'] );
				}
				$msg = implode('<br />', $_msg);
				
			}
			else {
				$msg = self::$provider.' unknown error.';
			}
			return array_merge($ret, array(
				'msg'       => $msg,
				'html'      => $msg,
				'code'      => 2,
				'amz_code'  => $amz_code,
			));
		}

		//:: No products found!
		//isset($response['Items']['Item']) && count($response['Items']['Item']) > 0
		$rules = array();
		// if ( 'cartThem' == $operation ) {
		// 	$rules[0] = !isset($response["$ul"]['CartItems']) 
		// 		|| ( count($response["$ul"]['CartItems']) <= 0 )
		// 		|| !isset($response["$ul"]['CartItems']["CartItem"])
		// 		|| ( count($response["$ul"]['CartItems']["CartItem"]) <= 0 );
		// }
		// else {
		$rules[0] = ( count($response["$ul"]) <= 0 )
			|| !isset($response["$ul"]["$li"])
			|| ( count($response["$ul"]["$li"]) <= 0 );
		// }

		//No products found!
		if ( $rules[0] ) {

			if ( isset($response["$ul"]['Request']['Errors']['Error']['Code']) ) {

				$amz_code = strtolower( $response["$ul"]['Request']['Errors']['Error']['Code'] );

				$msg = self::$provider.' error id: <bold>' . ( $response["$ul"]['Request']['Errors']['Error']['Code'] ) . '</bold> = ' . ( $response["$ul"]['Request']['Errors']['Error']['Message'] );

				switch ($response["$ul"]['Request']['Errors']['Error']['Code']) {
					case 'AWS.ECommerceService.NoExactMatches':
						$msg = self::$provider.' Sorry, your search did not return any results.';
						break;
						
					case 'AWS.ECommerceService.NoSimilarities':
						$msg = self::$provider.' Sorry, there are no similar items for this product.';
						break;

					case 'AWS.InvalidParameterValue':
						break;
				}
			}
			else if (
				isset($response["$ul"]['Request']['Errors']['Error'])
				&& is_array($response["$ul"]['Request']['Errors']['Error'])
			) {
				$_msg = array();
				$_msg[] = self::$provider.' error id:';
				foreach ($response["$ul"]['Request']['Errors']['Error'] as $err_key => $err_val) {
					$_msg[] = '<bold>' . ( $err_val['Code'] ) . '</bold> = ' . ( $err_val['Message'] );
				}
				$msg = implode('<br />', $_msg);
			}
			else {
				$msg = self::$provider.' no products found.';
			}
			return array_merge($ret, array(
				'msg'       => $msg,
				'html'      => $msg,
				'code'      => 3,
				'amz_code'  => $amz_code,
			));
		}

		//:: success   
		return array_merge($ret, array(
			'status'        => 'valid',
			'msg'           => 'valid message.',
			'html'          => 'valid message.',
			'code'          => 0,
		));
	}

	// product data is valid
	public function is_valid_product_data( $product=array(), $from='' ) {
		if ( empty($product) || !is_array($product) ) return false;
		
		$rules = isset($product['ASIN']) && !empty($product['ASIN']);
		$rules = $rules && 1;
		return $rules ? true : false;
	}

	// build single product data based on amazon request array
	public function build_product_data( $item=array(), $old_item=array() ) {

		// summarize product details
		$retProd = array(
			'ASIN'                  => isset($item['ASIN']) ? $item['ASIN'] : '',
			'ParentASIN'            => isset($item['ParentASIN']) ? $item['ParentASIN'] : '',
			
			'ItemAttributes'        => isset($item['ItemAttributes']) ? $item['ItemAttributes'] : '',
			'Title'                 => isset($item['ItemAttributes']['Title']) ? stripslashes($item['ItemAttributes']['Title']) : '',
			'SKU'                   => isset($item['ItemAttributes']['SKU']) ? $item['ItemAttributes']['SKU'] : '',
			'Feature'               => isset($item['ItemAttributes']['Feature']) ? $item['ItemAttributes']['Feature'] : '',
			'Brand'                 => isset($item['ItemAttributes']['Brand']) ? $item['ItemAttributes']['Brand'] : '',
			'Binding'               => isset($item['ItemAttributes']['Binding']) ? $item['ItemAttributes']['Binding'] : '',
			//'ListPrice'           => isset($item['ItemAttributes']['ListPrice']['FormattedPrice']) ? $item['ItemAttributes']['ListPrice']['FormattedPrice'] : '',
			
			'Variations'            => isset($item['Variations']) ? $item['Variations'] : array(),
			'VariationSummary'      => isset($item['VariationSummary']) ? $item['VariationSummary'] : array(),
			'BrowseNodes'           => isset($item['BrowseNodes']) ? $item['BrowseNodes'] : array(),
			'DetailPageURL'         => isset($item['DetailPageURL']) ? $item['DetailPageURL'] : '',
			'SalesRank'             => isset($item['SalesRank']) ? $item['SalesRank'] : 999999,

			'SmallImage'            => isset($item['SmallImage']['URL']) ? trim( $item['SmallImage']['URL'] ) : '',
			'LargeImage'            => isset($item['LargeImage']['URL']) ? trim( $item['LargeImage']['URL'] ) : '',

			'Offers'                => isset($item['Offers']) ? $item['Offers'] : '',
			'OfferSummary'          => isset($item['OfferSummary']) ? $item['OfferSummary'] : '',
			'EditorialReviews'      => isset($item['EditorialReviews']['EditorialReview']['Content'])
				? $item['EditorialReviews']['EditorialReview']['Content'] : '',
				
			'hasGallery'			=> 'false',
			'country' 				=> '',
		);
		
		// added by jimmy /2017-02-16
		$retProd['country'] = isset($this->amz_settings['country']) ? $this->amz_settings['country'] : '';
		if ( ! empty($retProd['DetailPageURL']) ) {
			$country = $this->the_plugin->get_country_from_url( $retProd['DetailPageURL'] );
			if ( ! empty($country) ) {
				$retProd['country'] = $country;
			}
		}
		//var_dump('<pre>', $retProd['DetailPageURL'], $retProd['country'], '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		
		// try to rebuid the description if is empty
		if( trim($retProd["EditorialReviews"]) == "" ){
			if( isset($item['EditorialReviews']['EditorialReview']) && count($item['EditorialReviews']['EditorialReview']) > 0 ){
				
				$new_description = array();
				foreach ($item['EditorialReviews']['EditorialReview'] as $desc) {
					if( isset($desc['Content']) && isset($desc['Source']) ){
						//$new_description[] = '<h3>' . ( $desc['Source'] ) . ':</h3>';
						$new_description[] = $desc['Content'] . '<br />';
					}
				}
			}
			
			if( isset($new_description) && count($new_description) > 0 ){
				$retProd["EditorialReviews"] = implode( "\n", $new_description );
			}
		}
		
		// Customer Reviews
		$reviewsURL = isset($item['CustomerReviews'], $item['CustomerReviews']['IFrameURL'])
			? $item['CustomerReviews']['IFrameURL'] : '';
		$reviewsURL = trim( $reviewsURL );

		$retProd['CustomerReviewsURL'] = '';
		if ( $reviewsURL != "" ) {
			$retProd['CustomerReviewsURL'] = $reviewsURL;
		}

		// Images
		$retProd['images'] = $this->build_images_data( $item );
		if ( empty($retProd['images']['large']) ) {
			// no images found - if has variations, try to find first image from variations
			$retProd['images'] = $this->get_first_variation_image( $item );
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
		//var_dump('<pre>', $retProd['images'] , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( !empty($retProd['images']['large']) && (count($retProd['images']['large']) > 1) ) {
			$retProd['hasGallery'] = 'true';
		}
		//var_dump('<pre>', $retProd , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $retProd;
	}

	public function build_images_data( $item=array(), $nb_images='all' ) {
		$retProd = array( 'large' => array(), 'small' => array(), 'sizes' => array() );

		//if ( isset($item['LargeImage']['URL']) ) {
		//   $retProd['large'][] = $item['LargeImage']['URL'];
		//}
		//if ( isset($item['SmallImage']['URL']) ) {
		//   $retProd['small'][] = $item['SmallImage']['URL'];
		//}
		$retProd = $this->build_current_image($item, $retProd);
		//var_dump('<pre>', $retProd , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		// get gallery images
		if (isset($item['ImageSets'], $item['ImageSets']['ImageSet']) && count($item['ImageSets']["ImageSet"]) > 0) {
			
			// hack if have only 1 item
			if( isset($item['ImageSets']['ImageSet']['SwatchImage']) ){
				$_tmp = $item['ImageSets']['ImageSet'];
				$item['ImageSets']['ImageSet'] = array();
				$item['ImageSets']['ImageSet'][0] = $_tmp;  
			}

			$count = 0;
			foreach ($item['ImageSets']['ImageSet'] as $key => $value) {
				
				//if( isset($value['LargeImage']['URL']) ){
				//    $retProd['large'][] = $value['LargeImage']['URL'];
				//}
				//if( isset($value['SmallImage']['URL']) ){
				//    $retProd['small'][] = $value['SmallImage']['URL'];
				//}
				$retProd = $this->build_current_image($value, $retProd);
				$count++;
			}
			//var_dump('<pre>', $retProd , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}

		// clean images
		//foreach ($retProd as $key => $val) {
		//	if ( in_array($key, array('large')) ) {
		//		// keep unique images
		//		$retProd["$key"] = @array_unique($retProd["$key"]);
		//		// remove empty array elements!
		//		$retProd["$key"] = @array_filter($retProd["$key"]);
		//	}
		//}
			//var_dump('<pre>', $retProd , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $retProd;
	}
	
	// if product is variation parent, get first variation child image as product image
	public function get_first_variation_image( $retProd ) {

		$images = array( 'large' => array(), 'small' => array(), 'sizes' => array() );

		if ( isset($retProd['Variations'], $retProd['Variations']['Item']) ) {
			$total = $this->the_plugin->get_amazon_variations_nb( $retProd['Variations']['Item'] );
			
			$variations = array();
			if ($total <= 1 || isset($retProd['Variations']['Item']['ASIN'])) { // --fix 2015.03.19
				$variations[] = $retProd['Variations']['Item'];
			} else {
				$variations = (array) $retProd['Variations']['Item'];
			}

			// Loop through the variation
			foreach ($variations as $variation_item) {
				
				$images = $this->build_images_data( $variation_item );
				if ( !empty($images['large']) ) {
					return $images;
				}
			} // end foreach
		}
		return $images;
	}

	private function _build_current_image( $item=array() ) {
		$current = array( 'large' => '', 'small' => '', 'sizes' => array() );

		$key2key = array('SmallImage' => 'small', 'LargeImage' => 'large');
		
		if ( ! is_array($item) || empty($item) ) {
			$item = array();
		}

		foreach ($item as $sizek => $sizev) {
			$sizek 	= (string) $sizek;

			if ( preg_match('/image$/iu', $sizek) ) {
				// large & small
				if ( in_array($sizek, array_keys($key2key)) && isset($sizev['URL']) ) {
					$__ = $key2key["$sizek"];
					$current["$__"] = $sizev['URL'];
				}

				// all sizes
				//var_dump('<pre>', $sizek, $sizev ,'</pre>');
				if ( isset($sizev['URL']) ) {
					$__ = strtolower( str_ireplace('image', '', $sizek) );
					if ( isset($current['sizes']["$__"]) ) continue 1;
					
					$url = isset($sizev['URL']) ? $sizev['URL'] : '';
					
					$width = 0;
					if ( isset($sizev['Width']) ) {
						$width = is_numeric($sizev['Width']) ? (int) $sizev['Width'] : ( isset($sizev['Width']['_']) ? (int) $sizev['Width']['_'] : 0 );
					}
					
					$height = 0;
					if ( isset($sizev['Height']) ) {
						$height = is_numeric($sizev['Height']) ? (int) $sizev['Height'] : ( isset($sizev['Height']['_']) ? (int) $sizev['Height']['_'] : 0 );
					}

					$current['sizes']["$__"] = array(
						'url'		=> $url,
						'width'		=> $width,
						'height'	=> $height,
					);
				}
			}
		}

		if ( !empty($current['large']) && empty($current['small']) ) {
			$current['small'] = $current['large'];
		}
		//var_dump('<pre>',$current ,'</pre>');
		return $current;
	}

	private function build_current_image( $item=array(), $retProd=array() ) {
		$current = $this->_build_current_image( $item );
		//var_dump('<pre>',$current ,'</pre>');

		if ( !isset($current['large']) || empty($current['large']) ) return $retProd;

		//if ( in_array($current['large'], $retProd['large']) ) return $retProd;
		// changed on 2018-jul-26
		if ( in_array($current['large'], $retProd['large']) ) {
			$idx = array_search($current['large'], $retProd['large']);

			// replace with those from ImageSets/ImageSet
			if ( ! isset($retProd['sizes']) || ! is_array($retProd['sizes']) ) {
				$retProd['sizes'] = array();
				$idx = 0;
			}
			$retProd['sizes'][$idx] = $current['sizes'];
			return $retProd;
		}

		$index = count($retProd['large']);

		$retProd['large'][$index] = $current['large'];
		$retProd['small'][$index] = $current['small'];
		$retProd['sizes'][$index] = $current['sizes'];

		return $retProd;
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

		if( $action == 'import_images' ){

			//var_dump('<pre>', $_SESSION, '</pre>'); //echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$product_id = isset($_SESSION["WooZoneLite_test_product_local_id"])
				? $_SESSION["WooZoneLite_test_product_local_id"] : 0;

			if( isset($_SESSION["WooZoneLite_test_product"]) && count($_SESSION["WooZoneLite_test_product"]) > 0 && $product_id ){
				$product = $_SESSION["WooZoneLite_test_product"];

				$this->set_product_images( $product, $product_id, 0, 1 );

				$return = array( 
					'status' => 'valid',
					'log' => "Images added for product: " . $product_id,
					'execution_time' => number_format( microtime(true) - $start, 2),
				);
			}
			
			else{
				$return = array( 
					'status' => 'invalid',
					'log' => 'Unable to add images for the product!'
				);
			}
		}
		
		if( $action == 'insert_product' ){
			if( isset($_SESSION["WooZoneLite_test_product"]) && count($_SESSION["WooZoneLite_test_product"]) > 0 ){
				$product = $_SESSION["WooZoneLite_test_product"];

				$addNewProductStat = $this->the_plugin->addNewProduct( $product, array(
					'import_images' => false,
				));
				$insert_id = (int) $addNewProductStat['insert_id'];
				//var_dump('<pre>', $insert_id , '</pre>');
				if ( $insert_id ) {
					
					$_SESSION["WooZoneLite_test_product"]['local_id'] = $insert_id;
					$_SESSION["WooZoneLite_test_product_local_id"] = $insert_id;

					$return = array( 
						'status' => 'valid',
						'log' => "New product added: " . $insert_id,
						'execution_time' => number_format( microtime(true) - $start, 2),
					);
				}
			}
			
			else{
				$return = array( 
					'status' => 'invalid',
					'log' => 'Unable to create the woocommerce product!'
				);
			}

			//var_dump('<pre>', $_SESSION, '</pre>'); //echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}

		if( $action == 'get_product_data' ){

			$asin = isset($_REQUEST['ASIN']) ? $_REQUEST['ASIN'] : '';
			if( $asin != "" ) {

				$rsp = $this->api_main_request(array(
					'what_func' 			=> 'api_make_request',
					'method'				=> 'lookup',
					'amz_settings'			=> $this->the_plugin->amz_settings,
					'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
					'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
					'requestData'			=> array(
						'asin'					=> $asin,
					),
					'optionalParameters'	=> array(),
					'responseGroup'			=> 'Large,ItemAttributes,Offers,Reviews',
				));
				$product = $rsp['response'];
				
				$respStatus = $this->is_amazon_valid_response( $product );
				if ( $respStatus['status'] != 'valid' ) { // error occured!
					$return = array(
						'status' => 'invalid',
						'msg'	=> 'ASIN [' . $asin . '] - ' . 'Amazon Error: ' . $respStatus['code'] . ' - ' . $respStatus['msg'],
						'log'	=> $product
					);
				}
				//if($product['Items']["Request"]["IsValid"] == "True"){
				else {

					$thisProd = isset($product['Items']['Item']) ? $product['Items']['Item'] : array();
					if (1) {
						// build product data array
						$retProd = array();
						$retProd = $this->build_product_data( $thisProd );

						$return = array( 
							'status' => 'valid',
							'log' => $retProd,
							'execution_time' => number_format( microtime(true) - $start, 2),
						);
						
						// save the product into session, for feature using of it
						$_SESSION["WooZoneLite_test_product"] = $retProd;
					}
				}

			} else {
				$return = array(
					'status' => 'invalid',
					'msg'	=> 'Please provide a valid ASIN code!'
				);
			}

			//var_dump('<pre>', $_SESSION, '</pre>'); //echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}
		
		die( json_encode($return) );   
	}
	
	public function check_amazon( $retType='die', $pms=array() )
	{
		$pms = array_replace_recursive(array(
			'access_keys_id' 		=> 0,
			'extra_msg' 			=> '',
			'extra_msg_pos' 		=> 'top', // top | bottom
		), $pms);

		$status = 'valid';
		$msg = '';
		try {
			// Do a test connection
			$rsp = $this->api_main_request(array(
				'access_keys_id' 		=> isset($pms['access_keys_id']) ? $pms['access_keys_id'] : 0,
				'what_func' 			=> 'api_make_request',
				'method'				=> 'search',
				'amz_settings'			=> $this->the_plugin->amz_settings,
				'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
				'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
				'requestData'			=> array(
					//'category'					=> 'DVD',
					//'page'						=> 1,
					//'keyword'					=> 'Matrix',

					// fix july 2016 - Books works for all countries
					'category'					=> 'Books',
					'page'						=> 1,
					'keyword'					=> 'fantasy',
				),
				//'optionalParameters'	=> array(),
				'responseGroup'			=> 'Images',
				'doGetVariations' 		=> false,
			));
			$tryRequest = $rsp['response'];
			//var_dump('<pre>', $tryRequest , '</pre>'); echo __FILE__ . ":". __LINE__;die . PHP_EOL;
			
			$respStatus = $this->is_amazon_valid_response( $tryRequest );
			if ( $respStatus['status'] != 'valid' ) { // error occured!

				$msg = "Amazon Error: ErrCode = {$respStatus['code']} | ErrMsg = [[ {$respStatus['msg']} ]]";
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
			$msg .= '<p>WooCommerce Amazon Affiliates was able to connect to Amazon with the specified AWS Key Pair and Associate ID</p>';
		}
		// error
		else {
			$msg .= '<p>WooCommerce Amazon Affiliates was not able to connect to Amazon with the specified AWS Key Pair and Associate ID. Please triple-check your AWS Keys and Associate ID.</p>';

			if ( false !== strpos( $msg, 'aws:Client.AWS.InvalidAssociate' ) ) {
				$msg .= '<p><strong>Don\'t panic</strong>, this error is easy to fix, please follow the instructions from ';
				$msg .= 	'<a href="http://support.aa-team.com/knowledgebase-details/198" target="_blank">here</a>.';
				$msg .= '</p>';
			}
			else if ( false !== strpos( $msg, 'aws:Client.RequestThrottled' ) ) {
				$msg .= '<p>';
				$msg .= 'Effective 23-Jan-2019, the request limit for each account is calculated based on revenue performance attributed to calls to the Product Advertising API (PA API) during the last 30 days. <br/> Each account used for Product Advertising API is allowed an initial usage limit of 8640 requests per day (TPD) subject to a maximum of 1 request per second (TPS). Your account will receive an additional 1 TPD for every 5 cents or 1 TPS (up to a maximum of 10) for every $4320 of shipped item revenue generated via the use of Product Advertising API for shipments in the last 30 days. You can check to see sales that have been attributed to Product Advertising API through generating a Link Type Performance report through the Associates Central reports tool. <br/> If you are trying to submit requests that exceed the maximum request for your account (TPD limit), or if your access has been revoked you will receive a 503 error message from Product Advertising API. <br/><br/>';
				$msg .= '<a href="https://docs.aws.amazon.com/AWSECommerceService/latest/DG/TroubleshootingApplications.html" target="_blank" style="color:#fff;">Read More</a>.';
				$msg .= '</p>';
			}
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
	
	public function getAmazonCategs()
	{
		global $wpdb;
		
		$country = $this->the_plugin->get_country2mainaffid( $this->amz_settings['country'] );

		$table = $wpdb->prefix . "amz_locale_reference";
		$query = "SELECT * FROM $table WHERE 1=1 AND country = %s ORDER BY department ASC, searchIndex ASC;";
		$query = $wpdb->prepare( $query, $country );
		$sql_search_index_by_country = $wpdb->get_results( $query );

		$categs = array();
		if( is_array( $sql_search_index_by_country ) && ! empty($sql_search_index_by_country) ) {
			foreach( $sql_search_index_by_country as $search_index ) {

				$key = $search_index->searchIndex;
				$key = trim( $key );
				$key = 'all' == strtolower($key) ? 'AllCategories' : $key;

				$categ_nicename = $search_index->department;
				$categ_nicename = trim( $categ_nicename );
				$categ_nicename = '' == $categ_nicename ? $key : $categ_nicename;

				$nodeid = $search_index->browseNode;

				if( ( $nodeid <= 0 ) && ( $key != 'AllCategories' ) ) {
					continue 1;
				}

				$nodeid = 'AllCategories' == $key ? 'all' : $nodeid;

				$item_ = array(
					'searchIndex' 		=> $key,
					'department' 		=> $categ_nicename,
					'browseNode' 		=> $nodeid,
				);

				// All should be first item
				if ( 'AllCategories' == $key ) {
					$categs = array( $key => $item_ ) + $categs;
				}
				else {
					$categs[$key] = $item_;
				}
			}
		}
		//die( var_dump( "<pre>", $categs , "<pre>" ) . PHP_EOL .  __FILE__ . ":" . __LINE__  );  
		return $categs;  
	}

	public function getAmazonItemSearchParameters()
	{
		global $wpdb;

		$country = $this->the_plugin->get_country2mainaffid( $this->amz_settings['country'] );

		$table = $wpdb->prefix . "amz_locale_reference";
		$query = "SELECT * FROM $table WHERE 1=1 AND country = %s ORDER BY searchIndex ASC;";
		$query = $wpdb->prepare( $query, $country );
		$sql_search_index_by_country = $wpdb->get_results( $query );

		$categs = array();
		if( is_array( $sql_search_index_by_country ) && ! empty($sql_search_index_by_country) ) {
			foreach( $sql_search_index_by_country as $search_index ) {

				$key = $search_index->searchIndex;
				$key = trim( $key );
				if( $key != 'All' ) {
					if( strpos( $search_index->itemSearchParams, "BrowseNode" ) == false ){
						$search_index->itemSearchParams .= "#BrowseNode";
					}
					
				}
				$key = 'all' == strtolower($key) ? 'AllCategories' : $key;

				if ( 'newapi' === $this->the_plugin->amzapi ) {
					//[new in api v5]
					$search_index->itemSearchParams .= "#Condition#Brand#MinReviewsRating#MinSavingPercent";
				}

				$categs[$key] = explode( '#', $search_index->itemSearchParams );
			}
		}
		//var_dump('<pre>', $categs , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $categs;  
	}

	public function getAmazonSortValues()
	{
		global $wpdb;

		$country = $this->the_plugin->get_country2mainaffid( $this->amz_settings['country'] );

		$table = $wpdb->prefix . "amz_locale_reference";
		$query = "SELECT * FROM $table WHERE 1=1 AND country = %s ORDER BY searchIndex ASC;";
		$query = $wpdb->prepare( $query, $country );
		$sql_search_index_by_country = $wpdb->get_results( $query );

		$categs = array();
		if( is_array( $sql_search_index_by_country ) && ! empty($sql_search_index_by_country) ) {
			foreach( $sql_search_index_by_country as $search_index ) {

				$key = $search_index->searchIndex;
				$key = trim( $key );
				$key = 'all' == strtolower($key) ? 'AllCategories' : $key;

				if ( 'newapi' === $this->the_plugin->amzapi ) {
					//[new in api v5]
					$search_index->sortValues = implode('#', array(
						'AvgCustomerReviews',
						'Featured',
						'NewestArrivals',
						'PriceHighToLow',
						'PriceLowToHigh',
						'Relevance',
					));
				}

				$categs[$key] = explode( '#', $search_index->sortValues );
			}
		}
		//die( var_dump( "<pre>", $categs , "<pre>" ) . PHP_EOL .  __FILE__ . ":" . __LINE__  );  
		return $categs; 
	}

	public function getBrowseNodesList( $nodeid=0 ) {

		$provider = self::$provider;
		if( !is_numeric($nodeid) ){
			return array(
				'status'    => 'invalid',
				'msg'       => 'The $nodeid is not numeric: ' . $nodeid
			);
		}

		$prefix_opt = '';

		$optname = $this->the_plugin->alias . $prefix_opt . '_node_children_' . $nodeid;
		$nodes = get_option( $optname, false );

		// unable to find the node into cache, get live data
		if ( !isset($nodes) || $nodes == false || count($nodes) == 0 ) {

			//$nodes = $this->aaAmazonWS->setBrowseNodeIds( $nodeid )->browseNodeLookup();
			$nodes = $this->browseNodeLookup( $nodeid );
			//var_dump('<pre>', $nodes , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			if ( isset($nodes['BrowseNodes']) && count($nodes['BrowseNodes']) > 0 ) {
				if ( isset($nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode']) && count($nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode']) > 0 ) {

					if ( !isset($nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode'][1]['BrowseNodeId']) ) {
						$nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode'] = array(
							$nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode']
						);
					}
					
					if ( count($nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode']) > 0 ) {
						$nodes = $nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode'];
						update_option( $optname, $nodes );
					}
				}
			}
			else {
				$nodes = false;
			}
		}

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
			'responseGroup'			=> 'BrowseNodeInfo',
		));
		$ret = $rsp['response'];
		return $ret;
		//return $rsp;
	}
	
	public function updateProductReviews( $post_id=0 )
	{
		$reviewsURL = '';

		// get product ASIN by post_id
		$asin = get_post_meta( $post_id, '_amzASIN', true );

		$rsp = $this->api_main_request( array(
			'what_func' 			=> 'api_make_request',
			'method'				=> 'lookup',
			'amz_settings'			=> $this->the_plugin->amz_settings,
			'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
			'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
			'requestData'			=> array(
				'asin'					=> $asin,
			),
			'optionalParameters'	=> array(),
			'responseGroup'			=> 'Reviews',
		));
		$product = $rsp['response'];

		$respStatus = $this->is_amazon_valid_response( $product );
		if ( $respStatus['status'] != 'valid' ) { // error occured!
			$msg = 'ASIN [' . $asin . '] - ' . 'Amazon Error: ' . $respStatus['code'] . ' - ' . $respStatus['msg'];
			return $reviewsURL;
		}

		//if($product['Items']["Request"]["IsValid"] == "True"){
		if (1) {
			$thisProd = isset($product['Items']['Item']) ? $product['Items']['Item'] : array();

			if ( ! empty($thisProd) ) {

				$reviewsURL = isset($thisProd['CustomerReviews'], $thisProd['CustomerReviews']['IFrameURL'])
					? $thisProd['CustomerReviews']['IFrameURL'] : '';
				$reviewsURL = trim( $reviewsURL );

				if ( $reviewsURL != "" ) {
					$tab_data = array();
					$tab_data[] = array(
						'id' => 'amzAff-customer-review',
						'content' => '<iframe src="' . $reviewsURL . '" width="100%" height="450" frameborder="0"></iframe>'
					);

					update_post_meta( $post_id, 'amzaff_woo_product_tabs', $tab_data );
				}
			}
		}

		return $reviewsURL;
	}
	
	// Get Product From WebService
	public function getProductDataFromAmazon( $retType='die', $pms=array() ) {
		// require_once( $this->the_plugin->cfg['paths']["scripts_dir_path"] . '/shutdown-scheduler/shutdown-scheduler.php' );
		// $scheduler = new aateamShutdownScheduler();

		$this->the_plugin->timer_start(); // Start Timer

		//$cross_selling = (isset($this->amz_settings["cross_selling"]) && $this->amz_settings["cross_selling"] == 'yes' ? true : false);

		$_msg = array();
		$ret = array(
			'status'						=> 'invalid',
			'msg'							=> '',
			'product_data'					=> array(),
			'show_download_lightbox'		=> false,
			'download_lightbox_html'		=> '',
			'product_id'					=> 0,
			'do_import'						=> true,
		);

		$provider_status = $this->the_plugin->provider_action_controller( 'can_import_products', self::$provider, array() );
		if ( 'invalid' == $provider_status['status'] ) {
			$ret = array_merge($ret, array(
				'do_import'		=> false,
				'msg'			=> self::MSG_SEP . $provider_status['msg'],
			));
			if ( $retType == 'return' ) { return $ret; }
			else { die( json_encode( $ret ) ); }
		}
		
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
					'responseGroup'			=> 'Large,ItemAttributes,OfferFull,Offers,Variations,Reviews,PromotionSummary,SalesRank',
				));
				$product = $rsp['response'];

				$respStatus = $this->is_amazon_valid_response( $product );
				if ( $respStatus['status'] != 'valid' ) { // error occured!
					
					$_msg[] = 'Invalid '.self::$provider.' response ( ' . $respStatus['code'] . ' - ' . $respStatus['msg'] . ' )';
					
					$ret = array_merge($ret, array('msg' => implode('<br />', $_msg)));
					if ( $retType == 'return' ) { return $ret; }
					else { die( json_encode( $ret ) ); }
			
				} else { // success!
	
					$thisProd = $product['Items']['Item'];
					//var_dump('<pre>', $thisProd , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
					if ( 1 ) {

						// build product data array
						$retProd = array(); 
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

				if ( !empty($import_type) && $import_type=='default' ) {
					if ( !$this->the_plugin->is_remote_images ) {
						$ret = array_merge($ret, array(
							'show_download_lightbox'     => true,
							'download_lightbox_html'     => $this->the_plugin->download_asset_lightbox( $insert_id, $from_module, 'html' ),
						));
					}
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
		
		// Count the top browsenodes
		$topBrowseNodeCounter = 0;

		if ( !isset($browseNodes['BrowseNode']) ) {
			// Delete the product_cat_children
			// This is to force the creation of a fresh product_cat_children
			//delete_option( 'product_cat_children' );
		
			return array();
		}

		// Check if we have multiple top browseNode
		if( is_array( $browseNodes['BrowseNode'] ) )
		{
			// check if is has only one key
			if( isset($browseNodes["BrowseNode"]["BrowseNodeId"]) && trim($browseNodes["BrowseNode"]["BrowseNodeId"]) != "" ){
				$_browseNodes = $browseNodes["BrowseNode"];
				$browseNodes = array();
				$browseNodes['BrowseNode'][0] = $_browseNodes;
				unset($_browseNodes);
			}

			foreach( $browseNodes['BrowseNode'] as $browseNode )
			{
				// Create a clone
				$currentNode = $browseNode;

				// Track the child layer
				$childLayer = 0;

				// Inifinite loop, since we don't know how many ancestral levels
				while( true )
				{
					$validCat = true;
					
					// Replace html entities
					$dmCatName = str_replace( '&', 'and', $currentNode['Name'] );
					$dmCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
					
					$dmCatSlug_id = '';
					if ( is_object($currentNode) && isset($currentNode->BrowseNodeId) )
						$dmCatSlug_id = ($currentNode->BrowseNodeId);
					else if ( is_array($currentNode) && isset($currentNode['BrowseNodeId']) )
						$dmCatSlug_id = ($currentNode['BrowseNodeId']);

					// $dmCatSlug = ( !empty($dmCatSlug_id) ? $dmCatSlug_id . '-' . $dmCatSlug : $dmCatSlug );

					$dmTempCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
					
					if( $dmTempCatSlug == 'departments' ) $validCat = false;
					if( $dmTempCatSlug == 'featured-categories' ) $validCat = false;
					if( $dmTempCatSlug == 'categories' ) $validCat = false;
					if( $dmTempCatSlug == 'products' ) $validCat = false;
					if( $dmTempCatSlug == 'all-products') $validCat = false;

					// Check if we will make the cat
					if( $validCat ) {
						$categories[0][] = array(
							'name' => $dmCatName,
							'slug' => $dmCatSlug
						);
					}

					// Check if the current node has a parent
					if( isset($currentNode['Ancestors']['BrowseNode']['Name']) )
					{
						// Set the next Ancestor as the current node
						$currentNode = $currentNode['Ancestors']['BrowseNode'];
						$childLayer++;
						continue;
					}
					else
					{
						// There's no more ancestors beyond this
						break;
					}
				} // end infinite while
				
				// Increment the tracker
				$topBrowseNodeCounter++;
			} // end foreach
		}
		else
		{
			// Handle single branch browsenode
			
			// Create a clone
			$currentNode = isset($browseNodes['BrowseNode']) ? $browseNodes['BrowseNode'] : array();
			
			// Inifinite loop, since we don't know how many ancestral levels
			while (true) 
			{
				// Always true unless proven
				$validCat = true;
				
				// Replace html entities
				$dmCatName = str_replace( '&', 'and', $currentNode['Name'] );
				$dmCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
				$dmCatSlug_id = $currentNode['BrowseNodeId'];
				// $dmCatSlug = ( !empty($dmCatSlug_id) ? $dmCatSlug_id . '-' . $dmCatSlug : $dmCatSlug );  
				
				$dmTempCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
				
				if( $dmTempCatSlug == 'departments' ) $validCat = false;
				if( $dmTempCatSlug == 'featured-categories' ) $validCat = false;
				if( $dmTempCatSlug == 'categories' ) $validCat = false;
				if( $dmTempCatSlug == 'products' ) $validCat = false;
				if( $dmTempCatSlug == 'all-products') $validCat = false;
				
				// Check if we will make the cat
				if( $validCat ) {
					$categories[0][] = array(
						'name' => $dmCatName,
						'slug' => $dmCatSlug
					);
				}

				// Check if the current node has a parent
				if (isset($currentNode['Ancestors']['BrowseNode']['Name'])) 
				{
					// Set the next Ancestor as the current node
					$currentNode = $currentNode['Ancestors']['BrowseNode'];
					continue;
				} 
				else 
				{
					// There's no more ancestors beyond this
					break;
				}
			} // end infinite while
				
		} // end if browsenode is an array
		
		// Tracker
		$catCounter = 0;

		// Make the parent at the top
		foreach( $categories as $category )
		{
			$categories[$catCounter] = array_reverse( $category );
			$catCounter++;
		}
		
		// Import only parent category from Amazon
		if( isset( $this->amz_settings["create_only_parent_category"] )
			&& $this->amz_settings["create_only_parent_category"] != ''
			&& $this->amz_settings["create_only_parent_category"] == 'yes'
		) {
			$categories = array( array( $categories[0][0] ) );
		}

		// current top browsenode
		$categoryCounter = 0;

		// top browsenode foreach
		foreach ( $categories as $category ) {

			// The current node
			$nodeCounter = 0;

			// Loop through the array of the current browsenode
			foreach ( $category as $node ) {

				// Check if we're at parent
				if ( $nodeCounter === 0 ) {
					// Check if term exists
					$checkTerm = term_exists( str_replace( '&', 'and', $node['slug'] ), $wooTaxonomy );
					if ( empty( $checkTerm ) ) {
						// Create the new category
						$newCat = wp_insert_term( $node['name'], $wooTaxonomy, array( 'slug' => $node['slug'] ) );

						// Add the created category in the createdCategories
						// Only run when the $newCat is an error
						if( gettype($newCat) != 'object' ) {
							$createdCategories[] = $newCat['term_id'];
						}
					}
					else {
						// if term already exists add it on the createdCats
						$createdCategories[] = $checkTerm['term_id'];
					}
				}
				else {
					// The parent of the current node
					$parentNode = $categories[$categoryCounter][$nodeCounter - 1];
					// Get the term id of the parent
					$parent = term_exists( str_replace( '&', 'and', $parentNode['slug'] ), $wooTaxonomy );
					
					// Check if the category exists on the parent
					$checkTerm = term_exists( str_replace( '&', 'and', $node['slug'] ), $wooTaxonomy );
					
					if ( empty( $checkTerm ) ) {
						$newCat = wp_insert_term(
							$node['name'],
							$wooTaxonomy,
							array( 'slug' => $node['slug'], 'parent' => $parent['term_id'] )
						);
						
						// Add the created category in the createdCategories
						$createdCategories[] = $newCat['term_id'];
					}
					else {
						$createdCategories[] = $checkTerm['term_id'];
					}
				}
				
				$nodeCounter++;
			}
			// End Loop through the array of the current browsenode
	
			$categoryCounter++;
		}
		// End top browsenode foreach
		
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
 
		// convert Amazon attributes into woocommerce attributes
		$_product_attributes = array();
		$position = 0;
		
		$allowedAttributes = 'all';

		if ( isset($this->amz_settings['selected_attributes'])
			&& !empty($this->amz_settings['selected_attributes'])
			&& is_array($this->amz_settings['selected_attributes'])
		) {
			$allowedAttributes_ = $this->the_plugin->clean_multiselect( $this->amz_settings['selected_attributes'] );
			if ( ! empty($allowedAttributes_) ) {
				$allowedAttributes = $allowedAttributes_;
			}
		}
			
		foreach( $itemAttributes as $key => $value )
		{ 
			if (!is_object($value)) 
			{
				if ( is_array($allowedAttributes) ) {
					if ( !in_array($key, $allowedAttributes) ) {
						continue 1;
					}
				}
				
				// Apparel size hack
				if($key === 'ClothingSize') {
					$key = 'Size';
				}
				// don't add list price,Feature,Title into attributes
				if( in_array($key, array('ListPrice', 'Feature', 'Title') ) ) continue;
				
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
				
				$this->add_attribute( $post_id, $key, $value );
			}
		}
		
		// update product attribute
		update_post_meta($post_id, '_product_attributes', $_product_attributes);
		
		$this->the_plugin->get_ws_object( 'generic' )->attrclean_clean_all( 'array' ); // delete duplicate attributes
		
		// refresh attribute cache
		//$dmtransient_name = 'wc_attribute_taxonomies';
		//$dmattribute_taxonomies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies");
		//set_transient($dmtransient_name, $dmattribute_taxonomies);
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

				if (is_string($attribute_value)) {
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
							/*$wpdb->insert(
								$wpdb->terms, array(
									'name' => $name,
									'slug' => $slug
								)
							);*/

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
							/*$wpdb->insert(
								$wpdb->term_taxonomy, array(
									'term_id' => $term_id,
									'taxonomy' => $taxonomy
								)
							);*/
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
						/*$wpdb->insert(
							$wpdb->term_taxonomy, array(
								'term_id' => $term_id,
								'taxonomy' => $taxonomy
							)
						);*/
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
			/*$terms = get_terms($taxonomy, array('hide_empty' => true));
			if( !is_wp_error( $terms ) ) {
				foreach ($terms as $term) {
					$attribute_values[] = $term->name;
				}
			} else {
				$error_string = $terms->get_error_message();
				var_dump('<pre>',$error_string,'</pre>');  
			}*/
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
		
		// wp_term_relationships (object_id to term_taxonomy_id)
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

		$product_price = $this->get_product_price(
			$thisProd,
			null,
			array() //array( 'do_update' => false )
		);

		if (
			empty($product_price['_regular_price'])
			|| (float) $product_price['_regular_price'] <= 0.00
		) {
			return true;
		}
		return false;

		// $multiply_factor = $this->amz_settings['country'] == 'co.jp' ? 1 : 0.01;

		// $price_setup = isset($this->amz_settings["price_setup"]) && 'amazon_or_sellers' == $this->amz_settings["price_setup"] ? 'amazon_or_sellers' : 'only_amazon';
		
		// //:: price blocks
		// $blockMain = array(
		// 	'ListPrice' => isset($thisProd['ItemAttributes']['ListPrice'])
		// 		? $thisProd['ItemAttributes']['ListPrice'] : array(),

		// 	'OfferListingPrice' => isset($thisProd['Offers']['Offer']['OfferListing']['Price'])
		// 		? $thisProd['Offers']['Offer']['OfferListing']['Price'] : array(),

		// 	'OfferSummaryPrice' => isset($thisProd['OfferSummary']['LowestNewPrice'])
		// 		? $thisProd['OfferSummary']['LowestNewPrice'] : array(),

		// 	'VarSummaryPrice' => isset($thisProd['VariationSummary']['LowestPrice'])
		// 		? $thisProd['VariationSummary']['LowestPrice'] : array(),
		// );

		// foreach ( $blockMain as $kk => $vv ) {

		// 	$AmountDef = in_array( $kk, array(
		// 		'OfferSummaryPrice'
		// 	) ) ? false : '';
		// 	$Amount = isset($vv['Amount']) ? $vv['Amount'] * $multiply_factor : $AmountDef;
		// 	$offerBlock["$kk"] = $Amount;
		// }

		// if (
		// 	'amazon_or_sellers' === $price_setup
		// 	 && false !== $offerBlock['OfferSummaryPrice']
		// ) {
		// 	$offerBlock['OfferListingPrice'] = $offerBlock['OfferSummaryPrice'];
		// }

		// //:: init
		// $prodprice = array('regular_price' => '');

		// //:: regular/
		// $prodprice['regular_price'] = $offerBlock['ListPrice'];

		// //:: regular/ if we don't have a regular price or lowest offer price from offer is greater than current list price
		// if ( 
		// 	( 0.00 === (float) $offerBlock['ListPrice'] )
		// 	|| ( $offerBlock['OfferListingPrice'] > $offerBlock['ListPrice'] )
		// ) {
		// 	$prodprice['regular_price'] = $offerBlock['OfferListingPrice'];
		// }

		// //:: regular/ if still don't have any regular price, try to get from VariationSummary (ex: Apparel category)
		// if ( 0.00 === (float) $prodprice['regular_price'] ) {
		// 	$prodprice['regular_price'] = $offerBlock['VarSummaryPrice'];
		// }

		// if (
		// 	empty($prodprice['regular_price'])
		// 	|| (float) $prodprice['regular_price'] <= 0.00
		// ) {
		// 	return true;
		// }
		// return false;
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

		//:: if any of regular | sale price set to auto => no product price syncronization!
		if ( $do_update ) {
			$priceStatus = $this->productPriceGetRegularSaleStatus( $post_id );
			if ( $priceStatus['regular'] == 'selected' || $priceStatus['sale'] == 'selected' ) {
				$do_update = false; // just don't update the post meta in this case!
			}
		}

		//:: get current product meta, update the values of prices and update it back
		if ( $do_update ) {
			$product_meta = get_post_meta( $post_id, '_product_meta', true );
			$product_meta = ! is_array($product_meta) ? array('product' => array()) : $product_meta;
		}
		else {
			$product_meta = array('product' => array());
		}

		$country = $this->amz_settings['country'];
		$multiply_factor = 'co.jp' === $country ? 1 : 0.01;
		
		$price_setup = isset($this->amz_settings["price_setup"]) && 'amazon_or_sellers' == $this->amz_settings["price_setup"] ? 'amazon_or_sellers' : 'only_amazon';
		
		//:: price blocks
		$blockMain = array(
			'ListPrice' => isset($thisProd['ItemAttributes']['ListPrice'])
				? $thisProd['ItemAttributes']['ListPrice'] : array(),

			'OfferListingPrice' => isset($thisProd['Offers']['Offer']['OfferListing']['Price'])
				? $thisProd['Offers']['Offer']['OfferListing']['Price'] : array(),

			'OfferSummaryPrice' => isset($thisProd['OfferSummary']['LowestNewPrice'])
				? $thisProd['OfferSummary']['LowestNewPrice'] : array(),

			'OfferListingSalePrice' => isset($thisProd['Offers']['Offer']['OfferListing']['SalePrice'])
				? $thisProd['Offers']['Offer']['OfferListing']['SalePrice'] : array(),

			'VarSummaryPrice' => isset($thisProd['VariationSummary']['LowestPrice'])
				? $thisProd['VariationSummary']['LowestPrice'] : array(),

			'VarSummarySalePrice' => isset($thisProd['VariationSummary']['LowestSalePrice'])
				? $thisProd['VariationSummary']['LowestSalePrice'] : array(),
		);

		foreach ( $blockMain as $kk => $vv ) {

			$AmountDef = in_array( $kk, array(
				'OfferSummaryPrice', 'OfferListingSalePrice', 'VarSummarySalePrice'
			) ) ? false : '';
			$Amount = isset($vv['Amount']) ? $vv['Amount'] * $multiply_factor : $AmountDef;
			$offerBlock["$kk"] = $Amount;

			$Currency = isset($vv['CurrencyCode']) ? $vv['CurrencyCode'] : '';
			$currencyBlock["$kk"] = $Currency;
		}

		if (
			'amazon_or_sellers' === $price_setup
			 && false !== $offerBlock['OfferSummaryPrice']
		) {
			$offerBlock['OfferListingPrice'] = $offerBlock['OfferSummaryPrice'];
			$currencyBlock['OfferListingPrice'] = $currencyBlock['OfferSummaryPrice'];
		}

		//:: regular/
		$product_meta['product']['regular_price'] = $offerBlock['ListPrice'];
		$product_meta['product']['currency'] = $currencyBlock['ListPrice'];

		//:: regular/ if we don't have a regular price or lowest offer price from offer is greater than current list price
		if ( 
			( 0.00 === (float) $offerBlock['ListPrice'] )
			|| ( $offerBlock['OfferListingPrice'] > $offerBlock['ListPrice'] )
		) {
			$product_meta['product']['regular_price'] = $offerBlock['OfferListingPrice'];
			$product_meta['product']['currency'] = $currencyBlock['OfferListingPrice'];
		}

		//:: regular/ if still don't have any regular price, try to get from VariationSummary (ex: Apparel category)
		if ( 0.00 === (float) $product_meta['product']['regular_price'] ) {
			$product_meta['product']['regular_price'] = $offerBlock['VarSummaryPrice'];
			$product_meta['product']['currency'] = $currencyBlock['VarSummaryPrice'];
		}

		//:: sale/ from Offers or OfferSummary
		$product_meta['product']['sales_price'] = $offerBlock['OfferListingPrice']; 
		// if offer price is higher than regular price, delete the offer
		if ( $offerBlock['OfferListingPrice'] >= $product_meta['product']['regular_price'] ) {
			unset($product_meta['product']['sales_price']);
		}

		//:: sale/ from Offers or OfferSummary - for variation child
		if (
			'amazon_or_sellers' === $price_setup
			|| ! isset($product_meta['product']['sales_price'])
			|| empty($product_meta['product']['sales_price'])
		) {
			if ( false !== $offerBlock['OfferListingSalePrice'] ) {

				$product_meta['product']['sales_price'] = $offerBlock['OfferListingSalePrice']; 
				// if offer price is higher than regular price, delete the offer
				if ( $offerBlock['OfferListingSalePrice'] >= $product_meta['product']['regular_price'] ) {
					unset($product_meta['product']['sales_price']);
				}
			}
		}

		//:: sale/ from VariationSummary (ex: Apparel category)
		if (
			! isset($product_meta['product']['sales_price'])
			|| empty($product_meta['product']['sales_price'])
		) {
			if ( false !== $offerBlock['VarSummarySalePrice'] ) {

				$product_meta['product']['sales_price'] = $offerBlock['VarSummarySalePrice']; 
				// if offer price is higher than regular price, delete the offer
				if ( $offerBlock['VarSummarySalePrice'] >= $product_meta['product']['regular_price'] ) {
					unset($product_meta['product']['sales_price']);
				}
			}
		}

		//:: set product price metas!
		$ret['_currency'] = $product_meta['product']['currency'];
		if (
			isset($product_meta['product']['sales_price'])
			&& !empty($product_meta['product']['sales_price'])
		) {
			if ( $do_update ) {
				//update_post_meta($post_id, '_sale_price', $product_meta['product']['sales_price']);

				$this->productPriceSetRegularSaleMeta($post_id, 'sale', array(
					'auto' => number_format( (float)($product_meta['product']['sales_price']), 2, '.', '')
				));
			}
			$ret['_sale_price'] = $product_meta['product']['sales_price'];
		}
		// new sale price is 0
		else {
			if ( $do_update ) {
				//update_post_meta($post_id, '_sale_price', '');

				$this->productPriceSetRegularSaleMeta($post_id, 'sale', array(
					'auto' => ''
				));
			}
			$ret['_sale_price'] = '';
		}

		$current_time = time();

		$ret['_price_update_date'] = $current_time;
		$ret['_regular_price'] = $product_meta['product']['regular_price'];

		$ret['_price'] = $product_meta['product']['regular_price'];
		if (
			isset($product_meta['product']['sales_price'])
			&& '' !== trim($product_meta['product']['sales_price'])
		) {
			$ret['_price'] = $product_meta['product']['sales_price'];
		}

		if ( $do_update ) {
			update_post_meta($post_id, '_price_update_date', $current_time);
			update_post_meta($post_id, '_regular_price', $ret['_regular_price']);
			
			$this->productPriceSetRegularSaleMeta($post_id, 'regular', array(
				'auto' => number_format((float)($ret['_regular_price']), 2, '.', '')
			));

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
			? $this->amz_settings['product_variation'] : 'yes_2';
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
		if ( ! isset($retProd['Variations'], $retProd['Variations']['Item'])
			|| empty($retProd['Variations']['Item'])
			|| ! is_array($retProd['Variations']['Item'])
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
		if ( count($retProd['Variations']['VariationDimensions']['VariationDimension']) == 1 ) {
			$VariationDimensions[$retProd['Variations']['VariationDimensions']['VariationDimension']] = array();
		}
		else {
			// Check if VariationDimension is given
			if ( count($retProd['Variations']['VariationDimensions']['VariationDimension']) > 0 ) {
				foreach ($retProd['Variations']['VariationDimensions']['VariationDimension'] as $dim) {
					$VariationDimensions[$dim] = array();
				}
			}
		}

		//$retProd['Variations']['TotalVariations']
		$total = $this->the_plugin->get_amazon_variations_nb( $retProd['Variations']['Item'] );
		$ret['nb_found'] = $total;

		$variations = array();
		if ($total <= 1 || isset($retProd['Variations']['Item']['ASIN'])) {
			$variations[] = $retProd['Variations']['Item'];
		} else {
			$variations = (array) $retProd['Variations']['Item'];
		}

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

			if ( is_array($variation_item) ) {
				$variation_item['country'] = $retProd['country'];
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
		$tempProdAttr = ! empty($tempProdAttr) && is_array($tempProdAttr) ? $tempProdAttr : array();

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

		if ( ! $this->the_plugin->import_product_variation_offerlistingid_missing ) {

			// verify if : amazon missing offerlistingid product!
			$prod_has_offerlistingid = $this->productHasOfferlistingid( array(
				'verify_variations' => false,
				'thisProd' 	=> $variation_item,
				'post_id' 	=> 0,
			));

			if ( !$prod_has_offerlistingid ) {
				// status messages
				$msg = sprintf( '- variation %s offerListingId is missing, so it is skipped!', $variation_asin );
				$this->the_plugin->opStatusMsgSet(array(
					'msg'       => $msg,
				));
		
				return array_replace_recursive($ret, array(
					'msg' 					=> $msg,
				));
			}
		}

		$variation_post = get_post( $parent_id, ARRAY_A );

		$variation_item__ = array_merge_recursive($variation_item, array(
			'ws' => 'amazon',
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
				$child_asin = $this->the_plugin->prodid_set($child_asin, 'amazon', 'add');
				$desc_used = array(
					'child_asin' => isset($variation_item['ASIN']) ? $variation_item['ASIN'] : '',
				);
			}
		}
		if ( !empty($excerpt) ) {
			$__post_content = $variation_post['post_excerpt'];
			$__post_content = trim( $__post_content );

			if ( $__post_content == '' ) {
				$args_update['post_excerpt'] = $excerpt;

				//$child_asin = isset($variation_item['ASIN']) ? $variation_item['ASIN'] : '';
				//$child_asin = $this->the_plugin->prodid_set($child_asin, 'amazon', 'add');
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

		// :: insert variation child
		//$variation_post['post_title'] = isset($variation_item['ItemAttributes']['Title']) ? $variation_item['ItemAttributes']['Title'] : '';
		if ( isset($variation_item['ItemAttributes']['Title']) ) {
			$variation_post['post_title'] = $variation_item['ItemAttributes']['Title'];
		}
		$variation_post['post_content'] = $desc;
		$variation_post['post_excerpt'] = $excerpt;
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

		// variation images
		$images = array();
		$images['Title'] = isset($variation_item['ItemAttributes']['Title']) ? $variation_item['ItemAttributes']['Title'] : uniqid();
		$images['images'] = $this->build_images_data( $variation_item );

		$this->set_product_images( $images, $variation_post_id, $parent_id );

		// set the product price
		$this->get_product_price(
			$variation_item,
			$variation_post_id,
			array( 'do_update' => true )
		);
		
		// than update the metapost
		$this->set_product_meta_options( $variation_item, $variation_post_id, true );
		 
		// Compile all the possible variation dimensions         
		if(is_array($variation_item['VariationAttributes']['VariationAttribute']) && isset($variation_item['VariationAttributes']['VariationAttribute'][0]['Name'])) {
			
			foreach ($variation_item['VariationAttributes']['VariationAttribute'] as $va) {

				// 2018-march-23 fix
				if ( isset($va['Value']) ) {
					if ( '0' === (string) $va['Value'] ) {
						$va['Value'] = 'zero';
					}
				}

				if ( isset($va['Value']) && !empty($va['Value']) ) {
					// Clean
					$va['Value'] = $this->the_plugin->cleanValue( $va['Value'] );

					$this->add_attribute( $parent_id, $va['Name'], $va['Value'] );

					$curarr = $VariationDimensions[$va['Name']];
					$curarr[$va['Value']] = $va['Value'];

					$VariationDimensions[$va['Name']] = $curarr;
			
					$dimension_name = $this->the_plugin->cleanTaxonomyName(strtolower($va['Name']));
					update_post_meta($variation_post_id, 'attribute_' . $dimension_name, sanitize_title($va['Value']));
				}  
			}
		} else {
			$var_item = $variation_item['VariationAttributes']['VariationAttribute'];
			$dmName = isset($var_item['Name']) ? $var_item['Name'] : '';
			$dmValue = isset($var_item['Value']) ? $var_item['Value'] : '';

			// 2018-march-23 fix
			if ( '0' === (string) $dmValue ) {
				$dmValue = 'zero';
			}
			
			if ( !empty($dmValue) ) {
				// Clean
				$dmValue = $this->the_plugin->cleanValue( $dmValue );

				$this->add_attribute( $parent_id, $dmName, $dmValue );
					
				$curarr = $VariationDimensions[$dmName];
				$curarr[$dmValue] = $dmValue;
				$VariationDimensions[$dmName] = $curarr;
			
				$dimension_name = $this->the_plugin->cleanTaxonomyName(strtolower($dmName));
				update_post_meta($variation_post_id, 'attribute_' . $dimension_name, sanitize_title($dmValue));
			}
		}
			
		// refresh attribute cache
		$dmtransient_name = 'wc_attribute_taxonomies';
		$dmattribute_taxonomies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies");
		set_transient($dmtransient_name, $dmattribute_taxonomies);
		
		// status messages
		$msg = sprintf( '- variation %s inserted with ID: ' . $variation_post_id, $variation_asin );
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
	public function set_product_images( $retProd, $post_id, $parent_id=0, $number_of_images=2 )
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
				'msg'       => sprintf( $status . ': no images found (number of images setting in config: %s).', $_max_nb_setting ),
			));
		}
		$ret['nb_found'] = count($retProd["images"]['large']);

		
		if ( (int) $number_of_images > 0 ) {
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
					'post_id' 		=> $post_id, 
					'post_parent' 	=> $parent_id,
					'title' 		=> isset($retProd["Title"]) ? $retProd["Title"] : 'untitled',
					'type' 			=> (int) $parent_id > 0 ? 'variation' : 'post',
					'nb_assets'		=> count($retProd["images"]['large'])
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
						'post_id' 		=> $post_id,
						'asset' 		=> $value,
						'thumb' 		=> $thumb,
						'date_added'	=> date( "Y-m-d H:i:s" ),
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
		// update the post metas
		update_post_meta($post_id, '_amzASIN', $retProd['ASIN']);
		update_post_meta($post_id, '_visibility', 'visible');
		update_post_meta($post_id, '_downloadable', 'no');
		update_post_meta($post_id, '_virtual', 'no');
		update_post_meta($post_id, '_stock_status', 'instock');
		update_post_meta($post_id, '_backorders', 'no');
		update_post_meta($post_id, '_manage_stock', 'no');
		update_post_meta($post_id, '_amzaff_country', $retProd['country']); // added by jimmy /2017-02-16
		update_post_meta($post_id, '_product_url', home_url('/?redirectAmzASIN=' . $retProd['ASIN'] ));

		if ( isset($retProd['SKU']) ) {
			update_post_meta($post_id, '_sku', $retProd['SKU']);
		}

		if ( isset($retProd['SalesRank']) ) {
			update_post_meta($post_id, '_sales_rank', $retProd['SalesRank']);
		}

		// product is imported using aa-team demo keys
		if ( $is_variation == false ) {
			if ( ! $this->the_plugin->is_aateam_devserver() && ! $this->the_plugin->is_aateam_server() ) {
				if ( $this->the_plugin->is_aateam_demo_keys() ) {
					update_post_meta($post_id, '_amzaff_aateam_keys', 1);
				}
			}
		}
		
		if ( $is_variation == false ) {
			update_post_meta($post_id, '_product_version', $this->the_plugin->get_woocommerce_version()); // 2015, october 28 - attributes bug repaired!

			delete_transient( "wc_product_type_$post_id" );
			set_transient( "wc_product_type_$post_id", 'external');
			
			wp_set_object_terms( $post_id, 'external', 'product_type' );

			if ( isset($retProd['CustomerReviewsURL']) && $retProd['CustomerReviewsURL'] != "" ) {
				$tab_data = array();
				$tab_data[] = array(
					'id' => 'amzAff-customer-review',
					'content' => '<iframe src="' . $retProd['CustomerReviewsURL'] . '" width="100%" height="450" frameborder="0"></iframe>'
				);

				update_post_meta( $post_id, 'amzaff_woo_product_tabs', $tab_data );
			}
		}

		//:: 2018-aug-27
		update_post_meta($post_id, '_amzASIN', $this->the_plugin->prodid_set($retProd['ASIN'], self::$provider, 'sub'));
		update_post_meta($post_id, '_amzaff_prodid', $this->the_plugin->prodid_set($retProd['ASIN'], self::$provider, 'add'));
		update_post_meta($post_id, '_amzaff_prodtype', self::$provider);
		update_post_meta($post_id, '_product_url', home_url(sprintf(
			'/?redirectAmzASIN=%s&redirect_prodid=%s',
			$this->the_plugin->prodid_set($retProd['ASIN'], self::$provider, 'sub'),
			$this->the_plugin->prodid_set($retProd['ASIN'], self::$provider, 'add')
		)));
		if ( isset($retProd['DetailPageURL']) ) {
			update_post_meta($post_id, '_amzaff_product_url', $retProd['DetailPageURL']);
		}

		//:: compatibility with wooaffiliates
		//update_post_meta($post_id, '_aiowaff_prodid', $this->the_plugin->prodid_set($retProd['ASIN'], self::$provider, 'add'));
		//update_post_meta($post_id, '_aiowaff_prodtype', self::$provider);
		//if ( isset($retProd['DetailPageURL']) ) {
		//	update_post_meta($post_id, '_aiowaff_product_url', $retProd['DetailPageURL']);
		//}
	}

	public function attrclean_splitTitle($title) {
		$extra = array(
			'ASIN' => 'ASIN',
			'CEROAgeRating' => 'CERO Age Rating',
			'EAN' => 'EAN',
			'EANList' => 'EAN List',
			'EANListElement' => 'EAN List Element',
			'EISBN' => 'EISBN',
			'ESRBAgeRating' => 'ESRB Age Rating',
			'HMAC' => 'HMAC',
			'IFrameURL' => 'IFrame URL',
			'ISBN' => 'ISBN',
			'MPN' => 'MPN',
			'ParentASIN' => 'Parent ASIN',
			'PurchaseURL' => 'Purchase URL',
			'SKU' => 'SKU',
			'UPC' => 'UPC',
			'UPCList' => 'UPC List',
			'UPCListElement' => 'UPC List Element',
			'URL' => 'URL',
			'URLEncodedHMAC' => 'URL Encoded HMAC',
			'WEEETaxValue' => 'WEEE Tax Value'
		);
		
		if ( in_array($title, array_keys($extra)) ) {
			return $extra["$title"];
		}
		
		preg_match_all('/((?:^|[A-Z])[a-z]+)/', $title, $matches, PREG_PATTERN_ORDER);
		return implode(' ', $matches[1]);
	}

	// Product Price - Update november 2014
	public function productPriceSetMeta( $thisProd, $post_id='', $return=true ) {
		$ret = array();
		$o = array(
			'ItemAttributes'		=> isset($thisProd['ItemAttributes']['ListPrice']) ? array('ListPrice' => $thisProd['ItemAttributes']['ListPrice']) : array(),
			'Offers'				=> isset($thisProd['Offers']) ? $thisProd['Offers'] : array(),
			'OfferSummary'			=> isset($thisProd['OfferSummary']) ? $thisProd['OfferSummary'] : array(),
			'VariationSummary'		=> isset($thisProd['VariationSummary']) ? $thisProd['VariationSummary'] : array(),
		);
		/*
		if ( isset($o['Offers']['Offer']['Promotions']['Promotion']['Summary']) ) {
			//BenefitDescription, TermsAndConditions
			foreach (array('BenefitDescription', 'TermsAndConditions') as $key) {
				if ( isset($o['Offers']['Offer']['Promotions']['Promotion']['Summary']["$key"]) ) {
					$__tmp = $o['Offers']['Offer']['Promotions']['Promotion']['Summary']["$key"];
					$o['Offers']['Offer']['Promotions']['Promotion']['Summary']["$key"] = esc_html($__tmp);
				}
			}
		}
		*/
		update_post_meta($post_id, '_amzaff_amzRespPrice', $o);
		
		// Offers/Offer/OfferListing/IsEligibleForSuperSaverShipping
		if ( isset($o['Offers']['Offer']['OfferListing']['IsEligibleForSuperSaverShipping']) ) {
			$ret['isSuperSaverShipping'] = $o['Offers']['Offer']['OfferListing']['IsEligibleForSuperSaverShipping'] === true ? 1 : 0;
			update_post_meta($post_id, '_amzaff_isSuperSaverShipping', $ret['isSuperSaverShipping']);
		}
		
		// Offers/Offer/OfferListing/Availability
		if ( isset($o['Offers']['Offer']['OfferListing']['Availability']) ) {
			$ret['availability'] = (string) $o['Offers']['Offer']['OfferListing']['Availability'];
			update_post_meta($post_id, '_amzaff_availability', $ret['availability']);
		}

		// Offers/Offer/OfferListing/IsEligibleForPrime
		if ( isset($o['Offers']['Offer']['OfferListing']['IsEligibleForPrime']) ) {
			$ret['isPrime'] = $o['Offers']['Offer']['OfferListing']['IsEligibleForPrime'] === true ? 1 : 0;
			update_post_meta($post_id, '_amzaff_isAmazonPrime', $ret['isPrime']);
		}
		
		return $ret;
	}

	public function productPriceSetRegularSaleMeta( $post_id, $type, $newMetas=array() ) {
		$_amzaff_price = $newMetas;
		$_amzaff_price_db = get_post_meta( $post_id, '_amzaff_'.$type.'_price', true );
		if ( !empty($_amzaff_price_db) && is_array($_amzaff_price_db) ) {
			$_amzaff_price = array_merge($_amzaff_price_db, $_amzaff_price);
		}
		update_post_meta($post_id, '_amzaff_'.$type.'_price', $_amzaff_price);
	}

	public function productPriceGetRegularSaleStatus( $post_id, $type='both' ) {
		$ret = array('regular' => 'auto', 'sale' => 'auto');
		
		foreach (array('regular', 'sale') as $priceType) {
			$meta = (array) get_post_meta( $post_id, '_amzaff_'.$priceType.'_price', true );
			if ( !empty($meta) && isset($meta["current"]) && !empty($meta["current"]) ) {
				$ret["$priceType"] = $meta["current"];
			}
		}
		if ( $type != 'both' && in_array($type, array('regular', 'sale')) ) {
			return $ret["$type"];
		}
		return $ret;
	}


	// Seller
	public function product_has_amazon_seller( $thisProd ) {
		//$price_setup = (isset($this->amz_settings["price_setup"]) && $this->amz_settings["price_setup"] == 'amazon_or_sellers' ? 'amazon_or_sellers' : 'only_amazon');
		$merchant_setup = (isset($this->amz_settings["merchant_setup"]) && $this->amz_settings["merchant_setup"] == 'only_amazon' ? 'only_amazon' : 'amazon_or_sellers');
		
		// request has had (MerchantId = Amazon) in order for the bellow code to work!
		if ( 'only_amazon' == $merchant_setup ) {
			if ( isset($thisProd['Offers'], $thisProd['Offers']['TotalOffers']) ) {
				$total_offers = (int) $thisProd['Offers']['TotalOffers'];

				if ( $total_offers ) {
					return true;
				} else {
					// false only when there is no offer when (MerchantId = Amazon)
					return false;
				}
			}
		}
		return true;
	}


	/**
	 * Octomber 2015 - new plugin functions
	 */
	// key: country || main_aff_id
	public function get_countries( $key='country' ) {
		$localizationName = $this->the_plugin->localizationName;
		if ( 'country' == $key ) {
			return  array(
				'com' => __('United States', $localizationName),
				'co.uk' => __('United Kingdom', $localizationName),
				'de' => __('Germany', $localizationName),
				'in' => __('India', $localizationName),
			);
		}
		else if ( 'main_aff_id' == $key ) {
			return  array(
				'com' => __('United States', $localizationName),
				'uk' => __('United Kingdom', $localizationName),
				'de' => __('Germany', $localizationName), //__('Deutschland', $localizationName),
				'in' => __('India', $localizationName),
			);
		}
		else {
			return  array(
				'com' => '<a href="https://affiliate-program.amazon.com/" target="_blank">United States</a>',
				'uk' => '<a href="https://affiliate-program.amazon.co.uk/" target="_blank">United Kingdom</a>',
				'de' => '<a href="https://partnernet.amazon.de/" target="_blank">Deutschland</a>',
				'in' => '<a href="https://affiliate-program.amazon.in/" target="_blank">India</a>',
			);
		}
		return array();
	}
	
	// key: country || main_aff_id
	public function get_country_name( $country, $key='country' ) {
		$countries = $this->get_countries( $key );
		$country = isset($countries["$country"]) ? $countries["$country"] : '';
		return $country;
	}


	/**
	 * search products by pages
	 * input(pms): array(
	 * 		requestData					: array
	 * 		parameters					: array
	 * 		_optionalParameters			: array
	 * 		page						: int
	 * )
	 * return: array(
	 * 		response					: array
	 * 		status						: string ( valid | invalid )
	 *      msg							: string
	 * 		code						: int
	 * 		amz_code 					: string
	 * 		req_link					: string
	 * )
	 */
	public function api_search_bypages( $pms=array() ) {

		// moved from here in 2018-feb
		//$is_remote_keys = isset($pms['keys_id']) && !empty($pms['keys_id']) ? true : false;

		//:: make request to amazon api
		extract($pms);
		
		// lock current amazon key - aateam keys
		// moved from here in 2018-feb

		$req_link = '';

		try {

		$this->aaAmazonWS->initRequestConfig();

		$this->aaAmazonWS->setCategory(
			$parameters['category'] == 'AllCategories' ? 'All' : $parameters['category']
		);
		$this->aaAmazonWS->setKeywords(
			isset($parameters['keyword']) ? $parameters['keyword'] : ''
		);
		$this->aaAmazonWS->setPage( $page );

		if ( 'oldapi' === $this->the_plugin->amzapi ) {
			$this->aaAmazonWS->setResponseGroup(
				'Large,ItemAttributes,OfferFull,Offers,Variations,Reviews,PromotionSummary,SalesRank'
			);
		}
 
		// set the page
		$_optionalParameters['ItemPage'] = $page;
				
		if( isset($_optionalParameters) && count($_optionalParameters) > 0 ){
			// add optional parameter to query
			$this->aaAmazonWS->setOptionalParameters( $_optionalParameters );
		}
		//var_dump('<pre>',$this->aaAmazonWS,'</pre>');
			
		// add the search keywords
		$response = $this->aaAmazonWS->search();

		if ( 'oldapi' === $this->the_plugin->amzapi ) {
			$req_link = $this->aaAmazonWS->get_xml_amazon_link('normal');
			//var_dump('<pre>',$req_link, $response,'</pre>'); die;
		}

		//$__asinsDebug = array();
		//foreach ( $response['Items']['Item'] as $item_key => $item_val ) {
		//    $__asinsDebug[] = $item_val['ASIN'];
		//}
		//var_dump('<pre>',$__asinsDebug,'</pre>');

		}
		catch (Exception $e) {

			$msg = WooZoneLiteGetExceptionMsg( $e );

			// unlock current amazon key - aateam keys
			// moved from here in 2018-feb

			$response = array('status' => 'invalid', 'msg' => $msg, 'code' => 1, 'amz_code' => strtolower(WooZoneLiteGetExceptionCode( $e )), 'req_link' => $req_link);
			return array_merge(array('response' => $response), $response);
		}

		// unlock current amazon key - aateam keys
		// moved from here in 2018-feb

		$request_status = $this->is_amazon_valid_response( $response );

		$this->the_plugin->save_amazon_last_requests(array_merge($pms, array(
			'request_status'		=> $request_status,
		)));

		$response = $this->apiv5_addVariationsToResponse( $response );

		$request_status = $this->is_amazon_valid_response( $response );

		return array(
			'status'			=> $request_status['status'],
			'msg'				=> $request_status['msg'],
			'code'				=> $request_status['code'],
			'amz_code' 			=> $request_status['amz_code'],
			'req_link' 			=> $req_link,
			'response' 			=> $response,
		);
	}

	/**
	 * search products by asins list
	 * input(pms): array(
	 * 		asins							: array
	 * )
	 * return: array(
	 * 		response						: array
	 * 		status							: string ( valid | invalid )
	 *      msg								: string
	 * 		code							: int
	 * 		amz_code 						: string
	 * 		req_link						: string
	 * )
	 */
	public function api_search_byasin( $pms=array() ) {

		// moved from here in 2018-feb
		//$is_remote_keys = isset($pms['keys_id']) && !empty($pms['keys_id']) ? true : false;

		//:: make request to amazon api
		extract($pms);
		
		// lock current amazon key - aateam keys
		// moved from here in 2018-feb

		$req_link = '';

		try {

		$merchant_setup = (isset($this->amz_settings["merchant_setup"]) && $this->amz_settings["merchant_setup"] == 'only_amazon' ? 'only_amazon' : 'amazon_or_sellers');
		$merchant_setup_ = ('only_amazon' == $merchant_setup ? 'Amazon' : 'All');

		$this->aaAmazonWS->initRequestConfig();

		if ( 'oldapi' === $this->the_plugin->amzapi ) {
			$this->aaAmazonWS->setResponseGroup(
					'Large,ItemAttributes,OfferFull,Offers,Variations,Reviews,PromotionSummary,SalesRank'
			);
		}
		$this->aaAmazonWS->setOptionalParameters(array('MerchantId' => $merchant_setup_));
		//var_dump('<pre>',$this->aaAmazonWS,'</pre>');
		
		$response = $this->aaAmazonWS->setItemIds( implode(",", $asins) )->lookup();

		if ( 'oldapi' === $this->the_plugin->amzapi ) {
			$req_link = $this->aaAmazonWS->get_xml_amazon_link('normal');
			//var_dump('<pre>',$req_link, $response,'</pre>'); die;
		}
				
		//$__asinsDebug = array();
		//foreach ( $response['Items']['Item'] as $item_key => $item_val ) {
		//    $__asinsDebug[] = $item_val['ASIN'];
		//}
		//var_dump('<pre>',$__asinsDebug,'</pre>');
		
		}
		catch (Exception $e) {

			$msg = WooZoneLiteGetExceptionMsg( $e );

			// unlock current amazon key - aateam keys
			// moved from here in 2018-feb

			$response = array('status' => 'invalid', 'msg' => $msg, 'code' => 1, 'amz_code' => strtolower(WooZoneLiteGetExceptionCode( $e )), 'req_link' => $req_link);
			return array_merge(array('response' => $response), $response);
		}

		// unlock current amazon key - aateam keys
		// moved from here in 2018-feb

		$request_status = $this->is_amazon_valid_response( $response );

		$this->the_plugin->save_amazon_last_requests(array_merge($pms, array(
			'request_status'		=> $request_status,
		)));

		$response = $this->apiv5_addVariationsToResponse( $response );

		$request_status = $this->is_amazon_valid_response( $response );

		return array(
			'status'			=> $request_status['status'],
			'msg'				=> $request_status['msg'],
			'code'				=> $request_status['code'],
			'amz_code' 			=> $request_status['amz_code'],
			'req_link' 			=> $req_link,
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

		{
			$rsp = $this->api_search_set_stats(array(
				'requestData'				=> $requestData,
				'response'					=> $response,
			));
			$requestData = $rsp['requestData'];
		}

		// verify array of Items or array of Item elements
		if ( isset($response['Items']['Item']['ASIN']) ) {
			$response['Items']['Item'] = array( $response['Items']['Item'] );
		}

		$_response = array();
		foreach ( $response['Items']['Item'] as $key => $value ) {

			$_response["$key"] = $value;
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
		if ( !isset($results['Items'], $results['Items']['TotalResults'], $results['Items']['NbPagesSelected'])
			|| count($results) < 2 ) {
			$status = false;
		}
		$nbpages = isset($results['Items'], $results['Items']['NbPagesSelected']) ? (int) $results['Items']['NbPagesSelected'] : 0;
		
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
				'TotalResults'			=> $results['Items']['TotalResults'],
				'NbPagesSelected'		=> $results['Items']['NbPagesSelected'],
				'TotalPages'			=> $results['Items']['TotalPages'],
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
			if ( isset($response['Items']['TotalResults']) ) {

				$totalItems = isset($response['Items']['TotalResults']) ? $response['Items']['TotalResults'] : 0;
				$totalPages = $totalItems > 0 ? ceil( $totalItems / 10 ) : 0;
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

		$asins = $page_content['Items']['Item'];
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

			$dataToSave['Items']['TotalResults'] = $stats['TotalResults'];
			$dataToSave['Items']['TotalPages'] = $stats['TotalPages'];
			$dataToSave['Items']['NbPagesSelected'] = $cachename->params['nbpages'];
		}

		if ( is_array($content) && !isset($content['__notused__']) ) {

			$rsp = $this->api_format_results(array(
				'requestData'			=> $requestData,
				'response'				=> $response,
			));

			$dataToSave["$page"] = array();

			// 1 item found only
			if ( $dataToSave['Items']['TotalResults'] == 1 && !isset($rsp['response'][0]) ) {
				$rsp['response'] = array($rsp['response']);
			}

			foreach ($rsp['response'] as $key => $value) {
				$product = $this->build_product_data( $value );
				if ( !empty($product['ASIN']) ) {
					$dataToSave["$page"]['Items']['Item']["$key"] = $product['ASIN'];
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
	 * 			nodeid 							: string
	 * 		)
	 * 		optionalParameters				: array
	 * 		responseGroup					: '' //ex.: Large,ItemAttributes,Offers,Reviews
	 * 		method							: '' //ex.: lookup | search
	 * )
	 * return: array(
	 * 		response						: array
	 * 		status							: string ( valid | invalid )
	 *      msg								: string
	 * 		code							: int
	 * 		amz_code 						: string
	 * 		req_link						: string
	 * )
	 */
	public function api_make_request( $pms=array() ) {

		// moved from here in 2018-feb
		//$is_remote_keys = isset($pms['keys_id']) && !empty($pms['keys_id']) ? true : false;

		//:: make request to amazon api
		extract($pms);
		if ( isset($requestData) ) {
			extract($requestData);
		}
		
		// lock current amazon key - aateam keys
		// moved from here in 2018-feb

		// for new amazon api: if you want to request the variations for each product found (can increase exponentialy the number of api requests)
		$doGetVariations 	= isset($doGetVariations) ? $doGetVariations : true;

		$responseGroup 		= isset($responseGroup) ? $responseGroup : 'Large,ItemAttributes,Offers,Reviews';

		$merchant_setup 	= (isset($this->amz_settings["merchant_setup"]) && $this->amz_settings["merchant_setup"] == 'only_amazon' ? 'only_amazon' : 'amazon_or_sellers');
		$merchant_setup_ 	= ('only_amazon' == $merchant_setup ? 'Amazon' : 'All');

		$optionalParameters = isset($optionalParameters) && !empty($optionalParameters) ? $optionalParameters : array();
		$optionalParameters = array_merge($optionalParameters, array('MerchantId' => $merchant_setup_));

		if ( isset($asin) && is_array($asin) ) {
			$asin = implode(",", $asin);
		}
		
		$category			= isset($category) ? $category : 'DVD';
		$page				= isset($page) ? $page : 1;
		$keyword			= isset($keyword) ? $keyword : 'Matrix';
		$nodeid				= isset($nodeid) ? $nodeid : 0;
		$selectedItems		= isset($selectedItems) ? $selectedItems : array();

		$req_link = '';

		try {

		$method = isset($pms['method']) ? $pms['method'] : '';
		switch ( $method ) {

			case 'lookup':
				$this->aaAmazonWS->initRequestConfig();
				if ( 'oldapi' === $this->the_plugin->amzapi ) {
					$this->aaAmazonWS->setResponseGroup( $responseGroup );
				}
				$response = $this->aaAmazonWS->setOptionalParameters( $optionalParameters )
					->setItemIds( $asin )
					->lookup();
				break;
				
			case 'similarityLookup':
				$this->aaAmazonWS->initRequestConfig();
				if ( 'oldapi' === $this->the_plugin->amzapi ) {
					$this->aaAmazonWS->setResponseGroup( $responseGroup );
				}
				$response = $this->aaAmazonWS->setOptionalParameters( $optionalParameters )
					->setItemIds( $asin )
					->similarityLookup();
				break;

			case 'search':
				$this->aaAmazonWS->initRequestConfig();
				if ( 'oldapi' === $this->the_plugin->amzapi ) {
					$this->aaAmazonWS->setResponseGroup( $responseGroup );
				}
				$response = $this->aaAmazonWS->setCategory( $category )->setKeywords( $keyword )->setPage( $page );
				//var_dump('<pre>', $response->getcfg() , '</pre>'); echo __FILE__ . ":". __LINE__;die . PHP_EOL;
				$response = $response->search();
				//var_dump('<pre>', $response , '</pre>'); echo __FILE__ . ":". __LINE__;die . PHP_EOL;
				break;

			case 'browseNodeLookup':
				$this->aaAmazonWS->initRequestConfig();
				if ( 'oldapi' === $this->the_plugin->amzapi ) {
					$this->aaAmazonWS->setResponseGroup( $responseGroup );
				}
				$response = $this->aaAmazonWS
					->setBrowseNodeIds( $nodeid )
					->browseNodeLookup();
				break;

			// not available in old api
			case 'getVariations':
				$this->aaAmazonWS->initRequestConfig();
				$response = $this->aaAmazonWS->setOptionalParameters( $optionalParameters )
					->setAsin( $asin )->setVariationsPage( $page )
					->getVariations();
				break;

			// NOT USED - verification on 2019-11-04
			// case 'cartThem':
			// 	if ( 'oldapi' === $this->the_plugin->amzapi ) {
			// 		$this->aaAmazonWS->setResponseGroup( $responseGroup );
			// 	}
			// 	$response = $this->aaAmazonWS
			// 		->cartThem( $selectedItems );
			// 	break;

			// NOT USED - verification on 2019-11-04
			// case 'cartKill':
			// 	if ( 'oldapi' === $this->the_plugin->amzapi ) {
			// 		$this->aaAmazonWS->setResponseGroup( $responseGroup );
			// 	}
			// 	$response = $this->aaAmazonWS
			// 		->cartKill();
			// 	break;
				
			default:
				$response = array('status' => 'invalid', 'msg' => 'you need to provide a valid method!', 'code' => 1, 'amz_code' => 'woozonelite:aws.init.issue', 'req_link' => $req_link);
				return array_merge(array('response' => $response), $response);
				//break;
		}
		
		//var_dump('<pre>', $response , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( 'oldapi' === $this->the_plugin->amzapi ) {
			$req_link = $this->aaAmazonWS->get_xml_amazon_link('normal');
			//var_dump('<pre>',$req_link, $response,'</pre>'); die;
		}

		}
		catch (Exception $e) {

			$msg = WooZoneLiteGetExceptionMsg( $e );

			// unlock current amazon key - aateam keys
			// moved from here in 2018-feb

			$response = array('status' => 'invalid', 'msg' => $msg, 'code' => 1, 'amz_code' => strtolower(WooZoneLiteGetExceptionCode( $e )), 'req_link' => $req_link);
			return array_merge(array('response' => $response), $response);
		}
		//var_dump('<pre>', $response , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		// unlock current amazon key - aateam keys
		// moved from here in 2018-feb

		// $responseToValidate = $response;
		// if ( 'cartThem' == $method ) {
		// 	if ( 'oldapi' === $this->the_plugin->amzapi ) {
		// 		$responseToValidate = $this->aaAmazonWS->get_lastCart();
		// 	}
		// }

		$request_status = $this->is_amazon_valid_response( $response, $method );

		$this->the_plugin->save_amazon_last_requests(array_merge($pms, array(
			'request_status'		=> $request_status,
		)));

		if ( $doGetVariations && in_array($method, array('lookup', 'search')) ) {
			$response = $this->apiv5_addVariationsToResponse( $response );
		}

		$request_status = $this->is_amazon_valid_response( $response, $method );

		return array(
			'status'			=> $request_status['status'],
			'msg'				=> $request_status['msg'],
			'code'				=> $request_status['code'],
			'amz_code' 			=> $request_status['amz_code'],
			'req_link' 			=> $req_link,
			'response' 			=> $response,
		);
	}

	// main method to make requests to amazon api (all others go through it)
	public function api_main_request( $pms=array() ) {

		//:: some init
		$DEBUG = false;

		$keys_id = isset($pms['keys_id']) && !empty($pms['keys_id']) ? $pms['keys_id'] : 0;
		$access_keys_id = isset($pms['access_keys_id']) && !empty($pms['access_keys_id']) ? $pms['access_keys_id'] : 0;

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

		//:: YES - try to make remote request through aa-team server
		if ( $this->the_plugin->do_remote_amazon_request() ) {
			$stat = $this->the_plugin->get_remote_amazon_request( $pms );
			//var_dump('<pre>', $stat , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			//if ( 'valid' == $stat['status'] ) {
				$method = isset($pms['method']) ? $pms['method'] : '';
				$this->the_plugin->save_amazon_last_requests(array_merge($pms, array(
					//'request_status'		=> $this->is_amazon_valid_response( $stat['response'], $method ),
					'request_status'		=> array(
						'status'				=> $stat['status'],
						'msg'					=> $stat['msg'],
					),
					'is_remote'				=> true,
				)));

				$this->the_plugin->save_amazon_request_remote_time();
			//}

			$stat = array_replace_recursive($response, array(
				'status'			=> $stat['status'],
				'msg'				=> $stat['msg'],
				'code'				=> $stat['code'],
				'amz_code' 			=> $stat['amz_code'],
				'req_link' 			=> isset($stat['req_link']) ? $stat['req_link'] : '',
				'response' 			=> $stat['response'],
			));

			// we've made a remote request through aa-team server and it was successfull
			if ( 'valid' == $stat['status'] ) {
				return $stat;
			}
		}

		//:: try to make the request
		$max_allowed_req = 3; // maximum number of tries to make per each request
		$current_req = 0; // contor for current number of tries
		$multi_used_keys = array( 1 ); // add aateam demo keys by default, so they are not used as multi

		// don't use multiple keys when making request on aateam demo server
		if ( $keys_id ) {
			$this->use_multi_keys = false;
		}
		// don't use multiple keys on check amazon method
		if ( 'WooZoneLiteAmazonHelper::check_amazon' == $from_func ) {
			$this->use_multi_keys = false;
		}

		do {
			if ( $current_req ) {
				usleep(350000);
			}

			$current_keys_id = $access_keys_id;
			$access_keys = array();

			// get & set the current amazon key to make requests
			if ( $this->use_multi_keys ) {

				$access_key = 'aateam demo access key';
				$secret_key = 'aateam demo secret access key';

				$access_keys = $this->the_plugin->amzkeysObj->get_available_access_key( $multi_used_keys );
				if ( empty($access_keys) || !isset($access_keys['id']) ) {
					$this->use_multi_keys = false;

					// don't count aateam demo keys
					if ( count($multi_used_keys) > 1 ) {
						$current_req++;
						continue 1;
					}
				}
				else {
					$current_keys_id = (int) $access_keys['id'];
					$multi_used_keys[] = $current_keys_id;

					$access_key = $access_keys['access_key'];
					$secret_key = $access_keys['secret_key'];
				}

				$this->setupAmazonWS( array(
					//'AccessKeyID' 			=> WooZoneLite_generateRandomString(),
					//'SecretAccessKey' 		=> WooZoneLite_generateRandomString(),
					'AccessKeyID' 			=> $access_key,
					'SecretAccessKey' 		=> $secret_key,
					'overwrite_settings' 	=> true,
				));
			}

			if ( $this->using_aateam_demo_keys ) {
				$current_keys_id = 1;
			}

			if ( $DEBUG ) {
				echo __FILE__ . ":" . __LINE__. PHP_EOL;
				var_dump('<pre>','--------------', 'loop step: ' . $current_req ,'</pre>');
				var_dump('<pre>', 'multi_used_keys', $multi_used_keys, 'access_keys', $access_keys, 'using_aateam_demo_keys: ' . $this->using_aateam_demo_keys, 'current_keys_id: ' . $current_keys_id, '</pre>');
			}

   			// validate main object
			if ( ! is_object($this->aaAmazonWS) ) {
				$msg = $this->the_plugin->wsStatus['amazon']['msg'];
				$response = array('status' => 'invalid', 'msg' => $msg, 'code' => 1, 'amz_code' => 'woozonelite:aws.init.issue', 'req_link' => '');
				$response = array_merge(array('response' => $response), $response);

				if ( $DEBUG ) {
					echo __FILE__ . ":" . __LINE__. PHP_EOL;
					var_dump('<pre>','response', $response, '</pre>');
				}

				$current_req++;
				continue 1;
			}

			// debug
			if ( $DEBUG ) {
				echo __FILE__ . ":" . __LINE__. PHP_EOL;
				var_dump('<pre>', 'aaAmazonWS cfg', $this->aaAmazonWS->getcfg(), '</pre>');
			}

			// lock current amazon key - aateam keys
			if ( $keys_id ) {
				$this->the_plugin->demokeysObj->lock_current_access_key( $pms['keys_id'] );
			}

			// lock current amazon key - multiple keys
			if ( $current_keys_id ) {

				$lockPms = array();
				$this->the_plugin->amzkeysObj->lock_current_access_key( $current_keys_id, $lockPms );
			}

			// try to make the request
			// HERE WE CALL THE METHOD WHICH MAKE THE REQUEST TO AMAZON API
			$response = $this->$what_func( $pms );

			// unlock current amazon key - multiple keys
			if ( $current_keys_id ) {

				$unlockPms = array();
				if ( 'valid' == $response['status'] ) {
					$unlockPms = array_replace_recursive($unlockPms, array(
						'nb_requests_valid' 	=> true,
					));
				}

				if ( isset($pms['amz_settings']) ) {
					foreach ( $pms['amz_settings'] as $kk => $vv ) {
						if ( ! in_array($kk, array(
							'AccessKeyID', 'SecretAccessKey', 'country', 'main_aff_id'
						))) {
							unset( $pms['amz_settings']["$kk"] );
						}
					}
				}
				//var_dump('<pre>', $this->aaAmazonWS->getcfg() , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				$unlockPms = array_replace_recursive($unlockPms, array(
					'ratio_success' 		=> true,
					'last_request_status' 	=> $response['status'],
					'last_request_output' 	=> maybe_serialize( $response ),
					'last_request_input'	=> maybe_serialize( array_replace_recursive( $pms, array(
						'aaAmazonWS' => $this->aaAmazonWS->getcfg()
					))),
				));

				$this->the_plugin->amzkeysObj->unlock_current_access_key( $current_keys_id, $unlockPms );
			}

			// unlock current amazon key - aateam keys
			if ( $keys_id ) {
				$this->the_plugin->demokeysObj->unlock_current_access_key( $pms['keys_id'] );
			}

			if ( $DEBUG ) {
				$response_dg = $response; unset($response_dg['response']);
				echo __FILE__ . ":" . __LINE__. PHP_EOL;
				var_dump('<pre>', 'response', $response_dg, '</pre>');
			}

			$current_req++;
		}
		while (
			('invalid' == $response['status'])
			&& !in_array($response['code'], array(3))
			&& ($current_req < $max_allowed_req)
			&& $this->use_multi_keys
		);
		// 3 = Sorry, your search did not return any results.

		return $response;
	}


	// Product has an offerListingId
	public function productHasOfferlistingid( $pms=array() ) {
		$pms = array_replace_recursive(array(
			// true = force verification of variations childs for parent variable product
			'verify_variations' => false,

			'thisProd' 			=> null,
			'post_id' 			=> 0,
		), $pms);
		extract($pms);

		if ( empty($thisProd) && $post_id ) {
			$amzResp = get_post_meta($post_id, '_amzaff_amzRespPrice', true);
			$thisProd = $amzResp;
		}

		if ( empty($thisProd) || ! is_array($thisProd) ) {
			return false;
		}

		//:: variable product & it's the parent (not a variation child)
		// DON'T VERIFY CHILDS
		if ( ! $verify_variations ) {
			// -- we consider it by default valid : only it's variation childs are verified
			if ( isset($thisProd["VariationSummary"]) && ! empty($thisProd["VariationSummary"]) ) {
				return true;
			}
		}
		// FORCE CHILDS VERIFICATION
		else {
			// -- must have at least one valid variation child
			if ( isset($thisProd['Variations'], $thisProd['Variations']['Item']) ) {
				//$thisProd['Variations']['TotalVariations']
				$total = $this->the_plugin->get_amazon_variations_nb( $thisProd['Variations']['Item'] );
				
				$variations = array();
				if ($total <= 1 || isset($thisProd['Variations']['Item']['ASIN'])) { // --fix 2015.03.19
					$variations[] = $thisProd['Variations']['Item'];
				} else {
					$variations = (array) $thisProd['Variations']['Item'];
				}
 
				// Loop through the variation
				foreach ($variations as $variation_item) {
					if ( $this->productHasOfferlistingid__( $variation_item ) ) {
						return true;
					}
				} // end foreach
			}
		}

		//:: simple product or a variation child
		if ( $this->productHasOfferlistingid__( $thisProd ) ) {
			return true;
		}

		return false;
	}
	public function productHasOfferlistingid__( $thisProd ) {
		if ( isset($thisProd['Offers']['Offer']['OfferListing'])
			&& ! empty($thisProd['Offers']['Offer']['OfferListing'])
			&& is_array($thisProd['Offers']['Offer']['OfferListing'])
		) {
			if ( isset($thisProd['Offers']['Offer']['OfferListing']['OfferListingId'])
				&& ! empty($thisProd['Offers']['Offer']['OfferListing']['OfferListingId'])
			) {
				return $thisProd['Offers']['Offer']['OfferListing']['OfferListingId'];
			}
		}
		return false;
	}

	public function apiv5_getVariations( $asin, $page=1 ) {

		$rsp = $this->api_main_request(array(
			'what_func' 			=> 'api_make_request',
			'method'				=> 'getVariations',
			'amz_settings'			=> $this->the_plugin->amz_settings,
			'from_file'				=> str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
			'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
			'requestData'			=> array(
				'asin'					=> $asin,
				'page' 					=> $page,
			),
			//'optionalParameters'	=> array(),
		));
		$ret = $rsp['response'];
		return $ret;
	}

	public function apiv5_addVariationsToResponse( $response ) {

		//return $response; //DEBUG
		$respStatus = $this->is_amazon_valid_response( $response );
		//var_dump('<pre>', $respStatus, $response , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( 'oldapi' === $this->the_plugin->amzapi || 'valid' !== $respStatus['status'] ) {
			return $response;
		}

		// verify array of Items or array of Item elements
		if ( isset($response['Items']['Item']['ASIN']) ) {
			$response['Items']['Item'] = array( $response['Items']['Item'] );
		}

		foreach ( $response['Items']['Item'] as $key => $value ) {

			$asin = $value['ASIN'];
			$varResp = $this->apiv5_getVariations( $asin );
			$varRespStatus = $this->is_amazon_valid_response( $varResp, 'getVariations' );
			//var_dump('<pre>', $varRespStatus, $varResp , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			if ( 'valid' !== $varRespStatus['status'] ) {
				continue 1;
			}

			$response['Items']['Item']["$key"] = array_merge(
				$response['Items']['Item']["$key"],
				$varResp
			);
		}
		//var_dump('<pre>', $response , '</pre>'); echo __FILE__ . ":". __LINE__;die . PHP_EOL;
		return $response;
	}

} }