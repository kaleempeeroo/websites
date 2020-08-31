<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $WooZoneLite;

do_action( 'woozonelite_template_badges_before' );

$__ = compact( 'product_is_new', 'product_is_onsale', 'product_is_amazonprime', 'product_is_freeshipping' );
//var_dump('<pre>', $product_id, $__ , '</pre>');

?>

<div class="wzfront-badges wzfront-badges-big <?php echo $box_css_class; ?>" style="<?php echo $box_style; ?>" data-product_id="<?php echo $product_id; ?>">

	<ul>

		<?php if ( isset($product_is_new) && $product_is_new ) { ?>
			<li class="wzfront-badges-badge-new">
				<div>
					<span class="badge-text"><?php _e('New', 'woozonelite'); ?></span>
				</div>
			</li>
		<?php } ?>

		<?php if ( isset($product_is_onsale) && $product_is_onsale ) { ?>
			<li class="wzfront-badges-badge-onsale">
				<div>
					<span class="badge-text"><?php _e('On Sale', 'woozonelite'); ?></span>
				</div>
			</li>
		<?php } ?>

		<?php if ( isset($product_is_amazonprime) && $product_is_amazonprime ) { ?>
			<li class="wzfront-badges-badge-amazonprime">
				<div>
					<span class="badge-text"><?php _e('Amazon Prime', 'woozonelite'); ?></span>
					<img src="<?php echo $WooZoneLite->cfg['paths']['plugin_dir_url']; ?>lib/frontend/badges/badge-amazon-prime.png">
				</div>
			</li>
		<?php } ?>

		<?php if ( isset($product_is_freeshipping) && $product_is_freeshipping) { ?>
			<li class="wzfront-badges-badge-freeshipping">
				<div>
					<span class="badge-text"><?php _e('Free Shipping', 'woozonelite'); ?></span>
					<a onclick="return WooZoneLite.popup(this.href,'AmazonHelp','width=550,height=550,resizable=1,scrollbars=1,toolbar=0,status=0');" target="AmazonHelp" href="<?php echo isset($freeshipping_link) ? $freeshipping_link : '#'; ?>">
						<?php _e('Free Shipping', 'woozonelite'); ?>
					</a>
				</div>
			</li>
		<?php } ?>

	</ul>

</div>

<?php do_action( 'woozonelite_template_badges_after' ); ?>