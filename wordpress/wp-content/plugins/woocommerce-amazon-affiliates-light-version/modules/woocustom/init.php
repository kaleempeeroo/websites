<?php
/*
* Define class WooZoneLiteWooCustom
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLiteWooCustom') != true) {
	class WooZoneLiteWooCustom
	{
		/*
		* Some required plugin information
		*/
		const VERSION = '1.0';

		/*
		* Store some helpers config
		*/
		public $the_plugin = null;

		private $module_folder = '';
		private $module = '';
		
		static protected $_instance;
		
		//custom attributes
		private $plugin_settings = array();
		
		public $is_admin = false;
		
		private $WooZoneLitePriceSelect = null;


		/*
		 * Required __construct() function that initalizes the AA-Team Framework
		 */
		public function __construct()
		{
			global $WooZoneLite;

			$this->the_plugin = $WooZoneLite;
			$this->module_folder = $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'modules/woocustom/';
			$this->module = $this->the_plugin->cfg['modules']['woocustom'];
 
			$this->is_admin = $this->the_plugin->is_admin;

			$this->init();
		}
		
		/**
		 * Head Filters & Init!
		 *
		 */
		public function init() {

			if ( $this->is_admin ) {
				// admin header
				add_action( 'admin_head', array( $this, 'admin_make_head' ), 1 );

				// admin footer
				add_action( 'admin_footer', array( &$this, 'admin_make_footer' ), 1 );
			}

			if ( $this->is_admin && current_user_can('administrator') ) {

				add_action( 'WooZoneLite_admin_header', array($this, 'admin_custom_fields_header'), 0 );
				add_action( 'WooZoneLite_admin_footer', array($this, 'admin_custom_fields_footer'), 31 );

				// adding custom product info on the edit product page, the general tab section of the WooCommerce, underneath the price fields
				add_action( 'WooZoneLite_admin_footer', array($this, 'admin_edit_metabox_footer'), 30 );
				add_action( 'woocommerce_product_options_sku', array( $this, 'admin_edit_metabox' ) );

				//:: PRODUCTS Listing
				// adding custom product info on the product listing page
				$screens = array('product');
				foreach ($screens as $screen) {
					add_filter( 'manage_edit-' . $screen . '_columns', array( &$this, 'admin_prodlist_edit_columns' ), 10, 1 );
					//add_filter( 'manage_' . $screen . '_posts_columns', array( $this, 'admin_prodlist_edit_columns' ), 10, 1 );
					add_action( 'manage_' . $screen . '_posts_custom_column', array( $this, 'admin_prodlist_posts_columns' ), 10, 2 );
					add_action( 'manage_edit-' . $screen . '_sortable_columns', array( $this, 'custom_col_sort' ), 10, 2 );
				}

				//:: ORDERS Listing
				add_action( 'restrict_manage_posts', array( $this, 'custom_col_sort_select' ), 100, 2 );
				add_filter( 'request', array( $this, 'custom_col_sort_orderby' ) );

				add_action( 'manage_shop_order_posts_custom_column', array( $this, 'admin_shop_order_posts_columns' ), 10, 2 );

				// !!! moved in above hooks
				// woocommerce fix thumb for remote images with https
				// - on product listing page - admin product listing wp-admin/edit.php?post_type=product
				//add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_columns' ), 999 );
				//add_filter( 'manage_product_posts_columns', array( $this, 'product_columns' ), 999 );

				// try to get the price_select module
				// if ( in_array('price_select', $this->the_plugin->cfg['core-modules'])
				// 	|| $this->the_plugin->verify_module_status( 'price_select' ) ) {
				// 	require_once( $this->the_plugin->cfg['modules']['price_select']['folder_path'] . 'init.php');
				// 	$this->WooZoneLitePriceSelect = WooZoneLitePriceSelect::getInstance();
				// }
			}

			//:: OLD - don't know if they work anymore! (todo - verification)
			add_filter( 'woocommerce_get_catalog_ordering_args', array( $this, 'get_catalog_ordering_args') );
			add_filter( 'woocommerce_default_catalog_orderby_options', array( $this, 'catalog_orderby') );
			add_filter( 'woocommerce_catalog_orderby', array( $this, 'catalog_orderby') );


			//:: ORDER - DISABLED AMAZON CHECKOUT & DROPSHIP TAX
			if ( $this->the_plugin->disable_amazon_checkout ) {

				// frontend/ when creating an order - update order metas
				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'woo_checkout_update_order_meta' ), 10, 2 );

				// frontend/ when adding an product to cart
				add_filter( 'woocommerce_add_cart_item_data', array( $this, 'woo_add_cart_item_data' ), 10, 4 );

				// frontend/ when creating an order - update metas for each item from order
				add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'woo_checkout_create_order_line_item'), 10, 4 );
			}

			// admin/ hide (from being displayed) some order item metas
			add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'woo_hidden_order_itemmeta' ), 10, 1 );

			// admin/ admin order page, order items box
			add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'woo_admin_order_totals_after_total' ), 10, 1 );

			// admin/ admin order page, order items box
			add_action( 'woocommerce_after_order_itemmeta', array( $this, 'woo_after_order_itemmeta' ), 10, 3 );

			// wp ajax actions
			add_action('wp_ajax_WooZoneLite_woocustom', array( $this, 'ajax_requests') );
			//add_action('wp_ajax_nopriv_WooZoneLite_woocustom', array( $this, 'ajax_requests') );
		}

		/**
		 * Singleton pattern
		 *
		 * @return WooZoneLiteWooCustom Singleton instance
		 */
		static public function getInstance() {
			if (!self::$_instance) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}


		/**
		 * Admin Header & Footer hooks!
		 */
		public function admin_make_head() {
			$details = array('plugin_name' => 'WooZoneLite');

			if ( !has_action('WooZoneLite_admin_header') )
				return true;
   
			ob_start();
		?>
			<!-- start/ admin header/ <?php echo $details['plugin_name']; ?> -->
		<?php
			do_action( 'WooZoneLite_admin_header' );
		?>
			<!-- end/ admin header/ <?php echo $details['plugin_name']; ?> -->
		<?php
			$contents = ob_get_clean();
			echo $contents;
			return true;
		}
		
		public function admin_make_footer() {
			$details = array('plugin_name' => 'WooZoneLite');
			
			if ( !has_action('WooZoneLite_admin_footer') )
				return true;

			ob_start();
		?>
			<!-- start/ admin footer/ <?php echo $details['plugin_name']; ?> -->
		<?php
			do_action( 'WooZoneLite_admin_footer' );
		?>
			<!-- end/ admin footer/ <?php echo $details['plugin_name']; ?> -->
		<?php
			$contents = ob_get_clean();
			echo $contents;
			return true;
		}


		/**
		 * custom fields header & footer / css & js files
		 */
		public function admin_custom_fields_header() {
			echo WooZoneLite_asset_path( 'css', $this->module_folder . 'app.woocustom.css', false );			
			//echo $this->WooZoneLitePriceSelect->css_page_list();
		}

		public function admin_custom_fields_footer() {
			echo WooZoneLite_asset_path( 'js', $this->module_folder . 'app.woocustom.js', false );

			global $post;
			$post_id = isset($post->ID) ? $post->ID : 0;

			// admin order details page - marker has amazon products
			$html = array();
			$html[] = '<div class="WooZoneLite-marker-order-hasamazon-tpl" style="display: none;">';
			$html[] = 	$this->order_marker_hasamazon_show( $post_id );
			$html[] = '</div>';

			$html = implode( PHP_EOL, $html );
			echo $html;
		}
		

		/**
		 * edit product page & listing products - add ASIN & amazon product URL fields
		 */
		public function admin_edit_metabox() {
			global $post;
			$post_id = isset($post->ID) ? (int) $post->ID : 0;
			
			if ( $post_id <= 0 ) return ;
			$asin = (string) WooZoneLite_get_post_meta( $post_id, '_amzASIN', true );

			$provider = $this->the_plugin->prodid_get_provider_by_asin( $asin );

			$label_text = sprintf( __( '%s ASIN', 'woozonelite' ), ucfirst($provider) );
			
			// no asin => not an amazon product!
			if ( empty($asin) ) return ;

			woocommerce_wp_text_input( array( 'id' => 'WooZoneLite_asin', 'class' => 'wc_input_url short', 'label' => $label_text, 'value' => $asin, 'data_type' => 'price', 'custom_attributes' => array('readonly' => 'readonly', 'disabled' => 'disabled', 'style' => 'color: green; font-weight: bold;'), 'style' => 'color: green; font-weight: bold;' ) );
		}
		
		public function admin_edit_metabox_footer( $post_id = 0 ) {
			$req = array(
				'is_post_edit'      => isset($_REQUEST['post']) ? true : false,
				'post_id'           => isset($_REQUEST['post']) ? (int) $_REQUEST['post'] : $post_id,
			);
			extract($req);

			$arrProducts = array();

			if ( empty($post_id) ) return;

			$isProdValid = $this->the_plugin->verify_product_is_amazon($post_id, array( 'verify_provider' => false ));
			if ( $isProdValid !== true ) return;
			
			$arrProducts[0] = $post_id;

			// verify if it's a variable product?
			$isProdVariation = $this->the_plugin->verify_product_isvariation($post_id);
			if ( $isProdVariation ) {
				$arrProducts = array_merge( $arrProducts, $this->the_plugin->get_product_variations($post_id) );
				//if ( empty($arrProducts) ) return;
			};
  
			$post_id_orig = $post_id;
			$html = array();
			foreach ( $arrProducts as $post_id ) { // products loop!
  
				$asin = (string) WooZoneLite_get_post_meta( $post_id, '_amzASIN', true);
				$prod_url = $this->the_plugin->_product_buy_url( $post_id, $asin, true );

				$provider = $this->the_plugin->prodid_get_provider_by_asin( $asin );
				$provider_alias = $this->the_plugin->prodid_get_provider_alias( $asin );
			
				// start html
				$html[] = '<div class="WooZoneLiteWoocustomFields" data-post_id="' . ($post_id) . '" data-asin="' . ($asin) . '" data-provider="' . ($provider) . '" data-provider_alias="' . ($provider_alias) . '" style="display: none;">';

				//$attr = array(
				//    'cssClass'          => "WooZoneLite-price-$priceType-$metaVal",
				//    'name'              => "WooZoneLite-price[$post_id][$priceType][$metaVal]",
				//    'value'             => $_value,
				//);
				//$html[] = '<input type="hidden" class="'.$attr['cssClass'].'" name="'.$attr['name'].'" value="'.$attr['value'].'" />';
				$html[] = 	'<a href="' . $prod_url . '" class="button button-primary button-large" target="_blank">' . __('View this Product online', 'woozonelite') . '</a>';

				// dropshiping tax related
				if ( $this->the_plugin->dropshiptax_is_active() ) {

					$the_product = wc_get_product( $post_id );
					$price_html = $this->the_plugin->get_price_html( $the_product, array(
						'with_wrapper' => true,
					));

					$html[] = $price_html;
				}

				$html[] = '</div>';
				// end html
			
			} // end // products loop!
			if ( empty($html) ) return;
			
			$ret = implode( PHP_EOL, $html );
			if ( $is_post_edit ) {
				echo $ret;
			} else {
				return $ret;
			}
		}
		
		public function admin_prodlist_edit_columns( $existing_columns ) {

			$new_columns['WooZoneLite_product_info'] = __('WooZoneLite Info', 'woozonelite');

			//$old_key = 'thumb'; $new_key = 'thumb_woozonelite';
			$old2new = array();
			$old2new['thumb'] = 'thumb_woozonelite';

			if ( $this->the_plugin->dropshiptax_is_active() ) {
				$old2new['price'] = 'price_woozonelite';
			}

			if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
				$existing_columns = array();
			}
	
			$keys = array_keys($existing_columns);

			foreach ( $old2new as $old_key => $new_key ) {
				if ( false !== ($index = array_search($old_key, $keys)) ) {
					$keys[$index] = $new_key;
					$existing_columns = array_combine($keys, array_values($existing_columns));
				}
			}

			//var_dump('<pre>',$existing_columns,'</pre>');
			return array_merge( $existing_columns, $new_columns );
		}
		
		public function admin_prodlist_posts_columns( $column_name, $id ) {

			switch ($column_name) {

				case 'name':

					$is_direct_import_products = (boolean) get_post_meta( $id, '_amzaff_direct_import', true );
					$is_direct_import_noawskeys_products = (boolean) get_post_meta( $id, '_amzaff_direct_import_noawskeys', true );

					if ( $is_direct_import_products === true ) {
						$html = array();
						$html[] = '<span class="WooZoneLite-marker-direct-import">';
						$html[] = 	'<img src="' . ( $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'icon_directimport.png' ) . '" />';
						$html[] = 	'Direct Import';
						$html[] = '</span>';

						echo implode( "\n", $html );
					}
					else if ( $is_direct_import_noawskeys_products === true ) {
						$html = array();
						$html[] = '<span class="WooZoneLite-marker-direct-import">';
						$html[] = 	'<img src="' . ( $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'icon_directimport.png' ) . '" />';
						$html[] = 	'Direct Import NoAwsKeys';
						$html[] = '</span>';

						echo implode( "\n", $html );
					}
					else {
						$is_demo_products = (boolean) get_post_meta( $id, '_amzaff_aateam_keys', true );
						if ( $is_demo_products === true ) {
							$html = array();
							$html[] = '<span class="WooZoneLite-marker-demo-product">';
							$html[] = 	'<img src="' . ( $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'icon_24.png' ) . '" />';
							$html[] = 	'Demo Product';
							$html[] = '</span>';

							echo implode( "\n", $html );
						}
					}
					break;

				case 'thumb_woozonelite':

					global $post, $the_product;

					if ( ! empty( $the_product ) && is_object($the_product) ) {
						if ( method_exists( $the_product, 'get_id' ) ) {
							$prod_id = (int) $the_product->get_id();
						} else if ( isset($the_product->id) && (int) $the_product->id > 0 ) {
							$prod_id = (int) $the_product->id;
						}
					}
					if ( empty( $the_product ) || $prod_id != $post->ID ) {
						$the_product = wc_get_product( $post );
					}

					echo '<a href="' . get_edit_post_link( $post->ID ) . '">' . $this->product_get_image( $the_product, 'thumbnail' ) . '</a>';
					break;

				case 'WooZoneLite_product_info':

					global $id;
					$post_id = $id;

					if ( empty($post_id) ) break;

					$isProdValid = $this->the_plugin->verify_product_is_amazon($post_id, array( 'verify_provider' => false ));
					if ( $isProdValid !== true ) break;

					$arrProducts = array();
					$isProdVariation = $this->the_plugin->verify_product_isvariation($post_id);
					if ( $isProdVariation ) {
						$arrProducts = array_merge( $arrProducts, $this->the_plugin->get_product_variations($post_id) );
						//if ( empty($arrProducts) ) return;
					}

					// product (parent in case of variations)
					$asin = (string) WooZoneLite_get_post_meta( $post_id, '_amzASIN', true );

					$provider = $this->the_plugin->prodid_get_provider_by_asin( $asin );

					$country_flag = $this->the_plugin->get_product_import_country_flag( array(
						'product_id' => $post_id,
						'asin' 		=> $asin,
					));

					$prod_url = $this->the_plugin->_product_buy_url( $post_id, $asin, $country_flag['country'] );



					$text_last_sync_niceinfo = $country_flag['image_link'];

					if ( 'amazon' === $provider ) {

						$post_metas = array();
						$what_metas = array( '_amzaff_syncwidget_trash_tries', '_amzaff_syncwidget_hits', '_amzaff_syncwidget_last_date', '_amzaff_syncwidget_last_status_msg', '_amzaff_syncwidget_last_status' );
						$post_metas = $post_metas + $this->the_plugin->get_product_metas( $post_id, $what_metas, array('remove_prefix' => '_amzaff_syncwidget_') );
						//var_dump('<pre>', $post_metas , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

						$sync_last_stats_column = $this->the_plugin->syncwidget_build_last_stats_column( array(
							'asin' => $asin,
							'sync_nb' => isset($post_metas['hits']) ? $post_metas['hits'] : 0,
							'sync_last_status' => isset($post_metas['last_status']) ? $post_metas['last_status'] : '',
							'sync_last_status_msg' => isset($post_metas['last_status_msg']) ? maybe_unserialize( $post_metas['last_status_msg'] ) : '',
							'sync_trash_tries' => isset($post_metas['trash_tries']) ? $post_metas['trash_tries'] : 0,
							'sync_last_date' => isset($post_metas['last_date']) ? $post_metas['last_date'] : '',
							'sync_import_country' => $country_flag['image_link'],
							//'sync_current_cycle' => $row['sync_current_cycle'],
							//'first_updated_date' => $row['first_updated_date'],
						));
						$text_last_sync_niceinfo = $sync_last_stats_column['text_last_sync_niceinfo'];
					}

					ob_start();
		?>
					<div class="WooZoneLiteWoocustomFields" data-post_id="<?php echo $post_id; ?>" data-asin="<?php echo $asin; ?>" style="">
						<?php echo $text_last_sync_niceinfo; ?>
						<?php //echo $country_flag['image_link']; ?>
						<span title="<?php echo sprintf( __('%s ASIN', 'woozonelite'), $provider ); ?>"><?php echo $asin; ?></span>
						<a href="<?php echo $prod_url; ?>" target="_blank" title="<?php echo sprintf( __('View this Product on %s', 'woozonelite'), $provider ); ?>"><i class="fa fa-lg fa-external-link"></i></a>
						<?php if ( $isProdVariation ) { ?>
						<span title="<?php _e('variations number for this product', $this->the_plugin->localizationName); ?>">(<?php echo count($arrProducts); ?>)</span>
						<?php } ?>
					</div>
		<?php
					$html[] = ob_get_contents();
					ob_end_clean();
					
					/*
					$post_id_orig = $post_id;
					foreach ( $arrProducts as $post_id ) { // products loop!
		  
						$asin = (string) get_post_meta( $post_id, '_amzASIN', true);
						$prod_url = $this->the_plugin->_product_buy_url( $post_id, $asin, $country_flag['country'] );
					
						// start html
						$html[] = '<div class="WooZoneLiteWoocustomFields" data-post_id="' . ($post_id) . '" data-asin="' . ($asin) . '" style="display: none;">';
		
						//$attr = array(
						//    'cssClass'          => "WooZoneLite-price-$priceType-$metaVal",
						//    'name'              => "WooZoneLite-price[$post_id][$priceType][$metaVal]",
						//    'value'             => $_value,
						//);
						//$html[] = '<input type="hidden" class="'.$attr['cssClass'].'" name="'.$attr['name'].'" value="'.$attr['value'].'" />';
						$html[] = '<a href="' . $prod_url . '" class="button button-primary button-large" target="_blank">' . __('View Product Amazon page', 'WooZoneLite') . '</a>';
		
						$html[] = '</div>';
						// end html
					
					} // end // products loop!
					*/
					
					// price_select module
					if ( 'amazon' == $provider ) {
						//$html[] = $this->WooZoneLitePriceSelect->get_post_column($post_id);
					}
					
					echo implode(PHP_EOL, $html);
					break;

				case 'price_woozonelite':

					//$product = new WC_Product( $post_id );
					//$product = new WC_Product_Variable( $post_id );
					global $post, $the_product;

					if ( ! empty( $the_product ) && is_object($the_product) ) {
						if ( method_exists( $the_product, 'get_id' ) ) {
							$prod_id = (int) $the_product->get_id();
						} else if ( isset($the_product->id) && (int) $the_product->id > 0 ) {
							$prod_id = (int) $the_product->id;
						}
					}
					if ( empty( $the_product ) || $prod_id != $post->ID ) {
						$the_product = wc_get_product( $post );
					}

					echo $this->the_plugin->get_price_html( $the_product, array(
						'with_wrapper' => false,
					));
					break;
					
				default:
					break;
			} // end switch
		}

		public function custom_col_sort( $columns ) {
			$new_columns['price_woozonelite'] = 'price_woozonelite';

			return array_merge( $columns, $new_columns );
		}

		public function custom_col_sort_orderby( $request ) {

			//:: orderby/ column: price_woozonelite
			if ( isset( $request['orderby'] ) && $request['orderby'] == 'price_woozonelite' ) {
				$request = array_merge($request, array(
					'meta_key' => '_price',
					'orderby'  => 'meta_value_num'
				));
			}

			//:: filter/ drop-down has amazon / has dropship tax
			if ( isset( $_GET['woozonelite_order_filter_hasamz'] ) ) {
				
				$selVal = $_GET['woozonelite_order_filter_hasamz'];

				$interval = false;
				if ( in_array($selVal, array('hasamazon', 'hasdptax', 'nonamazon')) ) {
					$interval = $selVal;
				}

				if ( $interval!==false ) {

					if ( 'nonamazon' == $interval ) {
						$request = array_merge($request, array(
							'meta_query' => array(
								'relation' => 'OR'
								,array(
									'key' 		=> '_amz_nbamzprods',
									'value' 	=> '', // this is ignored, but is necessary
									'compare' 	=> 'NOT EXISTS', // works
								)
								,array(
									'key' 		=> '_amz_nbamzprods',
									'value' 	=> '0',
									'compare' 	=> '<=',
								)
							)
						));
					}
					else if ( 'hasamazon' == $interval ) {
						$request = array_merge($request, array(
							'meta_query' => array(
								'relation' => 'AND'
								,array(
									'key' 		=> '_amz_nbamzprods',
									'value' 	=> '0',
									'compare' 	=> '>',
								)
							)
						));
					}
					else if ( 'hasdptax' == $interval ) {
						$request = array_merge($request, array(
							'meta_query' => array(
								'relation' => 'AND'
								,array(
									'key' 		=> '_amz_dptax',
									'value' 	=> '0',
									'compare' 	=> '>',
								)
								,array(
									'key' 		=> '_amz_nbamzprods',
									'value' 	=> '0',
									'compare' 	=> '>',
								)
							)
						));
					}
				}
			}

			//:: filter/ drop-down has amazon / has dropship tax
			if ( isset( $_GET['woozonelite_order_filter_amzstatus'] ) ) {
				
				$selVal = $_GET['woozonelite_order_filter_amzstatus'];

				$all_amz_status = $this->the_plugin->woo_order_all_amazon_status();
				$all_amz_status = array_keys( $all_amz_status );

				$interval = false;
				if ( in_array($selVal, $all_amz_status) ) {
					$interval = $selVal;
				}

				if ( $interval!==false ) {

					$request = array_merge($request, array(
						'meta_query' => array(
							'relation' => 'AND'
							,array(
								'key' 		=> '_amz_status',
								'value' 	=> $interval,
								'compare' 	=> '=',
							)
						)
					));
				}
			}

			//:: filter/ products provider
			if ( isset( $_GET['woozonelite_product_filter_provider'] ) ) {
				
				$selVal = $_GET['woozonelite_product_filter_provider'];

				$interval = false;
				if ( in_array($selVal, $this->the_plugin->providers_is_enabled()) ) {
					$interval = $selVal;
				}

				if ( $interval!==false ) {

					if ( 'amazon' == $interval ) {
						$request = array_merge($request, array(
							'meta_query' => array(
								//'relation' => 'OR'
								array(
									'key' 		=> '_amzASIN',
									'compare' 	=> 'EXISTS', // works
								)
								//,array(
								//	'key' 		=> '_amzaff_prodid',
								//	'value' 	=> 'amz-',
								//	'compare' 	=> 'REGEXP', //works in >= WordPress 3.7
								//)
							)
						));
					}
					else {
						$pprefix = $this->the_plugin->get_ws_prefixes( $selVal );
						$request = array_merge($request, array(
							'meta_query' => array(
								'relation' => 'OR'
								,array(
									'key' 		=> '_amzaff_prodid',
									'value' 	=> "{$pprefix}-",
									'compare' 	=> 'REGEXP', //works in >= WordPress 3.7
								)
							)
						));
					}
				}
			}

			return $request;
		}

		public function custom_col_sort_select( $post_type, $which ) {
			global $pagenow;
			if ( $pagenow == 'upload.php' ) {
				return false;
			}

			if ( 'shop_order' == $this->the_plugin->u->get_current_post_type() ) {

				$html = array();

				$html[] = '<select name="woozonelite_order_filter_hasamz">';
				$html[] = '<option value="all">' . __( 'WooZoneLite: All Orders', 'woozonelite' ) . '</option>';
				$values = array(
					//'none' 			=> __( 'WooZoneLite: All Orders', 'WooZoneLite' ),
					'hasamazon' 	=> __( 'WooZoneLite: Orders with amazon products', 'woozonelite' ),
					'hasdptax' 		=> __( 'WooZoneLite: Orders with amazon products & Dropshipping tax', 'woozonelite' ),
					'nonamazon' 	=> __( 'WooZoneLite: Orders without amazon products', 'woozonelite' ),
				);
				foreach ( $values as $key => $val ) {
					$html[] = '<option ' . (isset( $_GET['woozonelite_order_filter_hasamz'] ) && $_GET['woozonelite_order_filter_hasamz'] == $key ? ' selected="selected" ' : '') . 'value="' . $key . '">' . $val . '</option>';
				}
				$html[] = '</select>';

				$html[] = '<select name="woozonelite_order_filter_amzstatus">';
				$html[] = '<option value="all">' . __( 'WooZoneLite Status: All', 'woozonelite' ) . '</option>';
				$values = $this->the_plugin->woo_order_all_amazon_status();
				foreach ( $values as $key => $val ) {
					$html[] = '<option ' . (isset( $_GET['woozonelite_order_filter_amzstatus'] ) && $_GET['woozonelite_order_filter_amzstatus'] == $key ? ' selected="selected" ' : '') . 'value="' . $key . '">' . sprintf( __( 'WooZoneLite Status: %s', 'woozonelite' ), $val ) . '</option>';
				}
				$html[] = '</select>';

				echo implode('', $html);
			}
			else if ( 'product' == $this->the_plugin->u->get_current_post_type() ) {

				$html = array();

				$html[] = '<select name="woozonelite_product_filter_provider">';
				$html[] = '<option value="all">' . __( 'WooZoneLite: Products Provider', 'woozonelite' ) . '</option>';
				$values = $this->the_plugin->providers_get_filter_dropdown( array(
					'use_key' 		=> 'alias',
					'title_prefix' 	=> '', //__( 'WooZoneLite: ', 'WooZoneLite' ),
				));
				foreach ( $values as $key => $val ) {
					$html[] = '<option ' . (isset( $_GET['woozonelite_product_filter_provider'] ) && $_GET['woozonelite_product_filter_provider'] == $key ? ' selected="selected" ' : '') . 'value="' . $key . '">' . $val . '</option>';
				}
				$html[] = '</select>';

				echo implode('', $html);
			}

			return false;
		}



		//====================================================================================
		//== ORDER - DISABLED AMAZON CHECKOUT & DROPSHIP TAX
		//====================================================================================

		public function admin_shop_order_posts_columns( $column_name, $id ) {

			switch ($column_name) {

				case 'order_number':

					echo $this->order_marker_hasamazon_show( $id );
					break;

				case 'order_status':

					echo $this->order_marker_amzstatus_show( $id );
					break;
			}
		}

		public function woo_hidden_order_itemmeta( $arr ) {
			$arr = array_merge( $arr, array(
				'_amz_asin',
				'_amz_parent_asin',
				'_amz_country',
				'_amz_prodinfo',
			));
			return $arr;
		}

		public function woo_checkout_update_order_meta( $order_id, $data ) {

			// dropship tax
			$orderinfo = array();
			$orderinfo['dropshiptax'] = $this->the_plugin->dropshiptax;
			$orderinfo['roundedprices'] = $this->the_plugin->roundedprices;

			update_post_meta( $order_id, '_amz_orderinfo', $orderinfo );

			$order_has_amazon = $this->the_plugin->woo_order_has_amazon( $order_id, true );
			update_post_meta( $order_id, '_amz_nbamzprods', $order_has_amazon );

			$has_dptax = $this->the_plugin->dropshiptax_is_active() ? 1 : 0;
			update_post_meta( $order_id, '_amz_dptax', $has_dptax );
		}

		public function woo_add_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {

			$prodinfo_arr = $this->order_get_item_info_by_id( $product_id, $variation_id );

			foreach ( $prodinfo_arr as $key => $val ) {
				if ( '' == $val ) continue 1;

				$cart_item_data["_amz_$key"] = $val;
			}

			return $cart_item_data;
		}

		public function woo_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {

			if ( isset($values['_amz_asin'], $values['_amz_country'], $values['_amz_prodinfo']) ) {
				$prodinfo_arr = $this->order_get_item_info_from_values( $values );
			}
			else {
				$product_id = isset($values['product_id']) ? (int) $values['product_id'] : 0;
				$variation_id = isset($values['variation_id']) ? (int) $values['variation_id'] : 0;

				$prodinfo_arr = $this->order_get_item_info_by_id( $product_id, $variation_id );
			}

			foreach ( $prodinfo_arr as $key => $val ) {
				if ( '' == $val ) continue 1;

				$item->add_meta_data( "_amz_$key", $val );
			}
		}

		public function woo_admin_order_totals_after_total( $order_id ) {

			$order_has_amazon = get_post_meta( $order_id, '_amz_nbamzprods', true );
			$order_has_dptax = get_post_meta( $order_id, '_amz_dptax', true );
			$order_amz_status = get_post_meta( $order_id, '_amz_status', true );

			$totals = array();

			// admin order page - our amazon checkout box
			if ( $order_has_amazon ) {

				$shops = $this->the_plugin->woo_order_get_amazon_prods_bycountry( $order_id );
				if ( empty($shops) ) return false;

				//if ( $order_has_dptax ) {
					$totals = $this->the_plugin->woo_order_get_amazon_totals( $order_id, array(
						'shops' => $shops,
					));
				//}

				$box = $this->the_plugin->frontend->box_amazon_shops_checkout( array(
					'where' 	=> 'order',
					'shops' 	=> $shops,
					'totals' 	=> $totals,
					'order_id' 	=> $order_id,
					'order_info'=> array(
						'has_amazon' 	=> $order_has_amazon,
						'has_dptax' 	=> $order_has_dptax,
						'amazon_status' => $order_amz_status,
					),
				));
				if ( !empty($box) ) {
					echo $box;
				}
			}

			// admin order page - main box
			if ( $order_has_dptax && $order_has_amazon ) {

				$totals_diff = 0.00;
				if ( isset($totals['gtotal'], $totals['gtotal']['price'], $totals['gtotal']['price_orig']) ) {
					$totals_diff = $totals['gtotal']['price'] - $totals['gtotal']['price_orig'];
				}

				$price_args = array(); //array( 'currency' => $order->get_currency() );

				$html = array();

				$html[] = '<tr>';
				$html[] = 		'<td class="label refunded-total WooZoneLite-dptax-profit">';
				$html[] = 			__( 'Your Profit from Dropshiping Tax', 'woozonelite' ) . ':';
				$html[] = 		'</td>';
				$html[] = 		'<td width="1%"></td>';
				$html[] = 		'<td class="total refunded-total WooZoneLite-dptax-profit">';
				$html[] = 			wc_price( $totals_diff, $price_args );
				$html[] = 		'</td>';
				$html[] = '</tr>';

				$html = implode( PHP_EOL, $html );
				echo $html;
			}
		}

		public function woo_after_order_itemmeta( $item_id, $item, $product ) {

			//var_dump('<pre>', $item_id, $item, $product , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			$html = array();

			// Get the common data in an array
			$item_product_data = $item->get_data();

			$quantity = isset($item_product_data['quantity']) ? (int) $item_product_data['quantity'] : 1;

			// Get the special meta data in an array
			//$item_product_meta_data = $item->get_meta_data();

			$item_metas = $this->the_plugin->woo_order_get_item_metas( $item );
			//var_dump('<pre>', $item_metas , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$order_id = isset($item_product_data['order_id']) ? (int) $item_product_data['order_id'] : 0;
			$order_has_amazon = get_post_meta( $order_id, '_amz_nbamzprods', true );
			$order_has_dptax = get_post_meta( $order_id, '_amz_dptax', true );

			if ( ! $order_has_amazon ) {
				return true;
			}

			$product_id = isset($item_product_data['product_id']) ? (int) $item_product_data['product_id'] : 0;
			$variation_id = isset($item_product_data['variation_id']) ? (int) $item_product_data['variation_id'] : 0;
			$elem_id = $variation_id ? $variation_id : $product_id;

			// is amazon product?
			$amzASIN = isset($item_metas['_amz_asin']) && ! empty($item_metas['_amz_asin'])
				? $item_metas['_amz_asin'] : '';

			if ( empty($amzASIN) ) return true;

			$parent_amzASIN = isset($item_metas['_amz_parent_asin']) && ! empty($item_metas['_amz_parent_asin'])
				? $item_metas['_amz_parent_asin'] : '';

			$country = isset($item_metas['_amz_country']) && ! empty($item_metas['_amz_country'])
				? $item_metas['_amz_country'] : '';

			$country_flag_asin = $this->order_get_item_country_flag( $amzASIN, $country );
			$country_flag_parentasin = '';
			if ( '' != $parent_amzASIN ) {
				$country_flag_parentasin = $this->order_get_item_country_flag( $parent_amzASIN, $country );
			}
			//var_dump('<pre>', $country_flag_asin, $country_flag_parentasin, '</pre>');

			$prodinfo = isset($item_metas['_amz_prodinfo']) && ! empty($item_metas['_amz_prodinfo'])
				? $item_metas['_amz_prodinfo'] : array();
			//var_dump('<pre>', $prodinfo , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			//:: dropshiping tax related
			//$the_product = wc_get_product( $elem_id );
			//$price_html = $this->the_plugin->get_price_html( $the_product, array(
			//	'with_wrapper' => true,
			//));

			$price_html = '';
			if ( $order_has_dptax ) {

				$price_arr = array(
					'price' => isset($prodinfo['price']) ? $prodinfo['price'] : 0.00,
					'price_orig' => isset($prodinfo['price_orig']) ? $prodinfo['price_orig'] : 0.00,
				);
				foreach ( $price_arr as $pkey => $pval ) {
					if ( $pval > 0.00 && $quantity > 1 ) {
						$price_arr["$pkey"] = $pval * $quantity;
					}
				}

				$price_html = $this->the_plugin->get_price_html_profit( $price_arr, array(
					'with_wrapper' 	=> true,
					'quantity' 		=> $quantity,
				));
			}

			ob_start();
		?>

			<div class="WooZoneLite-order-lineitems">

				<div class="WooZoneLite-order-lineitem">

					<div class="country">
						<?php echo $country_flag_asin['image_link']; ?>
					</div>

					<?php
					if ( '' != $amzASIN ) {
					?>
					<div class="asin">
						<?php echo sprintf( __('ASIN = %s', 'woozonelite'), $amzASIN ); ?>
						<a href="<?php echo $country_flag_asin['link']; ?>" target="_blank" title="<?php _e('View this Product on Amazon', $this->the_plugin->localizationName); ?>">
							<i class="fa fa-lg fa-external-link"></i>
						</a>
					</div>
					<?php
					}
					?>

					<?php
					if ( '' != $parent_amzASIN ) {
					?>
					<div class="parentasin">
						<?php echo sprintf( __('Parent ASIN = %s', 'woozonelite'), $parent_amzASIN ); ?>
						<a href="<?php echo $country_flag_parentasin['link']; ?>" target="_blank" title="<?php _e('View this Product on Amazon', $this->the_plugin->localizationName); ?>">
							<i class="fa fa-lg fa-external-link"></i>
						</a>
					</div>
					<?php
					}
					?>

					<?php
					if ( '' != $price_html ) {
					?>
					<div class="prices">
						<?php echo $price_html; ?>
					</div>
					<?php
					}
					?>

				</div>

			</div>

		<?php
			$html[] = ob_get_clean();
			echo implode( PHP_EOL, $html );
		}

		public function order_get_item_info_by_id( $product_id, $variation_id=0 ) {

			$ret = array(
				'asin' 			=> '',
				'parent_asin' 	=> '',
				'country' 		=> '',
				'prodinfo' 		=> array(),
			);

			$elem_id = $variation_id ? $variation_id : $product_id;

			// asin
			$asin = '';
			if ( $elem_id ) {
				$asin = get_post_meta( $elem_id, '_amzASIN', true );
				$asin = trim( $asin );
			}

			// parent asin
			$parent_asin = '';
			if ( $variation_id ) {
				$parent_asin = get_post_meta( $product_id, '_amzASIN', true );
				$parent_asin = trim( $parent_asin );
			}

			// country & other product info
			$prodinfo = array(
				'countryinfo' 	=> array(),
				'asin' 			=> $asin,
				'parent_asin' 	=> $parent_asin,
			);

			// country (amazon store from where the product will be received)
			$country = '';
			$product_country = $this->get_product_country_current( $product_id );
			//var_dump('<pre>', $product_id, $product_country, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( !empty($product_country) && isset($product_country['website']) ) {

				$prodinfo['countryinfo'] = $product_country;
				
				$product_country = substr($product_country['website'], 1);
				$country = $product_country;
				$prodinfo['country'] = $country;
			}

			// dropship tax price
			remove_filter( 'get_post_metadata', array( $this->the_plugin, 'gpm_on_price' ), 999 );
			$price_orig = get_post_meta( $elem_id, '_price', true );
			add_filter( 'get_post_metadata', array( $this->the_plugin, 'gpm_on_price' ), 999, 4 );

			$price = get_post_meta( $elem_id, '_price', true );
			//var_dump('<pre>', $price, $price_dp , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$prodinfo['price_orig'] = $price_orig;
			$prodinfo['price'] = $price;

			$ret = array_replace_recursive( $ret, array(
				'asin' 			=> $asin,
				'parent_asin' 	=> $parent_asin,
				'country' 		=> $country,
				'prodinfo' 		=> $prodinfo,
			));
			return $ret;
		}

		public function order_get_item_info_from_values( $values=array() ) {

			$asin = isset($values['_amz_asin']) ? (string) $values['_amz_asin'] : '';
			$parent_asin = isset($values['_amz_parent_asin']) ? (string) $values['_amz_parent_asin'] : '';
			$country = isset($values['_amz_country']) ? (string) $values['_amz_country'] : '';
			$prodinfo = isset($values['_amz_prodinfo']) ? (array) $values['_amz_prodinfo'] : array();

			$ret = array(
				'asin' 			=> $asin,
				'parent_asin' 	=> $parent_asin,
				'country' 		=> $country,
				'prodinfo'		=> $prodinfo,
			);
			return $ret;
		}

		public function order_get_item_amazon_url( $asin, $country, $pms=array() ) {
			$product_buy_url = $this->the_plugin->_product_buy_url_asin( array(
				'product_id' 		=> 0,
				'redirect_asin' 	=> $asin,
				'force_country' 	=> $country,
			));
			$product_buy_url = $product_buy_url['link'];
			return $product_buy_url;
		}

		public function order_get_item_country_flag( $asin, $country, $pms=array() ) {

			$provider = $this->the_plugin->prodid_get_provider_by_asin( $asin );

			$ret = $this->the_plugin->get_product_import_country_flag( array(
				'product_id' 	=> 0,
				'asin' 			=> $asin,
				'country' 		=> $country,
				'use_fallback_location' => false,
				'filter_choose_country' => false,
				'text' 			=> str_replace( '[[country]]', $provider, __('product was ordered from [[country]] location %s', 'woozonelite') ),
			));
			return $ret;
		}

		public function order_marker_hasamazon_show( $order_id ) {

			$order_has_amazon = get_post_meta( $order_id, '_amz_nbamzprods', true );
			$order_has_dptax = get_post_meta( $order_id, '_amz_dptax', true );
			//var_dump('<pre>',$order_has_amazon, $order_has_dptax ,'</pre>');

			// && ! $order_has_dptax
			if ( ! $order_has_amazon ) {
				return '';
			}

			$css = array();
			$css[] = 'WooZoneLite-marker-order-hasamazon';
			$text = __( 'Order Contains Amazon Products', 'woozonelite' );
			if ( $order_has_dptax ) {
				$css[] = 'WooZoneLite-marker-order-hasdptax';
				$text = __( 'Order Contains Amazon Products & Dropshipping Tax', 'woozonelite' );
			}
			$css = implode( ' ', $css );

			$html = array();
			$html[] = '<span class="' . $css . '">';
			$html[] = 	'<img src="' . ( $this->the_plugin->cfg['paths']['plugin_dir_url'] . '16.png' ) . '" />';
			$html[] = 	$text;
			$html[] = '</span>';

			return implode( PHP_EOL, $html );
		}

		public function order_marker_amzstatus_show( $order_id ) {

			$order_status = get_post_meta( $order_id, '_amz_status', true );
			//var_dump('<pre>',$order_status ,'</pre>');

			$all_amz_status = $this->the_plugin->woo_order_all_amazon_status();

			if ( ! in_array( $order_status, array_keys($all_amz_status) ) ) {
				return '';
			}

			$css = 'WooZoneLite-marker-order-amzstatus ' . $order_status;
			$text = $all_amz_status["$order_status"];

			$html = array();
			$html[] = '<mark class="' . $css . ' aa-tooltip title="' . sprintf( __( 'Amazon Status: %s', 'woozonelite' ), $text ) . '">';
			$html[] = 		'<span>';
			$html[] = 			$text;
			$html[] = 		'</span>';
			$html[] = '</mark>';

			return implode( PHP_EOL, $html );
		}

		public function get_product_country_current( $product_id ) {
			return $this->the_plugin->get_product_country_current( $product_id );
		}



		//====================================================================================
		//== AJAX
		//====================================================================================

		public function ajax_requests()
		{
			$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : 'none';
	
			$allowed_action = array( 'save_order_amazon_status' );

			if( !in_array($action, $allowed_action) ){
				die(json_encode(array(
					'status'	=> 'invalid',
					'html'		=> 'Invalid action!'
				)));
			}

			if ( 'save_order_amazon_status' == $action ) {
				$req = array(
					'order_id' => isset($_REQUEST['order_id']) ? (int) $_REQUEST['order_id'] : 0,
					'order_status' => isset($_REQUEST['order_status']) ? (string) $_REQUEST['order_status'] : '',
				);
				extract($req);
				//var_dump('<pre>', $req, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				update_post_meta( $order_id, '_amz_status', $order_status );

				die(json_encode(array(
					'status'	=> 'valid',
					'html'		=> 'ok'
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

		public function catalog_orderby( $sortby ) 
		{
			$sortby['sales_rank'] = __('Sort by Sales Rank', 'woozonelite');
			return $sortby;
		}

		public function get_catalog_ordering_args( $args )
		{
		  $orderby_value = isset( $_GET['orderby'] )
			? ( function_exists('wc_clean') ? wc_clean( $_GET['orderby'] ) : woocommerce_clean( $_GET['orderby'] ) )
			: apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
		
			if ( 'sales_rank' == $orderby_value ) {
				$args['orderby'] = 'meta_value_num';
				$args['order'] = 'ASC';
				$args['meta_key'] = '_sales_rank';
			}
			
			return $args;
		}

		/*
		// woocommerce fix thumb for remote images with https
		// - on product listing page - admin product listing wp-admin/edit.php?post_type=product
		public function product_columns( $existing_columns ) {
			$old_key = 'thumb'; $new_key = 'thumb_woozonelite';

			if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
				$existing_columns = array();
			}
	
			$keys = array_keys($existing_columns);
			if ( false !== ($index = array_search($old_key, $keys)) ) {
				$keys[$index] = $new_key;
				$existing_columns = array_combine($keys, array_values($existing_columns));
			}

			//var_dump('<pre>',$existing_columns,'</pre>');
			return $existing_columns;
		}

		public function render_product_columns( $column ) {
			if ( 'thumb_woozonelite' == $column ) {
				global $post, $the_product;

				if ( ! empty( $the_product ) && is_object($the_product) ) {
					if ( method_exists( $the_product, 'get_id' ) ) {
						$prod_id = (int) $the_product->get_id();
					} else if ( isset($the_product->id) && (int) $the_product->id > 0 ) {
						$prod_id = (int) $the_product->id;
					}
				}
				if ( empty( $the_product ) || $prod_id != $post->ID ) {
					$the_product = wc_get_product( $post );
				}

				echo '<a href="' . get_edit_post_link( $post->ID ) . '">' . $this->product_get_image( $the_product, 'thumbnail' ) . '</a>';
			}
		}
		*/
		
		public function product_get_image( $product, $size = 'shop_thumbnail', $attr = array(), $placeholder = true ) {
			$prod_id = 0;
			if ( is_object($product) ) {
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}

			if ( has_post_thumbnail( $prod_id ) ) {
				$image = get_the_post_thumbnail( $prod_id, $size, $attr );
			} elseif ( ( $parent_id = wp_get_post_parent_id( $prod_id ) ) && has_post_thumbnail( $parent_id ) ) {
				$image = get_the_post_thumbnail( $parent_id, $size, $attr );
			} elseif ( $placeholder ) {
				$image = wc_placeholder_img( $size );
			} else {
				$image = '';
			}

			// NOT an woozonelite product
			if ( $this->the_plugin->verify_product_is_amazon($product, array( 'verify_provider' => 'amazon' )) !== true ) {
				$image = str_replace( array( 'https://', 'http://' ), '//', $image );
			}

			//var_dump('<pre>', 'aateamdbg', $image ,'</pre>');
			return $image;
		}
	}
}

//$WooZoneLiteWooCustom = new WooZoneLiteWooCustom();
$WooZoneLiteWooCustom = WooZoneLiteWooCustom::getInstance();