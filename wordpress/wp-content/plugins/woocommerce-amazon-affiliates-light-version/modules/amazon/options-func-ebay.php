<?php

/* ebay */
function WooZoneLiteAffIDsHTML___ebay( $istab = '', $is_subtab='' )
{
    global $WooZoneLite;
    
    $html         = array();
    $img_base_url = $WooZoneLite->cfg['paths']["plugin_dir_url"] . 'modules/amazon/images/flags/';
    
    //$config = @unserialize(get_option($WooZoneLite->alias . '_amazon'));
    $config = $WooZoneLite->settings();

	$theHelper = $WooZoneLite->get_ws_object( 'ebay', 'helper' );
	//:: disabled on 2018-aug
	////$list = is_object($WooZoneLite->get_ws_object( 'ebay' )) ? $WooZoneLite->get_ws_object( 'ebay' )->get_countries() : array();
	//$config = $WooZoneLite->build_amz_settings(array(
	//	'ebay_DEVID'		=> 'zzz',
	//	'ebay_AppID'		=> 'zzz',
	//	'ebay_CertID'		=> 'zzz',
	//	'ebay_country'		=> 'EBAY-US',
	//));
	//require_once( $WooZoneLite->cfg['paths']['plugin_dir_path'] . 'aa-framework/ebay.helper.class.php' );
	//if ( class_exists('WooZoneLiteEbayHelper') ) {
	//	//$theHelper = WooZoneLiteEbayHelper::getInstance( $WooZoneLite );
	//	$theHelper = new WooZoneLiteEbayHelper( $WooZoneLite );
	//}
	$list = is_object($theHelper) ? $theHelper->get_countries() : array();
	
	ob_start();
?>
	<style>
		.WooZoneLite-form .WooZoneLite-form-row .WooZoneLite-form-item.large .WooZoneLite-div2table {
			display: table;
			width: 420px;
		}
			.WooZoneLite-form .WooZoneLite-form-row .WooZoneLite-form-item.large .WooZoneLite-div2table .WooZoneLite-div2table-tr {
				display: table-row;
			}
				.WooZoneLite-form .WooZoneLite-form-row .WooZoneLite-form-item.large .WooZoneLite-div2table .WooZoneLite-div2table-tr > div {
					display: table-cell;
				}
	</style>
    <div class="WooZoneLite-form-row <?php echo ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : ''); ?>">
    <label>Your Affiliate campids</label>
    <div class="WooZoneLite-form-item large">
    <span class="formNote">You get this ID by signing up for Ebay Associates.</span>
    <div class="WooZoneLite-aff-ids WooZoneLite-div2table">
    	<?php
    	foreach ($list as $globalid => $country_name) {
    	?>
    	<div class="WooZoneLite-div2table-tr">
	    	<?php /*<div>
	    		<img src="<?php echo $img_base_url; ?>US-flag.gif" height="20">
	    	</div>*/ ?>
	    	<div>
	    		<input type="text" value="<?php echo isset($config['ebay_AffiliateID']["$globalid"]) ? $config['ebay_AffiliateID']["$globalid"] : ''; ?>" name="ebay_AffiliateID[<?php echo $globalid; ?>]" id="ebay_AffiliateID[<?php echo $globalid; ?>]" placeholder="ENTER YOUR AFFILIATE ID FOR <?php echo strtoupper($globalid); ?>">
	    	</div>
	    	<div>
	    		<?php echo $country_name; ?>
	    	</div>
	    </div>
	    <?php
		}
		?>
    </div>
<?php
	$html[] = ob_get_clean();

    $html[] = '<h3>Some hints and information:</h3>';
    //$html[] = '- The link will use IP-based Geolocation to geographically target your visitor to the Ebay store of his/her country (according to their current location). <br />';
    //$html[] = '- You don\'t have to specify all affiliate IDs if you are not registered to all programs. <br />';
    //$html[] = '- The ASIN is unfortunately not always globally unique. That\'s why you sometimes need to specify several ASINs for different shops. <br />';
    //$html[] = '- If you have an English website, it makes most sense to sign up for the US, UK and Canadian programs. <br />';
	$html[] = '- The ID of the campaign you specify, each campaign has its own ID. (i.e. 1234), You can find it on your partner account : <a target="_blank" href="https://publisher.ebaypartnernetwork.com/files/hub/en-US/index.html">eBay Partner Network</a>';
    $html[] = '</div>';
    $html[] = '</div>';
    
    return implode("\n", $html);
}

function WooZoneLite_ebay_countries__( $istab = '', $is_subtab='', $what='array' ) {
    global $WooZoneLite;
    
    $html         = array();
    $img_base_url = $WooZoneLite->cfg['paths']["plugin_dir_url"] . 'modules/amazon/images/flags/';
    
    //$config = @unserialize(get_option($WooZoneLite->alias . '_amazon'));
    $config = $WooZoneLite->settings();
	
	$theHelper = $WooZoneLite->get_ws_object( 'ebay', 'helper' );
	//:: disabled on 2018-aug
	////$list = is_object($WooZoneLite->get_ws_object( 'ebay' )) ? $WooZoneLite->get_ws_object( 'ebay' )->get_countries() : array();
	//$config = $WooZoneLite->build_amz_settings(array(
	//	'ebay_DEVID'		=> 'zzz',
	//	'ebay_AppID'		=> 'zzz',
	//	'ebay_CertID'		=> 'zzz',
	//	'ebay_country'		=> 'EBAY-US',
	//));
	//require_once( $WooZoneLite->cfg['paths']['plugin_dir_path'] . 'aa-framework/ebay.helper.class.php' );
	//if ( class_exists('WooZoneLiteEbayHelper') ) {
	//	//$theHelper = WooZoneLiteEbayHelper::getInstance( $WooZoneLite );
	//	$theHelper = new WooZoneLiteEbayHelper( $WooZoneLite );
	//}
	$list = is_object($theHelper) ? $theHelper->get_countries() : array();
	
	if ( 'array' == $what ) {
		return $list;
	}
	return implode(', ', array_values($list));
}