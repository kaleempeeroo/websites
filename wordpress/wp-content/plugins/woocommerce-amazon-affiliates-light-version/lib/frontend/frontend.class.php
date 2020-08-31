<?php
/*
* Define class WooZoneLiteFrontend
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;

if (class_exists('WooZoneLiteFrontend') != true) {
	class WooZoneLiteFrontend
	{
		const VERSION = '1.0';
		
		public $the_plugin = null;
		public $is_admin = null;

		public $amz_settings = array();
		public $countryflags_aslink = false;

		static protected $_instance;

		public $alias;
		public $localizationName;

		private $current_theme = null;

		private $woo_tab_data = false;

		public $p_type = null;
		public $product_buy_is_amazon_url = null;
		public $product_url_short = null;

		private $syncfront_args = array();
		private $sync_options = array();
		private $sync_settings = array();

		private static $sql_chunk_limit = 2000;

		// synchronization on frontend is activated?
		public $syncfront_activate = 'no';

		public $cached_product_terms = array();


		public function __construct( $parent )
		{
			$this->the_plugin = $parent;
			$this->is_admin = $this->the_plugin->is_admin;
			
			$this->amz_settings = $this->the_plugin->amz_settings;

			$this->alias = $this->the_plugin->alias;
			$this->localizationName = $this->the_plugin->localizationName;

			$this->p_type = $this->the_plugin->p_type;
			$this->product_buy_is_amazon_url = $this->the_plugin->product_buy_is_amazon_url;
			$this->product_url_short = $this->the_plugin->product_url_short;

			$this->countryflags_aslink = isset($this->amz_settings['product_countries_countryflags'])
				&& $this->amz_settings['product_countries_countryflags'] == "yes" ? true : false;
			
			$this->current_theme = wp_get_theme(); //get_current_theme() - deprecated notice!
			//var_dump('<pre>',$this->current_theme,'</pre>');

			// sync options & settings
			$this->init_sync_settings();
			$this->init_sync_options();

			$this->syncfront_activate = isset($this->sync_options['syncfront_activate'])
				? (string) $this->sync_options['syncfront_activate'] : 'no';
			//$this->syncfront_activate = 'no'; //DEBUG SYNC

			if ( ! in_array( 'syncfront_activate', $this->the_plugin->frontend_show_what() ) ) {
				$this->syncfront_activate = 'no';
			}

			// wp actions - frontend
			if ( ! $this->is_admin ) {

				// frontend header			
				add_action( 'wp_head', array( $this, 'make_head' ), 1 );

				// frontend footer
				add_action( 'wp_footer', array( $this, 'make_footer' ), 1 );

				// main init
				add_action( 'init' , array( $this, 'init' ) );

				// cross sell shortcode
				if ( ! in_array( 'cross_sell', $this->the_plugin->frontend_show_what() ) ) {
					add_shortcode( 'amz_corss_sell', array($this, 'cross_sell_box_empty') );
				}
				else {
					add_shortcode( 'amz_corss_sell', array($this, 'cross_sell_box') );
				}


				add_action( 'WooZoneLite_header', array($this, 'frontend_custom_header'), 0 );
				add_action( 'WooZoneLite_footer', array($this, 'frontend_custom_footer'), 31 );
			}

			// executed only on frontend
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
			
			// wp ajax actions
			add_action('wp_ajax_WooZoneLite_frontend', array( $this, 'ajax_requests') );
			add_action('wp_ajax_nopriv_WooZoneLite_frontend', array( $this, 'ajax_requests') );

			// checkout email: wp ajax actions
			if ( 'simple' == $this->p_type ) {
				if ( isset($this->amz_settings['checkout_email']) && $this->amz_settings['checkout_email'] == 'yes' ) {

					if ( in_array( 'checkout_email', $this->the_plugin->frontend_show_what() ) ) {

						add_action( 'wp_ajax_WooZoneLite_before_user_checkout', array( $this, 'woocommerce_ajax_before_user_checkout') );
						add_action( 'wp_ajax_nopriv_WooZoneLite_before_user_checkout', array( $this, 'woocommerce_ajax_before_user_checkout') );
					}
				}
			}
			
			// cross sell checkout - !needs to be bellow Amazon helper
			$this->cross_sell_checkout();

			// 2018-jan : make bitly request to retrieve product short url
			add_action( 'wp', array( $this, 'action_do_bitly_request' ), 10, 1 );
			add_action( 'wp', array( $this, 'action_do_product_page' ), 11, 1 );


			//:: GDPR update
			add_action( 'shutdown', array( $this, 'session_check' ), 0 );


			//:: Badges / Flags
			// removed from 3.0, used in older versions of woocommerce as 2.X
			add_filter('woocommerce_single_product_image_html', array( $this, 'badges_show_onproduct' ), 999, 2);

			// woocommerce 3.X
			add_filter('woocommerce_single_product_image_thumbnail_html', array( $this, 'badges_show_onproduct_thumbnail' ), 999, 2);
			//add_filter('post_thumbnail_html', array( $this, 'badges_show_onproduct' ), 9999, 2);

			// woocommerce default onsale badge
			add_filter( 'woocommerce_sale_flash', array( $this, 'woocommerce_sale_flash' ), 10, 3 );

			// woocommerce fix thumb for remote images with https - on frontend
			add_action( 'woocommerce_before_mini_cart', array( $this, 'woocommerce_before_mini_cart' ) );


			//:: remove_featured_image_from_gallery
			$remove_featured_image_from_gallery = isset($this->amz_settings['remove_featured_image_from_gallery'])
				&& $this->amz_settings['remove_featured_image_from_gallery'] == 'yes' ? true : false;

			if ( $remove_featured_image_from_gallery ) {
				add_filter('woocommerce_single_product_image_thumbnail_html', array( $this, 'remove_featured_image' ), 10, 3);
				add_filter('woocommerce_single_product_image_html', array( $this, 'remove_featured_image' ), 10, 3);
			}


			//:: [Speed Optimisation Module] Return cached product attributes to additional information tab
			add_action( 'woocommerce_before_single_product', array( $this, 'check_cached_product_terms') );

			//$this->the_plugin->import_stats_db_calc( 'all' );
			//echo __FILE__ . ":" . __LINE__;die . PHP_EOL;   
		}



		//====================================================================================
		//== MAIN METHODS
		//====================================================================================

		// 'init' hook
		public function init() {
			WooZoneLite_debugbar()->add2bar_menu( 'woozonelite-debugbar-session-check', WooZoneLite()->_translate_string( 'Session Check' ), array() );
			WooZoneLite_debugbar()->add2bar_menua( 'woozonelite-debugbar-session-check', WooZoneLite()->_translate_string( 'Session Check' ), array() );

			if ( isset($this->amz_settings['remove_gallery']) && $this->amz_settings['remove_gallery'] == 'no' ) {
				add_filter( 'the_content', array($this, 'remove_gallery'), 6);
			}

			// remove featured image from gallery ids list - fixed duplicated first image from gallery bug
			//add_filter( 'woocommerce_product_gallery_attachment_ids', array($this, 'amz_product_gallery_attachment_ids'), 10, 2 ); //DEPRECATED
			add_filter( 'woocommerce_product_get_gallery_image_ids', array($this, 'amz_product_gallery_attachment_ids'), 10, 2 );

			//::::::::::::::::::::::::::::::::::::
			// start box with product country check
			$is_country_check = ( ! isset($this->amz_settings['product_countries'])
				|| 'yes' == $this->amz_settings['product_countries'] ? true : false );

			if ( ! in_array( 'product_countries', $this->the_plugin->frontend_show_what() ) ) {
				$is_country_check = false;
			}

			if ( $is_country_check ) {

				// single product page
				$box_countries_pos = isset($this->amz_settings['product_countries_main_position'])
					? $this->amz_settings['product_countries_main_position'] : 'before_add_to_cart';
				/**
				 * woocommerce_single_product_summary hook
				 *
				 * @hooked woocommerce_template_single_title - 5
				 * @hooked woocommerce_template_single_rating - 10
				 * @hooked woocommerce_template_single_price - 10
				 * @hooked woocommerce_template_single_excerpt - 20
				 * @hooked woocommerce_template_single_add_to_cart - 30
				 * @hooked woocommerce_template_single_meta - 40
				 * @hooked woocommerce_template_single_sharing - 50
				 */
				switch ($box_countries_pos) {
					case 'before_add_to_cart':
						add_action( 'woocommerce_single_product_summary', array($this, 'woocommerce_single_product_summary'), 21 );
						if ( 'Kingdom - Woocommerce Amazon Affiliates Theme' == $this->current_theme || 'BravoStore' == $this->current_theme  ) {			
							add_action( 'WooZoneLite_footer', array( $this, 'before_add_to_cart' ), 1 );
						}
						break;
					
					case 'before_title_and_thumb':
						add_action( 'WooZoneLite_footer', array( $this, 'before_title_and_thumb' ), 1 );
						break;

					case 'before_woocommerce_tabs':
						add_action( 'WooZoneLite_footer', array( $this, 'before_woocommerce_tabs' ), 1 );
						break;
						
					case 'as_woocommerce_tab':
						add_action( 'woocommerce_product_tabs', array($this, 'woocommerce_product_tabs'), 0 );
						break;		
				}

				//$where_country_check = isset($this->amz_settings['product_countries_where'])
				//	? (array) $this->amz_settings['product_countries_where'] : array(); //'maincart', 'minicart'
				$product_countries_maincart = ( ! isset($this->amz_settings['product_countries_maincart'])
				|| 'yes' == $this->amz_settings['product_countries_maincart'] ? true : false );
				$where_country_check = $product_countries_maincart ? array('maincart') : array();

				// view main cart
				if ( in_array('maincart', $where_country_check) )
					add_filter( 'woocommerce_cart_item_quantity', array($this, 'woocommerce_cart_item_quantity'), 10, 3 );

				// view mini cart
				if ( in_array('minicart', $where_country_check) ) {
					add_filter( 'woocommerce_widget_cart_item_quantity', array($this, 'woocommerce_widget_cart_item_quantity'), 10, 3 );
					if ( 'Kingdom - Woocommerce Amazon Affiliates Theme' == $this->current_theme ) {
						add_action( 'WooZoneLite_footer', array( $this, 'widget_cart_item_quantity' ), 1 );
					}
				}

				// cart page
				//add_action( 'woocommerce_after_cart_table', array($this, 'woocommerce_after_cart') ); // doesn't work - already have a form
				add_action( 'woocommerce_after_cart', array($this, 'woocommerce_after_cart') );
			}
			// end box with product country check
			//::::::::::::::::::::::::::::::::::::
			
			$redirect_cart = (isset($_REQUEST['redirectCart']) && $_REQUEST['redirectCart']) != '' ? $_REQUEST['redirectCart'] : '';
			if( isset($redirect_cart) && $redirect_cart == 'true' ) {
				if ( ! $this->the_plugin->disable_amazon_checkout )
					$this->redirect_cart();
			}

			$redirect_asin = (isset($_REQUEST['redirectAmzASIN']) && $_REQUEST['redirectAmzASIN']) != '' ? $_REQUEST['redirectAmzASIN'] : '';
			//if( isset($redirect_asin) && strlen($redirect_asin) == 10 ) {
			if( isset($redirect_asin) && strlen($redirect_asin) > 0 ) {
				if ( ! $this->the_plugin->disable_amazon_checkout ) {
					$this->redirect_amazon( $redirect_asin );
				}
			}

			$redirect_prodid = (isset($_REQUEST['redirect_prodid']) && $_REQUEST['redirect_prodid']) != '' ? $_REQUEST['redirect_prodid'] : '';
			if( isset($redirect_prodid) && strlen($redirect_prodid) > 0 ) {
				$this->redirect_amazon($redirect_prodid);
			}

			// product details page - external product
			add_action( 'woocommerce_after_add_to_cart_button', array($this, 'woocommerce_external_add_to_cart'), 10 );

			// non-external product pages
			if ( 'simple' == $this->p_type ) {
				// cart checkout which will be going to amazon cart
				if ( ! $this->the_plugin->disable_amazon_checkout ) {
					add_action( 'woocommerce_checkout_init', array($this, 'woocommerce_external_checkout'), 10 );
				}

				// checkout email
				if( isset($this->amz_settings['checkout_email']) && $this->amz_settings['checkout_email'] == 'yes' ) {

					if ( in_array( 'checkout_email', $this->the_plugin->frontend_show_what() ) ) {

						add_filter( 'woocommerce_before_cart_totals', array($this, 'woocommerce_before_checkout'), 10 );
					}
				}
			}

			//:: Amazon Reviews
			if ( isset($this->amz_settings['show_review_tab']) && ($this->amz_settings['show_review_tab'] == 'yes') ) {

				if ( in_array( 'show_review_tab', $this->the_plugin->frontend_show_what() ) ) {

					add_action('woocommerce_product_tabs', array($this, 'amazon_reviews_custom_product_tabs'), 25);
				}
			}

			// external product pages
			if ( 'external' == $this->p_type ) {
				add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'product_buy_text'), -1, 2);
				add_filter('woocommerce_product_add_to_cart_text', array($this, 'product_buy_text'), -1, 2);
				
				// Change the Add To Cart Link
				add_filter( 'woocommerce_loop_add_to_cart_link', array($this, 'amz_add_product_link'), 10, 3 );
			}

			// product buy url is the original amazon url!
			if ( $this->product_buy_is_amazon_url && ( 'external' == $this->p_type ) ) {
				/*
				add_action( 'WooZoneLite_footer', array($this->the_plugin, '_product_buy_url_make'), 30 );
				
				add_action( 'woocommerce_after_shop_loop_item', array($this->the_plugin, '_product_buy_url_html'), 1 );
				
				if( is_object(wp_get_theme()) && wp_get_theme()->template == 'flatsome' ) {
					add_action( 'woocommerce_after_add_to_cart_button', array($this->the_plugin, '_product_buy_url_html'), 1 );
				} else {
					add_action( 'woocommerce_after_single_product', array($this->the_plugin, '_product_buy_url_html'), 1 );
				}
				*/
				// 2017-oct-10 update
				add_filter( 'get_post_metadata', array($this->the_plugin, 'gpm_on_product_url'), 999, 4 );
			}

			add_filter( 'woocommerce_get_price_html', array($this, 'amz_disclaimer_price_html'), 100, 2 );

			if ( in_array( 'show_availability_icon', $this->the_plugin->frontend_show_what() ) ) {
				if ( 'yes' == $this->the_plugin->show_availability_icon ) {
					add_filter( 'woocommerce_get_availability', array($this, 'amz_availability'), 100, 2 );
				}
			}


			//-----------------------------------------------
			//:: woocommerce price /woocommerce/includes/class-product-pricing.php
			// .*_price | .*_regular_price | .*_sale_price
			/*
			//:: from woocommerce 3.0.0
			//simple, grouped and external product
			add_filter('woocommerce_product_get_price', array($this, 'custom_price'), -999, 2 );
			add_filter('woocommerce_product_get_regular_price', array($this, 'custom_price'), -999, 2 );
			add_filter('woocommerce_product_get_sale_price', array($this, 'custom_price'), -999, 2 );

			//product variations (of a variable product)
			add_filter('woocommerce_product_variation_get_price', array($this, 'custom_price'), -999, 2 );
			add_filter('woocommerce_product_variation_get_regular_price', array($this, 'custom_price'), -999, 2 );
			add_filter('woocommerce_product_variation_get_sale_price', array($this, 'custom_price'), -999, 2 );

			//variable product price range - execution takes too long!
			//add_filter('woocommerce_variation_prices_price', array($this, 'custom_variation_price'), -999, 3 );
			//add_filter('woocommerce_variation_prices_regular_price', array($this, 'custom_variation_price'), -999, 3 );
			//add_filter('woocommerce_variation_prices_sale_price', array($this, 'custom_variation_price'), -999, 3 );

			//fix for variable product price range/ for products listing page & product details page / product price range
			add_filter( 'woocommerce_get_price_html', array($this, '_get_price_html'), -999, 2 );

			//cart page: each item from cart
			add_filter( 'woocommerce_cart_item_price', array($this, '_cart_item_price'), -999, 3 );
			*/
			if ( $this->the_plugin->dropshiptax_is_active() ) {

				add_filter( 'woocommerce_variable_price_html', array( $this, 'woocommerce_variable_price_html' ), 10, 2 );
				//add_filter( 'woocommerce_variable_sale_price_html', array( $this, 'woocommerce_variable_price_html' ), 10, 2 );

				// woocommerce >= 3.6.0
				if ( version_compare( $this->the_plugin->get_woocommerce_version(), '3.6.0' ) >= 0 ) {

					// works on variable products!
					add_filter( 'get_post_metadata', array($this->the_plugin, 'gpm_on_price'), 999, 4 );

					// works on single products!
					add_filter('woocommerce_product_get_price', array($this, 'custom_price'), -999, 2 );
					add_filter('woocommerce_product_get_regular_price', array($this, 'custom_price'), -999, 2 );
				}
				// woocommerce < 3.6.0
				else {
					add_filter( 'get_post_metadata', array($this->the_plugin, 'gpm_on_price'), 999, 4 );
				}
			}
		}

		// /woocommerce/includes/abstracts/abstract-wc-product.php
		// 		extends /woocommerce/includes/abstracts/abstract-wc-data.php
		public function custom_price( $price, $product ) {

			$product_id = (int) $product->get_id();
			$product_type = (string) $product->get_type();

			$new_price = $price;
			if ( 'simple' === $product_type ) {

				// is amazon product? (also imported with woozonelite)
				// [FIX] on 2019-jul-02
				$isProdValid = $this->the_plugin->verify_product_is_amazon($product, array( 'verify_provider' => 'amazon' ));
				if ( $isProdValid !== true ) {
					return $new_price;
				}

				$new_price = $this->the_plugin->dropshiptax_price_global( $price );
			}

			return $new_price;
		}

		// /woocommerce/includes/class-wc-product-variable.php
		public function woocommerce_variable_price_html( $price, $product ) {

			$ret = $this->the_plugin->woocommerce_get_price_html_variable( $product, array(
				'do_dropshiptax' 	=> true,
			), 'price' );
			return $ret;
		}

		public function make_head() {
			$details = array('plugin_name' => 'WooZoneLite');

			if ( !has_action('WooZoneLite_header') )
				return true;
   
			ob_start();
		?>
			<!-- start/ frontend header/ <?php echo $details['plugin_name']; ?> -->
		<?php
			do_action( 'WooZoneLite_header' );
		?>
			<!-- end/ frontend header/ <?php echo $details['plugin_name']; ?> -->
		<?php
			$contents = ob_get_clean();
			echo $contents;
			return true;
		}

		public function make_footer() {
			$details = array('plugin_name' => 'WooZoneLite');

			if ( !has_action('WooZoneLite_footer') )
				return true;
   
			ob_start();
		?>
			<!-- start/ frontend footer/ <?php echo $details['plugin_name']; ?> -->
		<?php
			do_action( 'WooZoneLite_footer' );
			//$this->make_head();
		?>
			<!-- end/ frontend footer/ <?php echo $details['plugin_name']; ?> -->
		<?php
			$contents = ob_get_clean();
			echo $contents;
			return true;
		}

		public function frontend_custom_header() {
			$asof_font_size = isset($this->amz_settings['asof_font_size'])
				? (string) $this->amz_settings['asof_font_size'] : '0.6';

			ob_start();
		?>
			<style type="text/css">
				.WooZoneLite-price-info {
					font-size: <?php echo $asof_font_size; ?>em;
				}
				.woocommerce div.product p.price, .woocommerce div.product span.price {
					line-height: initial !important;
				}
			</style>
		<?php
			$contents = ob_get_clean();
			echo $contents;
		}

		public function frontend_custom_footer() {
			global $wp_query;

			echo "<!-- WooZoneLite version: " . ( WOOZONELITE_VERSION ) . " -->" . PHP_EOL.PHP_EOL;

			// woocommerce-tabs amazon fix
			echo PHP_EOL . "<!-- start/ woocommerce-tabs amazon fix -->" . PHP_EOL;
			echo '<script type="text/javascript">' . PHP_EOL;
			echo "jQuery('.woocommerce-tabs #tab-description .aplus p img[height=1]').css({ 'height': '1px' });". PHP_EOL;
			echo '</script>' . PHP_EOL;
			echo "<!-- end/ woocommerce-tabs amazon fix -->" . PHP_EOL.PHP_EOL;

			$current_amazon_aff = $this->the_plugin->_get_current_amazon_aff();
			$current_amazon_aff = json_encode( $current_amazon_aff );
			$current_amazon_aff = htmlentities( $current_amazon_aff );
			echo '<span id="WooZoneLite_current_aff" class="display: none;" data-current_aff="' . $current_amazon_aff . '"></span>';

			$__wp_query = null;

			if ( !$wp_query->is_main_query() ) {
				$__wp_query = $wp_query;
				wp_reset_query();
			}

			if ( !empty($__wp_query) ) {
				$GLOBALS['wp_query'] = $__wp_query;
				unset( $__wp_query );
			}
		}

		/**
		 * Inits...
		 */
		// wp enqueue scripts & stypes
		public function wp_enqueue_scripts() {

			if( !wp_script_is('thickbox') ) {
				wp_enqueue_script('thickbox', null,  array('jquery'));
			}
			if( !wp_style_is('thickbox.css') ) {
				wp_enqueue_style('thickbox.css',  WooZoneLite_asset_path( 'css', '/' . WPINC . '/js/thickbox/thickbox.css', true ), null, WooZoneLite_asset_version( 'css' ));
			}

			if( !wp_style_is($this->alias . '-frontend-style') ) {
				wp_enqueue_style( $this->alias . '-frontend-style', WooZoneLite_asset_path( 'css', $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'lib/frontend/css/frontend.css', true ), array(), WooZoneLite_asset_version( 'css' ) );
			}
			
			if( !wp_script_is($this->alias . '-frontend-script') ) {
				wp_enqueue_script( $this->alias . '-frontend-script' , WooZoneLite_asset_path( 'js', $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'lib/frontend/js/frontend.js', true ), array( 'jquery' ), WooZoneLite_asset_version( 'js' ) );

				$_checkout_url = wc_get_checkout_url();
				$_checkout_url = is_string($_checkout_url) ? esc_url( $_checkout_url ) : '';

				$vars = array(
					'ajax_url'				=> admin_url('admin-ajax.php'),
					'checkout_url' 		=> $_checkout_url,
					'lang' 					=> array(
						'loading'								=> WooZoneLite()->_translate_string( 'Loading...' ),
						'closing'                   			=> WooZoneLite()->_translate_string( 'Closing...' ),
						'saving'                   				=> WooZoneLite()->_translate_string( 'Saving...' ),
						'amzcart_checkout'       				=> WooZoneLite()->_translate_string( 'checkout done' ),
						'amzcart_cancel' 						=> WooZoneLite()->_translate_string( 'canceled' ),
						'amzcart_checkout_msg'					=> WooZoneLite()->_translate_string( 'all good' ),
						'amzcart_cancel_msg'					=> WooZoneLite()->_translate_string( 'You must check or cancel all amazon shops!' ),
						'available_yes'							=> WooZoneLite()->_translate_string( 'available' ),
						'available_no' 							=> WooZoneLite()->_translate_string( 'not available' ),
						'load_cross_sell_box'					=> WooZoneLite()->_translate_string( 'Frequently Bought Together' ) . ' ' . WooZoneLite()->_translate_string( 'Loading...' ),
					),
				);
				wp_localize_script( 'WooZoneLite-frontend-script', 'woozonelite_vars', $vars );
			}
		}

		// !!! DO NOT REMOVE THE BELLOW COMMENTED CODE - COULD BE USEFULL...
		/*
		public function custom_price( $price, $product ) {
			//ob_start(); var_dump('<pre>----', $product->get_type(), $product->get_id(), $price, '</pre>'); $buffer = ob_get_clean();
			//$has_wrote = file_put_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '_gimi.txt', $buffer, FILE_APPEND );

			return $this->_custom_price( $price, $product );
		}

		public function custom_variation_price( $variation_price, $variation, $product ) {
			//ob_start(); var_dump('<pre>----', $product->get_type(), $variation->get_parent_id(), $variation->get_variation_id(), $variation_price, '</pre>'); $buffer = ob_get_clean();
			//$has_wrote = file_put_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '_gimi2.txt', $buffer, FILE_APPEND );

			//if ( !empty( $variation_get_price_edit ) ) {
			return $this->_custom_price( $variation_price, $product );
			//}
		}

		public function _custom_price( $price, $product ) {

			$product_type = $product->get_type();
			//$product_id = 'variation' == $product_type ? $product->get_parent_id() : $product->get_id();

			$new_price = $price;
			if ( 'simple' == $product_type ) {
				$new_price = 2.32;
			}
			else if ( 'variable' == $product_type ) {
				$new_price = $this->frand( 2.33, 2.37, 2 );
			}
			else if ( 'variation' == $product_type ) {
				$new_price = 2.38;
			}
			return $new_price;
		}

		public function _get_price_html( $price_html, $product ) {
			$prod_id = 0;
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}

			$post_id = $prod_id;
			if ( $post_id <=0 ) return $price_html;

			//if ( !is_product() || !$product->get_price() || ( $this->the_plugin->verify_product_is_amazon($post_id, array( 'verify_provider' => false )) !== true ) ) {
			//	return $price_html;
			//}

			$product_type = $product->get_type();
			//var_dump('<pre>belone',$product_type, $price_html ,'</pre>');
			//return $price_html;

			//var_dump('<pre>',$price_html ,'</pre>');
			$text = 'NEW';
			if (strpos($price_html, '</del>') !== false) {
				return $this->the_plugin->u->str_replace_last( '</del>', '</del>' . $text, $price_html );
			}
			else if (strpos($price_html, '</ins>') !== false) {
				return $this->the_plugin->u->str_replace_last( '</ins>', '</ins>' . $text, $price_html );
			}
			else {
				return $this->the_plugin->u->str_replace_last( '</span>', '</span>' . $text, $price_html );
			}
		}

		public function _cart_item_price( $price, $cart_item, $cart_item_key ) {
			//var_dump('<pre>',$price, $cart_item_key, $cart_item ,'</pre>'); 
			return $price .'never';
		}
		*/



		//====================================================================================
		//== COUNTRY AVAILABILITY
		//====================================================================================

		/**
		 * Hooks functions
		 */
		// wp 'hooks' functions
		// amazon shops checkout on cart page
		public function woocommerce_after_cart() {
			//$is_cart_page = is_cart();
			//if ( ! $is_cart_page ) return ;

			if ( $this->the_plugin->disable_amazon_checkout ) {
				return ;
			}

			$shops = $this->woo_cart_get_amazon_prods_bycountry();
			if ( empty($shops) ) return false;

			$is_multiple = $this->woo_cart_is_amazon_multiple( $shops );
			if ( empty($is_multiple) || $is_multiple <= 1 ) return false;

			$box = $this->box_amazon_shops_checkout( array(
				'where' 	=> 'cart',
				'shops' 	=> $shops,
			));
			if ( !empty($box) ) {
				echo $box;
			}
		}

		// product country on product details page
		public function woocommerce_single_product_summary() {
			global $product;

			// 20.jul.2018 - kingdom fix
			$show_hox = true;
			if( $this->the_plugin->disable_amazon_checkout ){
				if ( ! in_array( 'product_countries', $this->the_plugin->frontend_show_what() ) ) {
					$show_hox = false;
				}
			}

			if( $show_hox ){
				$box = $this->box_country_check_details( $product );
				if ( !empty($box) )
					echo $box;
			}
		}
		
		// product country on main cart
		public function woocommerce_cart_item_quantity($product_quantity, $cart_item_key, $cart_item=null) {
			$str = $product_quantity;

			// theme: kingdom
			if ( empty($cart_item) ) {
				$cart_items_nb = (int) WC()->cart->get_cart_contents_count();
				if ( $cart_items_nb )
					$cart_item = WC()->cart->get_cart_item( $cart_item_key);
			}

			$box = $this->box_country_check_small( isset($cart_item['product_id']) ? $cart_item['product_id'] : 0 );
			if ( !empty($box) ) {
				//$str .= $box;
				$str = str_replace('</div>', $box . '</div>', $str);
			}
			echo $str;
		}
		
		// product country on mini cart
		public function woocommerce_widget_cart_item_quantity($product_quantity, $cart_item, $cart_item_key) {
			$str = $product_quantity;
			$box = $this->box_country_check_small( isset($cart_item['product_id']) ? $cart_item['product_id'] : 0 );
			if ( !empty($box) ) {
				//$str .= $box;
				$str = str_replace('</span></span>', '</span></span>' . $box, $str);
			}
			echo $str;
		}
		public function widget_cart_item_quantity() {
			$pms = array('box_position' => 'minicart');
			$box = $this->box_country_check_minicart( $pms );
			if ( !empty($box) )
				echo $box;
		}
		
		// main box as woocommerce tab
		public function woocommerce_product_tabs( $tabs ) {
			$tabs['woozonelite_tab_countries_availability'] = array(
				'title'				=> WooZoneLite()->_translate_string( 'Countries availability' ),
				'priority'		=> 15,
				'callback'		=> array($this, 'woo_tab_countries_availability')
			);

			return $tabs;
		}
		public function woo_tab_countries_availability( $tab ) {
			global $product;

			$box = $this->box_country_check_details( $product );
			if ( !empty($box) )
				echo $box;
		}

		// main box positioning
		public function single_product_summary( $pms=array() ) {
			$is_product_page = is_product();
			if ( !$is_product_page ) return;

			global $product;

			$box = $this->box_country_check_details( $product, $pms );
			if ( !empty($box) )
				echo $box;
		}
		public function before_add_to_cart() {
			$this->single_product_summary( array('box_position' => 'before_add_to_cart') );
		}
		public function before_title_and_thumb() {
			$this->single_product_summary( array('box_position' => 'before_title_and_thumb') );
		}
		public function before_woocommerce_tabs() {
			$this->single_product_summary( array('box_position' => 'before_woocommerce_tabs') );
		}
		
		
		// build minicart box with product country check
		private function box_country_check_minicart( $pms=array() ) {
			// parameters
			$pms = array_merge(array(
				'with_wrapper'			=> true,
				'box_position'			=> false,
			), $pms);
			extract($pms);
			
			// theme: kingdom
			$cart_items_nb = (int) WC()->cart->get_cart_contents_count();
			if ( !$cart_items_nb )
				return false;

			$minicart_items = array();

			$cart_items = WC()->cart->get_cart();
			foreach ( $cart_items as $key => $value ) {

				//$prod_id = isset($value['variation_id']) && (int)$value['variation_id'] > 0 ? $value['variation_id'] : $value['product_id'];
				$product_id = $value['product_id'];

				$asin = get_post_meta( $product_id, '_amzASIN', true );
				if ( empty($asin) ) continue 1;

				$product_country = $this->get_product_country_current( $product_id );
				$product_country__ = $product_country;
				if ( !empty($product_country) && isset($product_country['website']) ) {
					$product_country = substr($product_country['website'], 1);
				}
				
				$country_name = $product_country__['name'];
				
				$country_status = $product_country__['available'];
				$country_status_css = 'available-todo'; $country_status_text = WooZoneLite()->_translate_string( 'not verified yet' );
				switch ($country_status) {
					case 1:
						$country_status_css = 'available-yes';
						$country_status_text = WooZoneLite()->_translate_string( 'is available' );
						break;
						
					case 0:
						$country_status_css = 'available-no';
						$country_status_text = WooZoneLite()->_translate_string( 'not available' );
						break;
				}
				
				$minicart_items[] = array(
					'cart_item_key'				=> $key,
					'product_id'					=> $product_id,
					'asin'								=> $asin,
					'product_country'			=> $product_country,
					'country_name'				=> $country_name,
					'country_status_css'		=> $country_status_css,
					'country_status_text'	=> $country_status_text,
				);
			}

			ob_start();
		?>

<div class="WooZoneLite-cc-small-cached" style="display: none;"><?php echo json_encode( $minicart_items ); ?></div>
<script type="text/template" id="WooZoneLite-cc-small-template">
	<span class="WooZoneLite-country-check-small WooZoneLite-cc-custom">
		
		<span>
			<span class="WooZoneLite-cc_domain"></span>
			<span class="WooZoneLite-cc_status"></span>
		</span>

	</span>
</script>

		<?php
			$contents = ob_get_clean();
			return $contents;
		}

		// build small box with product country check
		private function box_country_check_small( $product, $pms=array() ) {
			// get product id
			$product_id = $product;
			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				$product_id = $prod_id;
			}
			if ( empty($product_id) ) return false;

			// parameters
			$pms = array_merge(array(
				'with_wrapper'			=> true,
				'box_position'			=> false,
			), $pms);
			extract($pms);

			// get asin meta key
			$asin = get_post_meta($product_id, '_amzASIN', true);

			if ( empty($asin) ) return false; // verify to be amazon product!

			$first_variation_asin = $this->get_asin_first_variation( $product_id );
			if( $first_variation_asin !== false ){
				$asin = $first_variation_asin;
			}
			//$asin = 'B000P0ZSHK'; // DEBUG
			//var_dump('<pre>',$asin,'</pre>');

			$product_country = $this->get_product_country_current( $product_id );
			$product_country__ = $product_country;
			//var_dump('<pre>', $product_id, $product_country, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( !empty($product_country) && isset($product_country['website']) ) {
				$product_country = substr($product_country['website'], 1);
			}
			
			//$all_countries_affid = $this->the_plugin->get_ws_object( $this->the_plugin->cur_provider )->get_countries('main_aff_id');
			//$country_affid = $product_country__['key'];
			//$country_name = isset($all_countries_affid["$country_affid"]) ? $all_countries_affid["$country_affid"] : 'missing country name';
			$country_name = $product_country__['name'];

			$country_status = $product_country__['available'];
			$country_status_css = 'available-todo'; $country_status_text = WooZoneLite()->_translate_string( 'not verified yet' );
			switch ($country_status) {
				case 1:
					$country_status_css = 'available-yes';
					$country_status_text = WooZoneLite()->_translate_string( 'is available' );
					break;
					
				case 0:
					$country_status_css = 'available-no';
					$country_status_text = WooZoneLite()->_translate_string( 'not available' );
					break;
			}

			ob_start();
		?>

<?php if ($with_wrapper) { ?>
<span class="WooZoneLite-country-check-small" data-prodid="<?php echo $product_id; ?>" data-asin="<?php echo $asin; ?>" data-prodcountry="<?php echo $product_country; ?>">
<?php } ?>

		<span>
			<span class="WooZoneLite-cc_domain <?php echo str_replace('.', '-', $product_country); ?>" title="<?php echo $country_name; ?>"></span>
			<span class="WooZoneLite-cc_status <?php echo $country_status_css; ?>" title="<?php echo $country_status_text; ?>"></span>
		</span>

<?php if ($with_wrapper) { ?>
</span>
<?php } ?>

		<?php
			$contents = ob_get_clean();
			return $contents;
		}

		// build main box with product country check
		private function box_country_check_details( $product, $pms=array() ) {
			// get product id
			$product_id = $product;
			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				$product_id = $prod_id;
			}
			if ( empty($product_id) ) return false;

			// parameters
			$pms = array_merge(array(
				'with_wrapper'			=> true,
				'box_position'			=> false,
			), $pms);
			extract($pms);
			
			// get asin meta key
			$asin = get_post_meta($product_id, '_amzASIN', true);
			if ( empty($asin) ) return false; // verify to be amazon product!


			$first_variation_asin = $this->get_asin_first_variation( $product_id );
			if( $first_variation_asin !== false ){
				$asin = $first_variation_asin;
			}

			//$asin = 'B000P0ZSHK'; // DEBUG
			//var_dump('<pre>',$asin,'</pre>');
			
			$available_countries = $this->get_product_countries_available( $product_id );
			//var_dump('<pre>', $product_id, $available_countries, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;   
			if ( empty($available_countries) ) return false;

			$product_country = $this->get_product_country_current( $product_id );
			//var_dump('<pre>', $product_id, $product_country, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( !empty($product_country) && isset($product_country['website']) ) {
				$product_country = substr($product_country['website'], 1);
			}
			
			// aff ids
			$aff_ids = $this->the_plugin->get_aff_ids();


			//:: get template
			$contents = WooZoneLite_get_template_html( 'country_check/box_big.php', array_replace_recursive( array(
				'with_wrapper' 				=> $with_wrapper,
				'box_position' 				=> $box_position,
				'product_id' 				=> $product_id,
				'asin' 						=> $asin,
				'product_country' 			=> $product_country,
				'available_countries' 		=> $available_countries,
				'aff_ids' 					=> $aff_ids,
				'p_type' 					=> $this->p_type,
				'countryflags_aslink' 		=> $this->countryflags_aslink,
			), $pms ));

			//ob_start();
			//$contents = ob_get_clean();
			return $contents;
		}


		// box: multiple amazon shops (on cart page or on admin order details page)
		// WHEN WE HAVE PRODUCTS FROM MULTIPLE AMAZON STORES / COUNTRIES
		public function box_amazon_shops_checkout( $pms=array() ) {

			$pms = array_replace_recursive( array(
				'where' => 'cart',
				'shops' => array(),
				'totals' => array(),
				'order_id' => 0,
				'order_info' => array(),
			), $pms );
			extract( $pms );

			if ( empty($shops) ) return false;


			//:: get template
			$contents = WooZoneLite_get_template_html( 'amazon/checkout_multishops.php', array_replace_recursive( array(
				'where' 					=> $where,
				'shops' 					=> $shops,
			), $pms ));

			//ob_start();
			//$contents = ob_get_clean();
			return $contents;
		}



		//====================================================================================
		//== CART & CHECKOUT RELATED
		//====================================================================================

		public function redirect_amazon( $redirect_asin='' ) {

			if ( empty($redirect_asin) ) return '';

			$provider = $this->the_plugin->prodid_get_provider_by_asin( $redirect_asin );
			$asin = $this->the_plugin->prodid_get_asin($redirect_asin);
			//var_dump('<pre>', $provider, $asin , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			
			$product = $this->the_plugin->get_product_by_wsid( $redirect_asin );
			if ( empty($product) || !isset($product['ID']) ) return '';

			$post_id = $product['ID'];
			
			$this->cur_provider = $provider;

			$config = $this->amz_settings;

			if( isset($redirect_asin) && trim($redirect_asin) != "" ){
				//$post_id = $this->the_plugin->get_post_id_by_meta_key_and_value('_amzASIN', $redirect_asin);
								
				$redirect_to_amz = (int) get_post_meta($post_id, '_amzaff_redirect_to_amazon', true);
				update_post_meta($post_id, '_amzaff_redirect_to_amazon', (int)($redirect_to_amz+1));
								
				$redirect_to_amz2 = (int) get_post_meta($post_id, '_amzaff_redirect_to_amazon_prev', true);
				update_post_meta($post_id, '_amzaff_redirect_to_amazon_prev', (int)($redirect_to_amz2+1));
			}

			if ( in_array($provider, array('amazon', 'alibaba', 'envato', 'ebay')) ) {
				//$user_country = $this->get_country_perip_external();
			}

			// PER PROVIDER
			if ( 'amazon' == $provider ) {

				/*$get_user_location = wp_remote_get( 'http://api.hostip.info/country.php?ip=' . $_SERVER["REMOTE_ADDR"] );
				if( isset($get_user_location->errors) ) {
					$main_aff_site = $this->main_aff_site();
					$user_country = $this->amzForUser( strtoupper(str_replace(".", '', $main_aff_site)) );
				}else{
					$user_country = $this->amzForUser($get_user_location['body']);
				}*/
				//$user_country = $this->the_plugin->get_country_perip_external();

				$user_country = $this->get_product_country_current( $post_id );
				if ( empty($user_country) || ! isset($user_country['website']) ) {
					$user_country = $this->the_plugin->get_country_perip_external();
				}

				if ( isset($config["90day_cookie"]) && $config["90day_cookie"] == 'yes' ) {
			?>
				<form id="amzRedirect" method="GET" action="//www.amazon<?php echo $user_country['website'];?>/gp/aws/cart/add.html">
					<input type="hidden" name="AssociateTag" value="<?php echo $user_country['affID'];?>"/> 
					<input type="hidden" name="SubscriptionId" value="<?php echo $config['AccessKeyID'];?>"/> 
					<input type="hidden" name="ASIN.1" value="<?php echo $asin;?>"/>
					<input type="hidden" name="Quantity.1" value="1"/> 
				</form>
			<?php 
				die('
					<script>
					setTimeout(function() {
							document.getElementById("amzRedirect").submit();
					}, 1);
					</script>
				');
				}
				else {
					$link = 'http://www.amazon' . ( $user_country['website'] ) . '/gp/product/' . ( $asin ) . '/?tag=' . ( $user_country['affID'] ) . '';
			
					die('<meta http-equiv="refresh" content="0; url=' . ( $link ) . '">');
				/*<!--form id="amzRedirect" method="GET" action="<?php echo $link;?>"></form-->*/
				}
			}
			else if ( in_array($provider, array('alibaba', 'envato', 'ebay')) ) {

				$product_id = $post_id;

				// http://s.click.aliexpress.com/e/M3qYbAVb
				$link = get_post_meta($product_id, '_amzaff_product_url', true);
				//var_dump('<pre>', $product, $link, '</pre>'); die('debug...');
				
				if ( 'envato' == $provider ) {
					$affid = isset($this->amz_settings['envato_AffId']) ? $this->amz_settings['envato_AffId'] : '';
					$link .= '?ref=' . $affid;
				}
				else if ( 'ebay' == $provider ) {
					$link_ = $this->the_plugin->get_ws_object( 'ebay' )->get_product_link( array(
						'prod_id'		=> $asin,
						'prod_link'		=> $link,
					));
					if ( !empty($link_) ) {
						$link = $link_;
					}
				}
				
				if ( empty($link) ) {
					die('no product url defined!');
				} 
		
				die('<meta http-equiv="refresh" content="0; url=' . ( $link ) . '">');

			}
		}

		public function woocommerce_external_add_to_cart() {

			global $product;

			//:: product info
			if ( ! is_product() ) {
				return true;
			}

			if ( ! is_object( $product) ) {
				$product = wc_get_product( get_the_ID() );
			}

			if ( ! is_object( $product) ) {
				return true;
			}

			$prod_id = 0;
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}

			$product_type = '';
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_type' ) ) {
					$product_type = (string) $product->get_type();
				} else if ( isset($product->product_type) && (string) $product->product_type > 0 ) {
					$product_type = (string) $product->product_type;
				}
			}

			$asin = WooZoneLite_get_post_meta($prod_id, '_amzASIN', true);
			$prod_provider = $this->the_plugin->prodid_get_provider_by_asin( $asin );
			$sufix = 'amazon' === $prod_provider ? '' : '_' . $prod_provider;

			$prod_link_open_in = isset($this->amz_settings["product_buy_button_open_in{$sufix}"])
				? $this->amz_settings["product_buy_button_open_in{$sufix}"] : '';
			$prod_link_open_in = ! empty($prod_link_open_in) ? $prod_link_open_in : '_blank';

			if ( '_blank' == $prod_link_open_in && 'external' == $product_type ) {
				$html = array();
				ob_start();
?>
				<script>
				(function( w, d, $, undefined ) {
					"use strict";

					//console.log( 'external product button', $(".single_add_to_cart_button") );
					$(document).ready(function() {
						var btn 		= $(".single_add_to_cart_button"),
							btn_type 	= btn.length ? btn.prop('type') : '',
							form 		= btn.length ? btn.parents('form:first') : $(document.createDocumentFragment()),
							newurl 		= form.length ? form.prop('action') : '';
						console.log( btn, btn_type, form );

						//the formtarget attribute is only used for buttons with type="submit" /html5
						if ( 'submit' == btn_type ) {
							btn.attr( "formtarget", "_blank" );
							<?php /*//btn.attr( 'onclick', "parent.location = " + newurl + ";" );
							$(document).on( 'click', '.single_add_to_cart_button', function(e) {
								e.preventDefault();
								//$(location).attr( 'href', newurl );
							});*/ ?>
						}
						else if ( btn.length ) {
							btn.attr( "target", "_blank" );
						}
					});
				})( window, document, jQuery );
				</script>
<?php
				$html[] = ob_get_clean();
				echo implode( PHP_EOL, $html );
				//echo '<script>jQuery(".single_add_to_cart_button").attr("target", "_blank");</script>';
			}
		}

		public function woocommerce_external_checkout() {
			if( is_checkout() == true ){
				$this->redirect_cart();
			}
		}
		
		public function redirect_cart() {
			//global $woocommerce;

			$shops = $this->woo_cart_get_amazon_prods_bycountry();

			$is_multiple = $this->woo_cart_is_amazon_multiple( $shops );
			if ( empty($is_multiple) ) return true;

			// more than 1 amazon shops: product belonging to different amazon shops
			if ( $is_multiple > 1 ) {
				$this->woo_cart_update_meta_amazon_prods();
				$this->woo_cart_delete_amazon_prods();
				//echo '<script>setTimeout(function() { window.location.reload(true); }, 1);</script>';

				// this is done using woocommerce hook 'woocommerce_after_cart'
				return true;
			}

			// single amazon shops: all products from cart will go to single amazon shop at checkout
			foreach ($shops as $key => $value) {
				if ( empty($value) ) continue 1;

				$domain = $value['domain'];
				$affID = $value['affID'];
				$country_name = $value['name'];
				$products = $value['products'];
				$nb_products = count($products);
			}
			//var_dump('<pre>', $domain, $affID, $country_name, $nb_products, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			if ( ! $nb_products ) return true;

			$html = array();
			if ( isset($this->amz_settings["redirect_checkout_msg"]) && trim($this->amz_settings["redirect_checkout_msg"]) != "" ) {
				$html[] = '<img src="' . ( $this->the_plugin->cfg['paths']['freamwork_dir_url'] . 'images/checkout_loading.gif'  ) . '" style="margin: 10px auto;">';
				$html[] = "<h3>" . ( str_replace( '{amazon_website}', 'www.amazon.' . $domain, $this->amz_settings["redirect_checkout_msg"]) ) . "</h3>";
			}

			//$checkout_type =  isset($this->amz_settings['checkout_type']) && $this->amz_settings['checkout_type'] == '_blank' ? '_blank' : '_self';
			$checkout_type = '_self';

			ob_start();
			?>

			<form target="<?php echo $checkout_type;?>" id="amzRedirect" method="GET" action="//www.amazon.<?php echo $domain; ?>/gp/aws/cart/add.html">
				<input type="hidden" name="AssociateTag" value="<?php echo $affID;?>"/>
				<?php /*<input type="hidden" name="SubscriptionId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>*/ ?>
				<input type="hidden" name="AWSAccessKeyId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>
				<?php 
					$cc = 1; 
					foreach ($products as $key => $value){
				?>      
						<input type="hidden" name="ASIN.<?php echo $cc;?>" value="<?php echo $value['asin'];?>"/>
						<input type="hidden" name="Quantity.<?php echo $cc;?>" value="<?php echo $value['quantity'];?>"/>
				<?php
						$cc++;
					} // end foreach

					$redirect_in = isset($this->amz_settings['redirect_time']) && (int) $this->amz_settings['redirect_time'] > 0 ? ( (int) $this->amz_settings['redirect_time'] * 1000 ) : 1;
				?>
			</form>

			<script type="text/javascript">
				setTimeout(function() {
					document.getElementById("amzRedirect").submit();
					<?php 
						//if( (int)$woocommerce->cart->cart_contents_count > 0 && $checkout_type == '_blank' ){
						if ( $nb_products && $checkout_type == '_blank' ) {
					?>
					setTimeout(function() { window.location.reload(true); }, 1);
					<?php
						}
					?>
				}, <?php echo $redirect_in;?>);
			</script>

			<?php 
			$html[] = ob_get_contents(); //ob_clean();
			echo implode(PHP_EOL, $html);

			$this->woo_cart_update_meta_amazon_prods();
			$this->woo_cart_delete_amazon_prods();
			exit();
			return true;
		}

		// get product available amazon countries shops
		public function get_product_countries_available( $product ) {
			// get product id
			$product_id = $product;
			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				$product_id = $prod_id;
			}
			if ( empty($product_id) ) return false;

			// amazon location & main affiliate ids
			$affIds = (array) ( isset($this->amz_settings['AffiliateID']) ? $this->amz_settings['AffiliateID'] : array() );
			if ( empty($affIds) ) return false;

			$main_aff_id = $this->the_plugin->main_aff_id();
			$main_aff_site = $this->the_plugin->main_aff_site();

			// countries
			$all_countries = $this->the_plugin->get_ws_object( 'amazon' )->get_countries('country');
			$all_countries_affid = $this->the_plugin->get_ws_object( 'amazon' )->get_countries('main_aff_id');

			// loop through setted affiliate ids from amazon config
			$available = array(); $cc = 0;
			foreach ($affIds as $key => $val) {
				if ( empty($val) ) continue 1;

				$convertCountry = $this->the_plugin->discount_convert_country2country();
				$domain = isset($convertCountry['amzwebsite']["$key"]) ? $convertCountry['amzwebsite']["$key"] : '';
				if ( empty($domain) ) continue 1;

				$available[$cc] = array(
					'domain'	=> $domain,
					'name'		=> isset($all_countries_affid["$key"]) ? $all_countries_affid["$key"] : 'missing country name',
				);
				$cc++;
			}
			if ( empty($available) ) return false;

			// verify affiliate ids based on product cached/saved available countries
			$meta_frontend = get_post_meta($product_id, '_amzaff_frontend', true);
			$cache_countries = isset($meta_frontend['countries']) && is_array($meta_frontend['countries']) ? $meta_frontend['countries'] : array();
			$cache_time = isset($meta_frontend['countries_cache_time']) ? $meta_frontend['countries_cache_time'] : 0;

			$cache_need_refresh = empty($cache_countries)
				|| !$cache_time
				|| ( ($cache_time + $this->the_plugin->ss['countries_cache_time']) < time() );
			//var_dump('<pre>', $cache_need_refresh, $available , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			// product amazon countries availability needs refresh (mandatory)
			if ( $cache_need_refresh ) return $available;

			// may need refresh if one country availability verification is missing!
			// verification for refresh is done in javascript/json based on 'available' key
			foreach ($available as $key => $val) {
				foreach ($cache_countries as $key2 => $val2) {
					// country founded
					if ( isset($val2['domain'], $val2['available']) && ($val['domain'] == $val2['domain']) ) {
						$available["$key"]['available'] = $val2['available'];
						break 1;
					}
				}
			}
			//var_dump('<pre>', $available , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			return $available;
		}

		// get product default country when added to cart (based on client country and main affiliate id)
		public function get_product_country_default( $product, $find_client_country=true ) {
			// get product id
			$product_id = $product;
			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				$product_id = $prod_id;
			}
			if ( empty($product_id) ) return false;

			// client country
			$client_country = false;
			if ( $find_client_country ) {
				$client_country = $this->the_plugin->get_country_perip_external();
			}
			//var_dump('<pre>', $client_country, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			// return is of type:
			//array(3) {
			//	["key"]			=> string(3) "com"
			//	["website"]	=> string(4) ".com"
			//	["affID"]		=> string(8) "jimmy-us"
			//}

			// product available countries
			$available_countries = $this->get_product_countries_available( $product_id );
			$found = false; $first = false; $first_available = false;
			//var_dump('<pre>', $product_id, $available_countries, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			if ( !empty($available_countries) ) {
				foreach ($available_countries as $key => $val) {

					if ( empty($first) )
						$first = $val['domain'];

					if ( isset($val['available']) ) {
						if ( empty($first) )
							$first = $val['domain'];
						if ( empty($first_available) && $val['available'] )
							$first_available = $val['domain'];
					}
  
					if ( ! empty($client_country) && isset($client_country['website'])
						&& substr($client_country['website'], 1) == $val['domain'] ) {
						$found = $val['domain'];
					}
				}
			}
			//var_dump('<pre>',$found, $first, $first_available,'</pre>');  

			// default country based on: first from all valid countries, first available country or found client country
			$the_country = false;
			if ( !empty($first) ) 
				$the_country = $first;
			if ( !empty($first_available) ) 
				$the_country = $first_available;
			if ( !empty($found) ) 
				$the_country = $found;

			$country = $this->the_plugin->domain2amzForUser( $the_country );
			if ( !empty($available_countries) ) {
				foreach ($available_countries as $key => $val) {
					if ( substr($country['website'], 1) == $val['domain'] ) {
						$country = array_merge($country, array(
							'name'			=> $val['name'],
							'available'		=> isset($val['available']) ? $val['available'] : -1,
						));
					}
				}
			}
			return $country;
		}

		// get product current country when added to cart (based on default country and if client choose a country by himself)
		public function get_product_country_current( $product, $find_client_country=true ) {
			// get product id
			$product_id = $product;
			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				$product_id = $prod_id;
			}
			if ( empty($product_id) ) return false;

			$the_country = $this->get_product_country_default( $product_id, $find_client_country );
			$country = $the_country;

			// get asin meta key
			$asin = get_post_meta($product_id, '_amzASIN', true);
			$first_variation_asin = $this->get_asin_first_variation( $product_id );
			if( $first_variation_asin !== false ){
				$asin = $first_variation_asin;
			}
			//var_dump('<pre>',$asin,'</pre>');

			//unset($_SESSION['WooZoneLite']);
			//var_dump('<pre>', $the_country, $_SESSION, '</pre>');

			if ( !empty($asin)
				 && isset(
					$_SESSION['WooZoneLite'],
					$_SESSION['WooZoneLite']['product_country'],
					$_SESSION['WooZoneLite']['product_country']["$asin"]
				 )
				 && !empty($_SESSION['WooZoneLite']['product_country']["$asin"])
			) {
				$sess_country = $_SESSION['WooZoneLite']['product_country']["$asin"];

				// product available countries
				$available_countries = $this->get_product_countries_available( $product_id );

				if ( !empty($available_countries) ) {
					foreach ($available_countries as $key => $val) {

						if ( $sess_country == $val['domain'] ) {
							$the_country = $sess_country;
							$country = $this->the_plugin->domain2amzForUser( $the_country );
							$country = array_merge($country, array(
								'name'			=> $val['name'],
								'available'		=> isset($val['available']) ? $val['available'] : -1,
							));
						}
					}
				}
			}

			return $country;
		}
		
		// get amazon products from cart
		public function woo_cart_get_amazon_prods() {
			//global $woocommerce;

			$amz_products = array();

			$cart_items_nb = (int) WC()->cart->get_cart_contents_count();
			if ( ! $cart_items_nb ) return false;

			$cart_items = WC()->cart->get_cart();
			//var_dump('<pre>', $cart_items, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			foreach ($cart_items as $key => $value) {

				$prod_id = isset($value['variation_id']) && (int)$value['variation_id'] > 0 ? $value['variation_id'] : $value['product_id'];
				$amzASIN = $prod_id ? get_post_meta( $prod_id, '_amzASIN', true ) : '';
				
				$parent_id = isset($value['variation_id']) && (int)$value['variation_id'] > 0 ? $value['product_id'] : 0;
				$parent_amzASIN = $parent_id ? get_post_meta( $parent_id, '_amzASIN', true ) : '';

				//if ( empty($amzASIN) || strlen($amzASIN) != 10 )
				if ( empty($amzASIN) ) continue 1;

				//$meta_amzResp = get_post_meta($prod_id, '_amzaff_amzRespPrice', true);

				$amz_products["$key"] = array(
					'cart_item_key'				=> $key,
					'product_id'				=> $prod_id,
					'asin'						=> $amzASIN,
					'parent_id'					=> $parent_id,
					'parent_asin' 				=> $parent_amzASIN,
					'quantity'					=> $value['quantity'],
				);
			} // end foreach
	
			return $amz_products;
		}
		
		// get amazon products from cart by country availability
		public function woo_cart_get_amazon_prods_bycountry() {
			$prods = $this->woo_cart_get_amazon_prods();
			//var_dump('<pre>', $prods, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			if ( empty($prods) ) return false;

			foreach ($prods as $key => $value) {
				$prod_id = $value['parent_id'] ? $value['parent_id'] : $value['product_id'];
				$product_country = $this->get_product_country_current( $prod_id );

				//$prods["$key"] = array_merge($prods["$key"], $product_country);
				$prods["$key"]['countryinfo'] = $product_country;
			} // end foreach
			//var_dump('<pre>', $prods, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$bycountry = array();
			foreach ($prods as $key => $value) {
				//$domain = substr($value['website'], 1);
				$domain = substr($value['countryinfo']['website'], 1);

				if ( ! isset($bycountry["$domain"]) ) {
					$bycountry["$domain"] = array(
						'domain'			=> $domain,
						'affID'				=> $value['countryinfo']['affID'], //$value['affID'],
						'name'				=> $value['countryinfo']['name'], //$value['name'],
						'products'			=> array(),
					);
				}
				$bycountry["$domain"]["products"]["$key"] = $value;
			} // end foreach
			//var_dump('<pre>', $bycountry, '</pre>');    

			return $bycountry;
		}

		// woocommerce cart contains multiple amazon shops
		public function woo_cart_is_amazon_multiple( $shops=array() ) {
			if ( empty($shops) )
				$shops = $this->woo_cart_get_amazon_prods_bycountry();
			if ( empty($shops) ) return false;

			$domains = array();
			foreach ($shops as $key => $value) {
				if ( empty($value) ) continue 1;

				$domain = $value['domain'];
				if ( ! in_array($domain, $domains) )
					$domains[] = $domain;
			}
			return count($domains);
		}
		
		// update meta (redirect to amazon) for amazon products from cart
		public function woo_cart_update_meta_amazon_prods() {
			$prods = $this->woo_cart_get_amazon_prods();
			//var_dump('<pre>', $prods, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			if ( empty($prods) ) return false;
   
			foreach ($prods as $key => $value) {
				if ( ! isset($value['asin']) || trim($value['asin']) == '' ) continue 1;

				$post_id = $this->the_plugin->get_post_id_by_meta_key_and_value('_amzASIN', $value['asin']);

				$redirect_to_amz = (int) get_post_meta($post_id, '_amzaff_redirect_to_amazon', true);
				update_post_meta($post_id, '_amzaff_redirect_to_amazon', (int)($redirect_to_amz+1));

				$redirect_to_amz2 = (int) get_post_meta($post_id, '_amzaff_redirect_to_amazon_prev', true);
				update_post_meta($post_id, '_amzaff_redirect_to_amazon_prev', (int)($redirect_to_amz2+1));
			} // end foreach
		}
		
		// delete amazon products from cart
		public function woo_cart_delete_amazon_prods() {
			$prods = $this->woo_cart_get_amazon_prods();
			//var_dump('<pre>', $prods, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			if ( empty($prods) ) return false;

			foreach ($prods as $key => $value) {
				if ( ! isset($value['asin']) || trim($value['asin']) == '' ) continue 1;

				//var_dump('<pre>', $key, $value,'</pre>');

				// Remove it from the cart
				//WC()->cart->set_quantity( $value['key'], 0 );
				WC()->cart->remove_cart_item($key);

				//$cart_item = WC()->cart->get_cart_item( $value['key'] );
				//var_dump('<pre>','after delete:', $cart_item,'</pre>');
			} // end foreach

			$cart_items_nb = (int) WC()->cart->get_cart_contents_count();
			//var_dump('<pre>', $cart_items_nb, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}



		//====================================================================================
		//== CHECKOUT EMAIL ADDON
		//====================================================================================

		public function woocommerce_before_checkout()
		{
			$return = '<div class="woozonelite_email_wrapper">';
			$return .= '<label for="woozonelite_checkout_user_email">E-mail:</label>';
			if( isset($this->amz_settings['checkout_email_mandatory']) && $this->amz_settings['checkout_email_mandatory'] == 'yes' ) {
				$return .= '<input type="hidden" id="woozonelite_checkout_email_required" name="woozonelite_checkout_email_required" value="1"/>';
			}
			$return .= '<input type="hidden" id="woozonelite_checkout_email_nonce" name="woozonelite_checkout_email_nonce" value="' . ( wp_create_nonce('woozonelite_checkout_email_nonce') ) . '"/>';
			$return .= '<input type="text" id="woozonelite_checkout_email" name="woozonelite_checkout_email" placeholder="email@example.com"/>';
			$return .= '</div>';

			echo $return;
		}
		
		public function woocommerce_ajax_before_user_checkout()
		{
			if( ! wp_verify_nonce( $_REQUEST['_nonce'], 'woozonelite_checkout_email_nonce')) die ('Busted!');
			unset($_REQUEST['_nonce']);
			
			$email = sanitize_email( $_REQUEST['email'] );
			$users_email = array();
			$users_email = get_option('WooZoneLite_clients_email');
			
			if( is_email($email) ) {
				if( in_array($email, $users_email) ) {
					echo 'email_exists';
					die;
				}
				$users_email[] = $email;
				update_option('WooZoneLite_clients_email', $users_email);
				echo 'success';
			}else{
				echo 'invalid_email';
			}
			
			die;
		}
	


		//====================================================================================
		//== CROSS SELL
		//====================================================================================

		/**
		 * Cross Sell - Similarity Products
		 */
		public function cross_sell_checkout()
		{
			$amz_cross_sell = isset($_GET['amz_cross_sell']) ? (string) $_GET['amz_cross_sell'] : false;
			if ( false === $amz_cross_sell ) return '';
			
			$asins = isset($_GET['asins']) ? $_GET['asins'] : '';
			$asins = trim($asins);
			if ( '' == $asins ) return '';
			
			$asins = explode(',', $asins);
			if ( empty($asins) ) return '';

			// I: use amazon api to add products to cart
			// if (0) {

			// 	//$GLOBALS['WooZoneLite'] = $this;
				
			// 	if ( $this->the_plugin->is_aateam_demo_keys() ) {
			// 		return '';
			// 	}

			// 	$selectedItems = array();
			// 	foreach ($asins as $key => $value){
			// 		$selectedItems[] = array(
			// 			'offerId' => $value,
			// 			'quantity' => 1
			// 		);
			// 	}
   
			// 	$rsp = $this->the_plugin->get_ws_object( $this->the_plugin->cur_provider )->api_main_request(array(
			// 		'what_func' 			=> 'api_make_request',
			// 		'method'                => 'cartThem',
			// 		'amz_settings'          => $this->amz_settings,
			// 		'from_file'             => str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
			// 		'from_func'             => __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
			// 		'requestData'           => array(
			// 			'selectedItems'         => $selectedItems,
			// 		),
			// 		//'optionalParameters'  => array(),
			// 		'responseGroup'         => 'Cart',
			// 	));
			// 	$cart = $rsp['response'];
	  
			// 	// debug only
			// 	//unset($_SESSION['amzCart']);

			// 	$user_country = $this->the_plugin->get_country_perip_external();
			// 	$config = $this->amz_settings;
			// 	// AssociateTag => $user_country['affID']
			// 	// SubscriptionId => $config['AccessKeyID']
	
			// 	$cart_items = isset($cart['CartItems']['CartItem']) ? $cart['CartItems']['CartItem'] : array();
			// 	//var_dump('<pre>', $cart['PurchaseURL'], $cart_items, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;    
			// 	if( count($cart_items) ){
			// 		header('Location: ' . $cart['PurchaseURL'] . "%26tag=" . $user_country['affID']); // & = %26 => link must be encoded
			// 		exit();
			// 	}

			// }
			// end I

			// II: create a fake form and submit it with javascript
			if (1) {

			$user_country = $this->the_plugin->get_country_perip_external();
			$main_aff_id = $this->the_plugin->main_aff_id();
			$main_aff_site = $this->the_plugin->main_aff_site();

			$products = array();
			foreach ($asins as $key => $value){
				$products[] = array(
					'asin' => $value,
					'quantity' => 1
				);
			}
			
			if ( empty($products) ) return true;

			$domain = substr($user_country['website'], 1); //$this->amz_settings['country']; //substr($user_country['website'], 1);
			$affID = $user_country['affID'];

			$html = array();
			if ( isset($this->amz_settings["redirect_checkout_msg"]) && trim($this->amz_settings["redirect_checkout_msg"]) != "" ) {
				$html[] = '<img src="' . ( $this->the_plugin->cfg['paths']['freamwork_dir_url'] . 'images/checkout_loading.gif'  ) . '" style="margin: 10px auto;">';
				$html[] = "<h3>" . ( str_replace( '{amazon_website}', 'www.amazon.' . $domain, $this->amz_settings["redirect_checkout_msg"]) ) . "</h3>";
			}
		
			//$checkout_type =  isset($this->amz_settings['checkout_type']) && $this->amz_settings['checkout_type'] == '_blank' ? '_blank' : '_self';
			$checkout_type = '_self';
			
			ob_start();
			?>

			<form target="<?php echo $checkout_type;?>" id="amzRedirect" method="GET" action="//www.amazon.<?php echo $domain; ?>/gp/aws/cart/add.html">
				<input type="hidden" name="AssociateTag" value="<?php echo $affID;?>"/>
				<?php /*<input type="hidden" name="SubscriptionId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>*/ ?>
				<input type="hidden" name="AWSAccessKeyId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>
				<?php 
					$cc = 1; 
					foreach ($products as $key => $value){
				?>      
						<input type="hidden" name="ASIN.<?php echo $cc;?>" value="<?php echo $value['asin'];?>"/>
						<input type="hidden" name="Quantity.<?php echo $cc;?>" value="<?php echo $value['quantity'];?>"/>
				<?php
						$cc++;
					} // end foreach

					//$redirect_in = isset($this->amz_settings['redirect_time']) && (int) $this->amz_settings['redirect_time'] > 0 ? ( (int) $this->amz_settings['redirect_time'] * 1000 ) : 1;
					$redirect_in = 1;
				?>
			</form>

			<script type="text/javascript">
				setTimeout(function() {
					document.getElementById("amzRedirect").submit();
				}, <?php echo $redirect_in;?>);
			</script>

			<?php 
			$html[] = ob_get_contents(); //ob_clean();
			echo implode(PHP_EOL, $html);
			exit;
			
			}
			// end II
		}

		public function cross_sell_box_empty( $atts ) {
			return false;
		}
		public function cross_sell_box( $atts ) {
			extract( shortcode_atts( array(
				'asin' => ''
			), $atts ) );

			$cross_selling = (isset($this->amz_settings["cross_selling"]) && $this->amz_settings["cross_selling"] == 'yes' ? true : false);
			
			if( $cross_selling == false ) return '';

			$backHtml = array();
			$backHtml[] = '<div class="main-cross-sell" data-asin="' . $asin . '">';

			ob_start();
		?>

	<div class="WooZoneLite-cross-sell-loader">
		<div>
			<div id="floatingBarsG">
				<div class="blockG" id="rotateG_01"></div>
				<div class="blockG" id="rotateG_02"></div>
				<div class="blockG" id="rotateG_03"></div>
				<div class="blockG" id="rotateG_04"></div>
				<div class="blockG" id="rotateG_05"></div>
				<div class="blockG" id="rotateG_06"></div>
				<div class="blockG" id="rotateG_07"></div>
				<div class="blockG" id="rotateG_08"></div>
			</div>
			<div class="WooZoneLite-cross-sell-loader-text"></div>
		</div>
	</div>

		<?php
			$backHtml[] = ob_get_clean();

			$backHtml[] = '</div>';

			$opGetDebug = '<div id="WooZoneLite-cross-sell-debug" class="WooZoneLite-cross-sell-debug" data-asin="' . $asin . '"></div>';
			//if ( $this->the_plugin->is_debug_mode_allowed() ) {
			//	$backHtml[] = $opGetDebug;
			//}
			WooZoneLite_debugbar()->add2bar_row( 'woozonelite-debugbar-cross-sell', $opGetDebug, array() );
			WooZoneLite_debugbar()->add2bar_menu( 'woozonelite-debugbar-cross-sell', WooZoneLite()->_translate_string( 'Frequently Bought Together' ), array() );
			WooZoneLite_debugbar()->add2bar_menua( 'woozonelite-debugbar-cross-sell', WooZoneLite()->_translate_string( 'Frequently Bought Together' ), array() );

			$backHtml[] = '<div style="clear:both;"></div>';
			
			$html = implode(PHP_EOL, $backHtml);
			return $html;
		}

		public function _cross_sell_box( $atts=array() ) {
			extract($atts);

			global $product;
			
			$ret = array('status' => 'valid', 'html' => '', 'nbprods' => 0, 'debug' => '');

			// get product related items from Amazon
			$products = $this->_cross_sell_get_similarity_prods( $asin, 10 );
			
			$ret['debug'] = $this->_cross_sell_debug_msg( $products );
			$ret['nbprods'] = count($products['rows']);

			$backHtml = array();
			if ( isset($products['status'], $products['rows']) && 'valid' == $products['status'] && !empty($products['rows']) ) {
				
				$choose_variation = isset($this->amz_settings['cross_selling_choose_variation']) ? (string) $this->amz_settings['cross_selling_choose_variation'] : 'first';

				$how_many = isset($this->amz_settings['cross_selling_nbproducts']) ? (int) $this->amz_settings['cross_selling_nbproducts'] : 3;
				$how_many = $how_many + 1; // add 1 fake in products, current product

				// :: open box wrapper
				$backHtml[] = WooZoneLite_asset_path( 'css', $this->the_plugin->cfg['paths']['frontend_dir_url'] . '/css/cross-sell.css', false, array( 'id' => 'amz-cross-sell' ) );

				$backHtml[] = '<div class="cross-sell">';
				$backHtml[] = '<span class="cross-sell-price-sep" data-price_dec_sep="' . wc_get_price_decimal_separator() . '" style="display: none;"></span>';
				$backHtml[] =   '<h2>' . WooZoneLite()->_translate_string( 'Frequently Bought Together' ) . '</h2>';
				$backHtml[] =   '<div style="margin-top: 0px;" class="separator"></div>';

				// :: box first row - with thumbs
				$backHtml[] =   '<ul id="feq-products">';
				$cc = 0;
				$_total_price = 0;
				foreach ($products['rows'] as $key => $value) {
					
					if ( $cc >= $how_many ) break;

					// is variable product? => get chosen variation based on option
					if ( isset($value['is_variable']) && 'Y' == $value['is_variable'] ) {

						$variation_found = array();

						// if verification
						if ( isset($value['variations'], $value['variations_filtered'])
							&& is_array($value['variations']) && ! empty($value['variations'])
							&& is_array($value['variations_filtered']) ) {

							// just in case: choose first valid variation
							$variation_found = array_values($value['variations']);
							$variation_found = isset($variation_found[0]) ? $variation_found[0] : array();

							// choose variation from option value (allowed: first, lowest price, highest price)
							foreach ( $value['variations_filtered'] as $varType => $varAsin) {
								if ( ! empty($varAsin) && isset($value['variations']["$varAsin"]) ) {
									$variation_found = $value['variations']["$varAsin"];
									if ( $choose_variation == $varType ) { // the chosen one!
										break;
									}
								}
							}
						} // end if verification

						// couldn't find a valid variation for this product
						if ( empty($variation_found) ) {
							unset($products['rows']["$key"]); // delete this invalid product!
							continue 1; // we intentionaly don't increment the counter, so we can go and verify next products!
						}

						// replace old main variable product details with its variation child details
						$value = $variation_found;
						$products['rows']["$key"] = $variation_found;
					}

					$value['price'] = str_replace(",", ".", $value['price']);
					
					$product_buy_url = $this->the_plugin->_product_buy_url( 0, $value['ASIN'] );
					$prod_link = home_url('/?redirectAmzASIN=' . $value['ASIN'] );
					$prod_link = $product_buy_url;
					
					if( trim($value['thumb']) != "" ){
						$backHtml[] =   '<li>';
						$backHtml[] =   '<a target="_blank" rel="nofollow" href="' . ( $prod_link ) . '">';
						$backHtml[] =       '<img class="cross-sell-thumb" id="cross-sell-thumb-' . ( $value['ASIN'] ) . '" src="' . ( $value['thumb'] ) . '" alt="' . ( htmlentities( str_replace('"', "'", $value['Title']) ) ) . '">';
						$backHtml[] =   '</a>';
						if( $cc < (count($products['rows']) - 1) ){
							$backHtml[] =       '<div class="plus-sign">+</div>';
						}

						$backHtml[] =   '</li>';
						
						$_total_price = $_total_price + $value['price'];
					}

					
					$cc++;
				}
				$backHtml[] =   '</ul>';

				// :: box second row - with titles & prices
				$backHtml[] =   '<div class="cross-sell-buy-btn">';
				$backHtml[] =   	'<span id="cross-sell-bpt">' . WooZoneLite()->_translate_string( 'Price for all' ) . ':</span>';
				$backHtml[] =   	'<span id="cross-sell-buying-price" class="price">' . ( wc_price( $_total_price ) ) . '</span>';
				$backHtml[] =       '<div style="clear:both"></div><a href="' . home_url(). '" id="cross-sell-add-to-cart">' . WooZoneLite()->_translate_string( 'Add to cart' ) . '</a>';
				$backHtml[] =   '</div>';

				$backHtml[] = '<div class="cross-sell-buy-selectable">';
				$backHtml[] =   '<ul class="cross-sell-items">';
				$cc = 0;
				foreach ($products['rows'] as $key => $value) {
					
					if ( $cc >= $how_many ) break;

					if ( $cc == 0 && ( $asin == $value['ASIN'] || $asin == $value['ParentASIN'] ) ) {
						$backHtml[] =       '<li>';
						$backHtml[] =           '<input type="checkbox" checked="checked" value="' . ( $value['ASIN'] ) . '">';
						$backHtml[] =           '<div class="cross-sell-product-title"><strong>' . WooZoneLite()->_translate_string( 'This item' ) . ': </strong>' . $value['Title'] . '</div>';
						$backHtml[] =           '<div class="cross-sell-item-price" data-item_price="' . $value['price'] . '">' . ( wc_price( $value['price'] ) ) . '</div>';
						$backHtml[] =       '</li>';
					}
					else{
						$product_buy_url = $this->the_plugin->_product_buy_url( 0, $value['ASIN'] );
						$prod_link = home_url('/?redirectAmzASIN=' . $value['ASIN'] );
						$prod_link = $product_buy_url;

						$backHtml[] =       '<li>';
						$backHtml[] =           '<input type="checkbox" checked="checked" value="' . ( $value['ASIN'] ) . '">';
						$backHtml[] =           '<div class="cross-sell-product-title">' . ( '<a target="_blank" rel="nofollow" href="' . ( $prod_link ) . '">' . $value['Title'] .'</a>' ) . '</div>';
						$backHtml[] =           '<div class="cross-sell-item-price" data-item_price="' . $value['price'] . '">' . ( wc_price( $value['price'] ) ) . '</div>';
						$backHtml[] =       '</li>';
					}

					$cc++;
				}
				$backHtml[] =   '</ul>';
				$backHtml[] = '</div>';

				// :: close box wrapper
				$backHtml[] = '</div>';

				$backHtml[] = '<div style="clear:both;"></div>';

				if ( isset($_total_price) && ($_total_price > 0) ) {
					return array_merge($ret, array(
						'html'		=> implode(PHP_EOL, $backHtml), 
					));
				}
				return $ret;
			}
			return $ret;
		}

		public function _cross_sell_get_similarity_prods( $asin, $return_nr=3, $force_update=false ) {
			$max_tries = 5;
			$cache_valid_for = (60 * 60 * 24); // 24 hours in seconds

			$return_nr = $return_nr + 1; // add 1 fake in products, current product

			$ret = array('status' => 'invalid', 'rows' => array(), 'msg' => '', 'msg_extra' => array());
			$retProd = array();
			$msg_extra = array();
			$nb_tries = 'inc';

			// check for cache of this ASIN
			$db = $this->the_plugin->db;
			$cache_request = $db->get_row( $db->prepare( "SELECT * FROM " . ( $db->prefix ) . "amz_cross_sell WHERE ASIN = %s", $asin), ARRAY_A );

			// if cache found for this product & NOT force update
			if ( $cache_request != "" && count($cache_request) > 0 && $force_update === false ) {

				// get products from DB cache amz_cross_sell table
				$products = maybe_unserialize($cache_request['products']);

				$msg_extra = array(
					'asin'					=> $cache_request['ASIN'],
					'nr_products'		=> $cache_request['nr_products'],
					'is_variable'		=> $cache_request['is_variable'],
					'nb_tries'			=> $cache_request['nb_tries'],
				);

				// is valid cache?
				if ( isset($cache_request['add_date']) ) {
					$add_date = strtotime($cache_request['add_date']);
					//$add_date = gmdate("U", $add_date);
				}
				$cache_isvalid = 
					isset($cache_request['add_date'])
					&& ( ($add_date + $cache_valid_for) > time() )
					? true : false;

				// if cache timeout (not valid anymore) => reset nb tries
				if ( ! $cache_isvalid ) {
					$nb_tries = 0;
				}
				else {
					$msg_extra['cache_expires_in'] = $this->the_plugin->u->time_since(
						time(),
						($add_date + $cache_valid_for)
					);
					unset($msg_extra['cache_expires_in']);
				}

				// make cache invalid, because no products found saved in cache & still allowed to make tries
				if ( empty($products) && isset($cache_request['nb_tries']) && ( $cache_request['nb_tries'] < $max_tries ) ) {
					$cache_isvalid = false;
				}

				// if cache still valid, get from mysql cache & NOT force update
				if ( $cache_isvalid ) {
					$msg_extra['from_cache'] = true;
					return array('status' => 'valid', 'rows' => array_slice( $products, 0, $return_nr ), 'msg' => 'products returned from cache.', 'msg_extra' => $msg_extra);
				}
			}

			$provider_status = $this->the_plugin->provider_action_controller( 'not_aateam_demo_keys', 'amazon', array() );
			if ( 'invalid' == $provider_status['status'] ) {
				return array_merge( $ret, array('status' => 'invalid', 'rows' => array(), 'msg' => $provider_status['msg']) );
			}

			// get current product
			$rsp = $this->the_plugin->get_ws_object( $this->the_plugin->cur_provider )->api_main_request(array(
				'what_func' 			=> 'api_make_request',
				'method'                => 'lookup',
				'amz_settings'          => $this->amz_settings,
				'from_file'             => str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
				'from_func'             => __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
				'requestData'           => array(
					'asin'                  => $asin,
				),
				'optionalParameters'    => array(),
				'responseGroup'         => 'Large,ItemAttributes,OfferFull,Offers,Variations,VariationSummary',
			));
			//var_dump('<pre>', $rsp, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			$thisProd = $rsp['response'];
			$thisProd_respStatus = $this->the_plugin->get_ws_object( $this->the_plugin->cur_provider )->is_amazon_valid_response( $thisProd );
			
			// loop current product
			if ( $thisProd_respStatus['status'] == 'valid' ) { // success
				$thisProd = $thisProd['Items']['Item'];
				$thisProd = ! isset($thisProd['ASIN']) ? $thisProd[0] : $thisProd;
				$prodasin = $thisProd['ASIN'];
				$foundProd = $this->_cross_sell_get_prod_fields( $thisProd );
				if ( ! empty($foundProd) ) {
					$retProd[$prodasin] = $foundProd;
				}
			}
			//var_dump('<pre>', $retProd, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL; 
			
			// get SIMILARITY products
			$rsp = $this->the_plugin->get_ws_object( $this->the_plugin->cur_provider )->api_main_request(array(
				'what_func' 			=> 'api_make_request',
				'method'                => 'similarityLookup',
				'amz_settings'          => $this->amz_settings,
				'from_file'             => str_replace($this->the_plugin->cfg['paths']['plugin_dir_path'], '', __FILE__),
				'from_func'             => __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
				'requestData'           => array(
					'asin'                  => $asin,
				),
				'optionalParameters'    => array(),
				'responseGroup'         => 'Large,ItemAttributes,OfferFull,Offers,Variations,VariationSummary',
			));
			//var_dump('<pre>', $rsp, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			$similarity = $rsp['response'];
			$similarity_respStatus = $this->the_plugin->get_ws_object( $this->the_plugin->cur_provider )->is_amazon_valid_response( $similarity );

			// loop SIMILARITY products
			if ( $similarity_respStatus['status'] == 'valid' ) { // success
				foreach ($similarity['Items']['Item'] as $key => $value){
					if (
						count($similarity['Items']['Item']) > 0
						&& count($value) > 0
						&& isset($value['ASIN'])
						&& strlen($value['ASIN']) >= 10
					) {
						$thisProd = $value;
						$prodasin = $thisProd['ASIN'];
						$foundProd = $this->_cross_sell_get_prod_fields( $thisProd );
						if ( ! empty($foundProd) ) {
							$retProd[$prodasin] = $foundProd;
						}
					}
				}
			}
			//var_dump('<pre>', $retProd, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL; 

			// invalid response
			if ( empty($retProd) ) {
				$msg = array();
				if ( isset($thisProd['status'], $thisProd['msg']) && 'invalid' == $thisProd['status'] ) {
					$msg[] = $thisProd['msg'];
				}
				if ( isset($similarity['status'], $similarity['msg']) && 'invalid' == $similarity['status'] ) {
					$msg[] = $similarity['msg'];
				}
				$msg = implode('<br />', $msg);
				return array_merge( $ret, array('status' => 'invalid', 'rows' => array(), 'msg' => $msg) );
			}

			// SIMILARITY products response is invalid
			if ( $similarity_respStatus['status'] != 'valid' ) {
				// if "There are no similar items for this product" we need to save in cache
				if ( isset($similarity_respStatus['amz_code']) && 'aws.ecommerceservice.nosimilarities' == strtolower($similarity_respStatus['amz_code']) ) {
					$retProd = array();
					$ret['msg'] = $similarity_respStatus['amz_code'];
					$noSimilarities = true;
				}
				else {
					$msg = array();
					$msg[] = $similarity_respStatus['msg'];
					return array_merge( $ret, array('status' => 'invalid', 'rows' => array(), 'msg' => implode('<br />', $msg)) );
				}
			}

			// if cache found for this product
			$savedb = array(
				'products'				=> serialize($retProd), //serialize(array_slice( $retProd, 0, $return_nr)),
				'nr_products'			=> count($retProd), //$return_nr <= count($retProd) ? $return_nr : count($retProd),
				'is_variable'			=> isset($retProd["$asin"], $retProd["$asin"]['is_variable']) ? (string) $retProd["$asin"]['is_variable'] : 'N',
			);

			if ( $cache_request != "" && count($cache_request) > 0 ) {

				$nb_tries = isset($noSimilarities) && $noSimilarities ? $max_tries : $nb_tries;
				$calcTries = $this->_cross_sell_calc_tries($nb_tries, $cache_request['nb_tries'], $force_update);

				$updateQuery = "update " . $db->prefix . "amz_cross_sell" . " set products = %s, nr_products = %s, is_variable = %s" . $calcTries['query'] . "where 1=1 and ASIN = %s;";
				$updateQuery = $db->prepare( $updateQuery, $savedb['products'], $savedb['nr_products'], $savedb['is_variable'], $asin );
				$db->query( $updateQuery );
				/*
				$db->update(
					$db->prefix . "amz_cross_sell",
					array(
						'products'			=> $savedb['products'],
						'nr_products'		=> $savedb['nr_products'],
						'is_variable'		=> $savedb['is_variable'],
						'nb_tries'			=> 'nb_tries + 1',
					),
					array( 'ASIN' => $asin ),
					array(
						'%s',
						'%d',
						'%s',
						'%d'
					),
					array(
						'%s'
					)
				);
				*/
			}
			// if cache not found for this product
			else {
				$nb_tries = isset($noSimilarities) && $noSimilarities ? $max_tries : 1;
				$calcTries = $this->_cross_sell_calc_tries($nb_tries, 0, $force_update);

				/*$db->insert(
					$db->prefix . "amz_cross_sell",
					array(
						'ASIN'				=> $asin,
						'products'			=> $savedb['products'],
						'nr_products'		=> $savedb['nr_products'],
						'is_variable'		=> $savedb['is_variable'],
						'nb_tries'			=> 1,
					),
					array(
						'%s',
						'%s',
						'%d',
						'%s',
						'%d'
					)
				);*/
				$this->the_plugin->db_custom_insert(
					$db->prefix . "amz_cross_sell",
					array(
						'values' => array(
							'ASIN'				=> $asin,
							'products'			=> $savedb['products'],
							'nr_products'		=> $savedb['nr_products'],
							'is_variable'		=> $savedb['is_variable'],
							'nb_tries'			=> $nb_tries,
						),
						'format' => array(
							'%s',
							'%s',
							'%d',
							'%s',
							'%d'
						)
					),
					true
				);
			}
			
			$msg_extra = array(
				'asin'					=> $asin,
				'nr_products'		=> $savedb['nr_products'],
				'is_variable'		=> $savedb['is_variable'],
				'nb_tries'			=> $calcTries['nb'],
			);
			if ( $force_update ) {
				$msg_extra['force_update'] = 'yes';
			}

			if ( ! empty($ret['msg']) ) {
				$ret['msg'] .= ' - ';
			}
			if ( ! empty($retProd) ) {
				$ret['msg'] .= 'products successfully returned from amazon request.';
			}
			else {
				$ret['msg'] .= 'no products returned from amazon request.';
			}
			return array_merge( $ret, array('status' => 'valid', 'rows' => array_slice( $retProd, 0, $return_nr ), 'msg_extra' => $msg_extra) );
		}

		public function _cross_sell_get_prod_fields( $thisProd, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'max_variations'			=> -1, // -1 = unlimited; maximum variations to retrieve
				'is_variation_child'			=> false, // current product data is for a variation child
			), $pms);
			extract( $pms );

			$retProd = array();

			// :: main properties
			$retProd['ASIN'] = isset($thisProd['ASIN']) ? $thisProd['ASIN'] : '';
			$retProd['ParentASIN'] = isset($thisProd['ParentASIN']) ? $thisProd['ParentASIN'] : '';
			
			// :: product title
			$retProd['Title'] = isset($thisProd['ItemAttributes']['Title']) ? stripslashes($thisProd['ItemAttributes']['Title']) : '';
			
			// :: variations
			if ( ! $is_variation_child ) {
				
				$retProd['DetailPageURL'] = isset($thisProd['DetailPageURL']) ? $thisProd['DetailPageURL'] : '';

				$retProd['is_variable'] = 'N';
				$variations = isset($thisProd['Variations'], $thisProd['Variations']['Item'])
					? $thisProd['Variations']['Item'] : array();
	
				if ( ! empty($variations) ) {

					if ( isset($variations['ASIN']) ) {
						$variations = array( $variations );
					}

					$retProd['is_variable'] = 'Y';
					$retProd['variations'] = array();
					$retProd['variations_total'] = count($variations);
					$retProd['variations_filtered'] = array(
						'first'					=> '',
						'lowest_price'	=> '',
						'highest_price'	=> '',
					);
	
					$currentPrice = array('lowest_price' => null, 'highest_price' => null);
					foreach ($variations as $idx => $variation_item) {
						$variation_asin = isset($variation_item['ASIN']) ? $variation_item['ASIN'] : '';
						$variation_details = $this->_cross_sell_get_prod_fields( $variation_item, array('is_variation_child' => true) );

						if ( ! empty($variation_details) ) {
							$retProd['variations']["$variation_asin"] = $variation_details;
							
							//first variation
							if ( empty($retProd['variations_filtered']['first']) ) {
								$retProd['variations_filtered']['first'] = $variation_asin;
							}
							
							// compare prices so we can choose lowest price & highest price variation
							if ( is_null($currentPrice['lowest_price']) || ( $currentPrice['lowest_price'] > (float) $variation_details['price'] ) ) {
								$currentPrice['lowest_price'] = (float) $variation_details['price'];
								$retProd['variations_filtered']['lowest_price'] = $variation_asin;
							}
							if ( is_null($currentPrice['highest_price']) || ( $currentPrice['highest_price'] < (float) $variation_details['price'] ) ) {
								$currentPrice['highest_price'] = (float) $variation_details['price'];
								$retProd['variations_filtered']['highest_price'] = $variation_asin;
							}
						}
					} // end foreach variations
					
					// keep only necessary variations (optimization)
					$varKeep = array();
					foreach ($retProd['variations_filtered'] as $varAsin) {
						if ( ! empty($varAsin) ) {
							$varKeep["$varAsin"] = $retProd['variations']["$varAsin"];
						}
					}
					$retProd['variations'] = $varKeep;
				}
			}

			// :: product large image
			$retProd['thumb'] = isset($thisProd['SmallImage'], $thisProd['SmallImage']['URL'])
				? $thisProd['SmallImage']['URL'] : '';
			if ( empty($retProd['thumb']) ) {
				// Images
				$images = $this->the_plugin->get_ws_object( $this->the_plugin->cur_provider )->build_images_data( $thisProd );
				if ( empty($images['small']) ) {
					// no images found - if has variations, try to find first image from variations
					$images = $this->the_plugin->get_ws_object( $this->the_plugin->cur_provider )->get_first_variation_image( $thisProd );
				}
				if ( isset($images['small']) && !empty($images['small']) ) {
					$retProd['thumb'] = $images['small'][0];
				}
			}

			// :: product price
			$prodprice = $this->the_plugin->get_ws_object( $this->the_plugin->cur_provider )->get_product_price(
				$thisProd,
				null,
				array()
			);
			$retProd['price'] = $prodprice['_price'];
			$isValid_price = false;
			if ( trim($retProd['price']) != '' && (float) $retProd['price'] > '0.00' ) {
				//$retProd['price'] = number_format($retProd['price'], 2);
				$isValid_price = true;
			}

			// :: validation
			$isValid = true;
			// remove if don't have valid price
			if ( ! $isValid_price ) {
				$isValid = false;
			}
			else if ( isset($retProd['is_variable']) && 'Y' == $retProd['is_variable'] && empty( $retProd['variations'] ) ) {
				$isValid = false;
			}
			//var_dump('<pre>', $retProd, '</pre>'); 

			return $isValid ? $retProd : array();
		}

		public function _cross_sell_calc_tries( $nb_tries, $nb_tries_orig, $force_update ) {
			$ret = array('query' => '', 'nb' => $nb_tries);

			$ret['query'] = '';
			if ( $force_update ) ; // don't count tries if you force update
			else {
				if ( 'inc' == $nb_tries ) {
					$ret['query'] = ', nb_tries = nb_tries + 1';
				}
				else {
					$ret['query'] = ', nb_tries = '.$nb_tries;
				}
				$ret['query'] = ' '.$ret['query'].' ';
			}

			// here because of force_update case above
			if ( 'inc' == $nb_tries ) {
				$ret['nb'] = $nb_tries_orig + 1;
			}

			return $ret;
		}

		public function _cross_sell_debug_msg( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'msg'			=> '',
				'msg_extra'	=> array(),
			), $pms);
			extract($pms);

			$html = array();
			if ( '' != $msg ) {
				$html[] = '<div>' . $msg . '</div>';
			}
			if ( ! empty($msg_extra) && is_array($msg_extra) ) {

				$from_cache = isset($msg_extra['from_cache']) && $msg_extra['from_cache'] ? true : false;
				unset($msg_extra['from_cache']);

				$html[] = '<div>';
				$html[] = 		'<table>';
				$html[] = 			'<thead>';
				$html[] =				'<tr>';
				foreach ($msg_extra as $key => $val) {
					$html[] = 				'<th>' . str_replace('_', ' ', $key) . '</th>';
				}
				$html[] =				'</tr>';
				$html[] = 			'</thead>';
				$html[] = 			'<tbody>';
				$html[] = 				'<tr>';
				foreach ($msg_extra as $key => $val) {
					$html[] = 				'<td>' . $val . '</td>';
				}
				$html[] = 				'</tr>';
				$html[] = 			'</tbody>';
				$html[] = 		'</table>';
				$html[] = '</div>';
				
				if ( $from_cache ) {
					$html[] = '<div><button>empty cache</button></div>';
				}
			}

			return implode(PHP_EOL, $html); 
		}

		public function _cross_sell_empty_cache( $pms=array() ) {
			extract($pms);

			$db = $this->the_plugin->db;

			$asin = (string) $asin;

			$query = "DELETE FROM " . ( $db->prefix ) . "amz_cross_sell WHERE ASIN = %s;";
			$query = $db->prepare( $query, $asin );
			return $db->query( $query );
		}



		//====================================================================================
		//== AMAZON REVIEWS
		//====================================================================================

		/**
		 * Amazon Reviews
		 */
		// Write the custom tab on the product view page.  In WooCommerce these are handled by templates.
		public function amazon_reviews_custom_product_tabs( $tabs=array() ) {
			global $product;

			if ($this->amazon_reviews_product_has_custom_tabs($product)) {

				$priority = 15;

				foreach ($this->woo_tab_data as $tab) {

					$tabs[ $tab['id'] ] = array(
						'title'    => WooZoneLite()->_translate_string( 'Amazon Customer Reviews' ),
						'priority' => $priority,
						'callback' => array($this, 'amazon_reviews_product_review_tab')
					);
				} // end foreach
			}

			return $tabs;
		}

		public function amazon_reviews_product_review_tab( $tab ) {
			global $product;

			if ( $this->amazon_reviews_product_has_custom_tabs($product) ) {
				$content = $this->woo_tab_data[0]['content'];

				$prod_id = 0;
				if ( is_object($product) ) {
					if ( method_exists( $product, 'get_id' ) ) {
						$prod_id = (int) $product->get_id();
					} else if ( isset($product->id) && (int) $product->id > 0 ) {
						$prod_id = (int) $product->id;
					}
				}

				//$content = ''; //DEBUG
				echo '<div id="amzaff-amazon-review-tab" data-prodid="' . $prod_id . '">' . $content . '</div>';
			}
		}

		public function amazon_reviews_product_review_tab_ajax( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'prodid' 		=> 0,
			), $pms);
			extract( $pms );

			$ret = array(
				'status' 	=> 'invalid',
				'html' 		=> '',
			);

			if ( $this->amazon_reviews_product_has_custom_tabs($prodid) ) {

				$content = $this->woo_tab_data[0]['content'];

				preg_match('/src="([^"]+)"/', $content, $match);
				$url = (string) $match[1];
				$url = trim( $url );

				if ( $url != "" ) {
					// now try to parse the string
					parse_str( $url, $params );

					// verify if link expire 
					if ( trim($params['exp']) != "" ) {
						$expire_on = strtotime($params['exp']);

						if ( time() > $expire_on ) {
							// need to update the amazon review iframe
							//global $post;

							//$post_id = (int) $post->ID > 0 ? $post->ID : 0;
							$post_id = $prodid;
							if( $post_id > 0 ){
								$new_url = $this->the_plugin->get_ws_object( $this->the_plugin->cur_provider )->updateProductReviews( $post_id );
								$new_url = trim( $new_url );

								// update the url into content iframe tag
								if ( '' != $new_url ) {
									$content = str_replace( $url, $new_url, $content);
								}

								$content = str_replace( "http://", "//", $content );
								$content = str_replace( "https://", "//", $content );

								$ret = array_replace_recursive( $ret, array(
									'status' 	=> 'valid',
									'html' 		=> $content,
								));
							}
						}

						// DEBUG!
						//var_dump('<pre>', date( "F j, Y, g:i a", strtotime($params['exp'])),'</pre>'); die;  
					}
				} // end if url

				//echo str_replace( "http://", "//", $content );
				//echo str_replace( "https://", "//", $content );
			}

			return $ret;
		}

		// Lazy-load the product_tabs meta data, and return true if it exists, false otherwise
		// @return true if there is custom tab data, false otherwise
		private function amazon_reviews_product_has_custom_tabs( $product ) {
			if ( $this->woo_tab_data === false ) {
				$prod_id = 0;
				if ( is_object($product) ) {
					if ( method_exists( $product, 'get_id' ) ) {
						$prod_id = (int) $product->get_id();
					} else if ( isset($product->id) && (int) $product->id > 0 ) {
						$prod_id = (int) $product->id;
					}
				}
				else if ( ! is_array($product) ) {
					$prod_id = (int) $product;
				}

				$reviews = get_post_meta( $prod_id, 'amzaff_woo_product_tabs', true );
				$reviews = maybe_unserialize( $reviews );

				if ( isset($reviews, $reviews[0], $reviews[0]['content']) ) {

					if ( ! empty($reviews[0]['content']) ) {

						$user_country = $this->get_product_country_current( $prod_id );
						if ( empty($user_country) || ! isset($user_country['website']) ) {
							$user_country = $this->the_plugin->get_country_perip_external();
						}
						$affid = isset($user_country['affID']) ? $user_country['affID'] : '';

						$reviews[0]['content'] = $this->the_plugin->reviews_set_affiliateid( $reviews[0]['content'], array(
							'affid' => $affid,
							'post_id' => $prod_id,
						));
					}
					//var_dump('<pre>', $reviews[0]['content'] , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

					$this->woo_tab_data[] = $reviews[0];
				}
				else {
					//$this->woo_tab_data[] = array('content' => '');
					return false;
				}
			}

			// tab must have content to be considered valid
			$ret = !empty($this->woo_tab_data)
					&& isset($this->woo_tab_data[0]) && !empty($this->woo_tab_data[0])
					&& isset($this->woo_tab_data[0]['content']) && !empty($this->woo_tab_data[0]['content']);
			return $ret;
		}



		//====================================================================================
		//== OTHERS
		//====================================================================================

		public function get_asin_first_variation( $product_id ) {
			$asin = false;
			$_product = wc_get_product( $product_id );
			if ( $_product->is_type( 'variable' ) ){
				
				$variations = $_product->get_available_variations();
				if( isset($variations[0]['variation_id']) ){
					$variation_asin = get_post_meta( $variations[0]['variation_id'], '_amzASIN', true);
					if ( !empty($variation_asin) ) {
						$asin = $variation_asin;
					}
				}
			}

			return $asin;
		}

		/**
		 * OTHERS
		 */
		// woocommerce fix thumb for remote images with https - on frontend
		public function woocommerce_before_mini_cart() {
			echo '<div style="display: none;" class="WooZoneLite-fix-minicart"></div>';
		}

		public function action_do_bitly_request() {
			global $product;

			//:: bitly account must be connected to plugin
			$access_token = get_option( 'WooZoneLite_bitly_access_token', '' );
			// bitly access token wasn\'t found!
			if ( '' == $access_token ) {
				return true;
			}

			//:: other conditions
			if (
				$this->product_buy_is_amazon_url
				&& $this->product_url_short
				&& ( 'external' == $this->p_type )
				&& is_product()
			) {
				if ( ! is_object( $product) ) {
					$product = wc_get_product( get_the_ID() );
				}

				if ( ! is_object( $product) ) {
					return true;
				}

				$this->the_plugin->product_url_from_bitlymeta(array(
					'ret_what' 	=> 'do_request',
					'product' 	=> $product,
				));
			}
		}

		public function action_do_product_page() {
			global $product;

			//:: product info
			if ( function_exists('is_product') ) {
				if ( ! is_product() ) {
					return true;
				}
			}

			if ( ! is_object( $product) ) {
				$product = wc_get_product( get_the_ID() );
			}

			if ( ! is_object( $product) ) {
				return true;
			}

			$prod_id = 0;
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}

			$product_type = '';
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_type' ) ) {
					$product_type = (string) $product->get_type();
				} else if ( isset($product->product_type) && (string) $product->product_type > 0 ) {
					$product_type = (string) $product->product_type;
				}
			}

			//:: frontend synchronization
			if ( 'yes' == $this->syncfront_activate ) {

				// is amazon product?
				$redirect_asin = WooZoneLite_get_post_meta($prod_id, '_amzASIN', true);
				if ( empty($redirect_asin) ) {
					return true;
				}

				// build sync wrapper
				$this->syncfront_args = array(
					'asin' 			=> $redirect_asin,
					'product_id' 	=> $prod_id,
					'product_type' 	=> $product_type,
					'product' 		=> $product,
				);
				add_action( 'WooZoneLite_footer', array( $this, 'syncfront_wrapper' ), 1 );
			}

			//:: external product
			if ( 'external' == $product_type ) {

				$this->the_plugin->product_url_from_bitlymeta(array(
					'ret_what' 	=> 'do_request',
					'product' 	=> $product,
				));

				add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'product_buy_text'), -1, 2);
				add_filter('woocommerce_product_add_to_cart_text', array($this, 'product_buy_text'), -1, 2);

				if( $this->product_buy_is_amazon_url ) {
					add_filter( 'get_post_metadata', array($this->the_plugin, 'gpm_on_product_url'), 999, 4 );
				}
			}
		}

		public function remove_gallery($content) {
			return str_replace('[gallery]', '', $content);
		}

		public function remove_featured_image($html, $attachment_id, $post_id = '') {    
			$featured_image = get_post_thumbnail_id($post_id);
			if ($attachment_id != $featured_image) {
					return $html;
			}
			return '';
		}

		public function amz_product_gallery_attachment_ids( $gallery_ids, $product ) {
			if ( empty($gallery_ids) ) {
				return $gallery_ids;
			}

			// verify we are in woocommerce product
			$prod_id = 0;
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}

			// product id
			$product_id = $prod_id;
			if ( empty($product_id) ) {
				return $gallery_ids;
			}

			// verify product is from amazon
			if ( !is_product() || ( $this->the_plugin->verify_product_is_amazon($product_id, array( 'verify_provider' => false )) !== true ) ) {
				return $gallery_ids;
			}

			// get featured image
			$thumbnail_id = (int) get_post_thumbnail_id( $product_id );
			if ( empty($thumbnail_id) ) {
				return $gallery_ids;
			}

			// remove featured image from gallery ids
			if ( in_array($thumbnail_id, $gallery_ids) ) {
				$__ = array_search($thumbnail_id, $gallery_ids);
				if ( $__ !== false ) {
					unset($gallery_ids["$__"]);
				}
			}
			return $gallery_ids;
		}

		public function product_buy_text($text, $instance) {

			global $product;

			$gtext = array();
			foreach ( array('amazon', 'ebay') as $provider ) {
				$sufix = 'amazon' === $provider ? '' : '_' . $provider;

				$gtext["$provider"] = isset($this->amz_settings["product_buy_text{$sufix}"]) && !empty($this->amz_settings["product_buy_text{$sufix}"])
					? $this->amz_settings["product_buy_text{$sufix}"] : '';
			}

			//if ( empty($gtext) ) return $text;
			$all_empty = true;
			foreach ( array('amazon', 'ebay') as $provider ) {
				$all_empty = $all_empty && empty($gtext["$provider"]);
			}
			if ( $all_empty ) {
				return $text;
			}
	
			$prod_id = 0;			
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}

			if ( $prod_id ) {
				$product_id = $prod_id;
	 
				// original text for non amazon products!
				if ( $this->the_plugin->verify_product_is_amazon($product, array( 'verify_provider' => false )) !== true ) return $text;

				$_button_text = get_post_meta($product_id, '_button_text', true);
				if ( !empty($_button_text) ) {
					return $_button_text;
				}

				$asin = WooZoneLite_get_post_meta($product_id, '_amzASIN', true);
				$prod_provider = $this->the_plugin->prodid_get_provider_by_asin( $asin );

				return isset($gtext["$prod_provider"]) && ! empty($gtext["$prod_provider"]) ? $gtext["$prod_provider"] : $text;
			}
			return $text;
		}

		public function amz_add_product_link( $link, $product, $args=array() ) {
			global $product;

			$prod_id = 0;
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}
			$product_id = $prod_id;

			$product_type = $product->get_type();
			if ( 'external' !== $product_type ) {
				return $link;
			}

			$url = $product->add_to_cart_url();
			$product_sku = $product->get_sku();
			$quantity = isset( $quantity ) ? $quantity : 1;
			$text = $product->add_to_cart_text();

			$ajax_add_to_cart = '';
			if ( 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' ) ) {
				$ajax_add_to_cart = 'ajax_add_to_cart';
			}


			$asin = WooZoneLite_get_post_meta($product_id, '_amzASIN', true);
			$prod_provider = $this->the_plugin->prodid_get_provider_by_asin( $asin );
			$sufix = 'amazon' === $prod_provider ? '' : '_' . $prod_provider;

			$prod_link_open_in = isset($this->amz_settings["product_buy_button_open_in{$sufix}"])
				? $this->amz_settings["product_buy_button_open_in{$sufix}"] : '';
			$prod_link_open_in = ! empty($prod_link_open_in) ? $prod_link_open_in : '_blank';

			$product_buy_custom_classes = isset($this->amz_settings["product_buy_custom_classes{$sufix}"])
				? $this->amz_settings["product_buy_custom_classes{$sufix}"] : '';
			$product_buy_custom_classes = trim( $product_buy_custom_classes );

			$css_class = array( 'button' );
			//$css_class[] = 'button';
			if ( '' !== $product_buy_custom_classes ) {
				//$css_class[] = $this->amz_settings['product_buy_custom_classes'];
				$css_class = array( $product_buy_custom_classes );
			}
			$css_class[] = $ajax_add_to_cart;
			$css_class = implode(' ', $css_class);


			$link = '<a target="' . $prod_link_open_in . '" href="' . $url . '" rel="nofollow" data-product_id="' . $product_id . '" data-product_sku="' . $product_sku . '" data-quantity="' . $quantity . '" class="' . $css_class . '">' . $text . '</a>';
			return $link;
		}

		public function amz_disclaimer_price_html( $price, $product ) {

			//var_dump('<pre>', $price , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( $this->the_plugin->disable_amazon_checkout ) {
				return $price;
			}

			$prod_id = 0;
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}

			$post_id = $prod_id;
			if ( $post_id <=0 ) return $price;

			if ( !is_product() || !$product->get_price() || ( $this->the_plugin->verify_product_is_amazon($post_id, array( 'verify_provider' => false )) !== true ) ) {
				return $price;
			}

			$text = $this->_amz_disclaimer_price_html( $post_id, array(
				'price' 			=> $product->get_price(),
				'currency' 			=> get_woocommerce_currency_symbol(),
				//'_regular_price' 	=> $reg_price,
				//'_price' 			=> $s_price,
			));
			//var_dump('<pre>', $price, $text , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			if ( ! is_array($text) ) {

				$reg_price = get_post_meta( get_the_ID(), '_regular_price');
				$s_price = get_post_meta( get_the_ID(), '_price');

				if ( $reg_price != $s_price ) {
					if ( strpos($price, '</ins>') !== false ) {
						$ret = str_replace( '</ins>', '</ins>' . $text, $price );
					} else {
						$ret = str_replace( '</del>', '</del>' . $text, $price );
					}
				} else {
					if ( strpos($price, '</del>') !== false ) {
						$ret = $this->the_plugin->u->str_replace_last( '</del>', '</del>' . $text, $price );
					} else {
						$ret = $this->the_plugin->u->str_replace_last( '</span>', '</span>' . $text, $price );
					}
				}
				// if ( substr_count($price, '</ins>') > 0 ) {
				// 		$ret = str_replace( '</ins>', '</ins>' . $text, $price );
				// } else {
				// 	$ret = str_replace( '</span>', '</span>' . $text, $price );
				// }
				return $ret;
			}
			else {
				$ret = array();
				$ret[] = '<em class="WooZoneLite-price-info">';
				$ret[] = 	$text['first'] . "&nbsp;{$price}&nbsp;" . $text['last'];
				$ret[] = '</em>';
				$ret = implode('', $ret);
				return $ret;
			}
			return $price;
		}

		// returns: string | array
		private function _amz_disclaimer_price_html( $post_id, $pms=array() ) {

			$pms = array_replace_recursive( array(
				'price' 			=> null,
				'currency' 			=> get_woocommerce_currency_symbol(),
				//'_regular_price' 	=> null,
				//'_price' 			=> null,
			), $pms);
			extract( $pms );
			//var_dump('<pre>', $pms , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			//:: configuration
			$pptos_activate = isset($this->amz_settings['pptos_activate']) && 'no' === $this->amz_settings['pptos_activate']
				? false : true;

			if ( ! $pptos_activate ) {
				return '';
			}

			$pptos_tpl = isset($this->amz_settings['pptos_tpl']) ? (string) $this->amz_settings['pptos_tpl'] : 'v1';

			$opt_provider_activated = array(
				'amazon' 	=> 'Amazon',
				//'ebay' 		=> 'Ebay',
			);
			$provider_activated = 
				isset($this->amz_settings['pptos_provider_activated'])
				? (array) $this->amz_settings['pptos_provider_activated'] : array_keys( $opt_provider_activated );
			$provider_activated = $this->the_plugin->clean_multiselect( $provider_activated );


			//:: product asin
			$prod_asin = WooZoneLite_get_post_meta($post_id, '_amzASIN', true);

			$provider = $this->the_plugin->prodid_get_provider_by_asin( $prod_asin );

			if ( ! in_array($provider, $provider_activated) ) {
 				return '';
 			}


			//:: get price last updated date
			$current_date = date('Y-m-d', time());

			// $price_update_date_db = get_post_meta($post_id, "_price_update_date", true);
			$price_update_date_db = get_post_meta($post_id, "_amzaff_syncwidget_last_date", true);
			if ( empty($price_update_date_db) ) {
				$price_update_date_db = get_post_meta($post_id, "_amzaff_sync_last_date", true);
			}

			// product not synced at least once yet! - bug solved 2015-11-03
			if ( empty($price_update_date_db) ) {
				global $post;
				$price_update_date_db = strtotime($post->post_date); //$product->post->post_date
			}
			$price_update_date = $price_update_date_db;

			if ( ! empty($price_update_date) ) {

				$last_date = date('Y-m-d', $price_update_date_db);
				//var_dump('<pre>',$current_date, $last_date ,'</pre>');

				// if price update date is different that current date => force template model 1
				if ( $current_date !== $last_date && 'v1' !== $pptos_tpl ) {
					$pptos_tpl = 'v1';
					//echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				}

				if ( 'amazon' !== $provider ) {
					//$gmt_offset = get_option( 'gmt_offset' );
					//$price_update_date = gmdate( get_option( 'date_format' ) .', '. get_option( 'time_format' ), ($price_update_date + ($gmt_offset * 3600)) );
					if( '' !== get_option( 'date_format' ) && '' !== get_option( 'time_format' ) ) {
						$price_update_date = date_i18n( get_option( 'date_format' ) .', '. get_option( 'time_format' ) , $price_update_date );	
					} else {
						$price_update_date = date( 'F j, Y, g:i a', $price_update_date );
					}
				}
				else if ( 'v1' === $pptos_tpl ) {
					// PST = timezone 'America/Los_Angeles'
					// NEW TOS v1: Amazon.com Price: $ 32.77 (as of 01/07/2008 14:11 PST- Details)
					$price_update_date = new DateTime( "@$price_update_date" );
					$price_update_date->setTimezone( new DateTimeZone('America/Los_Angeles') );
					$date_format = isset( $this->amz_settings['asof_date_format'] ) && $this->amz_settings['asof_date_format'] != '' ? $this->amz_settings['asof_date_format'] : 'd/m/Y H:i';
					$price_update_date = $price_update_date->format($date_format);
					$price_update_date .= ' PST-';
				}
				else if ( 'v2' === $pptos_tpl ) {
					// EST = timezone 'America/New_York'
					// NEW TOS v2: Amazon.com Price: $ 32.77 (as of 14:11 EST- More info)
					$price_update_date = new DateTime( "@$price_update_date" );
					$price_update_date->setTimezone( new DateTimeZone('America/New_York') );
					$price_update_date = $price_update_date->format('H:i');
					$price_update_date .= ' EST-';
				}
			}

			if ( empty($price_update_date) ) {
				return '';
			}


			//:: product country
			$getCountry = $this->the_plugin->get_product_import_country( array(
				'product_id'			=> $post_id,
				'country' 				=> '',
				'asin' 					=> $prod_asin,
				'use_fallback_location' => true,
			));
			//var_dump('<pre>', $getCountry , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			$the_country = $getCountry['country'];


			//:: details | more info - links
			$details_text = $this->the_plugin->_translate_string( 'Details' );
			//$details_text = __('Details', $this->localizationName);
			if ( 'v2' === $pptos_tpl ) {
				$details_text = $this->the_plugin->_translate_string( 'More info' );
				//$details_text = __('More info', $this->localizationName);
			}
			$details_link = sprintf(
				'<a name="' . $details_text . '" href="#TB_inline?&inlineId=WooZoneLite-price-disclaimer&width=500&height=250" class="thickbox link">' . $details_text . '</a>' );

			$details_content = array();
			$details_content[] = '<div id="WooZoneLite-price-disclaimer" style="display: none;">';
			$details_content[] = 	'<p></p>';
			$details_content[] = 	'<p>';
			$details_content[] = $this->the_plugin->_translate_string( 'Product prices and availability are accurate as of the date/time indicated and are subject to change. Any price and availability information displayed on [relevant Amazon Site(s), as applicable] at the time of purchase will apply to the purchase of this product.' );
			$details_content[] = 	'</p>';
			$details_content[] = '</div>';
			$details_content = implode( '', $details_content );


			//:: Final TEXT
			$text_asof = $this->the_plugin->_translate_string( 'as of' );
			$text_price = $this->the_plugin->_translate_string( 'Price' );

			$text = array();

			if ( 'amazon' !== $provider ) {
				$text[] = '&nbsp;';
				$text[] = '<em class="WooZoneLite-price-info">';
				$text[] = sprintf(
					'(' . $this->the_plugin->_translate_string( 'as of' ) . ' %s)',
					$price_update_date
				);
				$text[] = '</em>';
				$text[] = $this->amz_product_free_shipping( $post_id );
				$text = implode( '', $text );
			}
			else if ( in_array($pptos_tpl, array('v1', 'v2')) ) {
				// $text[] = sprintf(
				// 	'Amazon.' . __( '%s Price: %s %s (as of %s %s)', $this->localizationName ),
				// 	$the_country,
				// 	$currency,
				// 	$price,
				// 	$price_update_date,
				// 	$details_link
				// );
				//$text[] = "Amazon.$the_country $text_price: $currency $price ($text_asof $price_update_date $details_link)";
				$text['first'] = "Amazon.$the_country $text_price:";
				$text['last'] = "($text_asof $price_update_date $details_link)";
				$text['last'] .= $this->amz_product_free_shipping( $post_id );
				$text['last'] .= $details_content;
			}

			return $text;
		}
		
		public function amz_availability( $availability, $product ) {
			//change text "In Stock' to 'available'
			//if ( $_product->is_in_stock() )
			//  $availability['availability'] = __('available', 'woocommerce');

			//change text "Out of Stock' to 'sold out'
			//if ( !$_product->is_in_stock() )
			//  $availability['availability'] = __('sold out', 'woocommerce');

			$prod_id = 0;
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}

			$post_id = $prod_id;
			if ( $post_id > 0 ) {
				$meta = get_post_meta($post_id, '_amzaff_availability', true);
				if ( !empty($meta) ) {
					$availability['availability'] = /*'<img src="shipping.png" width="24" height="18" alt="Shipping availability" />'*/'' . $meta;
					$availability['class'] = 'WooZoneLite-availability-icon';
				}
			}
			return $availability;
		}
		
		public function amz_product_free_shipping( $post_id ) {
			$contents = '';
			$current_amazon_aff = $this->the_plugin->_get_current_amazon_aff();

			$_tag = '';
			$_affid = $current_amazon_aff['user_country']['key'];
			if ( isset($this->amz_settings['AffiliateID']["$_affid"]) ) {
				$_tag = '&tag=' . $this->amz_settings['AffiliateID']["$_affid"];
			}

			if ( 'yes' == $this->the_plugin->frontend_show_free_shipping ) {

				$is_fs = $this->the_plugin->is_product_freeshipping( $post_id, array(
					'current_amazon_aff' 	=> $current_amazon_aff,
				));
				$contents .= $is_fs['html'];
			}

			// coupon
			if ( 'yes' == $this->the_plugin->frontend_show_coupon_text ) {

				$meta_amzResp = get_post_meta($post_id, '_amzaff_amzRespPrice', true);
 
				$promotion = array();
				if (
					!empty($meta_amzResp)
					&& isset(
						$meta_amzResp['Offers'],
						$meta_amzResp['Offers']['Offer'],
						$meta_amzResp['Offers']['Offer']['Promotions'],
						$meta_amzResp['Offers']['Offer']['Promotions']['Promotion']
					)
				) {
					if (
						isset($meta_amzResp['Offers']['Offer']['Promotions']['Promotion']['Summary'])
						&& !empty($meta_amzResp['Offers']['Offer']['Promotions']['Promotion']['Summary'])
					) {
						$promotion = $meta_amzResp['Offers']['Offer']['Promotions']['Promotion']['Summary'];
					}
					else if (
						is_array($meta_amzResp['Offers']['Offer']['Promotions']['Promotion'])
						&& !empty($meta_amzResp['Offers']['Offer']['Promotions']['Promotion'])
						&& isset(
							$meta_amzResp['Offers']['Offer']['Promotions']['Promotion'][0],
							$meta_amzResp['Offers']['Offer']['Promotions']['Promotion'][0]['Summary']
						)
					) {
						$promotion = $meta_amzResp['Offers']['Offer']['Promotions']['Promotion'][0]['Summary'];
					}
				}

				if ( ! empty($promotion) && is_array($promotion) && isset($promotion['PromotionId']) ) {
	 
					$post = get_post($post_id);

					$coupon = array(
						'asin'              => get_post_meta($post_id, '_amzASIN', true),
						'prod_title'        => (string) $post->post_title,
						'title'             => isset($promotion['BenefitDescription']) ? $promotion['BenefitDescription'] : '',
						'details'           => sprintf( __($this->the_plugin->_translate_string( 'Your coupon will be applied at amazon checkout.' ) . ' %s', $this->localizationName), '<a name="' . __($this->the_plugin->_translate_string( 'COUPON DETAILS' ), $this->localizationName) . '" href="#TB_inline?width=500&height=700&inlineId=WooZoneLite-coupon-popup" class="thickbox link">' . __($this->the_plugin->_translate_string( 'Details' ), $this->localizationName) . '</a>' ),
						'popup_content'     => isset($promotion['TermsAndConditions']) ? $promotion['TermsAndConditions'] : '',
						'link'              => '',
						'link_more'         => '',
					);
					if ( isset($promotion['PromotionId']) ) {
						$coupon = array_merge($coupon, array(
							'link'              => 'http://www.amazon' . $current_amazon_aff['user_country']['website'] . '/gp/coupon/c/' . $promotion['PromotionId'] . '?ie=UTF8&email=&redirectASIN=' . $coupon['asin'] . $_tag,
							'link_more'         => 'http://www.amazon' . $current_amazon_aff['user_country']['website'] . '/gp/coupons/most-popular?ref=vp_c_' . $promotion['PromotionId'] . '_tcs' . $_tag,
						)); 
					}

					// php query class
					require_once( $this->the_plugin->cfg['paths']['scripts_dir_path'] . '/php-query/phpQuery.php' );
					if( trim($coupon['popup_content']) != "" ){
						if ( !empty($this->the_plugin->charset) )
							$doc = WooZoneLitephpQuery::newDocument( $coupon['popup_content'], $this->the_plugin->charset );
						else
							$doc = WooZoneLitephpQuery::newDocument( $coupon['popup_content'] );
						
						$foundLinks = $doc->find("a");
						if ( (int)$foundLinks->size() > 0 ) {
							foreach ( $foundLinks as $foundLink ) {
								$foundLink = WooZoneLitepq( $foundLink );
								$foundLink_href = trim($foundLink->attr('href'));
								$foundLink_href .= $_tag;
								$foundLink->attr( 'href', $foundLink_href );
							}
							$coupon['popup_content'] = $doc->html();
						}
					}

					ob_start();
			?>
					<div class="WooZoneLite-coupon">
						<div class="WooZoneLite-coupon-title"><?php echo $coupon['title']; ?></div>
						<div class="WooZoneLite-coupon-details"><?php echo $coupon['details']; ?></div>
					</div>
					<div id="WooZoneLite-coupon-popup" style="display: none;">
						<div class="WooZoneLite-coupon-container">
							<div class="WooZoneLite-coupon-header">
								<p><?php echo WooZoneLite()->_translate_string( 'Coupons available for this offer' ); ?></p>
								<a href="<?php echo $coupon['link_more']; ?>" target="_blank"><?php echo WooZoneLite()->_translate_string( 'View more coupons' ); ?></a>
							</div>
							<div class="WooZoneLite-coupon-clear"></div>
							<div class="WooZoneLite-coupon-summary">
								<div class="WooZoneLite-coupon-summary-inner">
									<div class="WooZoneLite-coupon-summary-inner-left">
										<a href="<?php echo $coupon['link']; ?>" target="_blank"><?php echo WooZoneLite()->_translate_string( 'Your coupon' ); ?></a>
									</div>
									<div class="WooZoneLite-coupon-summary-inner-right">
										<div><?php echo $coupon['prod_title']; ?></div>
										<div><?php echo $coupon['title']; ?></div>
									</div>
								</div>
							</div>
							<div class="WooZoneLite-coupon-desc">
								<?php echo $coupon['popup_content']; ?>
							</div>
						</div>
					</div>
			<?php
					$contents .= ob_get_clean();
				}
			}

			return $contents;
		}

		// !!! METHOD NOT USED
		public function _product_buy_url_make() {
			$details = array('plugin_name' => 'WooZoneLite');

			$prod_link_open_in = isset( $this->amz_settings['product_buy_button_open_in'] ) && !empty( $this->amz_settings['product_buy_button_open_in'] ) ? $this->amz_settings['product_buy_button_open_in'] : '_blank';

			ob_start();
		?>
			<!-- start/ <?php echo $details['plugin_name']; ?> WooZoneLite product buy url -->
			<script type="text/javascript">
				(function($) {
					jQuery(document).ready(function () {
						var prod_link_open_in = '<?php echo $prod_link_open_in; ?>';

						var links = $('body a[href*="redirectAmzASIN"]');
						//console.log( links );

						// loop through found links
						links.each(function(i) {
							var $this 	= $(this),
								href 	= $this.prop('href'),
								asin 	= href.split('redirectAmzASIN=')[1],
								rpl_el 	= $('.WooZoneLite-product-buy-url-' + asin),
								rpl_link = rpl_el.length ? rpl_el.data('url') : '';
							//console.log( $this, asin );

							// replace link href
							if ( '' != rpl_link ) {
								//$this.attr('href', rpl_link);
								$this.prop('href', rpl_link);
								$this.prop('target', prod_link_open_in);
							}
						});
					});
				})(jQuery);
			</script>
			<!-- end/ <?php echo $details['plugin_name']; ?> wWooZoneLite product buy url -->
		<?php
			$contents = ob_get_clean();
			echo $contents;
		}

		// !!! METHOD NOT USED
		public function _product_buy_url_html() {
			global $product;

			$prod_id = 0;			
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}

			if ( $prod_id ) {
				$product_id = $prod_id;
				$product_buy_url = $this->the_plugin->_product_buy_url_asin( array(
					'product_id' 		=> $product_id,
					'redirect_asin' 	=> '',
				));

				$prod_link = $product_buy_url['link'];
				$prod_asin = $product_buy_url['asin'];

				if ( !empty($product_buy_url) ) {
					echo '<span data-url="' . $prod_link . '" data-product_id="' . $product_id . '" class="WooZoneLite-product-buy-url WooZoneLite-product-buy-url-' . $prod_asin . '" style="display: none;"></span>';
				}
			}
		}

		// [Speed Optimisation Module] Return cached product attributes to additional information tab
		public function check_cached_product_terms() {
			if ( is_product() ) {
				$this->cached_product_terms = get_post_meta( get_the_ID(), '_cached_product_terms', true );
				  
				if( is_array($this->cached_product_terms) && count($this->cached_product_terms) > 0 ) {
					add_filter( 'woocommerce_product_tabs', array( $this, 'cached_terms_additional_information_tab' ), 98 );
				}
			}
		}
		public function cached_terms_additional_information_tab( $tabs ) {
			$tabs['additional_information']['title'] = WooZoneLite()->_translate_string( 'Additional Information' );
			//$tabs['additional_information']['priority'] = 5;
			$tabs['additional_information']['callback'] = array( $this, 'return_cached_product_terms_to_tab' );
			return $tabs;	
		}
		public function return_cached_product_terms_to_tab() {
			$html = array();

			$html[] = '<table class="shop_attributes">';
			$html[] = 	'<tbody>';

			foreach( $this->cached_product_terms as $taxonomy => $terms ) {
				$display_terms = array();
				
				foreach( $terms as $term ) {  
					$display_terms[] = '<a href="' . ( home_url('/?s=' . $term['slug']) . '&post_type=product' ) . '" rel="tag">' . ( $term['name'] ) . '</a>';
				}
				
				$html[] = 		'<tr>';
				$html[] = 			'<th>' . ( $term['taxonomy_name'] ) . '</th>';
				$html[] = 			'<td>';
				$html[] = 				'<p>';
				if( isset($display_terms) && count( $display_terms) > 0 ) {
					$html[] = implode(', ', $display_terms);
				}
				$html[] = 				'</p>';
				$html[] = 			'</td>';
				$html[] = 		'</tr>';
			}
			
			$html[] = 	'</tbody>';
			$html[] = '</table>';
			
			echo implode("\n", $html);
		}



		//====================================================================================
		//== BADGES
		//====================================================================================

		// removed from 3.0, used in older versions of woocommerce as 2.X
		// image_string: sprintf('<li>%s</li>', $image)
		public function badges_show_onproduct( $image_string, $product_id ) {

			//return $image_string; //DEBUG;

			// only one copy allowed
			//var_dump('<pre>',$image_string, strpos( $image_string, 'wzfront-badges-wrapper' ) ,'</pre>');
			if ( strpos( $image_string, 'wzfront-badges-wrapper' ) > 0 ) {
				return $image_string;
			}

			$product = wc_get_product( $product_id );
			if ( !$product ) {
				return $image_string;
			}

			$badge_content = $this->badges_get_template( $product_id );

			if ( empty( $badge_content ) ) {
				return $image_string;
			}

			$badge_content = '<div class="wzfront-badges-wrapper">' . $image_string . $badge_content . '</div>';
			//var_dump('<pre>badge_content',$badge_content ,'</pre>');

			return $badge_content;
		}

		public function badges_show_onproduct_thumbnail( $image_string, $thumb_id ) {

			//return $image_string; //DEBUG;

			global $product;

			if ( did_action( 'woocommerce_product_thumbnails' ) || ! $product ) {
				return $image_string;
			}

			// image string
			// <div data-thumb="{image_url_thumb}" class="woocommerce-product-gallery__image"><a href="{image_url}"><img width="350" height="350" src="{image_url}" class="wp-post-image" alt="" title="" data-caption="" data-src="{image_url}" data-large_image="{image_url}" data-large_image_width="500" data-large_image_height="500" srcset="{image_url} 500w, {image_url_size1} 160w, {image_url_size2} 110w" sizes="100vw" /></a></div>

			//:: get product id
			$prod_id = 0;
			if ( is_object($product) ) {
				$key = $product->is_type( 'variation' ) ? 'parent_id' : 'id';
				$key_ = "get_$key";
				if ( method_exists( $product, $key_ ) ) {
					$prod_id = (int) $product->$key_();
				} else if ( isset($product->$key) ) {
					$prod_id = (int) $product->$key;
				}
			}
			$product_id = $prod_id;

			if ( ! $product_id ) {
				return $image_string;
			}

			//:: show badge
			$show_it = false;
			$div_close = '</div>';

			if ( version_compare( WC()->version, '3.0', '>=' )
				&& get_theme_support( 'wc-product-gallery-slider' )
				&& preg_match('~</div>$~imu', $image_string) !== false
			) {
				$show_it = true;
			}
			//$show_it = false; //DEBUG

			if ( $show_it && isset( $image_string ) && $image_string != '') {

				$image_string = substr( $image_string, 0, -strlen( $div_close ) );

				$badge_content = $this->badges_get_template( $product_id );
				$image_string .= $badge_content;

				$image_string .= $div_close;
			}
			else {
				$image_string = $this->badges_show_onproduct( $image_string, $product_id );
			}
			//var_dump('<pre>', $image_string , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			return $image_string;
		}

		private function badges_get_template( $product_id, $pms=array() ) {

			if ( empty($this->the_plugin->badges_activated) ) {
				return '';
			}

			//:: box position & offsets
			$badges_box_position = 
				isset($this->amz_settings['badges_box_position'])
					? (string) $this->amz_settings['badges_box_position'] : 'top_left';

			$badges_box_offset_vertical = 
				isset($this->amz_settings['badges_box_offset_vertical'])
					? (int) $this->amz_settings['badges_box_offset_vertical'] : 0;

			$badges_box_offset_horizontal = 
				isset($this->amz_settings['badges_box_offset_horizontal'])
					? (int) $this->amz_settings['badges_box_offset_horizontal'] : 0;

			$box_style = array();
			switch ($badges_box_position) {

				case 'top_left':

					$box_style[] = 'top: ' . $badges_box_offset_vertical . 'px;';
					$box_style[] = 'left: ' . $badges_box_offset_horizontal . 'px;';
					break;

				case 'top_right':

					$box_style[] = 'top: ' . $badges_box_offset_vertical . 'px;';
					$box_style[] = 'right: ' . $badges_box_offset_horizontal . 'px;';
					break;

				case 'bottom_left':

					$box_style[] = 'bottom: ' . $badges_box_offset_vertical . 'px;';
					$box_style[] = 'left: ' . $badges_box_offset_horizontal . 'px;';
					break;

				case 'bottom_right':

					$box_style[] = 'bottom: ' . $badges_box_offset_vertical . 'px;';
					$box_style[] = 'right: ' . $badges_box_offset_horizontal . 'px;';
					break;
			}
			$box_style = implode( ' ', $box_style );
			$box_css_class = $badges_box_position;
			//var_dump('<pre>', $box_style , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;


			//:: badges activated
			//var_dump('<pre>',$this->the_plugin->badges_activated ,'</pre>');
			$newproduct = false;
			if ( in_array('new', $this->the_plugin->badges_activated) ) {
				$newproduct = $this->the_plugin->product_badge_is_new( $product_id );
			}

			$onsale = false;
			if ( in_array('onsale', $this->the_plugin->badges_activated) ) {
				$onsale = $this->the_plugin->product_badge_is_onsale( $product_id );
			}

			$freeshipping = false;
			if ( in_array('freeshipping', $this->the_plugin->badges_activated) ) {
				//$freeshipping = $this->the_plugin->product_badge_is_freeshipping( $product_id );
				$freeshipping = $this->the_plugin->is_product_freeshipping( $product_id );
			}

			$amazonprime = false;
			if ( in_array('amazonprime', $this->the_plugin->badges_activated) ) {
				//$amazonprime = $this->the_plugin->product_badge_is_amazonprime( $product_id );
				$amazonprime = $this->the_plugin->is_product_amazonprime( $product_id );
			}

			$__ = compact( 'newproduct', 'onsale', 'freeshipping', 'amazonprime' );
			//var_dump('<pre>', $product_id, $__ , '</pre>');

			$is_found = false;
			$is_found = $is_found || $newproduct;
			$is_found = $is_found || $onsale;
			$is_found = isset($freeshipping['status']) ? ( $is_found || $freeshipping['status'] ) : ( $is_found || $freeshipping );
			$is_found = isset($amazonprime['status']) ? ( $is_found || $amazonprime['status'] ) : ( $is_found || $amazonprime );

			if ( ! $is_found ) {
				return '';
			}


			//:: get template
			$badge_content = WooZoneLite_get_template_html( 'badges/badges.php', array_replace_recursive( array(
				'product_id' 				=> $product_id,
				'box_style' 				=> $box_style,
				'box_css_class' 			=> $box_css_class,

				'product_is_new' 			=> $newproduct,
				'product_is_onsale'			=> $onsale,
				'product_is_freeshipping' 	=> isset($freeshipping['status']) ? $freeshipping['status'] : false,
				'freeshipping_link' 		=> isset($freeshipping['link']) ? $freeshipping['link'] : '',
				'product_is_amazonprime' 	=> isset($amazonprime['status']) ? $amazonprime['status'] : false,
				'amazonprime_link' 			=> isset($amazonprime['link']) ? $amazonprime['link'] : '',
			), $pms ));

			return $badge_content;
		}

		public function woocommerce_sale_flash( $html, $post, $product ) {
			if ( 'yes' == $this->the_plugin->frontend_hide_onsale_default_badge ) {
				return '';
			}
			return $html;
		}



		//====================================================================================
		//== SYNCHRONIZATION ON FRONTEND - by ajax
		//====================================================================================

		public function init_sync_settings() {
			$ss = get_option($this->alias . '_sync', array());
			$ss = maybe_unserialize($ss);
			$ss = $ss !== false ? $ss : array();
			$ss = array_merge(array(
				'sync_products_per_request'				=> 50, // Products to sync per each cron request
				'sync_hour_start'						=> '',
				'sync_recurrence'						=> 24,
				'sync_fields'							=> array(),
			), $ss);

			$this->sync_settings = $ss;
			return $this->sync_settings;
		}

		public function init_sync_options() {
			$ss = get_option($this->alias . '_sync_options', array());
			$ss = maybe_unserialize($ss);
			$ss = $ss !== false ? $ss : array();
			$ss = array_merge(array(
				'interface_max_products' => 'all',
			), $ss);

			$this->sync_options = $ss;
			return $this->sync_options;
		}

		public function syncfront_wrapper() {

			$pms = array_replace_recursive(array(
				'asin' 			=> '',
				'product_id' 	=> 0,
				'product_type' 	=> '',
				'product' 		=> null,
			), $this->syncfront_args);
			extract( $pms );
			//var_dump('<pre>', $pms , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$refresh_in = $this->the_plugin->ss['sync_frontend_refresh_page_sec'];

			$prods2meta = array();

			$__meta_toget = array('_amzaff_sync_last_date');
			$prods2meta = $prods2meta + $this->the_plugin->get_product_metas( $product_id, $__meta_toget, array() );

			//:: do we need to synced it?
			$is_sync_needed = $this->the_plugin->syncproduct_is_sync_needed( array(
				'recurrence' => (int) ( $this->sync_settings['sync_recurrence'] * 3600 ),
				'product_id' => $product_id,
				'sync_last_date' => isset($prods2meta['sync_last_date']) ? $prods2meta['sync_last_date'] : null,
			));

			//:: do we need to load ajax?
			$do_ajax = 'no';
			$do_msg = sprintf( '%s product : no need to synced it', $product_type );
			if ( $is_sync_needed ) {
				$do_ajax = 'yes';
				$do_msg = sprintf( '%s product : recurrence condition for last sync date is met', $product_type );
			}
            //$do_ajax = 'yes'; //DEBUG

			// simple product
			if ( 'simple' == $product_type ) {
			}
			// variable product
			else if ( 'variable' == $product_type ) {
				// because variable parent product could have sync_last_date updated as synced by the cronjob (or from sync admin interface) before it's variation childs were synced
				//$do_ajax = 'yes';
				//$do_msg .= ' - always make an ajax request for this product type';
			}
			// external product
			else if ( 'external' == $product_type ) {
				//TODO???
			}
			// grouped product
			else if ( 'grouped' == $product_type ) {
				//TODO???
			}
			//$do_ajax = 'yes'; //DEBUG SYNC

			$jsPms = array(
				'do_ajax' 		=> $do_ajax,
				'do_msg' 		=> $do_msg,
				'asin' 			=> $asin,
				'product_id' 	=> $product_id,
				'product_type' 	=> $product_type,
				'refresh_in' 	=> $refresh_in,
			);

			$html = array();

			//:: main wrapper
			$html[] = '<div id="WooZoneLite-syncfront-wrapper" class="WooZoneLite-syncfront-wrapper" style="display: none;">';
			$html[] = 	'<div class="WooZoneLite-syncfront-params" style="display: none;">' . json_encode( $jsPms ) . '</div>';
			$html[] = 	'<div id="WooZoneLite-syncfront-content">';
			$html[] = 		'<h3>';
			$html[] =		sprintf( __( $this->the_plugin->_translate_string( 'We`ve just updated this product information. The page will auto refresh in about <span>%s</span> seconds.' ), 'WooZoneLite'), $refresh_in );
			$html[] = 		'</h3>';
			$html[] = 		'<div class="WooZoneLite-syncfront-btn">';
			$html[] = 			'<input type="button" value="Refresh page now" class="WooZoneLite-form-button-small WooZoneLite-form-button-success WooZoneLite-syncfront-action-refresh-yes">';
			$html[] = 			'<input type="button" value="Cancel page refresh" class="WooZoneLite-form-button-small WooZoneLite-form-button-danger WooZoneLite-syncfront-action-refresh-no">';
			$html[] = 		'</div>';
			$html[] = 	'</div>';
			$html[] = '</div>';
			// end #WooZoneLite-syncfront-wrapper

			//:: debug wrapper
			$opGetDebug = $this->syncfront_wrapper_debug( array(
				'asin' 			=> $asin,
				'product_id' 	=> $product_id,
				'product_type' 	=> $product_type,
				'prods2meta' 	=> $prods2meta,
			));
			if ( ! empty($opGetDebug['html']) ) {
				//$html[] = $opGetDebug['html'];
			}
			WooZoneLite_debugbar()->add2bar_row( 'woozonelite-debugbar-sync-frontend', $opGetDebug['html'], array() );
			WooZoneLite_debugbar()->add2bar_menu( 'woozonelite-debugbar-sync-frontend', WooZoneLite()->_translate_string( 'Product Synchronization' ), array() );
			WooZoneLite_debugbar()->add2bar_menua( 'woozonelite-debugbar-sync-frontend', WooZoneLite()->_translate_string( 'Product Synchronization' ), array() );

			$html = implode( PHP_EOL, $html );
			echo $html;
		}

		public function syncfront_wrapper_debug( $pms=array() ) {
			global $wpdb;

			$pms = array_replace_recursive(array(
				'asin' 			=> '',
				'product_id' 	=> 0,
				'product_type' 	=> '',
				'prods2meta' 	=> array(),
			), $pms);
			extract( $pms );
			//var_dump('<pre>', $pms , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$ret = array(
				'html' 	=> '',
			);

			//:: debug wrapper
			//if ( $this->the_plugin->is_debug_mode_allowed() ) {

				$opLastSyncStats = $this->syncfront_wrapper_debug_lastsync( array(
					'asin' 			=> $asin,
					'product_id' 	=> $product_id,
					'product_type' 	=> $product_type,
					'prods2meta' 	=> $prods2meta,
				));
				extract( $opLastSyncStats );

				$html = array();
				$html[] = '<div id="WooZoneLite-syncfront-debug" class="WooZoneLite-syncfront-debug" style="display: none;">';
				$html[] = '<table>';
				$html[] = 	'<thead>';
				$html[] = 		'<tr>';
				$html[] = 			'<th>';
				$html[] = 				'Time';
				$html[] = 			'</th>';
				$html[] = 			'<th>';
				$html[] = 				'Operation';
				$html[] = 			'</th>';
				$html[] = 		'</tr>';
				$html[] = 	'</thead>';
				$html[] = 	'<tbody>';
				$html[] = 		$text_last_sync_niceinfo_html;
				$html[] = 		$text_last_sync_status_html;
				$html[] = 		$text_last_sync_date_html;
				$html[] = 		$text_product_info_html;
				$html[] = 	'</tbody>';
				$html[] = 	'<tfoot>';
				$html[] = 	'</tfoot>';
				$html[] = '</table>';
				$html[] = '</div>';
				// end #WooZoneLite-syncfront-debug

				$ret['html'] = implode( PHP_EOL, $html );
			//}
			return $ret;
		}

		public function syncfront_wrapper_debug_lastsync( $pms=array() ) {
			global $wpdb;

			$pms = array_replace_recursive(array(
				'asin' 			=> '',
				'product_id' 	=> 0,
				'product_type' 	=> '',
				'prods2meta' 	=> array(),
				'recurrence' 	=> $this->sync_settings['sync_recurrence'],
				'text_sync' 	=> 'last sync',
			), $pms);
			extract( $pms );
			//var_dump('<pre>', $pms , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$ret = array();

			$__meta_toget = array('_amzaff_sync_last_date', '_amzaff_sync_hits', '_amzaff_sync_last_status', '_amzaff_sync_last_status_msg', '_amzaff_sync_trash_tries', '_amzaff_country', '_amzaff_sync_current_cycle');
			$prods2meta = $prods2meta + $this->the_plugin->get_product_metas( $product_id, $__meta_toget, array() );

			$row = array_replace_recursive( array(
				'sync_hits' => 0,
				'sync_last_status' => '',
				'sync_last_status_msg' => '',
				'sync_trash_tries' => 0,
				'sync_import_country' => '',
				'sync_current_cycle' => '',
				'first_updated_date' => '',
			), $prods2meta);

			$row["sync_last_status"] = $this->the_plugin->syncproduct_sanitize_last_status(
				$row["sync_last_status"]
			);
			$row["sync_last_status_msg"] = maybe_unserialize( $row["sync_last_status_msg"] );

			$sync_import_country = $row["country"];
			if ( '' != $sync_import_country ) {
				$country_flag = $this->the_plugin->get_product_import_country_flag( array(
					'country' 	=> $sync_import_country,
					'asin' 		=> $asin,
				));
				$sync_import_country = $country_flag['image_link'];
			}
			$row['sync_import_country'] = $sync_import_country;

			$first_updated_date = (int) get_option('WooZoneLite_sync_first_updated_date', 0);
			$row['first_updated_date'] = $first_updated_date;

			//var_dump('<pre>', $row , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$sync_last_stats_column = $this->the_plugin->syncproduct_build_last_stats_column( array(
				'asin' => $asin,
				'sync_nb' => $row['sync_hits'],
				'sync_last_status' => $row['sync_last_status'],
				'sync_last_status_msg' => $row['sync_last_status_msg'],
				'sync_trash_tries' => $row['sync_trash_tries'],
				'sync_import_country' => $row['sync_import_country'],
				'sync_current_cycle' => $row['sync_current_cycle'],
				'first_updated_date' => $row['first_updated_date'],
			));
			$text_last_sync_niceinfo = $sync_last_stats_column['text_last_sync_niceinfo'];

			$text_last_sync_date = sprintf(
				'<u>%s date</u>: %s <br />recurrence: %s hour(s)',
				$text_sync,
				! empty($row['sync_last_date']) ? $this->the_plugin->last_update_date('true', $row['sync_last_date']) : 'none',
				$recurrence
			);

			$text_last_sync_status = sprintf(
				'<u>%s status</u>: %s',
				$text_sync,
				strtoupper($row['sync_last_status']) . '<br />' . $sync_last_stats_column['text_last_sync_title']
			);


			$text_product_info = array();
			$text_product_info[] = '<u>product info</u>';
			$text_product_info[] = 'product #ID: ' . $product_id;
			$text_product_info[] = 'product Asin: ' . $asin;
			$text_product_info[] = 'product Type: ' . $product_type;
			$text_product_info = implode('<br />', $text_product_info);


			//:: HTML
			$html = array();
			$html[] = 		'<tr class="wzsync-update-time">';
			$html[] = 			'<td>';
			$html[] = 				'00:00:00';
			$html[] = 			'</td>';
			$html[] = 			'<td>';
			$html[] = 				$text_product_info;
			$html[] = 			'</td>';
			$html[] = 		'</tr>';

			$ret['text_product_info'] = $text_product_info;
			$ret['text_product_info_html'] = implode( PHP_EOL, $html );


			$html = array();
			$html[] = 		'<tr class="wzsync-update-time">';
			$html[] = 			'<td>';
			$html[] = 				'00:00:00';
			$html[] = 			'</td>';
			$html[] = 			'<td>';
			$html[] = 				$text_last_sync_date;
			$html[] = 			'</td>';
			$html[] = 		'</tr>';

			$ret['text_last_sync_date'] = $text_last_sync_date;
			$ret['text_last_sync_date_html'] = implode( PHP_EOL, $html );


			$html = array();
			$html[] = 		'<tr class="wzsync-update-time">';
			$html[] = 			'<td>';
			$html[] = 				'00:00:00';
			$html[] = 			'</td>';
			$html[] = 			'<td>';
			$html[] = 				$text_last_sync_status;
			$html[] = 			'</td>';
			$html[] = 		'</tr>';

			$ret['text_last_sync_status'] = $text_last_sync_status;
			$ret['text_last_sync_status_html'] = implode( PHP_EOL, $html );


			$html = array();
			$html[] = 		'<tr class="wzsync-update-time">';
			$html[] = 			'<td>';
			$html[] = 				'00:00:00';
			$html[] = 			'</td>';
			$html[] = 			'<td class="wzsync-last-sync-info-wrapper">';
			$html[] = 				$text_last_sync_niceinfo;
			$html[] = 			'</td>';
			$html[] = 		'</tr>';

			$ret['text_last_sync_niceinfo'] = $text_last_sync_niceinfo;
			$ret['text_last_sync_niceinfo_html'] = implode( PHP_EOL, $html );

			return $ret;
		}



		//====================================================================================
		//== AJAX
		//====================================================================================

		/**
		 * Ajax request
		 */
		public function ajax_requests()
		{
			$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : 'none';
	
			$allowed_action = array( 'save_countries', 'save_product_country', 'load_cross_sell', 'cross_sell_empty_cache', 'load_amazon_reviews', 'do_sync' );

			if( !in_array($action, $allowed_action) ){
				die(json_encode(array(
					'status'	=> 'invalid',
					'html'		=> 'Invalid action!'
				)));
			}

			if ( 'save_countries' == $action ) {
				$req = array(
					'product_id'			=> isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0,
					'product_country'		=> isset($_REQUEST['product_country']) ? trim( $_REQUEST['product_country'] ) : 0,
					'countries'				=> isset($_REQUEST['countries']) ? stripslashes(trim( $_REQUEST['countries'] )) : '',
				);
				extract($req);
				//var_dump('<pre>', $req, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				
				$countries = json_decode( $countries, true );
				if ( $countries ) {
					foreach ($countries as $key => $val) {
						unset($countries["$key"]['name']);
					}
				}
				//var_dump('<pre>', $countries, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				
				// save it
				if ( $product_id && $countries ) {
					$meta_value = array(
						'countries'							=> $countries,
						'countries_cache_time'		=> time(),
					);
					update_post_meta( $product_id, '_amzaff_frontend', $meta_value );
				}
				
				// get asin meta key
				$asin = get_post_meta($product_id, '_amzASIN', true);
				$first_variation_asin = $this->get_asin_first_variation( $product_id );
				if( $first_variation_asin !== false ){
					$asin = $first_variation_asin;
				}
				//var_dump('<pre>',$asin,'</pre>');
				
				// save product country
				$_SESSION['WooZoneLite']['product_country']["$asin"] = $product_country;

				die(json_encode(array(
					'status'	=> 'valid',
					'html'		=> 'ok'
				)));
			}

			if ( 'save_product_country' == $action ) {
				$req = array(
					'product_id'			=> isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0,
					'product_country'	=> isset($_REQUEST['product_country']) ? trim( $_REQUEST['product_country'] ) : 0,
				);
				extract($req);
				//var_dump('<pre>', $req, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				
				// get asin meta key
				$asin = get_post_meta($product_id, '_amzASIN', true);
				$first_variation_asin = $this->get_asin_first_variation( $product_id );
				if( $first_variation_asin !== false ){
					$asin = $first_variation_asin;
				}
				//var_dump('<pre>',$asin,'</pre>');
				
				// save product country
				$_SESSION['WooZoneLite']['product_country']["$asin"] = $product_country;

				die(json_encode(array(
					'status'	=> 'valid',
					'html'		=> 'ok'
				)));
			}
			
			if ( 'load_cross_sell' == $action ) {
				$req = array(
					'asin'			=> isset($_REQUEST['asin']) ? (string) $_REQUEST['asin'] : 0,
				);
				extract($req);
				//var_dump('<pre>', $req, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				$boxRsp = $this->_cross_sell_box( array('asin' => $asin) );

				die(json_encode(array(
					'status'	=> 'valid',
					'html'		=> $boxRsp['html'],
					'debug'		=> $boxRsp['debug'],
				)));
			}
			
			if ( 'cross_sell_empty_cache' == $action ) {
				$req = array(
					'asin'			=> isset($_REQUEST['asin']) ? (string) $_REQUEST['asin'] : 0,
				);
				extract($req);
				//var_dump('<pre>', $req, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				$this->_cross_sell_empty_cache( array('asin' => $asin) );

				die(json_encode(array(
					'status'		=> 'valid',
				)));
			}

			if ( 'load_amazon_reviews' == $action ) {
				$req = array(
					'prodid'			=> isset($_REQUEST['prodid']) ? (string) $_REQUEST['prodid'] : 0,
				);
				extract($req);
				//var_dump('<pre>', $req, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				$boxRsp = $this->amazon_reviews_product_review_tab_ajax( array('prodid' => $prodid) );

				die(json_encode(array(
					'status'	=> $boxRsp['status'],
					'html'		=> $boxRsp['html'],
					//'debug'		=> $boxRsp['debug'],
				)));
			}

			// SYNCHRONIZATION
			if ( 'do_sync' == $action ) {
				$req = array(
					'product_id'		=> isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0,
					'asin'				=> isset($_REQUEST['asin']) ? trim( $_REQUEST['asin'] ) : 0,
					'product_type'		=> isset($_REQUEST['product_type']) ? trim( $_REQUEST['product_type'] ) : '',
				);
				extract($req);
				//var_dump('<pre>', $req, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				$html = array();

				$sync_choose_country = isset($this->sync_options['sync_choose_country'])
					? $this->sync_options['sync_choose_country'] : 'import_country';

				if ( empty($asin) ) {
					$asin = WooZoneLite_get_post_meta($id, '_amzASIN', true);
				}
				$asin = $this->the_plugin->prodid_set($asin, $this->the_plugin->prodid_get_provider_by_asin( $asin ), 'add');

				$provider = $this->the_plugin->prodid_get_provider_by_asin( $asin );


				//:: sync product!
				// Initialize the wwcAmazonSyncronize class
				require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/synchronization/init.php' );
				$syncObj = new wwcAmazonSyncronize($this->the_plugin);

				$syncProdPms = array(
					'provider' => $provider,
				);

				$country = '';
				if ( 'import_country' == $sync_choose_country ) {
					$country_db = get_post_meta($product_id, '_amzaff_country', true);
					if ( ! empty($country_db) && is_string($country_db) ) {
						$country = (string) $country_db;
					}
				}
				else {
					$prefix = 'amazon' != $provider ? $provider.'_' : '';
					$country = $this->the_plugin->amz_settings[$prefix.'country'];
				}

				$syncProdPms = array_replace_recursive( $syncProdPms, array(
					//'use_cache' => true,
					'verify_sync_date' => true,
					'verify_sync_date_vars' => true,
					//'recurrence' => '',
				));

				//DEBUG SYNC - BYPASS LAST SYNC DATE
				/*
				$syncProdPms = array_replace_recursive( $syncProdPms, array(
					'DEBUG' => true,
					'verify_sync_date' => false,
					'verify_sync_date_vars' => false,
				));
				*/

				//$syncStat = $syncObj->syncprod_multiple_oldvers( array( $product_id => $asin ), $country, $syncProdPms );
				$syncStat = $syncObj->syncprod_multiple( array( $product_id => $asin ), $country, $syncProdPms );
				$is_sync_needed = isset($syncStat['is_sync_needed'], $syncStat['is_sync_needed']["$product_id"])
					? $syncStat['is_sync_needed']["$product_id"] : true;
                //var_dump('<pre>', $is_sync_needed , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				//var_dump('<pre>', $syncStat , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				//$is_sync_needed = true; //DEBUG

				$html[] = $syncStat['msg'];

				$html = implode('<br />', $html);
				//var_dump('<pre>', $html , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;


				//:: current sync status
				$html_aftersync = '';
				if ( $is_sync_needed ) {
					$opLastSyncStats = $this->syncfront_wrapper_debug_lastsync( array(
						'asin' 			=> $asin,
						'product_id' 	=> $product_id,
						'product_type' 	=> $product_type,
						'prods2meta' 	=> array(),
						'text_sync' 	=> 'current sync',
					));
					extract( $opLastSyncStats );

					$html_aftersync = array();
					$html_aftersync[] = $text_last_sync_niceinfo_html;
					$html_aftersync[] = $text_last_sync_status_html;
					$html_aftersync[] = $text_last_sync_date_html;
					//$html_aftersync[] = $text_product_info_html;
					$html_aftersync = implode( PHP_EOL, $html_aftersync );
				}


				//:: needs refresh
				$do_refresh = 'no';
				if ( $is_sync_needed ) {
					$sync_last_status = get_post_meta($product_id, '_amzaff_sync_last_status', true);
					if ( 'updated' == $sync_last_status ) {
						$do_refresh = 'yes';
					}
				}
				//$do_refresh = 'yes'; //DEBUG SYNC

				die(json_encode(array(
					'status'	=> 'valid',
					'html'		=> $html,
					'html_aftersync' => $html_aftersync,
					'do_refresh' => $do_refresh,
				)));
			}

			die(json_encode(array(
				'status' 	=> 'invalid',
				'html'		=> 'Invalid action!'
			)));
		}



		//====================================================================================
		//== MISC
		//====================================================================================

		// Singleton pattern
		static public function getInstance( $parent )
		{
			if (!self::$_instance) {
				self::$_instance = new self($parent);
			}
			
			return self::$_instance;
		}

		public function session_check() {

			//if ( 0 ) { //DEBUG
			if ( 'yes' == $this->the_plugin->gdpr_rules_is_activated ) {
				$used_sessions = array(
					'WooZoneLite_wizard',
					'WooZoneLite_sync',
					'WooZoneLite_country',
					'WooZoneLite',
					'AmzStore_country',
				);

				if( count($used_sessions) ){
					foreach ( $used_sessions as $key) {
						unset( $_SESSION[$key] );
					}
				}
			}

			//$tmp = (json_encode($_SESSION));

			$html = array();
			$html[] = '<h2>' . 'SESSION:' . '</h2>';
			ob_start();
			echo '<pre>';
			print_r( $_SESSION );
			echo '</pre>';
			$html[] = ob_get_clean();

			$html[] = '<h2>' . 'COOKIES:' . '</h2>';
			ob_start();
			echo '<pre>';
			print_r( $_COOKIE );
			echo '</pre>';
			$html[] = ob_get_clean();

			$html = implode( PHP_EOL, $html );

			if ( ! is_admin() ) {
				WooZoneLite_debugbar()->add2bar_row( 'woozonelite-debugbar-session-check', $html, array() );
			}
		}

		public function frand($min, $max, $decimals = 0) {
			$scale = pow(10, $decimals);
			return mt_rand($min * $scale, $max * $scale) / $scale;
		}
	}
}