<?php
/*
* Define class WooZoneLiteCacheit_Init
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLiteCacheit_Init') != true) {
	class WooZoneLiteCacheit_Init
	{
		const VERSION = '1.0';
		
		public $the_plugin = null;
		public $is_admin = null;

		public $amz_settings = array();

		static protected $_instance;

		public $alias;
		public $localizationName;

		// cached amazon images from CDN & maybe other stuff...
		public $cacheit = null;


		/*
		 * Required __construct() function that initalizes the AA-Team Framework
		 */
		public function __construct( $parent )
		{
			$this->the_plugin = $parent;
			$this->is_admin = $this->the_plugin->is_admin;
			
			$this->amz_settings = $this->the_plugin->amz_settings;

			$this->alias = $this->the_plugin->alias;
			$this->localizationName = $this->the_plugin->localizationName;
		}
		
		/**
		 * Singleton pattern
		 *
		 * @return Singleton instance
		 */
		static public function getInstance( $parent )
		{
			if (!self::$_instance) {
				self::$_instance = new self($parent);
			}
			
			return self::$_instance;
		}



		/**
		 * Cacheit Init
		 */
		public function cacheitInit() {
			$is_deactivated = true;

			if ( $is_deactivated ) {
				$cache_type = 'none';
			}
			else {
				$cache_type = 'file';
				if ( isset($this->amz_settings['cache_remote_images']) ) {
					if ( 'none' == $this->amz_settings['cache_remote_images'] ) {
						$cache_type = 'none';
					}
					else {
						$cache_type = $this->amz_settings['cache_remote_images'];
					}
				}
			}

			$levels_used = array();
			if ( 'none' != $cache_type ) {
				$levels_used = array('session', $cache_type);
			}
			//$levels_used = array('wpoption'); //array('session', 'wpoption', 'file') //DEBUG

			$cache_pms = array(
				'do_load'					=> true,
				'levels_used'				=> $levels_used,
				'cache_keymain'				=> array('WooZoneLiteCached'),
				'cache_folder'				=> 'woozonelite-cached',
			);

			require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'aa-framework/utils/cacheit.class.php' );

			$_SESSION['WooZoneLiteCachedContor'] = array('hits' => 0, 'hitscache' => 0, 'nonamazon' => 0);

			$this->cacheit['imgurl'] = WooZoneLiteCacheImagesUrl::getInstanceMultiple( $this, array_replace_recursive($cache_pms, array(
				'cache_keymain'		=> array('WooZoneLiteCached_imgurl', 'imgurl'),
				//'cache_keymain'		=> array('WooZoneLiteCached', 'imgurl'),
			)));

			$this->cacheit['imgsources'] = WooZoneLiteCacheImagesSources::getInstanceMultiple( $this, array_replace_recursive($cache_pms, array(
				'cache_keymain'		=> array('WooZoneLiteCached_imgsources', 'imgsources'),
				//'cache_keymain'		=> array('WooZoneLiteCached', 'imgsources'),
			)));

			$this->cacheit['amzvalid'] = WooZoneLiteCacheAmzValid::getInstanceMultiple( $this, array_replace_recursive($cache_pms, array(
				'cache_keymain'		=> array('WooZoneLiteCached_amzvalid', 'amzvalid'),
				//'cache_keymain'		=> array('WooZoneLiteCached', 'amzvalid'),
			)));

			//DEBUG
			foreach ( $this->cacheit as $key => $obj) {
				//$this->cacheit["$key"]->empty_cache();
				//$this->cacheit["$key"]->debug_cache();
			}
			//echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
		}


		public function _attachment_url__( $url='', $post_id=0 ) {
			
			return $this->the_plugin->imagesfix->_attachment_url( $url, $post_id );
			// END HERE - CODE BELLOW NOT EXECUTED

			$uniqueid = md5( $post_id . $url );
			$thecache = $this->cacheit['imgurl']->get_row($uniqueid);

			if ( isset($thecache['v']) ) {
				$this->cacheit['imgurl']->add_row($uniqueid, array(
					//'hitsc' 			=> isset($thecache['hitsc']) ? ($thecache['hitsc'] + 1) : 1,
				));
				$_SESSION['WooZoneLiteCachedContor']['hitscache']++;
				return $thecache['v'];
			}

			$this->cacheit['imgurl']->add_row($uniqueid, array(
				//'hits' 				=> isset($thecache['hits']) ? ($thecache['hits'] + 1) : 1,
				//'post_id'		=> $post_id,
				//'url'				=> $url,
				'v' => $url,
			));

			// mandatory - must be amazon product
			$post = get_post($post_id);

			$this->cacheit['imgurl']->add_row($uniqueid, array(
				//'post_parent'		=> $post->post_parent,
			));

			if ( isset($post->post_parent) && $post->post_parent
				&& $this->the_plugin->verify_product_is_amazon($post->post_parent) === 0
			) {
				//$this->cacheit['imgurl']->save_cache(); // NON amazon product => don't save it to cache
				$this->cacheit['imgurl']->del_row($uniqueid);
				$_SESSION['WooZoneLiteCachedContor']['nonamazon']++;
				return $url;
			}

			// mandatory rule - must have amazon url
			$rules = array();
			$rules[0] = strpos( $url, $this->the_plugin->get_amazon_images_path() );
			$rules = $rules[0];

			if ( $rules ) {
				$uploads = wp_get_upload_dir();
				$url = str_replace( $uploads['baseurl'] . '/', '', $url );
				if( $this->is_ssl() == true ) {
					$uploads['baseurl'] = str_replace( 'http://', 'https://', $uploads['baseurl']);  
					$url = str_replace( $uploads['baseurl'] . '/', '', $url );
				}
			}
			$url = $this->the_plugin->imagesfix->amazon_url_to_ssl( $url );
			if ( ! is_admin() ) {
				$url = $this->the_plugin->imagesfix->woocommerce_image_replace_src( $url );
			}

			$this->cacheit['imgurl']->add_row($uniqueid, array(
				//'url'				=> $url,
				'v' => $url,
			));
			$this->cacheit['imgurl']->save_cache();
			$_SESSION['WooZoneLiteCachedContor']['hits']++;

			//var_dump('<pre>',$url,'</pre>');
			return $url;
		}

		public function _calculate_image_srcset__( $sources=array(), $size_array=array(), $image_src='', $image_meta=array(), $attachment_id=0 ) {

			return $this->the_plugin->imagesfix->_calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id );
			// END HERE - CODE BELLOW NOT EXECUTED

			if ( empty($sources) ) return $sources;

			$uniqueid = md5( $attachment_id . serialize($sources) );
			$thecache = $this->cacheit['imgsources']->get_row($uniqueid);
			
			if ( isset($thecache['v']) ) {
				$this->cacheit['imgsources']->add_row($uniqueid, array(
					//'hitsc' 			=> isset($thecache['hitsc']) ? ($thecache['hitsc'] + 1) : 1,
				));
				$_SESSION['WooZoneLiteCachedContor']['hitscache']++;
				return $thecache['v'];
			}

			$this->cacheit['imgsources']->add_row($uniqueid, array(
				//'hits' 					=> isset($thecache['hits']) ? ($thecache['hits'] + 1) : 1,
				//'attachment_id'	=> $attachment_id,
				//'sources'			=> $sources,
				'v' => $sources,
			));

			// mandatory - must be amazon product
			$post = get_post($attachment_id);
			
			$this->cacheit['imgsources']->add_row($uniqueid, array(
				//'post_parent'		=> $post->post_parent,
			));

			if ( isset($post->post_parent) && $post->post_parent
				&& $this->the_plugin->verify_product_is_amazon($post->post_parent) === 0
			) {
				//$this->cacheit['imgsources']->save_cache(); // NON amazon product => don't save it to cache
				$this->cacheit['imgsources']->del_row($uniqueid);
				$_SESSION['WooZoneLiteCachedContor']['nonamazon']++;
				return $sources;
			}

			$uploads = wp_get_upload_dir();
			foreach ( $sources as &$source ) {
				// mandatory rule - must have amazon url
				$rules = array();
				$rules[0] = strpos( $source['url'], $this->the_plugin->get_amazon_images_path() );
				$rules = $rules[0];

				if ( $rules ) {
					$source['url'] = str_replace( $uploads['baseurl'] . '/', '', $source['url'] );
				}
				$source['url'] = $this->the_plugin->imagesfix->amazon_url_to_ssl( $source['url'] );
				if ( ! is_admin() ) {
					$source['url'] = $this->the_plugin->imagesfix->woocommerce_image_replace_src( $source['url'] );
				}
			}

			$this->cacheit['imgsources']->add_row($uniqueid, array(
				//'sources'			=> $sources,
				'v' => $sources,
			));
			$this->cacheit['imgsources']->save_cache();
			$_SESSION['WooZoneLiteCachedContor']['hits']++;

			//var_dump('<pre>',$sources,'</pre>');  
			return $sources;
		}

		public function verify_product_is_amazon__($prod_id) {

			return $this->the_plugin->verify_product_is_amazon($prod_id);
			// END HERE - CODE BELLOW NOT EXECUTED

			// verify we are in woocommerce product
			if ( is_object($prod_id) ) {
				$product = $prod_id;
			} else if( function_exists('wc_get_product') ){
				$product = wc_get_product( $prod_id );
			} else if( function_exists('get_product') ){
				$product = get_product( $prod_id );
			}

			if ( isset($product) && is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}

				if ( $prod_id ) {

					$thecache = $this->cacheit['amzvalid']->get_row($prod_id);
					
					if ( isset($thecache['v']) ) {
						$this->cacheit['amzvalid']->add_row($prod_id, array(
							//'hitsc' 			=> isset($thecache['hitsc']) ? ($thecache['hitsc'] + 1) : 1,
						));
						$_SESSION['WooZoneLiteCachedContor']['hitscache']++;
						return $thecache['v'];
					}
		
					$this->cacheit['amzvalid']->add_row($prod_id, array(
						//'hits' 				=> isset($thecache['hits']) ? ($thecache['hits'] + 1) : 1,
						//'post_id'		=> $prod_id,
					));

					// verify is amazon product!
					$asin = get_post_meta($prod_id, '_amzASIN', true);

					if ( $asin!==false && strlen($asin) > 0 ) {
						$this->cacheit['amzvalid']->add_row($prod_id, array(
							//'isvalid' 			=> 1,
							//'asin'				=> $asin,
							'v' => 1,
						));
						$this->cacheit['amzvalid']->save_cache();
						$_SESSION['WooZoneLiteCachedContor']['hits']++;
						return 1;
					}

					$this->cacheit['amzvalid']->add_row($prod_id, array(
						//'isvalid' 			=> 0,
						//'asin'				=> $asin,
						'v' => 0,
					));
					$this->cacheit['amzvalid']->save_cache();
					$_SESSION['WooZoneLiteCachedContor']['hits']++;
					return 0;
				}
			}
			return false;
		}
	}
}