<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $WooZoneLite;

do_action( 'woozonelite_template_country_check_box_big_before' );

$__ = compact( 'with_wrapper', 'box_position', 'product_id', 'asin', 'product_country', 'available_countries', 'aff_ids', 'p_type', 'countryflags_aslink' );
//var_dump('<pre>', $__ , '</pre>');

?>

<?php if ($with_wrapper) { ?>
<ul class="WooZoneLite-country-check" data-prodid="<?php echo $product_id; ?>" data-asin="<?php echo $asin; ?>" data-prodcountry="<?php echo $product_country; ?>" data-boxpos="<?php echo $box_position; ?>" <?php echo !empty($box_position) ? 'style="display: none;"' : ''; ?>>
<?php } ?>

	<div class="WooZoneLite-country-cached" style="display: none;"><?php echo json_encode( $available_countries ); ?></div>
	<div class="WooZoneLite-country-affid" style="display: none;"><?php echo json_encode( $aff_ids ); ?></div>
	<div class="WooZoneLite-country-loader">
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
			<div class="WooZoneLite-country-loader-text"></div>
		</div>
	</div>
	<div class="WooZoneLite-country-loader bottom">
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
			<div class="WooZoneLite-country-loader-text"></div>
		</div>
	</div>
	<div style="display: none;" id="WooZoneLite-cc-template">
		<li>
			<?php if ( 'external' != $p_type ) { ?>
			<span class="WooZoneLite-cc_checkbox">
				<input type="radio" name="WooZoneLite-cc-choose[<?php echo $asin; ?>]" />
			</span>
			<?php } ?>
			<span class="WooZoneLite-cc_domain<?php echo $countryflags_aslink ? ' WooZoneLite-countryflag-aslink' : ''; ?>">
				<?php if ( $countryflags_aslink ) { ?>
				<a href="#" target="_blank"></a>
				<?php } ?>
			</span>
			<span class="WooZoneLite-cc_name"><a href="#" target="_blank"></a></span>
			-
			<span class="WooZoneLite-cc-status">
				<span class="WooZoneLite-cc-loader">
					<span class="WooZoneLite-cc-bounce1"></span>
					<span class="WooZoneLite-cc-bounce2"></span>
					<span class="WooZoneLite-cc-bounce3"></span>
				</span>
			</span>
		</li>
	</div>

<?php if ($with_wrapper) { ?>
</ul>
<?php } ?>

<?php do_action( 'woozonelite_template_country_check_box_big_after' ); ?>