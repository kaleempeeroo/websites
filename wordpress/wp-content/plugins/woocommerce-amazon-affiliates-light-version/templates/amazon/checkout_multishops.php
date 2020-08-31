<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $WooZoneLite;

do_action( 'woozonelite_template_amazon_checkout_multishops_before' );

$__ = compact( 'where', 'shops', 'totals', 'order_id', 'order_info' );
//var_dump('<pre>', $__ , '</pre>');

$price_args = array(); //array( 'currency' => $order->get_currency() );

?>

<div class="WooZoneLite-cart-checkout" style="<?php echo 'cart' == $where ? '' : 'display: none;'; ?>">

	<ul class="WooZoneLite-cart-shops">
	<?php
	foreach ($shops as $key => $value) {
		if ( empty($value) ) continue 1;

		//$country_name = array_shift(array_slice($array, 0, 1)); // get first element from array if a array "copy" is needed
		$domain = $value['domain'];
		$affID = $value['affID'];
		$country_name = $value['name'];

		$products = $value['products'];
		$nb_products = count($products);
		
		$prods_available = array();
		foreach ($products as $pkey => $pvalue) {
			if ( $pvalue['countryinfo']['available'] == 1 ) $prods_available[] = $pkey;
		}
		$nb_available = count($prods_available);

		$price_html = '';
		if ( is_array($totals) && isset($totals['bycountry'], $totals['bycountry']["$domain"]) ) {

			if ( is_array($totals['bycountry']["$domain"]) && isset($totals['bycountry']["$domain"]['price']) ) {
				$price_html = $WooZoneLite->get_price_html_profit( $totals['bycountry']["$domain"], array(
					'with_wrapper' 		=> true,
					'show_profit' 		=> isset($order_info['has_dptax']) && $order_info['has_dptax'] ? true : false,
					'text_title' 		=> __( 'Total', 'WooZoneLite' ),
					'text_price' 		=> __('total price for this amazon store (with the dropship tax applied)', $this->localizationName),
					'text_price_diff' 	=> __('your profit for this amazon store', $this->localizationName),
				));
			}
		}
	?>

		<li data-domain="<?php echo $domain; ?>">

			<span class="WooZoneLite-cc_domain <?php echo str_replace('.', '-', $domain); ?>"></span>

			<span class="WooZoneLite-cc_name"><?php echo $country_name; ?></span>

			<span class="WooZoneLite-cc_count">
				<?php
					if ( 'cart' == $where ) {
						echo sprintf( _n('(%s available from %s product)', '(%s available from %s products)', $nb_available, $nb_products, $this->localizationName),  $nb_available, $nb_products );
					}
					else {
						echo sprintf( _n('(%s product)', '(%s products)', $nb_products, $this->localizationName), $nb_products );	
					}
				?>
			</span>

			<span class="WooZoneLite-cc_checkout">

				<?php
					if ( 'cart' == $where ) {
						echo '<form target="_blank" method="GET" action="//www.amazon.' . $domain . '/gp/aws/cart/add.html">';
					}
					else {
						echo '<div class="WooZoneLite-cart-fakeform" data-formaction="//www.amazon.' . $domain . '/gp/aws/cart/add.html">';
					}
				?>

					<input type="hidden" name="AssociateTag" value="<?php echo $affID; ?>"/>
					<?php /*<input type="hidden" name="SubscriptionId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>*/ ?>
					<input type="hidden" name="AWSAccessKeyId" value="<?php echo $this->amz_settings['AccessKeyID'];?>"/>
					<?php 
					$cc = 1; 
					foreach ($products as $pkey => $pvalue) {
					?>      
						<input type="hidden" name="ASIN.<?php echo $cc;?>" value="<?php echo $pvalue['asin'];?>"/>
						<input type="hidden" name="Quantity.<?php echo $cc;?>" value="<?php echo $pvalue['quantity'];?>"/>
					<?php
						$cc++;
					} // end foreach
					$redirect_in = isset($this->amz_settings['redirect_time']) && (int) $this->amz_settings['redirect_time'] > 0 ? ( (int) $this->amz_settings['redirect_time'] * 1000 ) : 1;
					?>

					<input type="<?php echo 'cart' == $where ? 'submit' : 'button'; ?>" value="<?php _e('Order this product on Amazon', $this->localizationName); ?>" class="WooZoneLite-button proceed">
					<?php
						if ( 'cart' == $where ) {
					?>
					<input type="button" value="<?php _e('Cancel', $this->localizationName); ?>" class="WooZoneLite-button cancel">
					<?php
						}
					?>

				<?php
					if ( 'cart' == $where ) {
						echo '</form>';
					}
					else {
						echo '</div>';
					}
				?>

			</span>

			<?php
				if ( 'order' == $where ) {
			?>
			<div class="WooZoneLite-cc_order_totals">
				<?php echo $price_html; ?>
			</div>
			<?php
				}
			?>

			<span class="WooZoneLite-cc_status"></span>

		</li>

	<?php
	} // end foreach
	?>
	</ul>

	<?php
		$price_html = '';
		if ( is_array($totals) && isset($totals['gtotal']) ) {

			if ( isset($totals['gtotal']['price']) ) {
				$price_html = $WooZoneLite->get_price_html_profit( $totals['gtotal'], array(
					'with_wrapper' 		=> true,
					'show_profit' 		=> isset($order_info['has_dptax']) && $order_info['has_dptax'] ? true : false,
					'text_title' 		=> __( 'Global Total', 'WooZoneLite' ),
					'text_price' 		=> __('total price for all amazon stores (with the dropship tax applied)', $this->localizationName),
					'text_price_diff' 	=> __('your profit for all amazon stores', $this->localizationName),
				));
			}
		}
	?>

	<?php
		if ( 'order' == $where ) {
	?>
	<div class="WooZoneLite-cart-order-gtotal">
		<?php echo $price_html; ?>
	</div>
	<?php
		}
	?>

	<?php
		$jsPms = array();
		$lang = array(
			'amazon_checkout_title' => __( 'Amazon Checkout', 'WooZoneLite' ),
		);
	?>

	<div class="WooZoneLite-cart-data" style="display: none;">

		<div class="WooZoneLite-cart-lang"><?php echo json_encode( $lang ); ?></div>
		
		<div class="WooZoneLite-cart-pms"><?php echo json_encode( $jsPms ); ?></div>

		<?php
			if ( 'order' == $where ) {
		?>
		<div class="WooZoneLite-cart-order-status">
			<select id="WooZoneLite-cart-order-status" name="WooZoneLite-cart-order-status">
			<?php
				$all_amz_status = $WooZoneLite->woo_order_all_amazon_status();

				foreach ( $all_amz_status as $key => $val ) {

					$order_status = isset($order_info['amazon_status']) ? $order_info['amazon_status'] : 'new';
					$is_sel = $order_status == $key ? ' selected="selected"' : '';
					echo sprintf( '<option value="%s"%s>%s</option>', $key, $is_sel, $val );
				}
			?>
			</select>
		</div>
		<?php
			}
		?>

	</div>

	<div class="WooZoneLite-cart-msg"></div>
</div>

<?php do_action( 'woozonelite_template_amazon_checkout_multishops_after' ); ?>