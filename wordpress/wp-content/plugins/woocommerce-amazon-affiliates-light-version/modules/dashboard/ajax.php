<?php
/*
* Define class WooZoneLiteDashboardAjax
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLiteDashboardAjax') != true) {
	class WooZoneLiteDashboardAjax extends WooZoneLiteDashboard
	{
		public $the_plugin = null;
		private $module_folder = null;
		
		private static $sql_chunk_limit = 5000;
		
		/*
		* Required __construct() function that initalizes the AA-Team Framework
		*/
		public function __construct( $the_plugin=array() )
		{
			$this->the_plugin = $the_plugin;
			$this->module_folder = $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'modules/dashboard/';
			
			// ajax  helper
			add_action('wp_ajax_WooZoneLiteDashboardRequest', array( &$this, 'ajax_request' ));
		}
		
		/*
		* ajax_request, method
		* --------------------
		*
		* this will create requests to 404 table
		*/
		public function ajax_request()
		{
			$return = array();
			
			$actions = isset($_REQUEST['sub_actions']) ? explode(",", $_REQUEST['sub_actions']) : '';
			
			if( in_array( 'aateam_products', $actions) ){
				
				$sites = array('codecanyon', 'themeforest', 'graphicriver');
				$html = array();
				foreach( $sites as $site ){
					$api_url = 'http://marketplace.envato.com/api/edge/new-files-from-user:AA-Team,%s.json';
					
					$response_data = $this->getRemote( sprintf( $api_url, $site)  );
					
					// reorder the array
					if( isset($response_data["new-files-from-user"]) && count($response_data["new-files-from-user"]) > 0 ){
						$data = array();
						$__arr = $response_data["new-files-from-user"];
						$__newarr = array(); $__newarrSales = array();
						foreach ($__arr as $k => $v) {
							$key = $v['id'];
							$__newarr["$key"] = $v;
							$__newarrSales["$key"] = $v['sales'];
						}
						asort($__newarrSales, SORT_NUMERIC);
						foreach ($__newarrSales as $k => $v) {
							$__newarrSales["$k"] = $__newarr["$k"];
						}
						$reversed_data = array_reverse($__newarrSales, true);
						
						if( count($reversed_data) > 0 ){
							$html[] = '<div class="WooZoneLite-aa-products-container" id="aa-prod-' . ( $site ) . '">';
							$html[] = 	'<ul style="width: ' . ( count($reversed_data) * 135 ) .  'px">';
							foreach ( $reversed_data as $item ){
								$html[] = 	'<li>';
								$html[] = 		'<a target="_blank" href="' . ( $item['url'] ) . '?rel=AA-Team" data-preview="' . ( $item['live_preview_url'] ) . '">';
								$html[] = 			'<img src="' . ( $item['thumbnail'] ) . '" width="80" alt="' . ( $item['item'] ) . '">';
								$html[] = 			'<span class="the-rate-' . ( ceil( $item['rating'] ) ) . '"></span>';
								$html[] = 			'<strong>$' . ( $item['cost'] ) . '</strong>';
								$html[] = 		'</a>';
								$html[] = 	'</li>';
							}
							$html[] = 	'</ul>';			
							$html[] = '</div>';	
						}
						
					}
				}

				$return['aateam_products'] = array(
					'status' => 'valid',
					'html' => implode("\n", $html)
				);
			}

			if( in_array( 'products_performances', $actions) ){
				
				$prod_per_page = isset($_REQUEST['prod_per_page']) ? $_REQUEST['prod_per_page'] : 12;
				$products_response = $this->getPublishProductsWidthStatus( $prod_per_page );
				
				$html[] = '<div class="panel-body WooZoneLite-panel-body">';
				if( !isset($products_response['products']) || count($products_response['products']) == 0 ){
					$html[] = '<div class="WooZoneLite-callout WooZoneLite-callout-info">You need to import some Amazon products first!</div>';
				}
				else{
					
					/*$html[] = '<div class="WooZoneLite-products-summary">';
					$html[] = '<div class="the-item-stat">
									<span style="background-color:#a46497;" class="WooZoneLite-summary-icon">
										<img src="' . ( $this->module_folder . 'images/' ) . 'total_products.png">
									</span>
									<span class="WooZoneLite-summary-text">
										<span>' . ( $products_response['stats']['nb_products'] ) . '</span>
										<span>Total Number of products</span>
									</span>
								</div>';
								
					$html[] = '<div class="the-item-stat">
									<span style="background-color:#a46497;" class="WooZoneLite-summary-icon">
										<img src="' . ( $this->module_folder . 'images/' ) . 'view.png">
									</span>
									<span class="WooZoneLite-summary-text">
										<span>' . ( $products_response['stats']['total_hits'] ) . '</span>
										<span>Total products views</span>
									</span>
								</div>';
					
					$html[] = '<div class="the-item-stat">
									<span style="background-color:#a46497;" class="WooZoneLite-summary-icon">
										<img src="' . ( $this->module_folder . 'images/' ) . 'cart_add.png">
									</span>
									<span class="WooZoneLite-summary-text">
										<span>' . ( $products_response['stats']['total_addtocart'] ) . '</span>
										<span>Total added to cart</span>
									</span>
								</div>';
								
					$html[] = '<div class="the-item-stat">
									<span style="background-color:#a46497;" class="WooZoneLite-summary-icon">
										<img src="' . ( $this->module_folder . 'images/' ) . 'redirect_amazon.png">
									</span>
									<span class="WooZoneLite-summary-text">
										<span>' . ( $products_response['stats']['total_redirect_to_amazon'] ) . '</span>
										<span>Total redirected to Amazon</span>
									</span>
								</div>';
					
								
					$html[] = '</div>';*/
	
					$html[] = 	'<ul class="WooZoneLite-top-products">';
					
					if( isset($products_response, $products_response['products']) && count($products_response['products']) > 0 ) {
						$pos = 0;
	 
						foreach ($products_response['products'] as $product ) {

							$provider = $product['provider'];
							$provider_ = $this->the_plugin->prodid_get_provider($provider);

							$html[] = 		'<li>';
							$html[] = 			'<div class="WooZoneLite-prod-position"><span>#' . ( ++$pos ) . '</span></div>';
							$html[] = 			'<a href="' . ( admin_url('post.php?post=' . ( $product['id'] ) . '&action=edit') ) . '" target="_blank" class="WooZoneLite-the-product">';
							if( get_the_post_thumbnail( $product['id'], array(75, 75) ) != '' ) {
								$product_thumb = get_the_post_thumbnail( $product['id'], array(75, 75) );
							} else {
								$product_thumb = '<img class="no-image-available" src="'. $this->the_plugin->cfg['paths']['plugin_dir_url'] .'no-image.jpg" alt="no-image-available" />';
							}
							//var_dump('<pre>',$product_thumb,'</pre>');
							$product_thumb = $this->the_plugin->imagesfix->_parse_page_fix_amazon( $product_thumb );
  
							$html[] = 				'<div class="WooZoneLite-the-product-image">' . ( $product_thumb ) . '</div>';
							$html[] = 				'<span>Views: <strong>' . ( $product['hits'] ) . '</strong></span>';
							if ( 'ebay' !== $provider_ ) {
								$html[] = 			'<span>Added to cart: <strong>' . ( $product['addtocart'] ) . '</strong></span>';
							}
							$html[] = 				'<span>Redirect to ' . ( $provider_ ) . ': <strong>' . ( $product['redirect_to_amazon'] ) . '</strong></span>';
							$html[] = 			'</a>';
							$html[] = 		'</li>';
						}
					}
					$html[] = 	'</ul>';
				}
				$html[] = 	'</div>';
				
				$return['products_performances'] = array(
					'status' => 'valid',
					'html' => implode("\n", $html),
					'data' => $products_response['stats']
				);
			}
			

			die(json_encode($return));
		}

		// Old version
		private function _getPublishProductsWidthStatus( $limit=0 )
		{
			$ret = array();

			$key = '_amzASIN';
			$_key = $key;
			if ( $_key == '_amzASIN' ) $key = '_amzaff_prodid';

			$args = array();
			$ret['products'] = array();
			$ret['stats']['nb_products'] = 0;
			$ret['stats']['total_hits'] = 0;
			$ret['stats']['total_redirect_to_amazon'] = 0;
			$ret['stats']['total_addtocart'] = 0;
				
			$args['post_type'] = array('product'); //, 'product_variation'
	
			$args['meta_key'] = $key;
			$args['meta_value'] = '';
			$args['meta_compare'] = '!=';
	
			// show all posts
			$args['fields'] = 'ids';
			$args['posts_per_page'] = '-1';
			
			$loop = new WP_Query( $args );
			$cc = 0;
			 
			if( count($loop->posts) > 0 ){
				
				$stats_query = "SELECT post_id, meta_key, meta_value FROM " . (  $this->the_plugin->db->prefix ) . "postmeta WHERE 1=1 AND post_id IN (" . ( implode(",", $loop->posts) ) . ")";
				$stats_query .= " AND ( meta_key='_amzaff_redirect_to_amazon' ";
				$stats_query .= " OR meta_key='_amzaff_addtocart' ";
				$stats_query .= " OR meta_key='_amzaff_hits' )";  
				
				$stats_results = $this->the_plugin->db->get_results( $stats_query, ARRAY_A );
				
				$products_status = array();
				// reodering here
				if( count($stats_results) > 0 ){
					foreach ($stats_results as $row ) {
						$products_status[$row['post_id']][$row['meta_key']] = $row['meta_value'];
					}
				}
				
				foreach ($loop->posts as $post) {
					
					$redirect_to_amazon = ( isset($products_status[$post]['_amzaff_redirect_to_amazon']) ? (int) $products_status[$post]['_amzaff_redirect_to_amazon'] : 0 );
					$addtocart = ( isset($products_status[$post]['_amzaff_addtocart']) ? (int) $products_status[$post]['_amzaff_addtocart'] : 0 );
					$hits = ( isset($products_status[$post]['_amzaff_hits']) ? (int) $products_status[$post]['_amzaff_hits'] : 0 );
					$score = ($redirect_to_amazon * 3) + ($addtocart * 2) + ($hits * 1);
					
					$ret['products'][$post] = array(
						'id' => $post,
						'score' => $score,
						'redirect_to_amazon' => $redirect_to_amazon,
						'addtocart' => $addtocart,
						'hits' => $hits
					);
					
					$ret['stats']['nb_products'] = $ret['stats']['nb_products'] + 1;
					$ret['stats']['total_hits'] = $ret['stats']['total_hits'] + $hits;
					$ret['stats']['total_redirect_to_amazon'] = $ret['stats']['total_redirect_to_amazon'] + $redirect_to_amazon;
					$ret['stats']['total_addtocart'] = $ret['stats']['total_addtocart'] + $addtocart;
				}
			}
			
			if( count($ret['products']) > 0 ){
				// reorder the products as a top
				$ret['products'] = $this->sort_hight_to_low( $ret['products'], 'score' );
				
				// limit the return, if request
				if( (int) $limit != 0 ){
					$ret['products'] = array_slice($ret['products'], 0, $limit);
				}
			}
			 
			return $ret;
		}

		// New version /update on 2015.05.05
		private function _getPublishProductsWidthStatus_old2( $limit=0 )
		{
			global $wpdb;

			$ret = array();
			$ret['products'] = array();
			$ret['stats']['nb_products'] = 0;
			$ret['stats']['total_hits'] = 0;
			$ret['stats']['total_redirect_to_amazon'] = 0;
			$ret['stats']['total_addtocart'] = 0;

			$key = '_amzASIN';
			$_key = $key;
			if ( $_key == '_amzASIN' ) $key = '_amzaff_prodid';

			$prod_key = array( '_amzASIN', '_amzaff_prodid' );
			$prod_key_ = "'_amzASIN', '_amzaff_prodid'";

			$filterbyprov_clause = "( ( pm.meta_key = '_amzaff_prodid' AND pm.meta_value not regexp '^amz-' ) OR ( pm.meta_key = '_amzASIN' AND ! isnull(pm.meta_value) ) )";

			// OLD BUGGED QUERY
			// get products (simple or just parents without variations)
			//$sql = "SELECT p.ID, p.post_title, p.post_parent, p.post_date, if( pm.meta_value REGEXP '^(amz|eby)-', SUBSTRING( pm.meta_value, 1, 3 ), 'amz' ) AS provider FROM $wpdb->posts as p LEFT JOIN $wpdb->postmeta as pm ON p.ID = pm.post_id  LEFT JOIN $wpdb->postmeta as pm2 ON p.ID = pm2.post_id WHERE 1=1 AND ( ( pm.meta_key = '$key' and ( !isnull(pm.meta_value) AND pm.meta_value != '' ) ) OR ( pm.meta_key = '$_key' and ( !isnull(pm.meta_value) AND pm.meta_value != '' ) ) ) AND p.post_status = 'publish' AND p.post_parent = 0 AND p.post_type = 'product' ORDER BY p.ID ASC;";

			// [FIXED] query - on 2019-jun-21
			$sql = "SELECT p.ID, p.post_title, p.post_parent, p.post_date, if( pm.meta_value REGEXP '^(amz|eby)-', SUBSTRING( pm.meta_value, 1, 3 ), 'amz' ) AS provider FROM $wpdb->posts as p LEFT JOIN $wpdb->postmeta as pm ON p.ID = pm.post_id AND pm.meta_key in ($prod_key_) WHERE 1=1 AND p.post_status = 'publish' AND p.post_parent = 0 AND p.post_type = 'product' AND $filterbyprov_clause ORDER BY p.ID ASC;";
			//var_dump('<pre>',$sql ,'</pre>');
			$res = $wpdb->get_results( $sql, OBJECT_K );

			// OLD BUGGED QUERY
			// get product variations (only childs, no parents)
			//$sql_childs = "SELECT p.ID, p.post_title, p.post_parent, p.post_date FROM $wpdb->posts as p LEFT JOIN $wpdb->postmeta as pm ON p.ID = pm.post_id LEFT JOIN $wpdb->postmeta as pm2 ON p.ID = pm2.post_id WHERE 1=1 AND ( ( pm.meta_key = '$key' and ( !isnull(pm.meta_value) AND pm.meta_value != '' ) ) OR ( pm.meta_key = '$_key' and ( !isnull(pm.meta_value) AND pm.meta_value != '' ) ) ) AND p.post_status = 'publish' AND p.post_parent > 0 AND p.post_type = 'product_variation' ORDER BY p.ID ASC;";

			// [FIXED] query - on 2019-jun-21
			$sql_childs = "SELECT p.ID, p.post_title, p.post_parent, p.post_date FROM $wpdb->posts as p LEFT JOIN $wpdb->postmeta as pm ON p.ID = pm.post_id AND pm.meta_key in ($prod_key_) WHERE 1=1 AND p.post_status = 'publish' AND p.post_parent > 0 AND p.post_type = 'product_variation' AND $filterbyprov_clause ORDER BY p.ID ASC;";
			//var_dump('<pre>',$sql_childs ,'</pre>');
			$res_childs = $wpdb->get_results( $sql_childs, OBJECT_K );
			
			//var_dump('<pre>', $sql, $sql_childs, '</pre>'); die('debug...'); 
			if ( empty($res) && empty($res_childs) ) return $ret;
			
			// array with parents and their associated childrens
			$parent2child = array();
			foreach ($res_childs as $id => $val) {
				$parent = $val->post_parent;
				
				if ( !isset($parent2child["$parent"]) ) {
					$parent2child["$parent"] = array();
				}
				$parent2child["$parent"]["$id"] = $val; 
			}

			// products IDs
			$prods = array_merge(array(), array_keys($res), array_keys($res_childs));
			$prods = array_unique($prods);

			// get ASINs
			/*$prods2asin = array();
			foreach (array_chunk($prods, self::$sql_chunk_limit, true) as $current) {

				$currentP = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $current));
				$sql_getasin = "SELECT pm.post_id, pm.meta_value FROM $wpdb->postmeta as pm WHERE 1=1 AND pm.meta_key = '_amzASIN' AND pm.post_id IN ($currentP) ORDER BY pm.post_id ASC;";
				$res_getasin = $wpdb->get_results( $sql_getasin, OBJECT_K );
				$prods2asin = $prods2asin + $res_getasin; //array_replace($prods2asin, $res_getasin);
			}*/
			
			$__meta_toget = array('_amzaff_hits', '_amzaff_addtocart', '_amzaff_redirect_to_amazon');

			// get sync last date & sync hits
			foreach ( (array) $__meta_toget as $meta) {
				$prods2meta["$meta"] = array();

				foreach (array_chunk($prods, self::$sql_chunk_limit, true) as $current) {
	
					$currentP = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $current));
	
					$sql_getmeta = "SELECT pm.post_id, pm.meta_value FROM $wpdb->postmeta as pm WHERE 1=1 AND pm.meta_key = '$meta' AND pm.post_id IN ($currentP) ORDER BY pm.post_id ASC;";
					//var_dump('<pre>',$sql_getmeta ,'</pre>');
					$res_getmeta = $wpdb->get_results( $sql_getmeta, OBJECT_K );
					$prods2meta["$meta"] = $prods2meta["$meta"] + $res_getmeta; //array_replace($prods2meta["$meta"], $res_getmeta);
				}
			}
			
			if ( !empty($res) ) {
				foreach ($res as $id => $val) {

					$provider = isset($val->provider) ? $val->provider : 'zzz';
					
					$redirect_to_amazon = isset($prods2meta['_amzaff_redirect_to_amazon']["$id"]) ? (int) $prods2meta['_amzaff_redirect_to_amazon']["$id"]->meta_value : 0;
					$addtocart = isset($prods2meta['_amzaff_addtocart']["$id"]) ? (int) $prods2meta['_amzaff_addtocart']["$id"]->meta_value : 0;
					$hits = isset($prods2meta['_amzaff_hits']["$id"]) ? (int) $prods2meta['_amzaff_hits']["$id"]->meta_value : 0;
					
					//if ( $id == 2906 ) {
					//    var_dump('<pre>',$id, 0, $prods2meta['_amzaff_redirect_to_amazon']["$id"]->meta_value, $prods2meta['_amzaff_addtocart']["$id"]->meta_value, $prods2meta['_amzaff_hits']["$id"]->meta_value,'</pre>');
					//}
					
					if ( isset($parent2child["$id"]) && !empty($parent2child["$id"]) ) {
						$childs = $parent2child["$id"];
						//$childs_nb = count($childs);
						foreach ($childs as $childId => $childVal) {
  
							//if ( $id == 2906 ) {
							//    var_dump('<pre>',$id, $childId, $prods2meta['_amzaff_redirect_to_amazon']["$childId"]->meta_value, $prods2meta['_amzaff_addtocart']["$childId"]->meta_value, $prods2meta['_amzaff_hits']["$childId"]->meta_value,'</pre>');  
							//}

							$redirect_to_amazon += isset($prods2meta['_amzaff_redirect_to_amazon']["$childId"]) ? (int) $prods2meta['_amzaff_redirect_to_amazon']["$childId"]->meta_value : 0;
							$addtocart += isset($prods2meta['_amzaff_addtocart']["$childId"]) ? (int) $prods2meta['_amzaff_addtocart']["$childId"]->meta_value : 0;
							$hits += isset($prods2meta['_amzaff_hits']["$childId"]) ? (int) $prods2meta['_amzaff_hits']["$childId"]->meta_value : 0;
						}
					}

					$score = ($redirect_to_amazon * 3) + ($addtocart * 2) + ($hits * 1);

					$ret['products'][$id] = array(
						'id' => $id,
						'score' => $score,
						'redirect_to_amazon' => $redirect_to_amazon,
						'addtocart' => $addtocart,
						'hits' => $hits,
						'provider' => $provider,
					);
					
					$ret['stats']['nb_products'] = $ret['stats']['nb_products'] + 1;
					$ret['stats']['total_hits'] = $ret['stats']['total_hits'] + $hits;
					$ret['stats']['total_redirect_to_amazon'] = $ret['stats']['total_redirect_to_amazon'] + $redirect_to_amazon;
					$ret['stats']['total_addtocart'] = $ret['stats']['total_addtocart'] + $addtocart;
				}
			}
			
			if( count($ret['products']) > 0 ){
				// reorder the products as a top
				$ret['products'] = $this->sort_hight_to_low( $ret['products'], 'score' );
				
				// limit the return, if request
				if( (int) $limit != 0 ){
					$ret['products'] = array_slice($ret['products'], 0, $limit);
				}
			}
			 
			return $ret;
		}

		// New version made on 2019-jun-21
		private function getPublishProductsWidthStatus( $limit=0 )
		{
			global $wpdb;

			$ret = array();
			$ret['products'] = array();
			$ret['stats']['nb_products'] = 0;
			$ret['stats']['total_hits'] = 0;
			$ret['stats']['total_redirect_to_amazon'] = 0;
			$ret['stats']['total_addtocart'] = 0;

			$key = '_amzASIN';
			$_key = $key;
			if ( $_key == '_amzASIN' ) $key = '_amzaff_prodid';

			$prod_key = array( '_amzASIN', '_amzaff_prodid' );
			$prod_key_ = "'_amzASIN', '_amzaff_prodid'";

			$filterbyprov_clause = "( ( pm.meta_key = '_amzaff_prodid' AND pm.meta_value not regexp '^amz-' ) OR ( pm.meta_key = '_amzASIN' AND ! isnull(pm.meta_value) ) )";


			$products = array();
			$__meta_toget = array('_amzaff_hits', '_amzaff_addtocart', '_amzaff_redirect_to_amazon');
			foreach ( (array) $__meta_toget as $meta) {

				$sql = trim("
					SELECT
						p.ID, p.post_title, p.post_parent, p.post_date,
						if( pm.meta_value REGEXP '^(amz|eby)-', SUBSTRING( pm.meta_value, 1, 3 ), 'amz' ) AS provider
						,SUM( pm2.meta_value ) AS total
					FROM $wpdb->posts as p
					LEFT JOIN $wpdb->postmeta as pm ON pm.post_id = p.ID AND pm.meta_key in ($prod_key_)
					LEFT JOIN $wpdb->postmeta as pm2 ON pm2.post_id = p.ID
					WHERE 1=1
						AND pm2.meta_key = '$meta'
						AND p.post_status = 'publish'
						AND (
							( p.post_parent = 0 AND p.post_type = 'product' ) OR
							( p.post_parent > 0 AND p.post_type = 'product_variation' )
						)
						AND ( ( pm.meta_key = '_amzaff_prodid' AND pm.meta_value not regexp '^amz-' ) OR ( pm.meta_key = '_amzASIN' AND ! isnull(pm.meta_value) ) )
					GROUP BY pm.post_id
					ORDER BY pm.post_id asc
					;
				");
				//var_dump('<pre>',$sql ,'</pre>');
				$res = $wpdb->get_results( $sql, OBJECT_K );
				if ( ! empty($res) && is_array($res) ) {

					foreach ($res as $id => $val) {

						$provider = isset($val->provider) ? $val->provider : 'zzz';
						$current_meta = str_replace('_amzaff_', '', $meta);
						$current_total = isset($val->total) ? $val->total : 0;

						if ( ! isset($products[$id]) ) {
							$products[$id] = array(
								'id' => $id,
								'provider' => $provider,
								'score' => 0,
								'redirect_to_amazon' => 0,
								'addtocart' => 0,
								'hits' => 0,
							);
							$products[$id]["$current_meta"] = $current_total;
						}
						else {
							$products[$id]["$current_meta"] = $current_total;
						}
					}
				}
			}
			//var_dump('<pre>', $products , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			foreach ($products as $id => $val) {

				$redirect_to_amazon = $val['redirect_to_amazon'];
				$addtocart = $val['addtocart'];
				$hits = $val['hits'];

				$score = ($redirect_to_amazon * 3) + ($addtocart * 2) + ($hits * 1);

				$products[$id]['score'] = $score;

				$ret['stats']['nb_products'] = $ret['stats']['nb_products'] + 1;
				$ret['stats']['total_hits'] = $ret['stats']['total_hits'] + $hits;
				$ret['stats']['total_redirect_to_amazon'] = $ret['stats']['total_redirect_to_amazon'] + $redirect_to_amazon;
				$ret['stats']['total_addtocart'] = $ret['stats']['total_addtocart'] + $addtocart;
			}
			$ret['products'] = $products;
			//var_dump('<pre>', $products , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			if( count($ret['products']) > 0 ){
				// reorder the products as a top
				$ret['products'] = $this->sort_hight_to_low( $ret['products'], 'score' );
				
				// limit the return, if request
				if( (int) $limit != 0 ){
					$ret['products'] = array_slice($ret['products'], 0, $limit);
				}
			}
			 
			return $ret;
		}
		
		function sort_hight_to_low( $a, $subkey )
		{
			foreach($a as $k=>$v) {
				$b[$k] = strtolower($v[$subkey]);
			}
			arsort($b);
			foreach($b as $key=>$val) {
				$c[$key] = $a[$key];
			}
			return $c;
		}
		
		
	
		/**
		 * $cache_lifetime in minutes
		 */
		private function getRemote( $the_url, $cache_lifetime=60 )
		{
			// try to get from cache
			$request_alias = 'WooZoneLite_' . md5($the_url);
			$from_cache = get_option( $request_alias );
			
			if( $from_cache != false ){
				if( time() < ( $from_cache['when'] + ($cache_lifetime * 60) )){
					return $from_cache['data'];
				}
			}
			$response = wp_remote_get( $the_url, array('user-agent' => "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0", 'timeout' => 10) ); 
			
			// If there's error
			if ( is_wp_error( $response ) ){
				return array(
					'status' => 'invalid'
				);
			}
			$body = wp_remote_retrieve_body( $response );
			
			$response_data = json_decode( $body, true );
			
			// overwrite the cache data 
			update_option( $request_alias, array(
				'when' => time(),
				'data' => $response_data
			) );
				
			return $response_data;
		}
	}
}