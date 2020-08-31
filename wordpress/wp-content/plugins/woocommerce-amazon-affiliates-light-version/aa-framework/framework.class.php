<?php
/**
 * AA-Team freamwork class
 * http://www.aa-team.com
 * =======================
 *
 * @package     WooZoneLite
 * @author      AA-Team
 * @version     1.0
 */
! defined( 'ABSPATH' ) and exit;

require_once( dirname(__FILE__) . '/functions-before.php');

//use Leafo\ScssPhp\Compiler;
if(class_exists('WooZoneLite') != true) {
	class WooZoneLite {

		public $version = null; // see version method for details

		const VERSION = 1.0;

		// The time interval for the remote XML cache in the database (21600 seconds = 6 hours)
		const NOTIFIER_CACHE_INTERVAL = 21600;

		public $alias = 'WooZoneLite';
		public $details = array();
		public $localizationName = 'woozonelite';

		public $dev = '';
		public $debug = false;
		public $is_admin = false;

		/**
		 * configuration storage
		 *
		 * @var array
		 */
		public $cfg = array();

		/**
		 * plugin modules storage
		 *
		 * @var array
		 */
		public $modules = null;

		/**
		 * errors storage
		 *
		 * @var object
		 */
		private $errors = null;

		/**
		 * DB class storage
		 *
		 * @var object
		 */
		public $db = array();

		public $facebookInstance = null;
		public $fb_user_profile = null;
		public $fb_user_id = null;

		private $plugin_hash = null;
		private $v = null;

		// Products Providers Helpers!
		public $genericHelper = null;
		public $amzHelper = null;
		public $ebayHelper = null;

		public $jsFiles = array();

		public $wp_filesystem = null;

		private $opStatusMsg = array();

		public $charset = '';

		public $pluginDepedencies = null;
		public $pluginName = 'WooZoneLite';

		public $feedback_url = "http://aa-team.com/feedback/index.php?app=%s&refferer_url=%s";

		public $amz_settings = array();

		public $u; // utils function object!
		public $pu; // utils function object!
		public $timer; // timer object

		public $cur_provider = 'amazon';

		// New Settings / february 2016
		public $plugin_details = array(); // see constructor
		public $ss = array(
			//86400 seconds = 24 hours

			// (false = no caching) DEBUG: don't cache client country in session: $_SESSION['WooZoneLite_country']
			'cache_client_country'						=> true,

			// max allowed remote requests to aa-team demo server
			'max_remote_request_number'					=> 100, // -1 = DEBUG

			// max allowed number of products imported using aa-team demo keys
			// !!! changed to 3 on 2019-feb-18
			'max_products_demo_keys'					=> 3, //default: 10

			// admin css cache time ( 0 = no caching )
			'css_cache_time'							=> 86400, // in seconds

			// product details countries box
			// amazon country shops where product is available - cache time ( 0 = no caching )
			'countries_cache_time'						=> 60, // in seconds

			// timeout to verify if all plugin tables are installed right!
			'check_integrity'							=> array(
				// seconds  (86400 seconds = 24 hours)
				'check_tables'								=> 259200, // 3 days
				'check_alter_tables'						=> 259200, // 3 days
				'check_cronjobs_prefix'						=> 86400, // 1 day

				'check_table_amz_locale_reference' 			=> 86400, // 1 day
				'check_table_amz_amzkeys' 					=> 86400, // 1 day
				'check_table_amz_amazon_cache' 				=> 86400, // 1 day
				'check_table_amz_import_stats'				=> 86400, // 1 day
				'check_table_amz_sync_widget'				=> 86400, // 1 day
				'check_table_amz_sync_widget_asins'			=> 86400, // 1 day

				'check_alter_table_amz_queue' 				=> 86400, // 1 day
				'check_alter_table_amz_oct18' 				=> 86400, // 1 day
			),

			// maximum number of variations to import per product
			'max_per_product_variations' 				=> 2,

			// cronjob sync retries on error/throttled items
			'max_cron_sync_retries_onerror' 			=> 2,

			// frontend synchronization - the time to refresh the page when successfull sync
			'sync_frontend_refresh_page_sec'			=> 15, //360000, // in seconds

			// mysql expression for cached amazon requests (used for product synchronization)
			'sync_amazon_requests_cache_exp' 			=> 'INTERVAL 1 HOUR',

			// maximum number of images per each variation child (for variable products)
			'max_images_per_variation' 					=> 10,
		);

		private static $plugin_row_meta = array(
			'buy_url'           => 'http://codecanyon.net/item/woocommerce-amazon-affiliates-wordpress-plugin/3057503',
			'portfolio'         => 'http://codecanyon.net/user/aa-team/portfolio',
			'docs_url'          => 'http://docs.aa-team.com/products/woocommerce-amazon-affiliates/',
			'support_url'       => 'http://support.aa-team.com/',
			'latest_ver_url'    => 'http://cc.aa-team.com/apps-versions/index.php?app=',
		);

		private static $aateam_keys_script = 'http://cc.aa-team.com/woozone-keys/keys-woozone.php';
		public $sync_tries_till_trash = 3;

		public $frontend; // frontend object!

		public $plugin_tables = array('amz_assets', 'amz_cross_sell', 'amz_products', 'amz_queue', 'amz_report_log', 'amz_search', 'amz_locale_reference', 'amz_amzkeys', 'amz_amazon_cache', 'amz_import_stats', 'amz_sync_widget', 'amz_sync_widget_asins');

		public $updater_dev = null;

		public $country2mainaffid = array(
			'com' 	=> 'com',
			'ca' 	=> 'ca',
			'cn' 	=> 'cn',
			'de' 	=> 'de',
			'in' 	=> 'in',
			'it' 	=> 'it',
			'es' 	=> 'es',
			'fr' 	=> 'fr',
			'co.uk' => 'uk',
			'co.jp' => 'jp',
			'com.mx'=> 'mx',
			'com.br'=> 'br',
			'com.au'=> 'au',
			'ae' 	=> 'ae',
		);

		// init_plugin_attributes
		public $is_remote_images = false;
		public $amzapi = 'newapi';
		public $disable_amazon_checkout = false;
		public $p_type = null;
		public $product_buy_is_amazon_url = null;
		public $product_url_short = null;
		public $import_product_offerlistingid_missing = null;
		public $import_product_variation_offerlistingid_missing = null;
		public $product_offerlistingid_missing_external = null;
		public $product_offerlistingid_missing_delete = null;
		public $products_force_delete = null;
		public $gdpr_rules_is_activated = null;
		public $frontend_hide_onsale_default_badge = null;
		public $frontend_show_free_shipping = null;
		public $frontend_show_coupon_text = null;
		public $show_availability_icon = null;
		public $badges_activated = array();
		public $badges_where = array();
		public $dropshiptax = array();
		public $roundedprices = array();

		public $bitly_oauth_api = 'https://api-ssl.bitly.com/';

		public $demokeysObj = null;
		public $amzkeysObj = null;

		public $debug_bar_activate = true;
		public $debugbar = null; // debug bar object

		public $DirectImport = null; // direct import object

		public $cacheit = null;


		// amazon webservice status
		public $wsStatus = array();


		// amazon remote images
		public $imagesfix = null;

		// use method 'get_amazon_images_path'
		public static $amazon_images_path = 'images-amazon.|media-amazon.'; //m.media-amazon.com


		// ebay image sizes list
		public $ebay_image_sizes;
		public $ebay_utils = null;

		// use method 'get_ebay_images_path'
		public static $ebay_images_path = 'i.ebayimg.';


		// The constructor
		public function __construct($here = __FILE__) {
			if( defined('UPDATER_DEV') ) {
				$this->updater_dev = (string) UPDATER_DEV;
			}

			$this->fix_dbalias_issue(); // amzstore dbalias fix

			$this->update_developer();
			$this->is_admin = is_admin() === true ? true : false;

			// admin css cache time ( 0 = no caching )
			//$this->ss['css_cache_time'] = 86400; // seconds  (86400 seconds = 24 hours)
			if( defined('WOOZONELITE_DEV_STYLE') && WOOZONELITE_DEV_STYLE ){
				$this->ss['css_cache_time'] = (int) WOOZONELITE_DEV_STYLE; // seconds
			}
			if ( defined('WOOZONELITE_DEV_STYLE_GULP') && WOOZONELITE_DEV_STYLE_GULP ) {
				$this->ss['css_cache_time'] = -1; //always use cache
			}

			add_action('wp_ajax_WooZoneLite_framework_style', array( $this, 'framework_style') );
			add_action('wp_ajax_nopriv_WooZoneLite_framework_style', array( $this, 'framework_style') );

			// get all amazon settings options
			$this->settings();

			$this->init_plugin_attributes();

			//$current_url = $_SERVER['HTTP_REFERER'];
			$current_url = $this->get_current_page_url();
			$this->feedback_url = sprintf($this->feedback_url, $this->alias, rawurlencode($current_url));

			// load WP_Filesystem
			include_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
			global $wp_filesystem;
			$this->wp_filesystem = $wp_filesystem;

			$this->plugin_hash = get_option('WooZoneLite_hash');

			// set the freamwork alias
			$this->buildConfigParams('default', array( 'alias' => $this->alias ));

			// get the globals utils
			global $wpdb;

			// store database instance
			$this->db = $wpdb;

			// instance new WP_ERROR - http://codex.wordpress.org/Function_Reference/WP_Error
			$this->errors = new WP_Error();

			// charset
			if ( isset($this->amz_settings['charset']) && !empty($this->amz_settings['charset']) ) {
				$this->charset = $this->amz_settings['charset'];
			}

			// plugin root paths
			$this->buildConfigParams('paths', array(
				// http://codex.wordpress.org/Function_Reference/plugin_dir_url
				'plugin_dir_url' => str_replace('aa-framework/', '', plugin_dir_url( (__FILE__)  )),

				// http://codex.wordpress.org/Function_Reference/plugin_dir_path
				'plugin_dir_path' => str_replace('aa-framework/', '', plugin_dir_path( (__FILE__) ))
			));

			// add plugin lib frontend paths and url
			$this->buildConfigParams('paths', array(
				'frontend_dir_url' => $this->cfg['paths']['plugin_dir_url'] . 'lib/frontend',
				'frontend_dir_path' => $this->cfg['paths']['plugin_dir_path'] . 'lib/frontend'
			));

			// add plugin scripts paths and url
			$this->buildConfigParams('paths', array(
				'scripts_dir_url' => $this->cfg['paths']['plugin_dir_url'] . 'lib/scripts',
				'scripts_dir_path' => $this->cfg['paths']['plugin_dir_path'] . 'lib/scripts'
			));

			// add plugin admin paths and url
			$this->buildConfigParams('paths', array(
				'freamwork_dir_url' => $this->cfg['paths']['plugin_dir_url'] . 'aa-framework/',
				'freamwork_dir_path' => $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/'
			));

			// composer paths
			$this->buildConfigParams('paths', array(
				'composer_dir_url' => $this->cfg['paths']['plugin_dir_url'] . 'composer/',
				'composer_dir_path' => $this->cfg['paths']['plugin_dir_path'] . 'composer/'
			));

			// add core-modules alias
			$this->buildConfigParams('core-modules', array(
				'amazon',
				'dashboard',
				'modules_manager',
				'setup_backup',
				'server_status',
				'insane_import',
				'support',
				'assets_download',
				'stats_prod',
				'price_select',
				'amazon_debug',
				'woocustom',
				'cronjobs',
				'direct_import',
				'addons',
				'noaws_import',
				'noaws_sync_widget',
			));

			// list of freamwork css files
			$this->buildConfigParams('freamwork-css-files', array(
				'core' => 'css/core.css',
				'panel' => 'css/panel.css',
				'form-structure' => 'css/form-structure.css',
				'form-elements' => 'css/form-elements.css',
				'form-message' => 'css/form-message.css',
				'button' => 'css/button.css',
				'table' => 'css/table.css',
				//'tipsy' => 'css/tooltip.css',
				'tipsy' => 'js/tippyjs/tippy.min.css',
				'admin' => 'css/admin-style.css',
				'jquery.simplemodal' => 'js/jquery.simplemodal/basic.css',
			));

			// list of freamwork js files
			$this->buildConfigParams('freamwork-js-files', array(
				'admin'             => 'js/adminv9.js',
				'hashchange'        => 'js/hashchange.min.js',
				'ajaxupload'        => 'js/ajaxupload.js',
				//'tipsy'             => 'js/tooltip.js',
				'tipsy'             => 'js/tippyjs/tippy.min.js',
				'download_asset'    => '../modules/assets_download/app.assets_download.js',
				'counter'           => 'js/counter.js',
				'jquery.simplemodal' => 'js/jquery.simplemodal/jquery.simplemodal.1.4.4.min.js',
			));

			$this->version(); // set plugin version

			// DEBUG - use hola chrome extension to test different client countries
			//$this->debug_get_country();

			// plugin folder in wp-content/plugins/
			$plugin_folder = explode('wp-content/plugins/', $this->cfg['paths']['plugin_dir_path']);
			$plugin_folder = end($plugin_folder);
			$this->plugin_details = array(
				'folder'        => $plugin_folder,
				'folder_index'  => $plugin_folder . 'plugin.php',
			);

			// utils functions
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/utils/utils.php' );
			if( class_exists('WooZoneLite_Utils') ){
				// $this->u = new WooZoneLite_Utils( $this );
				$this->u = WooZoneLite_Utils::getInstance( $this );
			}

			// plugin utils functions
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/utils/plugin_utils.php' );
			if( class_exists('WooZoneLite_PluginUtils') ){
				// $this->pu = new WooZoneLite_PluginUtils( $this );
				$this->pu = WooZoneLite_PluginUtils::getInstance( $this );
			}

			// images fix
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/utils/images.fix.php' );
			if( class_exists('WooZoneLite_ImagesFix') ){
				// $this->imagesfix = new WooZoneLite_ImagesFix( $this );
				$this->imagesfix = WooZoneLite_ImagesFix::getInstance( $this );
			}

			// cacheit - deactivated on 2017-08-16
			//require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/utils/cacheit.init.php' );
			//if( class_exists('WooZoneLiteCacheit_Init') ){
			//	// $this->cacheit = new WooZoneLiteCacheit_Init( $this );
			//	$this->cacheit = WooZoneLiteCacheit_Init::getInstance( $this );
			//	//$this->cacheit->cacheitInit(); //deactivated on 2017-08-16
			//}

			// DEBUG BAR
			require_once( $this->cfg['paths']['scripts_dir_path'] . '/debugbar/debugbar.php' );
			if( class_exists('WooZoneLiteDebugBar') ){
				//$this->debugbar = new WooZoneLiteDebugBar();
				$this->debugbar = WooZoneLiteDebugBar::getInstance( $this );
			}

			// DIRECT IMPORT
			// require_once( $this->cfg['paths']['scripts_dir_path'] . '/directimport/directimport.php' );
			// if( class_exists('WooZoneLiteDirectImport') ){
			// 	//$this->DirectImport = new WooZoneLiteDirectImport();
			// 	$this->DirectImport = WooZoneLiteDirectImport::getInstance( $this );
			// }

			// product updater
			add_action( 'admin_init', array($this, 'product_updater') );

			// get plugin text details
			$this->get_plugin_data();

			// timer functions
			require_once( $this->cfg['paths']['scripts_dir_path'] . '/runtime/runtime.php' );
			if( class_exists('aaRenderTime') ){
				//$this->timer = new aaRenderTime( $this );
				$this->timer = aaRenderTime::getInstance();
			}

			// mandatory step, try to load the validation file
			// require_once( $this->cfg['paths']['plugin_dir_path'] . 'validation.php' );
			// $this->v = new WooZoneLite_Validation();
			// $this->v->isReg($this->plugin_hash);

			require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/menu.php' );

			// Run the plugins section load method
			add_action('wp_ajax_WooZoneLiteLoadSection', array( $this, 'load_section' ));

			add_action('wp_ajax_WooZoneLiteDismissNotice', array( $this, 'dismiss_notice' ));

			// Plugin Depedencies Verification!
			if ( false === $this->depedencies_verify() ) {
				return false;
			}

			// Run the plugins initialization method
			add_action('init', array( $this, 'initThePlugin' ), 5);
			add_action('init', array( $this, 'session_start' ), 1);
			//add_action('wp_logout', 'WooZoneLite_session_close', 1);
			//add_action('wp_login', 'WooZoneLite_session_close', 1);

			// Run the plugins section options save method
			add_action('wp_ajax_WooZoneLiteSaveOptions', array( $this, 'save_options' ));

			// Run the plugins section options save method
			add_action('wp_ajax_WooZoneLiteModuleChangeStatus', array( $this, 'module_change_status' ));

			// Run the plugins section options save method
			add_action('wp_ajax_WooZoneLiteModuleChangeStatus_bulk_rows', array( $this, 'module_bulk_change_status' ));

			// Run the plugins section options save method
			add_action('wp_ajax_WooZoneLiteInstallDefaultOptions', array( $this, 'install_default_options' ));

			add_action('wp_ajax_WooZoneLiteUpload', array( $this, 'upload_file' ));

			add_action('admin_init', array($this, 'plugin_redirect'));

			if( $this->debug == true ){
				add_action('wp_footer', array($this, 'print_plugin_usages') );
				add_action('admin_footer', array($this, 'print_plugin_usages') );
			}

			add_action( 'admin_init', array($this, 'product_assets_verify') );

			if ( $this->is_admin ) {
				//add_action( 'admin_bar_menu', array($this->pu, 'update_notifier_bar_menu'), 1000 );
				//add_action( 'admin_menu', array($this->pu, 'update_plugin_notifier_menu'), 1000 );

				// add additional links below plugin on the plugins page
				add_filter( 'plugin_row_meta', array($this->pu, 'plugin_row_meta_filter'), 10, 2 );

				// alternative API to check updating for the filter transient
				//add_filter( 'pre_set_site_transient_update_plugins', array( $this->pu, 'update_plugins_overwrite' ), 10, 1 );

				// alternative response with plugin details for admin thickbox tab
				//add_filter( 'plugins_api', array( $this->pu, 'plugins_api_overwrite' ), 10, 3 );

				// message on wp plugins page with updating link
				//add_action( 'in_plugin_update_message-'.$this->plugin_details['folder_index'], array($this->pu, 'in_plugin_update_message'), 10, 2 );

				// 2018-jul-10 /moved to modules/woocustom/init.php
				//if( isset($_GET['post_type']) && $_GET['post_type'] == 'product' ) {
				//	add_action( 'manage_posts_custom_column' , array( $this, 'add_demo_products_marker' ), 10, 2 );
				//}
			}

			require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/ajax-list-table.php' );
			new WooZoneLiteAjaxListTable( $this );

			// aateam amazon keys - when client use aateam demo keys
			require_once( $this->cfg['paths']['plugin_dir_path'] . '_keys/demokeys.php' );
			$this->demokeysObj = new aaWoozoneDemoKeysLib( $this, array() );

			// multiple amazon keys
			require_once( $this->cfg['paths']['plugin_dir_path'] . '_keys/amzkeys.php' );
			$this->amzkeysObj = new aaWoozoneAmzKeysLib( $this );

			// when we've implemented amazon multiple keys - version 9.3
			$this->fix_multikeys_from_single();


			//==========================================
			//:: HELPERS INIT

			// GENERIC Helper
			if ( 1 ) {
				require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/helpers/generic.helper.class.php' );

				if( class_exists('WooZoneLiteGenericHelper') ){
					// $this->genericHelper = new WooZoneLiteGenericHelper( $this );
					$this->genericHelper = WooZoneLiteGenericHelper::getInstance( $this );
				}
			}

			// AMAZON Helper
			//if (
			//	isset($this->amz_settings['AccessKeyID'])
			//	&& isset($this->amz_settings['SecretAccessKey'])
			//	&& trim($this->amz_settings['AccessKeyID']) != ""
			//	&& trim($this->amz_settings['SecretAccessKey']) != ""
			//) {
				$this->amzHelper = $this->get_ws_object_new( 'amazon', 'new_helper', array(
					'the_plugin' => $this,
				));
				//:: disabled on 2018-feb
				//require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/amz.helper.class.php' );
				//if( class_exists('WooZoneLiteAmazonHelper') ){
				//	// $this->amzHelper = new WooZoneLiteAmazonHelper( $this );
				//	$this->amzHelper = WooZoneLiteAmazonHelper::getInstance( $this );
				//}
				//:: end disabled on 2018-feb
			//}

			// EBAY Helper
			//require( $this->cfg['paths']['scripts_dir_path'] . '/ebay/ebay_utils.php' );
			$getEbayUtils = $this->ebay_addon_controller( 'file_utils', array() );
			if ( isset($getEbayUtils['file_path']) ) {
				require( $getEbayUtils['file_path'] );
				$this->ebay_utils = new aaWZoneEbayUtils();
				$this->ebay_image_sizes = $this->ebay_utils->get_image_sizes( 'square' );
			}
			//if (
			//	isset($this->amz_settings['ebay_DEVID'])
			//	&& isset($this->amz_settings['ebay_AppID'])
			//	&& isset($this->amz_settings['ebay_CertID'])
			//	&& trim($this->amz_settings['ebay_DEVID']) != ""
			//	&& $this->amz_settings['ebay_AppID'] != ""
			//	&& trim($this->amz_settings['ebay_CertID']) != ""
			//) {
				$this->ebayHelper = $this->get_ws_object_new( 'ebay', 'new_helper', array(
					'the_plugin' => $this,
				));
				//:: disabled on 2018-09
				//require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/helpers/ebay.helper.class.php' );
				//if( class_exists('WooZoneLiteEbayHelper') ){
				//	// $this->ebayHelper = new WooZoneLiteEbayHelper( $this );
				//	$this->ebayHelper = WooZoneLiteEbayHelper::getInstance( $this );
				//}
				//:: end disabled on 2018-09
			//}

			//==========================================
			//:: end HELPERS INIT


			// reset current provider to default
			$this->cur_provider = 'amazon';

			// ajax download lightbox
			add_action('wp_ajax_WooZoneLiteDownoadAssetLightbox', array( $this, 'download_asset_lightbox' ));

			// admin ajax action
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/utils/action_admin_ajax.php' );
			new WooZoneLite_ActionAdminAjax( $this );

			// admin ajax action
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'modules/cronjobs/cronjobs.core.php' );
			new WooZoneLiteCronjobs( $this );
			//WooZoneLiteCronjobs::getInstance();

			// frontend class
			if ( $this->is_woocommerce_installed() ) {
				require_once( $this->cfg['paths']['plugin_dir_path'] . 'lib/frontend/frontend.class.php' );
				$this->frontend = WooZoneLiteFrontend::getInstance( $this );
			}

			$is_installed = get_option( $this->alias . "_is_installed" );
			if( $this->is_admin && $is_installed === false ) {
				add_action( 'admin_print_styles', array( $this, 'admin_notice_install_styles' ) );
			}

			// delete attachments when you delete post (product)
			$delete_post_attachments = isset( $this->amz_settings['delete_attachments_at_delete_post'] )
				&& 'yes' == $this->amz_settings['delete_attachments_at_delete_post'] ? true : false;
			if ( $delete_post_attachments ) {
				add_action('before_delete_post', array( $this, 'delete_post_attachments' ));
			}

			// Export Emails Action
			$doExportEmails = isset($_REQUEST['do']) && $_REQUEST['do'] == 'export_emails' ? true : false;
			if( $this->is_admin && $doExportEmails ) {
				// output headers so that the file is downloaded rather than displayed
				header('Content-Type: text/csv; charset=utf-8');
				header('Content-Disposition: attachment; filename=clients_email_'. ( date('d-m-Y_H-i') ) .'.csv');

				// create a file pointer connected to the output stream
				$output = fopen('php://output', 'w');

				// output the column headings
				fputcsv($output, array('Email'));

				$emails = get_option( 'WooZoneLite_clients_email' );
				// loop over the rows, outputting them
				foreach( $emails as $email ) {
					fputcsv($output, array($email));
				}
				die;
			}

			$this->expressions = array(
				'as of' => 'as of',
				'Frequently Bought Together' => 'Frequently Bought Together',
				'Price for all' => 'Price for all',
				'This item' => 'This item',
				'Amazon Customer Reviews' => 'Amazon Customer Reviews',
				'FREE Shipping' => 'FREE Shipping',
				'Details' => 'Details',
				'Loading...' => 'Loading...',
				'not available' => 'not available',
				'available' => 'available',
				'You must check or cancel all amazon shops!' => 'You must check or cancel all amazon shops!',
				'all good' => 'all good',
				'canceled' => 'canceled',
				'checkout done' => 'checkout done',
				'Saving...' => 'Saving...',
				'Closing...' => 'Closing...',
				'Add to cart' => 'Add to cart',
				'Buy Now' => 'Buy Now',
				'Price:' => 'Price:',
				'Additional images:' => 'Additional images:',
				'See larger image' => 'See larger image',
				'Add Products' => 'Add Products',
				'Please first select a product from the left side' => 'Please first select a product from the left side',
				'Chosen Product(s)' => 'Chosen Product(s)',
				'Product prices and availability are accurate as of the date/time indicated and are subject to change. Any price and availability information displayed on [relevant Amazon Site(s), as applicable] at the time of purchase will apply to the purchase of this product.' => 'Product prices and availability are accurate as of the date/time indicated and are subject to change. Any price and availability information displayed on [relevant Amazon Site(s), as applicable] at the time of purchase will apply to the purchase of this product.',
				'Countries availability' => 'Countries availability',
				'not verified yet' => 'not verified yet',
				'is available' => 'is available',
				'not available' => 'not available',
				'Additional Information' => 'Additional Information',
				'Product Synchronization' => 'Product Synchronization',
				'Session Check' => 'Session Check',
				'Coupons available for this offer' => 'Coupons available for this offer',
				'View more coupons' => 'View more coupons',
				'Your coupon' => 'Your coupon',
				'More info' => 'More info',
			);
			$this->translatable_strings();

			//:: WOOCOMMERCE HOOKS /made in 2018-june-19
			// !!! NOTICE: those related to FRONTEND are
			// 		- in /lib/frontend/frontend.class.php
			// also there might be some hooks (related to admin mostly)
			// 		- in /modules/woocustom/init.php
			// 		- in /aa-framework/utils/images.fix.php
			add_filter( "woocommerce_product_class", array( $this, 'try_to_overwrite' ), 10, 2 );
			if( isset( $this->amz_settings['force_disable_images_srcset'] ) && $this->amz_settings['force_disable_images_srcset'] == 'yes' ) {
				add_filter( 'wp_calculate_image_srcset', '__return_false' );
			}

			// if ( isset($_REQUEST['aateam_is_here']) ) {
			// 	$_SESSION['aateam_is_here'] = 'aateam is here!!! ' . (string) $_REQUEST['aateam_is_here'] . ' the end';
			// }
			// if ($_SERVER['REMOTE_ADDR']=='86.126.123.137') {
			// 	$sess_start = -1; //WooZoneLite_session_start();
			// 	var_dump('<pre>_SESSION', $_SESSION, $sess_start, WooZoneLite_session_start(), '</pre>');
			// 	//echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			// }
		}

		public function session_start() {

			$ret = WooZoneLite_session_start();

			$this->wp_ajax_debug( true );

			//!isset($_SESSION['aateam_sess_dbg']) ? $_SESSION['aateam_sess_dbg'] = 0 : $_SESSION['aateam_sess_dbg']++;
			//var_dump('<pre>',$_SESSION['aateam_sess_dbg'],'</pre>');

			return $ret;
		}

		public function wp_ajax_debug( $setit=false ) {
			$rule = wp_doing_ajax() && $setit;

			if ( isset($_SESSION['AATeam_ajax_debug']) && $_SESSION['AATeam_ajax_debug'] ) {
				if ( $rule ) {
					@ini_set( 'display_errors', 1 );
				}
				return true;
			}

			if ( $rule ) {
				@ini_set( 'display_errors', 0 );
			}
			return false;
		}

		public function get_amazon_images_path() {
			return self::$amazon_images_path;
		}
		public function get_ebay_images_path() {
			return self::$ebay_images_path;
		}

		public function activate() {
			add_option('WooZoneLite_do_activation_redirect', true);
			add_option('WooZoneLite_depedencies_is_valid', true);
			add_option('WooZoneLite_depedencies_do_activation_redirect', true);
			$this->plugin_integrity_check( 'all', false );
		}

		public function depedencies_verify() {

			// woocommerce plugin is not installed!
			if ( ! $this->is_woocommerce_installed() ) {
				add_option('WooZoneLite_depedencies_is_valid', true);
				//add_option('WooZoneLite_depedencies_do_activation_redirect', true);
			}

			// plugin was just activated => we'll need to verify depedencies
			// this option is deleted when the depedencies validation is successfull => after that it will not enter in this code block
			if ( get_option('WooZoneLite_depedencies_is_valid', false) ) {

				require_once( $this->cfg['paths']['scripts_dir_path'] . '/plugin-depedencies/plugin_depedencies.php' );
				$this->pluginDepedencies = new aaTeamPluginDepedencies( $this );

				// this option is deleted after first time entering this block code => after that it will not enter in this code block
				if ( get_option('WooZoneLite_depedencies_do_activation_redirect', false) ) {

					// redirect to depedencies page
					add_action('admin_init', array($this->pluginDepedencies, 'depedencies_plugin_redirect'));
					$this->depedencies_verification_invalid();
					return false;
				}

				// verify plugin library depedencies
				$depedenciesStatus = $this->pluginDepedencies->verifyDepedencies();

				// depedencies validation is successfull
				if ( $depedenciesStatus['status'] == 'valid' ) {
					// go to plugin page (maybe license code activation will be necessary)
					add_action('admin_init', array($this->pluginDepedencies, 'depedencies_plugin_redirect_valid'));
				}
				// invalid depedencies validation
				else {
					// create depedencies page
					add_action('init', array( $this->pluginDepedencies, 'initDepedenciesPage' ), 5);
					$this->depedencies_verification_invalid();
					return false;
				}
			}

			return true;
		}

		public function depedencies_verification_invalid() {
			$page = isset($_REQUEST['page']) ? (string) $_REQUEST['page'] : '';

			$cond = preg_match( '/^WooZoneLite(?:.+)/imu', $page );
			if ( false !== $cond && $cond ) {
				//$_REQUEST['section'] = 'depedencies'; $this->load_section();
				header('Location: ' . get_admin_url() . 'admin.php?page=WooZoneLite');
				die;
			}
		}

		public function try_to_overwrite( $product_type )
		{
			$allowed_product_type = array('WC_Product_Simple', 'WC_Product_Variable', 'WC_Product_External', 'WC_Product_Grouped' );

			if( in_array($product_type, $allowed_product_type) ){
				$file_name = '';

				if( $product_type == 'WC_Product_Simple' ){
					$file_name = 'overwrite-simple.php';
					$ret_class = 'WooZoneLiteWcProductModify_Simple';
				}

				if( $product_type == 'WC_Product_External' ){
					$file_name = 'overwrite-external.php';
					$ret_class = 'WooZoneLiteWcProductModify_External';
				}
				elseif( $product_type == 'WC_Product_Grouped' ){
					$file_name = 'overwrite-grouped.php';
					$ret_class = 'WooZoneLiteWcProductModify_Grouped';
				}

				elseif( $product_type == 'WC_Product_Variable' ){
					$file_name = 'overwrite-variable.php';
					$ret_class = 'WooZoneLiteWcProductModify_Variable';
				}

				if( $file_name != '' ){
					require_once(  $this->cfg['paths']['plugin_dir_path'] . "woocommerce-overwrite/" . $file_name );
					return $ret_class;
				}
			}
			//elseif( $product_type == 'WC_Product_Variation' ){
			//	return $product_type;
			//}
			return $product_type;
		}

		public function verify_product_is_amazon( $prod_id, $pms=array() ) {
			$pms = array_replace_recursive( array(
				// if (false) = we only verify that it has a provider, any is ok
				// if (amazon | ebay) = mandatory must belong to this provider
				'verify_provider' 	=> 'amazon',
			), $pms );
			extract( $pms );

			// verify we are in woocommerce product
			// if ( is_object($prod_id) ) {
			// 	$product = $prod_id;
			// }
			// else if( function_exists('wc_get_product') ){
			// 	$product = wc_get_product( $prod_id );
			// }
			// else if( function_exists('get_product') ){
			// 	$product = get_product( $prod_id );
			// }

			// if ( isset($product) && is_object($product) ) {
			// 	$prod_id = 0;
			// 	if ( method_exists( $product, 'get_id' ) ) {
			// 		$prod_id = (int) $product->get_id();
			// 	} else if ( isset($product->id) && (int) $product->id > 0 ) {
			// 		$prod_id = (int) $product->id;
			// 	}

			// [FIX] - 2019-jul-02
			if ( is_object($prod_id) ) {
				$product = $prod_id;

				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
			}

			if ( $prod_id ) {

				// verify is amazon product!
				$asin = WooZoneLite_get_post_meta($prod_id, '_amzASIN', true);

				if ( $asin!==false && strlen($asin) > 0 ) {

					if ( false === $verify_provider ) {
						return true;
					}
					else {
						$provider = $this->prodid_get_provider_by_asin( $asin );
						if ( $provider == $verify_provider ) {
							return true;
						}
					}
				}
				return -1;
			}
			return -2;
		}



		//============================================================
		//== IMPORT/ ADD NEW PRODUCT & UPDATE PRODUCT
		public function importProdDefaultParams( $settings=array() ) {

			$params = array(
				'ws'                    => 'amazon',
				'asin' 					=> '',
				'from_op' 				=> '',
				'operation_id'          => '',

				// true = don't continue add product operation, if there is already an product with the same title
				'stop_at_same_title' 	=> false,

				'import_to_category'    => 'amz',

				'import_images'         => isset($settings["number_of_images"])
					&& (int) $settings["number_of_images"] > 0
					? (int) $settings["number_of_images"] : 2,

				'import_variations'     => isset($settings['product_variation'])
					? $settings['product_variation'] : 'yes_2',

				'spin_at_import'        => isset($settings['spin_at_import'])
					&& ($settings['spin_at_import'] == 'yes') ? true : false,

				'import_attributes'     => isset($settings['item_attribute'])
					&& ($settings['item_attribute'] == 'no') ? false : true,
			);
			return $params;
		}

		// $retProd must be formated through method 'build_product_data' from amz.helper.class.php
		public function addNewProduct( $retProd=array(), $pms=array() )
		{
			$default_pms = array_merge( array(), $this->importProdDefaultParams( $this->amz_settings ) );
			$pms = array_merge( $default_pms, $pms );

			$durationImportStats = array(
				'total' 		=> 0,
				'spin' 			=> 0,
				'attributes' 	=> 0,
				'vars' 			=> 0,
				'nb_vars' 		=> 0,
				'img' 			=> 0,
				'nb_img' 		=> 0,
				'img_dw' 		=> 0,
				'nb_img_dw' 	=> 0,
			);

			$durationQueue = array(); // Duration Queue
			$this->timer_start(); // Start Timer

			//---------------------
			//:: status messages
			$this->opStatusMsgInit(array(
				'operation_id'  => $pms['operation_id'],
				'operation'     => 'add_prod',
			));

			$ret = array(
				'status' 		=> 'invalid',
				'msg' 			=> '',
				'msg_arr' 		=> array(),
				'insert_id' 	=> 0,
				'nb_remote_err' => 0,
				'duration_total' => 0,
			);
			$msg = array();

			//---------------------
			//:: empty amazon response?
			if ( count($retProd) == 0 ) {
				// status messages
				$msg[] = $this->opStatusMsgSet(array(
					'msg'       => sprintf( 'empty product array from %s!', $pms['ws']),
					'duration'  => $this->timer_end(), // End Timer
				));

				$ret = array_replace_recursive( $ret, array(
					'msg' 	=> implode('<br />', $msg),
					'msg_arr' => $msg,
				));
				return $ret;
			}

			$default_import = !isset($this->amz_settings["default_import"])
				|| ($this->amz_settings["default_import"] == 'publish')
				? 'publish' : 'draft';
			$default_import = strtolower($default_import);

			//---------------------
			//:: verify if : amazon zero price product!
			$price_zero_import = isset($this->amz_settings["import_price_zero_products"])
				&& $this->amz_settings["import_price_zero_products"] == 'yes'
				? true : false;

			if ( ! $price_zero_import && $this->get_ws_object( $pms['ws'] )->is_product_price_zero( $retProd ) ) {
				// status messages
				$msg[] = $this->opStatusMsgSet(array(
					'msg'       => 'price is zero, so it is skipped!',
					'duration'  => $this->timer_end(), // End Timer
				));

				$ret = array_replace_recursive( $ret, array(
					'msg' 	=> implode('<br />', $msg),
					'msg_arr' => $msg,
				));
				return $ret;
			}

			//---------------------
			//:: verify if : amazon missing offerlistingid product!
			if ( ! $this->import_product_offerlistingid_missing && ( 'amazon' == $pms['ws'] ) ) {

				$prod_has_offerlistingid = $this->get_ws_object( 'amazon' )->productHasOfferlistingid( array(
					'verify_variations' => true,
					'thisProd' 	=> $retProd,
					'post_id' 	=> 0,
				));
				//var_dump('<pre>', $prod_has_offerlistingid , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				if ( ! $prod_has_offerlistingid ) {
					// status messages
					$msg[] = $this->opStatusMsgSet(array(
						'msg'       => 'offerListingId is missing, so it is skipped!',
						'duration'  => $this->timer_end(), // End Timer
					));

					$ret = array_replace_recursive( $ret, array(
						'msg' 	=> implode('<br />', $msg),
						'msg_arr' => $msg,
					));
					return $ret;
				}
			}

			//---------------------
			//:: verify if : merchant is "only_amazon" and product has amazon among its sellers
			$merchant_is_amazon_only_import = isset($this->amz_settings["merchant_setup"])
				&& 'only_amazon' == $this->amz_settings["merchant_setup"]
				? true : false;

			if ( $merchant_is_amazon_only_import
				&& ! $this->get_ws_object( 'amazon' )->product_has_amazon_seller( $retProd )
				&& ( 'amazon' == $pms['ws'] )
			) {
				// status messages
				$msg[] = $this->opStatusMsgSet(array(
					'msg'       => 'merchant setup is "only_amazon" and the product doesn\'t have amazon among its sellers!',
					'duration'  => $this->timer_end(), // End Timer
				));

				$ret = array_replace_recursive( $ret, array(
					'msg' 	=> implode('<br />', $msg),
					'msg_arr' => $msg,
				));
				return $ret;
			}

			//---------------------
			//:: build post data & import it if not exists in database
			$product_desc = $this->product_build_desc( array_merge_recursive($retProd, array(
				'ws' 	=> $pms['ws'],
			)));
			$excerpt = isset($product_desc['short']) ? $product_desc['short'] : '';
			$desc = isset($product_desc['desc']) ? $product_desc['desc'] : '';

			$args = array(
				'post_title'    => $retProd['Title'],
				'post_status'   => $default_import,
				'post_content'  => $desc,
				'post_excerpt'  => $excerpt,
				'post_type'     => 'product',
				'menu_order'    => 0,
				'post_author'   => 1, //get_current_user_id()
			);

			$existProduct = amzStore_bulk_wp_exist_post_by_args($args);
			$metaPrefix = 'amzStore_product_';

			// check if post exists
			if ( $existProduct === false){
				$lastId = wp_insert_post($args);

				$duration = $this->timer_end(); // End Timer
				// status messages
				$msg[] = $this->opStatusMsgSet(array(
					'status'    => 'valid',
					'msg'       => 'product inserted with ID: ' . $lastId,
					'duration'  => $duration,
				));
			}
			else {
				$lastId = $existProduct['ID'];

				$duration = $this->timer_end(); // End Timer
				// status messages
				$msg[] = $this->opStatusMsgSet(array(
					'status'    => 'valid',
					'msg'       => 'a product with the same title already exists with ID: ' . $lastId,
					'duration'  => $duration,
				));

				if ( $pms['stop_at_same_title'] ) {
					$ret = array_replace_recursive( $ret, array(
						'msg' 	=> implode('<br />', $msg),
						'msg_arr' => $msg,
					));
					return $ret;
				}
			}

			apply_filters( 'WooZoneLite_after_product_import', $lastId );

			$durationQueue[] = $this->timer_end(); // End Timer
			$this->timer_start(); // Start Timer

			//---------------------
			//:: spin post/product content!
			if ( $pms['spin_at_import'] ) {

				$replacements_nb = 10;
				if ( isset($this->amz_settings['spin_max_replacements']) ) {
					$replacements_nb = (int) $this->amz_settings['spin_max_replacements'];
				}

				$this->spin_content(array(
					'prodID'        => $lastId,
					'replacements'  => $replacements_nb
				));

				$duration = $this->timer_end(); // End Timer
				$this->timer_start(); // Start Timer

				// status messages
				$msg[] = $this->opStatusMsgSet(array(
					'status'    => 'valid',
					'msg'       => 'spin content done',
					'duration'  => $duration,
				));

				// add last import report
				$this->add_last_imports('last_import_spin', array('duration' => $duration)); // End Timer & Add Report

				$durationImportStats['spin'] = ceil( $duration );
			}

			//---------------------
			//:: import images - just put images paths to assets table
			if ( ( $pms['import_images'] === 'all' ) || ( (int) $pms['import_images'] > 0 ) ) {

				// get product images
				$setImagesStatus = $this->get_ws_object( $pms['ws'] )->set_product_images(
					$retProd,
					$lastId,
					0,
					$pms['import_images']
				);

				$duration = $this->timer_end(); // End Timer
				$durationQueue[] = $duration; // End Timer
				$this->timer_start(); // Start Timer

				// status messages
				$msg[] = $this->opStatusMsgSet(array(
					'status'    => 'valid',
					'msg'       => $setImagesStatus['msg'],
					'duration'  => $duration,
				));
			}

			$durationQueue[] = $this->timer_end(); // End Timer
			$this->timer_start(); // Start Timer

			//---------------------
			//:: import to category
			if ( $pms['import_to_category'] != 'amz' ) {

				$tocateg = $pms['import_to_category'];

				$final_categs = array();
				$final_categs[] = (int) $tocateg;

				$ancestors = get_ancestors( (int) $tocateg, 'product_cat' );

				if( count( $ancestors ) > 0 && is_array( $ancestors ) && $ancestors != '' ) {
					$final_categs = array_merge( $final_categs, $ancestors );
				}

				// set the post category
				wp_set_object_terms( $lastId, $final_categs, 'product_cat', true);

			}
			else {
				$tocateg = $retProd['BrowseNodes'];

				// setup product categories
				$createdCats = $this->get_ws_object( $pms['ws'] )->set_product_categories( $tocateg );

				// Assign the post on the categories created
				wp_set_post_terms( $lastId,  $createdCats, 'product_cat' );
			}

			//---------------------
			//:: product tags
			if ( isset($retProd['Tags']) && !empty($retProd['Tags']) ) {
				// setup product tags
				$createdTags = $this->get_ws_object( $pms['ws'] )->set_product_tags( $retProd['Tags'] );

				// Assign the post on the categories created
				if ( !empty($createdTags) ) {
					wp_set_post_terms( $lastId,  $createdTags, 'product_tag' );
				}
			}

			$duration = $this->timer_end(); // End Timer
			$durationQueue[] = $duration; // End Timer
			$this->timer_start(); // Start Timer

			// status messages
			$msg[] = $this->opStatusMsgSet(array(
				'status'    => 'valid',
				'msg'       => 'set product categories',
				'duration'  => $duration,
			));

			//---------------------
			//:: import attributes
			if ( $pms['import_attributes'] ) {
				if ( count($retProd['ItemAttributes']) > 0 ) {
					$this->timer_start(); // Start Timer
				}

				// add product attributes
				$this->get_ws_object( $pms['ws'] )->set_woocommerce_attributes( $retProd['ItemAttributes'], $lastId );

				if ( count($retProd['ItemAttributes']) > 0 ) {
					$duration = $this->timer_end(); // End Timer
					$this->timer_start(); // Start Timer

					// status messages
					$msg[] = $this->opStatusMsgSet(array(
						'status'    => 'valid',
						'msg'       => 'import attributes',
						'duration'  => $duration,
					));

					// add last import report
					$this->add_last_imports('last_import_attributes', array(
						'duration'      => $duration,
					)); // End Timer & Add Report

					$durationImportStats['attributes'] = ceil( $duration );
				}
			}

			//---------------------
			//:: than update the post metas
			$this->get_ws_object( $pms['ws'] )->set_product_meta_options( $retProd, $lastId, false );

			$duration = $this->timer_end(); // End Timer
			$durationQueue[] = $duration; // End Timer
			$this->timer_start(); // Start Timer

			// status messages
			$msg[] = $this->opStatusMsgSet(array(
				'status'    => 'valid',
				'msg'       => 'set product metas',
				'duration'  => $duration,
			));

			//---------------------
			//:: set the product price
			$this->get_ws_object( $pms['ws'] )->get_product_price(
				$retProd,
				$lastId,
				array( 'do_update' => true )
			);

			$duration = $this->timer_end(); // End Timer
			$durationQueue[] = $duration; // End Timer
			$this->timer_start(); // Start Timer

			// status messages
			$msg[] = $this->opStatusMsgSet(array(
				'status'    => 'valid',
				'msg'       => 'product price update',
				'duration'  => $duration,
			));

			//---------------------
			//:: IMPORT PRODUCT VARIATIONS
			if ( $pms['import_variations'] != 'no' && in_array($pms['ws'], $this->providers_allow_variations()) ) {
				$this->timer_start(); // Start Timer

				// current message
				$current_msg = $this->opStatusMsg['msg'];

				$setVariationsStatus = $this->get_ws_object( $pms['ws'] )->set_woocommerce_variations(
					$retProd,
					$lastId,
					array(
						'var_max_allowed' 	=> $this->convert_variation_number_to_number( $pms['import_variations'] ),
					)
				);

				// don't add all variation adding texts to the final message!
				$this->opStatusMsg['msg'] = $current_msg;

				$duration = $this->timer_end(); // End Timer
				$this->timer_start(); // Start Timer

				// status messages
				$msg[] = $this->opStatusMsgSet(array(
					'status'    => 'valid',
					'msg'       => $setVariationsStatus['msg'],
					'duration'  => $duration,
				));

				// add last import report
				// ...done in amazon helper file

				if ( $setVariationsStatus['nb_items'] ) {
					$durationImportStats['vars'] = ceil( $duration );
					$durationImportStats['nb_vars'] = $setVariationsStatus['nb_items'];
				}
			}

			//---------------------
			//:: set remote images
			//if ( $this->is_remote_images && ( 'amazon' == $pms['ws'] ) ) {
			$nb_remote_err = 0;
			if ( $this->is_remote_images ) {
				$setRemoteImgStatus = $this->get_ws_object( 'generic' )->build_remote_images( $lastId );

				$nb_remote_err = $setRemoteImgStatus['nb_remote_err'];

				$duration = $this->timer_end(); // End Timer
				$this->timer_start(); // Start Timer

				// status messages
				$msg[] = $this->opStatusMsgSet(array(
					'status'    => 'valid',
					'msg'       => $setRemoteImgStatus['msg'],
					'duration'  => $duration,
				));

				if ( ! $setRemoteImgStatus['nb_remote_err'] && $setRemoteImgStatus['nb_parsed'] ) {
					$durationImportStats['img'] = ceil( $duration );
					$durationImportStats['nb_img'] = $setRemoteImgStatus['nb_parsed'];
				}
			}

			//---------------------
			//:: Set the product type
			if ( 'amazon' == $pms['ws'] ) {

				$this->update_products_type( array(
					'products' => array( $lastId )
				));

				$duration = $this->timer_end(); // End Timer
				$durationQueue[] = $duration; // End Timer
				$this->timer_start(); // Start Timer

				// status messages
				$msg[] = $this->opStatusMsgSet(array(
					'status'    => 'valid',
					'msg'       => 'update products type',
					'duration'  => $duration,
				));
			}
			else if ( 'ebay' == $pms['ws'] ) {

				$this->u->product_force_external( $lastId );
			}

			//---------------------
			//:: FINAL step

			$duration = $this->timer_end(); // End Timer
			$durationQueue[] = $duration; // End Timer
			$duration = round( array_sum($durationQueue), 4 ); // End Timer

			$duration_total = $duration;
			$duration_total += $durationImportStats['spin'];
			$duration_total += $durationImportStats['attributes'];
			$duration_total += $durationImportStats['vars'];
			$duration_total += $durationImportStats['img'];
			//$duration_total += $durationImportStats['img_dw'];
			$durationImportStats['total'] = $duration_total;
			$durationImportStats['total'] = ceil( $durationImportStats['total'] );
			//var_dump('<pre>',$duration_product, $duration_total ,'</pre>');

			// status messages
			$msg[] = $this->opStatusMsgSet(array(
				'status'    => 'valid',
				'msg'       => 'Finished. TOTAL DURATION (doesn\'t contain duration to dowload product images - if you don\'t use remote images)',
				'duration'  => $duration_total,
				'end'       => true,
			));

			// here in 'last_product' is duration without spin, attributes, variations, remote images (and ofcourse without downloaded images)
			// add last import report
			$this->add_last_imports('last_product', array(
				'duration'      => $duration,
			)); // End Timer & Add Report

			$ret = array_replace_recursive( $ret, array(
				'status' 	=> 'valid',
				'msg' 		=> implode('<br />', $msg),
				'msg_arr' => $msg,
				'insert_id' => $lastId,
				'nb_remote_err' => $nb_remote_err,
				'duration_total' => $duration_total,
			));

			if ( $lastId ) {
				update_post_meta( $lastId, '_amzaff_import_status', $ret );

				$from_op_ = explode('#', $pms['from_op']);

				$db_calc = $this->import_stats_db_calc( array(
					'wp_posts',
					'wp_postmeta',
					'wp_terms',
					'nb_prods',
					'nb_attrs',
					'nb_images',
				));

				$this->import_stats_add_row( array(
					'post_id' 				=> $lastId,
					'post_title' 			=> $retProd['Title'],
					'asin' 					=> $pms['asin'],
					'provider' 				=> $pms['ws'],
					'country' 				=> $retProd['country'],
					'from_op'				=> $pms['from_op'],
					'from_op_p1' 			=> is_array($from_op_) && isset($from_op_[0]) ? $from_op_[0] : '',
					'from_op_p2' 			=> is_array($from_op_) && isset($from_op_[1]) ? $from_op_[1] : '',
					'import_status_msg' 	=> $ret,

					'duration_spin' 		=> $durationImportStats['spin'],
					'duration_attributes' 	=> $durationImportStats['attributes'],
					'duration_vars' 		=> $durationImportStats['vars'],
					'duration_nb_vars' 		=> $durationImportStats['nb_vars'],
					'duration_img' 			=> $durationImportStats['img'],
					'duration_nb_img' 		=> $durationImportStats['nb_img'],
					'duration_img_dw' 		=> $durationImportStats['img_dw'],
					'duration_nb_img_dw'	=> $durationImportStats['nb_img_dw'],
					'duration_product' 		=> $durationImportStats['total'],
					'db_calc' 				=> isset($db_calc) ? $db_calc : null,
				));
			}
			return $ret;
		}

		// $retProd must be formated through method 'build_product_data' from amz.helper.class.php
		public function updateWooProduct( $retProd=array(), $pms=array() )
		{
			$pms = array_replace_recursive(array(
				'provider' 		=> 'amazon',
				'rules' 		=> array(),

				'post_id' 		=> 0,
				'post_asin'		=> '',

				// array with post_title, post_content, post_excerpt or get_post( POSTID, ARRAY_A )
				'current_post' 	=> false,

				'parent_id' 	=> false, // integer or false,

				// array with post_title, post_content, post_excerpt or get_post( POSTID, ARRAY_A )
				'parent_post' 	=> false,

				// the return of method 'product_find_new_variations'
				'product_vars' 	=> array(),
			), $pms);
			extract( $pms );

			//---------------------
			//:: status messages
			$ret = array(
				'status' 	=> 'notfound',
				'msg' 		=> 'update product - init',
				'rules'		=> array(),
				'updated' 	=> array(),
			);
			$stats = array();

			//---------------------
			//:: empty amazon response?
			if ( empty($retProd) || ! is_array($retProd) ) {
				$ret = array_replace_recursive( $ret, array(
					'status' => 'notfound',
					'msg' 	=> sprintf( 'provider %s: update product - empty product array!', $provider ),
				));
				return $ret;
			}

			//---------------------
			//:: some inits
			$show_short_description = isset($this->amz_settings['show_short_description'])
				? $this->amz_settings['show_short_description'] : 'yes';
			$is_short_desc = isset($rules['short_desc']) && $rules['short_desc'] == true
				&& $show_short_description == 'yes';

			$opProductType = $this->get_product_type_by_apiresponse( $retProd, $provider );
			extract( $opProductType ); //is_variable, is_variation_child, nb_variations, product_type

			//---------------------
			//:: verify if : amazon missing offerlistingid product!
			if ( $this->product_offerlistingid_missing_delete && ( 'amazon' == $provider ) ) {

				$verifyOfferPms = array(
					'verify_variations' => false,
					'thisProd' => $retProd,
					'post_id' => 0,
				);

				// variation child
				if ( $is_variation_child ) {
				}
				// variable product - parent
				else if ( $is_variable ) {
					$verifyOfferPms = array_replace_recursive( $verifyOfferPms, array(
						'verify_variations' => true,
					));
				}
				// simple product
				else {
				}

				$prod_has_offerlistingid = $this->get_ws_object( $this->cur_provider )->productHasOfferlistingid( $verifyOfferPms );
				//var_dump('<pre>', $prod_has_offerlistingid , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				if ( ! $prod_has_offerlistingid ) {
					$ret = array_replace_recursive( $ret, array(
						'status' => 'notfound',
						'msg' 	=> sprintf( 'provider %s: offerListingId is missing, so it is ( removed | moved to trash )!', $provider ),
					));
					return $ret;
				}
			}

			//---------------------
			//:: configuration & get product meta
			$_sync_rules = array();
			$sync_rules = array_keys( $this->get_product_sync_rules() );
			foreach ( $sync_rules as $sync_rule ) {

				$_sync_rules["$sync_rule"] = false;
				if ( isset($rules["$sync_rule"]) && $rules["$sync_rule"] ) {
					$_sync_rules["$sync_rule"] = true;
				}

				if ( 'short_desc' == $sync_rule && isset($is_short_desc) ) {
					$_sync_rules["short_desc"] = $is_short_desc;
				}
			}

			// filter rules that can be applied for ebay
			if ( 'ebay' == $provider ) {
				foreach ( $_sync_rules as $kk => $vv ) {
					// i've added short_desc only because some parts of the bellow code depends on it (but it's not supported by ebay)
					if ( ! in_array($kk, array(
						'price', 'title', 'url', 'desc', 'sku', 'new_variations', 'short_desc'
					)) ) {
						$_sync_rules["$kk"] = false;
					}
					if ( $is_variation_child && in_array($kk, array('desc', 'short_desc')) ) {
						$_sync_rules["$kk"] = false;
					}
				}
				//var_dump('<pre>', $retProd, $is_variation_child, $_sync_rules , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			}
			// filter rules for amazon new api
			else if ( 'amazon' == $provider && 'newapi' === $this->amzapi ) {
				foreach ( $_sync_rules as $kk => $vv ) {
					if ( ! in_array($kk, array(
						'price', 'title', 'url', 'new_variations', 'short_desc'
					)) ) {
						$_sync_rules["$kk"] = false;
					}
					if ( $is_variation_child && in_array($kk, array('desc', 'short_desc')) ) {
						$_sync_rules["$kk"] = false;
					}
				}

				$can_sync_variations = $this->can_sync_variations( $retProd, $provider );
				if ( ! $can_sync_variations && isset($_sync_rules['new_variations']) ) {
					$_sync_rules['new_variations'] = false;
				}
				//var_dump('<pre>', $retProd, $is_variation_child, $_sync_rules , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			}

			// short OR full description - at least one of them
			$hasDesc = false;
			if ( $_sync_rules["short_desc"] || $_sync_rules["desc"] ) {
				$hasDesc = true;
			}

			//---------------------
			//:: get post meta
			$post_metas = array();
			$what_metas = array( '_amzASIN', '_amzaff_prodid', '_amzaff_desc_used', '_sku', '_product_url', 'amzaff_woo_product_tabs', '_sales_rank', '_price' );
			$post_metas = $post_metas + $this->get_product_metas( $post_id, $what_metas, array('remove_prefix' => '') );

			//---------------------
			//:: other inits
			$need_post_maininfo = $_sync_rules['title'] || $hasDesc;

			$is_valid_current_post = is_array($current_post)
				&& isset($current_post['post_title'], $current_post['post_parent'], $current_post['post_content']);

			if ( $need_post_maininfo && ! $is_valid_current_post ) {
				$current_post = get_post( $post_id, ARRAY_A );
			}

			// full & short description need info
			if ( $hasDesc ) {

				if ( empty($post_asin) ) {
					$post_asin = isset($post_metas['_amzASIN'])
						? $post_metas['_amzASIN'] : get_post_meta( $post_id, '_amzASIN', true );
					$post_asin = !empty($post_asin) ? (string) $post_asin : '';
				}

				if ( ! empty($post_asin) ) {
					$post_asin = $this->prodid_set($post_asin, $provider, 'add');
				}

				if ( $parent_id === false ) {
					if ( ! $is_valid_current_post ) {
						$current_post = get_post( $post_id, ARRAY_A );
					}
					$parent_id = isset($current_post['post_parent']) ? $current_post['post_parent'] : 0;
				}

				// is variation child?
				if ( $parent_id ) {

					$is_valid_parent_post = is_array($parent_post)
						&& isset($parent_post['post_title'], $parent_post['post_parent'], $parent_post['post_content']);

					if ( ! $is_valid_parent_post ) {
						$parent_post = get_post( $parent_id, ARRAY_A );
					}

					$retProd = array_merge_recursive($retProd, array(
						'__parent_asin'		=> isset($retProd['ParentASIN']) ? $retProd['ParentASIN'] : '',
						'__parent_content'	=> isset($parent_post['post_content']) ? $parent_post['post_content'] : '',
					));
				}

				$retProd = array_merge_recursive($retProd, array(
					'ws' 				=> $provider,
				));

				$product_desc = $this->product_build_desc($retProd);
				$excerpt = isset($product_desc['short']) ? $product_desc['short'] : '';
				$desc = isset($product_desc['desc']) ? $product_desc['desc'] : '';
			}

			//---------------------
			//:: main update body
			$args_update = array();
			$args_update['ID'] = $post_id;

			//---------------------
			//:: TITLE
			if ( $_sync_rules["title"] ) {

				$args_update['post_title'] = $retProd['Title'];

				$opGetRule = $this->_updateWooProduct_get_rule_stats(
					'title',
					$args_update['post_title'],
					isset($current_post['post_title']) ? $current_post['post_title'] : null,
					array( 'rules' => $_sync_rules )
				);
				$stats = $stats + $opGetRule;
			}

			//---------------------
			//:: SHORT DESCRIPTION
			// short description
			if ( $_sync_rules["short_desc"] ) {

				$args_update['post_excerpt'] = $excerpt;

				$opGetRule = $this->_updateWooProduct_get_rule_stats(
					'short_desc',
					$args_update['post_excerpt'],
					isset($current_post['post_excerpt']) ? $current_post['post_excerpt'] : null,
					array( 'rules' => $_sync_rules )
				);
				$stats = $stats + $opGetRule;
			}

			//---------------------
			//:: FULL DESCRIPTION
			// full description
			if ( $_sync_rules["desc"] ) {

				$args_update['post_content'] = $desc;

				$opGetRule = $this->_updateWooProduct_get_rule_stats(
					'desc',
					$args_update['post_content'],
					isset($current_post['post_content']) ? $current_post['post_content'] : null,
					array( 'rules' => $_sync_rules )
				);
				$stats = $stats + $opGetRule;
			}

			if ( $hasDesc && ( 'amazon' == $provider ) ) {
				$desc_used = array();
				if ( $parent_id ) { // is variation child?
					$desc_used = get_post_meta( $parent_id, '_amzaff_desc_used', true );
				}
				else if ( $is_variable ) { // variable product
					$desc_used = isset($post_metas['_amzaff_desc_used'])
						? $post_metas['_amzaff_desc_used'] : get_post_meta( $post_id, '_amzaff_desc_used', true );
				}
				$desc_used = !empty($desc_used) && is_array($desc_used) && isset($desc_used['child_asin']) ? $desc_used : array();

				//---------------------
				// is variation child?
				if ( $parent_id ) {
					$doit = false;
					if ( empty($desc_used) || empty($desc_used['child_asin']) ) {
						$doit = true;
					}
					else if ( $post_asin == $desc_used['child_asin'] ) {
						$doit = true;
					}

					if ( $doit ) {
						$desc_used = array(
							'child_asin'			=> $post_asin,
							'date_done'				=> date("Y-m-d H:i:s"), // only for debug purpose
						);

						if ( !empty($desc_used) && isset($desc_used['child_asin']) ) {
							update_post_meta( $parent_id, '_amzaff_desc_used', $desc_used );
						}

						//---------------------
						// update parent variation
						$parent_update = array();
						$parent_update['ID'] = $parent_id;

						if ( $_sync_rules["short_desc"] ) {
							$parent_update['post_excerpt'] = $excerpt;
						}
						if ( $_sync_rules["desc"] ) {
							$parent_update['post_content'] = $desc;
						}

						if ( isset($parent_update['post_content']) || isset($parent_update['post_excerpt']) ) {
							wp_update_post( $parent_update );
						}
					}
				}
				//---------------------
				// parent variable product OR non-variable product
				else if ( $is_variable ) {
					$variations = isset($retProd['Variations']['Item']) ? $retProd['Variations']['Item'] : array();
					$found = false;
					foreach ( $variations as $variation ) {
						$asin = isset($variation['ASIN']) ? $variation['ASIN'] : '';
						//var_dump('<pre>',$asin, $desc_used['child_asin'],'</pre>');
						if ( isset($desc_used['child_asin']) && ( $asin == $desc_used['child_asin'] ) ) {
							$found = true;
						}
					}

					// variation child not found anymore => next sync will use another variation child to update desc
					if ( ! $found ) {
						$desc_used = array(
							'child_asin'			=> '',
							'date_done'				=> date("Y-m-d H:i:s"), // only for debug purpose
						);
					}

					$__post_content = isset($args_update['post_excerpt']) ? $args_update['post_excerpt'] : '';
					$__post_content = trim( $__post_content );

					if ( $__post_content == '' || $found ) { // is empty => don't try to update
						if ( isset($args_update['post_excerpt']) ) {
							unset( $args_update['post_excerpt'] );
						}
					}

					$__post_content = isset($args_update['post_content']) ? $args_update['post_content'] : '';
					$__post_content = $this->product_clean_desc( $__post_content );

					if ( $__post_content == '' || $found ) { // is empty => don't try to update
						if ( isset($args_update['post_content']) ) {
							unset( $args_update['post_content'] );
						}
						if ( !empty($desc_used) && isset($desc_used['child_asin']) ) {
							update_post_meta( $post_id, '_amzaff_desc_used', $desc_used );
						}
					}
				}
			}

			//---------------------
			//:: UPDATE POST - posts table
			//var_dump('<pre>', $args_update, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			// update the post if needed
			if ( count($args_update) > 1 ) { // because ID is allways the same!
				wp_update_post( $args_update );
			}

			//---------------------
			//:: SKU - postmeta table
			// than update the metapost
			if ( $_sync_rules["sku"] && isset($retProd['SKU']) ) {

				$old_meta = isset($post_metas['_sku'])
					? $post_metas['_sku'] : get_post_meta( $post_id, '_sku', true );

				update_post_meta($post_id, '_sku', $retProd['SKU']);

				$opGetRule = $this->_updateWooProduct_get_rule_stats(
					'sku',
					$retProd['SKU'],
					$old_meta,
					array( 'rules' => $_sync_rules )
				);
				$stats = $stats + $opGetRule;
			}

			//---------------------
			//:: PRODUCT URL - postmeta table
			if ( $_sync_rules["url"] ) {

				$old_meta = isset($post_metas['_product_url'])
					? $post_metas['_product_url'] : get_post_meta( $post_id, '_product_url', true );

				$new_url = home_url('/?redirectAmzASIN=' . $retProd['ASIN'] );

				if ( 'amazon' == $provider ) {

					$new_url = home_url(sprintf(
						'/?redirectAmzASIN=%s&redirect_prodid=%s',
						$this->prodid_set($retProd['ASIN'], $provider, 'sub'),
						$this->prodid_set($retProd['ASIN'], $provider, 'add')
					));
					if ( isset($retProd['DetailPageURL']) && ! empty($retProd['DetailPageURL']) ) {
						update_post_meta($post_id, '_amzaff_product_url', $retProd['DetailPageURL']);
						//update_post_meta($post_id, '_aiowaff_product_url', $retProd['DetailPageURL']);
					}
				}
				else if ( in_array($provider, array('alibaba', 'envato', 'ebay')) ) {

					$new_url = home_url(sprintf(
						'/?redirect_prodid=%s',
						$this->prodid_set($retProd['ASIN'], $provider, 'add')
					));
					if ( isset($retProd['DetailPageURL']) ) {
						update_post_meta($post_id, '_amzaff_product_url', $retProd['DetailPageURL']);
						//update_post_meta($post_id, '_aiowaff_product_url', $retProd['DetailPageURL']);
						//if ( 'ebay' == $provider ) {
						//	update_post_meta($post_id, '_wwcEbyAff_product_url', $retProd['DetailPageURL']);
						//}
					}
				}

				update_post_meta($post_id, '_product_url', $new_url);

				$opGetRule = $this->_updateWooProduct_get_rule_stats(
					'url',
					$new_url,
					$old_meta,
					array( 'rules' => $_sync_rules )
				);
				$stats = $stats + $opGetRule;
			}

			//---------------------
			//:: REVIEWS - postmeta table
			if ( $_sync_rules["reviews"] ) {
				if ( isset($retProd['CustomerReviewsURL']) && $retProd['CustomerReviewsURL'] != "" ) {

					$old_meta = isset($post_metas['amzaff_woo_product_tabs'])
						? $post_metas['amzaff_woo_product_tabs'] : get_post_meta( $post_id, 'amzaff_woo_product_tabs', true );

					$tab_data = array();
					$tab_data[] = array(
						'id' => 'amzAff-customer-review',
						'content' => '<iframe src="' . $retProd['CustomerReviewsURL'] . '" width="100%" height="450" frameborder="0"></iframe>'
					);
					//var_dump( $retProd, $tab_data );

					update_post_meta($post_id, 'amzaff_woo_product_tabs', $tab_data);

					$opGetRule = $this->_updateWooProduct_get_rule_stats(
						'reviews',
						maybe_serialize( $tab_data ),
						maybe_serialize( $old_meta ),
						array( 'rules' => $_sync_rules )
					);
					$stats = $stats + $opGetRule;
				}
			}

			//---------------------
			//:: SALES RANK - postmeta table
			if ( $_sync_rules["sales_rank"] && isset($retProd['SalesRank']) ) {

				$old_meta = isset($post_metas['_sales_rank'])
					? $post_metas['_sales_rank'] : get_post_meta( $post_id, '_sales_rank', true );

				update_post_meta($post_id, '_sales_rank', $retProd['SalesRank']);

				$opGetRule = $this->_updateWooProduct_get_rule_stats(
					'sales_rank',
					$retProd['SalesRank'],
					$old_meta,
					array( 'rules' => $_sync_rules )
				);
				$stats = $stats + $opGetRule;
			}

			//---------------------
			//:: PRICE - postmeta table
			if ( $_sync_rules["price"] ) {

				$old_meta = isset($post_metas['_price'])
					? $post_metas['_price'] : get_post_meta( $post_id, '_price', true );

				//if ($is_variation_child ) {
				//	var_dump('<pre>', $post_metas, $retProd , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				//}

				// set the product price
				$product_price = $this->get_ws_object( $provider )->get_product_price(
					$retProd,
					$post_id,
					array( 'do_update' => true )
				);

				$opGetRule = $this->_updateWooProduct_get_rule_stats(
					'price',
					$product_price['_price'],
					$old_meta,
					array( 'rules' => $_sync_rules )
				);
				$stats = $stats + $opGetRule;
			}

			//---------------------
			//:: NEW VARIATIONS (VARIABLE PARENT PRODUCT)
			// variable products only: only parent
			// also we update the product type here & remove woocommerce transients
			$is_ptupdated = false;
			if ( $_sync_rules["new_variations"] && $is_variable ) {

				if ( ! is_array($product_vars) || ! isset($product_vars['status']) ) {
					$product_vars = $this->product_find_new_variations( $retProd, array(
						'only_new' 		=> false,
						'product_id' 	=> $post_id,
						'provider' 		=> $provider,
					));
				}
				if ( is_array($product_vars) ) {
					$product_vars['provider'] = $provider;
				}
				//var_dump('<pre>', $product_vars , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				$product_addvars = $this->product_add_new_variations( $post_id, $product_vars );
				$is_ptupdated = true;

				$opGetRule = $this->_updateWooProduct_get_rule_stats(
					'new_variations',
					$product_addvars['new_added'],
					null,
					array( 'rules' => $_sync_rules, 'msg' => $product_addvars['msg'] )
				);
				$stats = $stats + $opGetRule;
			}

			//---------------------
			// variable products only: parent or child
			// product type not updated or woocommerce transients not removed above
			if ( ! $is_ptupdated ) {

				// parent variation
				$_idprod = 0;
				if ( $is_variable ) {
					$_idprod = $post_id;
				}

				// variation child
				if ( ! $_idprod && $is_variation_child ) {
					if ( ! isset($parent_id) || $parent_id === false ) {
						$current_post = get_post( $post_id, ARRAY_A );
						$parent_id = isset($current_post['post_parent']) ? $current_post['post_parent'] : 0;
					}
					$_idprod = $parent_id;
				}

				if ( 'amazon' == $provider ) {
					// parent variation | variation child
					if ( $_idprod ) {
						delete_transient( "wc_product_children_$_idprod" );
						delete_transient( "wc_var_prices_$_idprod" );

						// Set the product type
						$this->update_products_type( array(
							'products' => array( $_idprod )
						));
					}
				}
				else if ( 'ebay' == $provider ) {
					if ( $_idprod ) {
						$this->u->product_force_external( $_idprod );
					}
				}
			}

			// any stats changed?
			$status = 'notupdated';
			$updated = array();
			foreach ( $stats as $rule => $ruleinfo ) {
				if ( 'yes' == $ruleinfo['status'] ) {
					$status = 'updated';
					//break;
					$updated[] = $rule;
				}
			}

			$ret = array_replace_recursive( $ret, array(
				'status' 	=> $status,
				'msg' 		=> 'update product - parsing rules finished',
				'rules' 	=> $stats,
				'updated' 	=> $updated,
			));
			return $ret;
		}

		public function _updateWooProduct_get_rule_stats( $rule, $new, $old=null, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'rules' 			=> array(),
				'rules_toverify' 	=> '',
			), $pms);
			extract( $pms );

			//$rules_toverify = explode(',', $rules_toverify);
			//$rules_toverify = array_map('trim', $rules_toverify);
			//$rules_toverify = array_unique( array_filter( $rules_toverify ) );

			$stats = array();
			$stats["$rule"] = array(
				'code' 		=> '',

				// def = no verification made | no = same info, so no update | yes = info was updated with new one
				// if ( rule is new_variations) => (int) number of new variations added
				'status' 	=> 'def',
			);

			if ( 'new_variations' == $rule ) {
				$stats["$rule"]['status'] = $new ? 'yes' : 'no';
				$stats["$rule"]['new_added'] = $new;
				if ( isset($msg) ) {
					$stats["$rule"]['msg'] = $msg;
				}
				return $stats;
			}

			$code_amz = md5( $new );
			$stats["$rule"]['code'] = $code_amz;

			//if ( in_array( $rule, $rules_toverify ) && ! is_null($old) ) {
				$code_old = md5( $old );
				$stats["$rule"]['status'] = ( $code_amz == $code_old ? 'no' : 'yes' );
			//}
			return $stats;
		}

		// $product_vars = the return of method 'product_find_new_variations'
		public function product_add_new_variations( $product_id, $product_vars=array() ) {
			$ret = array(
				'status' 	=> 'invalid',
				'msg' 		=> '',
				'new_added' => 0,
				'provider' 	=> 'amazon',
			);
			$msg = array();

			$provider = $product_vars['provider'];

			$retProd_new = $product_vars['retProd_new'];

			if ( ! $product_vars['total_new'] ) {
				$ret = array_replace_recursive( $ret, array(
					'msg' 	=> $product_vars['msg'],
				));
			}

			$msg[] = $product_vars['msg'];

			$setVariationsStatus = $this->get_ws_object( $provider )->set_woocommerce_variations(
				$retProd_new,
				$product_id,
				array(
					'var_exist' 	=> count( $product_vars['variations_exist'] ),
					'var_new' 		=> count( $product_vars['variations_new'] ),
				)
			);
			$msg[] = $setVariationsStatus['msg'];
			//var_dump('<pre>', $setVariationsStatus , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			delete_transient( "wc_product_children_$product_id" );
			delete_transient( "wc_var_prices_$product_id" );

			// set remote images
			if ( $this->is_remote_images ) {
				$setRemoteImgStatus = $this->get_ws_object( 'generic' )->build_remote_images( $product_id );
				$msg[] = $setRemoteImgStatus['msg'];
			}

			// Set the product type
			if ( 'amazon' == $provider ) {
				$this->update_products_type( array(
					'products' => array( $product_id )
				));
			}

			$msg = implode( '<br />', $msg );
			$ret = array_replace_recursive( $ret, array(
				'status' 	=> $setVariationsStatus['status'],
				'new_added' => $setVariationsStatus['nb_parsed'],
				'msg' 		=> $msg,
			));
			return $ret;
		}

		// $retProd must be formated through method 'build_product_data' from amz.helper.class.php
		public function product_find_new_variations( $retProd=array(), $pms=array() ) {
			global $wpdb;

			$pms = array_replace_recursive(array(
				'DEBUG' 		=> false,

				// optimization: find only new variations
				'only_new' 		=> true,

				'product_id' 	=> 0,

				'provider' 		=> 'amazon',
			), $pms);
			extract( $pms );


			//:: init
			$product_asin = isset($retProd['ASIN']) ? $retProd['ASIN'] : '';
			$product_asin = $this->prodid_set($product_asin, $provider, 'add');

			$opProductType = $this->get_product_type_by_apiresponse( $retProd, $provider );
			extract( $opProductType ); //is_variable, is_variation_child, nb_variations, product_type

			$asins = array();
			$variations = array();

			$variations_new = array();
			$variations_new_asin = array();
			$variations_exist = array();
			$variations_exist_asin = array();
			$variations_notfound = array();
			$variations_notfound_asin = array();

			$retProd_new = $retProd;
			// amazon
			if ( isset($retProd_new['Variations'], $retProd_new['Variations']['Item']) ) {
				//$retProd_new['Variations']['Item'] = array();
				unset( $retProd_new['Variations']['Item'] );
			}
			// ebay
			if ( isset($retProd_new['Variations'], $retProd_new['Variations']['Variation']) ) {
				//$retProd_new['Variations']['Variation'] = array();
				unset( $retProd_new['Variations']['Variation'] );
			}

			if ( isset($retProd_new['Variations'], $retProd_new['Variations']['TotalVariations']) ) {
				//$retProd_new['Variations']['TotalVariations'] = 0;
				unset( $retProd_new['Variations']['TotalVariations'] );
			}


			//:: return init
			$ret = array(
				'status' 					=> 'invalid',
				'msg' 						=> '',
				'current_post' 				=> false,

				'product_type' 				=> $product_type,

				// new variations from amazon
				'variations_new_asin' 		=> $variations_new_asin,
				'variations_new' 			=> $variations_new,
				'retProd_new' 				=> $retProd_new,
				'total_new' 				=> 0,

				// variations from amazon which already exists in the table
				'variations_exist_asin'		=> $variations_exist_asin,
				'variations_exist' 			=> $variations_exist,

				// variations which exists in table but aren't received from amazon in response
				'variations_notfound_asin'	=> $variations_notfound_asin,
				'variations_notfound'		=> $variations_notfound,

				'provider' 					=> $provider,
			);


			//:: find all variations childs asins from amazon response
			if ( $is_variable ) {
				//$retProd['Variations']['TotalVariations']
				if ( 'ebay' == $provider ) {
					$total = $this->get_amazon_variations_nb( $retProd['Variations']['Variation'], 'ebay' );

					if ($total <= 1) {
						$variations[] = $retProd['Variations']['Variation'];
					} else {
						$variations = (array) $retProd['Variations']['Variation'];
					}

					$compatVariations = $this->ebayHelper->ebay_product_compatible_variations( $retProd, $variations );
					$variations = $compatVariations['variations'];
				}
				else {
					$total = $this->get_amazon_variations_nb( $retProd['Variations']['Item'] );

					if ($total <= 1 || isset($retProd['Variations']['Item']['ASIN'])) {
						$variations[] = $retProd['Variations']['Item'];
					} else {
						$variations = (array) $retProd['Variations']['Item'];
					}
				}

				// Loop through the variation
				foreach ($variations as $variation_item) {
					if ( isset($variation_item['ASIN']) && ! empty($variation_item['ASIN']) ) {
						$asins[] = $variation_item['ASIN'];
					}
				} // end foreach
			}

			$asins = array_unique( array_filter( $asins ) );


			//:: validation
			if ( empty($asins) ) {
				$ret = array_replace_recursive($ret, array(
					'msg' 		=> sprintf( 'no variations found in %s response!', $provider ),
				));
				if ( $DEBUG ) {
					unset( $ret['variations_new'], $ret['retProd_new'], $ret['variations_exist'], $ret['variations_notfound'] );
				}
				return $ret;
			}

			//:: find all variation childs which already exists in database
			$tposts = $wpdb->posts;
			$tpostmeta = $wpdb->postmeta;

			$get_fields = 'pm.meta_value, p.ID, p.post_parent, p.post_title, p.post_content, p.post_excerpt, p.post_type';

			$asins_ = $asins;
			if ( 'amazon' == $provider ) {
				$asins_ = $this->prodid_set($asins_, $provider, 'sub');
				$prod_key = '_amzASIN';

				$get_fields = 'concat( \'amz-\', pm.meta_value ) as _prod_asin, p.ID, p.post_parent, p.post_title, p.post_content, p.post_excerpt, p.post_type';
			}
			else {
				$asins_ = $this->prodid_set($asins_, $provider, 'add');
				$prod_key = '_amzaff_prodid';
			}
			$asins_ = implode(',', array_map(array($this, 'prepareForInList'), $asins_));
			//var_dump('<pre>', $asins_ , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			// and p.post_status != ''
			if ( $only_new ) {
				$sql_x = "select $get_fields from $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id and pm.meta_key='$prod_key' where 1=1 and p.post_type IN ( 'product_variation', 'product' ) and ! isnull(pm.meta_value) and pm.meta_value IN ($asins_) order by p.ID asc;";
			}
			else {
				$sql_x = "select $get_fields from $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id and pm.meta_key='$prod_key' where 1=1 and p.post_type IN ( 'product_variation', 'product' ) and ! isnull(pm.meta_value) and ( p.ID = '$product_id' or p.post_parent = '$product_id' ) order by p.ID asc;";
			}
			$res_x = $wpdb->get_results( $sql_x, OBJECT_K );
			$asins_found = $res_x;
			//var_dump('<pre>', $asins_found , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			// parent variable product
			if ( isset($asins_found["$product_asin"]) && is_object($asins_found["$product_asin"]) ) {
				if ( 'product' == $asins_found["$product_asin"]->post_type ) {
					$ret['current_post'] = $this->_product_find_new_variations_getprodinfo( $asins_found["$product_asin"] );
				}
			}


			//:: new variations from amazon and variations from amazon which already exists in the table
			// Loop through the variation
			foreach ($variations as $variation_item) {
				$variation_asin = '';
				if ( isset($variation_item['ASIN']) && ! empty($variation_item['ASIN']) ) {
					$variation_asin = $variation_item['ASIN'];
				}
				if ( empty($variation_asin) ) {
					continue 1;
				}
				$variation_asin = $this->prodid_set($variation_asin, $provider, 'add');

				// variation already exists in database - UPDATE
				if ( isset($asins_found["$variation_asin"]) ) {
					$variations_exist["$variation_asin"] = array(
						'variation_item' 	=> $variation_item,
						'current_post' 		=> $this->_product_find_new_variations_getprodinfo( $asins_found["$variation_asin"] ),
					);
					$variations_exist_asin[] = $variation_asin;
				}
				// new variation - INSERT
				else {
					$variations_new[] = $variation_item;
					$variations_new_asin[] = $variation_asin;
				}
			}
			// end Loop through the variation
			//var_dump('<pre>', 'variations NEW', $variations_new_asin, 'variations EXIST', $variations_exist_asin , '</pre>');
			//var_dump('<pre>', 'variations NEW', $variations_new, 'variations EXIST', $variations_exist , '</pre>');
			//echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$total_new = count( $variations_new );
			if ( $total_new ) {
				if ( 'ebay' == $provider ) {
					$retProd_new['Variations']['Variation'] = ( $total_new > 1 ? $variations_new : $variations_new[0] );
				}
				else {
					$retProd_new['Variations']['Item'] = ( $total_new > 1 ? $variations_new : $variations_new[0] );
				}
				$retProd_new['Variations']['TotalVariations'] = $total_new;
			}


			//:: variations from database, which don't exist on amazon anymore - DELETE
			if ( ! $only_new ) {
				foreach ($asins_found as $prodAsin => $prodInfo) {
					if (
						'product' == $prodInfo->post_type
						|| in_array($prodAsin, $variations_exist_asin)
						|| in_array($prodAsin, $variations_new_asin)
					) {
						continue 1;
					}

					$variations_notfound["$prodAsin"] = array(
						'variation_item' 	=> array(),
						'current_post' 		=> $this->_product_find_new_variations_getprodinfo( $prodInfo ),
					);
					$variations_notfound_asin[] = $prodAsin;
				}
			}


			//:: return
			$msg = array();
			if ( $only_new ) {
				$msg[] = sprintf( 'we\'ve found %s new variations, %s variations already imported', $total_new, count($variations_exist) );
			}
			else {
				$msg[] = sprintf( 'we\'ve found %s new variations, %s variations already imported, %s variations which don\'t exit on %s anymore', $total_new, count($variations_exist), count($variations_notfound), $provider );
			}
			$msg = implode( '<br />', $msg );

			$ret = array_replace_recursive($ret, array(
				'status' 					=> 'valid',
				'msg' 						=> $msg,

				'variations_new_asin' 		=> $variations_new_asin,
				'variations_new' 			=> $variations_new,
				'retProd_new' 				=> $retProd_new,
				'total_new' 				=> $total_new,

				'variations_exist_asin'		=> $variations_exist_asin,
				'variations_exist' 			=> $variations_exist,

				'variations_notfound_asin'	=> $variations_notfound_asin,
				'variations_notfound' 		=> $variations_notfound,
			));
			if ( $DEBUG ) {
				unset( $ret['variations_new'], $ret['retProd_new'], $ret['variations_exist'], $ret['variations_notfound'] );
			}
			return $ret;
		}

		public function _product_find_new_variations_getprodinfo( $prodInfo ) {
			if ( empty($prodInfo) ) {
				return false;
			}
			return array(
				'post_parent' 		=> $prodInfo->post_parent,
				'post_id'			=> $prodInfo->ID,
				'post_title'		=> $prodInfo->post_title,
				'post_excerpt'		=> $prodInfo->post_excerpt,
				'post_content'		=> $prodInfo->post_content,
			);
		}
		//============================================================
		//== end IMPORT/ ADD NEW PRODUCT & UPDATE PRODUCT



		// used on wordpress hook 'get_post_metadata'
		public function gpm_on_product_url( $null, $object_id, $meta_key, $single ) {
			if ( ! isset($meta_key) ) {
				return $null;
			}
			if ( ! $object_id ) {
				return $null;
			}

			if ( '_product_url' == $meta_key ) {

				$product_buy_url = $this->_product_buy_url_asin( array(
					'product_id' 		=> $object_id,
					'redirect_asin' 	=> '',
				));

				$prod_link = $product_buy_url['link'];
				//$prod_asin = $product_buy_url['asin'];

				if ( $this->product_url_short ) {
					$prod_bitlymeta = $this->product_url_from_bitlymeta(array(
						'product' 	=> $object_id,
						'orig_url' 	=> $product_buy_url['link'],
						'country' 	=> $product_buy_url['country'],
					));
					if ( 'valid' === $prod_bitlymeta['status'] ) {
						$prod_link = $prod_bitlymeta['short_url'];
					}
				}

				// always return an array with your return value => no need to handle $single
				if( $prod_link != '' ) {
					return array( $prod_link );
				}
			}
			return $null;
		}
		// used on wordpress hook 'get_post_metadata'
		public function gpm_on_price( $null, $object_id, $meta_key, $single ) {
			if ( ! isset($meta_key) ) {
				return $null;
			}
			if ( ! $object_id ) {
				return $null;
			}

			$regex_price = '/^(_regular|_sale)?_price$/imu';

			if ( $_found = preg_match( $regex_price, $meta_key ) ) {

				// is amazon product? (also imported with woozonelite)
				// //remove_filter( 'get_post_metadata', array( $this, 'gpm_on_price' ), 999 );
				// $redirect_asin = WooZoneLite_get_post_meta($object_id, '_amzASIN', true);
				// //add_filter( 'get_post_metadata', array( $this, 'gpm_on_price' ), 999, 4 );
				// if ( empty($redirect_asin) ) {
				// 	return $null;
				// }

				// [FIX] on 2019-jul-02
				$isProdValid = $this->verify_product_is_amazon($object_id, array( 'verify_provider' => 'amazon' ));
				//var_dump('<pre>',$isProdValid ,'</pre>');
				if ( $isProdValid !== true ) {
					return $null;
				}

				remove_filter( 'get_post_metadata', array( $this, 'gpm_on_price' ), 999 );
				$current_meta = get_post_meta( $object_id, $meta_key, true );
				add_filter( 'get_post_metadata', array( $this, 'gpm_on_price' ), 999, 4 );

				if ( $current_meta != '' ) {
					$current_meta = $this->dropshiptax_price_global( $current_meta );

					// always return an array with your return value => no need to handle $single
					return array( (string) $current_meta );
				}
			}
			return $null;
		}



		public function _product_buy_url_asin( $pms=array() ) {
			$pms = array_replace_recursive( array(
				'product_id' 		=> 0,
				'redirect_asin' 	=> '',

				// string value of the country to be forced on product amazon link | bool true = we determin here the country
				// if bool true then product_id is mandatory
				'force_country' 	=> false,
			), $pms);
			extract( $pms );

			$ret = array(
				'asin' 		=> '',
				'link' 		=> '',
				'country' 	=> '',
			);

			//:: get asin
			if ( empty($redirect_asin) ) {
				$redirect_asin = WooZoneLite_get_post_meta($product_id, '_amzASIN', true);
			}
			if ( empty($redirect_asin) ) {
				return $ret;
			}

			$provider = $this->prodid_get_provider_by_asin( $redirect_asin );
			$asin = $this->prodid_get_asin($redirect_asin);

			$ret = array_replace_recursive($ret, array(
				'provider' => $provider,
				'asin' 	=> $redirect_asin,
			));

			$this->cur_provider = $provider;

			$link = '';
			$country = '';

			// PER PROVIDER
			if ( 'amazon' == $provider ) {

				//:: get country
				if ( is_bool($force_country) && $force_country ) {

					$getCountry = $this->get_product_import_country( array(
						'product_id'			=> $product_id,
						'country' 				=> '',
						'asin' 					=> $redirect_asin,
						'use_fallback_location' => true,
					));
					//var_dump('<pre>', $getCountry , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
					$the_country = $getCountry['country'];

					$the_country = $this->get_country2mainaffid( $the_country, array(
						'uk2gb' 	=> true,
					));

					$user_country = $this->amzForUser( $the_country );
				}
				else if ( is_string($force_country) && '' != $force_country ) {

					$the_country = $this->get_country2mainaffid( $force_country, array(
						'uk2gb' 	=> true,
					));

					$user_country = $this->amzForUser( $the_country );
					//var_dump('<pre>', $force_country, $the_country, $user_country ,'</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				}
				else {
					/*$get_user_location = wp_remote_get( 'http://api.hostip.info/country.php?ip=' . $_SERVER["REMOTE_ADDR"] );
					if( isset($get_user_location->errors) ) {
						$main_aff_site = $this->main_aff_site();
						$user_country = $this->amzForUser( strtoupper(str_replace(".", '', $main_aff_site)) );
					}else{
						$user_country = $this->amzForUser($get_user_location['body']);
					}*/
					$user_country = $this->get_country_perip_external();
				}
				//var_dump('<pre>', $user_country, '</pre>'); die('debug...');

				//:: build link
				$link = '//www.amazon' . ( $user_country['website'] ) . '/gp/product/' . ( $asin ) . '/?tag=' . ( $user_country['affID'] ) . '';
				$country = substr( $user_country['website'], 1 );
			}
			else if ( in_array($provider, array('alibaba', 'envato', 'ebay')) ) {

				$link = get_post_meta($product_id, '_amzaff_product_url', true);
				//var_dump('<pre>', $link, '</pre>');

				if ( 'envato' == $provider ) {
					$affid = isset($this->amz_settings['envato_AffId']) ? $this->amz_settings['envato_AffId'] : '';
					$link .= '?ref=' . $affid;
				}
				else if ( 'ebay' == $provider ) {
					$link_ = $this->get_ws_object( 'ebay' )->get_product_link( array(
						'prod_id'		=> $asin,
						'prod_link'		=> $link,
					));
					if ( !empty($link_) ) {
						$link = $link_;
					}
				}
			}

			$ret = array_replace_recursive($ret, array(
				'link' 		=> $link,
				'country' 	=> $country,
			));
			return $ret;
		}
		public function _product_buy_url( $product_id, $redirect_asin='', $force_country=false ) {
			$ret = $this->_product_buy_url_asin( array(
				'product_id' 		=> $product_id,
				'redirect_asin' 	=> $redirect_asin,
				'force_country' 	=> $force_country,
			));
			return $ret['link'];
		}

		/**
		 * Gets updater instance.
		 *
		 * @return AATeam_Product_Updater
		 */
		public function product_updater()
		{
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/utils/class-updater.php' );

			if( class_exists('WooZoneLite_AATeam_Product_Updater') ){
				$product_data = get_plugin_data( $this->cfg['paths']['plugin_dir_path'] . 'plugin.php', false );
				new WooZoneLite_AATeam_Product_Updater( $this, $product_data['Version'], 'woozonelite', 'woozonelite/plugin.php' );
			}
		}

		public function framework_style( $onlygenerate=false )
		{
			$start = microtime(true);

			$main_file = $this->wp_filesystem->get_contents( $this->cfg['paths']['freamwork_dir_path'] . "/scss/styles.scss" );
			if( !$main_file ){
				$main_file = file_get_contents( $this->cfg['paths']['freamwork_dir_path'] . "/scss/styles.scss" );
			}

			$files = array();
			if(preg_match_all('/@import (url\(\"?)?(url\()?(\")?(.*?)(?(1)\")+(?(2)\))+(?(3)\")/i', $main_file, $matches)){
				foreach ($matches[4] as $url) {
					if( file_exists( $this->cfg['paths']['freamwork_dir_path'] . "/scss/_" . $url . '.scss') ){
						$files[] = '_' . $url . '.scss';
					}
					if( file_exists( $this->cfg['paths']['freamwork_dir_path'] . "/scss/" . $url . '.scss') ){
						$files[] = $url . '.scss';
					}
				}
			}

			$buffer = '';
			if( count($files) > 0 ){
				foreach ($files as $scss_file) {
					if( 0 ){
						$buffer .= "\n" .   "/****-------------------------------\n";
						$buffer .= "\n" .   " IN FILE: $scss_file \n";
						$buffer .= "\n" .   "------------------------------------\n";
						$buffer .= "\n***/\n";
					}

					$has_wrote = $this->wp_filesystem->get_contents( $this->cfg['paths']['freamwork_dir_path'] . "/scss/" . $scss_file );
					if ( !$has_wrote ) {
						$has_wrote = file_get_contents( $this->cfg['paths']['freamwork_dir_path'] . "/scss/" . $scss_file );
					}
					$buffer .= $has_wrote;
				}
			}

			try {
				// 2018-may-31 update
				require $this->cfg['paths']['scripts_dir_path'] . "/scssphp/scss.inc.php";
				$scss = new scssc();
				//require $this->cfg['paths']['scripts_dir_path'] . "/scssphpnew/scss.inc.php";
				//$scss = new Compiler();
				//$scss->setLineNumberStyle(Compiler::LINE_COMMENTS);
				//$scss->setSourceMap(Compiler::SOURCE_MAP_INLINE);
				//$scss->setSourceMap(Compiler::SOURCE_MAP_FILE);
				//$scss->setSourceMapOptions( array(
				//	'sourceMapWriteTo' 	=> $this->cfg['paths']['freamwork_dir_path'] . 'main-style.css.map',
				//));

				$buffer = $scss->compile( $buffer );
			}
			catch (Exception $e) {
				die( 'scssphp: Unable to compile content' );
			}

			#$buffer = str_replace( "fonts/", $this->cfg['paths']['freamwork_dir_url'] . "fonts/", $buffer );
			$buffer = str_replace( '#framework_url/', $this->cfg['paths']['freamwork_dir_url'], $buffer );
			$buffer = str_replace( '#plugin_url', $this->cfg['paths']['plugin_dir_url'], $buffer );


			$time_elapsed_secs = microtime(true) - $start;
			$buffer .= "\n\n/*** Compile time: $time_elapsed_secs */";

			if ( ! isset($onlygenerate) || ! $onlygenerate ) {
				// Enable caching
				header('Cache-Control: public');

				// Expire in one day
				header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

				// Set the correct MIME type, because Apache won't set it for us
				header("Content-type: text/css");

				// Write everything out
				echo $buffer;
			}

			$buffer = str_replace( $this->cfg['paths']['freamwork_dir_url'], '', $buffer );

			$has_wrote = $this->wp_filesystem->put_contents( $this->cfg['paths']['freamwork_dir_path'] . 'main-style.css', $buffer );
			if ( !$has_wrote ) {
				$has_wrote = file_put_contents( $this->cfg['paths']['freamwork_dir_path'] . 'main-style.css', $buffer );
			}

			if ( isset($onlygenerate) && $onlygenerate ) {
				return true;
			}

			die;
		}

		public function dismiss_notice()
		{
			$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
			if ( !$id ) {
				header( 'Location: ' . sprintf( admin_url('admin.php?page=%s'), $this->alias ) );
				die;
			}

			$current = get_option( $this->alias . "_dismiss_notice", array() );
			$current = !empty($current) && is_array($current) ? $current : array();
			$current["$id"] = 1;
			update_option( $this->alias . "_dismiss_notice" , $current );
			header( 'Location: ' . sprintf( admin_url('admin.php?page=%s'), $this->alias ) );
			die;
		}

		public function notifier_cache_interval() {
			return self::NOTIFIER_CACHE_INTERVAL;
		}

		public function plugin_row_meta($what='') {
			if ( !empty($what) && isset(self::$plugin_row_meta["$what"]) ) {
				return self::$plugin_row_meta["$what"];
			}
			return self::$plugin_row_meta;
		}

		/**
		 * Operation Messages
		 */
		public function opStatusMsgInit( $pms=array() ) {
			extract($pms);
			$this->opStatusMsg = array(
				'status'            => isset($status) ? $status : 'invalid',
				'operation'         => isset($operation) ? $operation : '',
				'operation_id'      => isset($operation_id) ? (string) $operation_id : '',
				'msg_header'        => isset($msg_header) ? $msg_header : '',
				'msg'               => array(),
				'duration'          => 0,
			);
			if ( isset($keep_msg) && $keep_msg ) {
				$opStatusMsg = $this->opStatusMsgGet('', 'file');
				$this->opStatusMsg['msg'][] = $opStatusMsg['msg'];
			}
			$this->opStatusMsgSetCache();
			return true;
		}
		public function opStatusMsgSet( $pms=array() ) {
			if ( empty($pms) ) return '';

			$msg = array();
			foreach ($pms as $key => $val) {
				if ( $key == 'msg' ) {
					if ( isset($pms['duration']) ) {
						$val .= ' - [ ' . (isset($pms['end']) ? 'total: ' : '') . $this->format_duration($pms['duration']) . ' ]';
					}
					$this->opStatusMsg["$key"][] = $val;
					$msg[] = $val;
				} else {
					$this->opStatusMsg["$key"] = $val;
				}
			}
			$this->opStatusMsgSetCache();

			$msg = implode('<br />', $msg);
			return $msg;
		}

		public function opStatusMsgSetCache( $from='file' ) {
			WooZoneLite_session_close(); // close the session to allow asynchronous ajax calls

			if ( $from == 'session' ) {
				$this->opStatusMsgSetSession();
			} else if ( $from == 'cookie' ) {
				$this->opStatusMsgSetCookie();
			} else if ( $from == 'file' ) {
				$this->opStatusMsgSetFile();
			}
		}
		private function opStatusMsgSetSession() {
			WooZoneLite_session_start(); // start the session
			$_SESSION['WooZoneLite_opStatusMsg'] = serialize($this->opStatusMsg);
			WooZoneLite_session_close(); // close the session
		}
		private function opStatusMsgSetCookie() {
			$cookie = $this->opStatusMsgGet();
			$cookie = $cookie['msg'];
			//$cookie = base64_encode($cookie);
			//$cookie = $this->encodeURIComponent( $cookie );

			$this->cookie_set(array(
				'name'          => 'WooZoneLite_opStatusMsg',
				'value'         => $cookie,
				// time() + 604800, // 1 hour = 3600 || 1 day = 86400 || 1 week = 604800 || '+30 days'
				'expire_sec'    => strtotime( time() + 86400 )
			));
		}
		private function opStatusMsgSetFile() {
			$filename = $this->cfg['paths']['plugin_dir_path'] . 'cache/operation_status_msg.txt';

			$opStatusMsg = serialize($this->opStatusMsg);
			$this->u->writeCacheFile( $filename, $opStatusMsg );
		}

		public function opStatusMsgGet( $sep='<br />', $from='code' ) {
			$opStatusMsg = $this->opStatusMsg;
			if ( $from == 'session' ) {
				$opStatusMsg = unserialize($_SESSION['WooZoneLite_opStatusMsg']);
			} else if ( $from == 'cookie' ) {
				$opStatusMsg = $_COOKIE['WooZoneLite_opStatusMsg'];
				return $opStatusMsg;
			} else if ( $from == 'file' ) {
				$filename = $this->cfg['paths']['plugin_dir_path'] . 'cache/operation_status_msg.txt';

				if ( !$this->u->verifyFileExists($filename) ) {
					$this->u->createFile($filename);
				}
				$opStatusMsg = $this->u->getCacheFile( $filename );
				$opStatusMsg = unserialize($opStatusMsg);
			}

			$msg = (array) $opStatusMsg['msg'];
			$opStatusMsg['msg'] = implode( $sep, $msg );
			if ( isset($opStatusMsg['msg_header']) && !empty($opStatusMsg['msg_header']) ) {
				$opStatusMsg['msg'] = $opStatusMsg['msg_header'] . $sep . $opStatusMsg['msg'];
			}
			return $opStatusMsg;
		}

		/**
		 * Database tables
		 */
		public function admin_notice_install_styles()
		{
			if( !wp_style_is($this->alias . '-activation') ) {
				wp_enqueue_style( $this->alias . '-activation', $this->plugin_asset_get_path( 'css', $this->cfg['paths']['freamwork_dir_url'] . 'css/activation.css', true ), array(), $this->plugin_asset_get_version( 'css' ) );
			}

			// 2017-08-14 this code is deprecated - there is an wizard now to guide you through install settings
			//add_action( 'admin_notices', array( $this, 'admin_install_notice' ) );
		}

		public function admin_install_notice()
		{
		?>
		<div class="updated WooZoneLite-message_activate wc-connect">
			<div class="squeezer">
				<h4><?php _e( sprintf( '<strong>%s</strong> &#8211; You are almost ready, if this is your first install, please install the default setup. To do that click on the "Install Default Setup" button below and after that click on the "Install Settings" button from the Setup/Backup page.', $this->pluginName ), 'woozonelite' ); ?></h4>
				<p class="submit"><a href="<?php echo admin_url( 'admin.php?page=' . $this->alias ); ?>#!/setup_backup#makeinstall" class="button-primary"><?php _e( 'Install Default Setup', 'woozonelite' ); ?></a> |
				<a href="<?php echo admin_url("admin.php?page=WooZoneLite&disable_activation");?>" class="aaFrm-dismiss"><?php _e('Dismiss This Message', 'woozonelite'); ?></a>
				</p>
			</div>
		</div>
		<?php
		}

		public function update_developer()
		{
			$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
			if ( in_array($ip, array('1.1.1.1')) ) {
				$this->dev = 'andrei';
			}
			else if ( in_array($ip, array()) ) {
				$this->dev = 'gimi';
			}
		}

		/*
		public function add_demo_products_marker( $column, $post_id )
		{
			switch ( $column ) {
				case 'name':

					$is_direct_import_products = (boolean)get_post_meta( $post_id, '_amzaff_direct_import', true );
					if( $is_direct_import_products === true ){
						$html = array();
						$html[] = '<span class="WooZoneLite-marker-direct-import">';
						$html[] = 	'<img src="' . ( $this->cfg['paths']['plugin_dir_url'] . 'icon_directimport.png' ) . '" />';
						$html[] = 	'Direct Import';
						$html[] = '</span>';

						echo implode( "\n", $html );
					}else{
						$is_demo_products = (boolean)get_post_meta( $post_id, '_amzaff_aateam_keys', true );
						if( $is_demo_products === true ){
							$html = array();
							$html[] = '<span class="WooZoneLite-marker-demo-product">';
							$html[] = 	'<img src="' . ( $this->cfg['paths']['plugin_dir_url'] . 'icon_24.png' ) . '" />';
							$html[] = 	'Demo Product';
							$html[] = '</span>';

							echo implode( "\n", $html );
						}
					}
					break;
			}
		}
		*/

		public function demo_products_import_end_html( $pms=array() )
		{
			extract($pms);

			$html = array();

			$products_id = $this->get_products_demo_keys('idlist');

			$html[] = '<div class="WooZoneLite-demo_end_wrapper">';
			$html[] = 	'<div class="WooZoneLite-demo_big_logo">';
			$html[] = 		'<img src="' . ( $this->cfg['paths']['freamwork_dir_url'] ) . 'images/woozone-big-logo.png" />';
			$html[] = 	'</div>';
			$html[] = 	'<div class="WooZoneLite-demo_message">';
			$html[] = 		'<span class="WooZoneLite-demo-arrow_box"></span>';
			$html[] = 		__( '<h3>Thank you for using WooZoneLite, the best Amazon Affiliate WooCommerce plugin available on the market.</h3>', 'woozonelite' );

			if ( isset($is_block_demo_keys) && $is_block_demo_keys ) {

				$html[] = sprintf( __( 'When you\'re using aateam demo keys, you\'re not allowed to use this module.<br/>Please follow the instructions to generate your own Amazon API keys and register for the affiliation program. You can find the instructions <a href="%s" target="_blank">here</a> or you can open a ticket at <a href="%s">support.aa-team.com</a>. <br />', 'woozonelite' ), 'http://docs.aa-team.com/woocommerce-amazon-affiliates/documentation/how-to-create-an-amazon-account-and-sign-up-for-the-product-advertising-api/', 'http://support.aa-team.com' );
			}
			else {

				$html[] = 		sprintf( __( 'For an easier understanding about how awesome our plugin is, we give you the opportunity to import %s products using our demo keys.<br />', 'woozonelite'), $this->ss['max_products_demo_keys'] );
				$html[] = 		__( 'These are the products you choose to import:', 'woozonelite' );

				$html[] =		'<ul class="WooZoneLite-demo-products-list">';

				if ( !empty($products_id) && count($products_id) > 0 ) {

					foreach ($products_id as $prod_id) {
						$product_thumb = '<img class="no-image-available" src="'. ( $this->cfg['paths']['plugin_dir_url'] ) . 'no-image.jpg" alt="no-image-available" />';
						if( get_the_post_thumbnail( $prod_id, array(50, 50) ) != '' ) {
							$product_thumb = get_the_post_thumbnail( $prod_id, array(50, 50) );
						}

						$html[] = '<li><a href="' . ( admin_url('post.php?post=' . ( $prod_id ) . '&action=edit') ) . '">' . ( $product_thumb ) . '</a></li>';
					}
				}

				$html[] =		'</ul>';

				$html[] = 		sprintf( __( 'In order to use the plugin at its full capacity, please follow the instructions to generate your own Amazon API keys and register for the affiliation program. You can find the instructions <a href="%s" target="_blank">here</a> or you can open a ticket at <a href="%s">support.aa-team.com</a>. <br />', 'woozonelite' ), 'http://docs.aa-team.com/woocommerce-amazon-affiliates/documentation/how-to-create-an-amazon-account-and-sign-up-for-the-product-advertising-api/', 'http://support.aa-team.com' );
			}

			$html[] =		'<a class="WooZoneLite-form-button-primary" href="' . ( admin_url( 'admin.php?page=WooZoneLite#!/amazon' ) ) . '">' . __('Set your own keys now', 'woozonelite' ) . '</a>';
			$html[] = 	'</div>';
			$html[] = '</div>';

			return implode( "\n", $html );
		}

		public function _get_current_amazon_aff() {
			$__ = $this->cur_provider;
			$this->cur_provider = 'amazon';

			$user_country = $this->get_country_perip_external();

			$ret = array(
				//'main_aff_site'           => $main_aff_site,
				'user_country'              => $user_country,
			);

			$this->cur_provider = $__;

			return $ret;
		}

		public function get_amazon_country_site($country, $withPrefixPoint=false) {
			$provider = $this->cur_provider;

			if ( isset($country) && !empty($country) ) {

				if ( 'amazon' == $provider ) {

				$config = array('main_aff_id' => $country);

				$ret = '';
				if( $config['main_aff_id'] == 'com' ){
					$ret = '.com';
				}
				elseif( $config['main_aff_id'] == 'ca' ){
					$ret = '.ca';
				}
				elseif( $config['main_aff_id'] == 'cn' ){
					$ret = '.cn';
				}
				elseif( $config['main_aff_id'] == 'de' ){
					$ret = '.de';
				}
				elseif( $config['main_aff_id'] == 'in' ){
					$ret = '.in';
				}
				elseif( $config['main_aff_id'] == 'it' ){
					$ret = '.it';
				}
				elseif( $config['main_aff_id'] == 'es' ){
					$ret = '.es';
				}
				elseif( $config['main_aff_id'] == 'fr' ){
					$ret = '.fr';
				}
				elseif( $config['main_aff_id'] == 'uk' ){
					$ret = '.co.uk';
				}
				elseif( $config['main_aff_id'] == 'jp' ){
					$ret = '.co.jp';
				}
				elseif( $config['main_aff_id'] == 'mx' ){
					$ret = '.com.mx';
				}
				elseif( $config['main_aff_id'] == 'br' ){
					$ret = '.com.br';
				}
				elseif( $config['main_aff_id'] == 'au' ){
					$ret = '.com.au';
				}
				elseif( $config['main_aff_id'] == 'ae' ){
					$ret = '.ae';
				}

				if ( !empty($ret) && !$withPrefixPoint )
					$ret = substr($ret, 1);
				return $ret;

				}
				else if ( 'ebay' == $provider ) {
					return $country; // same as $config['main_aff_id']
				}
			}
			return '';
		}

		public function amz_default_affid( $config ) {
			$provider = $this->cur_provider;
			$prefix = 'amazon' != $provider ? $provider.'_' : '';

			$config = (array) $config;

			// get all amazon settings options
			$main_aff_id = 'com'; $country = 'com';
			if ( 'ebay' == $provider ) {
				$main_aff_id = 'EBAY-US'; $country = $main_aff_id;
			}

			// already have a Valid main affiliate id!
			if( isset($config[$prefix.'main_aff_id'], $config[$prefix.'AffiliateID'], $config[$prefix.'AffiliateID'][$config[$prefix.'main_aff_id']])
				&& !empty($config[$prefix.'main_aff_id'])
				&& !empty($config[$prefix.'AffiliateID'][$config[$prefix.'main_aff_id']]) ) {

				return $config;
			}

			// get key for first found not empty affiliate id!
			if ( isset($config[$prefix.'AffiliateID']) && !empty($config[$prefix.'AffiliateID'])
				&& is_array($config[$prefix.'AffiliateID']) ) {
					foreach ( $config[$prefix.'AffiliateID'] as $key => $val ) {
						if ( !empty($val) ) {
							$main_aff_id = $key;
							$country = $this->get_amazon_country_site($main_aff_id);
							break;
						}
					}
			}

			$config[$prefix.'main_aff_id'] = $main_aff_id;
			$config[$prefix.'country'] = $country;

			return $config;
		}

		public function main_aff_id()
		{
			$provider = $this->cur_provider;
			$prefix = 'amazon' != $provider ? $provider.'_' : '';

			$config = $this->amz_settings;
			$config = $this->amz_default_affid( $config );
			$config = (array) $config;

			if( isset($config[$prefix.'main_aff_id'], $config[$prefix.'AffiliateID'], $config[$prefix.'AffiliateID'][$config[$prefix.'main_aff_id']])
				&& !empty($config[$prefix.'main_aff_id'])
				&& !empty($config[$prefix.'AffiliateID'][$config[$prefix.'main_aff_id']]) ) {

				return $config[$prefix.'AffiliateID'][$config[$prefix.'main_aff_id']];
			}
			return 'amazon' == $provider ? 'com' : 'EBAY-US';
		}

		public function main_aff_site()
		{
			$provider = $this->cur_provider;
			$prefix = 'amazon' != $provider ? $provider.'_' : '';

			$config = $this->amz_settings;
			$config = $this->amz_default_affid( $config );
			$config = (array) $config;

			if( isset($config[$prefix.'main_aff_id'], $config[$prefix.'AffiliateID'], $config[$prefix.'AffiliateID'][$config[$prefix.'main_aff_id']])
				&& !empty($config[$prefix.'main_aff_id'])
				&& !empty($config[$prefix.'AffiliateID'][$config[$prefix.'main_aff_id']]) ) {

				if ( 'amazon' == $provider ) {

				if( $config['main_aff_id'] == 'com' ){
					return '.com';
				}
				elseif( $config['main_aff_id'] == 'ca' ){
					return '.ca';
				}
				elseif( $config['main_aff_id'] == 'cn' ){
					return '.cn';
				}
				elseif( $config['main_aff_id'] == 'de' ){
					return '.de';
				}
				elseif( $config['main_aff_id'] == 'in' ){
					return '.in';
				}
				elseif( $config['main_aff_id'] == 'it' ){
					return '.it';
				}
				elseif( $config['main_aff_id'] == 'es' ){
					return '.es';
				}
				elseif( $config['main_aff_id'] == 'fr' ){
					return '.fr';
				}
				elseif( $config['main_aff_id'] == 'uk' ){
					return '.co.uk';
				}
				elseif( $config['main_aff_id'] == 'jp' ){
					return '.co.jp';
				}
				elseif( $config['main_aff_id'] == 'mx' ){
					return '.com.mx';
				}
				elseif( $config['main_aff_id'] == 'br' ){
					return '.com.br';
				}
				elseif( $config['main_aff_id'] == 'au' ){
					return '.com.au';
				}
				elseif( $config['main_aff_id'] == 'ae' ){
					return '.ae';
				}
				else {
						return '.com';
				}

				}
				else if ( 'ebay' == $provider ) {
					return $config[$prefix.'main_aff_id'];
				}
			}

			if ( 'amazon' == $provider ) {
				return '.com';
			}
			else if ( 'ebay' == $provider ) {
				return 'EBAY-US';
			}
		}

		private function amzForUser( $userCountry='US' )
		{
			$provider = $this->cur_provider;
			$prefix = 'ebay' == $provider ? 'ebay_' : '';

			if ( in_array($provider, array('alibaba', 'envato', 'ebay')) ) {
				return array('key' => $userCountry);
			}

			$config = $this->amz_settings;
			$config = $this->amz_default_affid( $config );
			$config = (array) $config;

			$affIds = (array) isset($config[$prefix.'AffiliateID']) ? $config[$prefix.'AffiliateID'] : array();
			$main_aff_id = $this->main_aff_id();
			$main_aff_site = $this->main_aff_site();

			if ( 'amazon' == $provider ) {

			if( $userCountry == 'US' ){
				return array(
					'key'   => 'com',
					'website' => isset($affIds['com']) && (trim($affIds['com']) != "") ? '.com' : $main_aff_site,
					'affID' => isset($affIds['com']) && (trim($affIds['com']) != "") ? $affIds['com'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'CA' ){
				return array(
					'key'   => 'ca',
					'website' => isset($affIds['ca']) && (trim($affIds['ca']) != "") ? '.ca' : $main_aff_site,
					'affID' => isset($affIds['ca']) && (trim($affIds['ca']) != "") ? $affIds['ca'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'FR' ){
				return array(
					'key'   => 'fr',
					'website' => isset($affIds['fr']) && (trim($affIds['fr']) != "") ? '.fr' : $main_aff_site,
					'affID' => isset($affIds['fr']) && (trim($affIds['fr']) != "") ? $affIds['fr'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'CN' ){
				return array(
					'key'   => 'cn',
					'website' => isset($affIds['cn']) && (trim($affIds['cn']) != "") ? '.cn' : $main_aff_site,
					'affID' => isset($affIds['cn']) && (trim($affIds['cn']) != "") ? $affIds['cn'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'DE' ){
				return array(
					'key'   => 'de',
					'website' => isset($affIds['de']) && (trim($affIds['de']) != "") ? '.de' : $main_aff_site,
					'affID' => isset($affIds['de']) && (trim($affIds['de']) != "") ? $affIds['de'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'IN' ){
				return array(
					'key'   => 'in',
					'website' => isset($affIds['in']) && (trim($affIds['in']) != "") ? '.in' : $main_aff_site,
					'affID' => isset($affIds['in']) && (trim($affIds['in']) != "") ? $affIds['in'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'IT' ){
				return array(
					'key'   => 'it',
					'website' => isset($affIds['it']) && (trim($affIds['it']) != "") ? '.it' : $main_aff_site,
					'affID' => isset($affIds['it']) && (trim($affIds['it']) != "") ? $affIds['it'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'JP' ){
				return array(
					'key'   => 'jp',
					'website' => isset($affIds['jp']) && (trim($affIds['jp']) != "") ? '.co.jp' : $main_aff_site,
					'affID' => isset($affIds['jp']) && (trim($affIds['jp']) != "") ? $affIds['jp'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'ES' ){
				return array(
					'key'   => 'es',
					'website' => isset($affIds['es']) && (trim($affIds['es']) != "") ? '.es' : $main_aff_site,
					'affID' => isset($affIds['es']) && (trim($affIds['es']) != "") ? $affIds['es'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'GB' ){
				return array(
					'key'   => 'uk',
					'website' => isset($affIds['uk']) && (trim($affIds['uk']) != "") ? '.co.uk' : $main_aff_site,
					'affID' => isset($affIds['uk']) && (trim($affIds['uk']) != "") ? $affIds['uk'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'MX' ){
				return array(
					'key'   => 'mx',
					'website' => isset($affIds['mx']) && (trim($affIds['mx']) != "") ? '.com.mx' : $main_aff_site,
					'affID' => isset($affIds['mx']) && (trim($affIds['mx']) != "") ? $affIds['mx'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'BR' ){
				return array(
					'key'   => 'br',
					'website' => isset($affIds['br']) && (trim($affIds['br']) != "") ? '.com.br' : $main_aff_site,
					'affID' => isset($affIds['br']) && (trim($affIds['br']) != "") ? $affIds['br'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'AU' ){
				return array(
					'key'   => 'au',
					'website' => isset($affIds['au']) && (trim($affIds['au']) != "") ? '.com.au' : $main_aff_site,
					'affID' => isset($affIds['au']) && (trim($affIds['au']) != "") ? $affIds['au'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'AE' ){
				return array(
					'key'   => 'ae',
					'website' => isset($affIds['ae']) && (trim($affIds['ae']) != "") ? '.ae' : $main_aff_site,
					'affID' => isset($affIds['ae']) && (trim($affIds['ae']) != "") ? $affIds['ae'] : $main_aff_id
				);
			}

			else{

				$website = $config["main_aff_id"];
				if( $config["main_aff_id"] == 'uk' ) $website = 'co.uk';
				if( $config["main_aff_id"] == 'jp' ) $website = 'co.jp';
				if( $config["main_aff_id"] == 'mx' ) $website = 'com.mx';
				if( $config["main_aff_id"] == 'br' ) $website = 'com.br';
				if( $config["main_aff_id"] == 'au' ) $website = 'com.au';

				return array(
					'key'           => $config["main_aff_id"],
					'website'       => "." . $website,
					'affID'         => $main_aff_id
				);
			}

			}
		}

		public function get_post_id_by_meta_key_and_value($key, $value)
		{
			global $wpdb;

			$provider = $this->prodid_get_provider_by_asin( $value );
			if ( 'amazon' != $provider ) {
				$_key = $key;
				if ( $_key == '_amzASIN' ) $key = '_amzaff_prodid';
			}

			$meta = $wpdb->get_results($wpdb->prepare("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key=%s AND meta_value=%s", $key, $value));

			if (is_array($meta) && !empty($meta) && isset($meta[0])) {
				$meta = $meta[0];
			}
			if (is_object($meta)) {
				return $meta->post_id;
			}
			else {
				return false;
			}
		}


		/**
		 * Some Plugin Status Info
		 */
		public function plugin_redirect() {

			$req = array(
				'disable_activation'        => isset($_REQUEST['disable_activation']) ? 1 : 0,
				'page'                      => isset($_REQUEST['page']) ? (string) $_REQUEST['page'] : '',
			);
			extract($req);

			if ( $disable_activation && $this->alias == $page ) {
				update_option( $this->alias . "_is_installed", true );
				wp_redirect( get_admin_url() . 'admin.php?page=WooZoneLite' );
			}

			if (get_option('WooZoneLite_do_activation_redirect', false)) {

				$is_makeinstall = 1;

				$pullOutArray = @json_decode( file_get_contents( $this->cfg['paths']['plugin_dir_path'] . 'modules/setup_backup/default-setup.json' ), true );
				foreach ($pullOutArray as $key => $value){

					// prepare the data for DB update
					$saveIntoDb = $value;
					$saveIntoDb = is_bool($value) ? ( $value ? 'true' : 'false' ) : $value; //2016-june-21 fix

					//$saveIntoDb = $value != "true" ? serialize( $value ) : "true";
					//$saveIntoDb = !in_array( $value, array('true', 'false') ) && !is_bool($value) ? serialize( $value ) : $value; //2016-june-21 fix

					if ( 'WooZoneLite_amazon' == $key ) {
						$saveIntoDb = $this->amazon_config_with_default( $value );
						//$saveIntoDb = serialize( $saveIntoDb ); //2016-june-21 fix
					}

					// option already exists in db => don't overwrite it!
					if  ( $is_makeinstall && get_option( $key, false ) ) {
						//var_dump('<pre>',$key, 'exists in db.', '</pre>');
						continue 1;
					}

					// Use the function update_option() to update a named option/value pair to the options database table. The option_name value is escaped with $wpdb->escape before the INSERT statement.
					update_option( $key, $saveIntoDb );
				}

				delete_option('WooZoneLite_do_activation_redirect');
				wp_redirect( get_admin_url() . 'admin.php?page=WooZoneLite' );
			}
		}

		public function amazon_config_with_default( $default ) {
			$dbs = $this->settings();
			$dbs = !empty($dbs) && is_array($dbs) ? $dbs : array();

			// keys to be maintained
			// AccessKeyID, SecretAccessKey, protocol, country, main_aff_id, amazon_requests_rate
			$maintain = array('AccessKeyID', 'SecretAccessKey', 'protocol', 'country', 'main_aff_id', 'amazon_requests_rate');
			foreach ($maintain as $key) {
				if ( isset($dbs["$key"]) && empty($dbs["$key"]) ) {
					unset($dbs["$key"]);
				}
			}

			// default mandatory keys & affiliate id
			//if ( isset($dbs['AccessKeyID']) && empty($dbs['AccessKeyID']) ) {
			//  unset($dbs['AccessKeyID']);
			//}
			//if ( isset($dbs['SecretAccessKey']) && empty($dbs['SecretAccessKey']) ) {
			//  unset($dbs['SecretAccessKey']);
			//}
			if ( isset($dbs['AffiliateID']) ) {
				if ( empty($dbs['AffiliateID']) || !is_array($dbs['AffiliateID']) ) {
					unset($dbs['AffiliateID']);
				} else {
					$found = false;
					foreach ($dbs['AffiliateID'] as $key => $val) {
						if ( !empty($val) ) {
							$found = true;
							break;
						}
					}
					if ( !$found ) {
						unset($dbs['AffiliateID']);
					}
				}
			}

			$new = array_replace_recursive( $default, $dbs);
			//var_dump('<pre>', $new, '</pre>'); die('debug...');
			return $new;
		}

		public function get_plugin_status ()
		{
			//return $this->v->isReg( get_option($this->alias.'_hash') );
			return 'valid_hash';
		}

		public function get_plugin_data()
		{
			$this->details = $this->pu->get_plugin_data();
			return $this->details;
		}

		public function get_latest_plugin_version($interval) {
			return $this->pu->get_latest_plugin_version($interval);
		}


		/**
		 * Create plugin init
		 *
		 *
		 * @no-return
		 */
		public function initThePlugin() {

			// If the user can manage options, let the fun begin!
			if ( $this->is_admin && current_user_can( 'manage_options' ) ) {
				// Adds actions to hook in the required css and javascript
				add_action( "admin_print_styles", array( $this, 'admin_load_styles') );
				add_action( "admin_print_scripts", array( $this, 'admin_load_scripts') );

				// create dashboard page
				add_action( 'admin_menu', array( $this, 'createDashboardPage' ) );

				// get fatal errors
				add_action ( 'admin_notices', array( $this, 'fatal_errors'), 10 );

				// get fatal errors
				add_action ( 'admin_notices', array( $this, 'admin_warnings'), 10 );

				// number of requests made to the API
				//add_action ( 'admin_notices', array( &$this, 'api_requests_show'), 10 );

				$section = isset( $_REQUEST['section'] ) ? $_REQUEST['section'] : '';
				$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';

				if ( $page == $this->alias || strpos($page, $this->alias) == true && trim($section) != "" ) {
					add_action('init', array( $this, 'go_to_section' ));
				}
			}

			// keep the plugin modules into storage
			if ( ! isset($_REQUEST['page']) || strpos($_REQUEST['page'],'codestyling') === false ) {
				$this->load_modules();
			}
		}

		public function go_to_section() {
			$section = isset( $_REQUEST['section'] ) ? $_REQUEST['section'] : '';
			if( trim($section) != "" ) {
				header('Location: ' . sprintf(admin_url('admin.php?page=%s#!/%s'), $this->alias, $section) );
				exit();
			}
		}

		// updated in 2018-jan-24
		private function update_products_type( $pms=array() ) {
			global $wpdb;

			$pms = array_replace_recursive(array(
				'products' 		=> 'all',
				'do_external' 	=> $this->product_offerlistingid_missing_external,
			), $pms);
			extract( $pms );

			//:: INIT
			$ret = array(
				'status' 	=> 'invalid',
				'msg' 		=> '',
			);

			$tposts = $wpdb->posts;
			$tpostmeta = $wpdb->postmeta;

			$p_type_gen = isset($this->amz_settings['onsite_cart']) && ( $this->amz_settings['onsite_cart'] == "no" )
				? 'external' : 'simple';

			$amzprods = array( 'prods' => array(), 'var' => array(), 'olprods' => array(), 'olvar' => array() );


			//:: what products do we want to retrieve - all or just some from input params
			$input_prods = array();
			if ( is_array($products) ) {
				$input_prods = (array) $products;
			}
			else if ( 'all' !== $products ) {
				$input_prods[] = (string) $products;
			}

			$input_prods_ = $input_prods;
			$input_prods_ = implode(',', array_map(array($this, 'prepareForInList'), $input_prods_));
			//var_dump('<pre>', $input_prods_ , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$sql_clause = "";
			if ( ! empty($input_prods) ) {
				$sql_clause = " and p.ID IN ( $input_prods_ ) ";
			}


			//:: get all amazon simple & variable (parent) products
			// and p.post_status != ''
			// and (p.post_status = 'publish' OR p.post_status = 'future' OR p.post_status = 'draft' OR p.post_status = 'pending' OR p.post_status = 'private')
			$sql = "select p.ID, pm.meta_value from $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id where 1=1 {clause} and p.post_type = 'product' and pm.meta_key='_amzASIN' and ! isnull(pm.post_id) and pm.meta_value != '' order by p.ID asc;";
			$sql = str_replace( '{clause}', $sql_clause, $sql );
			$res = $wpdb->get_results( $sql, OBJECT_K );
			$amzprods['prods'] = $res;
			//var_dump('<pre>', $sql, $amzprods['prods'] , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;


			//:: validation
			if ( empty($amzprods['prods']) || ! is_array($amzprods['prods']) ) {
				$ret = array_replace_recursive($ret, array(
					'msg' 	=> 'no amazon products found based on input parameters',
				));
				return $ret;
			}


			//:: get all amazon variable (parent) products (each variable product must have at least one variation child associated)
			$sql = "select p.post_parent, count(p.ID) as _nb_found from $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id where 1=1 {clause} and p.post_type = 'product_variation' and p.post_parent > 0 and pm.meta_key='_amzASIN' and ! isnull(pm.post_id) and pm.meta_value != '' group by p.post_parent having _nb_found > 0 order by p.post_parent asc;";
			$sql = str_replace( '{clause}', str_replace( 'p.ID', 'p.post_parent', $sql_clause ), $sql );
			$res = $wpdb->get_results( $sql, OBJECT_K );
			$amzprods['var'] = $res;
			//var_dump('<pre>', $sql, $amzprods['var'] , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;


			//:: we try to find all amazon products without an offerlistingid
			if ( $do_external ) {

				//:: get all amazon simple & variable (parent) products which have the _amzaff_amzRespPrice meta, but don't have an offerlistingid
				$sql = "select p.ID, pm.meta_value from $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id where 1=1 {clause} and p.post_type = 'product' and pm.meta_key='_amzaff_amzRespPrice' and ! isnull(pm.post_id) and pm.meta_value != '' and pm.meta_value not regexp 's:14:\"OfferListingId\";' order by p.ID asc;";
				$sql = str_replace( '{clause}', $sql_clause, $sql );
				$res = $wpdb->get_results( $sql, OBJECT_K );
				$amzprods['olprods'] = $res;
				//var_dump('<pre>', $sql, $amzprods['olprods'] , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				//:: get all amazon variable (parent) products which have an offerlistingid (each variable product must have at least one variation child with an offerlistingid)
				if ( ! empty($amzprods['olprods']) && is_array($amzprods['olprods']) ) {
					$sql = "select p.post_parent, count(p.ID) as _nb_found from $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id where 1=1 {clause} and p.post_type = 'product_variation' and p.post_parent > 0 and pm.meta_key='_amzaff_amzRespPrice' and ! isnull(pm.post_id) and pm.meta_value != '' and pm.meta_value regexp 's:14:\"OfferListingId\";' group by p.post_parent having _nb_found > 0 order by p.post_parent asc;";
					$sql = str_replace( '{clause}', str_replace( 'p.ID', 'p.post_parent', $sql_clause ), $sql );
					$res = $wpdb->get_results( $sql, OBJECT_K );
					$amzprods['olvar'] = $res;
					//var_dump('<pre>', $sql, $amzprods['olvar'] , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				}
			}
			//return true; //DEBUG


			//:: try to update the product type for the found products
			//var_dump('<pre>', $amzprods , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			foreach ($amzprods['prods'] as $key => $value) {

				$p_type = $p_type_gen;

				if ( $do_external ) {
					// product don't have an offerlistingid: simple | variable parent
					// & even if it's variable it doesn't have at least one valid variation
					if ( isset($amzprods['olprods']["$key"]) && ! isset($amzprods['olvar']["$key"]) ) {
						$p_type = 'external';
					}
				}

				if ( 'simple' == $p_type ) {
					if ( isset($amzprods['var']["$key"], $amzprods['var']["$key"]->_nb_found)
						&& $amzprods['var']["$key"]->_nb_found
					) {
						$p_type = 'variable';
					}
				}
				//var_dump('<pre>',$key, $p_type ,'</pre>');

				// doesn't seem to be used in woocommerce new version! /note on: 2015-07-14
				//delete_transient( "woocommerce_product_type_$key" );
				//set_transient( "woocommerce_product_type_$key", $p_type );

				delete_transient( "wc_product_type_$key" );
				set_transient( "wc_product_type_$key", $p_type );

				wp_set_object_terms( $key, $p_type, 'product_type');

			} // end foreach
		}

		public function fixPlusParseStr ( $input=array(), $type='string' )
		{
			if($type == 'array'){
				if(count($input) > 0){
					$ret_arr = array();
					foreach ($input as $key => $value){
						$ret_arr[$key] = str_replace("###", '+', $value);
					}

					return $ret_arr;
				}

				return $input;
			}else{
				return str_replace('+', '###', $input);
			}
		}

		// saving the options
		public function save_options ()
		{
			// remove action from request
			unset($_REQUEST['action']);

			// unserialize the request options
			$serializedData = $this->fixPlusParseStr(urldecode($_REQUEST['options']));

			$savingOptionsArr = array();

			parse_str($serializedData, $savingOptionsArr);

			$savingOptionsArr = $this->fixPlusParseStr( $savingOptionsArr, 'array');

			// create save_id and remote the box_id from array
			$save_id = $savingOptionsArr['box_id'];
			unset($savingOptionsArr['box_id']);

			// Verify that correct nonce was used with time limit.
			if( ! wp_verify_nonce( $savingOptionsArr['box_nonce'], $save_id . '-nonce')) die ('Busted!');
			unset($savingOptionsArr['box_nonce']);

			// remove the white space before asin
			if ( $save_id == 'WooZoneLite_amazon' ) {

				if( isset($_SESSION['WooZoneLite_country']) ){
					unset( $_SESSION['WooZoneLite_country'] );
				}

				$_savingOptionsArr = $savingOptionsArr;
				$savingOptionsArr = array();
				foreach ($_savingOptionsArr as $key => $value) {
					if ( ! is_array($value) ) {
						// Check for and remove mistake in string after copy/paste keys
						//if( $key == 'AccessKeyID' || $key == 'SecretAccessKey' ) {
						//	if( stristr($value, 'AWSAccessKeyId=') !== false ) {
						//		$value = str_ireplace('AWSAccessKeyId=', '', $value);
						//	}
						//	if( stristr($value, 'AWSSecretKey=') !== false ) {
						//		$value = str_ireplace('AWSSecretKey=', '', $value);
						//	}
						//}
						// update in 2018-mar-06
						if ( $key == 'AccessKeyID' || $key == 'SecretAccessKey' ) {
							unset( $savingOptionsArr[$key] );
						}
						else {
							$savingOptionsArr[$key] = trim($value);
						}
					} else {
						$savingOptionsArr[$key] = $value;
					}
				}

				$settings = get_option( $this->alias . '_amazon' ); // 'WooZoneLite_amazon'
				$settings = maybe_unserialize( $settings );
				$settings = !empty($settings) && is_array($settings) ? $settings : array();

				foreach ( array('AccessKeyID', 'SecretAccessKey') as $awsKeyId ) {
					if ( isset($settings["$awsKeyId"]) ) {
						$savingOptionsArr["$awsKeyId"] = $settings["$awsKeyId"];
					}
				}
			}

			/*if ( $save_id == 'WooZoneLite_report' ) {
				$__old_saving = get_option('WooZoneLite_report', array());
				$__old_saving = maybe_unserialize(maybe_unserialize($__old_saving));
				$__old_saving = (array) $__old_saving;

				$savingOptionsArr["report"] = $__old_saving["report"];
			}*/

			// prepare the data for DB update
			$saveIntoDb = $savingOptionsArr;

			// Use the function update_option() to update a named option/value pair to the options database table. The option_name value is escaped with $wpdb->escape before the INSERT statement.
			update_option( $save_id, $saveIntoDb );

			$this->settings();

			// check for onsite cart option
			// 'WooZoneLite_amazon'
			if( $save_id == $this->alias . '_amazon' ){
				$this->update_products_type( array(
					'products' => 'all'
				));
			}

			die(json_encode( array(
				'status' => 'ok',
				'html'   => 'Options updated successfully'
			)));
		}

		// saving the options
		public function install_default_options ()
		{
			// remove action from request
			unset($_REQUEST['action']);

			$is_makeinstall = isset($_REQUEST['is_makeinstall']) ? (int) $_REQUEST['is_makeinstall'] : 0;

			// unserialize the request options
			$serializedData = urldecode($_REQUEST['options']);

			$savingOptionsArr = array();
			parse_str($serializedData, $savingOptionsArr);

			// fix for setup
			if ( $savingOptionsArr['box_id'] == 'WooZoneLite_setup_box' ) {
				$serializedData = preg_replace('/box_id=WooZoneLite_setup_box&box_nonce=[\w]*&install_box=/', '', $serializedData);
				$savingOptionsArr['install_box'] = $serializedData;
				$savingOptionsArr['install_box'] = str_replace( "\\'", "\\\\'", $savingOptionsArr['install_box']);
			}

			// create save_id and remote the box_id from array
			$save_id = $savingOptionsArr['box_id'];
			unset($savingOptionsArr['box_id']);

			// Verify that correct nonce was used with time limit.
			if( ! wp_verify_nonce( $savingOptionsArr['box_nonce'], $save_id . '-nonce')) die ('Busted!');
			unset($savingOptionsArr['box_nonce']);

			// default sql - tables & tables data!
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'modules/setup_backup/default-sql.php');

			//if ( $save_id != 'WooZoneLite_setup_box' ) { //2016-june-21 fix
				$savingOptionsArr['install_box'] = str_replace( '\"', '"', $savingOptionsArr['install_box']);
			//}

			// convert to array
			$savingOptionsArr['install_box'] = str_replace('#!#', '&', $savingOptionsArr['install_box']);
			$savingOptionsArr['install_box'] = str_replace("'", "\'", $savingOptionsArr['install_box']);
			$pullOutArray = json_decode( $savingOptionsArr['install_box'], true );
			if(count($pullOutArray) == 0){
				die(json_encode( array(
					'status' => 'error',
					'html'   => "Invalid install default json string, can't parse it!"
				)));
			}else{

				foreach ($pullOutArray as $key => $value){

					// prepare the data for DB update
					$saveIntoDb = $value;
					$saveIntoDb = is_bool($value) ? ( $value ? 'true' : 'false' ) : $value; //2016-june-21 fix

					//if( $saveIntoDb === true ){
					//  $saveIntoDb = 'true';
					//} else if( $saveIntoDb === false ){
					//  $saveIntoDb = 'false';
					//}

					// prepare the data for DB update
					//$saveIntoDb = $value != "true" ? serialize( $value ) : $value; //2016-june-21 fix

					//if ( 'WooZoneLite_amazon' == $key ) {
					//    $saveIntoDb = $this->amazon_config_with_default( $value );
					//}

					// option already exists in db => don't overwrite it!
					if  ( $is_makeinstall && get_option( $key, false ) ) {
						//var_dump('<pre>',$key, 'exists in db.', '</pre>');
						continue 1;
					}

					//var_dump('<pre>',$key, 'not found.', '</pre>');

					// Use the function update_option() to update a named option/value pair to the options database table. The option_name value is escaped with $wpdb->escape before the INSERT statement.
					update_option( $key, $saveIntoDb );
				}

				// update is_installed value to true
				update_option( $this->alias . "_is_installed", 'true');

				die(json_encode( array(
					'status' => 'ok',
					'html'   => 'Install default successful'
				)));
			}
		}

		public function options_validate ( $input )
		{
			//var_dump('<pre>', $input  , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}

		public function module_change_status ( $resp='ajax' )
		{
			// remove action from request
			unset($_REQUEST['action']);

			// update into DB the new status
			$db_alias = $this->alias . '_module_' . $_REQUEST['module'];
			update_option( $db_alias, $_REQUEST['the_status'] );

			if ( !isset($resp) || empty($resp) || $resp == 'ajax' ) {
				die(json_encode(array(
					'status' => 'ok'
				)));
			}
		}

		public function module_bulk_change_status ()
		{
			global $wpdb; // this is how you get access to the database

			$request = array(
				'id'            => isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? trim($_REQUEST['id']) : ''
			);

			if (trim($request['id'])!='') {
				$__rq2 = array();
				$__rq = explode(',', $request['id']);
				if (is_array($__rq) && count($__rq)>0) {
					foreach ($__rq as $k=>$v) {
						$__rq2[] = (string) $v;
					}
				} else {
					$__rq2[] = $__rq;
				}
				$request['id'] = implode(',', $__rq2);
			}

			if (is_array($__rq2) && count($__rq2)>0) {
				foreach ($__rq2 as $kk=>$vv) {
					$_REQUEST['module'] = $vv;
					$this->module_change_status( 'non-ajax' );
				}

				die( json_encode(array(
					'status' => 'valid',
					'msg'    => 'valid module change status Bulk'
				)) );
			}

			die( json_encode(array(
				'status' => 'invalid',
				'msg'    => 'invalid module change status Bulk'
			)) );
		}

		// loading the requested section
		public function load_section ()
		{
			$request = array(
				'section' => isset($_REQUEST['section']) ? strip_tags($_REQUEST['section']) : false
			);

			if( isset($request['section']) && $request['section'] == 'insane_mode' ){
				die( json_encode(array(
					'status' => 'redirect',
					'url'   => admin_url( 'admin.php?page=WooZoneLite_insane_import' )
				)));
			}

			// get module if isset
			$activated_modules = isset($this->cfg['activate_modules']) ? $this->cfg['activate_modules'] : array();
			if ( ! in_array( $request['section'], $activated_modules) ) {
				die( json_encode(array('status' => 'err', 'msg' => 'invalid section want to load!')) );
			}

			$tryed_module = $this->cfg['modules'][$request['section']];

			if( isset($tryed_module) && count($tryed_module) > 0 ){
				// Turn on output buffering
				ob_start();

				$opt_file_path = $tryed_module['folder_path'] . 'options.php';
				if( is_file($opt_file_path) ) {
					require_once( $opt_file_path  );
				}
				$options = ob_get_clean(); //copy current buffer contents into $message variable and delete current output buffer

				if(trim($options) != "") {
					$options = json_decode($options, true);

					// Derive the current path and load up aaInterfaceTemplates
					$plugin_path = dirname(__FILE__) . '/';
					if(class_exists('aaInterfaceTemplates') != true) {
						require_once($plugin_path . 'settings-template.class.php');

						// Initalize the your aaInterfaceTemplates
						$aaInterfaceTemplates = new aaInterfaceTemplates($this->cfg);

						// then build the html, and return it as string
						$html = $aaInterfaceTemplates->build_page($options, $this->alias, $tryed_module);

						// fix some URI
						$html = str_replace('{plugin_folder_uri}', $tryed_module['folder_uri'], $html);

						if(trim($html) != "") {
							$headline = '';
							if( isset($tryed_module[$request['section']]['in_dashboard']['icon']) ){
								$headline .= '<img src="' . ($tryed_module['folder_uri'] . $tryed_module[$request['section']]['in_dashboard']['icon'] ) . '" class="WooZoneLite-headline-icon">';
							}
							$headline .= $tryed_module[$request['section']]['menu']['title'] . "<span class='WooZoneLite-section-info'>" . ( $tryed_module[$request['section']]['description'] ) . "</span>";

							$has_help = isset($tryed_module[$request['section']]['help']) ? true : false;
							if( $has_help === true ){

								$help_type = isset($tryed_module[$request['section']]['help']['type']) && $tryed_module[$request['section']]['help']['type'] ? 'remote' : 'local';
								if( $help_type == 'remote' ){
									$headline .= '<a href="#load_docs" class="WooZoneLite-show-docs" data-helptype="' . ( $help_type ) . '" data-url="' . ( $tryed_module[$request['section']]['help']['url'] ) . '" data-operation="help">HELP</a>';
								}
							}

							$headline .= '<a href="#load_docs" class="WooZoneLite-show-feedback" data-helptype="' . ( 'remote' ) . '" data-url="' . ( $this->feedback_url ) . '" data-operation="feedback">Feedback</a>';

							die( json_encode(array(
								'status'    => 'ok',
								'headline'  => $headline,
								'html'      =>  $html
							)) );
						}

						die(json_encode(array('status' => 'err', 'msg' => 'invalid html formatter!')));
					}
				}
			}
		}

		public function fatal_errors()
		{
			$_errors = array();

			// get fatal errors
			if ( is_wp_error( $this->errors ) ) {
				$_errors = $this->errors->get_error_messages('fatal');
			}

			// print errors
			if ( ! empty($_errors) && is_array($_errors) ) {
				foreach ($_errors as $key => $value) {
					echo '<div class="error"> <p>' . ( $value ) . '</p> </div>';
				}
			}
		}

		public function admin_warnings()
		{
			$_errors = array();

			// get warnings
			if ( is_wp_error( $this->errors ) ) {
				$_errors = $this->errors->get_error_messages('warning');
			}

			//:: start notices
			$current = get_option( $this->alias . "_dismiss_notice", array() );
			$current = !empty($current) && is_array($current) ? $current : array();
			//$is_dissmised = get_option( $this->alias . "_dismiss_notice" );

			// recommanded theme
			$theme_name = wp_get_theme(); //get_current_theme() - deprecated notice!

			if ( ! in_array($theme_name->get( 'Name' ), array(
				//'Kingdom - Woocommerce Amazon Affiliates Theme',
				'Kingdom',
				'Kingdom Child Theme',
				'BravoStore',
				'BravoStore Child Theme',
				'TheMarket',
				'The Market Child Theme',
			)) ) {

				if ( !isset($current['theme']) || !$current['theme'] ) {
					$_errors[] = '
						<div class="woozonelite-themes">
							<div class="woozonelite-themesimgs">

								<a href="https://themeforest.net/item/bravostore-wzone-affiliates-theme-for-wordpress/20701838?ref=AA-Team" target="_blank">
									<img src="' . ( $this->cfg['paths']['plugin_dir_url'] . 'bravostore-theme.jpg' ) . '" />
									<h3>BravoStore</h3>
								</a>

								<a href="http://themeforest.net/item/kingdom-woocommerce-amazon-affiliates-theme/15163199?ref=AA-Team" target="_blank">
									<img src="' . ( $this->cfg['paths']['plugin_dir_url'] . 'kingdom-theme.jpg' ) . '" />
									<h3>Kingdom</h3>
								</a>

								<a href="http://codecanyon.net/item/the-market-woozonelite-affiliates-theme/13469852?ref=AA-Team" target="_blank">
									<img src="' . ( $this->cfg['paths']['plugin_dir_url'] . 'themarket-theme.jpg' ) . '" />
									<h3>The Market</h3>
								</a>
							</div>
							<p>For the <strong>Best Possible User Experience with the WooZoneLite Plugin</strong> we highly Recommend using it in conjunction with any of the AA-Team custom Themes.</p>
						</div>
						<p>
							<strong>
								<a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLiteDismissNotice&id=theme' ) ) . '" target="_parent">Dismiss this notice</a>
							</strong>
						</p>
					';
				}
			}

			// memory limit notice
			$memory = $this->let_to_num( WP_MEMORY_LIMIT );
			if ( $memory < 127108864 ) {
				if( !isset($current['memorylimit']) || !$current['memorylimit'] ){
					$_errors[] = '<p><strong style="color: red;">Current memory limit: ' . size_format( $memory ) . '</strong> | We recommend setting memory to at least 128MB. See: <a href="http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP">Increasing memory allocated to PHP</a> | <a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLiteDismissNotice&id=memorylimit' ) ) . '" target="_parent">Dismiss this notice</a></p>';
				}
			}

			// soap module notice
			if ( extension_loaded('soap') || class_exists("SOAPClient") || class_exists("SOAP_Client") ) ;
			else {
				if( !isset($current['soap']) || !$current['soap'] ){
					$_errors[] = '<p>Your server does not have the <a href="http://php.net/manual/en/class.soapclient.php">SOAP Client</a> class enabled - some gateway plugins which use SOAP may not work as expected. | <a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLiteDismissNotice&id=soap' ) ) . '" target="_parent">Dismiss this notice</a></p>';
				}
			}

			// woocommerce pages/shortcodes check
			$check_pages = array(
				_x( 'Cart Page', 'Page setting', 'woocommerce' ) => array(
						'option' => 'woocommerce_cart_page_id',
						'shortcode' => '[' . apply_filters( 'woocommerce_cart_shortcode_tag', 'woocommerce_cart' ) . ']'
					),
				_x( 'Checkout Page', 'Page setting', 'woocommerce' ) => array(
						'option' => 'woocommerce_checkout_page_id',
						'shortcode' => '[' . apply_filters( 'woocommerce_checkout_shortcode_tag', 'woocommerce_checkout' ) . ']'
					),
			);
			if ( class_exists( 'WooCommerce' ) ) {

				foreach ( $check_pages as $page_name => $values ) {

					$page_id = get_option( $values['option'], false );

					// Page ID check
					if ( ! $page_id ) {
						$_errors[] = '<p>' . sprintf( __( 'You need to install default WooCommerce page: %s', 'woozonelite' ), $page_name ) . '.</p>';
					} else {
						// Shortcode check
						if ( $values['shortcode'] ) {
							$page = get_post( $page_id );
							$_wpnonce_untrash = wp_create_nonce( 'untrash-post_' . $page->ID );

							//var_dump('<pre>',$page ,'</pre>');
							if ( empty( $page ) ) {
								if( !isset($current['pageinstall']) || !$current['pageinstall'] ){
									$_errors[] = '<p><strong>Cart / Checkout</strong> page does not exist. Please install Woocommerce default pages from <a href="' . admin_url('admin.php?page=wc-status&tab=tools') . '" target="_blank">here</a>. | <a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLiteDismissNotice&id=pageinstall' ) ) . '" target="_parent">Dismiss this notice</a></p>';
								}
							} elseif ( ! strstr( $page->post_content, $values['shortcode'] ) ) {
								if( !isset($current['pageshortcode']) || !$current['pageshortcode'] ){
									$_errors[] = '<p>The <strong>' . $page->post_title . '</strong> page does not contain the shortcode: <strong>' . $values['shortcode'] . '</strong> | <a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLiteDismissNotice&id=pageshortcode' ) ) . '" target="_parent">Dismiss this notice</a></p>';
								}
							} elseif ( $page->post_status == 'trash' ) {
								if( !isset($current['pagetrash']) || !$current['pagetrash'] ){
									$_errors[] = '<p>The <strong>' . $page->post_title . '</strong> Woocommerce default page is in trash. Please <a href="' . admin_url('post.php?post=' . $page_id . '&action=untrash&_wpnonce=' . $_wpnonce_untrash) . '">restore it</a>. | <a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLiteDismissNotice&id=pagetrash' ) ) . '" target="_parent">Dismiss this notice</a></p>';
								}
							}
						}

					}

					//$_errors[] = '<p>#' . absint( $page_id ) . ' - ' . str_replace( home_url(), '', get_permalink( $page_id ) ) . '</p>';
				}
			}
			//:: end notices

			// Additional Variation Images Plugin for WooCommerce
			// https://codecanyon.net/item/additional-variation-images-plugin-for-woocommerce/22035959
			if ( !isset($current['plugin_avi']) || !$current['plugin_avi'] ) {
				if ( ! $this->is_plugin_avi_active() ) {
					$_errors[] = '

						<div class="woozonelite-themes">
							<div class="aat-musthave"></div>
							<div class="woozonelite-themesimgs">
								<h2>Good to know!</h2>
								<p>Do you want more Content for your forthcoming Imported Products? You can <strong>Automatically Import up to ' . ( $this->ss['max_images_per_variation'] ) . ' Additional Images for each Variation</strong> by using <br/> <strong>WZoneLite and this NEW Awesome Wordpress Plugin:</strong> <a href="https://1.envato.market/03BQY" target="_blank">Additional Variation Images for WooCommerce!</a> <strong style="color:#009f1e;">Get it for only 20$! </strong>
								</p>

								<a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLiteDismissNotice&id=plugin_avi' ) ) . '" target="_parent">Dismiss this notice</a>

							</div>
						</div>
					';
				}
			}

			// Compareazon
			if ( !isset($current['plugin_CT']) || !$current['plugin_CT'] ) {
				if ( ! $this->is_plugin_CT_active() ) {
					$_errors[] = '

						<div class="woozonelite-themes">
							<div class="aat-musthave"></div>
							<div class="woozonelite-themesimgs">
								<h2>Good to know!</h2>
								<p><strong> Search, Embed and Compare Amazon Products! </strong> Make your customer´s life easier and allow them to make a Comparison among products in the easiest and most efficient way ever! <a href="
https://1.envato.market/1LZM6" target="_blank">CompareAzon - Amazon Product Comparison Tables</a> <strong style="color:#009f1e;">Get it for only 19$! </strong>
<br/><br/>
 <strong>GProducts allows you to embed and display several Amazon products and provide relevant information – product details – rating, price, description, free shipping, prime ready & more into a great looking box! </strong>  <a href="https://1.envato.market/kEbD3" target="_blank"> GProducts - Amazon Affiliates Products Boxes Block </a> <strong style="color:#009f1e;">Get it for only 15$! </strong>
								</p>

								<a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLiteDismissNotice&id=plugin_CT' ) ) . '" target="_parent">Dismiss this notice</a>

							</div>
						</div>
					';
				}
			}


			// print errors
			if ( ! empty($_errors) && is_array($_errors) ) {
				foreach ($_errors as $key => $value) {
					echo '<div class="updated aat-design"> ' . ( $value ) . '</div>';
				}
			}
		}

		public function let_to_num($size) {
			if ( function_exists('wc_let_to_num') ) {
				return wc_let_to_num( $size );
			}

			$l = substr($size, -1);
			$ret = substr($size, 0, -1);
			switch( strtoupper( $l ) ) {
				case 'P' :
					$ret *= 1024;
				case 'T' :
					$ret *= 1024;
				case 'G' :
					$ret *= 1024;
				case 'M' :
					$ret *= 1024;
				case 'K' :
					$ret *= 1024;
			}
			return $ret;
		}

		/**
		 * Builds the config parameters
		 *
		 * @param string $function
		 * @param array $params
		 *
		 * @return array
		 */
		protected function buildConfigParams($type, array $params)
		{
			// check if array exist
			if(isset($this->cfg[$type])){
				$params = array_merge( $this->cfg[$type], $params );
			}

			// now merge the arrays
			$this->cfg = array_merge(
				$this->cfg,
				array(  $type => array_merge( $params ) )
			);
		}

		/*
		 * admin_load_styles()
		 *
		 * Loads admin-facing CSS
		 */
		public function admin_get_frm_style() {
			$css = array();

			if( isset($this->cfg['freamwork-css-files'])
				&& is_array($this->cfg['freamwork-css-files'])
				&& !empty($this->cfg['freamwork-css-files'])
			) {

				foreach ($this->cfg['freamwork-css-files'] as $key => $value){
					if( is_file($this->cfg['paths']['freamwork_dir_path'] . $value) ) {

						$cssId = $this->alias . '-' . $key;
						$css["$cssId"] = $this->cfg['paths']['freamwork_dir_path'] . $value;
						// wp_enqueue_style( $this->alias . '-' . $key, $this->plugin_asset_get_path( 'css', $this->cfg['paths']['freamwork_dir_url'] . $value, true ), array(), $this->plugin_asset_get_version( 'css' ) );
					} else {
						$this->errors->add( 'warning', __('Invalid CSS path to file: <strong>' . $this->cfg['paths']['freamwork_dir_path'] . $value . '</strong>. Call in:' . __FILE__ . ":" . __LINE__ , 'woozonelite') );
					}
				}
			}
			return $css;
		}

		public function admin_load_styles()
		{
			global $wp_scripts;
			$protocol = is_ssl() ? 'https' : 'http';

			$javascript = $this->admin_get_scripts();

			wp_enqueue_style( $this->alias . '-google-Roboto',  $this->plugin_asset_get_path( 'css', $protocol . '://fonts.googleapis.com/css?family=Roboto:400,500,400italic,500italic,700,700italic', true ), array(), $this->plugin_asset_get_version( 'css' ) );

			//wp_enqueue_style( $this->alias . '-bootstrap', $this->plugin_asset_get_path( 'css', $protocol . '://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css', true ), array(), $this->plugin_asset_get_version( 'css' ) );
			$font_awesome = $protocol . '://maxcdn.bootstrapcdn.com/font-awesome/4.6.2/css/font-awesome.min.css';
			$font_awesome_cached = $this->cfg['paths']['freamwork_dir_path'] . 'css/font-awesome-v4.6.2.min.css';
			clearstatcache();
			if( is_file( $font_awesome_cached ) && is_readable( $font_awesome_cached ) ) {
				$font_awesome = $this->cfg['paths']['freamwork_dir_url'] . 'css/font-awesome-v4.6.2.min.css';
			}
			wp_enqueue_style( $this->alias . '-font-awesome', $this->plugin_asset_get_path( 'css', $font_awesome, true ), array(), $this->plugin_asset_get_version( 'css' ) );

			//tippyjs
			wp_enqueue_style( $this->alias . '-tippyjs', $this->plugin_asset_get_path( 'css', $this->cfg['paths']['freamwork_dir_url'] . 'js/tippyjs/tippy.min.css', true ), array(), $this->plugin_asset_get_version( 'css' ) );

			$main_style = admin_url('admin-ajax.php?action=WooZoneLite_framework_style');
			$main_style_cached = $this->cfg['paths']['freamwork_dir_path'] . 'main-style.css';
			if( is_file( $main_style_cached ) ) {
				if(
					-1 === $this->ss['css_cache_time'] //always use cache
					||
					(filemtime($main_style_cached) + $this->ss['css_cache_time']) > time()
				 ) {
					$main_style = $this->cfg['paths']['freamwork_dir_url'] . 'main-style.css';
				}
			}


			// !!! debug - please in the future, don't forget to comment it after you're finished with debugging
			//$main_style = admin_url('admin-ajax.php?action=WooZoneLite_framework_style');

			wp_enqueue_style( $this->alias . '-main-style', $this->plugin_asset_get_path( 'css', $main_style, true ), array( $this->alias . '-font-awesome' ) );

			/*$style_url = $this->cfg['paths']['freamwork_dir_url'] . 'load-styles.php';
			if ( is_file( $this->cfg['paths']['freamwork_dir_path'] . 'load-styles.css' ) ) {
				$style_url = str_replace('.php', '.css', $style_url);
			}
			wp_enqueue_style( 'woozonelite-aa-framework-styles', $this->plugin_asset_get_path( 'css', $style_url, true ), array(), , $this->plugin_asset_get_version( 'css' ) );*/

			if( in_array( 'jquery-ui-core', $javascript ) ) {
				$ui = $wp_scripts->query('jquery-ui-core');
				if ($ui) {
					$uiBase = "http://code.jquery.com/ui/{$ui->ver}/themes/smoothness";
					wp_register_style('jquery-ui-core', "$uiBase/jquery-ui.css", FALSE, $ui->ver);
					wp_enqueue_style('jquery-ui-core');
				}
			}
			if( in_array( 'thickbox', $javascript ) ) wp_enqueue_style('thickbox');
		}

		/*
		 * admin_load_scripts()
		 *
		 * Loads admin-facing JavaScript
		 */
		public function admin_get_scripts() {
			$javascript = array();

			$current_url = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
			$current_url = explode("wp-admin/", $current_url);
			if( count($current_url) > 1 ){
				$current_url = "/wp-admin/" . $current_url[1];
			}else{
				$current_url = "/wp-admin/" . $current_url[0];
			}

			if ( isset($this->cfg['modules'])
				&& is_array($this->cfg['modules']) && !empty($this->cfg['modules'])
			) {
			foreach( $this->cfg['modules'] as $alias => $module ){

				if( isset($module[$alias]["load_in"]['backend']) && is_array($module[$alias]["load_in"]['backend']) && count($module[$alias]["load_in"]['backend']) > 0 ){
					// search into module for current module base on request uri
					foreach ( $module[$alias]["load_in"]['backend'] as $page ) {

						$expPregQuote = ( is_array($page) ? false : true );
							if ( is_array($page) ) $page = $page[0];

						$delimiterFound = strpos($page, '#');
						$page = substr($page, 0, ($delimiterFound!==false && $delimiterFound > 0 ? $delimiterFound : strlen($page)) );
						$urlfound = preg_match("%^/wp-admin/".($expPregQuote ? preg_quote($page) : $page)."%", $current_url);

						if(
							// $current_url == '/wp-admin/' . $page
							( ( $page == '@all' ) || ( $current_url == '/wp-admin/admin.php?page=WooZoneLite' ) || ( !empty($page) && $urlfound > 0 ) )
							&& isset($module[$alias]['javascript']) ) {

							$javascript = array_merge($javascript, $module[$alias]['javascript']);
						}
					}
				}
			}
			} // end if

			$this->jsFiles = $javascript;
			return $javascript;
		}
		public function admin_load_scripts()
		{
			// very defaults scripts (in wordpress defaults)
			wp_enqueue_script( 'jquery' );

			$javascript = $this->admin_get_scripts();

			if ( count($javascript) > 0 ) {
				$javascript = @array_unique( $javascript );

				if( in_array( 'jquery-ui-core', $javascript ) ) wp_enqueue_script( 'jquery-ui-core' );
				if( in_array( 'jquery-ui-widget', $javascript ) ) wp_enqueue_script( 'jquery-ui-widget' );
				if( in_array( 'jquery-ui-mouse', $javascript ) ) wp_enqueue_script( 'jquery-ui-mouse' );
				if( in_array( 'jquery-ui-accordion', $javascript ) ) wp_enqueue_script( 'jquery-ui-accordion' );
				if( in_array( 'jquery-ui-autocomplete', $javascript ) ) wp_enqueue_script( 'jquery-ui-autocomplete' );
				if( in_array( 'jquery-ui-slider', $javascript ) ) wp_enqueue_script( 'jquery-ui-slider' );
				if( in_array( 'jquery-ui-tabs', $javascript ) ) wp_enqueue_script( 'jquery-ui-tabs' );
				if( in_array( 'jquery-ui-sortable', $javascript ) ) wp_enqueue_script( 'jquery-ui-sortable' );
				if( in_array( 'jquery-ui-draggable', $javascript ) ) wp_enqueue_script( 'jquery-ui-draggable' );
				if( in_array( 'jquery-ui-droppable', $javascript ) ) wp_enqueue_script( 'jquery-ui-droppable' );
				if( in_array( 'jquery-ui-datepicker', $javascript ) ) wp_enqueue_script( 'jquery-ui-datepicker' );
				if( in_array( 'jquery-ui-resize', $javascript ) ) wp_enqueue_script( 'jquery-ui-resize' );
				if( in_array( 'jquery-ui-dialog', $javascript ) ) wp_enqueue_script( 'jquery-ui-dialog' );
				if( in_array( 'jquery-ui-button', $javascript ) ) wp_enqueue_script( 'jquery-ui-button' );

				if( in_array( 'thickbox', $javascript ) ) wp_enqueue_script( 'thickbox' );

				// date & time picker
				if( !wp_script_is('jquery-timepicker') ) {
					if ( in_array( 'jquery-timepicker', $javascript ) ) {
						wp_enqueue_script( 'jquery-timepicker' , $this->plugin_asset_get_path( 'js', $this->cfg['paths']['freamwork_dir_url'] . 'js/jquery.timepicker.v1.1.1.min.js', true ), array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-slider' ), $this->plugin_asset_get_version( 'js' ) );
					}
				}

				wp_enqueue_script( 'sweetalert2-min' , $this->plugin_asset_get_path( 'js', $this->cfg['paths']['freamwork_dir_url'] . 'js/sweetalert2.min.js', true ), array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-slider' ), $this->plugin_asset_get_version( 'js' ) );
			}

			if ( count($this->cfg['freamwork-js-files']) > 0 ) {
				foreach ($this->cfg['freamwork-js-files'] as $key => $value) {

					if ( is_file($this->cfg['paths']['freamwork_dir_path'] . $value) ) {
						if( in_array( $key, $javascript ) ) {
							wp_enqueue_script( $this->alias . '-' . $key, $this->plugin_asset_get_path( 'js', $this->cfg['paths']['freamwork_dir_url'] . $value, true ), array(), $this->plugin_asset_get_version( 'js' ) );
						}
					} else {
						$this->errors->add( 'warning', __('Invalid JS path to file: <strong>' . $this->cfg['paths']['freamwork_dir_path'] . $value . '</strong> . Call in:' . __FILE__ . ":" . __LINE__ , 'woozonelite') );
					}
				}
			}
		}

		/*
		 * Builds out the options panel.
		 *
		 * If we were using the Settings API as it was likely intended we would use
		 * do_settings_sections here. But as we don't want the settings wrapped in a table,
		 * we'll call our own custom wplanner_fields. See options-interface.php
		 * for specifics on how each individual field is generated.
		 *
		 * Nonces are provided using the settings_fields()
		 *
		 * @param array $params
		 * @param array $options (fields)
		 *
		 */
		public function createDashboardPage ()
		{
			add_menu_page(
				__( 'WZone Lite - WooCommerce Amazon Affiliates', 'woozonelite' ),
				__( 'WZone Lite', 'woozonelite' ),
				'manage_options',
				$this->alias,
				array( $this, 'manage_options_template' ),
				$this->cfg['paths']['plugin_dir_url'] . 'icon_16.png'
			);

			add_submenu_page(
					$this->alias,
					$this->alias . " " . __('Plugin configuration', 'woozonelite'),
							__('Config', 'woozonelite'),
							'manage_options',
							$this->alias . "&section=amazon",
							array( $this, 'manage_options_template')
					);


			if( $this->verify_module_status('advanced_search') == true ) {
				add_submenu_page(
						$this->alias,
						$this->alias . " " . __('Amazon Advanced Search', 'woozonelite'),
								__('Amazon Search', 'woozonelite'),
								'manage_options',
								$this->alias . "&section=advanced_search",
								array( $this, 'manage_options_template')
						);
			}

			add_submenu_page(
					$this->alias,
					$this->alias . " " . __('Import Insane Mode', 'woozonelite'),
							__('Insane Mode Import', 'woozonelite'),
							'manage_options',
							$this->alias . "&section=insane_mode",
							array( $this, 'insane_import_redirect')
					);

			if( $this->verify_module_status('csv_products_import') == true ) {
				add_submenu_page(
						$this->alias,
						$this->alias . " " . __('CSV bulk products import', 'woozonelite'),
								__('CSV import', 'woozonelite'),
								'manage_options',
								$this->alias . "&section=csv_products_import",
								array( $this, 'manage_options_template')
						);
			}
		}

		public function manage_options_template()
		{
			// Derive the current path and load up aaInterfaceTemplates
			$plugin_path = dirname(__FILE__) . '/';
			if(class_exists('aaInterfaceTemplates') != true) {
				require_once($plugin_path . 'settings-template.class.php');

				// Initalize the your aaInterfaceTemplates
				$aaInterfaceTemplates = new aaInterfaceTemplates($this->cfg);

				// try to init the interface
				$aaInterfaceTemplates->printBaseInterface();
			}
		}

		public function insane_import_redirect()
		{
			echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}

		/**
		 * Getter function, plugin config
		 *
		 * @return array
		 */
		public function getCfg()
		{
			return $this->cfg;
		}

		/**
		 * Getter function, plugin all settings
		 *
		 * @params $returnType
		 * @return array
		 */
		public function getAllSettings( $returnType='array', $only_box='', $this_call=false )
		{
			if( $this_call == true ){
				//var_dump('<pre>',$returnType, $only_box,'</pre>');
			}
			$allSettingsQuery = "SELECT * FROM " . $this->db->prefix . "options where 1=1 and option_name REGEXP '" . ( $this->alias) . "_([a-z])'";
			if (trim($only_box) != "") {
				$allSettingsQuery = "SELECT * FROM " . $this->db->prefix . "options where 1=1 and option_name = '" . ( $this->alias . '_' . $only_box) . "' LIMIT 1;";
			}
			$results = $this->db->get_results( $allSettingsQuery, ARRAY_A);

			// prepare the return
			$return = array();
			if( count($results) > 0 ){
				foreach ($results as $key => $value){
					if($value['option_value'] == 'true'){
						$return[$value['option_name']] = true;
					}else{
						//$return[$value['option_name']] = @unserialize(@unserialize($value['option_value']));
						$return[$value['option_name']] = maybe_unserialize($value['option_value']);
					}
				}
			}

			if(trim($only_box) != "" && isset($return[$this->alias . '_' . $only_box])){
				$return = $return[$this->alias . '_' . $only_box];
			}

			if($returnType == 'serialize'){
				return serialize($return);
			}else if( $returnType == 'array' ){
				return maybe_unserialize( $return );
			}else if( $returnType == 'json' ){
				return json_encode($return);
			}

			return false;
		}

		/**
		 * Getter function, all products
		 *
		 * @params $returnType
		 * @return array
		 */
		public function getAllProductsMeta( $returnType='array', $key='', $withPrefix=true, $ws='all' )
		{
			$_key = $key;
			if ( $_key == '_amzASIN' ) $key = '_amzaff_prodid';

			$q_ws = " AND ( ( a.meta_value regexp '^amz-' and b.post_type in ('product', 'product_variation') ) OR ( a.meta_value not regexp '^amz-' and b.post_type = 'product' ) ) ";

			if ( $ws != 'all' ) {
				if ( 'amz' == $ws ) {
					$q_ws = " AND a.meta_value regexp '^$ws-' and b.post_type in ('product', 'product_variation') ";
				}
				else {
					$q_ws = " AND a.meta_value regexp '^$ws-' and b.post_type in ('product') ";
				}
			}

			// prepare the return
			$return = array();

			$allSettingsQuery = "SELECT a.meta_value FROM " . $this->db->prefix . "postmeta AS a LEFT OUTER JOIN " . $this->db->prefix . "posts AS b ON a.post_id=b.ID WHERE 1=1 AND !ISNULL(b.ID) AND a.meta_key='" . $key . "' $q_ws;";
			$results = $this->db->get_results( $allSettingsQuery, ARRAY_A );
			$results = is_array($results) ? $results : array();

			if ( count($results) ) {
				foreach ($results as $vv) {
					if ( isset($vv['meta_value']) ) {
						$vv['meta_value'] = trim( $vv['meta_value'] );
						if ( $vv['meta_value'] != "" ) {
							$return[] = $vv['meta_value'];
						}
					}
				}
			}

			// because we have old amazon products which have only '_amzASIN' meta (they don't have this new '_amzaff_prodid' meta)
			if ( in_array($ws, array('amz', 'all')) ) {

				$allSettingsQuery = "SELECT a.meta_value FROM " . $this->db->prefix . "postmeta AS a LEFT OUTER JOIN " . $this->db->prefix . "posts AS b ON a.post_id=b.ID WHERE 1=1 AND a.meta_key='" . $_key . "' $q_ws AND !ISNULL(b.ID) AND b.post_type IN ('product', 'product_variation')";
				$results2 = $this->db->get_results( $allSettingsQuery, ARRAY_A );
				$results2 = is_array($results2) ? $results2 : array();

				if ( count($results2) ) {
					foreach ($results2 as $kk => $vv) {
						if ( isset($vv['meta_value']) ) {
							$_asin = $vv['meta_value'];
							$_asin = $this->prodid_set($_asin, 'amazon', 'add');

							$vv['meta_value'] = $_asin;
							$results2["$kk"]['meta_value'] = $_asin;

							if ( empty($return) || ! in_array($_asin, $return) ) {
								$return[] = $_asin;
							}
						}
					}
				}
			}

			if ( !$withPrefix && ($_key == '_amzASIN') ) {
				$wslist = $this->get_ws_prefixes();
				foreach ($return as $k => $v) {
					foreach ($wslist as $wsfull => $wsprefix) {
						//$return["$k"] = str_replace($wsprefix.'-', '', $return["$k"]);
						$return["$k"] = $this->prodid_set($return["$k"], $wsfull, 'sub');
					}
				}
			}

			if($returnType == 'serialize'){
				return serialize($return);
			}
			else if( $returnType == 'text' ){
				return implode("\n", $return);
			}
			else if( $returnType == 'array' ){
				return $return;
			}
			else if( $returnType == 'json' ){
				return json_encode($return);
			}

			return false;
		}

		/*
		 * GET modules lists
		 */
		public function load_modules( $pluginPage='' )
		{
			$GLOBALS['WooZoneLite'] = $this;

			$folder_path = $this->cfg['paths']['plugin_dir_path'] . 'modules/';
			$cfgFileName = 'config.php';

			// static usage, modules menu order
			$menu_order = array();

			$modules_list = glob($folder_path . '*/' . $cfgFileName);

			$nb_modules = count($modules_list);
			if ( $nb_modules > 0 ) {
				foreach ($modules_list as $key => $mod_path ) {

					$dashboard_isfound = preg_match("/modules\/dashboard\/config\.php$/", $mod_path);
					$depedencies_isfound = preg_match("/modules\/depedencies\/config\.php$/", $mod_path);

					// we're on depedencies page (not module)!
					if ( $pluginPage == 'depedencies' ) {
						// load only the depedencies module!
						if ( $depedencies_isfound!==false && $depedencies_isfound>0 ) ;
						else continue 1;
					}
					// plugin page
					else {
						// move dashboard module to the end of this list of modules
						if ( $dashboard_isfound!==false && $dashboard_isfound>0 ) {
							unset($modules_list[$key]);
							$modules_list[$nb_modules] = $mod_path;
						}
					}
				}
			}

			foreach ($modules_list as $module_config ) {
				$module_folder = str_replace($cfgFileName, '', $module_config);

				// Turn on output buffering
				ob_start();

				if( is_file( $module_config ) ) {
					require_once( $module_config  );
				}
				$settings = ob_get_clean(); //copy current buffer contents into $message variable and delete current output buffer

				if(trim($settings) != "") {
					$settings = json_decode($settings, true);
					$settings_keys = array_keys($settings);
					$alias = (string)end($settings_keys);

					// create the module folder URI
					// fix for windows server
					$module_folder = str_replace( DIRECTORY_SEPARATOR, '/',  $module_folder );

					$__tmpUrlSplit = explode("/", $module_folder);
					$__tmpUrl = '';
					$nrChunk = count($__tmpUrlSplit);
					if($nrChunk > 0) {
						foreach ($__tmpUrlSplit as $key => $value){
							if( $key > ( $nrChunk - 4) && trim($value) != ""){
								$__tmpUrl .= $value . "/";
							}
						}
					}

					// get the module status. Check if it's activate or not
					$status = false;

					// default activate all core modules
					if ( $pluginPage == 'depedencies' ) {
						if ( $alias != 'depedencies' ) continue 1;
						else $status = true;
					} else {
						if ( $alias == 'depedencies' ) continue 1;

						if(in_array( $alias, $this->cfg['core-modules'] )) {
							$status = true;
						}else{
							// activate the modules from DB status
							$db_alias = $this->alias . '_module_' . $alias;

							if(get_option($db_alias) == 'true'){
								$status = true;
							}
						}
					}

					// push to modules array
					$this->cfg['modules'][$alias] = array_merge(array(
						'folder_path'   => $module_folder,
						'folder_uri'    => $this->cfg['paths']['plugin_dir_url'] . $__tmpUrl,
						'db_alias'      => $this->alias . '_' . $alias,
						'alias'         => $alias,
						'status'        => $status
					), $settings );

					// add to menu order array
					if(!isset($this->cfg['menu_order'][(int)$settings[$alias]['menu']['order']])){
						$this->cfg['menu_order'][(int)$settings[$alias]['menu']['order']] = $alias;
					}else{
						// add the menu to next free key
						$this->cfg['menu_order'][] = $alias;
					}

					// add module to activate modules array
					if($status == true){
						$this->cfg['activate_modules'][$alias] = true;
					}

					// load the init of current loop module
					if( $this->debug === true ) {
						$time_start = microtime(true);
						$start_memory_usage = (memory_get_usage());
					}

					// in backend
					if( $this->is_admin === true && isset($settings[$alias]["load_in"]['backend']) ){

						$need_to_load = false;
						if( is_array($settings[$alias]["load_in"]['backend']) && count($settings[$alias]["load_in"]['backend']) > 0 ){

							$current_url = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
							$current_url = explode("wp-admin/", $current_url);
							if( count($current_url) > 1 ){
								$current_url = "/wp-admin/" . $current_url[1];
							}else{
								$current_url = "/wp-admin/" . $current_url[0];
							}

							foreach ( $settings[$alias]["load_in"]['backend'] as $page ) {

								$expPregQuote = ( is_array($page) ? false : true );
									if ( is_array($page) ) $page = $page[0];

								$delimiterFound = strpos($page, '#');
								$page = substr($page, 0, ($delimiterFound!==false && $delimiterFound > 0 ? $delimiterFound : strlen($page)) );
								$urlfound = preg_match("%^/wp-admin/".($expPregQuote ? preg_quote($page) : $page)."%", $current_url);

								$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
								$section = isset($_REQUEST['section']) ? $_REQUEST['section'] : '';
								if(
									// $current_url == '/wp-admin/' . $page ||
									( ( $page == '@all' ) || ( $current_url == '/wp-admin/admin.php?page=WooZoneLite' ) || ( !empty($page) && $urlfound > 0 ) )
									|| ( $action == 'WooZoneLiteLoadSection' && $section == $alias )
									|| substr($action, 0, 3) == 'WooZoneLite'
								){
									$need_to_load = true;
								}
							}
						}

						if( $need_to_load == false ){
							continue;
						}
					}

					if( $this->is_admin === false && isset($settings[$alias]["load_in"]['frontend']) ){

						$need_to_load = false;
						if( $settings[$alias]["load_in"]['frontend'] === true ){
							$need_to_load = true;
						}
						if( $need_to_load == false ){
							continue;
						}
					}

					// load the init of current loop module
					//var_dump(array($alias => $this->cfg['modules'][$alias]));
					//die();
					if( $status == true && isset( $settings[$alias]['module_init'] ) ){
						if( is_file($module_folder . $settings[$alias]['module_init']) ){
							//if( $this->is_admin ) {
								$current_module = array($alias => $this->cfg['modules'][$alias]);
								$GLOBALS['WooZoneLite_current_module'] = $current_module;

								require_once( $module_folder . $settings[$alias]['module_init'] );

								if( $this->debug === true ) {
									$time_end = microtime(true);
									$this->cfg['modules'][$alias]['loaded_in'] = $time_end - $time_start;

									$this->cfg['modules'][$alias]['memory_usage'] = (memory_get_usage() ) - $start_memory_usage;
									if( (float)$this->cfg['modules'][$alias]['memory_usage'] < 0 ){
										$this->cfg['modules'][$alias]['memory_usage'] = 0.0;
									}
								}
							//}
						}
					}
				}
			}

			// order menu_order ascendent
			ksort($this->cfg['menu_order']);
		}

		public function print_plugin_usages()
		{
			$html = array();

			$html[] = '<style type="text/css">
				.WooZoneLite-bench-log {
					border: 1px solid #ccc;
					width: 450px;
					position: absolute;
					top: 92px;
					right: 2%;
					background: #95a5a6;
					color: #fff;
					font-size: 12px;
					z-index: 99999;

				}
					.WooZoneLite-bench-log th {
						font-weight: bold;
						background: #34495e;
					}
					.WooZoneLite-bench-log th,
					.WooZoneLite-bench-log td {
						padding: 4px 12px;
					}
				.WooZoneLite-bench-title {
					position: absolute;
					top: 55px;
					right: 2%;
					width: 425px;
					margin: 0px 0px 0px 0px;
					font-size: 20px;
					background: #ec5e00;
					color: #fff;
					display: block;
					padding: 7px 12px;
					line-height: 24px;
					z-index: 99999;
				}
			</style>';

			$html[] = '<h1 class="WooZoneLite-bench-title">WooZoneLite: Benchmark performance</h1>';
			$html[] = '<table class="WooZoneLite-bench-log">';
			$html[] =   '<thead>';
			$html[] =       '<tr>';
			$html[] =           '<th>Module</th>';
			$html[] =           '<th>Loading time</th>';
			$html[] =           '<th>Memory usage</th>';
			$html[] =       '</tr>';
			$html[] =   '</thead>';


			$html[] =   '<tbody>';

			$total_time = 0;
			$total_size = 0;
			foreach ($this->cfg['modules'] as $key => $module ) {

				$html[] =       '<tr>';
				$html[] =           '<td>' . ( $key ) . '</td>';
				$html[] =           '<td>' . ( number_format($module['loaded_in'], 4) ) . '(seconds)</td>';
				$html[] =           '<td>' . (  $this->formatBytes($module['memory_usage']) ) . '</td>';
				$html[] =       '</tr>';

				$total_time = $total_time + $module['loaded_in'];
				$total_size = $total_size + $module['memory_usage'];
			}

			$html[] =       '<tr>';
			$html[] =           '<td colspan="3">';
			$html[] =               'Total time: <strong>' . ( $total_time ) . '(seconds)</strong><br />';
			$html[] =               'Total Memory: <strong>' . ( $this->formatBytes($total_size) ) . '</strong><br />';
			$html[] =           '</td>';
			$html[] =       '</tr>';

			$html[] =   '</tbody>';
			$html[] = '</table>';

			//echo '<script>jQuery("body").append(\'' . ( implode("\n", $html ) ) . '\')</script>';
			echo implode("\n", $html );
		}

		public function check_secure_connection ()
		{

			$secure_connection = false;
			if(isset($_SERVER['HTTPS']))
			{
				if ($_SERVER["HTTPS"] == "on")
				{
					$secure_connection = true;
				}
			}
			return $secure_connection;
		}


		/*
			helper function, image_resize
			// use timthumb
		*/
		public function image_resize ($src='', $w=100, $h=100, $zc=2)
		{
			// in no image source send, return no image
			if( trim($src) == "" ){
				$src = $this->cfg['paths']['freamwork_dir_url'] . '/images/no-product-img.jpg';
			}

			if( is_file($this->cfg['paths']['plugin_dir_path'] . 'timthumb.php') ) {
				return $this->cfg['paths']['plugin_dir_url'] . 'timthumb.php?src=' . $src . '&w=' . $w . '&h=' . $h . '&zc=' . $zc;
			}
		}

		/*
			helper function, upload_file
		*/
		public function upload_file ()
		{
			$slider_options = '';
			 // Acts as the name
						$clickedID = $_POST['clickedID'];
						// Upload
						if ($_POST['type'] == 'upload') {
								$override['action'] = 'wp_handle_upload';
								$override['test_form'] = false;
				$filename = $_FILES [$clickedID];

								$uploaded_file = wp_handle_upload($filename, $override);
								if (!empty($uploaded_file['error'])) {
										echo json_encode(array("error" => "Upload Error: " . $uploaded_file['error']));
								} else {
										echo json_encode(array(
							"url" => $uploaded_file['url'],
							"thumb" => ($this->image_resize( $uploaded_file['url'], $_POST['thumb_w'], $_POST['thumb_h'], $_POST['thumb_zc'] ))
						)
					);
								} // Is the Response
						}else{
				echo json_encode(array("error" => "Invalid action send" ));
			}

						die();
		}

		public function download_image( $file_url='', $pid=0, $action='insert', $product_title='', $step=0 )
		{
			$file_url = trim( $file_url );
			if ( '' != $file_url ) {

				if( $this->amz_settings["rename_image"] == 'product_title' ){
					$image_name = sanitize_file_name($product_title);
					$image_name = preg_replace("/[^a-zA-Z0-9-]/", "", $image_name);
					$image_name = substr($image_name, 0, 200);
				}else{
					$image_name = uniqid();
				}

				// Find Upload dir path
				$uploads = wp_upload_dir();
				$uploads_path = $uploads['path'] . '';
				$uploads_url = $uploads['url'];

				$fileExt = explode(".", $file_url);
				$fileExt = end($fileExt);

				// ebay fix for variation images which look like bellow:
				// https://erpimgs.idealhere.com/L0ltYWdlRm9ybWFsL2Q0LzZkLzViL2Q0NmQ1Yjg0LTVhNTMtNGUwMy04MzBiLTg2MmY3M2M2NGVkMi9oZWFkcy8zYTcyMzFmMi1hYjljLTRhMjMtOTcwYi1mNjExNDM5MDM1ZWUuanBnP3Q9MTUzNjU5NTIwMA==
				if ( ! in_array( strtolower($fileExt), array('png', 'jpg', 'jpeg', 'gif') ) ) {
					$fileExt = 'jpg';
				}

				$filename = $image_name . "-" . ( $step ) . "." . $fileExt;

				// Save image in uploads folder
				$response = wp_remote_get( $file_url );

				if( !is_wp_error( $response ) ){
					$image = $response['body'];

					$image_url = $uploads_url . '/' . $filename; // URL of the image on the disk
					$image_path = $uploads_path . '/' . $filename; // Path of the image on the disk
					$ii = 0;
					while ( $this->verifyFileExists($image_path) ) {
						$filename = $image_name . "-" . ( $step );
						$filename .= '-'.$ii;
						$filename .= "." . $fileExt;

						$image_url = $uploads_url . '/' . $filename; // URL of the image on the disk
						$image_path = $uploads_path . '/' . $filename; // Path of the image on the disk
						$ii++;
					}

					// verify image hash
					$hash = md5($image);
					$hashFound = $this->verifyProdImageHash( $hash );
					if ( !empty($hashFound) && isset($hashFound->media_id) ) { // image hash not found!

						$orig_attach_id = $hashFound->media_id;
						// $attach_data = wp_get_attachment_metadata( $orig_attach_id );
						// $image_path = $uploads_path . '/' . basename($attach_data['file']);
						$image_path = $hashFound->image_path;

						// Add image in the media library - Step 3
						/*$wp_filetype = wp_check_filetype( basename( $image_path ), null );
						$attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $image_path ) ),
							'post_content'   => '',
							'post_status'    => 'inherit'
						);

						// $attach_id = wp_insert_attachment( $attachment, $image_path, $pid  );
						require_once( ABSPATH . 'wp-admin/includes/image.php' );
						wp_update_attachment_metadata( $attach_id, $attach_data );*/

						return array(
							'attach_id'         => $orig_attach_id, // $attach_id,
							'image_path'        => $image_path,
							'hash'              => $hash
						);
					}
					//write image if the wp method fails
					$has_wrote = $this->wp_filesystem->put_contents(
						$uploads_path . '/' . $filename, $image, FS_CHMOD_FILE
					);

					if( !$has_wrote ){
						file_put_contents( $uploads_path . '/' . $filename, $image );
					}

					// Add image in the media library - Step 3
					$wp_filetype = wp_check_filetype( basename( $image_path ), null );
					$attachment = array(
						// 'guid'           => $image_url,
						'post_mime_type' => $wp_filetype['type'],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $image_path ) ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					);

					$attach_id = wp_insert_attachment( $attachment, $image_path, $pid  );
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					$attach_data = wp_generate_attachment_metadata( $attach_id, $image_path );
					wp_update_attachment_metadata( $attach_id, $attach_data );

					return array(
						'attach_id'         => $attach_id,
						'image_path'        => $image_path,
						'hash'              => $hash
					);
				}
				else{
					return array(
						'status'    => 'invalid',
						'msg'       => htmlspecialchars( implode(';', $response->get_error_messages()) )
					);
				}
			}
		}

		public function verifyProdImageHash( $hash ) {
			require( $this->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
			$WooZoneLiteAssetDownloadCron = new WooZoneLiteAssetDownload();

			return $WooZoneLiteAssetDownloadCron->verifyProdImageHash( $hash );
		}


		/**
			* HTML escape given string
			*
			* @param string $text
			* @return string
			*/
		public function escape($text)
			{
					$text = (string) $text;
					if ('' === $text) return '';

					$result = @htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
					if (empty($result)) {
							$result = @htmlspecialchars(utf8_encode($text), ENT_COMPAT, 'UTF-8');
					}

					return $result;
			}

		public function getBrowseNodes( $nodeid=0, $provider='amazon' ) {

			// if( !is_numeric($nodeid) ){
			// 	return array(
			// 		'status'    => 'invalid',
			// 		'msg'       => 'The $nodeid is not numeric: ' . $nodeid
			// 	);
			// }

			// $prefix_opt = 'amazon' != $provider ? '_'.$provider : '';
			// if ( 'amazon' != $provider ) {
			// 	$prefix = 'amazon' != $provider ? $provider.'_' : '';
			// 	$country = $this->amz_settings[$prefix.'country'];
			// 	$prefix_opt = $prefix_opt . '_' . $country;
			// }

			// $optname = $this->alias . $prefix_opt . '_node_children_' . $nodeid;
			// $nodes = get_option( $optname, false );

			$nodes = $this->get_ws_object( $provider )->getBrowseNodesList( $nodeid );
			return $nodes;
		}

		public function multi_implode($array, $glue)
		{
			$ret = '';

			foreach ($array as $item) {
				if (is_array($item)) {
					$ret .= $this->multi_implode($item, $glue) . $glue;
				} else {
					$ret .= $item . $glue;
				}
			}

			$ret = substr($ret, 0, 0-strlen($glue));

			return $ret;
		}

		public function download_asset_lightbox( $prod_id=0, $from='default', $return='die' )
		{
			$requestData = array(
				'prod_id'   => isset($_REQUEST['prod_id']) ? $_REQUEST['prod_id'] : $prod_id,
				'from'      => isset($_REQUEST['from']) ? $_REQUEST['from'] : $from,
			);
			extract($requestData);

			$assets = $this->get_ws_object( 'generic' )->get_asset_by_postid( 'all', $prod_id, true );
			if ( count($assets) <= 0 ) {
				if( $return == 'die' ){
					die( json_encode(array(
						'status' => 'invalid',
						'html'  => __("this product has no assets to be dowloaded!", 'woozonelite' )
					)));
				} else {
					return __("this product has no assets to be dowloaded!", 'woozonelite' );
				}
			}

			$css = array();
			$css['container'] = ( $from == 'default' ? 'WooZoneLite-asset-download-lightbox-properties' : 'WooZoneLite-asset-download-IM' );

			$html = array();
			$html[] = '<div class="WooZoneLite-asset-download-lightbox '.$css['container'].'">';
			$html[] =   '<div class="WooZoneLite-donwload-in-progress-box">';
			$html[] =       '<h1>' . __('Images download in progress ... ', 'woozonelite' ) . '<a href="#" class="WooZoneLite-button red" id="WooZoneLite-close-btn">' . __('CLOSE', 'woozonelite' ) . '</a></h1>';
			$html[] =       '<p class="WooZoneLite-message WooZoneLite-info WooZoneLite-donwload-notice">';
			$html[] =       __('Please be patient while the images are downloaded.
			This can take a while if your server is slow (inexpensive hosting) or if you have many images.
			Do not navigate away from this page until this script is done.
			You will be notified via this box when the regenerating is completed.', 'woozonelite' );
			$html[] =       '</p>';

			$html[] =       '<div class="WooZoneLite-process-progress-bar">';
			$html[] =           '<div class="WooZoneLite-process-progress-marker"><span>0%</span></div>';
			$html[] =       '</div>';

			$html[] =       '<div class="WooZoneLite-images-tail">';
			$html[] =           '<ul>';

			if( count($assets) > 0 ){
				foreach ($assets as $asset) {

					$html[] =       '<li data-id="' . ( $asset->id ) . '">';
					$html[] =           '<img src="' . ( $asset->thumb ) . '">';
					$html[] =       '</li>';
				}
			}

			$html[] =           '</ul>';
			$html[] =       '</div>';
			$html[] =       '
			<script>
				jQuery(".WooZoneLite-images-tail ul").each(function(){

					var that = jQuery(this),
						lis = that.find("li"),
						size = lis.size();

					that.width( size *  86 );
				});
				jQuery(".WooZoneLite-images-tail ul").scrollLeft(0);
			</script>
			';

			$html[] =       '<h2 class="WooZoneLite-process-headline">' . __('Debugging Information:', 'woozonelite' ) . '</h2>';
			$html[] =       '<table class="WooZoneLite-table WooZoneLite-debug-info">';
			if ( $from == 'default' ) {
			$html[] =           '<tr>';
			$html[] =               '<td width="150">' . __('Total Images:', 'woozonelite' ) . '</td>';
			$html[] =               '<td>' . ( count($assets) ) . '</td>';
			$html[] =           '</tr>';
			$html[] =           '<tr>';
			$html[] =               '<td>' . __('Images Downloaded:', 'woozonelite' ) . '</td>';
			$html[] =               '<td class="WooZoneLite-value-downloaded">0</td>';
			$html[] =           '</tr>';
			$html[] =           '<tr>';
			$html[] =               '<td>' . __('Downloaded Failures:', 'woozonelite' ) . '</td>';
			$html[] =               '<td class="WooZoneLite-value-failures">0</td>';
			$html[] =           '</tr>';
			} else {
				$html[] =           '<tr>';
				$html[] =               '<td>' . __('Total Images:', 'woozonelite' ) . '<span>' . ( count($assets) ) . '</span></td>';
				$html[] =               '<td>' . __('Images Downloaded:', 'woozonelite' ) . '<span class="WooZoneLite-value-downloaded">0</span></td>';
				$html[] =               '<td>' . __('Downloaded Failures:', 'woozonelite' ) . '<span class="WooZoneLite-value-failures">0</span></td>';
				$html[] =           '</tr>';
			}
			$html[] =       '</table>';

			$html[] =       '<div class="WooZoneLite-downoad-log">';
			$html[] =           '<ol>';
			//$html[] =                 '<li>"One-size-fits-most-Tube-DressCoverup-Field-Of-Flowers-White-0" (ID 214) failed to resize. The error message was: The originally uploaded image file cannot be found at <code>/home/aateam30/public_html/cc/wp-plugins/woo-Amazon-payments/wp-content/uploads/2014/03/One-size-fits-most-Tube-DressCoverup-Field-Of-Flowers-White-0.jpg</code></li>';
			$html[] =           '</ol>';
			$html[] =       '</div>';
			$html[] =   '</div>';
			$html[] = '</div>';

			if( $return == 'die' ){
				die( json_encode(array(
					'status' => 'valid',
					'html'  => implode("\n", $html)
				)));
			}

			return implode("\n", $html);
		}


		/**
		 * Delete product assets
		 */
		public function product_assets_verify() {
			if ( current_user_can( 'delete_posts' ) )
				add_action( 'delete_post', array($this, 'product_assets_delete'), 10 );
		}

		public function product_assets_delete($prod_id) {
			// verify we are in woocommerce product
			if ( is_object($prod_id) ) {
				$product = $prod_id;
			} else if( function_exists('wc_get_product') ){
				$product = wc_get_product( $prod_id );
			} else if( function_exists('get_product') ){
				$product = get_product( $prod_id );
			}

			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				if ( $prod_id ) {
					require( $this->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
					$WooZoneLiteAssetDownloadCron = new WooZoneLiteAssetDownload();

					return $WooZoneLiteAssetDownloadCron->product_assets_delete( $prod_id );
				}
			}
		}


		/**
		 * Usefull
		 */

		//format right (for db insertion) php range function!
		public function doRange( $arr ) {
			$newarr = array();
			if ( is_array($arr) && count($arr)>0 ) {
				foreach ($arr as $k => $v) {
					$newarr[ $v ] = $v;
				}
			}
			return $newarr;
		}

		//verify if file exists!
		public function verifyFileExists($file, $type='file') {
			clearstatcache();
			if ($type=='file') {
				if (!file_exists($file) || !is_file($file) || !is_readable($file)) {
					return false;
				}
				return true;
			} else if ($type=='folder') {
				if (!is_dir($file) || !is_readable($file)) {
					return false;
				}
				return true;
			}
			// invalid type
			return 0;
		}

		// Return current Unix timestamp with microseconds
		// Simple function to replicate PHP 5 behaviour
		public function microtime_float()
		{
			list($usec, $sec) = explode(" ", microtime());
			return ((float)$usec + (float)$sec);
		}

		public function formatBytes($bytes, $precision = 2) {
			$units = array('B', 'KB', 'MB', 'GB', 'TB');

			$bytes = max($bytes, 0);
			$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
			$pow = min($pow, count($units) - 1);

			// Uncomment one of the following alternatives
			// $bytes /= pow(1024, $pow);
			$bytes /= (1 << (10 * $pow));

			return round($bytes, $precision) . ' ' . $units[$pow];
		}

		public function prepareForInList($v) {
			return "'".$v."'";
		}

		public function prepareForPairView($v, $k) {
			return sprintf("(%s, %s)", $k, $v);
		}

		public function db_custom_insert($table, $fields, $ignore=false, $wp_way=false) {
			if ( $wp_way && !$ignore ) {
				$this->db->insert(
					$table,
					$fields['values'],
					$fields['format']
				);
			} else {

				$formatVals = implode(', ', array_map(array($this, 'prepareForInList'), $fields['format']));
				$theVals = array();
				foreach ( $fields['values'] as $k => $v ) $theVals[] = $k;

				$q = "INSERT " . ($ignore ? "IGNORE" : "") . " INTO $table (" . implode(', ', $theVals) . ") VALUES (" . $formatVals . ");";
				foreach ($fields['values'] as $kk => $vv) {
					$fields['values']["$kk"] = esc_sql($vv);
				}

				$q = vsprintf($q, $fields['values']);
				//var_dump('<pre>', $q , '</pre>');
				$r = $this->db->query( $q );
			}
			return $this->db->insert_id;
		}

		public function verify_product_isvariation($prod_id) {
			// verify we are in woocommerce product
			if ( is_object($prod_id) ) {
				$product = $prod_id;
			} else if( function_exists('wc_get_product') ){
				//$product = wc_get_product( $prod_id );
				$product = new WC_Product_Variable( $prod_id ); // WC_Product
			} else if( function_exists('get_product') ){
				//$product = get_product( $prod_id );
				$product = new WC_Product_Variable( $prod_id ); // WC_Product
			}

			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				if ( $prod_id ) {
					if ( $product->has_child() ) { // is product variation parent!
						return true;
					}
				}
			}
			return false;
		}

		public function get_product_variations($prod_id) {
			// verify we are in woocommerce product
			if ( is_object($prod_id) ) {
				$product = $prod_id;
			} else if( function_exists('wc_get_product') ){
				//$product = wc_get_product( $prod_id );
				$product = new WC_Product_Variable( $prod_id ); // WC_Product
			} else if( function_exists('get_product') ){
				//$product = get_product( $prod_id );
				$product = new WC_Product_Variable( $prod_id ); // WC_Product
			}

			if ( is_object($product) ) {
				$prod_id = 0;
				if ( method_exists( $product, 'get_id' ) ) {
					$prod_id = (int) $product->get_id();
				} else if ( isset($product->id) && (int) $product->id > 0 ) {
					$prod_id = (int) $product->id;
				}
				if ( $prod_id ) {
					return $product->get_children();
				}
			}
			return array();
		}

		/**
		 * spin post/product content
		 */
		public function spin_content( $req=array() ) {

			$request = array(
				'prodID'            => isset($req['prodID']) ? $req['prodID'] : 0,
				'replacements'      => isset($req['replacements']) ? $req['replacements'] : 10
			);

			$ret = array(
				'status' => 'valid',
				'data' => array()
			);

			// spin content action
			require_once( $this->cfg['paths']["scripts_dir_path"] . '/php-query/phpQuery.php' );
			require_once( $this->cfg['paths']["scripts_dir_path"] . '/spin-content/spin.class.php' );

			if ( 1 ) {

				$lang = isset($this->amz_settings['main_aff_id']) ? $this->amz_settings['main_aff_id'] : 'en';
				$lang = strtolower( $lang );

				$spinner = WooZoneLiteSpinner::getInstance();
				$spinner->set_syn_language( $lang );
				$spinner->set_replacements_number( $request['replacements'] );

				// first check if you have the original content saved into DB
				$post_content = get_post_meta( $request['prodID'], 'WooZoneLite_old_content', true );

				// if not, retrive from DB
				if( $post_content == false ){
					$live_post = get_post( $request['prodID'], ARRAY_A );
					$post_content = $live_post['post_content'];
				}

				$spinner->load_content( $post_content );
				$spin_return = $spinner->spin_content();
				$reorder_content = $spinner->reorder_synonyms();
				$fresh_content = $spinner->get_fresh_content( $reorder_content );

				update_post_meta( $request['prodID'], 'WooZoneLite_spinned_content', $spin_return['spinned_content'] );
				update_post_meta( $request['prodID'], 'WooZoneLite_reorder_content', $reorder_content );
				update_post_meta( $request['prodID'], 'WooZoneLite_old_content', $spin_return['old_content'] );
				update_post_meta( $request['prodID'], 'WooZoneLite_finded_replacements', $spin_return['finded_replacements'] );

				// Update the post into the database
				wp_update_post( array(
							'ID'           => $request['prodID'],
							'post_content' => $fresh_content
				) );

				$ret = array(
					'status' => 'valid',
					'data' => array(
						'reorder_content' => $reorder_content
					)
				);
			}
			return $ret;
		}


		/**
		 * setup module messages
		 */
		public function print_module_error( $module=array(), $error_number, $title="" )
		{
			$html = array();
			if( count($module) == 0 ) return true;

			$html[] = '<div class="WooZoneLite-grid_4 WooZoneLite-error-using-module">';
			$html[] =   '<div class="WooZoneLite-panel">';
			$html[] =       '<div class="WooZoneLite-panel-header">';
			$html[] =           '<span class="WooZoneLite-panel-title">';
			$html[] =               __( $title, 'woozonelite' );
			$html[] =           '</span>';
			$html[] =       '</div>';
			$html[] =       '<div class="WooZoneLite-panel-content">';

			$error_msg = isset($module[$module['alias']]['errors'][$error_number]) ? $module[$module['alias']]['errors'][$error_number] : '';

			$html[] =           '<div class="WooZoneLite-error-details">' . ( $error_msg ) . '</div>';
			$html[] =       '</div>';
			$html[] =   '</div>';
			$html[] = '</div>';

			return implode("\n", $html);
		}

		public function convert_to_button( $button_params=array() )
		{
			$button = array();
			$button[] = '<a';
			if(isset($button_params['url']))
				$button[] = ' href="' . ( $button_params['url'] ) . '"';

			if(isset($button_params['target']))
				$button[] = ' target="' . ( $button_params['target'] ) . '"';

			$button[] = ' class="WooZoneLite-button';

			if(isset($button_params['color']))
				$button[] = ' ' . ( $button_params['color'] ) . '';

			$button[] = '"';
			$button[] = '>';

			$button[] =  $button_params['title'];

			$button[] = '</a>';

			return implode("", $button);
		}

		public function load_terms($taxonomy){
			global $wpdb;

			$query = "SELECT DISTINCT t.name FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} as tt ON tt.term_id = t.term_id WHERE 1=1 AND tt.taxonomy = '".esc_sql($taxonomy)."'";
				$result =  $wpdb->get_results($query , OBJECT);
				return $result;
		}

		public function get_current_page_url() {
			$url = (!empty($_SERVER['HTTPS']))
				?
				"https://" . $this->get_host() . $_SERVER['REQUEST_URI']
				:
				"http://" . $this->get_host() . $_SERVER['REQUEST_URI']
			;
			return $url;
		}

		// verbose translation from Symfony
		public function get_host() {
			$possibleHostSources = array('HTTP_X_FORWARDED_HOST', 'HTTP_HOST', 'SERVER_NAME', 'SERVER_ADDR');
			$sourceTransformations = array(
				// since PHP 4 >= 4.0.1, PHP 5, PHP 7
				//"HTTP_X_FORWARDED_HOST" => create_function('$value', '$elements = explode(",", $value); return trim(end($elements));'),
				"HTTP_X_FORWARDED_HOST" => function ($value) {
					$elements = explode(",", $value); return trim(end($elements));
				},

				// since PHP 5.3.0 (anonymous function)
				//"HTTP_X_FORWARDED_HOST" => function($value) {
				//    $elements = explode(',', $value);
				//    return trim(end($elements));
				//},
			);
			$host = '';
			foreach ($possibleHostSources as $source)
			{
				if (!empty($host)) break;
				if (empty($_SERVER[$source])) continue;
				$host = $_SERVER[$source];
				if (array_key_exists($source, $sourceTransformations))
				{
					$host = $sourceTransformations[$source]($host);
				}
			}

			// Remove port number from host
			$host = preg_replace('/:\d+$/', '', $host);

			return trim($host);
		}

		public function get_country_perip_external( $return_field='country' ) {
			//if ( isset($_COOKIE["WooZoneLite_country"]) && !empty($_COOKIE["WooZoneLite_country"]) ) {
			//  return unserialize($_COOKIE["WooZoneLite_country"]);
			//}

			$provider = $this->cur_provider;

			//if ( isset($_SESSION['WooZoneLite_country']) ) unset($_SESSION["WooZoneLite_country"]); // DEBUG
			if ( $this->ss['cache_client_country'] ) {
				if ( isset($_SESSION['WooZoneLite_country'], $_SESSION['WooZoneLite_country']["$provider"])
					&& !empty($_SESSION['WooZoneLite_country']["$provider"]) ) {
					if ( $return_field == 'country' ) {
						return unserialize($_SESSION['WooZoneLite_country']["$provider"]);
					}
				}
			}

			$ip = $this->get_client_ip();
			//var_dump('<pre>', $ip , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$config = $this->amz_settings;

			$paths = array(
				//'api.hostip.info'           	=> 'http://api.hostip.info/country.php?ip={ipaddress}',
				'www.geoplugin.net'         	=> 'http://www.geoplugin.net/json.gp?ip={ipaddress}',
				//'www.telize.com'            	=> 'http://www.telize.com/geoip/{ipaddress}',
				'ipinfo.io'                 	=> 'http://ipinfo.io/{ipaddress}/geo',
			);

			$service_used = 'www.geoplugin.net';
			if ( isset($config['services_used_forip']) && !empty($config['services_used_forip']) ) {
				$service_used = $config['services_used_forip'];
			}

			$country = '';
			if ( $service_used == 'local_csv' ) { // local csv file with ip lists

				// read csv hash (string with ip from list)
				$csv_hash = file_get_contents( $this->cfg['paths']['plugin_dir_path'] . 'assets/GeoIPCountryWhois-hash.csv' );
				$csv_hash = explode(',', $csv_hash);

				// read csv full (ip from, ip to, country)
				$csv_full = file_get_contents( $this->cfg['paths']['plugin_dir_path'] . 'assets/GeoIPCountryWhois-full.csv' );
				$csv_full = explode(PHP_EOL, $csv_full);

				//var_dump('<pre>',count($csv_hash), count($csv_full),'</pre>');
				//var_dump('<pre>',$csv_hash, $csv_full,'</pre>');

				$ip2number = $this->ip2number( $ip );
				//var_dump('<pre>', $ip, $ip2number, '</pre>');

				$ipHashIndex = $this->binary_search($ip2number, $csv_hash, array($this, 'binary_search_cmp'));
				if ( $ipHashIndex < 0 ) { // verify if is between (ip_from, ip_to) of csv row
						$ipHashIndex = abs( $ipHashIndex );
						$ipFullRow = $csv_full["$ipHashIndex"];
						$csv_row = explode(',', $ipFullRow);
						if ( $ip2number >= $csv_row[0] && $ip2number <= $csv_row[1] ) {
								$country = $csv_row[2];
						}
				} else { // exact match in the list as ip_from of csv row
						$ipFullRow = $csv_full["$ipHashIndex"];
						$country = end( explode(',', $ipFullRow) );
				}

				if (empty($country)) {
						//$main_aff_site = $this->main_aff_site();
						//$country = strtoupper(str_replace(".", '', $main_aff_site));
						$country = 'NOT-FOUND';
				}
				$country = strtoupper( $country );

				//var_dump('<pre>', $ipHashIndex, $ipFullRow, $country, '</pre>');
				//echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			}
			// external service
			else {

				$service_url = $paths["$service_used"];
				$service_url = str_replace('{ipaddress}', $ip, $service_url);

				$get_user_location = wp_remote_get( $service_url );
				if ( isset($get_user_location->errors) ) {
					//$main_aff_site = $this->main_aff_site();
					//$country = strtoupper(str_replace(".", '', $main_aff_site));
					$country = 'NOT-FOUND';
				} else {
					$country = $get_user_location['body'];

					$country = json_decode($country);

					if ( ! is_object($country) ) $country = 'NOT-FOUND';
					else {
						switch ($service_used) {
							//case 'api.hostip.info':
							//	break;

							case 'www.geoplugin.net':
								//$country = json_decode($country);
								$country = isset($country->geoplugin_countryCode) ? strtoupper( (string)$country->geoplugin_countryCode ) : 'NOT-FOUND';
								break;

							//case 'www.telize.com':
							//	//$country = json_decode($country);
							//	$country = strtoupper( $country->country_code );
							//	break;

							case 'ipinfo.io':
								//$country = json_decode($country);
								$country = isset($country->country) ? strtoupper( (string)$country->country ) : 'NOT-FOUND';
								break;

							default:
								break;
						}
					}
					//$country = 'CN'; //DEBUG
				}
			}
			// end external service

			if ( $return_field == 'country' ) {
				$user_country = $this->amzForUser($country);
				//var_dump('<pre>',$user_country,'</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

				//$this->cookie_set(array(
				//  'name'          => 'WooZoneLite_country',
				//  'value'         => serialize($user_country),
				//  'expire_sec'    => strtotime( '+30 days' ) // time() + 604800, // 1 hour = 3600 || 1 day = 86400 || 1 week = 604800
				//));
				if ( $this->ss['cache_client_country'] ) {
					if ( ! isset($_SESSION['WooZoneLite_country']) || ! is_array($_SESSION['WooZoneLite_country']) ) {
						$_SESSION['WooZoneLite_country'] = array();
					}
					$_SESSION['WooZoneLite_country']["$provider"] = serialize($user_country);
				}
				return $user_country;
			}
		}

		public function cookie_set( $cookie_arr = array() ) {
			extract($cookie_arr);
			if ( !isset($path) )
				$path = '/';
			if ( !isset($domain) )
				$domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
					$stat = setcookie($name, $value, $expire_sec, $path, $domain);
			return $stat;
		}
		public function cookie_del( $cookie_arr = array() ) {
			extract($cookie_arr);
			if ( !isset($path) )
				$path = '/';
			if ( !isset($domain) )
				$domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
			setcookie($name, null, strtotime('-1 day'), $path, $domain);
		}

		public function get_client_ip() {
			$ipaddress = '';

			if ($_SERVER['REMOTE_ADDR'])
				$ipaddress = $_SERVER['REMOTE_ADDR'];
			else if ($_SERVER['HTTP_CLIENT_IP'])
				$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
			else if ($_SERVER['HTTP_X_FORWARDED'])
				$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
			else if ($_SERVER['HTTP_FORWARDED_FOR'])
				$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
			else if( $_SERVER['HTTP_FORWARDED'])
				$ipaddress = $_SERVER['HTTP_FORWARDED'];
			else if ($_SERVER['HTTP_X_FORWARDED_FOR'])
				$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];

			return $ipaddress;
		}

		public function ip2number( $ip ) {
			$long = ip2long($ip);
			if ($long == -1 || $long === false) {
				return false;
			}
			return sprintf("%u", $long);
		}

		public function verify_module_status( $module='' ) {
			if ( empty($module) ) return false;

			$mod_active = get_option( 'WooZoneLite_module_'.$module );
			if ( $mod_active != 'true' )
					return false; //module is inactive!
			return true;
		}

		public function last_update_date($format=false, $last_date=false, $year=false) {
			if ( $last_date === '' ) return $last_date;
			if ( $last_date === false ) $last_date = time();
			if ( !$format ) return $last_date;

			$date_format = 'D j M / H.i';
			if ( $year ) $date_format = 'D j M Y / H.i';
			return date($date_format, $last_date); // Mon 2 Feb / 13.21
		}

		public function set_content_type($content_type){
			return 'text/html';
		}

		public function category_nice_name($categ_name) {
			$ret = $categ_name;

			$special = array('DVD' => 'DVD', 'MP3Downloads' => 'MP3 Downloads', 'PCHardware' => 'PC Hardware', 'VHS' => 'VHS');
			if ( !in_array($categ_name, array_keys($special)) ) {
				$ret = preg_replace('/([A-Z])/', ' $1', $categ_name);
			} else {
				$ret = $special["$categ_name"];
			}
			return $ret;
		}

		// This function works exactly how encodeURIComponent is defined:
		// encodeURIComponent escapes all characters except the following: alphabetic, decimal digits, - _ . ! ~ * ' ( )
		public function encodeURIComponent($str) {
			$revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
			return strtr(rawurlencode($str), $revert);
		}

		/**
		 * Parameters:
		 *   $key - The key to be searched for.
		 *   $list - The sorted array.
		 *   $compare_func - A user defined function for comparison. Same definition as the one in usort
		 *   $low - First index of the array to be searched (local parameters).
		 *   $high - Last index of the array to be searched (local parameters).
		 *
		 * Return:
		 *   index of the search key if found, otherwise return -(insert_index + 1).
		 *   insert_index is the index of greatest element that is smaller than $key or count($list) if $key
		 *   is larger than all elements in the array.
		 *
		 * License: Feel free to use the code if you need it.
		 */
		public function binary_search($key, array $list, $compare_func) {
			$low = 0;
			$high = count($list) - 1;

			while ($low <= $high) {
					$mid = (int) (($high - $low) / 2) + $low; // could use php ceil function
					$cmp = call_user_func($compare_func, $list[$mid], $key);

					if ($cmp < 0) {
							$low = $mid + 1;
					} else if ($cmp > 0) {
							$high = $mid - 1;
					} else {
							return $mid;
					}
			}
			return -($low - 1);
		}
		public function binary_search_cmp($a, $b) {
			return ($a < $b) ? -1 : (($a > $b) ? 1 : 0);
		}

		/**
		 * Insane Mode - Last Imports Stats / Duration
		 */
		public function get_last_imports( $what='all' ) {
			$ret = array();
			$cfg = get_option('WooZoneLite_insane_last_reports', array());

			$def = array(
					// duration in miliseconds
					'request_amazon'                    	=> 200, // request product from amazon
					'request_cache'                     	=> 10, // request product from cache
					'last_product'                      	=> 150, // product without the bellow options
					//'last_import_images'					=> 120, // add images to assets table
					'last_import_images_download'			=> 1500, // download images
					'last_import_images_remote'				=> 20, // remote images
					'last_import_variations'            	=> 150, // import variations
					'last_import_spin'                  	=> 65, // spin post content
					'last_import_attributes'            	=> 230, // import attributes
			);
			foreach ($def as $key => $val) {
					$def["$key"] = array(
							'items' => array(
									array( 'duration' => $val ),
							),
					);
			}

			foreach ($def as $key => $val) {
					// default
					if ( !isset($cfg["$key"], $cfg["$key"]['items']) || !is_array($cfg["$key"]['items'])
							|| empty($cfg["$key"]['items']) ) {

							$cfg["$key"] = $def["$key"];
					}
			}
			foreach ($cfg as $key => $val) {

					$media = array();
					foreach ($val['items'] as $key2 => $val2) {

							$duration = $val2['duration'];
							if ( isset($val2['nb_items']) && (int) $val2['nb_items'] > 0 ) {
									$nb_items = (int) $val2['nb_items'];
									$media[] = round( $duration / $nb_items, 4 );
							} else {
									$media[] = round( $duration, 4 );
							}
					}
					$media = !empty($media) ? round( array_sum($media) / count($media), 4 ) : 0;

					$cfg["$key"]["media"] = array('duration' => $media);
			}

			$ret = $cfg;
			//var_dump('<pre>', $ret, '</pre>'); die('debug...');
			return $ret;
		}

		public function add_last_imports( $what='all', $new=array() ) {
			if ( $what === 'all' || empty($new) ) return false;

			$max_last_keep = in_array($what, array('last_import_images_download', 'last_import_variations')) ? 10 : 5;
			$ret = array();
			$cfg = get_option('WooZoneLite_insane_last_reports', array());

			if (
				!isset($cfg["$what"], $cfg["$what"]['items'])
				|| !is_array($cfg)
				|| !is_array($cfg["$what"])
				|| !is_array($cfg["$what"]['items'])
			) {
				$cfg["$what"] = array(
					'items'     => array()
				);
			}

			if ( count($cfg["$what"]['items']) >= $max_last_keep ) {
					array_shift($cfg["$what"]['items']); // remove oldes maintained log regarding import
			}
			// add new latest log regarding import
			$cfg["$what"]['items'][] = $new;

			update_option('WooZoneLite_insane_last_reports', $cfg);
		}

		public function timer_start() {
			$this->timer->start();
		}
		public function timer_end( $debug=false ) {
			$this->timer->end( $debug );
			$duration = $this->timer->getRenderTime(1, 0, false);
			return $duration;
		}

		public function format_duration( $duration, $precision=1 ) {
			$prec = $this->timer->getUnit( $precision );
			$ret = $duration . ' ' . $prec;
			$ret = '<i>' . $ret . '</i>';
			return $ret;
		}

		public function save_amazon_request_time() {
			$time = microtime(true);
			update_option('WooZoneLite_last_amazon_request_time', $time);

			$nb = get_option('WooZoneLite_amazon_request_number', 0);
			update_option('WooZoneLite_amazon_request_number', (int)($nb+1));
			return true;
		}
		public function verify_amazon_request_rate( $do_pause=true ) {
			$ret = array('status' => 'valid'); // valid = no need for pause!

			$rate = isset($this->amz_settings['amazon_requests_rate']) ? $this->amz_settings['amazon_requests_rate'] : 1;
			$rate = (float) $rate;
			$rate_milisec = $rate > 0.00 && (int)$rate != 1 ? 1000 / $rate : 1000; // interval between requests in miliseconds
			$rate_milisec = floatval($rate_milisec);

			$current = microtime(true);
			$last = get_option('WooZoneLite_last_amazon_request_time', 0);
			$elapsed = round(($current - $last) * pow(10, 3), 0); // time elapsed from the last amazon requests

			//var_dump('<pre>', $elapsed, $rate_milisec, $elapsed < $rate_milisec , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			// we may need to pause
			if ( $elapsed < $rate_milisec ) {
				if ( $do_pause ) {
					$pause_microsec = ( $rate_milisec - $elapsed ) + 30; // here is in miliseconds - add 30 miliseconds to be sure
					$pause_microsec = $pause_microsec * 1000; // pause in microseconds
					//var_dump('<pre>',$pause_microsec ,'</pre>');
					usleep( $pause_microsec );
				}
			}
			return $ret;
		}
		public function get_amazon_request_number() {
			$nb = get_option('WooZoneLite_amazon_request_number', 0);
			return $nb;
		}

		public function save_amazon_request_remote_time() {
			$time = microtime(true);
			update_option('WooZoneLite_last_amazon_request_remote_time', $time);

			$nb = get_option('WooZoneLite_amazon_request_remote_number', 0);
			update_option('WooZoneLite_amazon_request_remote_number', (int)($nb+1));
			return true;
		}

		public function get_amazon_request_remote_number() {
			$nb = get_option('WooZoneLite_amazon_request_remote_number', 0);
			return $nb;
		}

		/**
		 * cURL / Send http requests with curl
		 */
		public static function curl($url, $input_params=array(), $output_params=array(), $debug=false) {
			$ret = array(
				'status' 		=> 'invalid',
				'http_code' 	=> 0,
				'data' 			=> ''
			);

			// build curl options
			$ipms = array_replace_recursive(array(
				'userpwd'                   => false,
				'htaccess'                  => false,
				'post'                      => false,
				'postfields'                => array(),
				'httpheader'                => false,
				'verbose'                   => false,
				'ssl_verifypeer'            => false,
				'ssl_verifyhost'            => false,
				'httpauth'                  => false,
				'failonerror'               => false,
				'returntransfer'            => true,
				'binarytransfer'            => false,
				'header'                    => false,
				'cainfo'                    => false,
				'useragent'                 => false,
				//'followlocation' 			=> false,
			), $input_params);
			extract($ipms);

			$opms = array_replace_recursive(array(
				'resp_is_json'              => false,
				'resp_add_http_code'        => false,
				'parse_headers'             => false,
			), $output_params);
			extract($opms);

			//var_dump('<pre>', $ipms, $opms, '</pre>'); die('debug...');

			// begin curl
			$url = trim($url);
			if (empty($url)) return (object) $ret;

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);

			if ( !empty($userpwd) ) {
				curl_setopt($curl, CURLOPT_USERPWD, $userpwd);
			}
			if ( !empty($htaccess) ) {
				$url = preg_replace( "/http(|s):\/\//i", "http://" . $htaccess . "@", $url );
			}
			if (!$post && !empty($postfields)) {
				$url = $url . "?" . http_build_query($postfields);
			}

			if ($post) {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
			}

			if ( !empty($httpheader) ) {
				curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
			}

			curl_setopt($curl, CURLOPT_VERBOSE, $verbose);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $ssl_verifypeer);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $ssl_verifyhost);
			if ( $httpauth!== false ) curl_setopt($curl, CURLOPT_HTTPAUTH, $httpauth);
			curl_setopt($curl, CURLOPT_FAILONERROR, $failonerror);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, $returntransfer);
			if ( isset($followlocation) && $followlocation!== false ) curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $followlocation);
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, $binarytransfer);
			curl_setopt($curl, CURLOPT_HEADER, $header);
			if ( $cainfo!== false ) curl_setopt($curl, CURLOPT_CAINFO, $cainfo);
			if ( $useragent!== false ) curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
			if ( isset($timeout) && $timeout!== false ) curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
			if ( isset($connecttimeout) && $connecttimeout!== false ) curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connecttimeout);

			$data = curl_exec($curl);
			$http_code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

			$ret = array_merge($ret, array('http_code' => $http_code));
			if ($debug) {
				$ret = array_merge($ret, array('debug_details' => curl_getinfo($curl)));
			}
			if ( $data === false || curl_errno($curl) ) { // error occurred
				$ret = array_merge($ret, array(
					'data' => curl_errno($curl) . ' : ' . curl_error($curl)
				));
			} else { // success

				if ( $parse_headers ) {
					$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
					$headers = self::parse_headers( substr($data, 0, $header_size) ); // response begin with the headers
					$data = substr($data, $header_size);
					$ret = array_merge($ret, array('headers' => $headers));
				}

				// Add the status code to the json data, useful for error-checking
				if ( $resp_add_http_code && $resp_is_json ) {
					$data = preg_replace('/^{/', '{"http_code":'.$http_code.',', $data);
				}

				$ret = array_merge($ret, array(
					'status'    => 'valid',
					'data'       => $data
				));
			}

			curl_close($curl);
			return $ret;
		}
		private static function parse_headers($headers) {
			if (!is_array($headers)) {
				$headers = explode("\r\n", $headers);
			}
			$ret = array();
			foreach ($headers as $header) {
				$header = explode(":", $header, 2);
				if (count($header) == 2) {
					$ret[$header[0]] = trim($header[1]);
				}
			}
			return $ret;
		}


		/**
		 * 2015, October fixes including attributes after woocommerce version 2.4.0!
		 */
		public function cleanValue($value) {
			// Format Camel Case
			//$value = trim( preg_replace('/([A-Z])/', ' $1', $value) );

			// Clean / from value
			$value = preg_replace('/(\/)/', '-', $value);
			$value = preg_replace('/(\+)/', 'plus', $value);
			$value = trim( $value );
			return $value;
		}

		public function cleanTaxonomyName($value, $withPrefix=true) {
			$ret = $value;

			// Sanitize taxonomy names. Slug format (no spaces, lowercase) - uses sanitize_title
			if ( $withPrefix ) {
				$ret = wc_attribute_taxonomy_name($value); // return 'pa_' . $value
			} else {
				// return $value
				$ret = function_exists('wc_sanitize_taxonomy_name')
					? wc_sanitize_taxonomy_name($value) : woocommerce_sanitize_taxonomy_name($value);
			}
			$limit_max = $withPrefix ? 32 : 29; // 29 = 32 - strlen('pa_')

			// limit to 32 characters (database/ table wp_term_taxonomy/ field taxonomy/ is limited to varchar(32) )
			return substr($ret, 0, $limit_max);

			return $ret;
		}

		public function get_woocommerce_version() {
			$ver = '';
			$is_found = false;

			// try to find version
			if ( !$is_found && defined('WC_VERSION') ) {
				$ver = WC_VERSION;
				$is_found = true;
			}

			if ( !$is_found ) {
				global $woocommerce;
				if ( is_object($woocommerce) && isset($woocommerce->version) && !empty($woocommerce->version) ) {
					$ver = $woocommerce->version;
					$is_found = true;
				}
			}

			if ( !$is_found ) {
				// If get_plugins() isn't available, require it
				if ( !function_exists( 'get_plugins' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}

				foreach (array('envato-wordpress-toolkit', 'woocommerce') as $folder) {
					// Create the plugins folder and file variables
					$plugin_folder = get_plugins( '/'.$folder );
					$plugin_file = 'woocommerce.php';

					// If the plugin version number is set, return it
					if ( isset( $plugin_folder[$plugin_file]['Version'] )
						&& !empty($plugin_folder[$plugin_file]['Version']) ) {

						$ver = $plugin_folder[$plugin_file]['Version'];
						$is_found = true;
						break;
					}
				}
			}
			return $ver;
		}
		public function force_woocommerce_product_version($ver_prod, $ver_min='2.4.0', $ver_ret=false) {
			// min version compare
			$ret = $ver_prod;
			if( version_compare( $ver_prod, $ver_min, "<" ) ) {
				$ret = $ver_ret ? $ver_ret : $ver_min;
			}
			return $ret;
		}

		public function get_main_settings( $provider='all' ) {
			$amz_settings = $this->amz_settings;
			$providers = array(
				'amazon'    => array(
					'title'     => __( 'Amazon Settings', 'woozonelite' ),
					'mandatory' => array('AccessKeyID', 'SecretAccessKey', 'country', 'main_aff_id'),
					'keys'      => array(
						'AccessKeyID'       => array(
							'title'             => __( 'Access Key ID','woozonelite' ),
							'value'             => '',
						),
						'SecretAccessKey'       => array(
							'title'             => __( 'Secret Access Key','woozonelite' ),
							'value'             => '',
						),
						'country'       => array(
							'title'             => __( 'Amazon location','woozonelite' ),
							'value'             => '',
						),
						'main_aff_id'       => array(
							'title'             => __( 'Main Affiliate ID','woozonelite' ),
							'value'             => '',
						),
						'AffiliateID'       => array(
							'title'             => __( 'Affiliate IDs','woozonelite' ),
							'value'             => '',
						),
					),
				),

				'ebay'	=> array(
					'title'		=> __( 'EBay Settings', 'woozonelite' ),
					'mandatory'	=> array('ebay_DEVID', 'ebay_AppID', 'ebay_CertID', 'ebay_country', 'ebay_main_aff_id'),
					'keys'		=> array(
						'ebay_DEVID'		=> array(
							'title'				=> __( 'DEVID','woozonelite' ),
							'value'				=> '',
						),
						'ebay_AppID'		=> array(
							'title'				=> __( 'AppID','woozonelite' ),
							'value'				=> '',
						),
						'ebay_CertID'		=> array(
							'title'				=> __( 'CertID','woozonelite' ),
							'value'				=> '',
						),
						'ebay_country'		=> array(
							'title'				=> __( 'Ebay location','woozonelite' ),
							'value'				=> '',
						),
						'ebay_main_aff_id'		=> array(
							'title'				=> __( 'Main Affiliate ID','woozonelite' ),
							'value'				=> '',
						),
						'ebay_AffiliateID'		=> array(
							'title'				=> __( 'Affiliate campid IDs','woozonelite' ),
							'value'				=> '',
						),
					),
				),

				/*'alibaba'	=> array(
					'title'		=> __( 'Alibaba Settings', $this->localizationName ),
					'mandatory'	=> array('alibaba_AppKey', 'alibaba_TrackingID', 'alibaba_DigitalSignature'),
					'keys'		=> array(
						'alibaba_AppKey'		=> array(
							'title'				=> __( 'App Key',$this->localizationName ),
							'value'				=> '',
						),
						'alibaba_TrackingID'		=> array(
							'title'				=> __( 'Tracking ID',$this->localizationName ),
							'value'				=> '',
						),
						'alibaba_DigitalSignature'		=> array(
							'title'				=> __( 'Digital Signature',$this->localizationName ),
							'value'				=> '',
						),
					),
				),

				'envato'	=> array(
					'title'		=> __( 'Envato Settings', $this->localizationName ),
					'mandatory'	=> array('envato_AffId', 'envato_ClientId', 'envato_ClientSecret', 'envato_RedirectUrl'),
					'keys'		=> array(
						'envato_AffId'		=> array(
							'title'				=> __( 'Affiliate ID',$this->localizationName ),
							'value'				=> '',
						),
						'envato_ClientId'		=> array(
							'title'				=> __( 'OAuth Client ID',$this->localizationName ),
							'value'				=> '',
						),
						'envato_ClientSecret'		=> array(
							'title'				=> __( 'Client Secret',$this->localizationName ),
							'value'				=> '',
						),
						'envato_RedirectUrl'		=> array(
							'title'				=> __( 'Confirmation URL',$this->localizationName ),
							'value'				=> '',
						),
					),
				),*/
			);
			foreach ($providers as $pkey => $pval) {
				foreach ($pval['keys'] as $pkey2 => $pval2) {
					if ( isset($amz_settings["$pkey2"]) ) {
						$pval2 = $amz_settings["$pkey2"];
						$providers["$pkey"]['keys']["$pkey2"]['value'] = $pval2;

						if ( preg_match('/(country|main_aff_id)/iu', $pkey2) ) {
							$obj = is_object($this->get_ws_object( $pkey )) ? $this->get_ws_object( $pkey ) : null;

							if ( !is_null($obj) ) {
								$providers["$pkey"]['keys']["$pkey2"]['value'] = $obj->get_country_name(
									$pval2,
									str_replace('ebay_', '', $pkey2)
								);
							}
						}
					}
				}
			}
			//var_dump('<pre>', $providers, '</pre>'); die('debug...');

			if ( $provider != 'all' ) {
				return isset($providers["$provider"]) ? $providers["$provider"] : array();
			}
			return $providers;
		}

		public function verify_mandatory_settings( $provider='amazon' ) {
			$ret = array(
				'status'        => 'invalid',
				'fields'        => array(),
				'fields_title'  => array(),
			);

			$module_settings = $this->get_main_settings( $provider );
			if ( empty($module_settings) ) return array_merge($ret, array());

			$mandatory = isset($module_settings['mandatory']) ? $module_settings['mandatory'] : array();
			if ( empty($mandatory) ) return array_merge($ret, array('status' => 'valid'));

			$fields = array();
			$module_mandatoryFields = array();
			foreach ( $mandatory as $field ) {

				$fields["$field"] = $field;
				if ( isset($module_settings['keys']["$field"]['title']) ) {
					$fields["$field"] = $module_settings['keys']["$field"]['title'];
				}

				$module_mandatoryFields["$field"] = false;
				//var_dump('<pre>', $field, $module_settings['keys']["$field"]['value'] ,'</pre>');
				if ( isset($module_settings['keys']["$field"]['value'])
					&& !empty($module_settings['keys']["$field"]['value']) ) {

					$module_mandatoryFields["$field"] = true;
				}
			}

			$mandatoryInvalid = array();
			foreach ($module_mandatoryFields as $k => $v) {
				if ( !$v ) {
					$_title = isset($fields["$k"]) ? $fields["$k"] : $k;
					$mandatoryInvalid["$k"] = $_title;
					break;
				}
			}
			return array_merge($ret, array(
				'status' 				=> empty($mandatoryInvalid) ? 'valid' : 'invalid',
				'mandatory_fields' 		=> array_values($fields),
				'mandatory_fields_err' 	=> array_values($mandatoryInvalid),
			));
		}

		public function settings() {
			//$settings = $this->getAllSettings('array', 'amazon');
			$settings = get_option( $this->alias . '_amazon' ); // 'WooZoneLite_amazon'
			$settings = maybe_unserialize( $settings );
			$settings = !empty($settings) && is_array($settings) ? $settings : array();

			$def = array(
				// amazon
				'AccessKeyID' 		=> '', //zzz
				'SecretAccessKey' 	=> '', //zzz
				'country'			=> 'com',
				'main_aff_id' 		=> 'aateam',
				'AffiliateID' 		=> array(),

				// ebay
				'number_of_requests_daily_limit' => 5000, // ebay daily request max limit
				'ebay_DEVID' 		=> '',
				'ebay_AppID'		=> '',
				'ebay_CertID' 		=> '',
				'ebay_country' 		=> 'EBAY-US',
				'ebay_main_aff_id' 	=> 'EBAY-US',
				'ebay_AffiliateID' 	=> array(),
			);
			foreach ($def as $key => $val) {
				if ( ! isset($settings["$key"]) || ('' == $settings["$key"]) ) {
					$settings["$key"] = $val;
				}
			}

			$this->amz_settings = $settings;
			return $this->amz_settings;
		}

		public function build_amz_settings( $new=array() ) {
			if ( !empty($new) && is_array($new) ) {
				$this->amz_settings = array_replace_recursive($this->amz_settings, $new);
			}
			return $this->amz_settings;
		}


		//============================================================
		//== MULTIPLE PROVIDERS FUNCTIONS
		/**
		 * Octomber 2015 - new plugin functions
		 */
		public function get_ws_prefixes($ws='all') {
			$wslist = array(
				'amazon'        => 'amz',
				'envato'		=> 'env',
				'alibaba'		=> 'ali',
				'ebay'			=> 'eby',
			);
			return $ws == 'all' ? $wslist : ( isset($wslist["$ws"]) ? $wslist["$ws"] : false );
		}

		public function get_ws_status($ws='all') {
			$wslist = array(
				'amazon'        => true,
				'envato'		=> false,
				'alibaba'		=> false,
				'ebay'			=> true,
			);
			return $ws == 'all' ? $wslist : ( isset($wslist["$ws"]) ? $wslist["$ws"] : false );
		}

		// 'all' = get all enabled providers, returns array
		// NOT 'all' = verify if provider is enabled, returns true | false
		public function providers_is_enabled($provider='all') {
			$providers = $this->get_ws_status();

			if ( 'all' == $provider ) {
				$__ = array();
				foreach ( $providers as $kk => $vv ) {
					if ( $vv ) {
						$__[] = $kk;
					}
				}
				$ret = $__;
			}
			else {
				$ret = isset($providers["$provider"]) ? $providers["$provider"] : false;
			}

			return $ret;
		}

		public function providers_allow_variations() {
			return array('amazon', 'ebay');
		}

		public function providers_get_countries() {
			$ret = new stdClass();
			$ret->countries = array();
			$ret->main_aff_ids = array();
			$ret->countries_allprov = array();

			$providers = array('amazon', 'ebay');
			foreach ( $providers as $provider ) {
				$theHelper = $this->get_ws_object( $provider );
				$ret->countries["$provider"] = is_object($theHelper) ? $theHelper->get_countries( 'country' ) : array();
				$ret->main_aff_ids["$provider"] = is_object($theHelper) ? $theHelper->get_countries( 'main_aff_id' ) : array();

				if ( is_array($ret->countries["$provider"]) && ! empty($ret->countries["$provider"]) ) {
					$ret->countries_allprov += $ret->countries["$provider"];
				}
			}

			return $ret;
		}

		public function providers_get_filter_dropdown( $pms=array() ) {

			$pms = array_replace_recursive( array(), array(
				'use_key' 		=> 'prefix', // prefix | alias
				'title_prefix' 	=> '',
			), $pms );
			extract( $pms );

			// values array keys must be the same as what providers_is_enabled with 'all' returns
			$values = array(
				'amazon' 	=> __( 'Amazon Products', 'woozonelite' ),
				'ebay' 	=> __( 'Ebay Products', 'woozonelite' ),
			);

			$providers = $this->providers_is_enabled();
			$providers_prefix = $this->get_ws_prefixes();

			$ret = array();
			foreach ( $providers as $provider ) {
				$key = $provider;
				if ( 'prefix' == $use_key ) {
					$key = isset($providers_prefix["$provider"]) ? $providers_prefix["$provider"] : '';
				}

				if ( isset($values["$provider"]) ) {
					$ret["$key"] = $title_prefix . $values["$provider"];
				}
			}

			return $ret;
		}

		public function get_post_meta($post_id, $key='', $single=false, $withPrefix=true) {
			$_key = $key;
			if ( $_key == '_amzASIN' ) $key = '_amzaff_prodid';

			$ret = get_post_meta($post_id, $key, $single);

			// because we have old amazon products which have only '_amzASIN' meta (they don't have this new '_amzaff_prodid' meta)
			if ( empty($ret) ) {
				if ( $_key == '_amzASIN' ) {
					$ret = get_post_meta($post_id, $_key, $single);
				}
			}

			if ( !$withPrefix && ($_key == '_amzASIN') ) {
				$wslist = $this->get_ws_prefixes();
				foreach ($wslist as $wsprefix) {
					$ret = str_replace($wsprefix.'-', '', $ret);
				}
			}
			return $ret;
		}

		public function get_product_by_wsid( $wsid ) {
			global $wpdb;

			$key = '_amzaff_prodid';
			$_key = '_amzASIN';

			$query = "SELECT a.ID, a.post_title FROM {$wpdb->posts} AS a LEFT JOIN {$wpdb->postmeta} AS b ON a.ID = b.post_id WHERE 1=1 AND b.meta_key = '$key' AND b.meta_value = '".esc_sql($wsid)."' AND !ISNULL(b.meta_id);";
			$result =  $wpdb->get_results($query , ARRAY_A);

			// because we have old amazon products which have only '_amzASIN' meta (they don't have this new '_amzaff_prodid' meta)
			if ( empty($result) ) {

				$_asin = $this->prodid_set($wsid, 'amazon', 'sub');

				$query = "SELECT a.ID, a.post_title FROM {$wpdb->posts} AS a LEFT JOIN {$wpdb->postmeta} AS b ON a.ID = b.post_id WHERE 1=1 AND b.meta_key = '$_key' AND b.meta_value = '".esc_sql($_asin)."' AND !ISNULL(b.meta_id);";
				$result =  $wpdb->get_results($query , ARRAY_A);
			}

			return (isset($result[0]) ? $result[0] : $result);
		}

		/**
		 * Call Example
			$args = array(
				'post_title'    => $retProd['Title'],
				'post_status'   => $default_import,
				'post_content'  => $desc,
				'post_excerpt'  => $excerpt,
				'post_type'     => 'product',
				'menu_order'    => 0,
				'post_author'   => 1
			);
		 */
		public function get_product_by_args($args) {
			global $wpdb;

			$args = array_merge(array(
				'post_title'    => '',
				'post_status'   => 'publish',
				'post_content'  => '',
				'post_excerpt'  => '',
				'post_type'     => 'product',
				'menu_order'    => 0,
				'post_author'   => 1
			), $args);

			//$result = $wpdb->get_row("SELECT * FROM " . ( $wpdb->prefix ) . "posts WHERE 1=1 and post_status = '" . ( $args['post_status'] ) . "' and post_title = '" .  ( $args['post_title'] )  . "'", 'ARRAY_A');
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . ( $wpdb->prefix ) . "posts WHERE 1=1 and post_type IN ('product', 'product_variation') and post_status = '" . ( $args['post_status'] ) . "' and post_title = %s", $args['post_title'] ), 'ARRAY_A' );
			if(count($result) > 0){
				return $result;
			}
			return false;
		}

		// get webservice object
		public function get_ws_object( $provider, $what='helper' ) {
			$arr = array(
				'generic'     => array(
				  'helper'        => $this->genericHelper,
				  'ws'            => null,
				),
				'amazon'        => array(
					'helper'        => $this->amzHelper,
					'ws'            => is_object($this->amzHelper) ? $this->amzHelper->aaAmazonWS : null,
				),
				//'alibaba'		=> array(
				//	'helper'		=> $this->alibabaHelper,
				//	'ws'			=> is_object($this->alibabaHelper) ? $this->alibabaHelper->aaAlibabaWS : null,
				//),
				//'envato'		=> array(
				//	'helper'		=> $this->envatoHelper,
				//	'ws'			=> is_object($this->envatoHelper) ? $this->envatoHelper->aaEnvatoWS : null,
				//),
				'ebay'        => array(
					'helper'        => $this->ebayHelper,
					'ws'            => is_object($this->ebayHelper) ? $this->ebayHelper->aaWooZoneEbayWS : null,
				),
			);
			return $arr["$provider"]["$what"];
		}

		// update 2018-feb - get webservice object
		public function get_ws_object_new( $provider, $what='helper', $pms=array() ) {
			$pms = array_replace_recursive(array(
				'the_plugin' 	=> null,
				'settings' 		=> array(),
				'params_new' 	=> array(),
			), $pms);
			extract( $pms );

			$provider_prefix = $provider;
			if ( 'amazon' == $provider ) {
				$provider_prefix = 'amz';
			}

			$provider_4helper = 'WooZoneLite'.ucfirst($provider).'Helper';
			$provider_4ws = 'aa'.('amazon' != $provider ? 'WooZone' : '').ucfirst($provider).'WS';

			if ( 'amazon' == $provider && 'newapi' === $this->amzapi ) {
				$provider_4ws .= 'New';
			}

			//ebay.helper.class.php
			//amz.helper.class.php
			if ( 'new_helper' == $what ) {
				require_once( $the_plugin->cfg['paths']['plugin_dir_path'] . "aa-framework/helpers/{$provider_prefix}.helper.class.php" );

				$amzHelper = null;
				if ( class_exists( $provider_4helper ) ) {
					$amzHelper = new $provider_4helper( $the_plugin, $params_new );
					//$amzHelper = $provider_4helper::getInstance( $the_plugin );
				}
				return $amzHelper;
			}
			else if ( 'new_ws' == $what ) {
				$this->wsStatus["$provider"] = array( 'status' => 'valid', 'exception' => null, 'msg' => "$provider webservice class default!" );

				// load the amazon webservices client class
				if ( 'ebay' == $provider ) {
					$getEbayWS = $this->ebay_addon_controller( 'file_ws', array() );
					if ( isset($getEbayWS['file_path']) ) {
						require( $getEbayWS['file_path'] );
					}
				}
				else {
					require_once( $the_plugin->cfg['paths']['plugin_dir_path'] . "/lib/scripts/{$provider}/{$provider_4ws}.class.php" );
				}

				$aaAmazonWS = null;
				if ( class_exists( $provider_4ws ) ) {
					try {
						// create new webservice instance
						if ( 'amazon' == $provider ) {
							//DEBUG
							//if (1) { $params_new['AccessKeyID'] = ''; }

							//$associateTag = $this->main_aff_id();
							$associateTag = isset($params_new['associateTag']) ? trim($params_new['associateTag']) : '';
							if ( '' === $associateTag ) {
								$associateTag = $this->getAssociateTagByCountry( $params_new['country'] );
							}
							//var_dump('<pre>', $associateTag , '</pre>');

							$aaAmazonWS = new $provider_4ws(
								$params_new['AccessKeyID'],
								$params_new['SecretAccessKey'],
								$params_new['country'],
								$associateTag
							);
						}
						else if ( 'ebay' == $provider ) {
							//DEBUG
							//if (1) { $params_new['ebay_DEVID'] = ''; }

							$aaAmazonWS = new $provider_4ws(
								$params_new['ebay_DEVID'],
								$params_new['ebay_AppID'],
								$params_new['ebay_CertID'],
								$params_new['ebay_country'],
								$params_new['ebay_main_aff_id'] //$this->the_plugin->main_aff_id()
							);
						}
					}
					catch (Exception $e) {

						$msg = WooZoneLiteGetExceptionMsg( $e );

						$this->wsStatus["$provider"] = array_replace_recursive( $this->wsStatus["$provider"], array(
							'status' 	=> 'invalid',
							'exception' => $e,
							'msg' 		=> $msg,
						));
					}
				}
				else {
					$this->wsStatus["$provider"] = array_replace_recursive( $this->wsStatus["$provider"], array(
						'status' 	=> 'invalid',
						//'exception' => $e,
						'msg' 		=> "$provider:aws.init.issue: cannot find $provider webservice class!",
					));
				}

				if ( is_object($aaAmazonWS) ) {
					$aaAmazonWS->set_the_plugin( $the_plugin, $settings );
				}
				return $aaAmazonWS;
			}

			return $this->get_ws_object( $provider, $what );
		}

		public function prodid_get_provider_alias( $id ) {
			if ( empty($id) ) {
				return 'zzz';
			}
			$_id = explode('-', $id);
			return count($_id) > 1 ? $_id[0] : 'amz';
		}

		public function prodid_get_asin( $id ) {
			if ( empty($id) ) {
				return ''; //'9999999';
			}
			$_id = explode('-', $id);
			return count($_id) > 1 ? $_id[1] : $id;
		}

		public function prodid_get_provider_by_asin( $id ) {

			return $this->prodid_get_provider( $this->prodid_get_provider_alias($id) );
		}

		public function prodid_get_provider( $alias ) {
			$wslist = $this->get_ws_prefixes();
			foreach ($wslist as $key => $wsprefix) {
				if ( $alias == $wsprefix ) {
					return $key;
				}
			}
			return '';
		}

		public function prodid_set( $id, $provider, $what ) {
			$ret = array();
			$alias = $this->get_ws_prefixes($provider);

			if (empty($id)) return $id;
			$isa = is_array($id) ? true : false;

			if ( !$isa ) {
				$id = array($id);
			}
			foreach ($id as $key => $val) {
				if (empty($val)) {
					$ret["$key"] = $val;
					continue;
				}
				if ( 'add' == $what ) {
					//$ret["$key"] = $val;
					//if ( !preg_match('/^('.$alias.').*/imu', $val, $m) ) {
					//	$ret["$key"] = $alias.'-' . $val;
					//}

					//change made in 2018-09-27
					$val = preg_replace('/^('.$alias.'-)/imu', '', $val); // to remove cases like AMZ | AmZ | EBY | EBy etc
					$ret["$key"] = $alias.'-' . $val;
				}
				else if ( 'sub' == $what ) {
					//$ret["$key"] = str_replace($alias.'-', '', $val);

					//change made in 2018-09-27
					$ret["$key"] = preg_replace('/^('.$alias.'-)/imu', '', $val);
				}
			}
			if ( !$isa ) {
				return $ret[0];
			}
			return $ret;
		}

		public function set_product_meta_asset( $post_id, $metas=array() ) {
			foreach ($metas as $key => $val) {
				update_post_meta( $post_id, $key, $val );
			}
		}

		public function multi_implode_keyval($array, $glue) {
			$ret = '';

			foreach ($array as $key => $item) {
				if (is_array($item)) {
						$ret .= $this->multi_implode($item, $glue) . $glue;
				} else {
						$ret .= ($key . ': ' . $item) . $glue;
				}
			}

			$ret = substr($ret, 0, 0-strlen($glue));

			return $ret;
		}

		/**
		 * 2016, february
		 */
		public function is_module_active( $alias, $is_admin=true ) {
			$cfg = $this->cfg;

			$ret = false;

			// is module activated?
			if ( isset($cfg['modules'], $cfg['modules'][$alias], $cfg['modules'][$alias]['load_in']) ) {
				$ret = true;
			}
			// fix in 2018-jan
			else if ( isset($cfg['modules'], $cfg['modules'][$alias], $cfg['modules'][$alias][$alias]['load_in']) ) {
				$ret = true;
			}

			// is module in admin section?
			if ( $is_admin && !$this->is_admin ) {
				//$ret = false;
			}

			return $ret;
		}

		public function debug_get_country() {
			$ip = $this->get_client_ip();
			$country = $this->get_country_perip_external();

			var_dump('<pre>',$ip, $country,'</pre>');
			echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}

		public function bulk_wp_exist_post_by_args( $args ) {
			global $wpdb;
			//$result = $wpdb->get_row("SELECT * FROM " . ( $wpdb->prefix ) . "posts WHERE 1=1 and post_status = '" . ( $args['post_status'] ) . "' and post_title = '" .  ( $args['post_title'] )  . "'", 'ARRAY_A');
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . ( $wpdb->prefix ) . "posts WHERE 1=1 and post_type IN ('product', 'product_variation') and post_status = '" . ( $args['post_status'] ) . "' and post_title = %s", $args['post_title'] ), 'ARRAY_A' );
			if ( is_array($result) && ! empty($result) ) {
				return $result;
			}
			return false;
		}

		public function product_by_asin( $asins=array() ) {
			$asins = array_unique( array_filter($asins) );
			if (empty($asins)) return array();

			$key = '_amzaff_prodid';
			$_key = '_amzASIN';

			$return = array_fill_keys( $asins, false );

			global $wpdb;

			$asins_ = implode(',', array_map(array($this, 'prepareForInList'), $asins));

			$sql_asin2id = "select pm.meta_value as asin, p.* from " . $wpdb->prefix.'posts' . " as p left join " . $wpdb->prefix.'postmeta' . " as pm on p.ID = pm.post_id where 1=1 and !isnull(p.ID) and pm.meta_key = '$key' and pm.meta_value != '' and pm.meta_value in ($asins_);";
			$res_asin2id = $wpdb->get_results( $sql_asin2id, OBJECT_K );
			//var_dump('<pre>', $res_asin2id , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( !empty($res_asin2id) ) {
				foreach ($res_asin2id as $k => $v) {
					$asin = $v->asin;
					$return["$asin"] = $v;
				}
			}

			// because we have old amazon products which have only '_amzASIN' meta (they don't have this new '_amzaff_prodid' meta)
			$sql_asin2id = "select pm.meta_value as asin, p.* from " . $wpdb->prefix.'posts' . " as p left join " . $wpdb->prefix.'postmeta' . " as pm on p.ID = pm.post_id where 1=1 and !isnull(p.ID) and pm.meta_key = '$_key' and pm.meta_value != '' and pm.meta_value in ($asins_);";
			$res_asin2id = $wpdb->get_results( $sql_asin2id, OBJECT_K );
			//var_dump('<pre>', $res_asin2id , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( !empty($res_asin2id) ) {
				foreach ($res_asin2id as $k => $v) {

					$_asin = $v->asin;
					$_asin = $this->prodid_set($_asin, 'amazon', 'add');
					$_asin_sub = $this->prodid_set($_asin, 'amazon', 'sub');

					$v->asin = $_asin;
					$res_asin2id["$k"]->asin = $_asin;

					if ( ! isset($return["$_asin"]) || empty($return["$_asin"]) ) {
						$return["$_asin"] = $v;
					}
					if ( ! isset($return["$_asin_sub"]) || empty($return["$_asin_sub"]) ) {
						$return["$_asin_sub"] = $v;
					}
				}
			}

			//var_dump('<pre>', $return , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			return $return;
		}


		/**
		 * March 2016 - new methods
		 */
		public function get_aateam_demo_keys() {
			$demo_keys = array(
				// this is the text by which we know that he uses aa-team demo keys
				'alias' 		=> array(
					'AccessKeyID' 		=> 'aateam demo access key',
					'SecretAccessKey' 	=> 'aateam demo secret access key',
				),
				// these are pairs of keys which if are found setted, we interpret them as he really wants aa-team demo keys
				// !!! empty value keys & keys as above alias are by default iterpreted as demo
				// for each pair: index 0 = AccessKeyID, index 1 = SecretAccessKey
				'pairs' 		=> array(),
			);
			$demo_keys['pairs'] = $this->_get_aateam_demo_keys();
			return $demo_keys;
		}

		private function _get_aateam_demo_keys() {

			$file = $this->cfg['paths']['plugin_dir_path'] . "_keys/demokeys.txt.php";
			$filec = file_get_contents( $file );
			if ( ! $filec ) {
				$filec = $this->wp_filesystem->get_contents( $file );
			}
			if ( ! $filec ) {
				return array();
			}

			if ( '1D8CCE0EA0831C8373F042FCC625032B' !== strtoupper(md5($filec)) ) {
				return array();
			}

			$keys = base64_decode($filec);
			$keys = explode('|||', $keys);
			if ( ! is_array($keys) || 2 !== count($keys) ) {
				return array();
			}
			return array( array( $keys[0], $keys[1] ) );
		}

		public function verify_amazon_keys( $pms=array() ) {

			$pms = array_replace_recursive(array(
				'settings' 	=> array(),
			), $pms);
			extract( $pms );

			$is_custom = ! empty( $settings ) && is_array($settings) ? true : false;

			// aa-team demo keys - March 2016 - new /Update on 2017-10-03
			$demo_keys = $this->get_aateam_demo_keys();

			$ret = array(
				// valid | invalid | demo
				'status' 			=> '',

				// -3 = just a default value
				// -2 = alias text keys
				// -1 = empty value keys
				// >=0 = demo keys pair
				'pair_idx' 		=> -3,

				// amazon settings
				'settings' 		=> array(),
			);

			if ( ! $is_custom ) {
				$amz_settings = $this->amz_settings;
				$settings = array(
					'AccessKeyID' 		=> isset($amz_settings['AccessKeyID'])
						? trim($amz_settings['AccessKeyID']) : '',
					'SecretAccessKey' 	=> isset($amz_settings['SecretAccessKey'])
						? trim($amz_settings['SecretAccessKey']) : '',
				);
			}
			$ret['settings'] = $settings;

			// current keys from db
			$current_keys = array(
				'AccessKeyID' 		=> isset($settings['AccessKeyID'])
					? trim($settings['AccessKeyID']) : '',
				'SecretAccessKey' 	=> isset($settings['SecretAccessKey'])
					? trim($settings['SecretAccessKey']) : '',
			);
			//var_dump('<pre>',$current_keys,'</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			// default keys status
			$status = 'valid';

			// at least one key for a pair is setted as ( empty value | demo alias text ) => demo keys
			$_status = array();
			foreach ($current_keys as $key_id => $key_val) {
				if ( '' == $key_val ) {
					$status = 'demo';
					$ret['pair_idx'] = -1;
					break;
				}

				if ( $key_val == $demo_keys['alias']["$key_id"] ) {
					$status = 'demo';
					$_status[] = $key_id;
				}
			}
			// if full pair of both keys is found => we don't mark them again in db - see below step
			if ( ( 2 == count($_status) ) && ( 'demo' == $status ) ) {
				$status = 'demo999';
				$ret['pair_idx'] = -2;
			}

			// verify if curenty keys are demo keys: both keys value from each pair must match
			if ( 'valid' == $status ) {
				foreach ($demo_keys['pairs'] as $pair_idx => $pair_set) {
					//if ( preg_match('/^demo/i', $status) ) break;

					$_status = true;
					foreach ($current_keys as $key_id => $key_val) {
						$__kdidx = 'AccessKeyID' == $key_id ? 0 : 1;

						if ( $key_val != $pair_set[$__kdidx] ) {
							$_status = false;
							break;
						}
					}

					if ( $_status ) {
						$status = 'demo';
						$ret['pair_idx'] = $pair_idx;
						break;
					}
				}
			}

			// mark demo keys in database with "demo text"
			if ( 'demo' == $status ) {
				if ( ! $is_custom ) {
					$amz_settings = $this->settings();
					$amz_settings = !empty($amz_settings) && is_array($amz_settings) ? $amz_settings : array();

					foreach ($demo_keys['alias'] as $key_id => $key_val) {
						$amz_settings["$key_id"] = $key_val;
					}
					update_option( $this->alias . '_amazon', $amz_settings ); // 'WooZoneLite_amazon'
				}
			}

			// make demo keys usable in amazon settings: use first found pair
			if ( preg_match('/^demo/i', $status) ) {
				$status = 'demo';

				// make demo keys usable
				foreach ($demo_keys['alias'] as $key_id => $key_val) {

					$__kdidx = 'AccessKeyID' == $key_id ? 0 : 1;
					$__kd = isset($demo_keys['pairs'][0], $demo_keys['pairs'][0][$__kdidx])
						? $demo_keys['pairs'][0][$__kdidx] : '';

					if ( ! $is_custom ) {
						$this->amz_settings["$key_id"] = $__kd;
					}
					$ret['settings']["$key_id"] = $__kd;
				}
			}

			$ret['status'] = $status;
			return $ret;
		}

		// number of products imported using aa-team demo keys
		// toret = nb (number of products) | idlist (list of product ids)
		public function get_products_demo_keys( $toret='nb' ) {
			$db = $this->db;
			$table = $db->postmeta;

			if ( 'nb' == $toret ) {
				$sql = "select count(pm.meta_id) as nb from $table as pm where 1=1 and pm.meta_key = '_amzaff_aateam_keys' and pm.meta_value = '1';";
				$res = $db->get_var( $sql );
				return (int) $res;
			}
			else {
				$sql = "select pm.post_id from $table as pm where 1=1 and pm.meta_key = '_amzaff_aateam_keys' and pm.meta_value = '1';";
				$res = $db->get_results( $sql, OBJECT_K );
				if ( empty($res) ) return array();
				return array_keys( $res );
			}
			return false;
		}

		// allowed: to import products using aa-team demo keys
		public function is_allowed_products_demo_keys() {
			$ret = $this->get_products_demo_keys() < $this->ss['max_products_demo_keys'] ? true : false;
			return $ret;
		}

		// allowed: to make remote requests to aa-team demo server
		public function is_allowed_remote_requests() {
			$ret = $this->get_amazon_request_remote_number() < $this->ss['max_remote_request_number'] ? true : false;
			return $ret;
		}

		// is: aa-team demo keys
		public function is_aateam_demo_keys() {
			$_status = $this->verify_amazon_keys();
			$_status = $_status['status'];

			$ret = 'demo' == $_status ? true : false;
			return $ret;
		}

		// aa-team demo server, not whole server, just the wp install for demo keys
		public function is_aateam_server() {
			//$ret = ('cc.aa-team.com' == $_SERVER['SERVER_NAME'])
			//	|| ('46.101.188.140' == $_SERVER['SERVER_ADDR']);
			//return $ret;

			if ( defined('WOOZONELITE_KEYS_SERVER') && WOOZONELITE_KEYS_SERVER ) {
				return true;
			}
			return false;
		}
		// aa-team development / dev server
		public function is_aateam_devserver() {
			if ( defined('WOOZONELITE_DEV_SERVER') && WOOZONELITE_DEV_SERVER ) {
				return true;
			}
			return false;
		}

		public function can_import_products() {
			// we are using aa-team demo keys
			// and
			// we are NOT on aa-team demo server
			if ( $this->is_aateam_demo_keys() ) {

				// we are allowed to import products using aa-team demo keys
				if ( $this->is_allowed_products_demo_keys() ) {
					return true;
				}
				else {
					return false;
				}
			}
			return true;
		}

		// 2018-05-07: return always FALSE, we've disabled this functionality
		// conditions are fulfilled for this to be a remote request to aa-team demo server
		public function do_remote_amazon_request( $what_rules=array() ) {
			return false;

			// we are using aa-team demo keys
			// and
			// we are NOT on aa-team demo server, not whole server, just the wp install for demo keys
			if ( $this->is_aateam_demo_keys() && ! $this->is_aateam_server() ) {

				// we are allowed to import products using aa-team demo keys
				if ( $this->is_allowed_products_demo_keys() ) {
					return true;
				}
				else {
					return false;
				}
			}
			return false;
		}

		// get remote request from aa-team demo server
		public function get_remote_amazon_request( $pms=array() ) {
			$ret = array(
				'status' 		=> 'invalid',
				'msg' 			=> '',
				'response' 		=> array(),
				'code' 			=> -1,
				'amz_code' 		=> '',
			);

			$remote_url = self::$aateam_keys_script . '?' . http_build_query(array(
				'action'            => 'amazon_request',
				'what_func'         => isset($pms['what_func']) ? $pms['what_func'] : '',
			));

			$params = array_merge(array(), $pms, array(
				'__request' => array(
					'client_ip'         => $this->get_client_ip(),
					'client_website'    => get_site_url(),
					'country'           => isset($this->amz_settings['country']) ? $this->amz_settings['country'] : 'com',
				),
			));
			if ( isset($params['amz_settings']) ) {
				$params['amz_settings'] = array(
					// NOT SENDing access keys for security concerns!
					'AccessKeyID'           => '', //$params['amz_settings']['AccessKeyID'],
					'SecretAccessKey'       => '', //$params['amz_settings']['SecretAccessKey'],

					'main_aff_id'           => isset($params['amz_settings']['main_aff_id']) ? $params['amz_settings']['main_aff_id'] : '',
					'country'               => isset($params['amz_settings']['country']) ? $params['amz_settings']['country'] : '',
				);
				//unset( $params['amz_settings'] );
			}
			//var_dump('<pre>', $remote_url, $params, '</pre>');
			//echo __FILE__ . ":" . __LINE__; die . PHP_EOL;

			$response = wp_remote_post( $remote_url, array(
				'method' => 'POST',
				'timeout' => 30,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => $params
			));
			//var_dump('<pre>', $response['body'], '</pre>');
			//echo __FILE__ . ":" . __LINE__; die . PHP_EOL;

			// If there's error
			if ( is_wp_error( $response ) ){
				return array_merge($ret, array('msg' => $response->get_error_message()));
			}
			$body = wp_remote_retrieve_body( $response );
			//var_dump('<pre>', $body, '</pre>'); die('debug...');

			if ( !function_exists('simplexml_load_string') ) {
				return array_merge($ret, array('msg' => 'Function simplexml_load_string don\'t exists!'));
			}

			if( strpos((string)$body, '<response>') === false ) {
				return array_merge($ret, array('msg' => 'Invalid xml response retrieved from aa-team server!'));
			}
			//var_dump('<pre>', $body, '</pre>'); die('debug...');

			$body = simplexml_load_string( $body );

			 $resp = array(
				'status' 		=> isset($body->status) ? (string) $body->status : 'invalid',
				'msg' 			=> isset($body->msg) ? (string) $body->msg : 'unknown error',
				'response' 		=> isset($body->body) ? (string) $body->body : '',
				'code' 			=> isset($body->code) ? (string) $body->code : -1,
				'amz_code' 		=> isset($body->amz_code) ? (string) $body->amz_code : '',
			 );

			// validate response
			 if ( empty($resp['response']) ) {
				$resp['response'] = array();
			 }

			 $resp['response'] = maybe_unserialize( $resp['response'] );

			 if ( empty($resp['response']) || !is_array($resp['response']) ) {
				$resp['response'] = array();
			 }

			return $resp;
		}

		// save last requests to amazon: local or from aa-team demo server
		public function save_amazon_last_requests( $new=array() ) {
			$max_last_keep = 50;

			$last = get_option('WooZoneLite_last_amazon_requests', array());

			if ( !isset($last['items']) || !is_array($last['items']) ) {
				$last = array(
						'items'     => array()
				);
			}

			if ( count($last['items']) >= $max_last_keep ) {
				array_shift($last['items']); // remove oldes maintained row
			}

			//'amz_settings'            => $this->amz_settings,
			//'from_file'               => str_replace($this->cfg['paths']['plugin_dir_path'], '', __FILE__),
			//'from_func'               => __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
						// add new latest row
			$last['items'][] = array(
				'time'              => time(),
				'amazon'            => array(
					'AccessKeyID'           => $new['amz_settings']['AccessKeyID'],
					'SecretAccessKey'       => $new['amz_settings']['SecretAccessKey'],
					'main_aff_id'           => isset($new['amz_settings']['main_aff_id']) ? $new['amz_settings']['main_aff_id'] : '',
					'country'               => isset($new['amz_settings']['country']) ? $new['amz_settings']['country'] : '',
				),
				'from_file'         => $new['from_file'],
				'from_func'         => $new['from_func'],
				'status'            => $new['request_status']['status'],
				'msg'               => $new['request_status']['msg'],
				'is_remote'         => isset($new['is_remote']) && $new['is_remote'] ? 1 : 0,
			);

			update_option('WooZoneLite_last_amazon_requests', $last);
		}

		// get last requests to amazon: local or from aa-team demo server
		public function get_amazon_last_requests() {
			$last = get_option('WooZoneLite_last_amazon_requests', array());
			return $last;
		}

		// notice to show amazon requests: local or from aa-team demo sever
		public function print_demo_request()
		{
			return $this->_admin_notice_amazon_keys();
		}

		public function _admin_notice_amazon_keys( $print=true )
		{
			ob_start();
		?>
		<div class="WooZoneLite-callout WooZoneLite-callout-info WooZoneLite-demo-keys">

			<h4><?php
				echo $this->api_requests_show();
			?></h4>

			<h4><?php
				//_e( sprintf(
				//	// '<strong>%s</strong> &#8211; You are using AA-Team DEMO keys ( AccessKeyID = <span class="marked">%s</span>, SecretAccessKey = <span class="marked">%s</span> ) and you\'ve made <span class="marked">%s</span> amazon requests (<span class="marked">%s</span> remote).',
				//	'<strong>%s</strong> &#8211; You are using AA-Team DEMO keys and you\'ve made <strong>%s</strong> requests to amazon and <strong>%s</strong> remote requests.',
				//	$this->pluginName,
				//	// '',
				//	// '',
				//	$this->get_amazon_request_number(),
				//	$this->get_amazon_request_remote_number()
				//), $this->localizationName );
				if ( $this->is_aateam_demo_keys() ) {
					$msg = sprintf(
						'<strong>%s</strong> &#8211; You are using AA-Team DEMO keys and you\'ve made <strong>%s</strong> requests to amazon and <strong>%s</strong> remote requests.',
						$this->pluginName,
						$this->get_amazon_request_number(),
						$this->get_amazon_request_remote_number()
					);
				}
				else {
					$msg = sprintf(
						'<strong>%s</strong> &#8211; You\'ve made <strong>%s</strong> requests to amazon and <strong>%s</strong> remote requests.',
						$this->pluginName,
						$this->get_amazon_request_number(),
						$this->get_amazon_request_remote_number()
					);
				}
				_e( $msg, 'woozonelite' );
			?></h4>
			<?php
				$html = array();
				$last = $this->get_amazon_last_requests();
				$last = isset($last['items']) ? (array) $last['items'] : array();
				$last = array_reverse($last, true);
				if ( !empty($last) ) {
					$html[] = '<div class="last-requests" id="WooZoneLite-list-rows">';
					$html[] = '<a href="#" class="WooZoneLite-form-button-small WooZoneLite-form-button-primary">' . __('view last requests', 'woozonelite') . '</a>';
					$html[] = '<table class="WooZoneLite-table" style="width: 100%">';
					$html[] =   '<thead>';
					$html[] =       '<tr>';
					$html[] =           '<th>';
					$html[] =               __('Time', 'woozonelite');
					$html[] =           '</th>';
					$html[] =           '<th width="300">';
					$html[] =               __('From file', 'woozonelite');
					$html[] =           '</th>';
					$html[] =           '<th width="400">';
					$html[] =               __('From function', 'woozonelite');
					$html[] =           '</th>';
					$html[] =           '<th width="100">';
					$html[] =               __('Status', 'woozonelite');
					$html[] =           '</th>';
					$html[] =           '<th width="200">';
					$html[] =               __('Status message', 'woozonelite');
					$html[] =           '</th>';
					$html[] =       '</tr>';
					$html[] =   '</thead>';
					$html[] =   '<tfoot>';
					$html[] =   '</tfoot>';
					$html[] =   '<tbody>';
				}
				foreach ($last as $key => $val) {
					$html[] =       '<tr>';
					$html[] =           '<td>';
					$html[] =               $this->last_update_date(true, $val['time']);
					$html[] =           '</td>';
					$html[] =           '<td>';
					$html[] =               $val['from_file'];
					$html[] =           '</td>';
					$html[] =           '<td>';
					$html[] =               $val['from_func'];
					$html[] =           '</td>';
					$html[] =           '<td>';
					$html[] =               $val['status'];
					$html[] =           '</td>';
					$html[] =           '<td>';
					$html[] =               '<div class="status-msg">'
												. (isset($val['is_remote']) && $val['is_remote'] ? 'Remote | ' : '')
												. $val['msg']
											. '</div>';
					$html[] =           '</td>';
					$html[] =       '</tr>';
				}
				if ( !empty($last) ) {
					$html[] =   '</tbody>';
					$html[] = '</table>';
					$html[] = '</div>';
				}
				echo implode(PHP_EOL, $html);
			?>
		</div>
		<?php
			$contents = ob_get_clean();

			if ( $print ) echo $contents;
			else return $contents;
		}

		public function print_section_header( $title='', $desc='', $docs_url='')
		{
			$html = array();

			$html[] = '<div class="panel panel-default ' . ( $this->alias ) . '-panel ' . ( $this->alias ) . '-section-header">';
			$html[] =   '<div class="panel-heading ' . ( $this->alias ) . '-panel-heading">';
			if( trim($title) != "" )    $html[] =       '<h1 class="panel-title ' . ( $this->alias ) . '-panel-title">' . ( $title ) . '</h1>';
			if( trim($desc) != "" )     $html[] =       $desc;
			$html[] =   '</div>';
			$html[] =   '<div class="panel-body ' . ( $this->alias ) . '-panel-body ' . ( $this->alias ) . '-no-padding" >';


			if( trim($docs_url) != "" ) $html[] =       '<a href="' . ( $docs_url ) . '" target="_blank" class="' . ( $this->alias ) . '-tab"><i class="' . ( $this->alias ) . '-icon-support"></i>  Documentation</a>';
			$html[] =       '<a href="' . ( $this->plugin_row_meta( 'portfolio' ) ) . '?ref=AA-Team" target="_blank" class="' . ( $this->alias ) . '-tab"><i class="' . ( $this->alias ) . '-icon-other_products"></i> More AA-Team Products</a>';
			$html[] =   '</div>';
			$html[] = '</div>';

			return implode(PHP_EOL, $html);
		}

		public function get_image_sizes_allowed() {
			$wp_sizes = $this->u->get_image_sizes();

			$allowed = isset($this->amz_settings['images_sizes_allowed'])
				? $this->amz_settings['images_sizes_allowed'] : array();
			$allowed = $this->clean_multiselect( $allowed );
			$allowed = !empty($allowed) && is_array($allowed) ? $allowed : array();

			if ( empty($allowed) ) return $wp_sizes;
			foreach ( $wp_sizes as $size => $props ) {
				if ( !in_array($size, $allowed) ) {
					unset($wp_sizes["$size"]);
				}
			}
			return $wp_sizes;
		}


		// 2016-july
		/**
		 * item			: A. result of amazon helper file / build_product_data | B. full api response array
		 * is_filtered	: true => you use A. ; false => you use B.
		 * retWhat	: what product description to retrieve: both | desc | short
		 */
		public function product_build_desc( $item=array(), $is_filtered=true, $retWhat='both' ) {

			$retProd = array_replace_recursive(array(
				'ws' 					=> 'amazon',
				'EditorialReviews'		=> '',
				'Feature'				=> '',
				'ASIN'					=> '',
				'hasGallery'			=> 'false',
			), $item);

			// parse full amazon api response
			if ( !$is_filtered ) {

				$retProd = array(
					'ws' => isset($retProd['ws']) ? $retProd['ws'] : '',
				);

				$EditorialReviews = isset($item['EditorialReviews']['EditorialReview']['Content'])
						? $item['EditorialReviews']['EditorialReview']['Content'] : '';

				// try to rebuid the description if it's empty
				if( trim($EditorialReviews) == "" ){
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
						$EditorialReviews = implode( "\n", $new_description );
					}
				}

				$retProd['EditorialReviews'] = $EditorialReviews;

				$retProd['Feature'] = isset($item['ItemAttributes']['Feature']) ? $item['ItemAttributes']['Feature'] : '';

				$retProd['hasGallery'] = 'false';
			}

			if ( isset($item['__parent_asin']) ) {
				$retProd['ASIN'] = isset($item['__parent_asin']) ? $item['__parent_asin'] : '';
			}
			if ( isset($item['__parent_content']) ) {
				if ( preg_match('/\[gallery\]/imu', $item['__parent_content']) ) {
					$retProd['hasGallery'] = 'true';
				}
			}

			// short description
			$show_short_description = isset($this->amz_settings['show_short_description'])
				? $this->amz_settings['show_short_description'] : 'yes';

			$is_short_desc = $show_short_description;

			$excerpt = '';
			if ( $is_short_desc ) {
				// first 3 paragraph
				$excerpt = @explode("\n", @strip_tags( implode("\n", $retProd['Feature']) ) );
				$excerpt = @implode("\n", @array_slice($excerpt, 0, 3));
			}

			// full description
			$__desc = array();
			$__desc[] = ($retProd['hasGallery'] == 'true' ? "[gallery]" : "");
			if ( $retProd['ws'] == 'amazon' ) {
				$__desc[] = !empty($retProd['EditorialReviews']) ? $retProd['EditorialReviews'] : '';
				$__desc[] = (count($retProd['Feature']) > 0 &&  is_array($retProd['Feature']) == true ? implode("\n", $retProd['Feature']) : '');

				// [amz_corss_sell asin="B01G7TG6SW"]
				$cross_selling = (isset($this->amz_settings["cross_selling"]) && $this->amz_settings["cross_selling"] == 'yes' ? true : false);

				if( $cross_selling == true ) {
					$__desc[] = '[amz_corss_sell asin="' . ( $retProd['ASIN'] ) . '"]';
				}
			}
			else {
				$__desc[] = isset($retProd['Description']) && !empty($retProd['Description']) ? $retProd['Description'] : '';
			}

			//var_dump('<pre>', $__desc , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			$desc = implode("\n", array_filter($__desc));
			//var_dump('<pre>', $desc , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			if ( 'both' == $retWhat ) {
				return array(
					'short'			=> $excerpt,
					'desc'			=> $desc,
				);
			}
			if ( 'desc' == $retWhat ) {
				return $desc;
			}
			return $excerpt;
		}

		public function product_clean_desc( $post_content ) {
			$__post_content = $post_content;
			$__post_content = preg_replace('/\[gallery\]/imu', '', $__post_content);

			// [amz_corss_sell asin="B01G7TG6SW"]
			$__post_content = preg_replace('/\[amz_corss_sell asin\=".*"\]/imu', '', $__post_content);
			$__post_content = trim( $__post_content );
			return $__post_content;
		}

		// Determine if SSL is used.
		public function is_ssl() {
			if ( isset($_SERVER['HTTPS']) ) {
				if ( 'on' == strtolower($_SERVER['HTTPS']) )
					return true;
				if ( '1' == $_SERVER['HTTPS'] )
					return true;
			} elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
				return true;
			}

			// HTTP_X_FORWARDED_PROTO: a de facto standard for identifying the originating protocol of an HTTP request, since a reverse proxy (load balancer) may communicate with a web server using HTTP even if the request to the reverse proxy is HTTPS
			if ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ) {
				if ( 'https' == strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) )
					return true;
			}
			if ( isset($_SERVER['HTTP_X_FORWARDED_SSL']) ) {
				if ( 'on' == strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) )
					return true;
				if ( '1' == $_SERVER['HTTP_X_FORWARDED_SSL'] )
					return true;
			}
			return false;
		}


		/**
		 * 2016-september - for amzstore plugin
		 */
		// in wp_options we have options like 'amzStore' but in version >= 9.0 I've changed plugin alias, so we have options like 'AmzStore"
		public function fix_dbalias_issue() {
			$ret = array('status' => 'invalid', 'msg' => 'unknown msg.', 'count' => array());
			if ( 'AmzStore' != $this->alias ) return $ret;

			$found = get_option('AmzStore_fixed_dbalias', false);

			// already fixed
			if ( $found ) return $ret;

			global $wpdb;
			$db = $wpdb;
			$table = $db->prefix . 'options';

			// old version settings
			// MySQL queries are not case-sensitive by default.
			// If you need to make a case-sensitive query, it is very easy to do using the BINARY operator, which forces a byte by byte comparison
			$sql = "select option_id, option_name, option_value from $table where 1=1 and option_name regexp binary '^amzStore_' order by option_name asc;";
			//var_dump('<pre>',$sql,'</pre>');
			$res = $db->get_results( $sql, OBJECT );
			if ( empty($res) ) {
				update_option('AmzStore_fixed_dbalias', true);
				return array_merge($ret, array('status' => 'valid', 'msg' => 'error or none found: trying to retrieve old version settings.'));
			}

			// new version 9.0 settings
			$sql90 = "select option_name, option_value from $table where 1=1 and option_name regexp binary '^AmzStore_' order by option_name asc;";
			$res90 = $db->get_results( $sql90, OBJECT_K );
			if ( empty($res90) ) {
				//return array_merge($ret, array('status' => 'valid', 'msg' => 'error or none found: trying to retrieve new version 9.0 settings.'));
			}
			foreach ($res90 as $key => $val) {
				$res90["$key"] = $val->option_value;
			}

			$ccupd = 0; $ccdel = 0; $ccupd_old = 0;
			foreach ($res as $val) {
				$option_id 		= $val->option_id;
				$option_name 	= $val->option_name;
				$option_value 	= $val->option_value;

				// amzStore_ option names become __amzStore_
				if ( 1 ) {
					$sqlupd_old = "update $table set option_name = concat('__', option_name) where 1=1 and option_name = binary %s;";
					$sqlupd_old = $db->prepare( $sqlupd_old, $option_name );
					$resupd_old = $db->query( $sqlupd_old );
					if ( $resupd_old ) ++$ccupd_old;
				}

				$option_name_new = str_replace('amzStore', 'AmzStore', $option_name);

				$option_value = maybe_unserialize( $option_value );
				$option_value = maybe_unserialize( $option_value ); // old version prior to 9.0 had a bug of double serialize for some options
				$option_value = maybe_serialize( $option_value );

				// add new option based on old setting value
				if ( isset($res90["$option_name_new"]) ) { // already exists
					$sqlupd = "update $table set option_value = %s where 1=1 and option_name = binary %s;";
					$sqlupd = $db->prepare( $sqlupd, $option_value, $option_name_new );
					$resupd = $db->query( $sqlupd );
					if ( $resupd ) ++$ccupd;
				}
				else {
					$sqlupd = "insert into $table (option_name, option_value) values (%s, %s);";
					$sqlupd = $db->prepare( $sqlupd, $option_name_new, $option_value );
					$resupd = $db->query( $sqlupd );
					if ( $resupd ) ++$ccupd;
				}
			} // end foreach

			update_option('AmzStore_fixed_dbalias', true);
			return array_merge($ret, array('status' => 'valid', 'msg' => 'successfull: old version settings fixed.', 'count' => array(
				'ccupd'			=> $ccupd,
				'ccdel'			=> $ccdel,
				'ccupd_old'	=> $ccupd_old,
			)));
			//return $ret;
		}


		/**
		 * 2016-october - for product country check
		 */
		// from ADF
		public function discount_convert_country2country() {
			$countries = array(
				'com' 	=> array('us', 'com', 'united-states', 'United States'),
				'uk' 	=> array('gb', 'co.uk', 'united-kingdom', 'United Kingdom'),
				'de' 	=> array('de', 'de', 'germany', 'Germany'),
				'fr' 	=> array('fr', 'fr', 'france', 'France'),
				'jp' 	=> array('jp', 'co.jp', 'japan', 'Japan'),
				'ca' 	=> array('ca', 'ca', 'canada', 'Canada'),
				'cn'	=> array('cn', 'cn', 'china', 'China'),
				'in' 	=> array('in', 'in', 'india', 'India'),
				'it' 	=> array('it', 'it', 'italy', 'Italy'),
				'es' 	=> array('es', 'es', 'spain', 'Spain'),
				'mx' 	=> array('mx', 'com.mx', 'mexico', 'Mexico'),
				'br' 	=> array('br', 'com.br', 'brazil', 'Brazil'),
				'au' 	=> array('au', 'com.au', 'australia', 'Australia'),
				'ae' 	=> array('ae', 'ae', 'ae', 'UAE')
			);
			$ret = array(
				'fromip' 	=> array(),
				'amzwebsite' => array(),
				'tovalues' 	=> array(),
				'totitles' 	=> array()
			);
			foreach ($countries as $k => $v) {
				$ret['fromip']["$k"] = $v[0];
				$ret['amzwebsite']["$k"] = $v[1];
				$ret['tovalues']["$k"] = $v[2];
				$ret['totitles']["$k"] = $v[3];
			}
			return $ret;
		}

		// build a return array of type amzForUser from a domain key
		public function domain2amzForUser( $domain ) {
			$convertCountry = $this->discount_convert_country2country();
			$country_key = 'com';
			if ( in_array($domain, $convertCountry['amzwebsite']) ) {
				$country_key = array_search($domain, $convertCountry['amzwebsite']);
			}
			$ipcountry = isset($convertCountry['fromip']["$country_key"]) ? $convertCountry['fromip']["$country_key"] : 'us';
			$ipcountry = strtoupper($ipcountry);

			$country = $this->amzForUser( $ipcountry );

			$country_name = isset($convertCountry['totitles']["$country_key"]) ? $convertCountry['totitles']["$country_key"] : 'United States';
			$country = array_merge($country, array(
				'name' => $country_name,
			));
			return $country;
		}

		public function get_aff_ids() {
			$main_aff_id = $this->main_aff_id();

			$config = $this->amz_settings;
			$aff_ids = array();
			if ( isset($config['AffiliateID'])
				&& !empty($config['AffiliateID'])
				&& is_array($config['AffiliateID'])
			) {
				foreach ( $config['AffiliateID'] as $key => $val ) {
					if ( !empty($val) ) {
						$_key = $this->get_amazon_country_site( $key );
						$aff_ids[] = array(
							'country' 		=> $_key,
							'aff_id'			=> $val,
						);
					}
				}
			}
			return array(
				'main_aff_id'			=> $main_aff_id,
				'aff_ids'					=> $aff_ids,
			);
		}

		public function get_country_from_url( $url, $provider='amazon' ) {
			$country = '';
			if ( empty($url) ) return $country;

			if ( 'amazon' == $provider ) {
				$regex = "/https?:\/\/(?:.+\.)amazon\.([^\/]*)/imu";
			}
			else if ( 'ebay' == $provider ) {
				//http://www.ebay.com/itm/Vintage-Stained-Rustic-Wood-Crates-/322042522061
				$regex = "/https?:\/\/(?:www\.|)([^\/]*)/imu";
			}
			$found = preg_match($regex, $url, $m);
			if ( false !== $found ) {
				$country = $m[1];
			}
			return $country;
		}

		public function delete_post_attachments( $post_id ) {
			global $wpdb, $post_type;

			//check if is product
			$_post_type = get_post_type($post_id); //$post_type
			if ( ! in_array($_post_type, array('product', 'product_variation')) ) return;
			if ( ! is_int( $post_id ) || $post_id <= 0 ) return;

			//$ids = get_children(array(
			//	'post_parent' => $post_id,
			//	'post_type' => 'attachment'
			//));
			//$ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_parent = $post_id AND post_type = 'attachment'");
			//if (empty($ids)) return;
			//foreach ( $ids as $id ) {
			//	wp_delete_attachment( $id, true );
			//}

			if (1) {
				$args = array(
					'post_type'   => 'attachment',
					'post_parent' => $post_id,
					'post_status' => 'any',
					'nopaging'    => true,

					// Optimize query for performance.
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				);
				$query = new WP_Query( $args );

				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();

						wp_delete_attachment( $query->post->ID, true );
					}
				}
				wp_reset_postdata();
			}
		}


		/**
		 * Plugin Version
		 */
		// latest code version
		public function version() {
			if ( defined('WOOZONELITE_VERSION') ) {
				$this->version = (string) WOOZONELITE_VERSION;
				return $this->version;
			}

			$path = $this->cfg['paths']['plugin_dir_path'] . 'plugin.php';
			if ( function_exists('get_plugin_data') ) {
				$plugin_data = get_plugin_data( $path );
			}
			else {
				$plugin_data = WooZoneLite_get_plugin_data();
			}

			$latest_version = '1.0';
			if( isset($plugin_data) && is_array($plugin_data) && !empty($plugin_data) ){
				if ( isset($plugin_data['Version']) ) {
					$latest_version = (string)$plugin_data['Version'];
				}
				else if ( isset($plugin_data['version']) ) {
					$latest_version = (string)$plugin_data['version'];
				}
			}

			$this->version = $latest_version;
			return $this->version;
		}

		private function check_if_table_exists( $force=false ) {
			$need_check_tables = $this->plugin_integrity_need_verification('check_tables');
			if ( ! $need_check_tables['status'] && ! $force ) {
				return true; // don't need verification yet!
			}

			// default sql - tables & tables data!
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'modules/setup_backup/default-sql.php' );

			// retrieve all database tables & clean prefix
			$dbTables = $this->db->get_results( "show tables;", OBJECT_K );
			$dbTables = array_keys( $dbTables );
			if ( empty($dbTables) || ! is_array($dbTables) ) {

				$this->plugin_integrity_update_time('check_tables', array(
					'status'		=> 'invalid',
					'html'		=> __('Check plugin tables: error requesting tables list.', 'woozonelite'),
				));
				return false; //something was wrong!
			}

			$dbTables_ = array();
			foreach ((array) $dbTables as $table) {
				$table_noprefix = str_replace($this->db->prefix, '', $table);
				$dbTables_[] = $table_noprefix;
			}

			// our plugin tables
			$dbTables_own = $this->plugin_tables;

			// did we find all our plugin tables?
			$dbTables_found = (array) array_intersect($dbTables_, $dbTables_own);
			$dbTables_missing = array_diff($dbTables_own, $dbTables_found);
			//var_dump('<pre>', $dbTables_own, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( ! $dbTables_missing ) {

				$this->plugin_integrity_update_time('check_tables', array(
					'timeout'	=> time(),
					'status'		=> 'valid',
					'html'		=> __('Check plugin tables: all installed ( ' . implode(', ', $dbTables_found) . ' ).', 'woozonelite'),
				));
				return true; // all is fine!
			}

			$this->plugin_integrity_update_time('check_tables', array(
				'status'		=> 'invalid',
				'html'		=> __('Check plugin tables: missing ( ' . implode(', ', $dbTables_missing) . ' ).', 'woozonelite'),
			));
			return false; //something was wrong!
		}

		private function update_db_version( $version=null ) {
			delete_option( 'WooZoneLite_db_version' );
			$version = empty($version) ? $this->version() : $version;
			add_option( 'WooZoneLite_db_version', $version );
		}

		public function update_db( $force=false ) {
			// current installed db version
			//$current_db_version = get_option( 'WooZoneLite_db_version' );
			//$current_db_version = !empty($current_db_version) ? (string)$current_db_version : '1.0';

			// added new amazon location 'australia'
			$amazon_location_check = get_option('WooZoneLite_amazon_location_check', array());
			$amazon_location_check = ! is_array($amazon_location_check) ? array() : $amazon_location_check;
			if ( ! in_array('com.au', $amazon_location_check) ) {
				$force = true;

				$amazon_location_check[] = 'com.au';
				$amazon_location_check = array_unique( array_filter($amazon_location_check) );
				update_option('WooZoneLite_amazon_location_check', $amazon_location_check);
			}

			// default db structure - integrity verification is done in function
			$this->check_if_table_exists( $force );

			$this->check_table_generic( 'amz_locale_reference', $force, array() ); // update 2018-feb
			$this->check_table_generic( 'amz_amzkeys', $force, array() ); // update 2018-feb
			$this->check_table_generic( 'amz_amazon_cache', $force, array( 'must_have_rows' => false ) ); // update 2018-apr
			$this->check_table_generic( 'amz_import_stats', $force, array( 'must_have_rows' => false ) ); // update 2019-jan-17
			$this->check_table_generic( 'amz_sync_widget', $force, array( 'must_have_rows' => false ) ); // update 2019-apr-24
			$this->check_table_generic( 'amz_sync_widget_asins', $force, array( 'must_have_rows' => false ) ); // update 2019-apr-24

			$need_check_cronjobs_prefix = $this->plugin_integrity_need_verification('check_cronjobs_prefix');

			$need_check_alter_tables = $this->plugin_integrity_need_verification('check_alter_tables');
			$need_check_alter_table_amz_queue = $this->plugin_integrity_need_verification('check_alter_table_amz_queue'); // added 2018-04-11
			$need_check_alter_table_amz_oct18 = $this->plugin_integrity_need_verification('check_alter_table_amz_oct18'); // added 2018-10-12

			//:: need_check_alter_tables
			//if ( version_compare( $current_db_version, '9.0', '<' ) ) {
			if ( $need_check_alter_tables['status'] || $force ) {
				// installed version less than 9.0 / ex. 8.4.1.3
				$table_name = $this->db->prefix . "amz_assets";
				if ( $this->db->get_var("show tables like '$table_name'") == $table_name ) {
					$this->_update_db_tables(array(
						'opt_name' 		=> 'check_alter_tables',
						'operation'		=> $table_name,
						'table'			=> $table_name,
						'queries'		=> array(
							'image_sizes'	  => array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `image_sizes` TEXT NULL;",
								'verify'		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'image_sizes';",
								'field_name'	=> 'image_sizes',
								'field_type'	=> 'text',
							),
							'download_status' => array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `download_status` VARCHAR(20) NULL DEFAULT 'new' COMMENT 'new, success, inprogress, error, remote';",
								'verify' 		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'download_status';",
								'field_name'	=> 'download_status',
								'field_type'	=> 'varchar(20)',
							),
						),
					));
				}

				// installed version less than 9.0.3.3
				$table_name = $this->db->prefix . "amz_cross_sell";
				if ( $this->db->get_var("show tables like '$table_name'") == $table_name ) {
					$this->_update_db_tables(array(
						'opt_name' 		=> 'check_alter_tables',
						'operation'		=> $table_name,
						'table'			=> $table_name,
						'queries'		=> array(
							'is_variable'	=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `is_variable` CHAR(1) NULL DEFAULT 'N';",
								'verify'		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'is_variable';",
								'field_name'	=> 'is_variable',
								'field_type'	=> 'char(1)',
							),
							'nb_tries'		=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `nb_tries` TINYINT(1) UNSIGNED NULL DEFAULT '0';",
								'verify' 		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'nb_tries';",
								'field_name'	=> 'nb_tries',
								'field_type'	=> 'tinyint(1)',
							),
						),
					));
				}

				// occures with some clients servers
				$table_name = $this->db->prefix . "amz_queue";
				if ( $this->db->get_var("show tables like '$table_name'") == $table_name ) {
					$this->_update_db_tables(array(
						'opt_name' 		=> 'check_alter_tables',
						'operation'		=> $table_name,
						'table'			=> $table_name,
						'queries'		=> array(
							'nb_tries'		=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `nb_tries` SMALLINT(1) UNSIGNED NOT NULL;",
								'verify' 		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'nb_tries';",
								'field_name'	=> 'nb_tries',
								'field_type'	=> 'smallint(1)',
							),
							'nb_tries_prev'	=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `nb_tries_prev` SMALLINT(1) UNSIGNED NOT NULL;",
								'verify' 		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'nb_tries_prev';",
								'field_name'	=> 'nb_tries_prev',
								'field_type'	=> 'smallint(1)',
							),
							'from_op'		=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `from_op` VARCHAR(30) NOT NULL;",
								'verify' 		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'from_op';",
								'field_name'	=> 'from_op',
								'field_type'	=> 'varchar(30)',
							),
						),
					));
				}

			}

			//:: added on 2018-04-11
			// if check_alter_table_amz_queue
			if ( $need_check_alter_table_amz_queue['status'] || $force ) {

				$table_name = $this->db->prefix . "amz_queue";
				if ( $this->db->get_var("show tables like '$table_name'") == $table_name ) {
					$this->_update_db_tables(array(
						'opt_name' 		=> 'check_alter_table_amz_queue',
						'operation'		=> $table_name,
						'table'			=> $table_name,
						'queries'		=> array(
							// columns
							'product_title'	=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `product_title` TEXT NULL;",
								'verify' 		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'product_title';",
								'field_name'	=> 'product_title',
								'field_type'	=> 'text',
							),
							'country'	=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `country` VARCHAR(10) NOT NULL DEFAULT '';",
								'verify'		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'country';",
								'field_name'	=> 'country',
								'field_type'	=> 'varchar(10)',
							),
						),
						// !!!must be after queries to be sure that all columns exists!
						// index_name, index_type, index_cols: all are mandatory
						'indexes'		=> array(
							'country'		=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s (`country`);",
								'verify'		=> "SHOW INDEX FROM " . $table_name . " WHERE 1=1 and Key_name LIKE 'country';",
								'index_name'	=> 'country',
								'index_type'	=> 'key',
								'index_cols'	=> array('country'),
							),
						),
					));
				}

			}

			//:: added on 2018-10-12
			// if check_alter_table_amz_oct18
			if ( $need_check_alter_table_amz_oct18['status'] || $force ) {

				// installed version less than 10.2
				$table_name = $this->db->prefix . "amz_queue";
				if ( $this->db->get_var("show tables like '$table_name'") == $table_name ) {
					$this->_update_db_tables(array(
						'opt_name' 		=> 'check_alter_table_amz_oct18',
						'operation'		=> $table_name,
						'table'			=> $table_name,
						'queries'		=> array(
							'country'	=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `country` VARCHAR(30) NOT NULL DEFAULT '';",
								'verify'		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'country';",
								'field_name'	=> 'country',
								'field_type'	=> 'varchar(30)',
							),
							'provider'	=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `provider` VARCHAR(20) NOT NULL DEFAULT 'amazon';",
								'verify'		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'provider';",
								'field_name'	=> 'provider',
								'field_type'	=> 'varchar(20)',
							),
						),
						// !!!must be after queries to be sure that all columns exists!
						// index_name, index_type, index_cols: all are mandatory
						'indexes'		=> array(
							'provider'		=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s (`provider`);",
								'verify'		=> "SHOW INDEX FROM " . $table_name . " WHERE 1=1 and Key_name LIKE 'provider';",
								'index_name'	=> 'provider',
								'index_type'	=> 'key',
								'index_cols'	=> array('provider'),
							),
						),
					));
				}

				// installed version less than 10.2
				$table_name = $this->db->prefix . "amz_search";
				if ( $this->db->get_var("show tables like '$table_name'") == $table_name ) {
					$this->_update_db_tables(array(
						'opt_name' 		=> 'check_alter_table_amz_oct18',
						'operation'		=> $table_name,
						'table'			=> $table_name,
						'queries'		=> array(
							'country'	=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `country` VARCHAR(30) NOT NULL DEFAULT '';",
								'verify'		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'country';",
								'field_name'	=> 'country',
								'field_type'	=> 'varchar(30)',
							),
						),
					));
				}

				// installed version less than 10.2
				$table_name = $this->db->prefix . "amz_amazon_cache";
				if ( $this->db->get_var("show tables like '$table_name'") == $table_name ) {
					$this->_update_db_tables(array(
						'opt_name' 		=> 'check_alter_table_amz_oct18',
						'operation'		=> $table_name,
						'table'			=> $table_name,
						'queries'		=> array(
							'country'	=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `country` VARCHAR(30) NOT NULL DEFAULT '';",
								'verify'		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'country';",
								'field_name'	=> 'country',
								'field_type'	=> 'varchar(30)',
							),
							'provider'	=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s COLUMN `provider` VARCHAR(20) NOT NULL DEFAULT 'amazon';",
								'verify'		=> "SHOW COLUMNS FROM " . $table_name . " LIKE 'provider';",
								'field_name'	=> 'provider',
								'field_type'	=> 'varchar(20)',
							),
						),
						// !!!must be after queries to be sure that all columns exists!
						// index_name, index_type, index_cols: all are mandatory
						'indexes'		=> array(
							'provider'		=> array(
								'main' 			=> "ALTER TABLE " . $table_name . " %s (`provider`);",
								'verify'		=> "SHOW INDEX FROM " . $table_name . " WHERE 1=1 and Key_name LIKE 'provider';",
								'index_name'	=> 'provider',
								'index_type'	=> 'key',
								'index_cols'	=> array('provider'),
							),
						),
					));
				}
			}

			//:: installed version less than 9.0 / ex. 8.4.1.3
			// update cronjobs prefix in wp_options / option name like 'cron'
			if ( $need_check_cronjobs_prefix['status'] || $force ) {
				$this->update_cronjobs();

				$this->plugin_integrity_update_time('check_cronjobs_prefix', array(
					'timeout'	=> time(),
					'status'		=> 'valid',
					'html'		=> __('Check cronjobs prefix: OK.', 'woozonelite'),
				));
			}

			// installed version less than 9.0 / ex. 8.4.1.3
			$this->update_db_version('9.0');

			// update installed version to latest
			$this->update_db_version();
			return true;
		}

		public function _update_db_tables( $pms=array() )  {
			//require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			//$status = dbDelta($sql);

			extract( $pms );

			global $wpdb;
			// queries columns
			foreach ( (array) $queries as $skey => $sql ) {
				if ( ! isset($sql['main']) ) continue 1;

				$do_main = 'add';
				if ( isset($sql['verify']) ) {
					$status = $wpdb->get_row( $sql['verify'], ARRAY_A );
					if ( ! empty($status) && isset($status['Field'], $status['Type']) ) {

						//'image_sizes' == strtolower($status['Field'])
						if ( isset($sql['field_type']) ) {
							if ( strtolower($sql['field_type']) == strtolower( $status['Type'] ) )
								$do_main = false;
							else
								$do_main = 'modify';
						}
					}
				} // end if verify

				if ( !empty($do_main) ) {
					$sql['main'] = sprintf( $sql['main'], strtoupper( $do_main ) );
					$status = $wpdb->query( $sql['main'] );
					//var_dump('<pre>', $sql, $status, '</pre>');
				}
			} // end foreach

			// queries indexes
			//ADD KEY newkeyname | DROP KEY oldkeyname, ADD KEY newkeyname
			if ( isset($indexes) ) { foreach ( (array) $indexes as $skey => $sql ) {
				if ( ! isset($sql['main']) ) continue 1;

				$index_name = isset($sql['index_name']) ? $sql['index_name'] : $skey;
				$index_type = isset($sql['index_type']) ? $sql['index_type'] : 'key';
				$index_cols = isset($sql['index_cols']) ? $sql['index_cols'] : array();

				$do_main = 'add';
				if ( isset($sql['verify']) ) {
					$status = $wpdb->get_results( $sql['verify'], ARRAY_A );

					$cols = array();
					if ( ! empty($status) ) {
						foreach ($status as $idxKey => $idxVal) {
							$cols[] = $idxVal['Column_name'];
						}
						$cols = array_unique( array_filter( $cols) );
						$diff = array_diff($index_cols, $cols);

						if ( ! empty($diff) )
							$do_main = 'modify';
						else
							$do_main = false;
					}
				} // end if verify

				if ( !empty($do_main) ) {
					$do_main2 = array();
					if ( 'modify' == $do_main ) {
						$do_main2[] = 'DROP ' . strtoupper($index_type) . ' ' . $index_name;
					}
					$do_main2[] = 'ADD ' . strtoupper($index_type) . ' ' . $index_name;
					$do_main = implode(', ', $do_main2);

					$sql['main'] = sprintf( $sql['main'], $do_main );
					$status = $wpdb->query( $sql['main'] );
					//var_dump('<pre>', $sql, $status, '</pre>');
				}
			} } // end foreach & if

			//if ( $this->db->prefix . "psp_link_redirect" == $operation ) {
			//	echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			//}

			$this->plugin_integrity_update_time($opt_name, array(
				'timeout'	=> time(),
				'status'		=> 'valid',
				'html'		=> sprintf( __('Check plugin tables (alter): %s OK.', 'woozonelite'), $opt_name ),
			));
		}

		public function update_options_prefix( $what='use_old' ) {
			$ret = array('status' => 'invalid', 'msg' => 'unknown msg.');

			$db = $this->db;
			$table = $db->prefix . 'options';

			if ( 'use_new' == $what ) {
				return array_merge($ret, array('status' => 'valid', 'msg' => 'successfull: you choose to use the new version settings, disregarding the old version settings.'));
			}
			else if ( 'use_old' == $what ) {

				// old version settings
				$sql = "select option_id, option_name, option_value from $table where 1=1 and option_name regexp '^wwcAmzAff' order by option_name asc;";
				$res = $db->get_results( $sql, OBJECT );
				if ( empty($res) ) {
					return array_merge($ret, array('status' => 'valid', 'msg' => 'error or none found: trying to retrieve old version settings.'));
				}

				// new version 9.0 settings
				$sql90 = "select option_name, option_value from $table where 1=1 and option_name regexp '^WooZoneLite' order by option_name asc;";
				$res90 = $db->get_results( $sql90, OBJECT_K );
				if ( empty($res90) ) {
					return array_merge($ret, array('status' => 'valid', 'msg' => 'error or none found: trying to retrieve new version 9.0 settings.'));
				}
				foreach ($res90 as $key => $val) {
						$res90["$key"] = $val->option_value;
				}

				$ccupd = 0; $ccdel = 0;
				foreach ($res as $val) {
					$option_id 		= $val->option_id;
					$option_name 	= $val->option_name;
					$option_value 	= $val->option_value;

					$option_name_new = str_replace('wwcAmzAff', $this->alias, $option_name);

					// delete current new version setting if exist
					//$sqldel = "delete from $table where 1=1 and option_name = %s;";
					//$sqldel = $db->prepare( $sqldel, $option_name_new );
					//$resdel = $db->query( $sqldel );
					//if ( $resdel ) ++$ccdel;

					$option_value = maybe_unserialize( $option_value );
					$option_value = maybe_unserialize( $option_value ); // old version prior to 9.0 had a bug of double serialize for some options
					$option_value = maybe_serialize( $option_value );

					// add new option based on old setting value
					if ( isset($res90["$option_name_new"]) ) { // already exists
						$sqlupd = "update $table set option_value = %s where 1=1 and option_name = %s;";
						$sqlupd = $db->prepare( $sqlupd, $option_value, $option_name_new );
						$resupd = $db->query( $sqlupd );
						if ( $resupd ) ++$ccupd;
					}
					else {
						$sqlupd = "insert into $table (option_name, option_value) values (%s, %s);";
						$sqlupd = $db->prepare( $sqlupd, $option_name_new, $option_value );
						$resupd = $db->query( $sqlupd );
						if ( $resupd ) ++$ccupd;
					}

					// replace new version setting with old version setting
					// !!! THIS WOULD REPLACE OLD VERSION SETTINGS - MAYBE WE SHOULD KEEP OLD VERSION SETTINGS FOR NOW
					//$sqlupd = "update $table set option_name = %s where 1=1 and option_id = %s;";
					//$sqlupd = $db->prepare( $sqlupd, $option_name_new, $option_id );
					//$resupd = $db->query( $sqlupd );
					//if ( $resupd ) ++$ccupd;
				}
				return array_merge($ret, array('status' => 'valid', 'msg' => 'successfull: you choose to use the old version settings, the new version settings were replaced.'));
			}
			return $ret;
		}

		public function update_cronjobs() {
			$ret = array('status' => 'invalid', 'msg' => 'unknown msg.');

			$db = $this->db;
			$table = $db->prefix . 'options';

			$sql = "SELECT option_id, option_name, option_value FROM $table WHERE 1=1 and option_name = 'cron';";
			$res = $db->get_results( $sql, OBJECT );
			if ( empty($res) ) {
				return array_merge($ret, array('status' => 'valid', 'msg' => 'not found'));
			}

			foreach ($res as $val) {
				$option_id 		= $val->option_id;
				$option_name 	= $val->option_name;
				$option_value 	= $val->option_value;

				$option_value = maybe_unserialize( $option_value );
				if ( empty($option_value) || !is_array($option_value) ) continue 1;

				foreach ($option_value as $kk => $vv) {
					if ( !is_array($vv) ) continue 1;
					foreach ($vv as $kk2 => $vv2) {
						if ( preg_match('/^wwcAmzAff/iu', $kk2) ) { // wwcAmzAff | WooZoneLite
							unset($option_value["$kk"]["$kk2"]);
						}
					}
				}

				foreach ($option_value as $kk => $vv) {
					if ( empty($vv) ) {
						unset($option_value["$kk"]);
					}
				}

				$option_value = serialize( $option_value );

				$sqlupd = "update $table set option_value = %s where 1=1 and option_id = %s;";
				$sqlupd = $db->prepare( $sqlupd, $option_value, $option_id );
				$resupd = $db->query( $sqlupd );
			}

			//$sql = "SELECT option_id, option_name, option_value FROM $table WHERE 1=1 and option_name = 'cron';";
			//$res = $db->get_results( $sql, OBJECT );
			//var_dump('<pre>', $res, '</pre>'); die('debug...');

			return array_merge($ret, array('status' => 'valid', 'msg' => 'ok'));
		}


		/**
		 * Plugin is ACTIVE
		 */
		// verify plugin is ACTIVE (the right way)
		public function is_plugin_active( $plugin_name, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'verify_active_for_network_only'		=> false,
				'verify_network_only_plugin'			=> false,
				'plugin_file'							=> array(), // verification is made by OR between items
				'plugin_class'							=> array(), // verification is made by OR between items
			), $pms);
			extract( $pms );

			switch ( strtolower($plugin_name) ) {
				case 'woocommerce':
					$plugin_file = array( 'woocommerce/woocommerce.php', 'envato-wordpress-toolkit/woocommerce.php' );
					$plugin_class = array( 'WooCommerce' );
					break;

				case 'woozonelite':
					$plugin_file = array( 'woozonelite/plugin.php' );
					$plugin_class = array( 'WooZoneLite' );
					break;

				case 'psp':
					$plugin_file = array( 'premium-seo-pack/plugin.php' );
					$plugin_class = array( 'psp' );
					break;

				case 'w3totalcache':
					$plugin_file = array( 'w3-total-cache/w3-total-cache.php' );
					break;

				// Additional Variation Images Plugin for WooCommerce
				case 'avi':
					$plugin_file = array( 'additional-variation-images/plugin.php' );
					$plugin_class = array( 'AVI' );
					break;

				// CompareAzon
				case 'compareazon':
					$plugin_file = array( 'compareazon/plugin.php' );
					$plugin_class = array( 'CT' );
					break;


				// WooZoneLite Provider Ebay
				case 'aawzone-ebay':
					$plugin_file = array( 'aawzone-ebay/plugin.php' );
					$plugin_class = array( 'WooZoneProviderEbay' );
					break;

				default:
					break;
			}

			$is_active = array();

			// verify plugin is active base on plugin main file
			if ( ! empty($plugin_file) ) {
				if ( ! is_array($plugin_file) )
					$plugin_file = array( $plugin_file );

				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

				$cc = false;
				foreach ($plugin_file as $_plugin_file) {
					// check if a plugin is site wide or network active only
					if ( $verify_active_for_network_only ) {
						if ( is_plugin_active_for_network( $_plugin_file ) )
							$cc = true;
					}
					// check if a plugin is a Network-Only-Plugin
					else if ( $verify_network_only_plugin ) {
						if ( is_network_only_plugin( $_plugin_file ) )
							$cc = true;
					}
					// check if a plugin is active (the right way)
					else {
						if ( is_plugin_active( $_plugin_file ) )
							$cc = true;
					}
				}
				$is_active[] = $cc;
			}

			// verify plugin class exists!
			if ( ! empty($plugin_class) ) {
				if ( ! is_array($plugin_class) )
					$plugin_class = array( $plugin_class );

				$cc = false;
				foreach ($plugin_class as $_plugin_class) {
					if ( class_exists( $_plugin_class ) )
						$cc = true;
				}
				$is_active[] = $cc;
			}

			// final verification
			if ( empty($is_active) ) return false;
			foreach ($is_active as $_is_active) {
				if ( ! $_is_active ) return false;
			}
			return true;
		}
		public function is_plugin_active_for_network_only( $plugin_name, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'verify_active_for_network_only'		=> true,
			), $pms);
			return $this->is_plugin_active( $plugin_name, $pms );
		}
		public function is_plugin_network_only_plugin( $plugin_name, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'verify_network_only_plugin'				=> true,
			), $pms);
			return $this->is_plugin_active( $plugin_name, $pms );
		}

		public function is_woocommerce_installed() {
			$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

			if (
				in_array( 'envato-wordpress-toolkit/woocommerce.php', $active_plugins )
				|| in_array( 'woocommerce/woocommerce.php', $active_plugins )
				|| is_multisite()
			) {
				return true;
			}
			else {
				if ( !empty($active_plugins) && is_array($active_plugins) ) {
					foreach ( $active_plugins as $key => $val ) {
						if ( ($status = preg_match('/^woocommerce[^\/]*\/woocommerce\.php$/imu', $val))!==false && $status > 0 ) {
							return true;
						}
					}
				}
				return false;
			}
		}

		public function is_plugin_avi_active() {
			return $this->is_plugin_active( 'AVI' );
		}

		public function is_plugin_CT_active() {
			return $this->is_plugin_active( 'CT' );
		}

		public function is_plugin_aawzoneebay_active() {
			return $this->is_plugin_active( 'aawzone-ebay' );
		}


		/**
		 * check plugin integrity: 2017-feb-28
		 */
		// what: check_database
		public function plugin_integrity_check( $what='all', $force=false ) {
			$what = ! is_array($what) ? array('check_database') : $what;

			if ( in_array('check_database', $what) ) {
				$this->update_db( $force );
			}
		}

		public function plugin_integrity_get_last_status( $what ) {
			$ret = array(
				'status'				=> true,
				'html'				=> '',
			);

			// verify plugin integrity
			$plugin_integrity = get_option( 'WooZoneLite_integrity_check', array() );
			$plugin_integrity = is_array($plugin_integrity) ? $plugin_integrity : array();

			$_status = true; $_html = array();
			if ( isset($plugin_integrity[ "$what" ]) && ! empty($plugin_integrity[ "$what" ]) ) {
				$__ = $plugin_integrity[ "$what" ];
				$_status = isset($__['status']) && 'valid' == $__['status'] ? true : false;
				$_html[] = $__['html'];
			}
			else {
				if ( 'check_database' == $what ) {
					foreach ($plugin_integrity as $key => $val) {
						if ( ! in_array($key, array(
							'check_tables',
							'check_alter_tables',
							'check_cronjobs_prefix',

							'check_table_amz_locale_reference',
							'check_table_amz_amzkeys',
							'check_table_amz_amazon_cache',
							'check_table_amz_import_stats',
							'check_table_amz_sync_widget',
							'check_table_amz_sync_widget_asins',

							'check_alter_table_amz_queue',
							'check_alter_table_amz_oct18',
						)) ) {
							continue 1;
						}

						$_status = $_status && ( isset($val['status']) && 'valid' == $val['status'] ? true : false );
						if ( ! empty($val['html']) ) {
							$_html[] = $val['html'];
						}
					}
				}
			}

			//$html = '<div><div>' . implode('</div><div>', $_html) . '</div></div>';
			$html = implode('&nbsp;&nbsp;&nbsp;&nbsp;', $_html);
			$ret = array_merge( $ret, array('status' => $_status, 'html' => $html) );
			return $ret;
		}

		// what: check_tables, check_alter_tables, check_cronjobs_prefix, etc...
		public function plugin_integrity_need_verification( $what ) {
			$ret = array(
				'status'			=> false,
				'data'				=> array(),
			);

			// verify plugin integrity
			$plugin_integrity = get_option( 'WooZoneLite_integrity_check', array() );
			$plugin_integrity = is_array($plugin_integrity) ? $plugin_integrity : array();
			$ret = array_merge( $ret, array('data' => $plugin_integrity) );

			if ( isset($plugin_integrity[ "$what" ]) && ! empty($plugin_integrity[ "$what" ]) ) {
				//var_dump('<pre>', $what, $plugin_integrity[ "$what" ] ,'</pre>');
				if ( ( $plugin_integrity[ "$what" ]['timeout'] + $this->ss['check_integrity'][ "$what" ] ) > time() ) {
					$ret = array_merge( $ret, array('status' => false) ); // don't need verification yet!
					//var_dump('<pre>',$ret,'</pre>');
					return $ret;
				}
			}

			$ret = array_merge( $ret, array('status' => true) );
			return $ret;
		}

		public function plugin_integrity_update_time( $what, $data=array() ) {
			$plugin_integrity = get_option( 'WooZoneLite_integrity_check', array() );
			$plugin_integrity = is_array($plugin_integrity) ? $plugin_integrity : array();

			$data = ! is_array($data) ? array() : $data;

			if ( ! isset($plugin_integrity[ "$what" ]) ) {
				$plugin_integrity[ "$what" ] = array(
					'timeout'	=> time(),
					'status'		=> 'invalid',
					'html'		=> '',
				);
			}
			$plugin_integrity[ "$what" ] = array_replace_recursive($plugin_integrity[ "$what" ], $data);
			update_option( 'WooZoneLite_integrity_check', $plugin_integrity );
		}

		public function is_debug_mode_allowed() {
			$ip = $this->get_client_ip();

			$debug_ip = isset($this->amz_settings['debug_ip']) && ! empty($this->amz_settings['debug_ip'])
				? trim($this->amz_settings['debug_ip']) : '';
			if ( ! empty($debug_ip) ) {
				$debug_ip = explode(',', $debug_ip);
				$debug_ip = array_map("trim", $debug_ip);

				if ( in_array($ip, $debug_ip) ) {
					return true;
				}
			}
			return false;
		}



		public function translatable_strings() {
			if( isset($this->amz_settings) && count($this->amz_settings) > 0 ){
				if( isset($this->amz_settings['string_trans']) && count($this->amz_settings['string_trans']) > 0 ){
					$cc = 0;
					foreach ($this->expressions as $key => $value) {
						if( isset($this->amz_settings['string_trans'][$cc]) ){
							$this->expressions[$key] = $this->amz_settings['string_trans'][$cc];
						}

						$cc++;
					}
				}
			}
		}

		public function _translate_string( $string='' ) {
			if( count($this->expressions) > 0 ){
				if( in_array( $string, array_keys($this->expressions)) ){
					return $this->expressions[$string];
				}
			}

			return $string;
		}



		// update 2017-nov
		public function get_country2mainaffid( $country, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'country2mainaffid' => true, // true = country to mainaffid OR false = mainaffid to country
				'com2us' 			=> true,
				'toupper' 			=> true,
				'uk2gb' 			=> false,
			), $pms);
			extract( $pms );

			$ret = '';

			if ( ! isset($country) || empty($country) ) {
				return $ret;
			}

			$arr = $country2mainaffid ? $this->country2mainaffid : array_flip( $this->country2mainaffid );
			if ( isset($arr["$country"]) ) {
				$ret = $arr["$country"];
			}

			if ( $com2us && ('com' == $ret) ) {
				$ret = 'us';
			}
			if ( $uk2gb && ( ('co.uk' == $ret) || ('uk' == $ret) ) ) {
				$ret = 'gb';
			}

			if ( $toupper ) {
				$ret = strtoupper( $ret );
			}
			return $ret;
		}

		// update 2017-nov
		public function get_mainaffid2country( $mainaffid, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'country2mainaffid' => false, // true = country to mainaffid OR false = mainaffid to country
				'com2us' 			=> false,
				'toupper' 			=> false,
				'withPrefixPoint' 	=> false,
			), $pms);
			extract( $pms );

			$ret = $this->get_country2mainaffid( $mainaffid, $pms );

			if ( $withPrefixPoint && ! empty($ret) ) {
				$ret = '.' . $ret;
			}
			return $ret;
		}

		public function init_plugin_attributes() {

			// disable amazon checkout?
			$this->amzapi = isset($this->amz_settings['amzapi'])
				&& in_array($this->amz_settings['amzapi'], array('oldapi', 'newapi'))
				? (string) $this->amz_settings['amzapi'] : 'newapi';

			// disable amazon checkout?
			$this->disable_amazon_checkout = isset($this->amz_settings['disable_amazon_checkout'])
				&& 'yes' == $this->amz_settings['disable_amazon_checkout'] ? true : false;

			// product type
			$this->p_type =
				isset($this->amz_settings['onsite_cart'])
				&& $this->amz_settings['onsite_cart'] == "no"
				? 'external' : 'simple';

			// make products without an offerlistingid as external
			$this->product_offerlistingid_missing_external = isset($this->amz_settings['product_offerlistingid_missing_external'])
				&& ( $this->amz_settings['product_offerlistingid_missing_external'] == 'yes' )
				? true : false;

			// ( delete | put in trash ) products (or variations childs) when syncing them
			$this->product_offerlistingid_missing_delete = isset($this->amz_settings['product_offerlistingid_missing_delete'])
				&& ( $this->amz_settings['product_offerlistingid_missing_delete'] == 'yes' )
				? true : false;

			// import amazon product missing offerlistingid
			$this->import_product_offerlistingid_missing = ! isset($this->amz_settings["import_product_offerlistingid_missing"])
				|| ( $this->amz_settings["import_product_offerlistingid_missing"] == 'yes' )
				? true : false;

			// import amazon product variation childs missing offerlistingid
			$this->import_product_variation_offerlistingid_missing = ! isset($this->amz_settings["import_product_variation_offerlistingid_missing"])
				|| ( $this->amz_settings["import_product_variation_offerlistingid_missing"] == 'yes' )
				? true : false;

			// product buy url is the original amazon url!
			$this->product_buy_is_amazon_url =
				!isset($this->amz_settings['product_buy_is_amazon_url'])
				|| (
					isset($this->amz_settings['product_buy_is_amazon_url'])
					&& $this->amz_settings['product_buy_is_amazon_url'] == 'yes'
				)
				? true : false;

			// get & show product short url (from bitly api)
			$this->product_url_short =
				isset($this->amz_settings['product_url_short'])
				&& $this->amz_settings['product_url_short'] == 'yes'
				? true : false;

			if ( ! in_array( 'product_url_short', $this->frontend_show_what() ) ) {
				$this->product_url_short = false;
			}

			// remote amazon images
			$is_ari = !isset($this->amz_settings['remote_amazon_images'])
				|| 'yes' == $this->amz_settings['remote_amazon_images'] ? true : false;
			//$is_ari = $is_ari && 'gimi' == $this->dev ? $is_ari : false; //IN DEVELOPMENT!
			//$is_ari = false; //DE-ACTIVATE!
			$this->is_remote_images = $is_ari;

			// product: delete | move to trash - ( when syncing or | delete zero priced bug fix)
			$this->products_force_delete =
				isset($this->amz_settings['products_force_delete'])
				&& $this->amz_settings['products_force_delete'] == 'yes'
				? true : false;

			// activate debugbar
			$this->debug_bar_activate =
				isset($this->amz_settings['debug_bar_activate'])
				&& $this->amz_settings['debug_bar_activate'] == 'no'
				? false : true;

			// gdpr - 25 may 2018
			$this->gdpr_rules_is_activated =
				isset($this->amz_settings['gdpr_rules_is_activated'])
				? (string) $this->amz_settings['gdpr_rules_is_activated'] : 'no';

			$this->frontend_hide_onsale_default_badge =
				isset($this->amz_settings['frontend_hide_onsale_default_badge'])
				? (string) $this->amz_settings['frontend_hide_onsale_default_badge'] : 'no';

			$this->frontend_show_free_shipping =
				isset($this->amz_settings['frontend_show_free_shipping'])
				? (string) $this->amz_settings['frontend_show_free_shipping'] : 'yes';

			$this->frontend_show_coupon_text =
				isset($this->amz_settings['frontend_show_coupon_text'])
				? (string) $this->amz_settings['frontend_show_coupon_text'] : 'yes';

			$this->show_availability_icon =
				isset($this->amz_settings['show_availability_icon'])
				? (string) $this->amz_settings['show_availability_icon'] : 'yes';

			$opt_badges_activated = array(
				'new' 			=> 'New',
				'onsale' 		=> 'On Sale',
				'freeshipping' 	=> 'Free Shipping',
				'amazonprime' 	=> 'Amazon Prime',
			);
			$this->badges_activated =
				isset($this->amz_settings['badges_activated'])
				? (array) $this->amz_settings['badges_activated'] : array_keys( $opt_badges_activated );
			$this->badges_activated = $this->clean_multiselect( $this->badges_activated );

			$opt_badges_where = array(
				'product_page' 			=> 'product page',
				'sidebar' 				=> 'sidebar',
				'minicart' 				=> 'minicart',
				'box_related_products' 	=> 'box related products',
				'box_cross_sell' 		=> 'box cross sell',
			);
			$this->badges_where =
				isset($this->amz_settings['badges_where'])
				? (array) $this->amz_settings['badges_where'] : array_keys( $opt_badges_where );
			$this->badges_where = $this->clean_multiselect( $this->badges_where );

			$this->dropshiptax = array(
				'activate' => isset($this->amz_settings['dropshiptax_activate'])
					? $this->amz_settings['dropshiptax_activate'] : 'no',

				'type' => isset($this->amz_settings['dropshiptax_type'])
					? $this->amz_settings['dropshiptax_type'] : 'proc',

				'value' => isset($this->amz_settings['dropshiptax_value'])
					? (float) $this->amz_settings['dropshiptax_value'] : 0,
			);

			$this->roundedprices = array(
				'activate' => isset($this->amz_settings['roundedprices_activate'])
					? $this->amz_settings['roundedprices_activate'] : 'no',

				'direction' => isset($this->amz_settings['roundedprices_direction'])
					? $this->amz_settings['roundedprices_direction'] : 'always_up',

				'decimals' => isset($this->amz_settings['roundedprices_decimals'])
					? (int) $this->amz_settings['roundedprices_decimals'] : 0,

				'marketing' => isset($this->amz_settings['roundedprices_marketing'])
					? $this->amz_settings['roundedprices_marketing'] : 'no',
			);
		}

		public function get_amazon_variations_nb( $prodvar=array(), $provider='amazon' ) {
			if ( empty($prodvar) || ! is_array($prodvar) ) {
				return 0;
			}

			if ( 'amazon' == $provider ) {
				if ( isset($prodvar['ASIN']) ) {
					return 1;
				}
				else {
					return count( $prodvar );
				}
			}
			else if ( 'ebay' == $provider ) {
				if ( $this->ebayHelper->ebay_variation_is_valid($prodvar) ) {
				//if ( isset($prodvar['VariationSpecifics']) || isset($prodvar['StartPrice']) || isset($prodvar['SKU']) ) {
					return 1;
				}
				else {
					return count( $prodvar );
				}
			}
			return 0;
		}

		// $retProd must be formated through method 'build_product_data' from amz.helper.class.php
		public function get_product_type_by_apiresponse( $retProd=array(), $provider='amazon' ) {

			$ret = array(
				'is_variable' => false,
				'is_variation_child' => false,
				'nb_variations' => 0,
				'product_type' => 'simple',
			);

			if ( 'amazon' === $provider ) {

				$is_variable = isset($retProd['Variations'], $retProd['Variations']['Item']);

				$is_variation_child = ('' != $retProd['ParentASIN'])
					&& ($retProd['ASIN'] != $retProd['ParentASIN']) ? true : false;

				$nb_variations = $is_variable
					? $this->get_amazon_variations_nb( $retProd['Variations']['Item'] ) : 0;
				$nb_variations = $nb_variations && isset($retProd['Variations']['TotalVariations'])
					? (int) $retProd['Variations']['TotalVariations'] : $nb_variations;
			}
			else if ( 'ebay' === $provider ) {

				$is_variable = isset($retProd['Variations'], $retProd['Variations']['Variation']);

				$is_variation_child = preg_match('/(?:.+)\/var-(?:.+)/imu', $retProd['ASIN'], $m);

				$nb_variations = $is_variable
					? $this->get_amazon_variations_nb( $retProd['Variations']['Variation'], 'ebay' ) : 0;
			}

			$product_type = $is_variable ? 'variable' : 'simple';
			if ( $is_variation_child ) {
				$product_type = 'variation';
			}

			$ret = array_replace_recursive( $ret, array(
				'is_variable' => $is_variable,
				'is_variation_child' => $is_variation_child,
				'nb_variations' => $nb_variations,
				'product_type' => $product_type,
			));
			return $ret;
		}

		// sync variations childs only if it's a variable product & must have maximum X (default = 10) total variations
		public function can_sync_variations( $retProd, $provider='amazon' ) {

			$opProductType = $this->get_product_type_by_apiresponse( $retProd, $provider );
			extract( $opProductType ); //is_variable, is_variation_child, nb_variations, product_type

			if ( ! $is_variable ) {
			//if ( 'variable' != $product_type ) {
				return false;
			}
			if ( ! $nb_variations || $nb_variations > 10 ) {
				return false;
			}
			return true;
		}


		/**
		 * BITLY related - 2018-jan
		 */
		public function bitly_api_shorten( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'longUrl' 	=> '',
				'domain' 	=> '',
			), $pms);
			extract( $pms );

			$ret = array(
				'status' 	=> 'invalid',
				'msg' 		=> '',
				'short_url' => '',
			);

			if ( '' == $longUrl ) {
				$ret = array_replace_recursive($ret, array(
					'msg' 		=> 'longUrl is empty!',
				));
				return $ret;
			}

			$access_token = get_option( 'WooZoneLite_bitly_access_token', '' );

			if ( '' == $access_token ) {
				$ret = array_replace_recursive($ret, array(
					'msg' 		=> 'bitly access token wasn\'t found!',
				));
				return $ret;
			}

			//:: make request to api
			$longUrl = $this->is_ssl() == true ? 'https:' . $longUrl : 'http:' . $longUrl;
			$uri = $this->bitly_oauth_api . "v3/shorten?access_token=" . $access_token . "&format=json&longUrl=" . urlencode($longUrl);
			if ( $domain != '' ) {
				$uri .= "&domain=" . $domain;
			}

			$input_params = array(
				'header'                        => true,
				'followlocation'                => true,
			);
			$output_params = array(
				'parse_headers'                 => true,
				'resp_is_json'                  => true,
				'resp_add_http_code'            => true,
			);
			$output = $this->curl( $uri, $input_params, $output_params, true );
			//var_dump('<pre>', $output , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			//:: end make request to api

			if ( $output['status'] === 'invalid' ) {
				$msg = sprintf( __('curl error; http code: %s; details: %s', 'woozonelite'), $output['http_code'], $output['data'] );
				//var_dump('<pre>', $msg , '</pre>'); echo __FILE__ . ":" . __LINE__; die . PHP_EOL;
				$ret = array_replace_recursive($ret, array(
					'msg' 		=> $msg,
				));
				return $ret;
			}

			$output = $output['data'];
			//var_dump('<pre>', $output , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			$output = json_decode( $output, true );

			if ( ! is_array($output) || ! isset($output['data'], $output['data']['url']) ) {

				$msg = 'bitly error; short url was not found in api response!';
				if ( is_array($output) && isset($output['status_code']) ) {
					$msg = sprintf( __('bitly error; status_code: %s; status_txt: %s', 'woozonelite'), $output['status_code'], $output['status_txt'] );
				}

				$ret = array_replace_recursive($ret, array(
					'msg' 		=> $msg,
				));
				return $ret;
			}

			if (1) {
				$result = array();

				$result['url'] = $output['data']['url'];
				//$result['hash'] = $output['data']['hash'];
				//$result['global_hash'] = $output['data']['global_hash'];
				//$result['long_url'] = $output['data']['long_url'];
				//$result['new_hash'] = $output['data']['new_hash'];

				//$ret['short_url'] = $result['url'];
				$ret = array_replace_recursive($ret, array(
					'status' 	=> 'valid',
					'msg' 		=> 'bitly short url was generated successfully.',
					'short_url' => $result['url'],
				));
				//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				return $ret;
			}
		}

		public function product_url_hash( $data ) {
			return hash_hmac( 'sha256', $data, 'woozonelite' );
		}

		public function product_url_from_bitlymeta( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'ret_what' 		=> 'only_get_meta', // only_get_meta | do_request | force_do_request
				'product' 		=> null,
				'orig_url' 		=> '',
				'country' 		=> '',
			), $pms);
			extract( $pms );
			//var_dump('<pre>', $ret_what, $orig_url, $country, $product , '</pre>'); //echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$ret = array(
				'status' 	=> 'invalid',
				'msg' 		=> '',
				'orig_url' 	=> $orig_url,
				'short_url' => $orig_url,
			);

			//:: get product id
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
			if ( empty($product_id) ) {
				$ret = array_replace_recursive($ret, array(
					'msg' 		=> 'invalid input parameter product!',
				));
				return $ret;
			}

			//:: get product current url or amazon link (if not provided as input parameter)
			if ( '' == $orig_url ) {
				$prod_link = $this->_product_buy_url_asin( array(
					'product_id' 		=> $product_id,
				));
				$orig_url = $prod_link['link'];
				//$country = $prod_link['country'];
			}

			if ( '' == $orig_url ) {
				$ret = array_replace_recursive($ret, array(
					'msg' 		=> 'product url is empty!',
				));
				return $ret;
			}

			//:: get amazon store country from product url (if not provided as input parameter)
			if ( '' == $country ) {
				$mstat = preg_match('~^//www\.amazon\.([a-z\.]{2,6})/gp/product/~imu', $orig_url, $mfound);
				//var_dump('<pre>jimmy', $orig_url, $mstat, $mfound , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				if ( $mstat ) {
					$country = $mfound[1];
				}
			}

			if ( '' == $country ) {
				$ret = array_replace_recursive($ret, array(
					'msg' 		=> 'country is empty!',
				));
				return $ret;
			}
			//var_dump('<pre>', $ret_what, $orig_url, $country, $product_id , '</pre>'); //echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			//:: product current url
			$orig_hash = $this->product_url_hash( $orig_url );

			$meta = get_post_meta( $product_id, '_amzaff_bitly', true );
			$meta2 = is_array($meta) && isset($meta["$country"]) ? $meta["$country"] : false;
			$meta_short_url = is_array($meta2) && ! empty($meta2['short_url']) ? (string) $meta2['short_url'] : '';

			//:: short url is Found!
			if ( '' != $meta_short_url ) {
				$meta_orig_hash = is_array($meta2) && ! empty($meta2['orig_hash']) ? (string) $meta2['orig_hash'] : '';

				// short url exists & is based on the same original url as the one product currently has!
				if ( $orig_hash === $meta_orig_hash ) {
					$ret = array_replace_recursive($ret, array(
						'status' 	=> 'valid',
						'msg' 		=> 'success.',
						'short_url' => $meta_short_url,
					));
					if ( 'only_get_meta' == $ret_what ) {
						return $ret;
					}
				}
				else {
					$ret = array_replace_recursive($ret, array(
						'msg' 		=> 'current product url hash is different than meta original url hash!',
					));
					if ( 'only_get_meta' == $ret_what ) {
						return $ret;
					}
				}
			}
			//:: short url NOT Found!
			else {
				$ret = array_replace_recursive($ret, array(
					'msg' 		=> 'no meta short url was found!',
				));
				if ( 'only_get_meta' == $ret_what ) {
					return $ret;
				}
			}

			//:: try to do a request to bitly api
			if (
				( 'force_do_request' == $ret_what )
				|| ( ( 'do_request' == $ret_what ) && ( 'invalid' == $ret['status'] ) )
			) {

				$ret['status'] = 'invalid'; // reset status to be sure we retrieve the right status for bitly request

				$bitly_stat = $this->bitly_api_shorten(array(
					'longUrl' 		=> $orig_url,
				));
				//var_dump('<pre>', $bitly_stat , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				if ( 'invalid' == $bitly_stat['status'] ) {
					$msg = $ret['msg'] . ' ' . $bitly_stat['msg'];

					$meta = is_array($meta) ? $meta : array();
					$meta["$country"] = array(
						'short_url' 	=> '',
						'orig_hash' 	=> $orig_hash,
						'req_msg' 		=> $msg,
					);
					update_post_meta( $product_id, '_amzaff_bitly', $meta );

					$ret = array_replace_recursive($ret, array(
						'msg' 		=> $msg,
					));
					return $ret;
				}

				$bitly_url = $bitly_stat['short_url'];

				$meta = is_array($meta) ? $meta : array();
				$meta["$country"] = array(
					'short_url' 	=> $bitly_url,
					'orig_hash' 	=> $orig_hash,
				);
				update_post_meta( $product_id, '_amzaff_bitly', $meta );

				$msg = $ret['msg'] . ' ' . 'success.';
				$ret = array_replace_recursive($ret, array(
					'status' 	=> 'valid',
					'msg' 		=> $msg,
					'short_url' => $bitly_url,
				));
			}
			return $ret;
		}


		// update 2018-feb
		private function check_table_generic( $table, $force=false, $pms=array() ) {
			$pms = array_replace_recursive( array(
				'must_have_rows' => true,
			), $pms);
			extract( $pms );

			$table_ = $this->db->prefix . $table;

			$need_check_tables = $this->plugin_integrity_need_verification('check_table_'.$table);

			if ( ! $need_check_tables['status'] && ! $force ) {
				return true; // don't need verification yet!
			}

			// default sql - tables & tables data!
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'modules/setup_backup/default-sql.php' );

			// retrieve all database tables & clean prefix
			$dbTables = $this->db->get_results( "show tables;", OBJECT_K );
			$dbTables = array_keys( $dbTables );
			if ( empty($dbTables) || ! is_array($dbTables) ) {

				$this->plugin_integrity_update_time('check_table_'.$table, array(
					'status'	=> 'invalid',
					'html'		=> sprintf( __('Check plugin table %s: error requesting tables list.', 'woozonelite'), $table_ ),
				));
				return false; //something was wrong!
			}

			// table exists?
			if ( ! in_array( $table_, $dbTables) ) {

				$this->plugin_integrity_update_time('check_table_'.$table, array(
					'status'	=> 'invalid',
					'html'		=> sprintf( __('Check plugin table %s: missing.', 'woozonelite'), $table_ ),
				));
				return false; //something was wrong!
			}

			// table has rows?
			if ( $must_have_rows ) {
				$query = "select count(a.ID) as nb from $table_ as a where 1=1;";
				$res = $this->db->get_var( $query );
				//var_dump('<pre>', $res , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
				if ( ($res === false) || ! $res ) {

					$this->plugin_integrity_update_time('check_table_'.$table, array(
						'status'	=> 'invalid',
						'html'		=> sprintf( __('Check plugin table %s: is empty - no rows found.', 'woozonelite'), $table_ ),
					));
					return false; //something was wrong!
				}
			}

			// all fine
			$this->plugin_integrity_update_time('check_table_'.$table, array(
				'timeout'	=> time(),
				'status'	=> 'valid',
				'html'		=> sprintf( __('Check plugin table %s: installed ok.', 'woozonelite'), $table_ ),
			));
			return true; // all is fine!
		}

		public function build_score_html_container( $score=0, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'show_score'		=> true,
				'css_style'			=> '',
			), $pms);
			extract( $pms );

			$_css_style = ( '' != $css_style ? ' ' . $css_style : '' );

			$size_class = 'size_';
			if ( $score >= 20 && $score < 40 ) {
				$size_class .= '20_40';
			}
			else if ( $score >= 40 && $score < 60 ) {
				$size_class .= '40_60';
			}
			else if ( $score >= 60 && $score < 80 ) {
				$size_class .= '60_80';
			}
			else if ( $score >= 80 && $score <= 100 ) {
				$size_class .= '80_100';
			}
			else {
				$size_class .= '0_20';
			}

			$html = array();
			$html[] = '<div class="WooZoneLite-progress"' . $_css_style . '>';
			$html[] = 		'<div class="WooZoneLite-progress-bar ' . ( $size_class ) . '" style="width:' . ( $score ) . '%"></div>';
			if ( $show_score ) {
				$html[] =	'<div class="WooZoneLite-progress-score">' . ( $score ) . '%</div>';
			}
			$html[] = '</div>';
			return implode('', $html);
		}

		// made for version 9.3 - from single amazon keys to multiple
		public function fix_multikeys_from_single() {

			$ret = -1;
			$found = get_option('WooZoneLite_fix_multikeys_from_single', false);

			//:: already fixed
			if ( $found ) {
				return $ret;
			}

			$ret = -2;

			$this->check_table_generic( 'amz_amzkeys', true, array() ); // update 2018-feb

			//:: get main plugin settings
			$settings = get_option( $this->alias . '_amazon' ); // 'WooZoneLite_amazon'
			$settings = maybe_unserialize( $settings );
			$settings = !empty($settings) && is_array($settings) ? $settings : array();

			//:: save pair in table of multiple amazon keys
			$save_opt = $settings;
			if ( isset($save_opt['AccessKeyID']) && isset($save_opt['SecretAccessKey'])
				&& ! empty($save_opt['AccessKeyID']) && ! empty($save_opt['SecretAccessKey'])
			) {
				$AccessKeyID = $save_opt['AccessKeyID'];
				$SecretAccessKey = $save_opt['SecretAccessKey'];

				//:: verify if you try with aateam demo keys
				$demo_keys = $this->get_aateam_demo_keys();
				$demo_keys = isset($demo_keys['pairs']) ? $demo_keys['pairs'] : array();

				foreach ( $demo_keys as $demokey ) {
					if ( ($AccessKeyID == $demokey[0]) && ($SecretAccessKey == $demokey[1]) ) {
						$AccessKeyID = 'aateam demo access key';
						$SecretAccessKey = 'aateam demo secret access key';
					}
				}

				//:: save keys in table
				$insert_id = $this->amzkeysObj->add_key_indb( $AccessKeyID, $SecretAccessKey );
				$ret = $insert_id;
			}

			update_option('WooZoneLite_fix_multikeys_from_single', true);
			return $ret;
		}

		// update 2018-mar-09
		// set version for plugin assets (css & js files)
		public function plugin_asset_get_version( $asset_type='css', $pms=array() ) {
			//return ''; //DEBUG
			$ret = $this->version();

			if ( defined('WOOZONELITE_DEV_SERVER') && WOOZONELITE_DEV_SERVER ) {
				$ret .= '&time=' . time();
			}
			return $ret;
		}
		public function plugin_asset_get_path( $asset_type='css', $path='', $is_wp_enqueue=false, $pms=array() ) {
			$pms = array_replace_recursive(array(
				'id' 			=> '',
				'with_wrapper' 	=> true,
			), $pms);
			extract( $pms );

			$path = trim( $path );
			if ( empty($path) ) {
				return '';
			}

			if ( $is_wp_enqueue ) {
				return $path;
			}

			if ( false !== preg_match('/(\.js|\.css)$/', $path) ) {
				$path .= '?';
			}
			$path .= 'ver=' . $this->plugin_asset_get_version( $asset_type );

			//if ( defined('WOOZONELITE_DEV_SERVER') && WOOZONELITE_DEV_SERVER ) {
			//	$path .= '&time=' . time();
			//}

			if ( ! $with_wrapper ) {
				return $path;
			}

			$str = '';
			if ( 'css' == $asset_type ) {
				$str = "<link {ID} type='text/css' href='$path' rel='stylesheet' media='all' />";
			}
			else if ( 'js' == $asset_type ) {
				$str = "<script {ID} type='text/javascript' src='$path'></script>";
			}

			if ( ! empty($id) ) {
				$str = str_replace('{ID}', "id='" . $id . "'", $str);
			}
			else {
				$str = str_replace('{ID}', '', $str);
			}

			return $str;
		}

		public function get_product_import_country( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'product_id'			=> 0,
				'country' 				=> '', //amazon location
				'asin' 					=> '',
				'use_fallback_location' => true,
				'filter_choose_country' => true,
				'text' 					=> '',
			), $pms);
			extract( $pms );

			$ret = array(
				'country' 	=> $country,
				'text' 		=> '',
			);

			if ( empty($asin) ) {
				$asin = WooZoneLite_get_post_meta($product_id, '_amzASIN', true);
			}
			$provider = $this->prodid_get_provider_by_asin( $asin );

			$countries = $this->get_ws_object( $provider )->get_countries('country');

			$prefix = '';
			if ( strlen($provider) ) {
				$prefix = 'amazon' != $provider ? $provider.'_' : '';
			}

			//:: product import country
			if ( empty($text) ) {
				$text = str_replace( '[[country]]', $provider, __('product was imported from [[country]] location %s', 'woozonelite') );
			}

			if ( empty($country) ) {
				if ( ! empty($product_id) ) {
					$country = get_post_meta( $product_id, '_amzaff_country', true );
				}
			}
			if ( empty($country) && $use_fallback_location ) {
				$country = $this->amz_settings['country'];
				$text = str_replace( '[[country]]', $provider, __('current [[country]] location in amazon config module is %s', 'woozonelite') );
			}

			$country_name = isset($countries["$country"]) ? $countries["$country"] : '';
			$text = sprintf( $text, $country_name );

			if ( empty($country) ) {
				$ret = array_replace_recursive($ret, array(
					'text' 	=> $text,
				));
				return $ret;
			}

			//:: do verify sync option regarding amazon location?
			if ( $filter_choose_country ) {
				$ss = get_option($this->alias . '_sync_options', array());
				$ss = maybe_unserialize($ss);
				$ss = $ss !== false ? $ss : array();

				$sync_choose_country = isset($ss['sync_choose_country']) ? $ss['sync_choose_country'] : 'import_country';

				if ( 'import_country' != $sync_choose_country ) {

					$country_orig = $country;
					$country = $this->amz_settings[$prefix.'country'];

					if ( $country_orig != $country ) {
						$text = str_replace( '[[country]]', $provider, __('current [[country]] location in amazon config module is %s, but product was imported from [[country]] location %s. go to <Synchronization log Settings module>, <Amazon location for sync> option and choose <Use product import country> if you want all products like this to be synced based on their import country', 'woozonelite') );

						$country_name = isset($countries["$country"]) ? $countries["$country"] : '';
						$country_orig_name = isset($countries["$country_orig"]) ? $countries["$country_orig"] : '';

						$text = sprintf( $text, $country_name, $country_orig_name );
					}
				}
			}

			$ret = array_replace_recursive($ret, array(
				'country' 	=> $country,
				'text' 		=> $text,
			));
			return $ret;
		}

		public function get_product_import_country_flag( $pms=array() ) {
			$pms = array_replace_recursive(array(
				'product_id'			=> 0,
				'country' 				=> '', //amazon location
				'use_fallback_location' => true,
				'filter_choose_country' => true,
				'asin' 					=> '',
				'with_link' 			=> true,
				'text' 					=> '',
			), $pms);
			extract( $pms );

			$ret = array(
				'country' 		=> $country,
				'image' 		=> '',
				'link' 			=> '',
				'image_link'	=> '',
			);

			//:: product import country
			$getCountry = $this->get_product_import_country( array(
				'product_id'			=> $product_id,
				'country' 				=> $country,
				'asin' 					=> $asin,
				'use_fallback_location' => $use_fallback_location,
				'text' 					=> $text,
			));
			$text = $getCountry['text'];
			$country = $getCountry['country'];

			if ( empty($asin) ) {
				$asin = WooZoneLite_get_post_meta($product_id, '_amzASIN', true);
			}
			$provider = $this->prodid_get_provider_by_asin( $asin );

			$prefix = ( 'amazon' != $provider ) && ( strlen($provider) > 0 ) ?  $provider . '-' : '';

			$ret = array_replace_recursive($ret, array(
				'country' 	=> $country,
			));

			if ( empty($country) ) {
				return $ret;
			}

			//:: try to get the image flag
			$ret['country'] = $country;

			$img_base_url = $this->cfg['paths']["plugin_dir_url"] . 'modules/amazon/images/' . $prefix . 'flags/';

			if ( 'amazon' == $provider ) {
				$flag = $this->get_country2mainaffid( $country );
			}
			else {
				$flag = strtoupper($country);
			}


			$img = '<img class="" src="%s" height="12">';
			$img = sprintf( $img, $img_base_url . $flag . '-flag.gif' );

			$product_buy_url = $this->_product_buy_url_asin( array(
				'product_id' 		=> $product_id,
				'redirect_asin' 	=> $asin,
				'force_country' 	=> $country,
			));
			//var_dump('<pre>', $product_buy_url , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			$prod_link = $product_buy_url['link'];

			$prod_link_html = '<a href="%s" target="_blank" class="aa-tooltip data-tippy-size="large" title="%s">%s</a>';
			$prod_link_html = sprintf( $prod_link_html, $prod_link, $text, $img );

			$ret = array_replace_recursive($ret, array(
				'image' 		=> $img,
				'link' 			=> $prod_link,
				'image_link' 	=> $prod_link_html,
			));
			return $ret;
		}

		// convert $variationNumber into number
		public function convert_variation_number_to_number( $variationNumber ) {
			if ( $variationNumber == 'yes_all' ) {
				$variationNumber = (int) $this->ss['max_per_product_variations'];
			}
			elseif ( $variationNumber == 'no' ) {
				$variationNumber = 0;
			}
			else {
				$variationNumber = explode(  "_", $variationNumber );
				$variationNumber = end( $variationNumber );
			}
			$variationNumber = (int) $variationNumber;
			return $variationNumber;
		}

		public function get_product_metas( $product_id, $metas=array(), $pms=array() ) {
			global $wpdb;

			$pms = array_replace_recursive( array(
				'remove_prefix' => '_amzaff_',
			), $pms);
			extract( $pms );

			if ( empty($metas) ) {
				return array();
			}

			$prods2meta = array();

			//foreach ( (array) $metas as $meta) {
			//	$meta_ = str_replace('_amzaff_', '', $meta);
			//	$prods2meta["$meta_"] = get_post_meta( $product_id, $meta, true );
			//}

			$what_metas = $metas;
			$what_metas_ = implode(',', array_map(array($this, 'prepareForInList'), $what_metas));

			$query = "select pm.meta_key, pm.meta_value from $wpdb->postmeta as pm where 1=1 and pm.post_id = $product_id and pm.meta_key in ( $what_metas_ ) order by pm.meta_key asc;";
			$res = $wpdb->get_results( $query, OBJECT_K );
			if ( ! empty($res) ) {
				foreach ( $res as $kk => $vv ) {

					$kk_ = $kk;
					if ( ! empty($remove_prefix) ) {
						$kk_ = str_replace($remove_prefix, '', $kk);
					}

					$prods2meta["$kk_"] = $vv->meta_value;
				}
			}
			return $prods2meta;
		}


		//====================================================
		//== AMAZON : MAKE A LOOKUP REQUEST & CACHE IT IN DB

		// update 2018-mar-28 - cache amazon requests
		public function amazon_request_get_cache( $cache_name, $cache_type, $pms=array() ) {
			global $wpdb;

			$pms = array_replace_recursive(array(
				'table' 	=> $wpdb->prefix . 'amz_amazon_cache',
				'how_often' => $this->ss['sync_amazon_requests_cache_exp'],
			), $pms);
			extract( $pms );

			//$how_often = 'INTERVAL 1 HOUR'; //DEBUG

			if ( is_array($cache_name) ) {
				$cache_name_ = implode(',', array_map(array($this, 'prepareForInList'), $cache_name));
				$sql = "select cache_name, response from $table where 1=1 and cache_name in ( $cache_name_ ) and cache_type = %s and ( response_date > DATE_SUB( NOW(), $how_often ) );";
				$sql = $wpdb->prepare( $sql, $cache_type );
				$res = $wpdb->get_results( $sql, OBJECT_K );
				if ( empty($res) ) {
					return array();
				}

				$ret = array();
				foreach ($res as $key => $val) {
					$ret["$key"] = maybe_unserialize( $val->response );
				}
				return $ret;
			}
			else {
				$sql = "select response from $table where 1=1 and cache_name = %s and cache_type = %s and ( response_date > DATE_SUB( NOW(), $how_often ) ) limit 1;";
				$sql = $wpdb->prepare( $sql, $cache_name, $cache_type );
				$res = $wpdb->get_var( $sql );
				if ( empty($res) ) {
					return $res;
				}

				$ret = maybe_unserialize( $res );
				return $ret;
			}
			return false;
		}

		public function amazon_request_save_cache( $cache_name, $cache_type, $pms=array() ) {
			global $wpdb;

			$pms = array_replace_recursive(array(
				'table' 	=> $wpdb->prefix . 'amz_amazon_cache',
				'country' 	=> '',
				'response' 	=> array(),
				'provider' 	=> 'amazon',
			), $pms);
			extract( $pms );

			// delete old cache
			$sql = "delete from $table where 1=1 and cache_name = %s and cache_type = %s;";
			$sql = $wpdb->prepare( $sql, $cache_name, $cache_type );
			$res = $wpdb->query( $sql );

			// insert (ignore) new response as cache
			$this->db_custom_insert(
				$table,
				array(
					'values' => array(
						'cache_name' 	=> $cache_name,
						'cache_type' 	=> $cache_type,
						'country' 		=> $country,
						'response' 		=> maybe_serialize( $response ),
						'provider' 		=> $provider,
					),
					'format' => array(
						'%s', '%s', '%s', '%s', '%s'
					)
				),
				true
			);
			return true;
		}

		// setup amazon object for making request
		public function setupAmazonHelper( $params=array(), $pms=array() ) {

			$pms = array_replace_recursive( array(
				'provider' => 'amazon',
			), $pms );
			extract( $pms );

			$provider_prefix = $provider;
			if ( 'amazon' == $provider ) {
				$provider_prefix = 'amz';
			}

			$provider_4helper = $provider_prefix.'Helper';

			//:: GET SETTINGS
			//$settings = $this->settings();
			//$settings = $this->amz_settings;

			//:: SETUP
			$params_new = array();
			foreach ( $params as $key => $val ) {
				if ( in_array($key, array(
					'AccessKeyID', 'SecretAccessKey', 'country', 'main_aff_id', //amazon
					'ebay_DEVID', 'ebay_AppID', 'ebay_CertID', 'ebay_country', 'ebay_main_aff_id', //ebay
					'overwrite_settings'
				)) ) {
					$params_new["$key"] = $val;
				}
			}

			$this->$provider_4helper = $this->get_ws_object_new( $provider, 'new_helper', array(
				'the_plugin' => $this,
				'params_new' => $params_new,
			));

			if ( is_object($this->$provider_4helper) ) {
			}
		}

		// make a request to amazon with a list of asins (all from the same country)
		public function amazon_request_make_lookup( $asins, $country='', $pms=array() ) {
			$pms = array_replace_recursive(array(
			), $pms);
			extract( $pms );


			//:: init
			$ret = array(
				'status' 			=> 'invalid',
				'msg' 				=> '',
				'code' 				=> '',
				'amz_code' 			=> '',
				'amz_response' 		=> array(),
			);
			$amz_code = '';

			$countries = $this->get_ws_object( 'amazon' )->get_countries('country');
			$country_name = isset($countries["$country"]) ? $countries["$country"] : '';


			//:: validation
			if ( empty($asins) || ! is_array($asins) ) {
				$ret = array_replace_recursive($ret, array(
					'msg' 		=> "no asins provided!",
				));
				return $ret;
			}
			$asins = $this->prodid_set( $asins, 'amazon', 'add' );
			//var_dump('<pre>', $asins, $country, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;


			//:: SETUP AMAZON & MAKE REQUEST
			$params_new = array();
			if ( ! empty($country) ) {
				$params_new['country'] = $country;
			}
			//var_dump('<pre>', $params_new , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			$this->setupAmazonHelper( $params_new, array(
				'provider' => 'amazon'
			));

			$rsp = $this->get_ws_object( 'amazon' )->api_main_request(array(
				'what_func' 			=> 'api_make_request',
				'method'				=> 'lookup',
				'amz_settings'			=> $this->amz_settings,
				'from_file'				=> str_replace($this->cfg['paths']['plugin_dir_path'], '', __FILE__),
				'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
				'requestData'			=> array(
					'asin'					=> $this->prodid_set( $asins, 'amazon', 'sub' ),
				),
				'optionalParameters'	=> array(),
				'responseGroup'			=> 'Large,ItemAttributes,OfferFull,Offers,Variations,Reviews,PromotionSummary,SalesRank',
			));
			$amz_response = $rsp['response'];

			$respStatus = $this->get_ws_object( 'amazon' )->is_amazon_valid_response( $amz_response );

			$ret = array_replace_recursive($ret, array(
				'code' 	=> $respStatus['code'],
			));

			$msg = $respStatus['code'] . ' - ' . $respStatus['msg'];
			//var_dump('<pre>', $respStatus, $amz_response , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;


			//:: ERROR
			if ( 'valid' != $respStatus['status'] ) {

				if ( isset($respStatus['amz_code'])
					&& in_array( strtolower($respStatus['amz_code']), array(
						'aws:client.requestthrottled',
						'woozonelite:aws.init.issue',
						'woozonelite:aws.request.dropped'
					))
				) {
					$amz_code = 'throttled';
				}

				$msg = 'amazon error : ' . $respStatus['code'] . ' - ' . $respStatus['msg'];

				// throttled
				$status = 'invalid';
				if ( 'throttled' == $amz_code ) {
					$status = 'throttled';
				}
				// some other error
				else if ( $respStatus['code'] < 3 ) {
					$status = 'invalid';
				}
				// product not found (all of them from asins)
				else {
					$status = 'notfound';
				}

				$ret = array_replace_recursive($ret, array(
					'status' 	=> $status,
					'amz_code' 	=> $amz_code,
					'msg' 		=> $msg,
				));
				return $ret;
			}


			//:: SUCCESS
			// fix an amazon issue with items
			$amazonItems = array();
			if ( isset($amz_response['Items']['Item']['ASIN']) ) {
				$amazonItems[] = $amz_response['Items']['Item'];
			} else {
				$amazonItems = $amz_response['Items']['Item'];
			}
			$amazonItems = (array) $amazonItems;

			// new array with ASIN as key
			$products = array();
			foreach ( $amazonItems as $idx => $amazonItem ) {
				$itemAsin = isset($amazonItem['ASIN']) ? $amazonItem['ASIN'] : '';
				//$itemAsin = $this->prodid_set($itemAsin, $this->the_plugin->prodid_get_provider_by_asin( $itemAsin ), 'add');
				$itemAsin = $this->prodid_set($itemAsin, 'amazon', 'add');

				if ( $this->get_ws_object( 'amazon' )->is_valid_product_data( $amazonItem ) ) {
					$products["$itemAsin"] = $amazonItem;
				}
			}

			if ( count($asins) <= 1 ) {
				foreach ( $asins as $asin ) {
					if ( isset($products["$asin"], $products["$asin"]['ASIN']) ) {
						$status = 'valid';
						$msg = sprintf( 'asin %s was successfully found on amazon country = %s', $asin, $country_name );
					}
					else {
						$status = 'notfound';
						$msg = sprintf( 'asin %s was not found on amazon country = %s', $asin, $country_name );
					}
					break;
				}
			}
			else {
				$status = count($asins) == count( array_keys($products) ) ? 'valid' : 'semivalid';

				$msg = array();
				if ( ! empty($products) ) {
					$msg[] = sprintf( 'asins %s were successfully found on amazon country = %s', implode(', ', array_keys($products)), $country_name );
				}
				$asins_notfound = array_diff( $asins, array_keys($products) );
				if ( ! empty($asins_notfound) ) {
					$msg[] = sprintf( 'asins %s were not found on amazon country = %s', implode(', ', $asins_notfound), $country_name );
				}
				$msg = implode(' and ', $msg);
			}

			$ret = array_replace_recursive($ret, array(
				'status' 		=> $status,
				'msg' 			=> $msg,
				'amz_response' 	=> $products,
			));
			//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			return $ret;
		}

		// make a request to ebay with a list of asins (all from the same country)
		public function ebay_request_make_lookup( $asins, $country='', $pms=array() ) {
			$pms = array_replace_recursive(array(
			), $pms);
			extract( $pms );


			//:: init
			$ret = array(
				'status' 			=> 'invalid',
				'msg' 				=> '',
				'code' 				=> '',
				'amz_code' 			=> '',
				'amz_response' 		=> array(),
			);
			$amz_code = '';

			$countries = $this->get_ws_object( 'ebay' )->get_countries('country');
			$country_name = isset($countries["$country"]) ? $countries["$country"] : '';


			//:: validation
			if ( empty($asins) || ! is_array($asins) ) {
				$ret = array_replace_recursive($ret, array(
					'msg' 		=> "no asins provided!",
				));
				return $ret;
			}
			$asins = $this->prodid_set( $asins, 'ebay', 'add' );
			//var_dump('<pre>', $asins, $country, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;


			//:: SETUP AMAZON & MAKE REQUEST
			$params_new = array();
			if ( ! empty($country) ) {
				$params_new['ebay_country'] = $country;
			}
			//var_dump('<pre>', $params_new , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			$this->setupAmazonHelper( $params_new, array(
				'provider' => 'ebay'
			));

			$rsp = $this->get_ws_object( 'ebay' )->api_main_request(array(
				'what_func' 			=> 'api_make_request',
				'method'				=> 'lookup',
				'amz_settings'			=> $this->amz_settings,
				'from_file'				=> str_replace($this->cfg['paths']['plugin_dir_path'], '', __FILE__),
				'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
				'requestData'			=> array(
					'asin'					=> $this->prodid_set( $asins, 'ebay', 'sub' ),
				),
				'optionalParameters'	=> array(),
			));
			$amz_response = $rsp['response'];

			$respStatus = $this->get_ws_object( 'ebay' )->is_amazon_valid_response( $amz_response );
			//var_dump('<pre>', $respStatus , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$ret = array_replace_recursive($ret, array(
				'code' 	=> $respStatus['code'],
			));

			$msg = $respStatus['code'] . ' - ' . $respStatus['msg'];


			//:: ERROR
			if ( 'valid' != $respStatus['status'] ) {

				if ( isset($respStatus['amz_code'])
					&& in_array( strtolower($respStatus['amz_code']), array(
						'ebay:aws.init.issue',
					))
				) {
					//$amz_code = 'throttled';
				}

				$msg = 'ebay error : ' . $respStatus['code'] . ' - ' . $respStatus['msg'];

				// throttled
				$status = 'invalid';
				if ( 'throttled' == $amz_code ) {
					$status = 'throttled';
				}
				else if ( 'notfound' == $amz_code ) {
					$status = 'notfound';
				}
				// some other error
				else if ( $respStatus['code'] < 3 ) {
					$status = 'invalid';
				}
				// product not found (all of them from asins)
				else {
					$status = 'notfound';
				}

				$ret = array_replace_recursive($ret, array(
					'status' 	=> $status,
					'amz_code' 	=> $amz_code,
					'msg' 		=> $msg,
				));
				return $ret;
			}


			//:: SUCCESS
			// fix an amazon issue with items
			$amazonItems = array();
			if ( isset($amz_response['Item']['ItemID']) ) {
				$amazonItems[] = $amz_response['Item'];
			} else {
				$amazonItems = $amz_response['Item'];
			}
			$amazonItems = (array) $amazonItems;

			// new array with ASIN as key
			$products = array();
			foreach ( $amazonItems as $idx => $amazonItem ) {
				$itemAsin = isset($amazonItem['ItemID']) ? $amazonItem['ItemID'] : '';
				//$itemAsin = $this->prodid_set($itemAsin, $this->the_plugin->prodid_get_provider_by_asin( $itemAsin ), 'add');
				$itemAsin = $this->prodid_set($itemAsin, 'ebay', 'add');

				$amazonItem['ASIN'] = isset($amazonItem['ItemID']) ? $amazonItem['ItemID'] : ''; //without provider prefix
				$amazonItem['__isfrom'] = 'details-only';

				if ( $this->get_ws_object( 'ebay' )->is_valid_product_data( $amazonItem, 'details-only' ) ) {
					$products["$itemAsin"] = $amazonItem;
				}
			}

			if ( count($asins) <= 1 ) {
				foreach ( $asins as $asin ) {
					if ( isset($products["$asin"], $products["$asin"]['ASIN']) ) {
						$status = 'valid';
						$msg = sprintf( 'asin %s was successfully found on ebay country = %s', $asin, $country_name );
					}
					else {
						$status = 'notfound';
						$msg = sprintf( 'asin %s was not found on ebay country = %s', $asin, $country_name );
					}
					break;
				}
			}
			else {
				$status = count($asins) == count( array_keys($products) ) ? 'valid' : 'semivalid';

				$msg = array();
				if ( ! empty($products) ) {
					$msg[] = sprintf( 'asins %s were successfully found on ebay country = %s', implode(', ', array_keys($products)), $country_name );
				}
				$asins_notfound = array_diff( $asins, array_keys($products) );
				if ( ! empty($asins_notfound) ) {
					$msg[] = sprintf( 'asins %s were not found on ebay country = %s', implode(', ', $asins_notfound), $country_name );
				}
				$msg = implode(' and ', $msg);
			}

			$ret = array_replace_recursive($ret, array(
				'status' 		=> $status,
				'msg' 			=> $msg,
				'amz_response' 	=> $products,
			));
			//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			return $ret;
		}



		//====================================================
		//== SYNCHRONIZATION of products

		public function get_product_sync_rules() {
			return array(
				'price'                 => __('Price', 'woozonelite'),
				'title'                 => __('Title', 'woozonelite'),
				'url'                   => __('Buy URL', 'woozonelite'),
				'desc'                  => __('Description', 'woozonelite'),
				'sku'                   => __('SKU', 'woozonelite'),
				'sales_rank'            => __('Sales Rank', 'woozonelite'),
				'reviews'               => __('Reviews', 'woozonelite'),
				'short_desc'            => __('Short description', 'woozonelite'),
				'new_variations'        => __('New Variations', 'woozonelite'),
			);
		}

		public function syncproduct_build_last_stats_column( $row=array() ) {
			$row = array_replace_recursive( array(
				'asin' => '',
				'sync_nb' => 0,
				'sync_last_status' => '',
				'sync_last_status_msg' => '',
				'sync_trash_tries' => 0,
				'sync_import_country' => '',
				'sync_current_cycle' => '',
				'first_updated_date' => '',
			), $row);
			extract( $row );

			$ret = array();

			$sync_last_status_text = array();
			if ( ! empty($sync_last_status_msg) ) {

				$sync_last_status_text[] = __('The last sync status for this product:<br />', 'woozonelite');

				if ( is_array($sync_last_status_msg) ) {

					$sync_rules = $this->get_product_sync_rules();

					if ( isset($sync_last_status_msg['msg']) ) {
						$sync_last_status_text[] = $sync_last_status_msg['msg'] . '<br />';
					}

					foreach ( array('notfound', 'updated') as $val ) {

						if ( isset($sync_last_status_msg["_variations_$val"])
							&& ! empty($sync_last_status_msg["_variations_$val"])
						) {

							if ( ! isset($sync_last_status_msg['rules']) || ! is_array($sync_last_status_msg['rules']) ) {
								$sync_last_status_msg['rules'] = array();
							}

							if ( ! isset($sync_last_status_msg['rules']["_variations_$val"]) ) {
								$sync_last_status_msg['rules']["_variations_$val"]
								 = $sync_last_status_msg["_variations_$val"];
							}
						}
					}

					if ( isset($sync_last_status_msg['rules']) && ! empty($sync_last_status_msg['rules']) ) {

						$sync_rules_text = array();
						$vars_changed_titles = array(
							'_variations_updated' => __('- %s variations were updated (ID, Asin) : %s', 'woozonelite'),
							'_variations_notfound' => __('- %s variations were not found (ID, Asin) : %s', 'woozonelite'),
						);

						$nbupdated = 0;
						$vars_changed_cc = 0;

						foreach ( $sync_last_status_msg['rules'] as $kk => $vv ) {

							if ( 'yes' == $vv['status'] ) {

								if ( 'new_variations' == $kk ) {

									$sync_rules_text[] = sprintf( __("- Field %s was updated ( %d new variations added ).", 'woozonelite'), $sync_rules["$kk"], $vv['new_added'] );

									if ( isset($vv['msg']) && ! empty($vv['msg']) ) {
										$sync_rules_text[] = $vv['msg'];
									}
									$nbupdated++;
									continue 1;
								}
								else if ( in_array($kk, array('_variations_notfound', '_variations_updated')) ) {
									if ( isset($vv) && ! empty($vv) ) {

										$sync_rules_text[] = sprintf(
											$vars_changed_titles["$kk"],
											count( $vv['vars'] ),
											implode(', ', array_map(array($this, 'prepareForPairView'), $vv['vars'], array_keys($vv['vars'])))
										);
										$nbupdated++;
										$vars_changed_cc++;
									}
									continue 1;
								}

								$sync_rules_text[] = sprintf( __("- Field %s was updated.", 'woozonelite'), $sync_rules["$kk"] );
								$nbupdated++;
							}
							else {

								if ( 'new_variations' == $kk ) {

									if ( isset($vv['msg']) && ! empty($vv['msg']) ) {
										$sync_rules_text[] = $vv['msg'];
									}
								}
							}
						}
						// end foreach

						if ( $nbupdated ) {
							$sync_last_status_text[] = 'The following fields were updated: <br />';
							$sync_last_status_text[] = implode('<br /><br />', $sync_rules_text);
						}
						else {
							$sync_last_status_text[] = __("It seems no product field needed to be updated.", 'woozonelite');
						}
					}
				}
				else {
					$sync_last_status_text[] = $sync_last_status_msg;
				}
			}
			else {
				$sync_last_status_text[] = __('This product was not synced yet.', 'woozonelite');
			}
			$sync_last_status_text = implode('<br />', $sync_last_status_text);

			$ret['text_last_sync_title'] = $sync_last_status_text;

			$sync_last_status_text = strip_tags($sync_last_status_text, '<br><br/><br />');



			$text_syncs_nb_title = __('The number represents the total successfull synchronizations for this product.', 'woozonelite');
			$text_syncs_nb = sprintf( __('<span>%s</span> SYNCS', 'woozonelite'), $sync_nb );


			$text_last_sync_niceinfo = array();
			$text_last_sync_niceinfo[] = $sync_import_country;


			$tmpp_css = '';
			if ( '' != $sync_last_status ) {
				switch ($sync_last_status) {
					case 'updated':
						$tmpp_css = 'updated';
						break;

					case 'notupdated':
					case 'valid':
						$tmpp_css = 'notupdated';
						break;

					case 'notfound':
						$tmpp_css = 'notfound';
						break;

					// invalid | throttled
					default:
						$tmpp_css = 'error';
						break;
				}
				$tmpp_css = 'sync-' . $tmpp_css;
			}

			//$text_last_sync_niceinfo[] = '<a href="#" title="' . $sync_last_status_text . '" class="WooZoneLite-sync-last-status-text ' . $tmpp_css . '">' . $text_syncs_nb . '</a>';
			$text_last_sync_niceinfo[] = '<span class="WooZoneLite-sync-last-status-text ' . $tmpp_css . '" title="' . $text_syncs_nb_title . '">' . $text_syncs_nb . '</span>';
			$text_last_sync_niceinfo[] = '<a href="#" class="WooZoneLite-simplemodal-trigger" title="' . $sync_last_status_text . '"><i class="fa fa-eye-slash"></i></a>';

			if ( $sync_trash_tries ) {
				$text_last_sync_niceinfo[] = sprintf( __('<span title="the number of consecutive synchronizations requests which returned error for this product. it is related to Put amazon products in trash when syncing after... from amazon config module / bug fixes tab">(%d)</span>', 'woozonelite'), (int) $sync_trash_tries );
			}

			// identify current cycle
			if ( ( $sync_current_cycle == $first_updated_date ) && ! empty($first_updated_date) ) {
					$text_last_sync_niceinfo[] = sprintf( __('<span class="sync-current-cycle" title="this row was parsed by the cronjob current sync cycle"><i class="fa fa-circle"></i></span>', 'woozonelite') );
			}

			$text_last_sync_niceinfo = implode( PHP_EOL, $text_last_sync_niceinfo );

			$ret['text_last_sync_niceinfo'] = $text_last_sync_niceinfo;
			return $ret;
		}

		public function syncproduct_sanitize_last_status( $status ) {
			if ( '0' === (string) $status || '1' === (string) $status ) {
				return $status ? 'valid' : 'invalid';
			}
			return $status;
		}

		public function syncproduct_is_sync_needed( $pms=array() ) {
			$pms = array_replace_recursive( array(
				'current_time' => time(),
				'recurrence' => 0,
				'product_id' => 0,
				'sync_last_date' => false,
			), $pms);
			extract( $pms );

			if ( false === $sync_last_date ) {
				if ( $product_id ) {
					$sync_last_date = get_post_meta( $product_id, '_amzaff_sync_last_date', true );
				}
			}

			if ( empty($sync_last_date) || ( $current_time >= ($sync_last_date + $recurrence) ) ) {
				return true;
			}
			return false;
		}

		public function syncwidget_build_last_stats_column( $row=array() ) {
			$row = array_replace_recursive( array(
				'asin' => '',
				'sync_nb' => 0,
				'sync_last_status' => '',
				'sync_last_status_msg' => '',
				'sync_trash_tries' => 0,
				'sync_last_date' => '',
				'sync_import_country' => '',
				//'sync_current_cycle' => '',
				//'first_updated_date' => '',
			), $row);
			extract( $row );

			$ret = array(
				'text_last_sync_title' => '',
				'text_last_sync_niceinfo' => '',
			);

			$sync_last_date_display = $this->last_update_date('true', $sync_last_date);

			$sync_last_status_text = array();
			if ( ! empty($sync_last_status_msg) ) {

				$sync_last_status_text[] = __('The last sync (by widget) status for this product:<br />', 'woozonelite');

				if ( is_array($sync_last_status_msg) ) {

					$sync_rules = array(
						'title' => 'Product Title',
						'price' => 'Product Price',
					);

					if ( isset($sync_last_status_msg['msg']) ) {
						$sync_last_status_text[] = $sync_last_status_msg['msg'] . '<br />';
					}

					foreach ( array('notfound', 'updated') as $val ) {

						if ( ! isset($sync_last_status_msg['rules']) || ! is_array($sync_last_status_msg['rules']) ) {
							$sync_last_status_msg['rules'] = array();
						}
					}

					if ( isset($sync_last_status_msg['rules']) && ! empty($sync_last_status_msg['rules']) ) {

						$sync_rules_text = array();

						$nbupdated = 0;

						foreach ( $sync_last_status_msg['rules'] as $kk => $vv ) {

							if ( 'yes' == $vv['status'] ) {

								$sync_rules_text[] = sprintf( __("- Field %s was updated.", 'woozonelite'), $sync_rules["$kk"] );
								$nbupdated++;
							}
						}
						// end foreach

						if ( $nbupdated ) {
							$sync_last_status_text[] = 'The following fields were updated: <br />';
							$sync_last_status_text[] = implode('<br /><br />', $sync_rules_text);
						}
						else {
							$sync_last_status_text[] = __("It seems no product field needed to be updated.", 'woozonelite');
						}
					}
				}
				else {
					$sync_last_status_text[] = $sync_last_status_msg;
				}
			}
			else {
				$sync_last_status_text[] = __('This product was not synced (by widget) yet.', 'woozonelite');
			}
			$sync_last_status_text = implode('<br />', $sync_last_status_text);

			$ret['text_last_sync_title'] = $sync_last_status_text;

			$sync_last_status_text = strip_tags($sync_last_status_text, '<br><br/><br />');

			$text_syncs_nb_title = array();
			$text_syncs_nb_title[] = sprintf( __('Last Sync Date : %s', 'woozonelite'), $sync_last_date_display );
			$text_syncs_nb_title[] = __('The number represents the total successfull synchronizations (by widget) for this product.', 'woozonelite');
			$text_syncs_nb_title = implode('<br/>', $text_syncs_nb_title);
			$text_syncs_nb = sprintf( __('<span>%s</span> SYNCS', 'woozonelite'), $sync_nb );


			$text_last_sync_niceinfo = array();
			$text_last_sync_niceinfo[] = '<div class="WooZoneLite-syncwidget-row-last-status">';
			$text_last_sync_niceinfo[] = $sync_import_country;


			$tmpp_css = '';
			if ( '' != $sync_last_status ) {
				switch ($sync_last_status) {
					case 'updated':
						$tmpp_css = 'updated';
						break;

					case 'notupdated':
					case 'valid':
						$tmpp_css = 'notupdated';
						break;

					case 'notfound':
						$tmpp_css = 'notfound';
						break;

					// invalid
					default:
						$tmpp_css = 'error';
						break;
				}
				$tmpp_css = 'sync-' . $tmpp_css;
			}

			$text_last_sync_niceinfo[] = '<span class="WooZoneLite-sync-last-status-text ' . $tmpp_css . '" title="' . $text_syncs_nb_title . '">' . $text_syncs_nb . '</span>';
			$text_last_sync_niceinfo[] = '<a href="#" class="WooZoneLite-simplemodal-trigger" title="' . $sync_last_status_text . '"><i class="fa fa-eye-slash"></i></a>';

			if ( $sync_trash_tries ) {
				$text_last_sync_niceinfo[] = sprintf( __('<span title="the number of consecutive synchronizations (by widget) requests which returned error for this product. it is related to Put amazon products in trash when syncing after... from No AWS Keys Sync Widget Settings module">(%d)</span>', 'woozonelite'), (int) $sync_trash_tries );
			}

			// identify current cycle
			// if ( ( $sync_current_cycle == $first_updated_date ) && ! empty($first_updated_date) ) {
			// 		$text_last_sync_niceinfo[] = sprintf( __('<span class="sync-current-cycle" title="this row was parsed by the cronjob current sync cycle"><i class="fa fa-circle"></i></span>', $this->localizationName) );
			// }

			$text_last_sync_niceinfo[] = '</div>';
			$text_last_sync_niceinfo = implode( PHP_EOL, $text_last_sync_niceinfo );

			$ret['text_last_sync_niceinfo'] = $text_last_sync_niceinfo;
			return $ret;
		}


		//====================================================
		//== TEMPLATES SYSTEM

		public function doing_it_wrong( $function, $message, $version ) {

			$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

			if ( is_ajax() ) {
				do_action( 'doing_it_wrong_run', $function, $message, $version );
				error_log( "{$function} was called incorrectly. {$message}. This message was added in plugin version {$version}." );
			} else {
				_doing_it_wrong( $function, $message, $version );
			}
		}

		public function tplsystem_get_template( $template_name, $pms=array() ) {

			$pms = array_replace_recursive( array(
				'template_path' 	=> '',
				'default_path' 		=> '',
			), $pms);
			extract( $pms );

			$located = $this->tplsystem_locate_template( $template_name, $pms );

			clearstatcache();
			if ( ! file_exists( $located ) ) {
				$this->doing_it_wrong(
					__FUNCTION__,
					sprintf( __( '%s does not exist.', 'woozonelite' ), '<code>' . $located . '</code>' ),
					'10.0.5'
				);
				return ;
			}

			// third party plugins can override the template file here
			$located = apply_filters( 'woozonelite_get_template', $located, $template_name, $pms );

			do_action( 'woozonelite_get_template_before', $located, $template_name, $pms );

			include $located;

			do_action( 'woozonelite_get_template_after', $located, $template_name, $pms );
		}

		public function tplsystem_get_template_html( $template_name, $pms=array() ) {

			ob_start();
			$this->tplsystem_get_template( $template_name, $pms );
			return ob_get_clean();
		}

		public function tplsystem_locate_template( $template_name, $pms=array() ) {

			$pms = array_replace_recursive( array(
				'template_path' 	=> '',
				'default_path' 		=> '',
			), $pms);
			extract( $pms );

			// your active theme
			if ( ! $template_path ) {
				$template_path = apply_filters( 'woozonelite_template_path', 'woozonelite/' );
			}

			// our plugin default templates folder
			if ( ! $default_path ) {
				$default_path = $this->cfg['paths']['plugin_dir_path'] . 'templates/';
			}

			// the loading order:
			// 		your active theme/$template_path/$template_name
			// 		$default_path/$template_name
			$located = locate_template(
				array(
					trailingslashit( $template_path ) . $template_name,
				),
				false //don't load the template in wp function
			);

			// get our default template
			if ( ! $located || ( defined('WOOZONELITE_TEMPLATE_DEBUG_MODE') && WOOZONELITE_TEMPLATE_DEBUG_MODE ) ) {
				$located = $default_path . $template_name;
			}

			// third party plugins can override the template file here
			$located = apply_filters( 'woozonelite_locate_template', $located, $template_name, $pms );

			return $located;
		}


		//====================================================
		//== BADGES / FLAGS

		public function is_product_freeshipping( $post_id, $pms=array() ) {

			$pms = array_replace_recursive( array(
				'current_amazon_aff' 	=> array(),
			), $pms);
			extract( $pms );

			$ret = array(
				'status' 	=> false,
				'html' 		=> '',
				'link' 		=> '',
			);

			$contents = '';

			if ( empty($current_amazon_aff) || ! is_array($current_amazon_aff) ) {
				$current_amazon_aff = $this->_get_current_amazon_aff();
			}

			$_tag = '';
			$_affid = $current_amazon_aff['user_country']['key'];
			if ( isset($this->amz_settings['AffiliateID']["$_affid"]) ) {
				$_tag = '&tag=' . $this->amz_settings['AffiliateID']["$_affid"];
			}
			$tag = $_tag;

			$meta = get_post_meta($post_id, '_amzaff_isSuperSaverShipping', true);
			if ( !empty($meta) ) {

				$link = '//www.amazon.com/gp/help/customer/display.html/ref=mk_sss_dp_1?ie=UTF8&amp;pop-up=1&amp;nodeId=527692' . $tag;

				ob_start();
		?>
				<span class="WooZoneLite-free-shipping">
					&amp; <b><?php echo $this->_translate_string( 'FREE Shipping' ); ?></b>.
					<?php if( isset( $this->amz_settings['show_free_shipping_details_link'] ) && $this->amz_settings['show_free_shipping_details_link'] == "yes") { ?>
					<a class="link" onclick="return WooZoneLite.popup(this.href,'AmazonHelp','width=550,height=550,resizable=1,scrollbars=1,toolbar=0,status=0');" target="AmazonHelp" href="<?php echo $link; ?>">	<?php echo $this->_translate_string( 'Details' ); ?></a>
					<?php } ?>
				</span>
		<?php
				$contents .= ob_get_clean();

				$ret = array_replace_recursive( $ret, array(
					'status' 	=> true,
					'html' 		=> $contents,
					'link' 		=> $link,
				));
			}

			return $ret;
		}

		public function is_product_amazonprime( $post_id, $pms=array() ) {

			$pms = array_replace_recursive( array(
			), $pms);
			extract( $pms );

			$ret = array(
				'status' 	=> false,
				'html' 		=> '',
				'link' 		=> '',
			);

			$contents = '';

			$meta = get_post_meta($post_id, '_amzaff_isAmazonPrime', true);
			if ( ! empty($meta) ) {
				$ret = array_replace_recursive( $ret, array(
					'status' 	=> true,
				));
			}

			//$ret['status'] = true; //DEBUG
			return $ret;
		}

		// return: false | true (if it has that badge)
		public function product_badge_is( $product, $badge_type ) {

			$prod_id = 0;
			if ( in_array($badge_type, array('new', 'onsale')) ) {

				if ( ! is_object( $product) ) {
					$product = wc_get_product( $product );
				}
				if ( ! is_object( $product) ) {
					return false;
				}

				if ( is_object($product) ) {
					if ( method_exists( $product, 'get_id' ) ) {
						$prod_id = (int) $product->get_id();
					} else if ( isset($product->id) && (int) $product->id > 0 ) {
						$prod_id = (int) $product->id;
					}
				}
			}
			else {
				$prod_id = (int) $product;
			}

			// is product?
			if ( $prod_id <=0 ) return false;

			// is amazon product?
			$redirect_asin = WooZoneLite_get_post_meta($prod_id, '_amzASIN', true);
			if ( empty($redirect_asin) ) {
				return false;
			}

			$status = false;
			switch( $badge_type ) {

				case 'new':

					$oneday = 86400; //seconds
					$today = time();

					//$post_date = strtotime( $product->post_date );
					$post_date = null;
					if ( is_object($product->get_date_created()) ) {
						$post_date = $product->get_date_created()->getTimestamp();
					}
					//var_dump('<pre>', $today, $post_date, $today - $post_date ,'</pre>');
					if ( is_null($post_date) ) {
						return false;
					}

					if ( ( $today - $post_date ) <= $oneday ) {
						$status = true;
					}
					break;

				case 'onsale':

					$is_onsale = $product->is_on_sale();
					if ( $is_onsale ) {
						$status = true;
					}
					break;

				case 'freeshipping':

					$is_fs = $this->is_product_freeshipping( $prod_id );
					$status = $is_fs['status'];
					break;

				case 'amazonprime':

					$is_amzp = $this->is_product_amazonprime( $prod_id );
					$status = $is_amzp['status'];
					break;
			}

			return $status;
		}

		public function product_badge_is_new( $product ) {
			return $this->product_badge_is( $product, 'new' );
		}

		public function product_badge_is_onsale( $product ) {
			return $this->product_badge_is( $product, 'onsale' );
		}

		public function product_badge_is_freeshipping( $product ) {
			return $this->product_badge_is( $product, 'freeshipping' );
		}

		public function product_badge_is_amazonprime( $product ) {
			return $this->product_badge_is( $product, 'amazonprime' );
		}

		public function clean_multiselect( $val=array() ) {
			$val = array_filter( array_unique( $val ) );
			return $val;
		}



		//====================================================
		//== Dropship Tax & Product Prices Related

		public function frontend_show_what() {
			$all = array(
				'checkout_email',
				'cross_sell',
				'product_url_short',
				'syncfront_activate',
				'product_countries',
				'show_review_tab',
				'show_availability_icon',
			);
			$def = $all;

			if ( ! $this->disable_amazon_checkout ) {
				return $all;
			}

			$show_what = isset($this->amz_settings['nocheckout_show_what'])
				? (array) $this->amz_settings['nocheckout_show_what'] : $def;
			$show_what = array_filter( $show_what );
			$show_what = array_unique( $show_what );

			return $show_what;
		}

		// is dropshiptax activated?
		public function dropshiptax_is_active() {

			return false;
		}

		// calculate global dropshiptax for product price
		public function dropshiptax_price_global( $price, $pms=array() ) {

			return $price;
		}

		// /woocommerce/includes/abstracts/abstract-wc-product.php
		// ret = price | array
		public function woocommerce_get_price_html( $product, $pms=array(), $ret_what='price' ) {

			$pms = array_replace_recursive( array(
				'do_dropshiptax' 	=> false,
				'verify_asin' 		=> true,
			), $pms );
			extract( $pms );

			if ( $verify_asin ) {
				// $redirect_asin = WooZoneLite_get_post_meta($product->get_id(), '_amzASIN', true);
				// if ( empty($redirect_asin) ) {
				// 	$do_dropshiptax = false;
				// }

				// [FIX] on 2019-jul-02
				$isProdValid = $this->verify_product_is_amazon($product, array( 'verify_provider' => 'amazon' ));
				if ( $isProdValid !== true ) {
					$do_dropshiptax = false;
				}
			}

			$price = $product->get_price();

			$ret = array(
				// (HTML) original price OR price with dropshiptax applied
				'price' 		=> $price,

				// (HTML) original price (always)
				'price_orig' 	=> $price,

				// (HTML) difference (if any) between price with dropshiptax applied and original price
				'price_diff' 	=> 0,

				'do_dropshiptax' => $do_dropshiptax,
			);

			$price_orig = $price;
			$price_html = $price;
			$price_diff = 0;

			if ( '' === $price ) {
				//$price = apply_filters( 'woocommerce_empty_price_html', '', $product );
			}
			elseif ( $product->is_on_sale() ) {

				$price_regular = $product->get_regular_price();

				$price_orig = wc_format_sale_price(
					wc_get_price_to_display( $product, array(
						'price' => $price_regular
					) ),
					wc_get_price_to_display( $product, array(
						'price' => $price
					) )
				)
				. $product->get_price_suffix();

				$price_html = $price_orig;

				if ( $do_dropshiptax ) {

					$price_html = wc_format_sale_price(
						wc_get_price_to_display( $product, array(
							'price' => $this->dropshiptax_price_global( $price_regular )
						) ),
						wc_get_price_to_display( $product, array(
							'price' => $this->dropshiptax_price_global( $price )
						) )
					)
					. $product->get_price_suffix();

					$diff_regular = $this->dropshiptax_price_global( $price_regular ) - $price_regular;
					$diff_regular = (float) number_format( $diff_regular, 2, '.', '' );

					$diff_ = $this->dropshiptax_price_global( $price ) - $price;
					$diff_ = (float) number_format( $diff_, 2, '.', '' );

					$price_diff = wc_format_sale_price(
						wc_get_price_to_display( $product, array(
							'price' => $diff_regular
						) ),
						wc_get_price_to_display( $product, array(
							'price' => $diff_
						) )
					)
					. $product->get_price_suffix();
				}
			}
			else {
				$price_orig = wc_price( wc_get_price_to_display( $product, array(
					'price' => $price
				) ) )
				. $product->get_price_suffix();

				$price_html = $price_orig;

				if ( $do_dropshiptax ) {

					$price_html = wc_price( wc_get_price_to_display( $product, array(
						'price' => $this->dropshiptax_price_global( $price )
					) ) )
					. $product->get_price_suffix();

					$diff_ = $this->dropshiptax_price_global( $price ) - $price;
					$diff_ = (float) number_format( $diff_, 2, '.', '' );

					$price_diff = wc_price( wc_get_price_to_display( $product, array(
						'price' => $diff_
					) ) )
					. $product->get_price_suffix();
				}
			}

			$ret = array_replace_recursive( $ret, array(
				'price' 		=> $price_html,
				'price_orig' 	=> $price_orig,
				'price_diff' 	=> $price_diff,
			));

			//return apply_filters( 'woocommerce_get_price_html', $price, $product );
			if ( 'price' == $ret_what ) {
				return $ret['price'];
			}
			return $ret;
		}

		// /woocommerce/includes/class-wc-product-variable.php
		// ret = price | array
		public function woocommerce_get_price_html_variable( $product, $pms=array(), $ret_what='price' ) {

			$pms = array_replace_recursive( array(
				'do_dropshiptax' 	=> false,
				'verify_asin' 		=> true,
				'theformat' 		=> 'default', // default | show_min
			), $pms );
			extract( $pms );

			if ( $verify_asin ) {
				// $redirect_asin = WooZoneLite_get_post_meta($product->get_id(), '_amzASIN', true);
				// if ( empty($redirect_asin) ) {
				// 	$do_dropshiptax = false;
				// }

				// [FIX] on 2019-jul-02
				$isProdValid = $this->verify_product_is_amazon($product, array( 'verify_provider' => 'amazon' ));
				if ( $isProdValid !== true ) {
					$do_dropshiptax = false;
				}
			}

			$price = '';

			$ret = array(
				// (HTML) original price OR price with dropshiptax applied
				'price' 		=> $price,

				// (HTML) original price (always)
				'price_orig' 	=> $price,

				// (HTML) difference (if any) between price with dropshiptax applied and original price
				'price_diff' 	=> 0,

				'do_dropshiptax' => $do_dropshiptax,
			);

			$price_orig = $price;
			$price_html = $price;
			$price_diff = 0;

			$prices = $product->get_variation_prices( true );
			//var_dump('<pre>', $prices , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			if ( empty( $prices['price'] ) ) ;
			else {
				//$product->get_variation_price( 'min', true ) //min|max
				//$product->get_variation_regular_price( 'min', true ) //min|max
				//$product->get_variation_sale_price( 'min', true ) //min|max
				$min_price 		= current( $prices['price'] );
				$max_price 		= end( $prices['price'] );
				$min_reg_price 	= current( $prices['regular_price'] );
				$max_reg_price 	= end( $prices['regular_price'] );
				$min_sale_price = current( $prices['sale_price'] );
				$max_sale_price = end( $prices['sale_price'] );

				//:: default format
				if ( $min_price !== $max_price ) {

					$price_orig = wc_format_price_range( $min_price, $max_price );
					$price_html = $price_orig;

					if ( $do_dropshiptax ) {

						$price_html = wc_format_price_range(
							$this->dropshiptax_price_global( $min_price ),
							$this->dropshiptax_price_global( $max_price )
						);

						$diff_min = $this->dropshiptax_price_global( $min_price ) - $min_price;
						$diff_min = (float) number_format( $diff_min, 2, '.', '' );

						$diff_max = $this->dropshiptax_price_global( $max_price ) - $max_price;
						$diff_max = (float) number_format( $diff_max, 2, '.', '' );

						$price_diff = wc_format_price_range( $diff_min, $diff_max );
					}
				}
				elseif ( $product->is_on_sale() && $min_reg_price === $max_reg_price ) {

					$price_orig = wc_format_sale_price( wc_price( $max_reg_price ), wc_price( $min_price ) );
					$price_html = $price_orig;

					if ( $do_dropshiptax ) {

						$price_html = wc_format_sale_price(
							wc_price( $this->dropshiptax_price_global( $max_reg_price ) ),
							wc_price( $this->dropshiptax_price_global( $min_price ) )
						);

						$diff_max = $this->dropshiptax_price_global( $max_reg_price ) - $max_reg_price;
						$diff_max = (float) number_format( $diff_max, 2, '.', '' );

						$diff_min = $this->dropshiptax_price_global( $min_price ) - $min_price;
						$diff_min = (float) number_format( $diff_min, 2, '.', '' );

						$price_diff = wc_format_sale_price( wc_price( $diff_max ), wc_price( $diff_min ) );
					}
				}
				else {

					$price_orig = wc_price( $min_price );
					$price_html = $price_orig;

					if ( $do_dropshiptax ) {

						$price_html = wc_price( $this->dropshiptax_price_global( $min_price ) );

						$diff_ = $this->dropshiptax_price_global( $min_price ) - $min_price;
						$diff_ = (float) number_format( $diff_, 2, '.', '' );

						$price_diff = wc_price( $diff_ );
					}
				}

				//:: show_min format
				if ( 'show_min' == $theformat ) {

					if ( $min_sale_price == $min_reg_price ) {

						$price_orig = wc_price( $min_reg_price );
						$price_html = $price_orig;

						if ( $do_dropshiptax ) {

							$price_html = wc_price( $this->dropshiptax_price_global( $min_reg_price ) );

							$diff_ = $this->dropshiptax_price_global( $min_reg_price ) - $min_reg_price;
							$diff_ = (float) number_format( $diff_, 2, '.', '' );

							$price_diff = wc_price( $diff_ );
						}
					}
					else {

						$price_orig = sprintf(
							'<del>%s</del><ins>%s</ins>',
							wc_price( $min_reg_price ),
							wc_price( $min_sale_price )
						);
						$price_html = $price_orig;

						if ( $do_dropshiptax ) {

							$price_html = sprintf(
								'<del>%s</del><ins>%s</ins>',
								wc_price( $this->dropshiptax_price_global( $min_reg_price ) ),
								wc_price( $this->dropshiptax_price_global( $min_sale_price ) )
							);

							$diff_min = $this->dropshiptax_price_global( $min_reg_price ) - $min_reg_price;
							$diff_min = (float) number_format( $diff_min, 2, '.', '' );

							$diff_min2 = $this->dropshiptax_price_global( $min_sale_price ) - $min_sale_price;
							$diff_min2 = (float) number_format( $diff_min2, 2, '.', '' );

							$price_diff = sprintf(
								'<del>%s</del><ins>%s</ins>',
								wc_price( $diff_min ),
								wc_price( $diff_min2 )
							);
						}
					}

					//if ( $min_price != $max_price ) {
					//	$price = sprintf( '%s: %s', __('from', 'woozonelite'), $price );
					//}
				}
			}

			$ret = array_replace_recursive( $ret, array(
				'price' 		=> $price_html,
				'price_orig' 	=> $price_orig,
				'price_diff' 	=> $price_diff,
			));

			if ( 'price' == $ret_what ) {
				return $ret['price'];
			}
			return $ret;
		}

		// /woocommerce/includes/abstracts/abstract-wc-product.php
		public function get_price_html( $product=null, $pms=array() ) {

			$def_pms = array(
				// texts
				'text_title' 		=> __('Dropshipping Price', 'woozonelite'),
				'text_price_notax' 	=> __('the product price', 'woozonelite'),
				'text_price_orig' 	=> __('the original product price (before the Dropshipping tax is applied)', 'woozonelite'),
				'text_price' 		=> __('current product price (with the Dropshipping tax applied)', 'woozonelite'),
				'text_price_diff' 	=> __('your profit for this product (basicaly is the difference between current product price and the original price)', 'woozonelite'),
			);
			$pms = array_replace_recursive( array(
				'with_wrapper' 		=> false,

				// used when product = null ; array keys: price_orig, price, price_diff
				'price_html' 		=> array(),
			), $pms );
			foreach ( $def_pms as $key => $val ) {
				if ( ! isset($pms["$key"]) || empty($pms["$key"]) ) {
					$pms["$key"] = $val;
				}
			}
			extract( $pms );

			//:: get prices
			if ( is_object($product) ) {
				$product_type = $product->get_type();

				if ( 'variable' == $product_type ) {

					$price_html = $this->woocommerce_get_price_html_variable( $product, array(
						'do_dropshiptax' 	=> true,
					), 'array' );
					$do_dropshiptax = $price_html['do_dropshiptax'];
				}
				else {

					$price_html = $this->woocommerce_get_price_html( $product, array(
						'do_dropshiptax' 	=> true,
					), 'array' );
					$do_dropshiptax = $price_html['do_dropshiptax'];

					if ( '' === $price_html['price'] ) {
						//$price_html['price'] = '<span class="na">&ndash;</span>';
						$price_html['price_orig'] = '';
						$price_html['price_diff'] = '';
					}
				}
			}

			if ( ! is_array($price_html) || empty($price_html) ) {
				return '';
			}

			//:: display prices
			$html = array();

			$html[] = '<div class="WooZoneLite-dp-prices">';

			if ( isset($do_dropshiptax) && ! $do_dropshiptax ) {
				// ORIGINAL Price
				$html[] = 		'<div class="WooZoneLite-dp-prices-aftertax aa-tooltip data-tippy-size="large" title="' . $text_price_notax . '">';
				if ( isset($price_html['price_orig']) && '' != $price_html['price_orig'] ) {
				$html[] = 			'<i class="WooZoneLite-icon-cash3"></i>';
				$html[] = 			$price_html['price_orig'];
				}
				$html[] = 		'</div>';
			}
			else {
				// ORIGINAL Price
				$html[] = 		'<div class="WooZoneLite-dp-prices-original aa-tooltip data-tippy-size="large" title="' . $text_price_orig . '">';
				if ( isset($price_html['price_orig']) && '' != $price_html['price_orig'] ) {
				$html[] = 			'<i class="WooZoneLite-icon-cash3"></i>';
				$html[] = 			$price_html['price_orig'];
				}
				$html[] = 		'</div>';

				// CURRENT PRICE
				$html[] = 		'<div class="WooZoneLite-dp-prices-aftertax aa-tooltip data-tippy-size="large" title="' . $text_price . '">';
				if ( isset($price_html['price']) && '' != $price_html['price'] ) {
				$html[] = 			'<i class="WooZoneLite-icon-cash3"></i>';
				$html[] = 			$price_html['price'];
				}
				else {
				$html[] = 			'<span class="na">&ndash;</span>';
				}
				$html[] = 		'</div>';

				// PROFIT
				$html[] = 		'<div class="WooZoneLite-dp-prices-profit aa-tooltip data-tippy-size="large" title="' . $text_price_diff . '">';
				if ( isset($price_html['price_diff']) && '' != $price_html['price_diff'] ) {
				$html[] = 			'<i class="WooZoneLite-icon-piggy-bank"></i>';
				$html[] = 			$price_html['price_diff'];
				}
				$html[] = 		'</div>';
			}

			$html[] = '</div>';

			$price_ret = implode( PHP_EOL, $html );

			//:: with main wrapper
			if ( $with_wrapper ) {

				$html = array();

				$html[] = 	'<div class="WooZoneLite-dp-pricebox">';
				$html[] = 		'<div class="WooZoneLite_finalprice_field">';
				$html[] = 			'<label>' . $text_title . ':</label>';
				$html[] = 			'<div class="WooZoneLite-dp-pricebox-price">';
				$html[] = 			$price_ret;
				$html[] = 			'</div>';
				$html[] = 		'</div>';
				$html[] = 	'</div>';

				$price_ret = implode( PHP_EOL, $html );
			}

			return $price_ret;
		}

		// price_arr keys: price_orig, price
		// $pms = same as method 'get_price_html'
		public function get_price_html_profit( $price_arr=array(), $pms=array() ) {

			// $pms = same as method 'get_price_html'
			$pms = array_replace_recursive( array(
				'quantity' 		=> 0,
				'show_profit' 	=> true,
			), $pms );
			extract( $pms );

			$price_orig = isset($price_arr['price_orig']) ? $price_arr['price_orig'] : 0.00;
			$price = isset($price_arr['price']) ? $price_arr['price'] : 0.00;
			//var_dump('<pre>',$price_orig, $price ,'</pre>');

			$price_args = array(); //array( 'currency' => $order->get_currency() );

			$price_sufix = '';
			if ( $quantity ) {
				$price_sufix = " (x$quantity)";
			}

			$_price = wc_price( $price, $price_args ) . $price_sufix;
			$_price_diff = $price != $price_orig
				? wc_price( $price - $price_orig, $price_args ) : wc_price( 0.00, $price_args );

			if ( ! $show_profit && $price == $price_orig ) {
				$_price_diff = '';
			}

			$price_html = $this->get_price_html( null, array_replace_recursive( $pms, array(
				'price_html' 	=> array(
					'price' 		=> $_price,
					'price_diff' 	=> $_price_diff,
				),
			)));
			return $price_html;
		}


		//====================================================
		//== ORDERS

		// get amazon products for an order (by order id)
		public function woo_order_get_amazon_prods( $order_id ) {

			$amz_products = array();

			// Getting an instance of the WC_Order object from a defined order id
			//$order = new WC_Order( $order_id );
			$order = wc_get_order( $order_id );

			$order_items = $order->get_items();

			if ( ! count($order_items) ) return false;

			// Loop to get the order items which are WC_Order_Item_Product objects since WOO 3+
			foreach ( $order_items as $item_id => $item_product ) {

				// Get the common data in an array
				$item_product_data = $item_product->get_data();

				// Get the special meta data in an array
				//$item_product_meta_data = $item_product->get_meta_data();

				$item_metas = $this->woo_order_get_item_metas( $item_product );

				// is amazon product?
				$amzASIN = isset($item_metas['_amz_asin']) && ! empty($item_metas['_amz_asin'])
					? $item_metas['_amz_asin'] : '';

				if ( empty($amzASIN) ) continue 1;

				$parent_amzASIN = isset($item_metas['_amz_parent_asin']) && ! empty($item_metas['_amz_parent_asin'])
					? $item_metas['_amz_parent_asin'] : '';

				$product_id = isset($item_product_data['product_id']) ? $item_product_data['product_id'] : 0;
				$variation_id = isset($item_product_data['variation_id']) ? $item_product_data['variation_id'] : 0;

				$prod_id = $variation_id ? $variation_id : $product_id;

				$parent_id = $variation_id ? $product_id : 0;

				$quantity = isset($item_product_data['quantity']) ? $item_product_data['quantity'] : 1;

				$prodinfo = isset($item_metas['_amz_prodinfo']) ? (array) $item_metas['_amz_prodinfo'] : array();

				$amz_products["$item_id"] = array(
					'item_id'					=> $item_id,
					'product_id'				=> $prod_id,
					'asin'						=> $amzASIN,
					'parent_id'					=> $parent_id,
					'parent_asin' 				=> $parent_amzASIN,
					'quantity'					=> $quantity,
					'prodinfo' 					=> $prodinfo,
				);
			}
			// end Loop

			return $amz_products;
		}

		// get amazon products for an order (by order id) by country availability
		public function woo_order_get_amazon_prods_bycountry( $order_id ) {

			$prods = $this->woo_order_get_amazon_prods( $order_id );
			//var_dump('<pre>', $prods, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( empty($prods) ) return false;

			foreach ($prods as $key => $value) {

				$prod_id = $value['parent_id'] ? $value['parent_id'] : $value['product_id'];
				$prodinfo = $value['prodinfo'] ? $value['prodinfo'] : $value['prodinfo'];

				$countryinfo = $prodinfo['countryinfo'] ? $prodinfo['countryinfo'] : $prodinfo['countryinfo'];
				$product_country = $countryinfo;

				//$prods["$key"] = array_merge($prods["$key"], $product_country);
				$prods["$key"]['countryinfo'] = $product_country;

				$price_orig = isset($prodinfo['price_orig']) ? (float) $prodinfo['price_orig'] : 0.00;
				$price = isset($prodinfo['price']) ? (float) $prodinfo['price'] : 0.00;

				$prods["$key"]['price_orig'] = $price_orig;
				$prods["$key"]['price'] = $price;
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
			//var_dump('<pre>', $bycountry , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			return $bycountry;
		}

		public function woo_order_get_amazon_totals( $order_id, $pms=array() ) {

			$pms = array_replace_recursive( array(
				'shops' => false,
			), $pms );
			extract( $pms );

			$ret = array(
				'bycountry' => array(),
				'gtotal'	=> array(),
			);

			if ( empty($shops) || ! is_array($shops) ) {

				$shops = $this->woo_order_get_amazon_prods_bycountry( $order_id );
			}

			if ( empty($shops) || ! is_array($shops) ) return $ret;

			//:: by country totals
			// loop1/ loops through amazon stores / countries
			$total = array();
			$def_total = array(
				'price_orig' 	=> 0.00,
				'price' 		=> 0.00,
			);
			foreach ( $shops as $shop_country => $shop_info ) {

				if ( ! isset($total["$shop_country"]) ) {
					$total["$shop_country"] = $def_total;
				}

				// loop2/ loops through store products
				foreach ( $shop_info['products'] as $prod_key => $prod_info ) {

					$quantity = (int) $prod_info['quantity'];

					foreach ( array('price_orig', 'price') as $pkey ) {

						if ( isset($prod_info["$pkey"]) && $prod_info["$pkey"] > 0.00 ) {

							if ( $quantity > 1 ) {
								$prod_info["$pkey"] = $prod_info["$pkey"] * $quantity;
							}
							$total["$shop_country"]["$pkey"] += $prod_info["$pkey"];
						}
					}
				}
				// end loop2
			}
			// end loop1

			$ret['bycountry'] = $total;

			//:: all stores total
			$gtotal = $def_total;
			foreach ( $total as $shop_country => $shop_total ) {

				foreach ( array('price_orig', 'price') as $pkey ) {

					if ( isset($shop_total["$pkey"]) && $shop_total["$pkey"] > 0.00 ) {
						$gtotal["$pkey"] += $shop_total["$pkey"];
					}
				}
			}

			$ret['gtotal'] = $gtotal;

			return $ret;
		}

		// item_product = WC_Order_Item_Product objects since WOO 3+
		public function woo_order_get_item_metas( $item_product ) {

			// Get the special meta data in an array
			$item_product_meta_data = $item_product->get_meta_data();

			$item_metas = array();
			foreach ( $item_product_meta_data as $kk => $vv ) {
				$__ = $vv->get_data();
				$__key = $__['key'];
				$__val = $__['value'];
				$item_metas["$__key"] = $__val;
			}
			//var_dump('<pre>', $item_id, $item_metas ,'</pre>'); die;
			return $item_metas;
		}

		// how many amazon products are in an order (based on order_id)
		public function woo_order_has_amazon( $order_id, $get_nb = false ) {

			// Getting an instance of the WC_Order object from a defined order id
			//$order = new WC_Order( $order_id );
			$order = wc_get_order( $order_id );

			$order_items = $order->get_items();

			if ( ! count($order_items) ) return 0;

			$nbfound = 0;

			// Loop to get the order items which are WC_Order_Item_Product objects since WOO 3+
			foreach ( $order_items as $item_id => $item_product ) {

				// Get the common data in an array
				//$item_product_data = $item_product->get_data();

				// Get the special meta data in an array
				//$item_product_meta_data = $item_product->get_meta_data();

				$item_metas = $this->woo_order_get_item_metas( $item_product );

				// is amazon product?
				$amzASIN = isset($item_metas['_amz_asin']) && ! empty($item_metas['_amz_asin'])
					? $item_metas['_amz_asin'] : '';

				if ( empty($amzASIN) ) continue 1;

				$nbfound++;

				// we've found an amazon product!
				if ( ! $get_nb ) {
					return $nbfound;
				}
			}

			return $nbfound;
		}

		public function woo_order_all_amazon_status() {

			$ret = array(
				'new' 			=> __( 'New', 'woozonelite' ),
				'processing' 	=> __( 'Processing', 'woozonelite' ),
				'completed' 	=> __( 'Completed', 'woozonelite' ),
				'error' 		=> __( 'Failed', 'woozonelite' ),
			);
			return $ret;
		}

		public function get_product_country_current( $product ) {
			//return $this->frontend->get_product_country_current( $product_id );

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

			//:: use product import country
			$country = get_post_meta( $product_id, '_amzaff_country', true );
			$country = $this->domain2amzForUser( $country );
			if ( is_array($country)
				&& isset($country['key'])
				&& ! empty($country['key'])
			) {
				$country = array_merge($country, array(
					'available'		=> -1,
				));
				return $country;
			}

			//:: safe case
			$country = $this->frontend->get_product_country_default( $product_id, true );
			return $country;
		}


		//====================================================
		//== import stats methods/ december 2018
		public function import_stats_add_row( $data=array() ) {

			global $wpdb;

			$data = array_replace_recursive( array(
				'post_id' 				=> 0,
				'post_title' 			=> '',
				'asin' 					=> '',
				'provider' 				=> '',
				'country' 				=> '',
				'from_op'				=> '',
				'from_op_p1'			=> '',
				'from_op_p2'			=> '',
				'import_status_msg' 	=> '',

				'duration_spin' 		=> 0,
				'duration_attributes' 	=> 0,
				'duration_vars' 		=> 0,
				'duration_nb_vars' 		=> 0,
				'duration_img' 			=> 0,
				'duration_nb_img' 		=> 0,
				'duration_img_dw' 		=> 0,
				'duration_nb_img_dw'	=> 0,

				// sum(duration_spin, duration_attributes, duration_vars, duration_img, [other product import operations]) ; doesn not contain duration_img_dw
				'duration_product' 		=> 0,

				'db_calc' 				=> null,
			), $data );
			extract( $data );
			//var_dump('<pre>', $data , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$ret = array(
				'status' 	=> 'invalid',
				'msg' 		=> '',
				'insert_id' => 0,
			);

			if ( empty($post_id) || empty($asin) || empty($provider) ) {
				return array_replace_recursive( $ret, array(
					'msg' 	=> 'invalid product data!',
				));
			}

			if ( is_null($db_calc) ) {
				$db_calc = $this->import_stats_db_calc( array(
					'wp_posts',
					'wp_postmeta',
					'wp_terms',
					'nb_prods',
					'nb_attrs',
					'nb_images',
				));
			}


			$table = $wpdb->prefix . 'amz_import_stats';

			// delete old logs for post_id
			$sql = "delete a from $table as a where 1=1 and a.post_id = %d;";
			$sql = $wpdb->prepare( $sql, $post_id );
			$wpdb->query( $sql );

			// now we'll insert the new import log for post_id
			if ( ! empty($data['import_status_msg']) ) {
				$data['import_status_msg'] = maybe_serialize( $data['import_status_msg'] );
			}
			if ( ! empty($data['db_calc']) && is_array($data['db_calc']) ) {
				$data['db_calc'] = maybe_serialize( $data['db_calc'] );
			}

			$format = array();
			foreach ( $data as $kk => $vv ) {
				$regexp = preg_match('/^duration_/iu', $kk, $m);
				//var_dump('<pre>',$kk, $regexp, $m ,'</pre>');
				if ( in_array($kk, array('post_id')) || ! empty($regexp) ) {
					$format[] = '%d';
					continue 1;
				}
				$format[] = '%s';
			}
			//var_dump('<pre>', $data, $format ,'</pre>');

			// insert (ignore)
			$stat = $this->db_custom_insert(
				$table,
				array(
					'values' => $data,
					'format' => $format,
				),
				true
			);

			return array_replace_recursive( $ret, array(
				'status' 	=> 'valid',
				'msg' 		=> 'successfull',
				'insert_id' => $stat,
			));
		}

		public function import_stats_update_imgdw( $data=array() ) {

			global $wpdb;

			$data = array_replace_recursive( array(
				'post_id' 				=> 0,
				'duration_img_dw'		=> 0,
				'duration_img_nb_dw'	=> 0,
			), $data );
			extract( $data );
			//var_dump('<pre>', $data , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$ret = array(
				'status' 	=> 'invalid',
				'msg' 		=> '',
			);

			if ( empty($post_id) || empty($duration_img_dw) || empty($duration_img_nb_dw) ) {
				return array_replace_recursive( $ret, array(
					'msg' 	=> 'invalid product data!',
				));
			}

			$table = $wpdb->prefix . 'amz_import_stats';

			// latest inserted row for a post (post_id): because we can have multiple post_id in this log table: wp_posts can have the ID column auto-increment reseted and a new post with same post_id as an old deleted one can be re-inserted
			$sql = "select a.id, a.duration_img_dw, a.duration_nb_img_dw from $table as a where 1=1 and a.post_id = %d order by a.id desc limit 1;";
			$sql = $wpdb->prepare( $sql, $post_id );
			$row = $wpdb->get_row( $sql );
			if ( empty($row) ) {
				return array_replace_recursive( $ret, array(
					'msg' 	=> 'couldn\'t retrieve the row id based on post_id!',
				));
			}

			// update number & duration for images downloaded on your hosting server
			$sql = "UPDATE $table as a SET a.duration_img_dw = %d, a.duration_nb_img_dw = %d WHERE 1=1 AND a.id = %d;";
			$sql = $wpdb->prepare( $sql,
				(int) ($row->duration_img_dw + $duration_img_dw),
				(int) ($row->duration_nb_img_dw + $duration_img_nb_dw),
				$row->id
			);
			$res = $wpdb->query( $sql );

			return array_replace_recursive( $ret, array(
				'status' 	=> 'valid',
				'msg' 		=> 'successfull',
				'nb_updated' => $res,
			));
		}

		// action = all | array(...)
		public function import_stats_db_calc( $action='all', $pms=array() ) {
			global $wpdb;

			$pms = array_replace_recursive( array(
			), $pms);
			extract( $pms );

			$ret = array();

			//wp_posts, wp_postmeta, wp_terms, wp_termmeta, wp_term_relationships, wp_term_taxonomy
			$arr_key_table = array(
				// key is auto-increment field, value is the table name
				'wp_posts' 				=> 'ID',
				'wp_postmeta' 			=> 'meta_id',
				'wp_terms' 				=> 'term_id',
				'wp_termmeta' 			=> 'meta_id',
				'wp_term_relationships' => 'object_id',
				'wp_term_taxonomy' 		=> 'term_taxonomy_id',
			);
			//'nb_prods', 'nb_prods_provider'
			$actions_all = array('wp_posts_grouped', 'wp_term_taxonomy_grouped', 'nb_attrs', 'nb_prods', 'nb_images');
			$actions_all = array_merge( $actions_all, array_keys($arr_key_table) );
			//var_dump('<pre>', $actions_all , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( 'all' === $action ) {
				$action = $actions_all;
			}


			if ( in_array('wp_posts_grouped', $action) ) {
				$sql = "SELECT p.post_type, p.post_status, COUNT(p.ID) AS nb FROM {$wpdb->prefix}posts AS p WHERE 1=1 GROUP BY p.post_type, p.post_status ORDER BY p.post_type ASC, p.post_status ASC;";
				$res = $wpdb->get_results( $sql, ARRAY_A );

				$ret['wp_posts_grouped'] = $res;
			}

			if ( in_array('wp_term_taxonomy_grouped', $action) ) {
				$sql = "SELECT if( tt.taxonomy REGEXP '^pa_', 'pa_woozonelite', tt.taxonomy ) AS tt_tax, COUNT(tt.term_taxonomy_id) AS nb FROM {$wpdb->prefix}term_taxonomy AS tt WHERE 1=1 GROUP BY tt_tax ORDER BY nb DESC, tt_tax ASC;";
				$res = $wpdb->get_results( $sql, ARRAY_A );

				$ret['wp_term_taxonomy_grouped'] = $res;
			}

			foreach ( $arr_key_table as $key => $val ) {
				if ( in_array($key, $action) ) {
					$key_ = str_replace('wp_', '', $key);
					$sql = "SELECT COUNT($val) as nb FROM {$wpdb->prefix}$key_;";
					$res = $wpdb->get_var( $sql );

					$ret["$key"] = $res;
				}
			}

			if ( in_array('nb_attrs', $action) ) {
				$sql = "SELECT COUNT(tt.term_taxonomy_id) AS nb FROM {$wpdb->prefix}term_taxonomy AS tt WHERE 1=1 AND tt.taxonomy REGEXP '^pa_';";
				$res = $wpdb->get_var( $sql );

				$ret['nb_attrs'] = $res;
			}

			if ( in_array('nb_prods_provider', $action) ) {
				$sql = "
					SELECT
						p.post_type, COUNT(p.ID) AS nb, if( pm.meta_value REGEXP '^(amz|eby)-', SUBSTRING( pm.meta_value, 1, 3 ), 'amz' ) AS provider
					FROM {$wpdb->prefix}posts AS p
					LEFT JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
					WHERE 1=1
						AND p.post_type IN ('product', 'product_variation')
						AND p.post_status = 'publish'
						AND (
							( pm.meta_key = '_amzaff_prodid' AND pm.meta_value not regexp '^amz-' ) OR ( pm.meta_key = '_amzASIN' AND ! isnull(pm.meta_value) )
						)
					GROUP BY provider, p.post_type;
				";
				$res = $wpdb->get_results( trim($sql), ARRAY_A );

				$ret['nb_prods_provider'] = $res;

				$ret['nb_prods_provider_total'] = array();
				if ( ! empty($res) && is_array($res) ) {
					$res_new = array();
					foreach ( $res as $vv ) {
						$vv_ = $vv['post_type'];
						$nb_cur = isset($res_new["$vv_"], $res_new["$vv_"]['nb']) ? (int) $res_new["$vv_"]['nb'] : 0;
						$res_new["$vv_"] = array(
							'post_type' => $vv_,
							'nb' 		=> $nb_cur + $vv['nb'],
						);
					}
					$ret['nb_prods_provider_total'] = array_values( $res_new );
				}
			}

			if ( in_array('nb_prods', $action) ) {
				$sql = "
					SELECT
						p.post_type, COUNT(p.ID) AS nb
					FROM {$wpdb->prefix}posts AS p
					LEFT JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
					WHERE 1=1
						AND p.post_type IN ('product', 'product_variation')
						AND p.post_status = 'publish'
						AND (
							( pm.meta_key = '_amzaff_prodid' AND pm.meta_value not regexp '^amz-' ) OR ( pm.meta_key = '_amzASIN' AND ! isnull(pm.meta_value) )
						)
					GROUP BY p.post_type;
				";
				$res = $wpdb->get_results( trim($sql), OBJECT_K );

				$ret['nb_prods'] = $res;
			}

			if ( in_array('nb_images', $action) ) {
				$sql = "
					SELECT
						COUNT(p2.ID) AS nb
					FROM {$wpdb->prefix}posts AS p
					RIGHT JOIN {$wpdb->prefix}posts AS p2 ON p2.post_parent = p.ID
					LEFT JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
					WHERE 1=1
						AND p2.post_type = 'attachment'
						AND p.post_type IN ('product', 'product_variation')
						AND p.post_status = 'publish'
						AND (
							( pm.meta_key = '_amzaff_prodid' AND pm.meta_value not regexp '^amz-' ) OR ( pm.meta_key = '_amzASIN' AND ! isnull(pm.meta_value) )
						);
				";
				$res = $wpdb->get_var( $sql );

				$ret['nb_images'] = $res;
			}

			//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			return $ret;
		}


		//====================================================
		//== EBAY (when I've added ebay) - around september - december 2018
		public function api_requests_set( $inc=1 ) {
			$today = gmdate('Y-m-d'); //Format a GMT/UTC date/time
			$current = get_option('WooZoneLite_apireq_nb', 0);
			$current_date = get_option('WooZoneLite_apireq_date', '');

			// new day
			if ( $today != $current_date ) {
				update_option('WooZoneLite_apireq_nb', 0);
				update_option('WooZoneLite_apireq_date', $today);
			}
			// same day
			else {
				update_option('WooZoneLite_apireq_nb', (int) ++$current);
			}
		}

		public function api_requests_get() {
			$today = gmdate('Y-m-d'); //Format a GMT/UTC date/time
			$current = get_option('WooZoneLite_apireq_nb', 0);
			$current_date = get_option('WooZoneLite_apireq_date', '');

			$ret = array('nb' => 0, 'date' => '');

			// new day
			if ( $today != $current_date ) {
				$ret = array_merge($ret, array('nb' => 0, 'date' => $today));
			}
			// same day
			else {
				$ret = array_merge($ret, array('nb' => $current, 'date' => $today));
			}
			return $ret;
		}

		public function api_requests_show() {
			$current_stats = $this->api_requests_get();
			$nb = $current_stats['nb'];
			$limit = $this->amz_settings['number_of_requests_daily_limit'];
			$show = isset($this->amz_settings['show_api_requests']) ? $this->amz_settings['show_api_requests'] : 'yes';
			$show = $show == 'yes' ? true : false;

			// print errors
			if ($show) {
				$text = '
					<strong>%s</strong> &#8211;
					Number of requests made today to the ebay api: %s |
					Daily limit: %s |
					<a href="https://go.developer.ebay.com/api-call-limits" target="_blank">EBAY API Call Limits</a>
				';
				$text = sprintf( $text, $this->pluginName, $nb, $limit );
				//echo '<div class="updated"> <p>' . ( $text ) . '</p> </div>';
				return $text;
			}
		}

		public function clean_html( $html, $loose=true ) {
			if ( '' == $html ) {
				return '';
			}

			$html = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $html);

			if ( ! $loose ) {
			$html = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $html);
			$html = preg_replace('/(<[^>]+) class=".*?"/i', '$1', $html);
			$html = preg_replace('/(<[^>]+) width=".*?"/i', '$1', $html);
			$html = preg_replace('/(<[^>]+) height=".*?"/i', '$1', $html);
			$html = preg_replace('/(<[^>]+) alt=".*?"/i', '$1', $html);
			}

			$html = preg_replace('/^<!DOCTYPE.+?>/', '$1', str_replace(array('<html>', '</html>', '<body>', '</body>'), '', $html));
			$html = preg_replace("/<\/?div[^>]*\>/i", "", $html);

			if ( ! $loose ) {
			$html = preg_replace('#(<a.*?>).*?(</a>)#', '$1$2', $html);
			$html = preg_replace('/<a[^>]*>(.*)<\/a>/iU', '', $html);

			$html = preg_replace("/<\/?h1[^>]*\>/i", "", $html);
			$html = preg_replace("/<\/?strong[^>]*\>/i", "", $html);
			$html = preg_replace("/<\/?span[^>]*\>/i", "", $html);
			}

			$html = str_replace('&nbsp;', ' ', $html);
			$html = str_replace('\t', ' ', $html);
			$html = str_replace('  ', ' ', $html);

			$html = preg_replace("/http:\/\/g(\d+)\.a\./i", "https://ae$1.", $html);

			$pattern = "/<[^\/>]*>([\s]?)*<\/[^>]*>/";
			$html = preg_replace($pattern, '', $html);

			$html = force_balance_tags($html);

			return $html;
		}

		public function ebay_addon_controller( $ret_what, $pms=array() ) {
			$pms = array_replace_recursive( array(
			), array());

			$ret = array();

			$wp_root = array();
			$wp_root[] = trailingslashit( ABSPATH ); //preg_replace( '/$\//', '', ABSPATH );
			$wp_root[] = trailingslashit( str_replace( 'wp-content/plugins/woozone/aa-framework/', '', plugin_dir_path( (__FILE__) ) ) );

			$addon_isactive = function_exists('WooZoneProviderEbay') && is_object( WooZoneProviderEbay() );
			$addon_path = 'wp-content/plugins/aawzone-ebay/wzone/ebay/';

			$files = array(
				'file_utils' 	=> 'ebay_utils.php',
				'file_ws' 		=> 'aaWooZoneEbayWS.class.php',
			);

			foreach ( $files as $file_key => $file_name ) {
				if ( $file_key == $ret_what )	{
					if ( $addon_isactive ) {

						foreach ( $wp_root as $wproot ) {
							$file = $wproot . $addon_path . $file_name;

							if ( $this->u->verifyFileExists( $file ) ) {
								return array_replace_recursive( $ret, array(
									'file_path' => $file,
								));
							}
						}
					}
				}
			}
			return $ret;
		}

		public function provider_action_controller( $action, $provider, $pms=array() ) {
			$pms = array_replace_recursive( array(
				'msg_type' 	=> 'default', //what kind of message box to return
			), $pms);
			extract( $pms );

			$ret = array(
				'status' 	=> 'invalid', // valid | invalid
				'msg' 		=> '',
				'msg_html'	=> '',
			);

			$status = 'invalid';
			$msg = '';
			$msg_html = '';
			$do_msg_wrapp = true;

			//:: verify provider mandatory settings
			if ( 'verify_mandatory_settings' == $action ) {

				$provider_status = $this->verify_mandatory_settings( $provider );
				//$provider_status['status'] = 'invalid'; //DEBUG
				$status = $provider_status['status'];

				if ( 'valid' == $provider_status['status'] ) {

					$msg = sprintf(
						__('%s: all the mandatory configuation settings ( %s ) seems ok', 'woozonelite'),
						$provider,
						implode(', ', $provider_status['mandatory_fields'])
					);
				}
				else {
					$msg = sprintf(
						__('%s error: please setup the following mandatory configuation settings: %s', 'woozonelite'),
						$provider,
						implode(', ', $provider_status['mandatory_fields_err'])
					);
				}

				$msg_html = $msg;
			}

			//:: verify provider main helper object exists and is valid!
			if ( 'verify_helper_obj' == $action ) {

				$cond = is_object( $this->get_ws_object( $provider ) ) && is_object( $this->get_ws_object( $provider, 'ws' ) );
				if ( 'ebay' == $provider ) {
					$cond = $cond && is_object( $this->ebay_utils );
				}
				$status = $cond ? 'valid' : 'invalid';

				$msg = $cond
					? sprintf( __('%s: helper object is ok!', 'woozonelite'), $provider )
					: sprintf( __('%s error: cannot initiate helper object!', 'woozonelite'), $provider );

				if ( isset($this->wsStatus["$provider"], $this->wsStatus["$provider"]['msg']) ) {
					$msg = $this->wsStatus["$provider"]['msg'];
				}

				$msg_html = $msg;
			}

			//:: verify if provider is valid: has mandatory settings & main helper object
			if ( 'is_valid' == $action ) {

				$provider_status = $this->provider_action_controller( 'verify_mandatory_settings', $provider, array() );
				if ( 'valid' == $provider_status['status'] ) {
					$provider_status = $this->provider_action_controller( 'verify_helper_obj', $provider, array() );
				}

				//DEBUG
				//if ( 'amazon' == $provider ) { $provider_status['status'] = 'invalid'; }

				extract( $provider_status ); //status, msg, msg_html

				$do_msg_wrapp = false;
			}

			//:: verify that provider (if needed) has the addon plugin activated?
			if ( 'has_addon_activated' == $action ) {

				$cond = $this->providers_is_enabled( $provider );
				$status = $cond ? 'valid' : 'invalid';

				if ( ! $cond ) {
					$msg = sprintf( __('%s error: provider is not enabled!', 'woozonelite'), $provider );
				}
				else if ( 'amazon' == $provider ) {
					$msg = sprintf( __('%s: no addon is necessary!', 'woozonelite'), $provider );
				}
				else if ( 'ebay' == $provider ) {
					$cond = $this->is_plugin_aawzoneebay_active();
					$status = $cond ? 'valid' : 'invalid';

					$msg = sprintf( __('%s: addon is installed and activated!', 'woozonelite'), $provider );
					if ( ! $cond ) {
						$msg = $this->provider_addon_info_box( $provider, $msg_type );
						$do_msg_wrapp = false;
					}
				}

				$msg_html = $msg;
			}

			//:: amazon only/ can import products using aateam demo keys?
			if ( 'can_import_products' == $action ) {

				$provider_status = $this->provider_action_controller( 'is_valid', $provider, array() );

				extract( $provider_status ); //status, msg, msg_html

				$do_msg_wrapp = false;

				if ( 'valid' == $provider_status['status'] ) {

					if ( 'amazon' == $provider ) {
						$cond = $this->can_import_products();
						//$cond = false; //DEBUG
						$status = $cond ? 'valid' : 'invalid';

						if ( ! $cond ) {
							//You can no longer import products using our demo keys.
							//You cannot import products using aateam demo keys as amazon keys.
							$msg = sprintf( __( '%s error: you\'re using aateam demo keys as amazon keys and you\'ve reached the max allowed limit to import products. In this case, some processes like products syncing, cross sell etc, will not work.', 'woozonelite'), $provider );
							$msg_html = $msg;
							$do_msg_wrapp = true;

							if ( 'box_demo' == $msg_type ) {
								$msg_html = $this->demo_products_import_end_html();
								$do_msg_wrapp = false;
							}
						}
					}
				}
			}

			//:: amazon only/ is still using aateam demo keys?
			if ( 'not_aateam_demo_keys' == $action ) {

				$provider_status = $this->provider_action_controller( 'is_valid', $provider, array() );

				extract( $provider_status ); //status, msg, msg_html

				$do_msg_wrapp = false;

				if ( 'valid' == $provider_status['status'] ) {

					if ( 'amazon' == $provider ) {
						$cond = $this->is_aateam_demo_keys() && ! $this->is_aateam_devserver();
						//$cond = true; //DEBUG
						$status = ! $cond ? 'valid' : 'invalid';

						if ( $cond ) {
							$msg = sprintf( __( '%s error: you\'re using aateam demo keys as amazon keys. In this case, some processes like products syncing, cross sell etc, will not work.', 'woozonelite'), $provider );
							$msg_html = $msg;
							$do_msg_wrapp = true;

							if ( 'box_demo' == $msg_type ) {
								$msg_html = $this->demo_products_import_end_html(array(
									'is_block_demo_keys'	=> true,
								));
								$do_msg_wrapp = false;
							}
						}
					}
				}
			}

			//:: is provider process (like syncing) not allowed?
			if ( 'is_process_allowed' == $action ) {

				if ( 'amazon' == $provider ) {

					$provider_status = $this->provider_action_controller( 'can_import_products', 'amazon', array(
						'msg_type' => 'box_demo'
					));

					$do_msg_wrapp = false;

					if ( 'invalid' == $provider_status['status'] ) {
						$html = array();
						$html[] = '<div class="panel-body WooZoneLite-panel-body">';
						$html[] = 	$provider_status['msg_html'];
						$html[] = '</div>';
						$html = implode( PHP_EOL, $html );
						$provider_status['msg_html'] = $html;
						//$do_msg_wrapp = false;
					}
					else {
						$provider_status = $this->provider_action_controller( 'not_aateam_demo_keys', 'amazon', array(
							'msg_type' => 'box_demo'
						));

						if ( 'invalid' == $provider_status['status'] ) {
							$html = array();
							$html[] = '<div class="panel-body WooZoneLite-panel-body">';
							$html[] = 	$provider_status['msg_html'];
							$html[] = '</div>';
							$html = implode( PHP_EOL, $html );
							$provider_status['msg_html'] = $html;
							//$do_msg_wrapp = false;
						}
					}
				}
				else {

					$provider_status = $this->provider_action_controller( 'is_valid', $provider, array() );

					$do_msg_wrapp = false;

					if ( 'invalid' == $provider_status['status'] ) {

						$provider_status_addon = $this->provider_action_controller( 'has_addon_activated', $provider, array(
							'msg_type' => 'box_info'
						));
						if ( 'invalid' == $provider_status_addon['status'] ) {
							$provider_status = $provider_status_addon;
						}

						$html = array();
						$html[] = '<div class="panel-body WooZoneLite-panel-body">';
						$html[] = 	$provider_status['msg_html'];
						$html[] = '</div>';
						$html = implode( PHP_EOL, $html );
						$provider_status['msg_html'] = $html;
						//$do_msg_wrapp = false;
					}
				}

				extract( $provider_status ); //status, msg, msg_html
			}

			if ( $do_msg_wrapp ) {
				if ( 'valid' == $status ) {
					$msg_html = sprintf( '<div class="WooZoneLite-provider WooZoneLite-provider-msg-success %s">%s</div>', $provider, $msg_html );
				}
				else {
					$msg_html = sprintf( '<div class="WooZoneLite-provider WooZoneLite-provider-msg-error %s">%s</div>', $provider, $msg_html );
				}
			}

			$ret = array_replace_recursive( $ret, array(
				'status'	=> $status,
				'msg' 		=> $msg,
				'msg_html' 	=> $msg_html,
			));
			return $ret;
		}

		public function provider_addon_info_box( $provider, $msg_type='default' ) {
			$html = array();

			$css_style = array();
			$css_style[] = 'WooZoneLite-provider-box-inactive';
			$css_style[] = $provider;
			if ( 'box_info' == $msg_type ) {
				$css_style[] = 'noticeboxebay';
			}
			$css_style = implode(' ', $css_style);

			$html[] = '<div class="' . $css_style . '">';

			if ( 'amazon' == $provider ) {
				$html[] = sprintf( __('%s: no addon is necessary!', 'woozonelite'), $provider );
			}
			else if ( 'ebay' == $provider ) {
				if ( 'box_info' == $msg_type ) {
					$html[] = 	sprintf(
						__('WZone eBay Addon is disabled, please keep in mind that this functionality is at the moment only available for Amazon Products. If you wish to use it for eBay Products as well, please purchase the WZone eBay Addon from Codecanyon: <a href="%s" target="_blank">%s</a> ', 'woozonelite'),
						'https://codecanyon.net/item/wzone-addon-woocommerce-ebay-affiliates/23171245?ref=AA-Team',
						'WZone eBay Provider Addon'
					);
				}
				else {
					$html[] = 	sprintf(
						__('The WZone eBay Addon allows you to mass import products from eBay into WooCommerce in just minutes! You can purchase this addon on Codecanyon:  <a href="%s" target="_blank">%s</a>', 'woozonelite'),
						'https://codecanyon.net/item/wzone-addon-woocommerce-ebay-affiliates/23171245?ref=AA-Team',
						'WZone eBay Provider Addon'
					);
				}
			}

			$html[] = '</div>';

			return implode( PHP_EOL, $html );
		}

		public function woo_recount_terms() {
			$product_cats = get_terms(
				'product_cat', array(
					'hide_empty' => false,
					'fields'     => 'id=>parent',
				)
			);
			_wc_term_recount( $product_cats, get_taxonomy( 'product_cat' ), true, false );
			$product_tags = get_terms(
				'product_tag', array(
					'hide_empty' => false,
					'fields'     => 'id=>parent',
				)
			);
			_wc_term_recount( $product_tags, get_taxonomy( 'product_tag' ), true, false );
			//$message = __( 'Terms successfully recounted', 'WooZoneLite' );
			return true;
		}

		public function WooZoneLite_show_table_status( $table_name = "" ) {
			if( isset( $table_name ) && $table_name != '' ) {
				global $wpdb;
				$table_name = "'". $wpdb->prefix . $table_name . "'";
				$table_size = $wpdb->get_results("show table status like ". $table_name);
				$table_size = ( $table_size[0]->Data_length + $table_size[0]->Index_length ) / 1024 / 1024;
				$table_size = round( $table_size, 2 ) . ' MB';
				return  $table_size;
			}
		}

		// Crawler Detect: load SKD
		public function load_sdk_crawlerdetect() {
			$main = 'Crawler-Detect-withoutnamespace'; //Crawler-Detect
			require_once( $this->cfg['paths']['scripts_dir_path'] . "/$main/Fixtures/AbstractProvider.php" );
			require_once( $this->cfg['paths']['scripts_dir_path'] . "/$main/Fixtures/Crawlers.php" );
			require_once( $this->cfg['paths']['scripts_dir_path'] . "/$main/Fixtures/Exclusions.php" );
			require_once( $this->cfg['paths']['scripts_dir_path'] . "/$main/Fixtures/Headers.php" );
			require_once( $this->cfg['paths']['scripts_dir_path'] . "/$main/CrawlerDetect.php" );
		}

		public function reviews_set_affiliateid( $content, $pms=array() ) {

			$pms = array_replace_recursive( array(
				'affid'	=> '',
				'post_id' => 0,
			), $pms);
			extract( $pms );

			if ( empty($affid) && $post_id ) {
				$product_country = $this->get_product_country_current( $post_id );
				$affid = isset($product_country['affID']) ? $product_country['affID'] : '';
			}

			$main_aff_id = $this->main_aff_id();
			$affid = ! empty($affid) ? $affid : $main_aff_id;

			$content = preg_replace('/(tag=)(.*)(\&exp)/imu', '${1}' . $affid . '${3}', $content);
			return $content;
		}


		//====================================================
		//== DIRECT IMPORT related - 2019-jul-11
		public function directimport_get_product_price_format( $price, $country ) {

			$ret = array(
				'Amount' => '0',
				'CurrencyCode' => '',
				'FormattedPrice' => '',
				'_Orig' => $price,
				'_OnlyPrice' => '0.00', //basicaly Amount * 0.01
			);

			if ( '' == $price ) {
				return $ret;
			}

			$_formatted = $price;

			//:: find price & currency in string
			// ex. of how prices as strings are received:
			// 		france: 1 419,00 € | sua/com: $259.00 | uk: £258.99

			//$price = "$2,159.00";
			//$price = "2 199,00 €";
			$price = $this->u->utf8_trim( $price );

			//~([^0-9]*)([0-9,\.\s]*)~im
			$regex = '~([^0-9]*)([0-9,\.\s]*)([^0-9]*)~imu';
			$find = preg_match( $regex, $price, $m );
			//var_dump('<pre>', $price, $find, $m , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			if ( is_array($m) && ! empty($m) ) {
				if ( isset($m[0]) ) {
					$_formatted = $m[0];
					$_formatted = $this->u->utf8_trim( $_formatted );
				}
				if ( isset($m[1]) ) {
					$_currency = $m[1];
					$_currency = $this->u->utf8_trim( $_currency );

					if ( '' === $_currency ) {
						if ( isset($m[3]) ) {
							$_currency = $m[3];
							$_currency = $this->u->utf8_trim( $_currency );
						}
					}
				}
				if ( isset($m[2]) ) {
					$_amount = $m[2];
					$_amount = $this->u->utf8_trim( $_amount );
				}
			}

			//:: formatted price
			$ret['FormattedPrice'] = $_formatted;

			//:: currency
			if ( isset($_currency) ) {

				$_currency = $this->_directimport_get_price_currency_code( $_currency );
				$ret['CurrencyCode'] = $_currency;
			}

			//:: amount
			if ( isset($_amount) ) {

				$_amount = $this->_directimport_get_price_format_bycountry( $_amount, $country );

				$_onlyprice = $_amount;
				$_onlyprice = number_format( $_onlyprice, 2, '.', '' );
				$ret['_OnlyPrice'] = (string) $_onlyprice;

				$_amount = $_amount * 100;
				$_amount = number_format( $_amount, 0, '.', '' );
				//$_amount = number_format( $_amount, 2, '.', '' );
				$ret['Amount'] = $_amount;
			}

			//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			return $ret;
		}

		private function _directimport_get_price_format_bycountry( $amount, $country ) {

			//var_dump('<pre>', $amount, $country , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			//countries = array( 'com', 'ca', 'cn', 'de', 'in', 'it', 'es', 'fr', 'co.uk', 'co.jp', 'com.mx', 'com.br', 'com.au' );

			// price like: 15,20 (decimals with , and thousands with .)
			if ( in_array( $country, array('de', 'it', 'es', 'fr', 'com.br') ) ) {
				$amount = str_replace( ',', '.', str_replace('.', '', $amount) );
			}
			// price like: 15.20 (decimals with . and thousands with ,)
			else {
				$amount = str_replace(',', '', $amount);
			}

			$amount = preg_replace("/[^0-9\.]/", '', $amount);
			$amount = (float) $amount;
			//var_dump('<pre>', $amount, $country , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			return $amount;
		}

		private function _directimport_get_price_currency_code( $currency ) {
			$codes = array(
				'$' => 'USD',
				'€' => 'EUR',
				'£' => 'GBP', //great britain pound sterling
			);

			if ( isset($codes["$currency"]) ) {
				return $codes["$currency"];
			}
			return $currency;
		}

		// update on 2020-feb-06
		public function getAssociateTagByCountry( $country ) {
			// aff ids
			$aff_ids = $this->get_aff_ids();
			$main_aff_id = isset($aff_ids['main_aff_id']) ? $aff_ids['main_aff_id'] : '<something-is-wrong>';
			$aff_ids = isset($aff_ids['aff_ids']) ? $aff_ids['aff_ids'] : array();
			//$aff_ids = ! empty($aff_ids) && is_array($aff_ids) ? $aff_ids : array();
			//var_dump('<pre>', $country, $main_aff_id, $aff_ids , '</pre>'); echo __FILE__ . ":". __LINE__;die . PHP_EOL;

			$ret = $main_aff_id;

			if ( empty($aff_ids) || ! is_array($aff_ids) ) {
				return $ret;
			}

			foreach ( $aff_ids as $item ) {
				$item_country = (string) $item['country'];
				$item_affid = (string) $item['aff_id'];

				if ( $country === $item_country ) {
					$ret = $item_affid;
					//return $ret;
					break;
				}
			}
			//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":". __LINE__;die . PHP_EOL;
			return $ret;
		}

	}
}
// __DIR__ - uses PHP 5.3 or higher
// require_once( __DIR__ . '/functions.php');
require_once( dirname(__FILE__) . '/functions.php');
