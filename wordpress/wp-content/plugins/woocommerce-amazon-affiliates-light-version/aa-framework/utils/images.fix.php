<?php
/*
* Define class WooZoneLite_ImagesFix
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLite_ImagesFix') != true) {
	class WooZoneLite_ImagesFix
	{
		const VERSION = '1.0';
		
		public $the_plugin = null;
		public $is_admin = null;

		public $amz_settings = array();

		static protected $_instance;

		public $alias;
		public $localizationName;

		public $page;

		public $is_remote_images;

		public $duplicate_images = array();

		protected static $sql_chunk_limit = 2000;


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

			$this->is_remote_images = $this->the_plugin->is_remote_images;


			// Fixed Images Issue (from amazon CDN) with https
			/*
			$cond1 = ! isset($_REQUEST['wp_customize']);
			$cond2 = ! isset($_REQUEST['action']) || ( ! in_array($_REQUEST['action'], array('upload-plugin', 'upload-theme')) );
			if ( $cond1 && $cond2 ) {
				if ( ! $this->is_plugin_active('w3totalcache') ) {

					//add_action( 'after_setup_theme', array( $this, 'buffer_start' ), 10 );
					//add_action( 'shutdown', array( $this, 'buffer_end' ), 10, 1 );
					add_action( 'plugins_loaded', array( $this, 'buffer_end_pre' ), 0 ); // hooks tried: plugins_loaded | wp_loaded
				}
				else {
					//add_filter( 'w3tc_can_cache', array($this, 'w3tc_can_cache'), 10, 3 );
					add_filter( 'w3tc_process_content', array($this, 'w3tc_process_content'), 999, 1 );
				}

				// 2017-08-17 fixes for amazon images
				add_filter( 'wp_prepare_attachment_for_js', array($this, 'wp_prepare_attachment_for_js'), 10, 3 );
				add_filter( 'woocommerce_available_variation', array($this, 'woocommerce_available_variation'), 10, 3 );
			}
			*/


			if ( $this->is_admin ) {
				// FIX: woocommerce product list image srcset wrong url
				//add_filter( 'max_srcset_image_width', create_function( '', 'return 1;' ) );
				add_filter( 'max_srcset_image_width', function () {
					return 1;
				});
			}

			add_filter( 'woocommerce_cart_item_thumbnail', array($this, 'filter_woocommerce_cart_item_thumbnail'), 10, 3 );

			//:: REMOTE AMAZON IMAGES
			//add_filter( "woocommerce_single_product_image_thumbnail_html", array($this, 'woocommerce_image_replace_src_revert'));
			//add_filter( "wp_get_attachment_image_src", array($this, 'woocommerce_image_replace_src_revert'));
			//add_filter( "wp_get_attachment_thumb_url", array($this, '_attachment_url'), 0, 2);
			//add_filter( "wp_get_attachment_metadata", array($this, '_attachment_metadata'), 0, 2);
			//add_filter( "image_get_intermediate_size", array($this, '_intermediate_size'), 0, 3);

			/*$meta_type = array('post', 'product');
			foreach ($meta_type as $meta_t) {
				get_{$meta_type}_metadata filter from wp-includes/meta.php
				add_filter( "get_{$meta_t}_metadata", array($this, '_hook_woc_metadata'), 0, 4);
			}*/

			add_filter( "wp_get_attachment_url", array($this, '_attachment_url'), 0, 2);
			add_filter( "wp_calculate_image_srcset", array($this, '_calculate_image_srcset'), 0, 5);

			// generate wordpress & woocommerce image sizes for amazon remote images
			add_filter( 'get_post_metadata', array($this, 'gpm_on_wp_attachment_metadata'), 999, 4 );

			// gallery full size (for amazon images)
			// woo filters: woocommerce_gallery_full_size | woocommerce_product_thumbnails_large_size
			add_filter( 'wp_get_attachment_image_src', array($this, 'wp_get_attachment_image_src'), 999, 4 );

			add_action( 'plugins_loaded', array( $this, '_test_aateam' ), 0 );
		}

		public function _test_aateam() {
			$this->ebay_images_build_sizes(
				'https://i.ebayimg.com/00/s/MTIwMFgxMjAw/z/QrEAAOSwbA5bIhlJ/$_10.JPG', array()
			);
		}

		static public function getInstance( $parent )
		{
			if (!self::$_instance) {
				self::$_instance = new self($parent);
			}
			
			return self::$_instance;
		}

		public function amazon_url_to_ssl( $url='' ) {
			if (empty($url)) return $url;
			if ( ! $this->the_plugin->is_ssl() ) return $url;

			$provider = $this->is_image_remote( $url );

			$newurl = '';
			if ( 'amazon' == $provider ) {
				// http://ecx.images-amazon
				// 		TO 
				// https://images-na.ssl-images-amazon
				$newurl = preg_replace(
					'/^http\:\/\/ec(.){0,1}\.images\-amazon/imu',
					'https://images-na.ssl-images-amazon',
					$url
				);
			}
			else if ( 'ebay' == $provider ) {
				// http://i.ebayimg.com/00/s/MTIwMFgxMjAw/z/QrEAAOSwbA5bIhlJ/$_10.JPG
				// 		TO
				// https://i.ebayimg.com/00/s/MTIwMFgxMjAw/z/QrEAAOSwbA5bIhlJ/$_10.JPG
				$newurl = preg_replace(
					'/^http\:\/\//imu',
					'https://',
					$url
				);
			}
			return !empty($newurl) ? $newurl : $url;
		}

		public function woocommerce_image_replace_src( $html='' ) {
			//return str_replace( "http", "http__", $html);
			return $html;
		}
		public function woocommerce_image_replace_src_revert( $html='' ) {
			//return str_replace( "http__", "http", $html);
			return $html;
		}

		public function _attachment_url( $url='', $post_id=0 ) 
		{
			$orig_url = $url;

			if( in_array( $orig_url, array_keys($this->duplicate_images) ) ){
				if( isset($this->duplicate_images[$orig_url]) ){
					return $this->duplicate_images[$orig_url];
				}
			}

			// mandatory - must be amazon product
			$post = get_post($post_id);

			if ( isset($post->post_parent) && $post->post_parent
				&& $this->the_plugin->verify_product_is_amazon($post->post_parent, array( 'verify_provider' => false )) !== true
			) {
				return $url;
			}

			// mandatory rule - must have amazon url
			if ( false !== $this->is_image_remote( $url ) ) {
				$uploads = wp_get_upload_dir();
				$url = str_replace( $uploads['baseurl'] . '/', '', $url );
	
				if( $this->the_plugin->is_ssl() == true ) {
					$uploads['baseurl'] = str_replace( 'http://', 'https://', $uploads['baseurl']);  
					$url = str_replace( $uploads['baseurl'] . '/', '', $url );
				}
			}
			$url = $this->amazon_url_to_ssl( $url );
			//if ( ! is_admin() ) {
			//	$url = $this->woocommerce_image_replace_src( $url );
			//}

			$this->duplicate_images[$orig_url] = $url;
			//var_dump( "<pre>", $this->duplicate_images  , "</pre>" ); 
			return $url;
		}

		public function _calculate_image_srcset( $sources=array(), $size_array=array(), $image_src='', $image_meta=array(), $attachment_id=0 ) {

			if ( empty($sources) ) return $sources;

			// mandatory - must be amazon product
			$post = get_post($attachment_id);
			
			if ( isset($post->post_parent) && $post->post_parent
				&& $this->the_plugin->verify_product_is_amazon($post->post_parent, array( 'verify_provider' => false )) !== true
			) {
				return $sources;
			}

			$uploads = wp_get_upload_dir();
			foreach ( $sources as &$source ) {

				// mandatory rule - must have amazon url
				if ( false !== $this->is_image_remote( $source['url'] ) ) {
					$source['url'] = str_replace( $uploads['baseurl'] . '/', '', $source['url'] );
				}
				$source['url'] = $this->amazon_url_to_ssl( $source['url'] );
				//if ( ! is_admin() ) {
				//	$source['url'] = $this->woocommerce_image_replace_src( $source['url'] );
				//}
			}

			//var_dump('<pre>',$sources,'</pre>');  
			return $sources;
		}




		public function filter_woocommerce_cart_item_thumbnail( $product_get_image, $cart_item, $cart_item_key ) {
			$product_get_image = $this->_parse_page_fix_amazon( $product_get_image );
			return $product_get_image;
		}

		public function wp_prepare_attachment_for_js($response, $attachment, $meta) {
			$theid = isset($response['id']) ? (int) $response['id'] : 0;
			if ( ! $theid ) return $response;
			
			if ( isset($response['url']) ) {
				$response['url'] = $this->_attachment_url( $response['url'], $theid );
			}
			if ( isset($response['sizes']) ) {
				foreach ($response['sizes'] as $key => $val) {
					if ( isset($val['url']) ) {
						$response['sizes']["$key"]['url'] = $this->_attachment_url( $val['url'], $theid );
					}
				}
			}
			return $response;
		}

		public function woocommerce_available_variation( $data, $that, $variation ) {
			//var_dump('<pre>', $data, $that, $variation, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			
			$image = array();
			if ( isset($data['image']) && ! empty($data['image']) ) {
				$image = $data['image'];
			}
			else {
				return $data;
			}
			
			foreach ($image as $key => $prop) {
				if ( ! in_array($key, array('url', 'src', 'srcset', 'full_src', 'thumb_src')) ) {
					continue 1;
				}
				
				//$image["$key"] = $this->_parse_page_fix_amazon( $prop );

				$theid = isset($data['variation_id']) ? (int) $data['variation_id'] : 0;
				if ( ! $theid ) return $data;

				$image["$key"] = $this->_attachment_url( $prop, $theid );
			}

			$data['image'] = $image;
			return $data;
		}


		/**
		 * w3 total cache related
		 */
		public function w3tc_can_cache($original_can_cache, $that, $buffer) {
			return true;
		}
		
		public function w3tc_process_content( $buffer ) {
			$buffer = $this->buffer_end( array('page' => $buffer, 'show' => false) );
			return $buffer;
		}


		public function buffer_end_pre()
		{
			ob_end_clean();
		}

		public function buffer_end( $pms=array() )
		{
			$pms = array_filter( (array) $pms );
			$pms = array_replace_recursive(array(
				'show'		=> true,
				'page'		=> '',
			), $pms);
			extract($pms);

			$page = isset($page) && ! empty($page) ? $page : $this->page;

			$page = $this->_parse_page_fix_amazon( $page );

			$cacheImagesDebug = $this->debug_cache_images();
			if ( ! empty($cacheImagesDebug) && isset($_REQUEST['aateam']) && (bool) $_REQUEST['aateam'] ) {
				$page .= $cacheImagesDebug;
			}

			$this->page = $page;
			if ( $show ) {
				echo $page;
			}
			return $page;
		}

		public function buffer_start()
		{
			ob_start( array($this, 'buffer_callback') );
		}

		public function buffer_callback( $buffer ) 
		{
			$this->page = $buffer;
		}


		public function _parse_page_fix_amazon( $page ) {
			$upload = wp_upload_dir();
			$upload_base = $upload['baseurl'];
			$upload_base_ = str_replace( array("http://", "https://"), '', $upload_base );
			$upload_base_non_ssl = 'http://' . $upload_base_;
			$upload_base_is_ssl = 'https://' . $upload_base_;
			$upload_base__ = array( $upload_base_is_ssl . '/', $upload_base_non_ssl . '/', '//' . $upload_base_ . '/' );
			//var_dump('<pre>',$upload_base__,'</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			
			// fix for wordpress.com hosted sites and jetpack
			// https://jetpack.com/support/photon/
			if ( class_exists( 'Jetpack' )
				&& method_exists( 'Jetpack', 'get_active_modules' )
				&& in_array( 'photon', Jetpack::get_active_modules() )
			) {
				$upload_base__[] = 'https://i0.wp.com/' . $upload_base_ . '/';
				$upload_base__[] = 'https://i1.wp.com/' . $upload_base_ . '/';
				$upload_base__[] = 'https://i2.wp.com/' . $upload_base_ . '/';
				$upload_base__[] = 'https://i3.wp.com/' . $upload_base_ . '/';
			}

			//:: PARSE PAGE

			//:: all images
			$nb_images = preg_match_all( '/<img[^>]+>/i', $page, $images );
			$images = isset($images[0]) && ! empty($images[0]) ? (array) $images[0] : array();

			// debug!
			//var_dump('<pre>', $nb_images, $result, '</pre>'); //echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			// has images?
			if ( ! empty($images) ) {
				foreach ( $images as $page_img ) {

					// mandatory rule - must have amazon url
					if ( false === $this->is_image_remote( $page_img ) ) {
						continue 1;
					}

					$new_img_html = $page_img;
					$new_img_html = str_replace( $upload_base__, '', $new_img_html );

					// check if is ssl image hosted
					$amz_ = ( strpos( $page_img, 'ssl-images' ) !== false ? 'https://' : 'http://' );

					$new_img_html = str_replace( 'src="//', 'src="' . $amz_, $new_img_html );
					$new_img_html = str_replace( 'srcset="//', 'srcset="' . $amz_, $new_img_html );
					$new_img_html = str_replace( 'data-large_image="//', 'data-large_image="' . $amz_, $new_img_html );
					$new_img_html = str_replace( ', //', ', ' . $amz_, $new_img_html );
	
					$page = str_replace( $page_img, $new_img_html, $page );
				} // end foreach
			} // end has images?

			// debug!
			/*
			$nb_images = preg_match_all('/<img[^>]+>/i', $page, $result);
			var_dump('<pre>', $nb_images, $result, '</pre>'); //echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			*/
			
			//:: others
			$rpath = $this->the_plugin->get_amazon_images_path();
			$rpath .= '|' . $this->the_plugin->get_ebay_images_path();
			$nb_others = preg_match_all(
				'/=(?:"|\')[^"\']*' . preg_quote( $rpath ) . '[^"\']*(?:"|\')/i',
				$page,
				$others
			);
			$others = isset($others[0]) && ! empty($others[0]) ? (array) $others[0] : array();

			// debug!
			//var_dump('<pre>', $nb_images, $result, '</pre>'); //echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			// has others?
			if ( ! empty($others) ) {
				foreach ( $others as $page_img ) {

					$new_img_html = $page_img;
					$new_img_html = str_replace( $upload_base__, '', $new_img_html );
					$page = str_replace( $page_img, $new_img_html, $page );
				} // end foreach
			} // end has images?

			//:: END PARSE PAGE

			//var_dump( "<pre>", $page , "</pre>" ) . PHP_EOL .  __FILE__ . ":" . __LINE__; die;  
			//die( var_dump( "<pre>", $page  , "<pre>" ) . PHP_EOL .  __FILE__ . ":" . __LINE__  );
			
			return $page;
		}


		public function debug_cache_images() {
			if ( $this->the_plugin->is_debug_mode_allowed() ) {
				$html = array();
				$html[] = '<div style="background-color: #3498db; color: #fff; position: fixed; bottom: 25px; right: 25px; max-width: 200px; font-size: 10px;">';
				$html[] = 		'<table style="border-spacing: 2px; margin: 0px; border: 0px;">';
				$html[] = 			'<thead style="line-height: 10px;">';
				$html[] =				'<tr>';
				if ( isset($_SESSION['WooZoneLiteCachedContor']) ) {
					foreach ($_SESSION['WooZoneLiteCachedContor'] as $key => $val) {
						$html[] = 				'<th>' . str_replace('_', ' ', $key) . '</th>';
					}
				}
				$html[] =				'</tr>';
				$html[] = 			'</thead>';
				$html[] = 			'<tbody style="line-height: 10px;">';
				$html[] = 				'<tr>';
				if ( isset($_SESSION['WooZoneLiteCachedContor']) ) {
					foreach ($_SESSION['WooZoneLiteCachedContor'] as $key => $val) {
						$html[] = 				'<td>' . $val . '</td>';
					}
				}
				$html[] = 				'</tr>';
				$html[] = 			'</tbody>';
				$html[] = 		'</table>';
				$html[] = '</div>';

				$html = implode(PHP_EOL, $html);
				return $html;
			}
			return false;
		}


		public function _hook_woc_metadata($metadata, $object_id, $meta_key, $single) {
			//var_dump('<pre>',$metadata, $object_id, $meta_key, $single,'</pre>');
			$metadata_orig = $metadata;
			
			$parsing = array(
				//'_product_image_gallery',
				//'_thumbnail_id',
				'_wp_attached_file',
				'_wp_attachment_metadata'
			);
			if ( !isset($meta_key) || !in_array($meta_key, $parsing) ) return $metadata;
			
			// must be amazon product
			// ... to do

			// loop through keys
			switch ( $meta_key ) {
				case '_wp_attached_file':
					$metadata = $this->_get_meta_key( $meta_key, $object_id );
					if ( empty($metadata) ) return $metadata;

					if ( strpos( $metadata->meta_value, $this->the_plugin->get_amazon_images_path() ) ) {
						return $metadata->meta_value;
					}

					$metadata = $this->_get_amz_asset( (int) $metadata->post_id );
					if ( empty($metadata) ) return $metadata;
						
					$metadata = $metadata->asset;
					break;
					
				case '_wp_attachment_metadata':
					$metadata = $this->_get_meta_key( $meta_key, $object_id );
					if ( empty($metadata) ) return $metadata;

					$meta_value = maybe_unserialize( $metadata->meta_value );
					if ( empty($meta_value) || !is_array($meta_value) ) {
						return $metadata_orig;
					}
	
					$metadata_ = array_replace_recursive(array(
						'width'         => 0,
						'height'        => 0,
						'file'          => '',
						'sizes'         => array(),
						'image_meta'    => array(),
					), $meta_value);
					
					if ( !empty($metadata_['file'])
						&& strpos( $metadata_['file'], $this->the_plugin->get_amazon_images_path() ) ) {
						return array($metadata_);
					}

					$metadata = $this->_get_amz_asset( (int) $metadata->post_id );
					if ( empty($metadata) ) return $metadata;
					
					$metadata_['file'] = $metadata->asset;
					
					$image_sizes = get_intermediate_image_sizes();
					foreach ( $image_sizes as $_size ) {

						$url = $metadata->asset;
						if ( in_array($_size, array('thumbnail', 'shop_thumbnail')) ) {
							$url = $metadata->thumb;
						}
						$url = basename($url);

						if ( isset($metadata_['sizes'], $metadata_['sizes']["$_size"]) ) {
							$metadata_['sizes']["$_size"]['file'] = $url;
						}
					}
					$metadata = array($metadata_);
					break;
			}

			//var_dump('<pre>',$object_id, $meta_key, $metadata,'</pre>');  
			return $metadata;
		}

		public function _get_meta_key( $meta_key, $post_id=0 ) {
			if ( empty($post_id) ) return false;
	 
			global $wpdb;
			
			$q = "select pm.post_id, pm.meta_value from $wpdb->postmeta as pm where 1=1 and pm.post_id = %s and pm.meta_key = %s order by pm.meta_id desc limit 1;";
			$q = $wpdb->prepare( $q, $post_id, $meta_key );
			$res = $wpdb->get_row( $q );
			if ( empty($res) ) return null;
			return $res;
		}
		
		public function _get_amz_asset( $media_id=0 ) {
			if ( empty($media_id) ) return false;
	 
			global $wpdb;
			$table = $wpdb->prefix . 'amz_assets';
	 
			$q = "select a.asset, a.thumb from $table as a where 1=1 and a.media_id = %s order by a.id asc limit 1;";
			$q = $wpdb->prepare( $q, $media_id );
			$res = $wpdb->get_row( $q );
			if ( empty($res) ) return null;
			return $res;
		}

		public function _intermediate_size( $data=array(), $post_id=0, $size='' ) {
		}

		public function _attachment_metadata( $data='', $post_id=0 ) {
			return $data;

			$rules = array();
			$rules[0] = !empty($data) && is_array($data);
			$rules[1] = $rules[0] && isset($data['width'], $data['width'], $data['file'], $data['image_meta']);
			$rules[2] = $rules[0] && isset($data['sizes'])
				&& !empty($data['sizes']) && is_array($data['sizes']);
			$rules = $rules[0] && $rules[1] && $rules[2];

			if ( $rules ) {
			} 
			return $data;
		}



		// used on wordpress hook 'get_post_metadata'
		public function gpm_on_wp_attachment_metadata( $null, $object_id, $meta_key, $single ) {
			if ( ! isset($meta_key) ) {
				return $null;
			}
			if ( ! $object_id ) {
				return $null;
			}

			if ( '_wp_attachment_metadata' == $meta_key ) {

				remove_filter( 'get_post_metadata', array( $this, 'gpm_on_wp_attachment_metadata' ), 999 );
				$current_meta = get_post_meta( $object_id, $meta_key, true );
				add_filter( 'get_post_metadata', array( $this, 'gpm_on_wp_attachment_metadata' ), 999, 4 );
				//var_dump('<pre>', $current_meta , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				if ( empty($current_meta) || ! is_array($current_meta) ) {
					return $null;
				}

				$file = isset($current_meta['file']) ? $current_meta['file'] : '';
				$sizes = isset($current_meta['sizes']) ? $current_meta['sizes'] : array();

				// file is mandatory!
				//if ( empty($file) || empty($sizes) || ! is_array($sizes) ) {
				if ( empty($file) ) {
					return $null;
				}

				// is amazon remote image?
				if ( false === $this->is_image_remote( $file ) ) {
					return $null;
				}

				//$wp_sizes = $this->get_image_sizes_allowed();
				$wp_sizes = $this->the_plugin->u->get_image_sizes();
				//var_dump('<pre>', $wp_sizes , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				//:: build image sizes
				$sizes = $this->build_amazon_image_sizes( array(
					'image_path' 	=> $file,
					'wp_sizes' 		=> $wp_sizes,
					'image_sizes' 	=> $sizes,
					'do_ebay_size' 	=> true,
				));
				//var_dump('<pre>', $sizes, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				//:: high resolution image
				// woo filters: woocommerce_gallery_full_size | woocommerce_product_thumbnails_large_size
				$amzhires_url = isset($current_meta['_wzone'], $current_meta['_wzone']['amzhires_url']) ?
					$current_meta['_wzone']['amzhires_url'] : '';
				$amzhires_size = isset($current_meta['_wzone'], $current_meta['_wzone']['amzhires_size']) ?
					$current_meta['_wzone']['amzhires_size'] : array();

				if ( ! empty($amzhires_url) && ! empty($amzhires_size) ) {
					$sizes['full'] = array_merge(
						isset($sizes['full']) ? $sizes['full'] : array(),
						$amzhires_size
					);
					//$sizes['full']['file'] = $amzhires_url;
				}

				$current_meta['sizes'] = $sizes;
				//var_dump('<pre>', $current_meta , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				// always return an array with your return value => no need to handle $single
				return array( $current_meta );
			}
			return $null;
		}

		// used also in /aa-framework/generic.helper.class.php
		// image_path = string aka full image url
		// wp_sizes = array( key => array('file', 'width', 'height', 'mime-type') ... )
		// image_sizes = array( key => array('file', 'width', 'height', 'mime-type') ... )
		public function build_amazon_image_sizes( $pms=array() ) {

			$pms = array_replace_recursive( array(
				'image_path' 	=> '',
				'wp_sizes' 		=> array(),
				'image_sizes' 	=> array(),
				'do_ebay_size' 	=> true, // choose here the right image size for an ebay image
			), $pms );
			extract( $pms );

			$provider = $this->is_image_remote( $image_path );

			$sizes_new = array();

			$wp_filetype = wp_check_filetype( basename( $image_path ), null );
			$mime_type = $wp_filetype['type']; //'image/jpeg'

			$image_ext = isset($wp_filetype['ext']) ? $wp_filetype['ext'] : 'jpg';

			$image_name = preg_replace( '/\.[^.]+$/', '', basename( $image_path ) );
			//var_dump('<pre>', $image_name, $image_ext, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			foreach ( $wp_sizes as $sizeid => $sizeinfo ) {

				$current_size = isset($image_sizes["$sizeid"]) ? $image_sizes["$sizeid"] : array();

				$w = isset($sizeinfo['width']) ? $sizeinfo['width'] : 0;
				$h = isset($sizeinfo['height']) ? $sizeinfo['height'] : 0;

				$imgsize = $w ? $w : $h;
				if ( empty($imgsize) ) {
					continue 1;
				}
				$imgsize = array( 'w' => $imgsize, 'h' => $imgsize );

				$imgname = $image_name;

				if ( false === $provider ) {
					continue 1;
				}
				else if ( 'amazon' == $provider ) {
					$imgname = $image_name . "._SS{$imgsize['w']}_";
					//$imgname = str_replace( $image_name, $imgname, $file );
					$imgname = $imgname . '.' . $image_ext;
				}
				else if ( 'ebay' == $provider ) {
					$imgname = preg_replace('/\$_[0-9]+$/imu', '[[size]]', $image_name);

					if ( $do_ebay_size ) {
						$found_size = $this->amazon_choose_image_size(
							array(
								'url' => $image_path,
								'width' => $w,
								'height' => $h,
							),
							$this->ebay_images_build_sizes( $image_path, array() ),
							array(
								'compare_by' => $w ? 'width' : 'height',
							)
						);
						if ( ! empty($found_size) && isset($found_size['size_key']) && ! empty($found_size['size_key']) ) {
							$imgsize = array( 'w' => $found_size['size_props']['width'], 'h' => $found_size['size_props']['height'] );

							$imgname = str_replace('[[size]]', $found_size['size_key'], $imgname);
						}
					}

					$imgname = str_replace('[[size]]', '$_10', $imgname); //just in case!!!
					$imgname = $imgname . '.' . $image_ext;
				}

				$sizes_new["$sizeid"] = array(
					'file' 		=> $imgname,
					'width' 	=> $imgsize['w'],
					'height' 	=> $imgsize['h'],
					'mime-type' => $mime_type,
				);
			}
			//var_dump('<pre>', $sizes_new, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			return $sizes_new;
		}

		public function wp_get_attachment_image_src( $image, $attachment_id, $size, $icon ) {

			if ( 'full' != $size ) {
				return $image;
			}
			//var_dump('<pre>jimmydbg',$image, $attachment_id, $size, $icon ,'</pre>');

			remove_filter( 'get_post_metadata', array( $this, 'gpm_on_wp_attachment_metadata' ), 999 );
			$current_meta = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
			add_filter( 'get_post_metadata', array( $this, 'gpm_on_wp_attachment_metadata' ), 999, 4 );
			//var_dump('<pre>', $current_meta , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			if ( empty($current_meta) || ! is_array($current_meta) ) {
				return $image;
			}

			$file = isset($current_meta['file']) ? $current_meta['file'] : '';

			// file is mandatory!
			if ( empty($file) ) {
				return $image;
			}

			// is amazon remote image?
			if ( false === $this->is_image_remote( $file ) ) {
				return $image;
			}

			//:: high resolution image
			$amzhires_url = isset($current_meta['_wzone'], $current_meta['_wzone']['amzhires_url']) ?
				$current_meta['_wzone']['amzhires_url'] : '';
			$amzhires_size = isset($current_meta['_wzone'], $current_meta['_wzone']['amzhires_size']) ?
				$current_meta['_wzone']['amzhires_size'] : array();

			if ( ! empty($amzhires_url) && ! empty($amzhires_size) ) {

				if ( is_array($image) && isset($image[0]) ) {
					$image[0] = $amzhires_url; // . '#jimmydbg';
				}
			}
			return $image;
		}

		public function is_image_remote( $url ) {
			$rules = array();

			$rules['ebay'] = false !== strpos( $url, $this->the_plugin->get_ebay_images_path() );

			//$rules['amazon'] = false !== strpos( $url, $this->the_plugin->get_amazon_images_path() );
			$amazon_rule = preg_match( '~' . $this->the_plugin->get_amazon_images_path() . '~imu', $url );
			$rules['amazon'] = ! empty($amazon_rule);

			if ( $rules['amazon'] ) {
				return 'amazon';
			}
			if ( $rules['ebay'] ) {
				return 'ebay';
			}
			return false;
		}

		
		//================================================================================
		// choose the right image size

		// --choose original size based on size_alias or else the image size with maximum width
		// size_alias = a string key which is among images_sizes keys
		// image_sizes = array( key => array('url', 'width', 'height') ... )
		public function amazon_choose_image_original( $size_alias='large', $image_sizes=array() ) {

			if ( empty($image_sizes) || ! is_array($image_sizes) ) return false;

			// selected size as original
			if ( isset($image_sizes["$size_alias"]) ) {
				return $image_sizes["$size_alias"];
			}

			// we try to find biggest image by width
			$current = array(
				'url' => '',
				'width' => 0,
				'height' => 0
			);
			foreach ($image_sizes as $_size => $props) {
				if ( (int) $props['width'] <= (int) $current['width'] ) {
					continue 1;
				}
				$current = $props;
			}
			return $current;
		}

		// -- choose best size from the image_sizes list based on size props
		// size = array('url', 'width', 'height')
		// image_sizes = array( key => array('url', 'width', 'height') ... )
		public function amazon_choose_image_size( $size, $image_sizes=array(), $pms=array() ) {

			$pms = array_replace_recursive( array(
				'compare_by' => 'width',
			), $pms );
			extract( $pms );

			$ret = array(
				'size_key' => false,
				'size_props' => array(),
			);

			if ( empty($image_sizes) || ! is_array($image_sizes) ) return false;
			if ( empty($size) || ! is_array($size) ) return false;

			$kkey = in_array($compare_by, array('width', 'height')) ? $compare_by : 'width';
			if ( ! isset($size["$kkey"]) || empty($size["$kkey"]) ) return false;

			$diff = array();
			foreach ($image_sizes as $_size => $props) {
				// found exact match (width or height)
				if ( (int) $size["$kkey"] == (int) $props["$kkey"] ) {
					return array_replace_recursive( $ret, array(
						'size_key' => $_size,
						'size_props' => $props,
					));
				}
				$diff["$_size"] = (int) $props["$kkey"] - (int) $size["$kkey"];
			}
			$positive = array_filter( $diff, array($this, '_positive') );
			$negative = array_filter( $diff, array($this, '_negative') );

			$found = false; $found_pos = false; $found_neg = false;
			if ( !empty($positive) ) {
				$found_pos = min( $positive );
			}
			if ( !empty($negative) ) {
				$found_neg = max( $negative );
			}

			if ( !empty($found_pos) && !empty($found_neg) ) {
				if ( $found_pos > 100 && ( $found_pos > ceil(3 * abs($found_neg)) ) ) {
					$found = $found_neg;
				} else {
					$found = $found_pos;
				}
			}
			else if ( !empty($found_pos) ) {
				$found = $found_pos;
			}
			else if ( !empty($found_neg) ) {
				$found = $found_neg;
			}
			if ( empty($found) ) return false;

			$found_size = array_search( $found, $diff );
			if ( empty($found_size) ) return false;

			return array_replace_recursive( $ret, array(
				'size_key' => $found_size,
				'size_props' => $image_sizes["$found_size"],
			));
		}

		// -- return a wp compatible size array based on an amazon size type
		// size = array('url', 'width', 'height')
		// return array('file', 'width', 'height', 'mime-type') - wp compatible
		public function amazon_format_size_to_wp( $size, $pms=array() ) {

			if ( empty($size) || ! is_array($size) ) return false;
			if ( ! isset($size['url']) || empty($size['url']) ) return false;

			$pms = array_replace_recursive( array(
				'only_image_name' => true,
				'find_mime_type' => true,
			), $pms );
			extract( $pms );

			$file = $size['url'];
			if ( $only_image_name ) {
				$file = basename( $size['url'] );
			}

			$attach_data = array(
				'file'		=> $file,
				'width'		=> $size['width'],
				'height'	=> $size['height'],
			);

			if ( $find_mime_type ) {
				$wp_filetype = wp_check_filetype( basename( $size['url'] ), null );

				$attach_data['mime-type'] = $wp_filetype['type'];
			}
			return $attach_data;
		}

		// you can: from php 4 use create_function; from php 5.3 use anonymous function
		private function _positive( $v ) {
			return $v >= 0;
		}
		private function _negative( $v ) {
			return $v < 0;
		}


		//================================================================================
		// ebay related
		public function ebay_image_clean_url( $url ) {
			//https://i.ebayimg.com/00/s/NjU5WDExMTY=/z/ei8AAOSwrN9azIJs/$_57.JPG?set_id=8800005007
			return preg_replace("/\?set_id=.+/imu", '', $url);
		}

		// -- ebay image build a size array of props
		// return array( key => array('url', 'width', 'height') ... )
		public function ebay_image_get_size( $url ) {
			$size = array(
				'url' => $url,
				'width' => 0,
				'height' => 0,
			);

			//:: original image
			$sizes = is_array($this->the_plugin->ebay_image_sizes) ? $this->the_plugin->ebay_image_sizes : array();

			//https://i.ebayimg.com/00/s/MTIwMFgxMjAw/z/QrEAAOSwbA5bIhlJ/$_10.JPG
			$img_size = array();
			$find = preg_match('/\/\$\_([0-9]+)\./imu', $url, $m);
			$is_ebay_cdn = $find && isset($m[1]) ? true : false;

			if ( $is_ebay_cdn ) {
				$__ = $m[1];
				$img_size = isset($sizes[$__]) ? $sizes[$__] : array();

				if ( ! empty($img_size) ) {
					$size['width'] = $img_size[0];
					$size['height'] = $img_size[1];
				}
			}
			//else {
			//	$ret = $this->the_plugin->u->getimagesize( $url );
			//	if ( 'valid' == $ret['status'] ) {
			//		$size['width'] = $ret['size'][0];
			//		$size['height'] = $ret['size'][1];
			//	}
			//}

			//var_dump('<pre>',$size ,'</pre>');
			$size = array( 'large' => $size );

			//:: simulate that we have an high resolution version for current ebay image
			if ( $is_ebay_cdn ) {
				$found_size = $this->amazon_choose_image_size(
					array_replace_recursive( $size['large'], array(
						'width' => 1280,
						'height' => 1280,
					)),
					$this->ebay_images_build_sizes( $url, array() ),
					array(
						'compare_by' => 'width',
					)
				);
				if ( ! empty($found_size) && isset($found_size['size_key']) && ! empty($found_size['size_key']) ) {
					$size['hires'] = $found_size['size_props'];
				}
			}

			if ( ! isset($size['hires']) ) {
				if ( (int) $size['large']['width'] >= 1280 ) {
					$size['hires'] = array(
						'url' => $url,
						'width' => $size['large']['width'],
						'height' => $size['large']['height'],
					);
				}
			}

			return $size;
		}

		// -- ebay all static image sizes
		// return array( key => array('url', 'width', 'height') ... )
		public function ebay_images_build_sizes( $url, $pms=array() ) {

			$pms = array_replace_recursive( array(
			), $pms );
			extract( $pms );

			$sizes = is_array($this->the_plugin->ebay_image_sizes) ? $this->the_plugin->ebay_image_sizes : array();

			$image_dir = dirname( $url );
			$image_dir .= '/';

			$wp_filetype = wp_check_filetype( basename( $url ), null );
			$image_ext = isset($wp_filetype['ext']) ? $wp_filetype['ext'] : 'jpg';

			$sizes_new = array();
			foreach ($sizes as $kk => $vv) {
				$newkey = '$_'.$kk;
				$sizes_new["$newkey"] = array(
					'url' => $image_dir . $newkey . '.' . $image_ext,
					'width' => $vv[0],
					'height' => $vv[1],
				);
			}
			//var_dump('<pre>', $sizes_new , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			return $sizes_new;
		}



		//================================================================================
		// Get Thumbnails / Thumbnail - based on WP functionality
		public function get_thumbs( $currentIds=array(), $size='thumbnail' ) {
			global $wpdb;
			
			$currentP = '';
			if ( !empty($currentIds) ) {
				$currentP = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $currentIds));
			}
			
			// get post & associated thumbnails id
			$sql = "select p.ID, pm.meta_value from $wpdb->posts as p left join $wpdb->postmeta as pm on p.ID = pm.post_id where 1=1 and pm.post_id IN ($currentP) and pm.meta_key = '_thumbnail_id' and !isnull(pm.meta_id) order by p.ID;";
			$res = $wpdb->get_results( $sql, OBJECT_K );
			if ( empty($res) ) return array();
			
			// get unique thumbnails id
			$sql_thumb = "select distinct(pm.meta_value) from $wpdb->posts as p left join $wpdb->postmeta as pm on p.ID = pm.post_id where 1=1 and pm.post_id IN ($currentP) and pm.meta_key = '_thumbnail_id' and !isnull(pm.meta_id) order by p.ID;";
			$res_thumb = $wpdb->get_results( $sql_thumb, OBJECT_K );
			$thumbsId = array_keys($res_thumb);

			// get meta fields for thumbnails
			$thumb2meta = array('_wp_attachment_metadata' => array(), '_wp_attached_file' => array());
			foreach (array_chunk($thumbsId, self::$sql_chunk_limit, true) as $current) {

				$currentP = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $current));

				$sql_getmeta = "select p.ID, pm.meta_value from $wpdb->posts as p left join $wpdb->postmeta as pm on p.ID = pm.post_id where 1=1 and pm.meta_key = '_wp_attachment_metadata' and !isnull(pm.meta_id) and pm.post_id IN ($currentP) order by p.ID;";
				$res_getmeta = $wpdb->get_results( $sql_getmeta, OBJECT_K );
				//array_replace($prods2asin, $res_getmeta);
				$thumb2meta['_wp_attachment_metadata'] = $thumb2meta['_wp_attachment_metadata'] + $res_getmeta;
				
				$sql_getmeta = "select p.ID, pm.meta_value, p.guid from $wpdb->posts as p left join $wpdb->postmeta as pm on p.ID = pm.post_id where 1=1 and pm.meta_key = '_wp_attached_file' and !isnull(pm.meta_id) and pm.post_id IN ($currentP) order by p.ID;";
				$res_getmeta = $wpdb->get_results( $sql_getmeta, OBJECT_K );
				//array_replace($prods2asin, $res_getmeta);
				$thumb2meta['_wp_attached_file'] = $thumb2meta['_wp_attached_file'] + $res_getmeta;
			}
 
			$default_meta = array(
				'uploads'       => wp_upload_dir(), // cache this wp function!
			);
			$thumbs = array();
			foreach ($thumbsId as $key) {

				$meta = array_merge($default_meta, array());
				$meta['file'] = isset($thumb2meta['_wp_attached_file']["$key"])
					? $thumb2meta['_wp_attached_file']["$key"] : '';
  
				$meta['sizes'] = isset($thumb2meta['_wp_attachment_metadata']["$key"]->meta_value)
					? $thumb2meta['_wp_attachment_metadata']["$key"]->meta_value : '';
				if ( !empty($meta['sizes']) ) {
					$meta['sizes'] = maybe_unserialize($meta['sizes']);
				}
				
				$thumbs["$key"] = $this->get_thumb_src( $meta, 'shop_thumbnail' );
				$thumbs["$key"] = isset($thumbs["$key"][0]) ? $thumbs["$key"][0] : '';
				$thumbs["$key"] = !empty($thumbs["$key"]) ? $thumbs["$key"] : $this->get_thumb_src_default();
				if ( false !== $this->is_image_remote($thumbs["$key"]) ) {
					$thumbs["$key"] = str_replace( $default_meta['uploads']['baseurl'] . '/', '', $thumbs["$key"] );
					$thumbs["$key"] = $this->amazon_url_to_ssl( $thumbs["$key"] );
				}
			}
	
			$post2thumb = array();
			foreach ( $res as $key => $val ) {
				$thumb_id = $val->meta_value;
				$post2thumb["$key"] = isset($thumbs["$thumb_id"]) && !empty($thumbs["$thumb_id"])
					? $thumbs["$thumb_id"] : $this->get_thumb_src_default();
			}
			return $post2thumb;
		}

		public function get_thumb( $meta, $size='medium', $pms=array() ) {
			$image = $this->get_thumb_src( $meta, $size );
			
			$html = '';
			if ( $image ) {
				list($src, $width, $height) = $image;
				$hwstring = image_hwstring($width, $height);
				$size_class = $size;
				if ( is_array( $size_class ) ) {
					$size_class = join( 'x', $size_class );
				}
				//$attachment = get_post($attachment_id);
				$default_attr = array(
					'src'   => $src,
					'class' => "attachment-$size_class",
					'alt'   => isset($pms['alt']) ? $pms['alt'] : "$size_class", //trim(strip_tags( get_post_meta($attachment_id, '_wp_attachment_image_alt', true) )), // Use Alt field first
				);
				//if ( empty($default_attr['alt']) )
				//    $default_attr['alt'] = trim(strip_tags( $attachment->post_excerpt )); // If not, Use the Caption
				//if ( empty($default_attr['alt']) )
				//    $default_attr['alt'] = trim(strip_tags( $attachment->post_title )); // Finally, use the title
 
				$attr = wp_parse_args($attr, $default_attr);
 
				$attr = array_map( 'esc_attr', $attr );
				$html = rtrim("<img $hwstring");
				foreach ( $attr as $name => $value ) {
					$html .= " $name=" . '"' . $value . '"';
				}
				$html .= ' />';
			}
			return $html;
		}

		public function get_thumb_src( $meta, $size='medium' ) {
			$img_url = $this->wp_get_attachment_url($meta);
  
			$width = $height = 0;
			$img_url_basename = wp_basename($img_url);
				
			// try for a new style intermediate size
			if ( $intermediate = $this->image_get_intermediate_size($meta, $size) ) {
				$img_url = str_replace($img_url_basename, $intermediate['file'], $img_url);
				$width = $intermediate['width'];
				$height = $intermediate['height'];
			}
			elseif ( $size == 'thumbnail' ) {
				// fall back to the old thumbnail
				$file = isset($meta['file']->meta_value) ? $meta['file']->meta_value : '';

				if ( ($thumb_file = $file) && $info = getimagesize($thumb_file) ) {
					$img_url = str_replace($img_url_basename, wp_basename($thumb_file), $img_url);
					$width = $info[0];
					$height = $info[1];
				}
			}
			
			if ( !$width && !$height && isset( $meta['sizes']['width'], $meta['sizes']['height'] ) ) {
				// any other type: use the real image
				$width = $meta['sizes']['width'];
				$height = $meta['sizes']['height'];
			}
			if ( $img_url) {
				// we have the actual image size, but might need to further constrain it if content_width is narrower
				list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );
				return array( $img_url, $width, $height );
			}
			return false;
		}

		public function wp_get_attachment_url( $meta ) {
			$url = '';

			$uploads = $meta['uploads'];
			$file = isset($meta['file']->meta_value) ? $meta['file']->meta_value : '';
			if ( !empty($file) ) {
				// Get upload directory.
				if ( $uploads && false === $uploads['error'] ) {
					// Check that the upload base exists in the file location.
					if ( 0 === strpos( $file, $uploads['basedir'] ) ) {
						// Replace file location with url location.
						$url = str_replace($uploads['basedir'], $uploads['baseurl'], $file);
					} elseif ( false !== strpos($file, 'wp-content/uploads') ) {
						$url = $uploads['baseurl'] . substr( $file, strpos($file, 'wp-content/uploads') + 18 );
					} else {
						// It's a newly-uploaded file, therefore $file is relative to the basedir.
						$url = $uploads['baseurl'] . "/$file";
					}
				}
			}

			if ( empty($url) ) {
				$url = isset($meta['sizes']->guid) ? $meta['sizes']->guid : '';
			}
			return $url;
		}

		public function image_get_intermediate_size( $meta, $size='thumbnail' ) {
			if ( !is_array( $imagedata = $meta['sizes'] ) )
				return false;

			// get the best one for a specified set of dimensions
			if ( is_array($size) && !empty($imagedata['sizes']) ) {
				foreach ( $imagedata['sizes'] as $_size => $data ) {
					// already cropped to width or height; so use this size
					if ( ( $data['width'] == $size[0] && $data['height'] <= $size[1] ) || ( $data['height'] == $size[1] && $data['width'] <= $size[0] ) ) {
						$file = $data['file'];
						list($width, $height) = image_constrain_size_for_editor( $data['width'], $data['height'], $size );
						return compact( 'file', 'width', 'height' );
					}
					// add to lookup table: area => size
					$areas[$data['width'] * $data['height']] = $_size;
				}
				if ( !$size || !empty($areas) ) {
					// find for the smallest image not smaller than the desired size
					ksort($areas);
					foreach ( $areas as $_size ) {
						$data = $imagedata['sizes'][$_size];
						if ( $data['width'] >= $size[0] || $data['height'] >= $size[1] ) {
							// Skip images with unexpectedly divergent aspect ratios (crops)
							// First, we calculate what size the original image would be if constrained to a box the size of the current image in the loop
							$maybe_cropped = image_resize_dimensions($imagedata['width'], $imagedata['height'], $data['width'], $data['height'], false );
							// If the size doesn't match within one pixel, then it is of a different aspect ratio, so we skip it, unless it's the thumbnail size
							if ( 'thumbnail' != $_size && ( !$maybe_cropped || ( $maybe_cropped[4] != $data['width'] && $maybe_cropped[4] + 1 != $data['width'] ) || ( $maybe_cropped[5] != $data['height'] && $maybe_cropped[5] + 1 != $data['height'] ) ) )
								continue;
							// If we're still here, then we're going to use this size
							$file = $data['file'];
							list($width, $height) = image_constrain_size_for_editor( $data['width'], $data['height'], $size );
							return compact( 'file', 'width', 'height' );
						}
					}
				}
			}
 
			if ( is_array($size) || empty($size) || empty($imagedata['sizes'][$size]) )
				return false;
 
			$data = $imagedata['sizes'][$size];
			// include the full filesystem path of the intermediate file
			if ( empty($data['path']) && !empty($data['file']) ) {
				$file_url = $this->wp_get_attachment_url($meta);
				$data['path'] = path_join( dirname($imagedata['file']), $data['file'] );
				$data['url'] = path_join( dirname($file_url), $data['file'] );
			}
			return $data;
		}
	}
}