<?php
/*
http://docs.aws.amazon.com/AWSECommerceService/latest/DG/CHAP_response_elements.html 
$('div.informaltable > table tr').each(function(i, el) {
	var $this = $(el), $td = $this.find('td:first'),
	$a = $td.find('a'), text = $a.attr('name');

	if ( typeof text == 'undefined' || text == '' ){
		text = $td.find('.code').text();
	}
	if ( typeof text != 'undefined' && text != '' ) {
		var text2 = text; //text.match(/([A-Z]?[^A-Z]*)/g).slice(0,-1).join(' ');
		console.log( '\''+text+'\' => \''+text+'\',' );
	}
});
*/
function WooZoneLite_attributesList() {
	require_once( 'lists.inc.php' );
	return $attrList;
}

function WooZoneLite_imageSizes() {
	global $WooZoneLite;
	
	$ret = array();
	$list = $WooZoneLite->u->get_image_sizes();
	foreach ($list as $k => $v) {
		$ret["$k"] = $k . ' ' . sprintf( '(%s X %s)', $v['width'], $v['height'] );
	}
	return $ret;
}

function WooZoneLite_variation_number() {
	$ret = array(
		'no'        => 'NO',
		'yes_1'     => 'Yes 1 variation',
		'yes_2'     => 'Yes 2 variations',
		'yes_3'     => 'Yes 3 variations',
		'yes_4'     => 'Yes 4 variations',
		'yes_5'     => 'Yes 5 variations',
		'yes_10'    => 'Yes 10 variations',
		'yes_all'   => 'Yes All variations',
	);

	$ret = array();
	$ret['no'] = 'NO';
	for ($ii = 1; $ii < 100; $ii++) {
		$ret["yes_$ii"] = "Yes $ii variation" . ($ii > 1 ? 's' : '');
	}
	$ret['yes_all'] = 'Yes All variations';
	return $ret;
}

function WooZoneLite_productinpost_extra_css() {
	/*
	.wb-buy {
		width: 176px;
		height: 28px;
		background: url(images/buy-amazon.gif) no-repeat top left;
		text-indent: -99999px;
		overflow: hidden;
		display: block;
		opacity: 0.7;
		transition: opacity 350ms ease;
	}
	*/
	ob_start();
?>
	.wb-box {
		background-color: #f9f9f9;
		border: 1px solid #ecf0f1;
		border-radius: 5px;
		font-family: 'Open Sans', sans-serif;
		margin: 20px auto;
		padding: 2%;
		width: 90%;
		max-width: 660px;
		font-family: 'Open Sans';
	}
<?php
	$html = ob_get_contents();
	ob_end_clean();
	return $html;
}

function WooZoneLite_asof_font_size($min=0.1, $max=2.0, $step=0.1) {
	$newarr = array();
	for ($i=$min; $i <= $max; $i += $step, $i = (float) number_format($i, 1)) {
		$newarr[ "$i" ] = $i . ' em';
	}
	return $newarr;
}



function WooZoneLiteAffIDsHTML( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
	
	$html         = array();
	$img_base_url = $WooZoneLite->cfg['paths']["plugin_dir_url"] . 'modules/amazon/images/flags/';
	
	$config = $WooZoneLite->settings();
 
	$theHelper = $WooZoneLite->get_ws_object_new( 'amazon', 'new_helper', array(
		'the_plugin' => $WooZoneLite,
	));
	//:: disabled on 2018-feb
	//require_once( $WooZoneLite->cfg['paths']['plugin_dir_path'] . 'aa-framework/amz.helper.class.php' );
	//if ( class_exists('WooZoneLiteAmazonHelper') ) {
	//	//$theHelper = WooZoneLiteAmazonHelper::getInstance( $WooZoneLite );
	//	$theHelper = new WooZoneLiteAmazonHelper( $WooZoneLite );
	//}
	//:: end disabled on 2018-feb
	$what = 'main_aff_id';
	$list = is_object($theHelper) ? $theHelper->get_countries( $what ) : array();
	
	ob_start();
?>
	<style type="text/css">
		.WooZoneLite-form .WooZoneLite-form-row .WooZoneLite-form-item.large .WooZoneLite-div2table {
			display: table;
			width: 420px;
		}
			.WooZoneLite-form .WooZoneLite-form-row .WooZoneLite-form-item.large .WooZoneLite-div2table .WooZoneLite-div2table-tr {
				display: table-row;
			}
				.WooZoneLite-form .WooZoneLite-form-row .WooZoneLite-form-item.large .WooZoneLite-div2table .WooZoneLite-div2table-tr > div {
					display: table-cell;
					padding: 5px;
				}
	</style>
	<div class="panel-body <?php echo WooZoneLite()->alias;?>-panel-body <?php echo WooZoneLite()->alias;?>-form-row <?php echo ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : ''); ?>">
	<label class="<?php echo  WooZoneLite()->alias;?>-form-label">Your Affiliate IDs</label>
	<div class="<?php echo  WooZoneLite()->alias;?>-form-item large">
	<span class="formNote">Your Affiliate ID probably ends in -20, -21 or -22. You get this ID by signing up for Amazon Associates.</span>
	<div class="<?php echo  WooZoneLite()->alias;?>-aff-ids <?php echo  WooZoneLite()->alias;?>-div2table">
		<?php
		foreach ($list as $globalid => $country_name) {
			$flag = 'com' == $globalid ? 'us' : $globalid;
			$flag = strtoupper($flag);
		?>
		<div class="<?php echo  WooZoneLite()->alias;?>-div2table-tr">
			<div>
				<img src="<?php echo $img_base_url . $flag; ?>-flag.gif" height="20">
			</div>
			<div>
				<input type="text" value="<?php echo isset($config['AffiliateID']["$globalid"]) ? $config['AffiliateID']["$globalid"] : ''; ?>" name="AffiliateID[<?php echo $globalid; ?>]" id="AffiliateID[<?php echo $globalid; ?>]" placeholder="ENTER YOUR AFFILIATE ID FOR <?php echo $flag; ?>">
			</div>
			<div class="WooZoneLite-country-name">
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
	$html[] = '- The link will use IP-based Geolocation to geographically target your visitor to the Amazon store of his/her country (according to their current location). <br />';
	$html[] = '- You don\'t have to specify all affiliate IDs if you are not registered to all programs. <br />';
	$html[] = '- The ASIN is unfortunately not always globally unique. That\'s why you sometimes need to specify several ASINs for different shops. <br />';
	$html[] = '- If you have an English website, it makes most sense to sign up for the US, UK and Canadian programs. <br />';
	$html[] = '</div>';
	$html[] = '</div>';
	
	return implode("\n", $html);
}

function WooZoneLite_clean_log_tables( $istab = '', $is_subtab='' ) {
	global $WooZoneLite, $wpdb;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row attr-clean-log-tables' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label for="clean_log_tables" class="WooZoneLite-form-label">' . __('Clean Log Tables:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['clean_log_tables']) ) {
		$val = $options['clean_log_tables'];
	}
		
	ob_start();
?>
	<div class="WooZoneLite-form-item">
		<select id="clean_log_tables" name="clean_log_tables" style="width:220px; margin-left: 18px;">
			<?php
			foreach (array('clear_all' => 'Clear all logs', 'clear_but_keep_1w' => 'Clear but keep logs from the last week', 'clear_but_keep_1m' => 'Clear but keep logs from the last month') as $kk => $vv){
				echo '<option value="' . ( $kk ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();
	
	// add tables to clean here without prefix
	$tables_2_clean = array('amz_report_log');
	
	$tables_2_clean_info = array();
	foreach ( $tables_2_clean as $table ) {
		$tables_2_clean_info[] =  $wpdb->prefix . $table . '(' . $WooZoneLite->WooZoneLite_show_table_status( $table ) . ')';
	}
	
	$tables_2_clean_info = implode(',', $tables_2_clean_info);
	
	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-cleanlogtables" value="' . ( __('clean Now ', $WooZoneLite->localizationName) ) . '">
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>
	<span class="WooZoneLite-form-note" style="display: inline-block; margin-left: 1.5rem; color: red;">This option will clear the following wordpress tables: ' . $tables_2_clean_info  . '.</span>';

	$html[] = '</div>';

	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		$("body").on("click", "#WooZoneLite-cleanlogtables", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_CleanLogTables',
				'sub_action'	: 'db_clean_log_tables',
				'clean_option'	: $('select#clean_log_tables').val(),			
				'tables'		: '<?php echo implode( ',', $tables_2_clean ); ?>'
			}, function(response) {

				var $box = $('.attr-clean-log-tables'), 
					$res = $box.find('.WooZoneLite-response-options');
				$res.html( response.msg_html ).show();
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function WooZoneLite_attributes_clean_duplicate( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row attr-clean-duplicate' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label for="clean_duplicate_attributes" class="WooZoneLite-form-label">' . __('Clean duplicate attributes:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['clean_duplicate_attributes']) ) {
		$val = $options['clean_duplicate_attributes'];
	}
		
	ob_start();
?>
	<div class="WooZoneLite-form-item">
		<select id="clean_duplicate_attributes" name="clean_duplicate_attributes" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-attributescleanduplicate" value="' . ( __('clean Now ', $WooZoneLite->localizationName) ) . '">
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';

	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		$("body").on("click", "#WooZoneLite-attributescleanduplicate", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_AttributesCleanDuplicate',
				'sub_action'	: 'attr_clean_duplicate'
			}, function(response) {

				var $box = $('.attr-clean-duplicate'), 
					$res = $box.find('.WooZoneLite-response-options');
				$res.html( response.msg_html ).show();
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function WooZoneLite_category_slug_clean_duplicate( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row category-slug-clean-duplicate' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="clean_duplicate_category_slug">' . __('Clean duplicate category slug:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['clean_duplicate_category_slug']) ) {
		$val = $options['clean_duplicate_category_slug'];
	}
		
	ob_start();
?>
	<div class="WooZoneLite-form-item">
		<select id="clean_duplicate_category_slug" name="clean_duplicate_category_slug" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-categoryslugcleanduplicate" value="' . ( __('clean Now ', $WooZoneLite->localizationName) ) . '">
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';

	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		$("body").on("click", "#WooZoneLite-categoryslugcleanduplicate", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_CategorySlugCleanDuplicate',
				'sub_action'	: 'category_slug_clean_duplicate'
			}, function(response) {

				var $box = $('.category-slug-clean-duplicate'), $res = $box.find('.WooZoneLite-response-options');
				$res.html( response.msg_html ).show();
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function WooZoneLite_clean_orphaned_amz_meta( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row clean_orphaned_amz_meta' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="clean_orphaned_amz_meta">' . __('Clean orphaned AMZ meta:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['clean_orphaned_amz_meta']) ) {
		$val = $options['clean_orphaned_amz_meta']; 
	}
		
	ob_start();
?>
	<div class="WooZoneLite-form-item">
		<select id="clean_orphaned_amz_meta" name="clean_orphaned_amz_meta" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-cleanduplicateamzmeta" value="' . ( __('clean Now ', $WooZoneLite->localizationName) ) . '">
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';
	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {

		$("body").on("click", "#WooZoneLite-cleanduplicateamzmeta", function(){
			console.log( $('#AccessKeyID').val() ); 
			//var tokenAnswer = prompt('Please enter security token - The security token is your AccessKeyID');
			//if( tokenAnswer == $('#AccessKeyID').val() ) {
			if (1) {
				var confirm_response = confirm("CAUTION! PERFORMING THIS ACTION WILL DELETE ALL YOUR AMAZON PRODUCT METAS! THIS ACTION IS IRREVERSIBLE! Are you sure you want to clear all amazon product meta?");
				if( confirm_response == true ) {
					$.post(ajaxurl, {
						'action' 		: 'WooZoneLite_clean_orphaned_amz_meta',
						'sub_action'	: 'clean_orphaned_amz_meta'
					}, function(response) {
						
						var $box = $('.clean_orphaned_amz_meta'), $res = $box.find('.WooZoneLite-response-options');
						$res.html( response.msg_html ).show();
						if ( response.status == 'valid' )
							return true;
						return false;
					}, 'json');
				}
			}
			//else {
			//	alert('Security token invalid!');
			//}
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function WooZoneLite_delete_zeropriced_products( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row  delete_zeropriced_products' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="delete_zeropriced_products">' . __('Delete zero priced products:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['delete_zeropriced_products']) ) {
		$val = $options['delete_zeropriced_products']; 
	}
		
	ob_start();
?>
	<div class="WooZoneLite-form-item">
		<select id="delete_zeropriced_products" name="delete_zeropriced_products" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-delete_zeropriced_products" value="' . ( __('delete now! ', $WooZoneLite->localizationName) ) . '">
	<span class="WooZoneLite-form-note" style="display: inline-block; margin-left: 1.5rem;">This action is influenced by "Product : Delete | Move to Trash" option /Plugin SETUP tab</span>
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';
	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		$("body").on("click", "#WooZoneLite-delete_zeropriced_products", function(){
			var confirm_response = confirm("Are you sure you want to delete all zero priced products?");
			if( confirm_response == true ) {

				var loop_max = 10, // number of max steps (10 products will be made per step => total = 10 * 10 = 100 products)
					  loop_step = 0; // current step
				var $box = $('.delete_zeropriced_products'), $res = $box.find('.WooZoneLite-response-options');

				function __doit() {
					loop_step++;
					if ( loop_step > loop_max ) {
						$res.append( 'WORK DONE. If there are posts remained, try again.' ).show();
						return true;
					}
					
					$res.append( 'WORK IN PROGRESS...' ).show();

					$.post(ajaxurl, {
						'action' 		: 'WooZoneLite_delete_zeropriced_products',
						'sub_action'	: 'delete_zeropriced_products'
					}, function(response) {

						$res.html( response.msg_html ).show();

						var remained = parseInt( response.nb_remained );
						if ( remained ) {
							__doit();
						} else {
							$res.append( 'WORK DONE.' ).show();
						}

						//if ( response.status == 'valid' ) {
						//	return true;
						//}
						//return false;
					}, 'json');
				}
				__doit();

			} // end confirm
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function WooZoneLite_clean_orphaned_prod_assets( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row clean_orphaned_prod_assets' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="clean_orphaned_prod_assets">' . __('Clean orphaned WooZoneLite Product Assets:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['clean_orphaned_prod_assets']) ) {
		$val = $options['clean_orphaned_prod_assets']; 
	}
		
	ob_start();
?>
	<div class="WooZoneLite-form-item">
		<select id="clean_orphaned_prod_assets" name="clean_orphaned_prod_assets" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-clean_orphaned_prod_assets" value="' . ( __('clean Now', $WooZoneLite->localizationName) ) . '">
	<span class="WooZoneLite-form-note" style="display: inline-block; margin-left: 1.5rem;">This option will clean orphan product assets from woozonelite tables: wp_amz_assets & wp_amz_products.</span>
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';
	//$html[] = '<span class="WooZoneLite-form-note" style="/* margin-left: 20rem; */">This Affiliate id will be use in API request and if user are not from any of available amazon country.</span>';
	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		$("body").on("click", "#WooZoneLite-clean_orphaned_prod_assets", function(){
			var confirm_response = confirm("Are you sure you want to delete all orphaned amazon products assets?");
			if( confirm_response == true ) {
				$.post(ajaxurl, {
					'action'        : 'WooZoneLite_clean_orphaned_prod_assets',
					'sub_action'    : 'clean_orphaned_prod_assets'
				}, function(response) {
					var $box = $('.clean_orphaned_prod_assets'), $res = $box.find('.WooZoneLite-response-options');
					$res.html( response.msg_html ).show();
					if ( response.status == 'valid' )
						return true;
					return false;
				}, 'json');
			}
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function WooZoneLite_clean_orphaned_prod_assets_wp( $istab = '', $is_subtab='' ) {
	global $WooZoneLite, $wpdb;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row clean_orphaned_prod_assets_wp' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="clean_orphaned_prod_assets_wp">' . __('Clean orphaned Wordpress Product Attachments:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['clean_orphaned_prod_assets_wp']) ) {
		$val = $options['clean_orphaned_prod_assets_wp']; 
	}
		
	ob_start();
?>
	<div class="WooZoneLite-form-item">
		<select id="clean_orphaned_prod_assets_wp" name="clean_orphaned_prod_assets_wp" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-clean_orphaned_prod_assets_wp" value="' . ( __('clean Now', $WooZoneLite->localizationName) ) . '">
	<span class="WooZoneLite-form-note" style="display: inline-block; margin-left: 1.5rem; color: red;">This option will clean orphan product assets from wordpress tables: ' . $wpdb->prefix . 'posts & ' . $wpdb->prefix . 'postmeta.</span>
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';
	//$html[] = '<span class="WooZoneLite-form-note" style="/* margin-left: 20rem; */">This Affiliate id will be use in API request and if user are not from any of available amazon country.</span>';
	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		$("body").on("click", "#WooZoneLite-clean_orphaned_prod_assets_wp", function(){
			var confirm_response = confirm("Are you sure you want to delete all orphaned wordpress products attachments?");
			if( confirm_response == true ) {
				$.post(ajaxurl, {
					'action'        : 'WooZoneLite_clean_orphaned_prod_assets_wp',
					'sub_action'    : 'clean_orphaned_prod_assets_wp'
				}, function(response) {
					var $box = $('.clean_orphaned_prod_assets_wp'), $res = $box.find('.WooZoneLite-response-options');
					$res.html( response.msg_html ).show();
					if ( response.status == 'valid' )
						return true;
					return false;
				}, 'json');
			}
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function WooZoneLite_fix_product_attributes( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row fix-product-attributes' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="fix_product_attributes">' . __('Fix Product Attributes (woocommerce 2.4 update):', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['fix_product_attributes']) ) {
		$val = $options['fix_product_attributes'];
	}
		
	ob_start();
?>
	<div class="WooZoneLite-form-item">
		<select id="fix_product_attributes" name="fix_product_attributes" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-fix_product_attributes" value="' . ( __('fix Now ', $WooZoneLite->localizationName) ) . '">
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';
	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>'

		$("body").on("click", "#WooZoneLite-fix_product_attributes", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_fix_product_attributes',
				'sub_action'	: 'fix_product_attributes'
			}, function(response) {

				var $box = $('.fix-product-attributes'), $res = $box.find('.WooZoneLite-response-options');
				$res.html( response.msg_html ).show();
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function WooZoneLite_fix_node_childrens( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row fix-node-childrens' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="fix_node_childrens">' . __('Clear Search old Node Childrens:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['fix_node_childrens']) ) {
		$val = $options['fix_node_childrens'];
	}
		
	ob_start();
?>
	<div class="WooZoneLite-form-item">
		<select id="fix_node_childrens" name="fix_node_childrens" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-fix_node_childrens" value="' . ( __('Clear Now ', $WooZoneLite->localizationName) ) . '">
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';
	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>'

		$("body").on("click", "#WooZoneLite-fix_node_childrens", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_fix_node_childrens',
				'sub_action'	: 'fix_node_childrens'
			}, function(response) {

				var $box = $('.fix-node-childrens'), $res = $box.find('.WooZoneLite-response-options');
				$res.html( response.msg_html ).show();
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function WooZoneLite_amazon_countries( $istab = '', $is_subtab='', $what='array' ) {
	global $WooZoneLite;
	
	$html         = array();
	$img_base_url = $WooZoneLite->cfg['paths']["plugin_dir_url"] . 'modules/amazon/images/flags/';
	
	$config = $WooZoneLite->settings();

	$theHelper = $WooZoneLite->get_ws_object_new( 'amazon', 'new_helper', array(
		'the_plugin' => $WooZoneLite,
	));
	//:: disabled on 2018-feb
	//require_once( $WooZoneLite->cfg['paths']['plugin_dir_path'] . 'aa-framework/amz.helper.class.php' );
	//if ( class_exists('WooZoneLiteAmazonHelper') ) {
	//	//$theHelper = WooZoneLiteAmazonHelper::getInstance( $WooZoneLite );
	//	$theHelper = new WooZoneLiteAmazonHelper( $WooZoneLite );
	//}
	//:: end disabled on 2018-feb
	$list = is_object($theHelper) ? $theHelper->get_countries( $what ) : array();
	
	if ( in_array($what, array('country', 'main_aff_id')) ) {
		return $list;
	}
	return implode(', ', array_values($list));
}

// WooZoneLite_insane_last_reports Warning: Illegal string offset 'request_amazon' issue
function WooZoneLite_fix_issue_request_amazon( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row fix_issue_request_amazon2' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="fix_issue_request_amazon">' . __('Fix Request Amazon Issue:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['fix_issue_request_amazon']) ) {
		$val = $options['fix_issue_request_amazon'];
	}
		
	ob_start();
?>
		<select id="fix_issue_request_amazon" name="fix_issue_request_amazon" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-fix_issue_request_amazon" value="' . ( __('fix Now ', $WooZoneLite->localizationName) ) . '">
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>'

		$("body").on("click", "#WooZoneLite-fix_issue_request_amazon", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_fix_issues',
				'sub_action'	: 'fix_issue_request_amazon'
			}, function(response) {

				var $box = $('.fix_issue_request_amazon2'), $res = $box.find('.WooZoneLite-response-options');
				$res.html( response.msg_html ).show();
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

// Fix Sync Issue
function WooZoneLite_fix_issue_sync( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();

	$options = $WooZoneLite->settings();

	$html[] = '<div class="WooZoneLite-bug-fix WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row fix_issue_sync-wrapp' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '" style="line-height: 35px;">';

	// products in trash after X tries
	$val_trash = $WooZoneLite->sync_tries_till_trash;
	if ( isset($options['fix_issue_sync'], $options['fix_issue_sync']['trash_tries']) ) {
		$val_trash = $options['fix_issue_sync']['trash_tries'];
	}
	
	$html[] = '<div>';
	$html[] = '<label style="display: inline; float: none;" for="fix_issue_sync-trash_tries">' . __('Put amazon products in trash when syncing after: ', $WooZoneLite->localizationName) . '</label>';

	ob_start();
?>
		<select id="fix_issue_sync-trash_tries" name="fix_issue_sync[trash_tries]" style="width: 120px; margin-left: 18px;">
			<?php
			foreach (array(1 => 'First try', 2 => 'Second try', 3 => 'Third try', 4 => '4th try', 5 => '5th try', -1 => 'Never') as $kk => $vv){
				echo '<option value="' . ( $kk ) . '" ' . ( $val_trash == $kk ? 'selected="selected"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	//$html[] = '<input type="button" class="WooZoneLite-button green" style="width: 160px;" id="fix_issue_sync-save_setting" value="' . ( __('Verify how many', $WooZoneLite->localizationName) ) . '">';
	$html[] = '<span style="margin: 0px; margin-left: 10px; display: block;" class="response_save"></span>';
	$html[] = '</div>';
	
	// restore products with status
	$val_restore = 'publish';
	if ( isset($options['fix_issue_sync'], $options['fix_issue_sync']['restore_status']) ) {
		$val_restore = $options['fix_issue_sync']['restore_status'];
	}
	
	$html[] = '<div>';
	$html[] = '<input type="button" class="WooZoneLite-form-button-small WooZoneLite-form-button-primary" style="vertical-align:middle;line-height:12px;" id="fix_issue_sync-fix_now" value="' . ( __('Restore now', $WooZoneLite->localizationName) ) . '">';
	$html[] = '<label style="display: inline; float: none;" for="fix_issue_sync-restore_status">' . __('trashed amazon products (and variations) | their NEW status: ', $WooZoneLite->localizationName) . '</label>';

	ob_start();
?>
		<select id="fix_issue_sync-restore_status" name="fix_issue_sync[restore_status]" style="width: 120px; margin-left: 18px;">
			<?php
			foreach (array('publish' => 'Publish', 'draft' => 'Draft') as $kk => $vv){
				echo '<option value="' . ( $kk ) . '" ' . ( $val_restore == $kk ? 'selected="selected"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<span style="margin: 0px; margin-left: 10px; display: block;" class="response_fixnow"></span>';
	$html[] = '</div>';

	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>'

		$("body").on("click", "#fix_issue_sync-save_setting", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_fix_issues',
				'sub_action'	: 'sync_tries_trash'
			}, function(response) {

				var $box = $('.fix_issue_sync-wrapp'), $res = $box.find('.response_save');
				$res.html( response.msg_html );
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});

		// restore status
		$("body").on("click", "#fix_issue_sync-fix_now", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_fix_issues',
				'sub_action'	: 'sync_restore_status',
				'what'			: 'verify'
			}, function(response) {

				var $box = $('.fix_issue_sync-wrapp'), $res = $box.find('.response_fixnow');
				$res.html( response.msg_html );
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
		
		$("body").on("click", "#fix_issue_sync-fix_now_cancel", function(){
			var $box = $('.fix_issue_sync-wrapp'), $res = $box.find('.response_fixnow');
			$res.html('');
		});

		$("body").on("click", "#fix_issue_sync-fix_now_doit", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_fix_issues',
				'sub_action'	: 'sync_restore_status',
				'what'			: 'doit',
				'post_status'	: $('#fix_issue_sync-restore_status').val(),
			}, function(response) {

				var $box = $('.fix_issue_sync-wrapp'), $res = $box.find('.response_fixnow');
				$res.html( response.msg_html );
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

// reset products stats
function WooZoneLite_reset_products_stats( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row reset_products_stats2' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="reset_products_stats">' . __('Reset products stats:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['reset_products_stats']) ) {
		$val = $options['reset_products_stats'];
	}
		
	ob_start();
?>
		<select id="reset_products_stats" name="reset_products_stats" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv){
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $vv ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-reset_products_stats" value="' . ( __('reset Now ', $WooZoneLite->localizationName) ) . '">
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>'

		$("body").on("click", "#WooZoneLite-reset_products_stats", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_fix_issues',
				'sub_action'	: 'reset_products_stats'
			}, function(response) {

				var $box = $('.reset_products_stats2'), $res = $box.find('.WooZoneLite-response-options');
				$res.html( response.msg_html ).show();
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

// from version 9.0 options prefix changed from wwcAmzAff to WooZoneLite
function WooZoneLite_options_prefix_change( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row options_prefix_change2' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="options_prefix_change">' . __('Version 9.0 options prefix change:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['options_prefix_change']) ) {
		$val = $options['options_prefix_change'];
	}
		
	ob_start();
?>
		<select id="options_prefix_change" name="options_prefix_change" style="width:240px; margin-left: 18px;">
			<?php
			$arr_sel = array(
				//'default' 		=> 'Default (keep new version 9.0 settings)',
				'use_new'		=> 'Keep new version 9.0 settings',
				'use_old'		=> 'Restore old version prior to 9.0 settings'
			);
			foreach ($arr_sel as $kk => $vv){
				echo '<option value="' . ( $kk ) . '" ' . ( $val == $kk ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-options_prefix_change" value="' . ( __('do it now', $WooZoneLite->localizationName) ) . '">
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>'

		$("body").on("click", "#WooZoneLite-options_prefix_change", function(){

			$.post(ajaxurl, {
				'action' 			: 'WooZoneLite_fix_issues',
				'sub_action'	: 'options_prefix_change',
				'what'			: $('#options_prefix_change').val()
			}, function(response) {

				var $box = $('.options_prefix_change2'), $res = $box.find('.WooZoneLite-response-options');
				$res.html( response.msg_html ).show();
				if ( response.status == 'valid' ) {
					window.location.reload();
					return true;
				}
				return false;
			}, 'json');
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

// from version 9.0 options prefix changed from wwcAmzAff to WooZoneLite
function WooZoneLite_unblock_cron( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row unblock_cron' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="options_prefix_change">' . __('Unblock CRON jobs:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = '';
	if ( isset($options['unblock_cron']) ) {
		$val = $options['unblock_cron'];
	}
?>
	<select id="unblock_cron" name="unblock_cron" style="width:120px; margin-left: 18px;">
			<?php
			foreach (array('yes' => 'YES', 'no' => 'NO') as $kk => $vv) {
				echo '<option value="' . ( $vv ) . '" ' . ( $val == $kk ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			}
			?>
		</select>&nbsp;&nbsp;
	<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-unblock_cron" value="' . ( __('Unblock Now ', $WooZoneLite->localizationName) ) . '">
	<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';

	// view page button
	ob_start();
	?>
	<script>
	(function($) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>'

		$("body").on("click", "#WooZoneLite-unblock_cron", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_fix_issues',
				'sub_action'	: 'unblock_cron'
			}, function(response) {

				var $box = $('.unblock_cron'), $res = $box.find('.WooZoneLite-response-options');
				$res.html( response.msg_html ).show();
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function WooZoneLite_cache_images( $action='default', $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
	
	$req['action'] = $action;

	if ( $req['action'] == 'getStatus' ) {
			return '';
	}

	$html = array();
	
	ob_start();
?>
<div class="WooZoneLite-form-row WooZoneLite-im-cache <?php echo ($istab!='' ? ' '.$istab : ''); ?><?php echo ($is_subtab!='' ? ' '.$is_subtab : '') . ($is_subtab!='' ? ' '.$is_subtab : ''); ?>">

	<label><?php _e('Images Cache', 'psp'); ?></label>
	<div class="WooZoneLite-form-item large">
		<span style="margin:0px 0px 0px 10px" class="response"><?php //echo WooZoneLite_cache_images( 'getStatus' ); ?></span><br />
		<input type="button" class="WooZoneLite-form-button WooZoneLite-form-button-danger" style="width: 160px;" id="WooZoneLite-im-cache-delete" value="<?php _e('Clear cache', 'psp'); ?>">
		<span class="formNote">&nbsp;</span>

	</div>
</div>
<?php
	$htmlRow = ob_get_contents();
	ob_end_clean();
	$html[] = $htmlRow;
	
	// view page button
	ob_start();
?>
	<script>
	(function($) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>';
		
		$(document).ready(function() {
			get_status();
		});

		$("body").on("click", "#WooZoneLite-im-cache-delete", function(){
			cache_delete();
		});
		
		function get_status() {
			$.post(ajaxurl, {
				'action'        : 'WooZoneLite_images_cache',
				'sub_action'    : 'getStatus'
			}, function(response) {

				var $box = $('.WooZoneLite-im-cache'), $res = $box.find('.response');
				$res.html( response.msg_html );
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		};
		
		function cache_delete() {
			$.post(ajaxurl, {
				'action'        : 'WooZoneLite_images_cache',
				'sub_action'    : 'cache_delete'
			}, function(response) {

				var $box = $('.WooZoneLite-im-cache'), $res = $box.find('.response');
				$res.html( response.msg_html );
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		}
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;

	return implode( "\n", $html );
}

// reset products stats
function WooZoneLite_reset_sync_stats( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();
	
	$html[] = '<div class="WooZoneLite-bug-fix panel-body WooZoneLite-panel-body WooZoneLite-form-row reset_sync_stats2' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = '<label class="WooZoneLite-form-label" for="reset_sync_stats">' . __('Reset products SYNC stats:', $WooZoneLite->localizationName) . '</label>';

	$options = $WooZoneLite->settings();
	$val = 'yes';
	if ( isset($options['reset_sync_stats']) ) {
		$val = $options['reset_sync_stats'];
	}
		
	ob_start();
?>
		<select id="reset_sync_stats" name="reset_sync_stats" style="width: 240px; margin-left: 18px;">
			<?php
			$optionsList = array(
				'yes_all' 	=> 'YES - complete sync reset',
				'yes' 		=> 'YES - only reset last sync date',
				'no' 		=> 'NO'
			);
			foreach ($optionsList as $kk => $vv){
				echo '<option value="' . ( $kk ) . '" ' . ( $val == $kk ? 'selected="true"' : '' ) . '>' . ( $vv ) . '</option>';
			} 
			?>
		</select>&nbsp;&nbsp;
<?php
	$html[] = ob_get_contents();
	ob_end_clean();

	$html[] = '<input type="button" class="' . ( WooZoneLite()->alias ) . '-form-button-small ' . ( WooZoneLite()->alias ) . '-form-button-primary" id="WooZoneLite-reset_sync_stats" value="' . ( __('reset Now ', $WooZoneLite->localizationName) ) . '">';
	$html[] = '<span class="WooZoneLite-form-note WooZoneLite-reset-sync-help" style="display: block;"><ul><li><span>YES - complete sync reset</span> : reset all sync meta info for your amazon products</li><li><span>YES - only reset last sync date</span> : reset only the last sync date meta info for your products</li><li><span>NO</span> : don\'t reset sync for products</li></ul></span>';
	$html[] = '<div style="width: 100%; display: none; margin-top: 10px; " class="WooZoneLite-response-options  WooZoneLite-callout WooZoneLite-callout-info"></div>';

	$html[] = '</div>';

	// view page button
	ob_start();
?>
	<script>
	(function($) {
		var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>'

		$("body").on("click", "#WooZoneLite-reset_sync_stats", function(){

			$.post(ajaxurl, {
				'action' 		: 'WooZoneLite_fix_issues',
				'sub_action'	: 'reset_sync_stats',
				'what'			: $('#reset_sync_stats').val()
			}, function(response) {

				var $box = $('.reset_sync_stats2'), $res = $box.find('.WooZoneLite-response-options');
				$res.html( response.msg_html ).show();
				if ( response.status == 'valid' )
					return true;
				return false;
			}, 'json');
		});
	})(jQuery);
	</script>
<?php
	$__js = ob_get_contents();
	ob_end_clean();
	$html[] = $__js;
  
	return implode( "\n", $html );
}

function WooZoneLite_optfunc_badges_box( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();

	$options = $WooZoneLite->settings();

	$html[] = '<div class="wzadmin-badges panel-body WooZoneLite-panel-body WooZoneLite-form-row ' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = 	'<label class="WooZoneLite-form-label">' . __('Badges / Flags', $WooZoneLite->localizationName) . '</label>';

	$html[] = 	'<div>';

	//var_dump('<pre>', $options , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
	$opt_yesno = array(
		'yes' 	=> 'YES',
		'no' 	=> 'NO',
	);
	$opt_box_position = array(
		'top_left' 		=> 'top left',
		'top_right' 	=> 'top right',
		'bottom_left' 	=> 'bottom left',
		'bottom_right' 	=> 'bottom right',
	);
	$opt_badges_activated = array(
		'new' 			=> 'New',
		'onsale' 		=> 'On Sale',
		'freeshipping' 	=> 'Free Shipping',
		'amazonprime' 	=> 'Amazon Prime',
	);
	$opt_badges_where = array(
		'product_page' 			=> 'product page',
		'sidebar' 				=> 'sidebar',
		'minicart' 				=> 'minicart',
		'box_related_products' 	=> 'box related products',
		'box_cross_sell' 		=> 'box cross sell',
	);


	$frontend_hide_onsale_default_badge = isset($options['frontend_hide_onsale_default_badge'])
		? $options['frontend_hide_onsale_default_badge'] : 'no';

	$frontend_show_free_shipping = isset($options['frontend_show_free_shipping'])
		? $options['frontend_show_free_shipping'] : 'yes';

	$badges_box_position = isset($options['badges_box_position'])
		? $options['badges_box_position'] : 'top_left';

	$badges_box_offset_vertical = isset($options['badges_box_offset_vertical'])
		? $options['badges_box_offset_vertical'] : '';

	$badges_box_offset_horizontal = isset($options['badges_box_offset_horizontal'])
		? $options['badges_box_offset_horizontal'] : '';

	$badges_activated = isset($options['badges_activated'])
		? (array) $options['badges_activated'] : array();
	$badges_activated_available = array_diff( array_keys($opt_badges_activated), $badges_activated );
	//var_dump('<pre>',$badges_activated, $badges_activated_available ,'</pre>');

	$badges_where = isset($options['badges_where'])
		? (array) $options['badges_where'] : array();
	$badges_where_available = array_diff( array_keys($opt_badges_where), $badges_where );
	//var_dump('<pre>',$badges_where, $badges_where_available ,'</pre>');
	//echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

	ob_start();
?>

	<!-- Hide Woocommerce "On sale" badge -->
	<div class="wzadmin-badges-item">
		<div class="wzadmin-badges-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Choose Yes, if you want to hide the default Woocommerce \'On sale- badge\'', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Hide Woocommerce Default "On sale" badge', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-badges-item-property">
			<select id="frontend_hide_onsale_default_badge" name="frontend_hide_onsale_default_badge" class="small">
				<?php
				foreach ( $opt_yesno as $key => $val ) {
					$is_selected = $key == $frontend_hide_onsale_default_badge ? ' selected="true"' : '';
					echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
				}
				?>
			</select>
		</div>
	</div>

	<!-- Show "Free Shipping" text beside product price -->
	<div class="wzadmin-badges-item">
		<div class="wzadmin-badges-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Choose Yes, if you want to show \'Free Shipping\' text on frontend beside the product price on product details page (you can choose No for this, and only show the \'Free Shipping\' badge)', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Show Default "Free Shipping" text beside product price', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-badges-item-property">
			<select id="frontend_show_free_shipping" name="frontend_show_free_shipping" class="small">
				<?php
				foreach ( $opt_yesno as $key => $val ) {
					$is_selected = $key == $frontend_show_free_shipping ? ' selected="true"' : '';
					echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
				}
				?>
			</select>
		</div>
	</div>

	<!-- Badges Box Position -->
	<div class="wzadmin-badges-item">
		<div class="wzadmin-badges-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Badges Box Position', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Badges Box Position', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-badges-item-property">
			<select id="badges_box_position" name="badges_box_position" class="small">
				<?php
				foreach ( $opt_box_position as $key => $val ) {
					$is_selected = $key == $badges_box_position ? ' selected="true"' : '';
					echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
				}
				?>
			</select>
		</div>
	</div>

	<!-- Badges Box Offset vertical (px) -->
	<div class="wzadmin-badges-item">
		<div class="wzadmin-badges-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Badges Box Offset vertical (in pixels)', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Badges Box Offset vertical (px)', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-badges-item-property">
			<input id="badges_box_offset_vertical" name="badges_box_offset_vertical" type="text" value="<?php echo $badges_box_offset_vertical; ?>" placeholder="enter the value in pixels (ex.: 15)" class="">
		</div>
	</div>

	<!-- Badges Box Offset horizontal (px) -->
	<div class="wzadmin-badges-item">
		<div class="wzadmin-badges-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Badges Box Offset horizontal (in pixels)', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Badges Box Offset horizontal (px)', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-badges-item-property">
			<input id="badges_box_offset_horizontal" name="badges_box_offset_horizontal" type="text" value="<?php echo $badges_box_offset_horizontal; ?>" placeholder="enter the value in pixels (ex.: 15)" class="">
		</div>
	</div>

	<!-- Activated Badges -->
	<div class="wzadmin-badges-item wzadmin-badges-badges_activated">
		<div class="wzadmin-badges-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Activated Badges', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Active Badges', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-badges-item-property">

			<div class="WooZoneLite-multiselect-half WooZoneLite-multiselect-available" style="margin-right: 2%;">
				<h5>All badges list</h5>
				<select multiple="multiple" size="8" name="badges_activated-available[]" id="badges_activated-available" class="multisel_l2r_available">
				<?php
				foreach ( $opt_badges_activated as $key => $val ) {
					if ( in_array($key, $badges_activated_available) ) {
						$is_selected = ' selected="true"';
						echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
					}
				}
				?>
				</select>
			</div>
			<div class="WooZoneLite-multiselect-half WooZoneLite-multiselect-selected">
				<h5>Selected Active Badges</h5>
				<select multiple="multiple" size="8" name="badges_activated[]" id="badges_activated" class="multisel_l2r_selected">
				<?php
				foreach ( $opt_badges_activated as $key => $val ) {
					if ( in_array($key, $badges_activated) ) {
						$is_selected = ' selected="true"';
						echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
					}
				}
				?>
				</select>
			</div>
			<div style="clear:both"></div>
			<div class="multisel_l2r_btn" style="">
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moveright" type="button" value="Move Right" class="moveright WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moverightall" type="button" value="Move Right All" class="moverightall WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moveleft" type="button" value="Move Left" class="moveleft WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moveleftall" type="button" value="Move Left All" class="moveleftall WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
			</div>

		</div>
	</div>

<?php /*
	<!-- Badges Where -->
	<div class="wzadmin-badges-item wzadmin-badges-badges_where">
		<div class="wzadmin-badges-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Badges Where', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Badges Where', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-badges-item-property">

			<div class="WooZoneLite-multiselect-half WooZoneLite-multiselect-available" style="margin-right: 2%;">
				<h5>All badges where list</h5>
				<select multiple="multiple" size="8" name="badges_where-available[]" id="badges_where-available" class="multisel_l2r_available">
				<?php
				foreach ( $opt_badges_where as $key => $val ) {
					if ( in_array($key, $badges_where_available) ) {
						$is_selected = ' selected="true"';
						echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
					}
				}
				?>
				</select>
			</div>
			<div class="WooZoneLite-multiselect-half WooZoneLite-multiselect-selected">
				<h5>Your chosen badges where from list</h5>
				<select multiple="multiple" size="8" name="badges_where[]" id="badges_where" class="multisel_l2r_selected">
				<?php
				foreach ( $opt_badges_where as $key => $val ) {
					if ( in_array($key, $badges_where) ) {
						$is_selected = ' selected="true"';
						echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
					}
				}
				?>
				</select>
			</div>
			<div style="clear:both"></div>
			<div class="multisel_l2r_btn" style="">
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moveright" type="button" value="Move Right" class="moveright WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moverightall" type="button" value="Move Right All" class="moverightall WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moveleft" type="button" value="Move Left" class="moveleft WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moveleftall" type="button" value="Move Left All" class="moveleftall WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
			</div>

		</div>
	</div>
*/ ?>

<?php
	$html[] = ob_get_clean();

	$html[] = 	'</div>';

	$html[] = '</div>';

	return implode( "\n", $html );
}

function WooZoneLite_optfunc_product_buy_box( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;
   
	$html = array();

	$options = $WooZoneLite->settings();

	$html[] = '<div class="wzadmin-productbuy panel-body WooZoneLite-panel-body WooZoneLite-form-row ' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = 	'<label class="WooZoneLite-form-label">' . __('Product Buy Button', $WooZoneLite->localizationName) . '</label>';

	//var_dump('<pre>', $options , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
	$opt_yesno = array(
		'yes' 	=> 'YES',
		'no' 	=> 'NO',
	);
	$opt_openin = array(
		'_self' => 'Same tab',
		'_blank' => 'New tab'
	);

	$product_buy_text = array();
	$product_buy_button_open_in = array();
	$product_buy_custom_classes = array();
	foreach ( array('amazon', 'ebay') as $provider ) {

		$sufix = 'amazon' === $provider ? '' : '_' . $provider;

		$product_buy_text["$provider"] = array(
			'id' 	=> "product_buy_text{$sufix}",
			'val' 	=> isset($options["product_buy_text{$sufix}"]) ? $options["product_buy_text{$sufix}"] : '',
		);

		$product_buy_button_open_in["$provider"] = array(
			'id' 	=> "product_buy_button_open_in{$sufix}",
			'val' 	=> isset($options["product_buy_button_open_in{$sufix}"]) ? $options["product_buy_button_open_in{$sufix}"] : '_self',
		);

		$product_buy_custom_classes["$provider"] = array(
			'id' 	=> "product_buy_custom_classes{$sufix}",
			'val' 	=> isset($options["product_buy_custom_classes{$sufix}"]) ? $options["product_buy_custom_classes{$sufix}"] : '',
		);
	}

	ob_start();
?>

<div class="wzadmin-productbuy-wrapper">
<?php
foreach ( array('amazon', 'ebay') as $provider ) {

?>

	<div class="wzadmin-productbuy-header"><?php echo ucfirst($provider); ?></div>

	<!-- product_buy_text -->
	<div class="wzadmin-productbuy-item">
		<div class="wzadmin-productbuy-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('(global) This text will be shown on the button linking to the external product. (global) = all external products; external products = those with \'On-site Cart\' option value set to \'No\'', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Custom Text', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-productbuy-item-property">
			<input id="<?php echo $product_buy_text["$provider"]['id']; ?>" name="<?php echo $product_buy_text["$provider"]['id']; ?>" type="text" value="<?php echo $product_buy_text["$provider"]['val']; ?>" placeholder="enter the custom text" class="">
		</div>
	</div>

	<!-- product_buy_custom_classes -->
	<div class="wzadmin-productbuy-item">
		<div class="wzadmin-productbuy-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('This option allows you to add custom classes to your Buy Now button. Normally this is used on custom themes where the Buy Now button layout displays different that it should. Add classes using spaces: Ex: button_class button_class_two', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Custom CSS Classes', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-productbuy-item-property">
			<input id="<?php echo $product_buy_custom_classes["$provider"]['id']; ?>" name="<?php echo $product_buy_custom_classes["$provider"]['id']; ?>" type="text" value="<?php echo $product_buy_custom_classes["$provider"]['val']; ?>" placeholder="enter the custom css classes" class="">
		</div>
	</div>

	<!-- product_buy_button_open_in -->
	<div class="wzadmin-productbuy-item">
		<div class="wzadmin-productbuy-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('This option will allow you to setup how the product buy button will work. You can choose between opening in the same tab or in a new tab.', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Open In', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-productbuy-item-property">
			<select id="<?php echo $product_buy_button_open_in["$provider"]['id']; ?>" name="<?php echo $product_buy_button_open_in["$provider"]['id']; ?>" class="medium">
				<?php
				foreach ( $opt_openin as $key => $val ) {
					$is_selected = $key == $product_buy_button_open_in["$provider"]['val'] ? ' selected="true"' : '';
					echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
				}
				?>
			</select>
		</div>
	</div>

<?php
} // end foreach
?>
</div>

<?php
	$html[] = ob_get_clean();

	$html[] = '</div>';

	return implode( "\n", $html );
}

function WooZoneLite_optfunc_product_price_disclaimer( $istab = '', $is_subtab='' ) {
	global $WooZoneLite;

	$html = array();

	$options = $WooZoneLite->settings();

	$html[] = '<div class="wzadmin-pricedisclaimer panel-body WooZoneLite-panel-body WooZoneLite-form-row ' . ($istab!='' ? ' '.$istab : '') . ($is_subtab!='' ? ' '.$is_subtab : '') . '">';

	$html[] = 	'<label class="WooZoneLite-form-label">' . __('Product Price Disclaimer', $WooZoneLite->localizationName) . '</label>';

	//var_dump('<pre>', $options , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
	$opt_yesno = array(
		'yes' 	=> 'YES',
		'no' 	=> 'NO',
	);

	$opt_asof_font_size = WooZoneLite_asof_font_size();

	$opt_provider_activated = array(
		'amazon' 	=> 'Amazon',
		'ebay' 		=> 'Ebay',
	);
	
	$opt_date_formats = array(
		'd/m/Y H:i' 	=> 'd/m/Y H:i',
		'm/d/Y H:i' 	=> 'm/d/Y H:i',
	);
	
	// pptos = product price tos
	$pptos_activate = array();
	$pptos_asof_font_size = array();
	$pptos_tpl = array();
	//foreach ( array('amazon') as $provider ) {
	//	$sufix = 'amazon' === $provider ? '' : '_' . $provider;
	$provider = 'amazon';
	$sufix = '';

		$pptos_activate["$provider"] = array(
			'id' 	=> "pptos_activate{$sufix}",
			'val' 	=> isset($options["pptos_activate{$sufix}"]) ? $options["pptos_activate{$sufix}"] : 'yes',
		);

		$pptos_asof_font_size["$provider"] = array(
			'id' 	=> "asof_font_size{$sufix}",
			'val' 	=> isset($options["asof_font_size{$sufix}"]) ? $options["asof_font_size{$sufix}"] : '0.6',
		);
		
		$pptos_asof_date_format["$provider"] = array(
			'id' 	=> "asof_date_format{$sufix}",
			'val' 	=> isset($options["asof_date_format{$sufix}"]) ? $options["asof_date_format{$sufix}"] : 'd/m/Y H:i',
		);

		$pptos_tpl["$provider"] = array(
			'name' 	=> "pptos_tpl{$sufix}",
			'id' 	=> array( "pptos_tpl_v1{$sufix}", "pptos_tpl_v2{$sufix}" ),
			'val' 	=> isset($options["pptos_tpl{$sufix}"]) ? $options["pptos_tpl{$sufix}"] : 'v1',
		);

		$provider_activated = isset($options['pptos_provider_activated'])
			? (array) $options['pptos_provider_activated'] : array( 'amazon' );
		$provider_activated_available = array_diff( array_keys($opt_provider_activated), $provider_activated );
		//var_dump('<pre>',$provider_activated, $provider_activated_available ,'</pre>');
	//}

	ob_start();
?>

<div class="wzadmin-pricedisclaimer-wrapper">
<?php
//foreach ( array('amazon', 'ebay') as $provider ) {

?>

	<?php /*<div class="wzadmin-pricedisclaimer-header"><?php echo ucfirst($provider); ?></div>*/ ?>

	<!-- pptos_activate -->
	<div class="wzadmin-pricedisclaimer-item">
		<div class="wzadmin-pricedisclaimer-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Choose Yes, if you want to activate Product Price Disclaimer acording to New Amazon TOS', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Activate Product Price Disclaimer', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-pricedisclaimer-item-property">
			<select id="<?php echo $pptos_activate["$provider"]['id']; ?>" name="<?php echo $pptos_activate["$provider"]['id']; ?>" class="medium">
				<?php
				foreach ( $opt_yesno as $key => $val ) {
					$is_selected = $key == $pptos_activate["$provider"]['val'] ? ' selected="true"' : '';
					echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
				}
				?>
			</select>
		</div>
	</div>

	<!-- pptos_asof_font_size -->
	<div class="wzadmin-pricedisclaimer-item">
		<div class="wzadmin-pricedisclaimer-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Choose the text font size (in em)', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Text font size', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-pricedisclaimer-item-property">
			<select id="<?php echo $pptos_asof_font_size["$provider"]['id']; ?>" name="<?php echo $pptos_asof_font_size["$provider"]['id']; ?>" class="medium">
				<?php
				foreach ( $opt_asof_font_size as $key => $val ) {
					$is_selected = $key == $pptos_asof_font_size["$provider"]['val'] ? ' selected="true"' : '';
					echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
				}
				?>
			</select>
		</div>
	</div>

	<!-- Activated Badges -->
	<div class="wzadmin-pricedisclaimer-item wzadmin-pricedisclaimer-provider_activated">
		<div class="wzadmin-pricedisclaimer-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Choose on which provider you want the product price disclaimer (on product details page) to be displayed', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Active Providers', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-pricedisclaimer-item-property">

			<div class="WooZoneLite-multiselect-half WooZoneLite-multiselect-available" style="margin-right: 2%;">
				<h5>All providers list</h5>
				<select multiple="multiple" size="8" name="pptos_provider_activated-available[]" id="pptos_provider_activated-available" class="multisel_l2r_available">
				<?php
				foreach ( $opt_provider_activated as $key => $val ) {
					if ( in_array($key, $provider_activated_available) ) {
						$is_selected = ' selected="true"';
						echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
					}
				}
				?>
				</select>
			</div>
			<div class="WooZoneLite-multiselect-half WooZoneLite-multiselect-selected">
				<h5>Selected Active Providers</h5>
				<select multiple="multiple" size="8" name="pptos_provider_activated[]" id="pptos_provider_activated" class="multisel_l2r_selected">
				<?php
				foreach ( $opt_provider_activated as $key => $val ) {
					if ( in_array($key, $provider_activated) ) {
						$is_selected = ' selected="true"';
						echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
					}
				}
				?>
				</select>
			</div>
			<div style="clear:both"></div>
			<div class="multisel_l2r_btn" style="">
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moveright" type="button" value="Move Right" class="moveright WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moverightall" type="button" value="Move Right All" class="moverightall WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moveleft" type="button" value="Move Left" class="moveleft WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
				<span style="display: inline-block; width: 24.1%; text-align: center;">
					<input id="standard_content-moveleftall" type="button" value="Move Left All" class="moveleftall WooZoneLite-button gray WooZoneLite-form-button-small WooZoneLite-form-button-info">
				</span>
			</div>

		</div>
	</div>

	<!-- pptos_tpl -->
	<div class="wzadmin-pricedisclaimer-item">
		<div class="wzadmin-pricedisclaimer-item-title">
			<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Display price template. New Amazon TOS: (i) You will include a date/time stamp adjacent to your display of pricing or availability information on your application if you obtain Product Advertising Content from Data Feeds, or if you call PA API or refresh the Product Advertising Content displayed on your application less frequently than hourly. However, during the same day on which you requested and refreshed the pricing and availability information displayed on your application, you may omit the date portion of the stamp. Details and More info when clicked will show a pop-up box with the following text, according to amazon rules: Product prices and availability are accurate as of the date/time indicated and are subject to change. Any price and availability information displayed on [relevant Amazon Site(s), as applicable] at the time of purchase will apply to the purchase of this product.', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			<span><?php _e('Choose display price template', 'woozonelite'); ?></span>
		</div>
		<div class="wzadmin-pricedisclaimer-item-property">
			<label for="<?php echo $pptos_tpl["$provider"]['id'][0]; ?>">
				<input id="<?php echo $pptos_tpl["$provider"]['id'][0]; ?>" name="<?php echo $pptos_tpl["$provider"]['name']; ?>" type="radio" value="v1" class="" <?php echo 'v1' === $pptos_tpl["$provider"]['val'] ? 'checked="checked"' : ''; ?>>
				<span>Amazon.com Price: $ 32.77 (as of 01/07/2008 14:11 PST- Details)</span>
			</label>
			<label for="<?php echo $pptos_tpl["$provider"]['id'][1]; ?>">
				<input id="<?php echo $pptos_tpl["$provider"]['id'][1]; ?>" name="<?php echo $pptos_tpl["$provider"]['name']; ?>" type="radio" value="v2" class="" <?php echo 'v2' === $pptos_tpl["$provider"]['val'] ? 'checked="checked"' : ''; ?>>
				<span>Amazon.com Price: $ 32.77 (as of 14:11 EST- More info)</span>
				<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Even if you choose this template model, it will be displayed only if it\'s the same day on which you requested and refreshed the pricing and availability information displayed on your application, otherwise the above template model will be used.', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
			</label>
			
			<div class="wzadmin-pricedisclaimer-item-title">
				<a href="#" class="WooZoneLite-simplemodal-trigger" title="<?php _e('Choose the date display format', 'woozonelite'); ?>"><i class="fa fa-info-circle"></i></a>
				<span><?php _e('Date format - <b>available only for 1st display price template option</b>', 'woozonelite'); ?></span>
			</div>
			<div class="wzadmin-pricedisclaimer-item-property">
				<select id="<?php echo $pptos_asof_date_format["$provider"]['id']; ?>" name="<?php echo $pptos_asof_date_format["$provider"]['id']; ?>" class="medium">
					<?php
					foreach ( $opt_date_formats as $key => $val ) {
						$is_selected = $key == $pptos_asof_date_format["$provider"]['val'] ? ' selected="true"' : '';
						echo '<option value="' . $key . '"' . $is_selected . '>' . $val . '</option>';
					}
					?>
				</select>
		</div>
			
			<div>Ebay products will have this text after price (date replaced with product last sync date): <em>(as of April 16, 2019, 18:24)</em></div>
		</div>
	</div>

<?php
//} // end foreach
?>
</div>

<?php
	$html[] = ob_get_clean();

	$html[] = '</div>';

	return implode( "\n", $html );
}