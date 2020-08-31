<?php
/*
* Define class WooZoneLiteAddons
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;

if (class_exists('WooZoneLiteAddons') != true) {
	class WooZoneLiteAddons
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


		/*
		* Required __construct() function that initalizes the AA-Team Framework
		*/
		public function __construct()
		{
			global $WooZoneLite;

			$this->the_plugin = $WooZoneLite;
			$this->module_folder = $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'modules/addons/';
			$this->module = $this->the_plugin->cfg['modules']['addons'];

			if ( $this->the_plugin->is_admin ) {
				add_action('admin_menu', array( $this, 'adminMenu' ));
			}
		}

		/**
	    * Hooks
	    */
	    static public function adminMenu()
	    {
	       self::getInstance()
	    		->_registerAdminPages();
	    }

	    /**
	    * Register plug-in module admin pages and menus
	    */
		protected function _registerAdminPages()
    	{
    		add_submenu_page(
    			$this->the_plugin->alias,
    			$this->the_plugin->alias . " " . __('Addons & Themes', $this->the_plugin->localizationName),
	            __('Addons & Themes', $this->the_plugin->localizationName),
	            'manage_options',
	            $this->the_plugin->alias . "_addons",
	            array($this, 'printBaseInterface')
	        );

			return $this;
		}

		public function printBaseInterface()
		{
			global $wpdb;
?>

	<div class="woozonelite-title_line" style="margin-bottom:10px">
		<div class="aat-icon"></div>
		<div class="aat-musthave"></div>
	</div>


		<div id="<?php echo WooZoneLite()->alias?>" class="WooZoneLite-addons">

			<div class="<?php echo WooZoneLite()->alias?>-content">

				<?php
				// show the top menu
				WooZoneLiteAdminMenu::getInstance()->make_active('addons|addons')->show_menu();
				?>

				<!-- Content -->
				<section class="WooZoneLite-main">

					<?php
					echo WooZoneLite()->print_section_header(
						$this->module['addons']['menu']['title'],
						$this->module['addons']['description'],
						$this->module['addons']['help']['url']
					);

					require_once( $this->the_plugin->cfg['paths']['freamwork_dir_path'] . 'settings-template.class.php');

					// Initalize the your aaInterfaceTemplates
					$aaInterfaceTemplates = new aaInterfaceTemplates($this->the_plugin->cfg);

					// then build the html, and return it as string
					echo $aaInterfaceTemplates->build_page( $this->options(), $this->the_plugin->alias, $this->module);
					?>
				</section>
			</div>
		</div>

<?php
		}

		private function options()
		{
			return array(
				$this->module['db_alias'] => array(

					/* define the form_sizes  box */
					'addons' => array(
						'title' => 'Amazon settings',
						'icon' => '{plugin_folder_uri}images/amazon.png',
						'size' => 'grid_4', // grid_1|grid_2|grid_3|grid_4
						'header' => false, // true|false
						'toggler' => false, // true|false
						'buttons' => true, // true|false
						'style' => 'panel', // panel|panel-widget

						// create the box elements array
						'elements' => array(

							'headeline' => array(
								'type' => 'html',
								'html' => $this->the_box(),
							),
						)
					)
				)
			);
		}

		/**
		* Singleton pattern
		*
		* @return WooZoneLiteAddons Singleton instance
		*/
		static public function getInstance()
		{
			if (!self::$_instance) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}


		//======================================
		//== @alexandra
		public function the_box() {

			// current theme
			$theme_name = wp_get_theme();
			$theme_name_ = $theme_name->get( 'Name' );

			$html = array();
			ob_start();
		?>
		<div class="aat-widget-wrapper">

		 <div class="aat-add-widget">

		 		<!-- addon div -->
		 		<div class="aat-addon-box" rel="aataddon-onclick-gproducts">
		 			<div class="aat-addon-ind">
		 				<a href="#"><img src='<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . "modules/addons/images/gproducts.png"; ?>' alt='' /></a>
		 				<h1> GProducts </h1>
		 				 <?php
	            if ( is_plugin_active( 'product-boxes/index.php' ) ) {
	              echo '<div class="aat-installed is-installed">installed</div>';
	            }
	           	else {
	             	echo '<div class="aat-installed">not installed</div>';
	            }
	           ?>
		 			</div>
		 		</div>

		 	 	<!-- addon div -->
		 		<div class="aat-addon-box" rel="aataddon-onclick-compare">
		 			<div class="aat-addon-ind">
		 				<a href="#"><img src='<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . "modules/addons/images/compareazon.png"; ?>' alt='' /></a>
		 				<h1> CompareAzon Addon </h1>
		 				 <?php
	            if ( is_plugin_active( 'compareazon/index.php' ) ) {
	              echo '<div class="aat-installed is-installed">installed</div>';
	            }
	           	else {
	             	echo '<div class="aat-installed">not installed</div>';
	            }
	           ?>
		 			</div>
		 		</div>

		 	<!-- addon div -->
		 		<div class="aat-addon-box" rel="aataddon-onclick-gzone">
		 			<div class="aat-addon-ind">
		 				<a href="#"><img src='<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . "modules/addons/images/gzone.png"; ?>' alt='' /></a>
		 				<h1> GZone Addon </h1>
		 				 <?php
	            if ( is_plugin_active( 'gzone-products-block/index.php' ) ) {
	              echo '<div class="aat-installed is-installed">installed</div>';
	            }
	           	else {
	             	echo '<div class="aat-installed">not installed</div>';
	            }
	           ?>
		 			</div>
		 		</div>

			 	<!-- addon div -->
		 		<div class="aat-addon-box" rel="aataddon-onclick-ebay">
		 			<div class="aat-addon-ind">
		 				<a href="#"><img src='<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . "modules/addons/images/ebay.png"; ?>' alt='' /></a>
		 				<h1> eBay Addon </h1>
		 				 <?php
	         			if ( $this->the_plugin->is_plugin_aawzoneebay_active() ) {
	              echo '<div class="aat-installed is-installed">installed</div>';
	            }
	           	else {
	             	echo '<div class="aat-installed">not installed</div>';
	            }
	           ?>
		 			</div>
		 		</div>

		 		 <!-- addon div -->
		 		<div class="aat-addon-box" rel="aataddon-onclick-additional">
		 			<div class="aat-addon-ind">
		 				<a href="#"><img src='<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . "modules/addons/images/additional.png"; ?>' alt='' /></a>
		 				<h1> Additional Variation</h1>
		 				 <?php
	         		if ( $this->the_plugin->is_plugin_avi_active() ) {
	              echo '<div class="aat-installed is-installed">installed</div>';
	            }
	           	else {
	             	echo '<div class="aat-installed">not installed</div>';
	            }
	           ?>
		 			</div>
		 		</div>

		 		<!-- addon div -->
		 		<div class="aat-addon-box" rel="aataddon-onclick-kingdom">
		 			<div class="aat-addon-ind">
		 				<a href="#"><img src='<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . "modules/addons/images/kingdom.jpg"; ?>' alt='' /></a>
		 				<h1> Kingdom </h1>
		 				 <?php
	         		 if ( in_array($theme_name_, array(
		            //'Kingdom - Woocommerce Amazon Affiliates Theme',
		            'Kingdom',
		            'Kingdom Child Theme',
		          )) ) {
	              echo '<div class="aat-installed is-installed">installed</div>';
	            }
	           	else {
	             	echo '<div class="aat-installed">not installed</div>';
	            }
	           ?>
		 			</div>
		 		</div>

		 		<!-- addon div -->
		 		<div class="aat-addon-box" rel="aataddon-onclick-bravo">
		 			<div class="aat-addon-ind">
		 				<a href="#"><img src='<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . "modules/addons/images/bravo.jpg"; ?>' alt='' /></a>
		 				<h1> BravoStore </h1>
		 				 <?php
		         	if ( in_array($theme_name_, array(
	              'BravoStore',
	              'BravoStore Child Theme',
	            )) ) {
	              echo '<div class="aat-installed is-installed">installed</div>';
	            }
	           	else {
	             	echo '<div class="aat-installed">not installed</div>';
	            }
	           ?>
		 			</div>
		 		</div>

		 		<!-- addon div -->
		 		<div class="aat-addon-box" rel="aataddon-onclick-themarket">
		 			<div class="aat-addon-ind">
		 				<a href="#"><img src='<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . "modules/addons/images/themarket.jpg"; ?>' alt='' /></a>
		 				<h1> The Market </h1>
		 				 <?php
		         	if ( in_array($theme_name_, array(
	              'TheMarket',
	              'The Market Child Theme',
	            )) ) {
	              echo '<div class="aat-installed is-installed">installed</div>';
	            }
	           	else {
	             	echo '<div class="aat-installed">not installed</div>';
	            }
	           ?>
		 			</div>
		 		</div>

		 		<!-- addon div -->
		 		<div class="aat-addon-box" rel="aataddon-onclick-composer">
		 			<div class="aat-addon-ind">
		 				<a href="#"><img src='<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . "modules/addons/images/composer.jpg"; ?>' alt='' /></a>
		 				<h1> WZone Addon for WPBakery</h1>
		 				 <?php
		         	if ( in_array($theme_name_, array(
	              'TheMarket',
	              'The Market Child Theme',
	            )) ) {
	              echo '<div class="aat-installed is-installed">installed</div>';
	            }
	           	else {
	             	echo '<div class="aat-installed">not installed</div>';
	            }
	           ?>
		 			</div>
		 		</div>

		 		<!-- addon div -->
		 		<div class="aat-addon-box" rel="aataddon-onclick-searchazon">
		 			<div class="aat-addon-ind">
		 				<a href="#"><img src='<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . "modules/addons/images/searchazon.jpg"; ?>' alt='' /></a>
		 				<h1> SearchAzon</h1>
		 				 <?php
		         	if ( in_array($theme_name_, array(
	              'TheMarket',
	              'The Market Child Theme',
	            )) ) {
	              echo '<div class="aat-installed is-installed">installed</div>';
	            }
	           	else {
	             	echo '<div class="aat-installed">not installed</div>';
	            }
	           ?>
		 			</div>
		 		</div>

		 		<!-- addon div -->
		 		<div class="aat-addon-box" rel="aataddon-onclick-gutensearch">
		 			<div class="aat-addon-ind">
		 				<a href="#"><img src='<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . "modules/addons/images/gutensearch.jpg"; ?>' alt='' /></a>
		 				<h1> GutenSearch</h1>
		 				 <?php
		         	if ( in_array($theme_name_, array(
	              'gutensearch',
	              'gutensearch',
	            )) ) {
	              echo '<div class="aat-installed is-installed">installed</div>';
	            }
	           	else {
	             	echo '<div class="aat-installed">not installed</div>';
	            }
	           ?>
		 			</div>
		 		</div>
		 </div>


		<!-- addon right container -->
		  <div class="aat-add-widget-bgcolor">


		  		<!-- addon details -->
			<div class="aat-addon-box-details" id="aataddon-onclick-gproducts">
				<h3>GProducts - Amazon Affiliates Products Boxes Block</h3>
				<a href="https://1.envato.market/kEbD3" target="_new" class="aat-add-button">Install Add-On</a>
				<p>GProducts allows you to embed and display several Amazon products and provide relevant information – product details – rating, price, description, free shipping, prime ready & more into a great looking box!
<br/><br/>
No coding necessary, no amazon API keys, the plugin works as is. Simply input your Affiliate ID and start making money!
<br/><br/>
Promptly find and select the amazon products by ASIN or Keyword and add them in a few simple steps!

 </p>
				<div class="aat-details">
					<p>Available Version: <span> 1.0</span></p>
				</div>
			</div>

		  	<!-- addon details -->
			<div class="aat-addon-box-details" id="aataddon-onclick-compare">
				<h3>CompareAzon - Amazon Product Comparison Tables</h3>
				<a href="https://1.envato.market/1LZM6" target="_new" class="aat-add-button">Install Add-On</a>
				<p>CompareAzon allows you to embed and compare several Amazon products and provide relevant information – product advantages and disadvantages into a great looking comparison table!
<br/><br/>
Easily build Comparison Tables on your website, embed and Compare Amazon Products and Showcase them into your Website by using Gutenberg blocks!
<br/>
Search and Embed Amazon Products into comparison tables in just a flash!
<br/>
No coding necessary, no amazon API keys, the plugin works as is. Simply input your Affiliate ID and start making money!
<br/>
Promptly find and select the amazon products by ASIN or Keyword and compare them in a few simple steps!</p>
				<div class="aat-details">
					<p>Available Version: <span> 1.0</span></p>
				</div>
			</div>


		  <!-- addon details -->
			<div class="aat-addon-box-details" id="aataddon-onclick-gzone">
				<h3>GZone - Insert Amazon / WooCommerce Products into Posts / Pages</h3>
				<a href="https://1.envato.market/AD5qJ" target="_new" class="aat-add-button">Install Add-On</a>
				<p>After you import products from Amazon, go and add any blog post / page , click on Add New Block, and you will find the Insert Amazon Products Block under the WooCommerce Section . Simply bulk select the products you wish to display into the blog post, click on insert products, choose the desired design and that’s all!</p>
				<div class="aat-details">
					<p>Available Version: <span> 1.0</span></p>
					<p>Requirements: WZone <span>V.12.5</span></p>
				</div>
			</div>

			<!-- addon details -->
			<div class="aat-addon-box-details" id="aataddon-onclick-ebay">
				<h3>WZone eBay Provider Addon</h3>
				<a href="https://1.envato.market/zkDgx" target="_new" class="aat-add-button">Install Add-On</a>
				<p>The WZone eBay addon allows you to mass import products from eBay into WooCommerce in just minutes!
				This is an Provider Addon made specially for WZone where you now have the possibility to have more than one provider from which you can import products into WooCommerce.</p>
				<div class="aat-details">
					<p>Available Version: <span> 1.0.1</span></p>
					<p>Requirements: WZone <span>V.12.5</span></p>
				</div>
			</div>

			<!-- addon details -->
			<div class="aat-addon-box-details" id="aataddon-onclick-additional">
				<h3> Additional Variation Images Plugin for WooCommerce</h3>
				<a href="https://1.envato.market/03BQY" target="_new" class="aat-add-button">Install Add-On</a>
				<p>Optimize your WooCommerce product variation image gallery and boost your sales today!
				Showcase Product Variations by Importing any number of Additional Images for each Variation by using WZone and the Additional Variation Images Plugin! </p>
				<div class="aat-details">
					<p>Available Version: <span> 1.1</span></p>
					<p>Requirements: WZone <span>V.12.</span></p>
				</div>
			</div>

			<!-- addon details -->
			<div class="aat-addon-box-details" id="aataddon-onclick-kingdom">
				<h3>Kingdom - WooCommerce Amazon Affiliates Theme</h3>
				<a href="https://1.envato.market/eGx46" target="_new" class="aat-add-button">Install Add-On</a>
				<p>Kingdom is 100% compatible with WZone and it comes with some great features that will help you create a store featuring Amazon products in no time!</p>
				<div class="aat-details">
					<p>Available Version: <span> 3.7</span></p>
					<p>Requirements: WZone <span>V.12</span></p>
				</div>
			</div>


			<!-- addon details -->
			<div class="aat-addon-box-details" id="aataddon-onclick-bravo">
				<h3>Bravo Store - WZone Affiliates Theme for WordPress</h3>
				<a href="https://1.envato.market/LGYPV" target="_new" class="aat-add-button">Install Add-On</a>
				<p>BravoStore is a Woocommerce Amazon Affiliates Theme. This is the second theme that’s 100% compatible with WZone!</p>
				<div class="aat-details">
					<p>Available Version: <span> 1.2</span></p>
					<p>Requirements: WZone <span>V.12</span></p>
				</div>
			</div>

			<!-- addon details -->
			<div class="aat-addon-box-details" id="aataddon-onclick-themarket">
				<h3>The Market - WZone Affiliates Theme</h3>
				<a href="https://codecanyon.net/item/the-market-woozone-affiliates-theme/13469852?ref=AA-Team" target="_new" class="aat-add-button">Install Add-On</a>
				<p>The Market has a minimalist design. Modern, one page & full width. Also Responsive!</p>
				<div class="aat-details">
					<p>Available Version: <span> 2.0</span></p>
					<p>Requirements: WZone <span>V.12</span></p>
				</div>
			</div>

			<!-- addon details -->
			<div class="aat-addon-box-details" id="aataddon-onclick-composer">
				<h3>Amazon Affiliates Addon for WPBakery Page Builder (formerly Visual Composer)</h3>
				<a href="https://1.envato.market/begbm" target="_new" class="aat-add-button">Install Add-On</a>
				<p>Azon Addon allows you to add Amazon products into your Website. This is an addon for WP Bakery Page Builder, so you must have it installed.

					Using the Azon Addon you will be able to browse through Amazon Departments and Showcase Products into any WordPress page! No coding necessary, no amazon API keys, the addon works as is.</p>
				<div class="aat-details">
					<p>Available Version: <span> 1.2</span></p>
					<p>Requirements: WZone <span>V.12</span></p>
				</div>
			</div>


			<!-- addon details -->
			<div class="aat-addon-box-details" id="aataddon-onclick-searchazon">
				<h3>SearchAzon - WooCommerce Amazon Affiliates Auto Search Plugin</h3>
				<a href="https://1.envato.market/OJLBK" target="_new" class="aat-add-button">Install Add-On</a>
				<p>SearchAzon allows you to integrate the Amazon Search functionality into your Website. You can choose to Display Amazon Search Results on your Website and Redirect Visitors to Amazon.</p>
				<div class="aat-details">
					<p>Available Version: <span> 1.3</span></p>
					<p>Requirements: WZone <span>V.12</span></p>
				</div>
			</div>

			<!-- addon details -->
			<div class="aat-addon-box-details" id="aataddon-onclick-gutensearch">
				<h3>GutenSearch - Amazon Affiliates Products Search and Embed</h3>
				<a href="https://1.envato.market/ZDEP0" target="_new" class="aat-add-button">Install Add-On</a>
				<p>sing this GutenSearch block you will be able to display any product from Amazon into any WordPress page / post by simply searching! Simply Search and Embed any Amazon products! No coding necessary, no amazon API keys, the addon works as is.</p>
				<div class="aat-details">
					<p>Available Version: <span> 1.0.1</span></p>
					<p>Requirements: WZone <span>V.12</span></p>
				</div>
			</div>

		  </div>
		 </div>



		<?php
			$html[] = ob_get_clean();
			return implode( PHP_EOL, $html );
		}

	}
}

// Initialize the WooZoneLiteAddons class
$WooZoneLiteAddons = WooZoneLiteAddons::getInstance();
