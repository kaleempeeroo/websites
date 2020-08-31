<?php
$plugin_path = plugin_dir_path( (__FILE__)  );
$plugin_path = str_replace('lib/scripts/amazon/', '', $plugin_path);
$plugin_path = str_replace('lib\scripts\amazon/', '', $plugin_path); // for Windows servers
require_once( $plugin_path . 'composer/amazon-paapi/paapi5-php-sdk/vendor/autoload.php' );

use Amazon\ProductAdvertisingAPI\v1\ObjectSerializer;

use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\api\DefaultApi;
use Amazon\ProductAdvertisingAPI\v1\ApiException;
use Amazon\ProductAdvertisingAPI\v1\Configuration;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\PartnerType;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\ProductAdvertisingAPIClientException;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsResource;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsResource;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetBrowseNodesRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetBrowseNodesResource;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetVariationsRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetVariationsResource;

if ( !class_exists('aaAmazonWSNew') ) { class aaAmazonWSNew {

	//================================================
	//== PUBLIC
	//...


	//================================================
	//== PROTECTED & PRIVATE
	protected $the_plugin = null;
	protected $amz_settings = array();

	protected $apiMsgPrefix = 'Amazon API v5: ';

	// Host And Region : https://webservices.amazon.com/paapi5/documentation/common-request-parameters.html#host-and-region
	protected $possibleLocations = array(
		'com' => 'us-east-1',
		'ca' => 'us-east-1',
		'cn' => 'us-east-1', //???
		'de' => 'eu-west-1',
		'in' => 'eu-west-1',
		'it' => 'eu-west-1',
		'es' => 'eu-west-1',
		'fr' => 'eu-west-1',
		'co.uk' => 'eu-west-1',
		'co.jp' => 'us-west-2',
		'com.mx' => 'us-east-1',
		'com.br' => 'us-east-1',
		'com.au' => 'us-west-2',
		'ae' => 'eu-west-1',
		'com.tr' => 'eu-west-1',
	);

	protected $requestConfig = array(
		'optionalParameters'	=> array()
	);

	protected $api_config = array(
		'accessKey' 		=> null,
		'secretKey' 		=> null,
		'country' 			=> null,
		'associateTag' 		=> null,
	);
	protected $api_instance = null;

	protected $testingActivated = 0;



	//================================================
	//== CONSTRUCTOR
	public function __construct( $accessKey, $secretKey, $country, $associateTag='' )
	{
		WooZoneLite_session_start();

		$this->init( array(
			'accessKey' 		=> $accessKey,
			'secretKey' 		=> $secretKey,
			'country' 			=> $country,
			'associateTag' 		=> $associateTag,
		));
		$this->initRequestConfig();
	}



	//====================================================================================
	//== PUBLIC
	//====================================================================================

	//================================================
	//=== CONFIGURATION
	public function getcfg()
	{
		$params = $this->requestConfig;
		$params = array_replace_recursive( $params, $params['optionalParameters'] );
		unset( $params['optionalParameters'] );

		$ret = array(
			'apiversion' 		=> 'v5',
			'requestConfig' 	=> $params,
			'responseConfig' 	=> array(), //Legacy Code for API V4
		);
		return $ret;
	}

	public function init( $pms=array() )
	{
		$pms = array_replace_recursive(array(
			'accessKey' 		=> null,
			'secretKey' 		=> null,
			'country' 			=> null,
			'associateTag' 		=> null,
		), $pms);
		//extract( $pms );

		foreach ( $pms as $key => $val ) {
			if ( null === $val ) {
				continue 1;
			}
			$this->$key( $val );
		}

		//:: amazon api config
		//var_dump('<pre>', $this->api_config , '</pre>'); echo __FILE__ . ":". __LINE__;die . PHP_EOL;
		extract( $this->api_config );

		$config = new Configuration();

		$config->setAccessKey( $accessKey );
		$config->setSecretKey( $secretKey );
		$config->setHost( 'webservices.amazon.' . $country );
		$config->setRegion( $this->possibleLocations["$country"] );
		//var_dump('<pre>', $config , '</pre>'); echo __FILE__ . ":". __LINE__;die . PHP_EOL;

		$apiInstance = new DefaultApi(
			new GuzzleHttp\Client(),
			$config
		);
		$this->api_instance = $apiInstance;
		//var_dump('<pre>', $this->api_instance , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		return $this;
	}

	public function set_the_plugin( $the_plugin=array(), $settings=array() )
	{
		$this->the_plugin = $the_plugin;

		if ( !empty($this->the_plugin) && !empty($this->the_plugin->amz_settings) ) {
			$this->amz_settings = $this->the_plugin->amz_settings;
		} else {
			$this->amz_settings = $settings;
		}
		$this->amz_settings = !empty($this->amz_settings) && is_array($this->amz_settings) ? $this->amz_settings : array();
	}

	public function accessKey( $accessKey=null )
	{
		if (null === $accessKey) {
			return $this->api_config['accessKey'];
		}

		if ( empty($accessKey) ) {
			//throw new Exception('No Access Key has been set');
			throw new InvalidArgumentException("{$this->apiMsgPrefix}No Access Key has been set");
		}

		$this->api_config['accessKey'] = $accessKey;

		return $this;
	}

	public function secretKey( $secretKey=null )
	{
		if (null === $secretKey)
		{
			return $this->api_config['secretKey'];
		}

		if ( empty($secretKey) ) {
			//throw new Exception('No Secret Key has been set');
			throw new InvalidArgumentException("{$this->apiMsgPrefix}No Secret Key has been set");
		}

		$this->api_config['secretKey'] = $secretKey;

		return $this;
	}

	public function country( $country=null )
	{
		if (null === $country)
		{
			return $this->api_config['country'];
		}

		$country = strtolower($country);

		if (false === in_array($country, array_keys($this->possibleLocations)))
		{
			throw new InvalidArgumentException(sprintf(
				"{$this->apiMsgPrefix}Invalid Country-Code: %s! Possible Country-Codes: %s",
				$country,
				implode(', ', array_keys($this->possibleLocations))
			));
		}

		$this->api_config['country'] = $country;

		return $this;
	}

	public function associateTag( $associateTag=null )
	{
		if (null === $associateTag)
		{
			return $this->api_config['associateTag'];
		}

		if ( empty($associateTag) ) {
			//throw new Exception('No Associate Tag has been set');
			throw new InvalidArgumentException("{$this->apiMsgPrefix}No Associate Tag has been set");
		}

		$this->api_config['associateTag'] = $associateTag;

		return $this;
	}

	//================================================
	//=== MAKE REQUEST - MAIN METHODS
	public function search( $pms=array() )
	{
		$params = array_replace_recursive( array(
			'SearchIndex' => null,
			'Keywords' => null,
			'optionalParameters' => array(),
		), $this->requestConfig, $pms );

		//:: validation
		//--none needed--

		//:: build the params
		if ( '' === $params['Keywords'] ) {
			$params['Keywords'] = null;
		}
		$params = array_replace_recursive( $params, $params['optionalParameters'] );
		unset( $params['optionalParameters'] );
		//var_dump('<pre>', $params , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		return $this->requestMake( 'SearchItems', $params );
	}

	public function lookup( $pms=array() )
	{
		$params = array_replace_recursive( array(
			'ItemIds' => null,
			'optionalParameters' => array(),
		), $this->requestConfig, $pms );
		//var_dump('<pre>', $params , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		//:: validation
		//( is_string($params['ItemIds']) && ( null === $params['ItemIds'] || '' === $params['ItemIds'] ) )
		if ( ! is_array($params['ItemIds']) || empty($params['ItemIds']) ) {
			throw new InvalidArgumentException(
				"{$this->apiMsgPrefix}ItemIds parameter must be: List of Non-Empty Strings (up to 10)"
			);
		}

		//:: build the params
		$params = array_replace_recursive( $params, $params['optionalParameters'] );
		unset( $params['optionalParameters'] );
		//var_dump('<pre>', $params , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		return $this->requestMake( 'GetItems', $params );
	}

	public function browseNodeLookup( $pms=array() )
	{
		$params = array_replace_recursive( array(
			'BrowseNodeIds' => null,
			'optionalParameters' => array(),
		), $this->requestConfig, $pms );
		//var_dump('<pre>', $params , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		//:: validation
		if ( ! is_array($params['BrowseNodeIds']) || empty($params['BrowseNodeIds']) ) {
			throw new InvalidArgumentException(
				"{$this->apiMsgPrefix}BrowseNodeIds parameter must be: List of Strings (Positive Long only) (up to 10)"
			);
		}

		//:: build the params
		$params = array_replace_recursive( $params, $params['optionalParameters'] );
		unset( $params['optionalParameters'] );
		//var_dump('<pre>', $params , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		return $this->requestMake( 'GetBrowseNodes', $params );
	}

	// Method only for API V5
	public function getVariations( $pms=array() )
	{
		$params = array_replace_recursive( array(
			'ASIN' => null,
			'optionalParameters' => array(),
		), $this->requestConfig, $pms );
		//var_dump('<pre>', $params , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		//:: validation
		if ( ! is_string($params['ASIN']) || null === $params['ASIN'] || '' === trim($params['ASIN']) ) {
			throw new InvalidArgumentException(
				"{$this->apiMsgPrefix}ASIN parameter must be: Non-Empty String"
			);
		}

		$params['ASIN'] = trim($params['ASIN']);

		//:: build the params
		$params = array_replace_recursive( $params, $params['optionalParameters'] );
		unset( $params['optionalParameters'] );
		//var_dump('<pre>', $params , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		return $this->requestMake( 'GetVariations', $params );
	}

	//================================================
	//=== Legacy Method for API V4
	// Legacy Method for API V4
	public function similarityLookup($asin='')
	{

		throw new Exception( "{$this->apiMsgPrefix}similarityLookup is not yet implemented in amazon api v5!" );
	}

	// Legacy Method for API V4
	public function cartThem($asin)
	{

		throw new Exception( "{$this->apiMsgPrefix}cartThem will not be implemented in amazon api v5!" );
	}

	// Legacy Method for API V4
	public function cartKill($asin)
	{

		throw new Exception( "{$this->apiMsgPrefix}cartKill will not be implemented in amazon api v5!" );
	}

	//================================================
	//=== REQUEST BUILD
	public function initRequestConfig()
	{
		$this->requestConfig = array(
			'optionalParameters'	=> array()
		);
		return $this;
	}

	public function setCategory($category=null)
	{
		if (null === $category)
		{
			return isset($this->requestConfig['SearchIndex']) ? $this->requestConfig['SearchIndex'] : null;
		}

		$this->requestConfig['SearchIndex'] = $category;

		return $this;
	}

	public function setKeywords($keywords=null)
	{
		if (null === $keywords)
		{
			return isset($this->requestConfig['Keywords']) ? $this->requestConfig['Keywords'] : null;
		}

		$this->requestConfig['Keywords'] = $keywords;

		return $this;
	}

	public function setOptionalParameters($params=null)
	{
		if (null === $params)
		{
			return $this->requestConfig['optionalParameters'];
		}

		if ( ! is_array($params) )
		{
			throw new InvalidArgumentException(sprintf(
				"{$this->apiMsgPrefix}%s is no valid parameter: Use an array with Key => Value Pairs", $params
			));
		}

		// if ( ! isset($this->requestConfig['optionalParameters'])
		// 	|| ! is_array($this->requestConfig['optionalParameters'])
		// ) {
		// 	$this->requestConfig['optionalParameters'] = array();
		// }

		$params_new = array();
		array_walk( $params, function( &$value, $key ) use (&$params_new) {
			$newkey = $this->_migrate2apiv5( $key );
			$params_new["$newkey"] = $value;
		});
		$params = $params_new;
		//var_dump('<pre>', $params , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( isset($params['BrowseNodeId']) && null !== $params['BrowseNodeId'] ) {
			$this->validateNodeId( $params['BrowseNodeId'] );
		}

		if ( isset($params['SortBy']) && null !== $params['SortBy'] ) {
			$params['SortBy'] = $this->_migrate2apiv5( $params['SortBy'] );
		}

		$this->requestConfig['optionalParameters'] = array_merge(
			$this->requestConfig['optionalParameters'],
			$params
		);

		return $this;
	}

	public function setPage($page=null)
	{
		if (null === $page)
		{
			return isset($this->requestConfig['optionalParameters']['ItemPage']) ? $this->requestConfig['optionalParameters']['ItemPage'] : null;
		}

		if (false === is_numeric($page) || $page <= 0)
		{
			throw new InvalidArgumentException(sprintf(
				"{$this->apiMsgPrefix}%s is an invalid page value. It has to be numeric and positive",
				$page
			));
		}

		$this->requestConfig['optionalParameters'] = array_merge(
			$this->requestConfig['optionalParameters'],
			array(
				"ItemPage" => $page,
				"ItemCount" => 10,
			)
		);

		return $this;
	}

	//$itemid = string (comma separated)
	public function setItemIds($itemid=null)
	{
		if (null === $itemid)
		{
			return isset($this->requestConfig['ItemIds']) ? $this->requestConfig['ItemIds'] : null;
		}

		$itemid = $this->_filterItemId( $itemid );

		$this->requestConfig['ItemIds'] = $itemid;

		return $this;
	}

	//$nodeId = integer
	public function setBrowseNodeIds($nodeid=null)
	{
		if (null === $nodeid)
		{
			return isset($this->requestConfig['BrowseNodeIds']) ? $this->requestConfig['BrowseNodeIds'] : null;
		}

		$nodeid = $this->_filterItemId( $nodeid );

		$this->requestConfig['BrowseNodeIds'] = $nodeid;

		return $this;
	}

	//for method getVariations
	public function setAsin($asin=null)
	{
		if (null === $asin)
		{
			return isset($this->requestConfig['ASIN']) ? $this->requestConfig['ASIN'] : null;
		}

		$this->requestConfig['ASIN'] = $asin;

		return $this;
	}

	//for method getVariations
	public function setVariationsPage($page=null)
	{
		if (null === $page)
		{
			return isset($this->requestConfig['optionalParameters']['VariationPage']) ? $this->requestConfig['optionalParameters']['VariationPage'] : null;
		}

		if (false === is_numeric($page) || $page <= 0)
		{
			throw new InvalidArgumentException(sprintf(
				"{$this->apiMsgPrefix}%s is an invalid page value. It has to be numeric and positive",
				$page
			));
		}

		$this->requestConfig['optionalParameters'] = array_merge(
			$this->requestConfig['optionalParameters'],
			array(
				"VariationPage" => $page,
				"VariationCount" => 10,
			)
		);

		return $this;
	}

	//================================================
	//=== MISC



	//====================================================================================
	//== PROTECTED & PRIVATE
	//====================================================================================

	//================================================
	//=== CONFIGURATION
	protected function save_amazon_request_time()
	{
		if ( !empty($this->the_plugin) && is_object($this->the_plugin)
			//&& is_a($this->the_plugin, $this->plugin_alias) ) {
		) {
			return $this->the_plugin->save_amazon_request_time();
		}
		return false;
	}
	
	protected function verify_amazon_request_rate( $do_pause=true )
	{
		if ( !empty($this->the_plugin) && is_object($this->the_plugin)
			//&& is_a($this->the_plugin, $this->plugin_alias)
		) {
			return $this->the_plugin->verify_amazon_request_rate( $do_pause );
		}
		return false;
	}

	//================================================
	//=== MAKE THE REQUEST
	protected function requestMake( $method, $pms=array() )
	{
		//:: forming the request
		switch ($method) {

			case 'SearchItems':
				$therequest = new SearchItemsRequest();
				$mGetResult = array( 'getSearchResult', 'getItems' );
				break;

			case 'GetItems':
				$therequest = new GetItemsRequest();
				$mGetResult = array( 'getItemsResult', 'getItems' );
				break;

			case 'GetVariations':
				$therequest = new GetVariationsRequest();
				$mGetResult = array( 'getVariationsResult', 'getItems' );
				break;

			case 'GetBrowseNodes':
				$therequest = new GetBrowseNodesRequest();
				$mGetResult = array( 'getBrowseNodesResult', 'getBrowseNodes' );
				break;
		}

		//var_dump('<pre>', $this->api_config['associateTag'] , '</pre>'); echo __FILE__ . ":". __LINE__;die . PHP_EOL;
		$therequest->setPartnerTag( $this->api_config['associateTag'] ); //mandatory
		$therequest->setPartnerType( PartnerType::ASSOCIATES ); //mandatory
		$therequest->setResources( $this->_set_resources($method) );

		foreach ( $pms as $key => $val ) {

			if ( is_null($val) ) {
				continue 1;
			}

			$setmethod = "set$key";
			//var_dump('<pre>', $setmethod, $val ,'</pre>');
			$therequest->$setmethod( $val );
		}

		//:: validating the request
		$invalidPropertyList = $therequest->listInvalidProperties();
		if ( count($invalidPropertyList) ) {

			$msg = "{$this->apiMsgPrefix}error forming the request: " . implode(' | ', $invalidPropertyList);
			throw new Exception( $msg );
		}

		//:: sending the request
		try {

			// verify amazon request rate (per second)
			$this->verify_amazon_request_rate();
			$this->save_amazon_request_time();

			// IS TESTING?
			if ( $this->testingActivated ) {
				$theresponse = $this->_testingGetResponse( $method );
			}
			// MAKE REQUEST TO AMAZON API
			else {
				$theresponse = $this->api_instance->{ lcfirst( $method ) }( $therequest );
			}
			//var_dump('<pre>', $theresponse , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$ret = $this->requestResponseStart( $method, $theresponse );
			//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$theresponse_ = $theresponse->{$mGetResult[0]}();
			if ( null !== $theresponse_ ) {

				// $theresponse_ = $theresponse_->{$mGetResult[1]}();
				// if ( null !== $theresponse_ ) {

					$theresponse_ = $this->requestResponseParse( $method, $theresponse_, array() );
					$ret = array_replace_recursive(
						$ret,
						$theresponse_
					);
				// }
			}

			//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			return $ret;
		}
		catch (ApiException $e) {

			$rh = $e->getResponseHeaders();
			$rh1 = isset($rh['x-amzn-RequestId'][0]) ? $rh['x-amzn-RequestId'][0] : '';

			$_msg = array();
			$_msg[] = "{$this->apiMsgPrefix}";
			$_msg[] = 'http status code = ' . $e->getCode();
			$_msg[] = 'http msg = ' . htmlentities( $e->getMessage(), ENT_QUOTES, "UTF-8" );
			if ( '' !== $rh1 ) {
				$_msg[] = 'http x-amzn-RequestId = ' . htmlentities( $rh1, ENT_QUOTES, "UTF-8" );
			}

			if ( $e->getResponseObject() instanceof ProductAdvertisingAPIClientException ) {

				$errors = $e->getResponseObject()->getErrors();
				if ( null !== $errors && is_array($errors) ) {

					foreach ( $errors as $error ) {
						$_msg[] = 'errcode = ' . $error->getCode();
						$_msg[] = 'errmsg = ' . htmlentities( $error->getMessage(), ENT_QUOTES, "UTF-8" );
					}
				}
			}
			else {
				$_msg[] = 'exception response body = ' . htmlentities( $e->getResponseBody(), ENT_QUOTES, "UTF-8" );
			}

			$msg = implode('<br />', $_msg);

			$ret = json_decode(json_encode(array(
				'status' => 'invalid',
				'msg' => $msg,
				'code' => 1, //error
				'amz_code' => 'woozonelite:aws5.issue',
			)), 1);
			//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":". __LINE__;die . PHP_EOL;
			return $ret;
		}
		catch (Exception $e) {

			$msg = "{$this->apiMsgPrefix}" . WooZoneLiteGetExceptionMsg( $e );
			$msg = htmlentities( $msg, ENT_QUOTES, "UTF-8" );

			$ret = json_decode(json_encode(array(
				'status' => 'invalid',
				'msg' => $msg,
				'code' => 1, //error
				'amz_code' => 'woozonelite:aws5.issue',
			)), 1);
			//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":". __LINE__;die . PHP_EOL;
			return $ret;
		}
	}

	protected function requestResponseStart( $method, $response )
	{
		$ret = array(
			'Request' => array(
				'IsValid' => 'True',
			),
		);

		//:: if some errors?
		$errors = $response->getErrors();
		if ( null !== $errors && is_array($errors) ) {

			$ret['Request']['Errors'] = array( 'Error' => array() );

			foreach ( $errors as $error ) {

				$ret['Request']['Errors']['Error'][] = array(
					'Code' => $error->getCode(),
					'Message' => htmlentities( $error->getMessage(), ENT_QUOTES, "UTF-8" ),
				);
			}
		}

		//:: forming by method
		$retnew = array();
		switch ($method) {

			case 'SearchItems':
			case 'GetItems':
				$retnew['Items'] = $ret;
				break;

			case 'GetVariations':
				$retnew['Variations'] = $ret;
				break;

			case 'GetBrowseNodes':
				$retnew['BrowseNodes'] = $ret;
				break;
		}
		//var_dump('<pre>', $retnew , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $retnew;
	}

	protected function requestResponseParse( $method, $response, $pms=array() )
	{
		//var_dump('<pre>', $response , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		$retnew = array();
		if ( null === $response ) {
			return $retnew;
		}

		switch ($method) {

			case 'SearchItems':
			case 'GetItems':

				$items = $response->getItems();
				break;

			case 'GetVariations':

				$items = $response->getItems();

				$VariationSummary = $response->getVariationSummary();
				$VariationDimensions = null !== $VariationSummary ? $VariationSummary->getVariationDimensions() : null;
				$VariationDimensions = is_array($VariationDimensions) ? array_values( $VariationDimensions ) : array();
				//var_dump('<pre>', $VariationDimensions , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				break;

			case 'GetBrowseNodes':

				$items = $response->getBrowseNodes();
				break;
		}

		if ( ! is_array($items) ) {
			return $retnew;
		}

		$newitems = array();
		foreach ( $items as $item ) {

			if ( null === $item ) {
				continue 1;
			}
			//var_dump('<pre>',$item ,'</pre>');

			if ( in_array($method, array('SearchItems', 'GetItems', 'GetVariations')) ) {

				$curitem = array_replace_recursive(
					array(),
					$this->getGroupMain( $item ),
					$this->getGroupBrowseNodeInfo( $item ),
					$this->getGroupOffers( $item ),
					$this->getGroupImages( $item ),
					$this->getGroupItemAttributes( $item ),
					array()
				);

				if ( 'GetVariations' === $method ) {

					$curitem = array_replace_recursive(
						//array(),
						$curitem,
						$this->getGroupVariationAttributes( $item, $VariationDimensions ),
						array()
					);

					if ( ! isset($curitem['ParentASIN']) && isset($this->requestConfig['ASIN']) ) {
						$curitem['ParentASIN'] = $this->requestConfig['ASIN'];
					}

					// if ( isset($curitem['OfferSummary']) ) {
					// 	unset( $curitem['OfferSummary'] );
					// }
				}
				$newitems[] = $curitem;
			}
			else if ( 'GetBrowseNodes' === $method ) {

				$curitem = array_replace_recursive(
					array(),
					$this->getGroupBrowseNodes( $item ),
					array()
				);
				$newitems[] = $curitem;
			}
		}
		//var_dump('<pre>', $newitems , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( 1 === count($newitems) ) {
			$newitems = $newitems[0];
		}

		//:: forming by method
		switch ($method) {

			case 'SearchItems':

				$retnew['Items'] = array_merge(
					array(),
					$this->getGroupSearchExtra( $response ),
					array(
						'Item' => $newitems,
					),
					array()
				);
				break;

			case 'GetItems':

				$retnew['Items'] = array_merge(
					array(),
					array(
						'Item' => $newitems,
					),
					array()
				);
				break;

			case 'GetVariations':

				$retnew['Variations'] = array_merge(
					array(),
					$this->getGroupVariationDimensions( $VariationDimensions ),
					$this->getGroupVariationExtra( $VariationSummary ),
					array(
						'Item' => $newitems,
					),
					array()
				);
				$retnew = array_merge(
					//array(),
					//$this->getGroupVariationSummary( $VariationSummary ),
					$this->getGroupVariationSummaryMinPrice( $VariationSummary, $newitems ),
					$retnew,
					array()
				);
				break;

			case 'GetBrowseNodes':

				$retnew['BrowseNodes'] = array_merge(
					array(),
					array(
						'BrowseNode' => $newitems,
					),
					array()
				);
				break;
		}
		//var_dump('<pre>', $retnew , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $retnew;
	}

	//================================================
	//=== BUILD RESPONSE - COMPATIBLE WITH OLD API RESPONSE

	// $item = single item
	protected function getGroupMain( $item)
	{
		$ret = array();

		if ( null === $item ) {
			return $ret;
		}

		if ( null !== $item->getASIN() ) {
			$ret['ASIN'] = $item->getASIN();
		}

		if ( null !== $item->getParentASIN() ) {
			$ret['ParentASIN'] = $item->getParentASIN();
		}

		if ( null !== $item->getDetailPageURL() ) {
			$ret['DetailPageURL'] = $item->getDetailPageURL();
		}

		//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $ret;
	}

	// $item = single item
	// DEPRECATED: SKU
	protected function getGroupItemAttributes( $item )
	{
		$ret = array(
			'ItemAttributes' => array(),
		);

		if ( null === $item ) {
			return $ret;
		}

		$ItemInfo = $item->getItemInfo();
		$attr = array();
		//$test = $ItemInfo->getProductInfo()->getItemDimensions();
		//var_dump('<pre>', $test , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( null === $ItemInfo ) {
			return $ret;
		}

		// DisplayValue = returns a string
		// DisplayValues = returns an numerical array (of string values OR objects)
		$thetree = array(
			//ByLineInfo
			'Brand' => array( 'ByLineInfo', 'Brand', 'DisplayValue' ),
			'Manufacturer' => array( 'ByLineInfo', 'Manufacturer', 'DisplayValue' ),
			'Contributors' => array( 'ByLineInfo', 'Contributors' ), //array of objects: Name, Role

			//Classifications
			'Binding' => array( 'Classifications', 'Binding', 'DisplayValue' ),
			'Product Group' => array( 'Classifications', 'ProductGroup', 'DisplayValue' ),

			//ContentInfo
			'Edition' => array( 'ContentInfo', 'Edition', 'DisplayValue' ),
			'Languages' => array( 'ContentInfo', 'Languages', 'DisplayValues' ), //array of objects: Type, DisplayValue
			'Number Of Pages' => array( 'ContentInfo', 'PagesCount', 'DisplayValue' ),
			'Publication Date' => array( 'ContentInfo', 'PublicationDate', 'DisplayValue' ),

			//ContentRating
			'Audience Rating' => array( 'ContentRating', 'AudienceRating', 'DisplayValue' ),

			//ExternalIds
			'EANs' => array( 'ExternalIds', 'EANs', 'DisplayValues' ), //array numerical
			'UPCs' => array( 'ExternalIds', 'UPCs', 'DisplayValues' ), //array numerical
			'ISBNs' => array( 'ExternalIds', 'ISBNs', 'DisplayValues' ), //array numerical

			//Features
			'Features' => array( 'Features', 'DisplayValues' ), //array numerical

			//ManufactureInfo
			'Part Number' => array( 'ManufactureInfo', 'ItemPartNumber', 'DisplayValue' ),
			'Model' => array( 'ManufactureInfo', 'Model', 'DisplayValue' ),
			'Warranty' => array( 'ManufactureInfo', 'Warranty', 'DisplayValue' ),

			//ProductInfo
			'Color' => array( 'ProductInfo', 'Color', 'DisplayValue' ),
			'Height' => array( 'ProductInfo', 'ItemDimensions', 'Height' ), //DisplayValue & Unit
			'Length' => array( 'ProductInfo', 'ItemDimensions', 'Length' ), //DisplayValue & Unit
			'Weight' => array( 'ProductInfo', 'ItemDimensions', 'Weight' ), //DisplayValue & Unit
			'Width' => array( 'ProductInfo', 'ItemDimensions', 'Width' ), //DisplayValue & Unit
			'Is Adult Product' => array( 'ProductInfo', 'IsAdultProduct', 'DisplayValue' ),
			'Release Date' => array( 'ProductInfo', 'ReleaseDate', 'DisplayValue' ),
			'Size' => array( 'ProductInfo', 'Size', 'DisplayValue' ),
			'Number Of Items' => array( 'ProductInfo', 'UnitCount', 'DisplayValue' ),

			//TechnicalInfo
			'Formats' => array( 'TechnicalInfo', 'Formats', 'DisplayValues' ), //array numerical

			//Title
			'Title' => array( 'Title', 'DisplayValue' ),

			//TradeInInfo
			'Is Eligible For TradeIn' => array( 'TradeInInfo', 'IsEligibleForTradeIn' ),
			'TradeIn Price' => array( 'TradeInInfo', 'Price', 'DisplayAmount' ),
		);

		// foreach main
		foreach ( $thetree as $name => $tree ) {

			$__ = $ItemInfo;

			foreach ( $tree as $mcall ) {

				$__ = $__->{"get{$mcall}"}();
				//var_dump('<pre>', "get{$mcall}", '</pre>');
				if ( null === $__ ) {
					continue 2; // continue in foreach main too
				}
			}

			// special case: extra property to retrieve
			if ( in_array($name, array('Height', 'Length', 'Weight', 'Width')) ) {
				//DisplayValue & Unit
				if ( null !== $__->getDisplayValue() ) {
					$__ = $__->getDisplayValue() . ( null !== $__->getUnit() ? ' ' . $__->getUnit() : '' );
				}
			}
			else if (
				in_array($name, array('Contributors', 'Languages'))
				&& is_array($__) && ! empty($__)
			) {
				//var_dump('<pre>', $mcall, $__, '</pre>');
				$__2 = array();

				foreach ( $__ as $vv ) {

					if ( 'Contributors' == $name ) {
						//Name & Role
						if ( null !== $vv->getName() && null !== $vv->getRole() ) {
							$__2[] = $vv->getName() . ' = ' . $vv->getRole();
						}
					}
					else if ( 'Languages' == $name ) {
						//Type, DisplayValue
						if ( null !== $vv->getType() && null !== $vv->getDisplayValue() ) {
							$__2[] = $vv->getType() . ' = ' . $vv->getDisplayValue();
						}
					}
				}

				$__ = $__2;
			}

			if ( 'features' === strtolower($name) ) {
				$name = 'Feature';
			}

			$attr["$name"] = $__;
		}
		// end foreach main
		
		//var_dump('<pre>', $attr , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		$ret['ItemAttributes'] = $attr;
		return $ret;
	}

	// $item = single item
	protected function getGroupImages( $item )
	{
		$ret = array(
			'ImageSets' => array(
				'ImageSet' => array(),
			),
		);

		if ( null === $item ) {
			return $ret;
		}

		$Images = $item->getImages();
		$ImageTypes = array('Primary', 'Variants');
		$ImageSizes = array('Small', 'Medium', 'Large');

		$imgs = array();
		$imgsize = array();
		$cc = 0;

		if ( null === $Images ) {
			return $ret;
		}

		// foreach main
		foreach ( $ImageTypes as $ImageType ) {

			$__ = $Images->{"get{$ImageType}"}();
			//var_dump('<pre>', "get{$ImageType}", '</pre>');
			if ( null === $__ ) {
				continue 1;
			}

			//var_dump('<pre>',getType($__) ,'</pre>');
			// Primary = returns an object
			// Variants = returns an array numerical (of objects)
			if ( 'object' === getType($__) ) {
				$__ = array( $__ );
			}

			if ( ! is_array($__) ) {
				continue 1;
			}

			// foreach sub-main
			foreach ( $__ as $ImageValue ) {

				foreach ( $ImageSizes as $ImageSize ) {

					$__2 = $ImageValue->{"get{$ImageSize}"}();
					if ( null === $__2 ) {
						continue 1;
					}

					$img_url = $__2->getURL();
					$img_w = $__2->getWidth();
					$img_h = $__2->getHeight();
					//var_dump('<pre>', $img_url, $img_w, $img_h, '</pre>');

					if ( null === $img_url || null === $img_h || null === $img_w ) {
						continue 1;
					}

					$img_val = array(
						'URL' => $img_url,
						'Height' => array(
							'_' => (string) $img_h,
							'Units' => 'pixels',
						),
						'Width' => array(
							'_' => (string) $img_w,
							'Units' => 'pixels',
						),
					);
					//var_dump('<pre>',$img_val ,'</pre>');

					$imgs[$cc]["{$ImageSize}Image"] = $img_val;

					// first found image resource => the main product image
					if ( ! isset($imgsize["{$ImageSize}Image"])
						|| ! is_array($imgsize["{$ImageSize}Image"])
						|| empty($imgsize["{$ImageSize}Image"])
					) {
						$imgsize["{$ImageSize}Image"] = $img_val;
					}
				}

				$cc++;
			}
			// end foreach sub-main
		}
		// end foreach main
		$imgs = array_values( $imgs ); // reset numerical keys if necessary
		//var_dump('<pre>', $imgsize, $imgs, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		$ret = array_replace_recursive( $ret, $imgsize, array(
			'ImageSets' => array(
				'ImageSet' => $imgs,
			),
		));
		return $ret;
	}

	// $item = single item
	protected function getGroupOffers( $item )
	{
		$ret = array(
			'OfferSummary' => array(),
			'Offers' => array(
				'TotalOffers' => 0,
				'TotalOfferPages' => 1, //DEPRECATED
				'Offer' => array(),
			),
		);

		if ( null === $item ) {
			return $ret;
		}

		$Offers = $item->getOffers();

		$Listings = null !== $Offers ? $Offers->getListings() : null;
		$Listings = is_array($Listings) ? array_values( $Listings ) : array();
		$Listing = isset($Listings[0]) ? $Listings[0] : null;

		//:: Offers / Offer
		$oflist = array(
			'Merchant' => array(
				'Name' => '--unknown--',
			),
			'OfferAttributes' => array(
				'Condition' => 'New',
			),
			'OfferListing' => array(),
		);
		$ConditionValue = 'New';

		if ( null !== $Listing ) {

			$Condition = $Listing->getCondition();
			if ( null !== $Condition && null !== $Condition->getValue() ) {
				$ConditionValue = $Condition->getValue();
				$oflist['OfferAttributes'] = array(
					'Condition' => $ConditionValue,
				);
			}

			$MerchantInfo = $Listing->getMerchantInfo();
			if ( null !== $MerchantInfo && null !== $MerchantInfo->getName() ) {
				$oflist['Merchant'] = array(
					'Name' => $MerchantInfo->getName(),
				);
			}

			$Id = $Listing->getId();
			$Availability = $Listing->getAvailability();
			$DeliveryInfo = $Listing->getDeliveryInfo();
			//$IsBuyBoxWinner = $Listing->getIsBuyBoxWinner();
			//DeliveryInfo / IsAmazonFulfilled
			// see amz.helper.php / product_has_amazon_seller method

			$ol = array();
			if ( null !== $Id ) {
				$ol['OfferListingId'] = $Id;
			}
			if ( null !== $Availability && null !== $Availability->getMessage() ) {
				$ol['Availability'] = (string) $Availability->getMessage();
			}
			if ( null !== $DeliveryInfo && null !== $DeliveryInfo->getIsPrimeEligible() ) {
				$ol['IsEligibleForPrime'] = (bool) $DeliveryInfo->getIsPrimeEligible();
			}
			if ( null !== $DeliveryInfo && null !== $DeliveryInfo->getIsFreeShippingEligible() ) {
				$ol['IsEligibleForSuperSaverShipping'] = (bool) $DeliveryInfo->getIsFreeShippingEligible();
			}
			$oflist['OfferListing'] = $ol;
		}

		//:: price
		$productSummary = $this->_getProductOfferSummaryByCondition( $item, $ConditionValue );
		$productPrice = $this->_getProductPriceBlocks( $Listing, $productSummary );
		//var_dump('<pre>',$productPrice ,'</pre>');

		if ( null !== $productPrice['ListPrice'] ) {
			$ret['ItemAttributes']['ListPrice'] = $productPrice['ListPrice'];
		}
		if ( null !== $productPrice['OfferListingPrice'] ) {
			$oflist['OfferListing'] = array_replace_recursive( $oflist['OfferListing'], array(
				'Price' => $productPrice['OfferListingPrice'],
			));
		}

		//:: OfferSummary
		$TotalOffers = 0;
		$ofsummary = array();
		if ( null !== $productPrice['OfferSummaryPrice'] ) {

			if ( null !== $productSummary && null !== $productSummary->getOfferCount() ) {
				$TotalOffers = (int) $productSummary->getOfferCount();
			}

			$ofsummary = array(
				"Lowest{$ConditionValue}Price" => $productPrice['OfferSummaryPrice'],
				"Total{$ConditionValue}" => $TotalOffers,
			);
		}

		$ret = array_replace_recursive( $ret, array(
			'OfferSummary' => $ofsummary,
			'Offers' => array(
				'TotalOffers' => $TotalOffers,
				'TotalOfferPages' => 1, //DEPRECATED
				'Offer' => $oflist,
			),
		));
		//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $ret;
	}

	// $item = single item ; $condition_value = condition value
	// get OfferSummary for a given condition value
	protected function _getProductOfferSummaryByCondition( $item, $condition_value='New' )
	{
		if ( null === $item ) {
			return null;
		}

		$Offers = $item->getOffers();

		$Summaries = null !== $Offers ? $Offers->getSummaries() : null;
		$Summaries = is_array($Summaries) ? array_values( $Summaries ) : array();

		$Summary = null;

		// get the summary for the right condition
		if ( empty($Summaries) || ! is_array($Summaries) ) {
			return $Summary;
		}

		$cc = 0;
		foreach ( $Summaries as $Summary ) {

			$Condition = $Summary->getCondition();
			if ( null !== $Condition && null !== $Condition->getValue() ) {

				$cur_value = (string) $Condition->getValue();
				if ( strtolower($condition_value) === strtolower($cur_value) ) {
					//return $Summary;
					break 1;
				}
			}
			$cc++;
		}
		//var_dump('<pre>', $cc, $Summary , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $Summary;
	}

	// $offer_listing = single item offer listing group ; $offer_summary = single item summary group
	protected function _getProductPriceBlocks( $offer_listing, $offer_summary=null )
	{
		// $noprice = array(
		// 	'Amount' => '0',
		// 	'CurrencyCode' => '',
		// 	'FormattedPrice' => '',
		// );
		// regular price = ListPrice
		// sale price = OfferListingPrice

		$thetree = array(
			'ListPrice' => array('SavingBasis'),
			'OfferListingPrice' => array('Price'),
			'OfferSummaryPrice' => array('LowestPrice'),
		);
		$theprice = array(
			'ListPrice' => null,
			'OfferListingPrice' => null,
			'OfferSummaryPrice' => null,
		);

		// foreach main
		foreach ( $thetree as $name => $tree ) {

			$__ = 'OfferSummaryPrice' === $name ? $offer_summary : $offer_listing;
			if ( null === $__ ) {
				continue 1;
			}

			foreach ( $tree as $mcall ) {

				$__ = $__->{"get{$mcall}"}();
				//var_dump('<pre>', "get{$mcall}", '</pre>');
				if ( null === $__ ) {
					continue 2; // continue in foreach main too
				}
			}

			$CurrencyCode = $__->getCurrency();
			$FormattedPrice = $__->getDisplayAmount();
			$Amount = $__->getAmount();

			if ( null === $Amount ) {
				continue 1;
			}

			if ( null === $CurrencyCode ) {
				$CurrencyCode = 'YYY';
			}
			if ( null === $FormattedPrice ) {
				$FormattedPrice = $Amount . " $CurrencyCode";
			}

			$Amount = number_format( $Amount, 2, '.', '' ); //mandatory 2 decimals (even if .00)
			$Amount = $Amount * 100;
			$Amount = number_format( $Amount, 0, '.', '' );

			$theprice["$name"] = array(
				'Amount' => $Amount,
				'CurrencyCode' => $CurrencyCode,
				'FormattedPrice' => $FormattedPrice,
			);
			//var_dump('<pre>',$theprice["$name"] ,'</pre>');
		}
		// end foreach main

		return $theprice;
	}

	// $item = single item
	// !!! use 'amz.helper.class.php' / get_product_price method, to get the product final price
	protected function _getProductPrice( $item, $pms=array() )
	{
		$pms = array_replace_recursive( array(
			'item_doformat' => false, // true = if you don't have the $item formatted, false otherwise
			'country' => null,
			'price_setup' => null, // amazon_or_sellers | only_amazon
		), $pms );
		//var_dump('<pre>', $pms , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		extract( $pms );

		if ( $item_doformat ) {
			$item = array_replace_recursive(
				array(),
				$this->getGroupMain( $item ),
				$this->getGroupOffers( $item ),
				$this->getGroupItemAttributes( $item ),
				array()
			);
		}

		if ( null === $country ) {
			$country = $this->amz_settings['country'];
		}
		$multiply_factor = 'co.jp' === $country ? 1 : 0.01;

		if ( null === $price_setup ) {
			$price_setup = isset($this->amz_settings["price_setup"]) && 'amazon_or_sellers' == $this->amz_settings["price_setup"] ? 'amazon_or_sellers' : 'only_amazon';
		}
		//$price_setup = 'amazon_or_sellers'; //DEBUG

		$product_meta = array('product' => array());
		$thisProd = $item;

		//:: price blocks
		$blockMain = array(
			'ListPrice' => isset($thisProd['ItemAttributes']['ListPrice'])
				? $thisProd['ItemAttributes']['ListPrice'] : array(),

			'OfferListingPrice' => isset($thisProd['Offers']['Offer']['OfferListing']['Price'])
				? $thisProd['Offers']['Offer']['OfferListing']['Price'] : array(),

			'OfferSummaryPrice' => isset($thisProd['OfferSummary']['LowestNewPrice'])
				? $thisProd['OfferSummary']['LowestNewPrice'] : array(),
		);

		foreach ( $blockMain as $kk => $vv ) {

			$AmountDef = in_array( $kk, array(
				'OfferSummaryPrice'
			) ) ? false : '';
			$Amount = isset($vv['Amount']) ? $vv['Amount'] * $multiply_factor : $AmountDef;
			$offerBlock["$kk"] = $Amount;

			$Currency = isset($vv['CurrencyCode']) ? $vv['CurrencyCode'] : '';
			$currencyBlock["$kk"] = $Currency;
		}
		$formattedBlock = $blockMain;

		if (
			'amazon_or_sellers' === $price_setup
			 && false !== $offerBlock['OfferSummaryPrice']
		) {
			$offerBlock['OfferListingPrice'] = $offerBlock['OfferSummaryPrice'];
			$currencyBlock['OfferListingPrice'] = $currencyBlock['OfferSummaryPrice'];
			$formattedBlock['OfferListingPrice'] = $blockMain['OfferSummaryPrice'];
		}

		//:: regular/
		$product_meta['product']['regular_price'] = $offerBlock['ListPrice'];
		$product_meta['product']['currency'] = $currencyBlock['ListPrice'];
		$product_meta['product']['regular_price_formatted'] = $formattedBlock['ListPrice'];

		//:: regular/ if we don't have a regular price or lowest offer price from offer is greater than current list price
		if ( 
			( 0.00 === (float) $offerBlock['ListPrice'] )
			|| ( $offerBlock['OfferListingPrice'] > $offerBlock['ListPrice'] )
		) {
			$product_meta['product']['regular_price'] = $offerBlock['OfferListingPrice'];
			$product_meta['product']['currency'] = $currencyBlock['OfferListingPrice'];
			$product_meta['product']['regular_price_formatted'] = $formattedBlock['OfferListingPrice'];
		}

		//:: sale/ from Offers or OfferSummary
		$product_meta['product']['sales_price'] = $offerBlock['OfferListingPrice'];
		$product_meta['product']['sales_price_formatted'] = $formattedBlock['OfferListingPrice'];
		// if offer price is higher than regular price, delete the offer
		if ( $offerBlock['OfferListingPrice'] >= $product_meta['product']['regular_price'] ) {
			unset($product_meta['product']['sales_price']);
			unset($product_meta['product']['sales_price_formatted']);
		}

		//:: return
		$ret['_currency'] = $product_meta['product']['currency'];
		if (
			isset($product_meta['product']['sales_price'])
			&& !empty($product_meta['product']['sales_price'])
		) {
			$ret['_sale_price'] = $product_meta['product']['sales_price'];
			$ret['_sale_price_formatted'] = $product_meta['product']['sales_price_formatted'];
		}
		// new sale price is 0
		else {
			$ret['_sale_price'] = '';
			$ret['_sale_price_formatted'] = '';
		}

		$ret['_regular_price'] = $product_meta['product']['regular_price'];
		$ret['_regular_price_formatted'] = $product_meta['product']['regular_price_formatted'];

		$ret['_price'] = $product_meta['product']['regular_price'];
		$ret['_price_formatted'] = $product_meta['product']['regular_price_formatted'];
		if (
			isset($product_meta['product']['sales_price'])
			&& '' !== trim($product_meta['product']['sales_price'])
		) {
			$ret['_price'] = $product_meta['product']['sales_price'];
			$ret['_price_formatted'] = $product_meta['product']['sales_price_formatted'];
		}

		return $ret;
	}

	// $item = single item
	protected function getGroupBrowseNodeInfo( $item )
	{
		$ret = array(
			'BrowseNodes' => array(),
		);

		if ( null === $item ) {
			return $ret;
		}

		$BNI = $item->getBrowseNodeInfo();

		//:: SalesRank
		$WebsiteSalesRank = null !== $BNI ? $BNI->getWebsiteSalesRank() : null;
		//var_dump('<pre>', $WebsiteSalesRank , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( null !== $WebsiteSalesRank && null !== $WebsiteSalesRank->getSalesRank() ) {
			$ret['SalesRank'] = (int) $WebsiteSalesRank->getSalesRank();
		}

		//:: BrowseNodes
		$BN = null !== $BNI ? $BNI->getBrowseNodes() : null;
		$BN = is_array($BN) ? array_values( $BN ) : array();
		//var_dump('<pre>', $BN , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( empty($BN) ) {
			return $ret;
		}

		$nodes = array();
		// foreach main
		foreach ( $BN as $curnode ) {

			$stat = $this->_getBrowseNodeAncestry( $curnode );
			if ( null !== $stat && is_array($stat) && isset($stat['BrowseNode']) ) {
				$nodes[] = $stat['BrowseNode'];
			}
		}
		// end foreach main

		if ( ! empty($nodes) ) {
			$ret['BrowseNodes'] = array(
				'BrowseNode' => $nodes,
			);
		}

		//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $ret;
	}

	// $node = single node
	protected function _getBrowseNodeAncestry( $node )
	{
		if ( null === $node ) {
			return null;
		}

		$Id = null !== $node ? $node->getId() : null;
		$DisplayName = null !== $node ? $node->getDisplayName() : null;
		$Ancestor = null !== $node ? $node->getAncestor() : null;

		if ( null === $Id || null === $DisplayName ) {
			return null;
		}

		$item['BrowseNode'] = array(
			'BrowseNodeId' => $Id,
			'Name' => $DisplayName,
			'Ancestors' => array(),
		);

		if ( null === $Ancestor ) {
			unset( $item['BrowseNode']['Ancestors'] );
			return $item;
		}

		$stat = $this->_getBrowseNodeAncestry( $Ancestor );
		$item['BrowseNode']['Ancestors'] = $stat;
		return $item;
	}

	// $item = single item
	protected function getGroupBrowseNodes( $item )
	{
		$ret = array();

		if ( null === $item ) {
			return $ret;
		}

		$Id = null !== $item ? $item->getId() : null;
		$DisplayName = null !== $item ? $item->getDisplayName() : null;

		if ( null === $Id || null === $DisplayName ) {
			return $ret;
		}

		$ret = array(
			'BrowseNodeId' => $Id,
			'Name' => $DisplayName,
			'Children' => array(),
		);


		//:: Children
		$Children = null !== $item ? $item->getChildren() : null;
		$Children = is_array($Children) ? array_values( $Children ) : array();
		//var_dump('<pre>', $Children , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( empty($Children) ) {
			return $ret;
		}

		$nodes = array();
		// foreach main
		foreach ( $Children as $node ) {

			if ( null === $node ) {
				return null;
			}

			$Id = null !== $node ? $node->getId() : null;
			$DisplayName = null !== $node ? $node->getDisplayName() : null;

			if ( null === $Id || null === $DisplayName ) {
				continue 1;
			}

			$nodes[] = array(
				'BrowseNodeId' => $Id,
				'Name' => $DisplayName,
			);
		}
		// end foreach main

		// compatibility with amz.helper.class.php / getBrowseNodesList method
		if ( count($nodes) === 1 ) {
			$nodes = $nodes[0];
		}

		if ( ! empty($nodes) ) {
			$ret['Children'] = array(
				'BrowseNode' => $nodes,
			);
		}

		//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $ret;
	}

	// $item = single item ; $VariationDimensions = for all variations
	protected function getGroupVariationAttributes( $item, $VariationDimensions=null )
	{
		$ret = array();

		if ( null === $item ) {
			return $ret;
		}

		//:: VariationAttributes
		$VariationAttributes = null !== $item ? $item->getVariationAttributes() : null;
		$VariationAttributes = is_array($VariationAttributes) ? array_values( $VariationAttributes ) : array();
		//var_dump('<pre>', $VariationAttributes , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( empty($VariationAttributes) ) {
			return $ret;
		}

		$attr = array();
		foreach ( $VariationAttributes as $VA ) {

			$Name = null !== $VA ? $VA->getName() : null;
			$Value = null !== $VA ? $VA->getValue() : null;

			if ( null === $Name || null === $Value ) {
				continue 1;
			}

			$attr[] = array(
				'Name' => $Name,
				'Value' => $Value,
			);
		}

		//:: overwrite VariationAttribute Name with DisplayName from VariationDimensions/VariationDimension
		//var_dump('<pre>', $VariationDimensions, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		$vardim = array();
		if ( null !== $VariationDimensions ) {
			$VariationDimensions = is_array($VariationDimensions) ? array_values( $VariationDimensions ) : array();
			if ( ! empty($VariationDimensions) ) {

				foreach ( $VariationDimensions as $VariationDimension ) {

					$Name = null !== $VariationDimension ? $VariationDimension->getName() : null;
					$DisplayName = null !== $VariationDimension ? $VariationDimension->getDisplayName() : null;

					if ( null === $Name || null === $DisplayName ) {
						continue 1;
					}

					$vardim["$Name"] = $DisplayName;
				}
			}
		}
		//var_dump('<pre>', $vardim , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( ! empty($vardim) ) {
			foreach ( $attr as $kk => $vv ) {

				$Name = $vv['Name'];
				$newName = isset($vardim["$Name"]) ? $vardim["$Name"] : null;

				if ( null !== $newName ) {
					$attr["$kk"] = array_replace_recursive( $vv, array(
						'Name' => $newName,
					));
				}
			}
		}

		if ( ! empty($attr) ) {
			$ret = array(
				'VariationAttributes' => array(
					'VariationAttribute' => $attr,
				),
			);
		}

		//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $ret;
	}

	// $VariationDimensions = for all variations
	protected function getGroupVariationDimensions( $VariationDimensions )
	{
		$ret = array();

		if ( null === $VariationDimensions ) {
			return $ret;
		}

		$vardim = array();
		$VariationDimensions = is_array($VariationDimensions) ? array_values( $VariationDimensions ) : array();
		if ( ! empty($VariationDimensions) ) {

			foreach ( $VariationDimensions as $VariationDimension ) {

				$Name = null !== $VariationDimension ? $VariationDimension->getName() : null;
				$DisplayName = null !== $VariationDimension ? $VariationDimension->getDisplayName() : null;

				if ( null === $DisplayName ) {
					continue 1;
				}

				$vardim[] = $DisplayName;
			}
		}
		//var_dump('<pre>', $vardim , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( ! empty($vardim) ) {
			$ret = array(
				'VariationDimensions' => array(
					'VariationDimension' => $vardim,
				),
			);
		}

		//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $ret;
	}

	// $VariationSummary = for all variations
	protected function getGroupVariationSummary( $VariationSummary )
	{
		$ret = array();

		if ( null === $VariationSummary ) {
			return $ret;
		}

		$Price = null !== $VariationSummary ? $VariationSummary->getPrice() : null;
		$productPrice = $this->_getProductPriceBlocks( null, $Price );

		//:: VariationSummary
		$ofsummary = array();
		if ( null !== $productPrice['OfferSummaryPrice'] ) {

			$ofsummary = array(
				"LowestPrice" => $productPrice['OfferSummaryPrice'],
			);
		}

		$ret = array_replace_recursive( $ret, array(
			'VariationSummary' => $ofsummary,
		));
		//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $ret;
	}

	// $VariationSummary = for all variations ; $items = array of Variations/Item/
	protected function getGroupVariationSummaryMinPrice( $VariationSummary, $items=null )
	{
		$ret = array();

		if ( ! is_array($items) ) {
			return $ret;
		}
		if ( isset($items['ASIN']) ) {
			$items = array( $items );
		}

		$curkey = -1; $curprice = null;
		foreach ( $items as $itemKey => $item ) {

			if ( null === $item ) {
				continue 1;
			}
			//var_dump('<pre>', $item, '</pre>');

			$itemPrice = $this->_getProductPrice( $item, array(
				'item_doformat' => false,
			));
			//var_dump('<pre>', $itemPrice , '</pre>');

			if (
				empty($itemPrice['_price'])
				|| (float) $itemPrice['_price'] <= 0.00
			) {
				continue 1;
			}

			if ( -1 === $curkey || $itemPrice['_price'] < $curprice['_price'] ) {
				$curkey = $itemKey;
				$curprice = $itemPrice;
			}
		}
		//var_dump('<pre>FINAL', $curkey, $curprice , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		if ( $curkey > -1 ) {

			$curprice = $curprice['_price_formatted'];
			$ofsummary = array(
				"LowestPrice" => array(
					'Amount' => $curprice['Amount'],
					'CurrencyCode' => $curprice['CurrencyCode'],
					'FormattedPrice' => $curprice['FormattedPrice'],
				),
			);

			$ret = array_replace_recursive( $ret, array(
				'VariationSummary' => $ofsummary,
			));
		}
		//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $ret;
	}

	// $VariationSummary = for all variations
	protected function getGroupVariationExtra( $VariationSummary )
	{
		$ret = array();

		if ( null === $VariationSummary ) {
			return $ret;
		}

		$PageCount = null !== $VariationSummary ? $VariationSummary->getPageCount() : null;
		$VariationCount = null !== $VariationSummary ? $VariationSummary->getVariationCount() : null;

		if ( null !== $VariationCount ) {
			$ret['TotalVariations'] = $VariationCount;
		}

		if ( null !== $PageCount ) {
			$ret['TotalVariationPages'] = $PageCount;
		}

		//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $ret;
	}

	// $response = full response from api
	protected function getGroupSearchExtra( $response )
	{
		$ret = array();

		if ( null === $response ) {
			return $ret;
		}

		$TotalResults = null !== $response ? $response->getTotalResultCount() : null;

		if ( null !== $TotalResults ) {
			$TotalResults = (int) $TotalResults;
			$ret['TotalResults'] = $TotalResults;

			$TotalPages = $TotalResults > 0 ? ceil( $TotalResults / 10 ) : 0;
			$ret['TotalPages'] = $TotalPages;
		}

		//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		return $ret;
	}

	//================================================
	//=== Migrate to API v5 /2019-October
	protected function _migrate2apiv5( $oldkey ) {

		$arr = array(
			//SearchItems - Search Parameters
			'MinimumPrice' 		=> 'MinPrice',
			'MaximumPrice' 		=> 'MaxPrice',
			'MerchantId' 		=> 'Merchant',
			'BrowseNode' 		=> 'BrowseNodeId',
			'Sort' 				=> 'SortBy',

			//SearchItems - Sort Values
			'PriceHighToLow' 	=> 'Price:HighToLow',
			'PriceLowToHigh' 	=> 'Price:LowToHigh',

			//GetItems
			'ItemId' 			=> 'ItemIds',

			//GetBrowseNodes
			'BrowseNodeId' 		=> 'BrowseNodeIds',
		);

		$ret = isset($arr["$oldkey"]) ? $arr["$oldkey"] : $oldkey;
		return $ret;
	}

	protected function _set_resources( $method ) {

		$def = array(
			'BROWSE_NODE_INFOBROWSE_NODES',
			'BROWSE_NODE_INFOBROWSE_NODESANCESTOR',
			'BROWSE_NODE_INFOBROWSE_NODESSALES_RANK',
			'BROWSE_NODE_INFOWEBSITE_SALES_RANK',
			'IMAGESPRIMARYSMALL',
			'IMAGESPRIMARYMEDIUM',
			'IMAGESPRIMARYLARGE',
			'IMAGESVARIANTSSMALL',
			'IMAGESVARIANTSMEDIUM',
			'IMAGESVARIANTSLARGE',
			'ITEM_INFOBY_LINE_INFO',
			'ITEM_INFOCONTENT_INFO',
			'ITEM_INFOCONTENT_RATING',
			'ITEM_INFOCLASSIFICATIONS',
			'ITEM_INFOEXTERNAL_IDS',
			'ITEM_INFOFEATURES',
			'ITEM_INFOMANUFACTURE_INFO',
			'ITEM_INFOPRODUCT_INFO',
			'ITEM_INFOTECHNICAL_INFO',
			'ITEM_INFOTITLE',
			'ITEM_INFOTRADE_IN_INFO',
			'OFFERSLISTINGSAVAILABILITYMAX_ORDER_QUANTITY',
			'OFFERSLISTINGSAVAILABILITYMESSAGE',
			'OFFERSLISTINGSAVAILABILITYMIN_ORDER_QUANTITY',
			'OFFERSLISTINGSAVAILABILITYTYPE',
			'OFFERSLISTINGSCONDITION',
			'OFFERSLISTINGSCONDITIONSUB_CONDITION',
			'OFFERSLISTINGSDELIVERY_INFOIS_AMAZON_FULFILLED',
			'OFFERSLISTINGSDELIVERY_INFOIS_FREE_SHIPPING_ELIGIBLE',
			'OFFERSLISTINGSDELIVERY_INFOIS_PRIME_ELIGIBLE',
			'OFFERSLISTINGSDELIVERY_INFOSHIPPING_CHARGES',
			'OFFERSLISTINGSIS_BUY_BOX_WINNER',
			'OFFERSLISTINGSLOYALTY_POINTSPOINTS',
			'OFFERSLISTINGSMERCHANT_INFO',
			'OFFERSLISTINGSPRICE',
			'OFFERSLISTINGSPROGRAM_ELIGIBILITYIS_PRIME_EXCLUSIVE',
			'OFFERSLISTINGSPROGRAM_ELIGIBILITYIS_PRIME_PANTRY',
			'OFFERSLISTINGSPROMOTIONS',
			'OFFERSLISTINGSSAVING_BASIS',
			'OFFERSSUMMARIESHIGHEST_PRICE',
			'OFFERSSUMMARIESLOWEST_PRICE',
			'OFFERSSUMMARIESOFFER_COUNT',
			'PARENT_ASIN',
			'RENTAL_OFFERSLISTINGSAVAILABILITYMAX_ORDER_QUANTITY',
			'RENTAL_OFFERSLISTINGSAVAILABILITYMESSAGE',
			'RENTAL_OFFERSLISTINGSAVAILABILITYMIN_ORDER_QUANTITY',
			'RENTAL_OFFERSLISTINGSAVAILABILITYTYPE',
			'RENTAL_OFFERSLISTINGSBASE_PRICE',
			'RENTAL_OFFERSLISTINGSCONDITION',
			'RENTAL_OFFERSLISTINGSCONDITIONSUB_CONDITION',
			'RENTAL_OFFERSLISTINGSDELIVERY_INFOIS_AMAZON_FULFILLED',
			'RENTAL_OFFERSLISTINGSDELIVERY_INFOIS_FREE_SHIPPING_ELIGIBLE',
			'RENTAL_OFFERSLISTINGSDELIVERY_INFOIS_PRIME_ELIGIBLE',
			'RENTAL_OFFERSLISTINGSDELIVERY_INFOSHIPPING_CHARGES',
			'RENTAL_OFFERSLISTINGSMERCHANT_INFO',
		);

		$arr = array();

		//GetBrowseNodesResource
		$arr['GetBrowseNodes'] = array(
			GetBrowseNodesResource::ANCESTOR,
			GetBrowseNodesResource::CHILDREN,
		);

		//GetItemsResource
		$arr['GetItems'] = array_merge( $def, array() );
		array_walk( $arr['GetItems'], function(&$value) {
			$value = constant( GetItemsResource::class.'::'.$value );
		});

		//SearchItemsResource
		$arr['SearchItems'] = array_merge( $def, array(
			'SEARCH_REFINEMENTS',
		));
		array_walk( $arr['SearchItems'], function(&$value) {
			$value = constant( SearchItemsResource::class.'::'.$value );
		});

		//GetVariationsResource
		$arr['GetVariations'] = array_merge( $def, array(
			'VARIATION_SUMMARYPRICEHIGHEST_PRICE',
			'VARIATION_SUMMARYPRICELOWEST_PRICE',
			'VARIATION_SUMMARYVARIATION_DIMENSION',
		));
		array_walk( $arr['GetVariations'], function(&$value) {
			$value = constant( GetVariationsResource::class.'::'.$value );
		});

		//var_dump('<pre>', $arr , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		$ret = isset($arr["$method"]) ? $arr["$method"] : array();
		return $ret;
	}

	//================================================
	//=== MISC
	protected function _filterItemId( $itemid )
	{
		if ( ! is_array($itemid)) {
			$itemid = explode(',', $itemid);
		}
		if ( is_array($itemid)) {
			$itemid = array_map('trim', $itemid);
			$itemid = array_filter($itemid);
			foreach ($itemid as $key => $val) {
				$itemid["$key"] = urlencode ( utf8_encode($val) );
			}
			//$itemid = implode(',', $itemid);
		}
		return $itemid;
	}

	protected function validateNodeId($nodeId)
	{
		if (false === is_numeric($nodeId))
		{
			throw new InvalidArgumentException("{$this->apiMsgPrefix}BrowseNodeId has to be a positive Integer.");
		}

		return true;
	}

	//================================================
	//=== TESTING
	protected function _testingGetResponse( $method )
	{
		global $plugin_path;

		$filepath = $plugin_path . "lib/scripts/amazon/v5testing/{$method}.json";
		$filecontent = file_get_contents( $filepath );
		$filecontent = json_decode($filecontent);
		//var_dump('<pre>', $method, $filecontent , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

		switch ($method) {

			case 'SearchItems':
				$returnType = '\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsResponse';
				break;

			case 'GetItems':
				$returnType = '\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsResponse';
				break;

			case 'GetVariations':
				$returnType = '\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetVariationsResponse';
				break;

			case 'GetBrowseNodes':
				$returnType = '\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetBrowseNodesResponse';
				break;
		}

		$fileresponse = $filecontent;
		//if ( 'GetBrowseNodes' !== $method ) {
			$fileresponse = [
				ObjectSerializer::deserialize(
					$filecontent,
					$returnType,
					[]
				),
				200, //$response->getStatusCode(),
				array(), //$response->getHeaders()
			];
		//}
		//var_dump('<pre>', $method, $returnType, $fileresponse , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		list($ret) = $fileresponse;
		return $ret;
	}

} } // end class exists!