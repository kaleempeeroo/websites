/*
Document   :  WooCustom
Author     :  Andrei Dinca, AA-Team http://codecanyon.net/user/AA-Team
*/

// Initialization and events code for the app
WooZoneLiteWooCustom = (function($) {
	"use strict";

	var page 			= '';
	var product_type 	= '';

	// init function, autoload
	(function init() {
		// load the triggers
		$(document).ready(function() {

			page = $('.inside #publish').length > 0 ? 'details' : 'list';
			//console.log( page );

			if ( page == 'details' ) {
				product_type = $('#woocommerce-product-data .hndle span #product-type').val();
				//console.log( product_type ); 
			}

			woo_buttons_all();

			trigger_fix_images();

			var tipsy_exp_prefix    = '.WooZoneLite_product_info .WooZoneLiteWoocustomFields',
				tipsy_exp           = tipsy_exp_prefix + ' a, ' + tipsy_exp_prefix + ' span';
			//jQuery( tipsy_exp ).tipsy({live: true, gravity: 'n'});
			WooZoneLite.aateam_tooltip( tipsy_exp );
		});
	})();


	//===================================================================
	//== others

	// woocommerce fix thumb for remote images with https - on frontend	
	function trigger_fix_images() {
		fix_images();

		/*$(window).on( 'load', function(){
			fix_images();
		});*/
	};

	function fix_images() {
		var $imgFound = $("img[src*='http__']");
		//console.log( $imgFound.size() ); 
		$imgFound.each(function() {
			$(this).attr( 'src', $(this).attr('src').replace("http__", "http") );
		});
	};


	//===================================================================
	//== add amazon asin & view product amazon page
	// on admin products listing page & admin product details page

	function when_variations_loaded() {
		WooZoneLite.aateam_tooltip();

		$('#woocommerce-product-data .woocommerce_variations .woocommerce_variation').each( function(i) {
			var that = $(this);
			var container = that.find('.woocommerce_variable_attributes .data .variable_pricing') || that.find('.woocommerce_variable_attributes .data_table .variable_pricing');
			var post_id = that.find('h3 .remove_variation').attr('rel');
 
			woo_buttons_add( post_id, null, { 'container' : container } );

			var $new_container = that.find('.woocommerce_variable_attributes .data .form-row.form-row-full.options') || that.find('.woocommerce_variable_attributes .data_table .form-row.form-row-full.options');
			dropshiptax_box_add( post_id, { 'container' : $new_container } );
		});
	}

	function woo_buttons_all() {
		var post_id 	= 0,
			pillar 		= null;

		//pillar = $('#woocommerce-product-data div.inside #general_product_data');
		pillar = $('#woocommerce-product-data div.inside .form-field._sku_field')
			.parents('.panel.woocommerce_options_panel').eq(0);
 
		// Prevent inputs in meta box headings opening/closing contents
		(function() {
			var maincontainer = $("#woocommerce-product-data .wc-metaboxes-wrapper .woocommerce_variations .woocommerce_variation.wc-metabox");
	
			$( maincontainer.find(' > h3') ).unbind('click');
	
			jQuery( maincontainer ).on('click', ' > h3', function(event){
					
				// If the user clicks on some form input inside the h3 the box should not be toggled
				if ( $(event.target).filter('input, option, label, select, a, span').length ) {
					return;
				}
					
			   $( maincontainer ).toggleClass( 'closed' );
			});
		})();

		if ( product_type == 'variable' ) {

			var $asin = pillar.find('input#WooZoneLite_asin'),
				asin = $asin.val(),
				$dpwhere = $('#woocommerce-product-data div.inside .form-field._sku_field');

			post_id = $asin.parents('form').find('input#post_ID').val();

			woo_buttons_add( post_id, $asin );

			dropshiptax_box_add( post_id, { 'container' : $dpwhere } );

			// add for product variations
			$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', when_variations_loaded);

			// add for product variations - old (before variations were loaded by ajax)
			//$("#woocommerce-product-data .wc-metaboxes-wrapper .woocommerce_variations .woocommerce_variation.wc-metabox").each( function(i) {
			//	var that = $(this);
			//	var container = that.find('.wc-metabox-content .sku').children().last();//that.find('h3 strong');
			//	var post_id = that.find('h3 .remove_variation').attr('rel');
			 
			//	woo_buttons_add( post_id, null, { 'container' : container } );
			//});
		}
		// simple product type
		else {
			var $asin = pillar.find('input#WooZoneLite_asin'),
				asin = $asin.val(),
				$dpwhere = $('#woocommerce-product-data div.inside .form-field._sale_price_field');

			post_id = $asin.parents('form').find('input#post_ID').val();

			woo_buttons_add( post_id, $asin );

			dropshiptax_box_add( post_id, { 'container' : $dpwhere } );
		}
	}
	
	function woo_buttons_add( post_id, $asin, pms ) {
		var pms 		= ( typeof pms === 'object' && pms !== null ? pms : {} );
		var $container 	= ( misc.hasOwnProperty(pms, 'container') ? pms.container : null );
		var $prod_wrap 	= get_current_wrapper( post_id ),
			$prod_url 	= $prod_wrap.find('a'),
			asin 		= $prod_wrap.data('asin'),
			provider_alias = $prod_wrap.data('provider_alias'),
			provider 	= $prod_wrap.data('provider');

		// build asin element if not available yet!
		if ( $asin === null && $container ) {
			$asin = $container.after( '<div class="WooZoneLite_asin"><span style="display: inline-block; margin-right: 10px;">' + provider + ' ASIN:</span><span title="' + provider + ' ASIN" style="color: green; font-weight: bold;">' + asin + '</span></div>' ).next('.WooZoneLite_asin');
		}

		if ( $asin ) {
			$container ? $asin.append( $prod_url ) : $asin.after( $prod_url );
		}
	}
	
	function get_current_wrapper( post_id ) {
		var wrapper = '';
		wrapper = '.WooZoneLiteWoocustomFields';
		var $wrapper = $(wrapper).filter(function(i) {
			return $(this).data('post_id') == post_id;
		});
		return $wrapper;
	}


	//===================================================================
	//== dropship tax - add prices (original, current & profit)
	// on admin products listing page & admin product details page
	function dropshiptax_box_add( post_id, pms ) {
		var pms 		= ( typeof pms === 'object' && pms !== null ? pms : {} );
		var $container 	= ( misc.hasOwnProperty(pms, 'container') ? pms.container : null );
		var $prod_wrap 	= get_current_wrapper( post_id ),
			asin 		= $prod_wrap.data('asin'),
			dp_info 	= $prod_wrap.find('.WooZoneLite-dp-pricebox').html(),
			$dp_info 	= $(dp_info);

		if ( $container ) {
			$container.after( $dp_info );
		}
	}


	//===================================================================
	//== admin ORDER page

	var orderpage = (function() {
		
		var DISABLED				= false; // disable this module!
		var DEBUG					= false;
		var maincontainer			= null,
			box_checkout 			= null;


		// init function, autoload
		(function init() {
			
			if ( DISABLED ) return false;

			// load the triggers
			$(document).ready(function(){
				maincontainer = $('body');

				set_checkout_box();

				$('h2.woocommerce-order-data__heading').prepend( $('.WooZoneLite-marker-order-hasamazon-tpl').html() );

				triggers();
			});
		})();

		// triggers
		function triggers() {

			// when item quantity is change in order details main box
			// /woocommerce/assets/js/admin/meta-boxes-order.js
			// $( this ).trigger( 'items_saved' );
			maincontainer.on( 'items_saved', woobox_items_saved );

			// amazon proceed to checkout
			maincontainer.on('click', '.WooZoneLite-cart-checkout li .WooZoneLite-cc_checkout input[type="button"].proceed', function (e) {

				e.preventDefault();
				//console.log( 'form proceed!' );

				var $this 		= $(this),
					$li 		= $this.parents('li:first'),
					$wrapp 		= $li.find('.WooZoneLite-cart-fakeform'),
					url 		= $wrapp.data('formaction'),
					$fields 	= $wrapp.find('input[type=hidden], input[type=button]').clone();

				var $fakeform = $('<form>', {
					action : url,
					method : 'get',
					target : '_blank'
				});

				$fields.appendTo( $fakeform );
				$fakeform.appendTo('body').submit();
				$fakeform.remove();

				return true;
			});

			// change order amazon status
			maincontainer.on('change', '.WooZoneLite-order-amzprods > h2 select', function (e) {

				e.preventDefault();
				console.log( 'change order amazon status!' );

				change_order_amazon_status( $(this) );
				return true;
			});
		};

		function change_order_amazon_status( that ) {

			var data = {
				action				: 'WooZoneLite_woocustom',
				sub_action			: 'save_order_amazon_status',
				order_id			: $('input#post_ID').val(),
				order_status 		: that.val()
			};
			//console.log( data );

			$.post(ajaxurl, data, function(response) {

				//if ( misc.hasOwnProperty(response, 'status') ) {}

			}, 'json')
			.fail(function() {})
			.done(function() {})
			.always(function() {});
		}

		function set_checkout_box() {

			$('body #postbox-container-2 #normal-sortables .WooZoneLite-order-amzprods').remove();

			box_checkout = $('body #postbox-container-2 #normal-sortables .WooZoneLite-cart-checkout').clone();
			//console.log( box_checkout  );

			var box_data = $('.WooZoneLite-cart-data'),
				box_lang = box_data.find('.WooZoneLite-cart-lang').html(),
				box_pms = box_data.find('.WooZoneLite-cart-pms').html(),
				box_order_status = box_data.find('.WooZoneLite-cart-order-status').html();

			box_lang = typeof box_lang != 'undefined'
				? JSON && JSON.parse(box_lang) || $.parseJSON(box_lang) : box_lang;
			//console.log( 'box_lang', box_lang );

			box_pms = typeof box_pms != 'undefined'
				? JSON && JSON.parse(box_pms) || $.parseJSON(box_pms) : box_pms;
			//console.log( 'box_pms', box_pms );

			if ( box_checkout.length ) {

				//box_checkout.appendTo( $('body #postbox-container-2 #normal-sortables #woocommerce-order-items .inside') );
				var __ = null;

				__ = $('<div class="postbox WooZoneLite-order-amzprods">')
				 	//hndle ui-sortable-handle
					.append(
						$('<h2 class="">')
						.append( '<span>' + box_lang.amazon_checkout_title + '</span>' )
						.append( box_order_status )
					)
					.append( 
						$('<div class="inside">').append( box_checkout )
					);

				$('body #postbox-container-2 #normal-sortables #woocommerce-order-items').after( __ );
				box_checkout.show();
				//console.log( box_checkout );
			}
		}

		function woobox_items_saved() {
			//console.log( 'items_saved' );

			/*
			function _doit() {
				var _timer = setTimeout( function() {

					if ( $( '#woocommerce-order-items .inside .WooZoneLite-cart-checkout' ).length ) {

						console.log( 'items_saved done' );
						_timer = null;
						clearTimeout( _timer );

						set_checkout_box();
					}
					else {
						console.log( 'items_saved redo' );
						_doit();
					}

				}, 100 );
			}
			_doit();
			*/

			//$('#woocommerce-order-items .inside .WooZoneLite-cart-checkout')
			//$('#woocommerce-order-items .inside').get(0)
			//document.getElementsByClassName('WooZoneLite-cart-checkout')[0]
			//document.getElementById('woocommerce-order-items')
			observer.init( $('#woocommerce-order-items .inside').get(0), function() {
				set_checkout_box();
			});
		}

		var observer = (function() {

			var targetNode = null,
				our_callback = null;

			// Options for the observer (which mutations to observe)
			var config = { attributes: true, childList: true, subtree: true };

			// Callback function to execute when mutations are observed
			var observer_callback = function( mutationsList ) {

				var __ = false;
				for ( var mutation of mutationsList ) {

					if ( mutation.type == 'childList' ) {
						//console.log('A child node has been added or removed.');
						__ = true;
					}
					else if ( mutation.type == 'attributes' ) {
						//console.log('The ' + mutation.attributeName + ' attribute was modified.');
						__ = true;
					}
				}

				if ( __ ) {
					if ( $.isFunction( our_callback ) ) {
						our_callback();
					}
				}
			};

			function init( node, callback ) {

				// Select the node that will be observed for mutations
				targetNode = node || null;

				// our callback function (if any)
				our_callback = callback || null;

				// Create an observer instance linked to the callback function
				var observer = new MutationObserver( observer_callback );

				// Start observing the target node for configured mutations
				observer.observe( targetNode, config );
			}

			function destroy() {
				// Later, you can stop observing
				observer.disconnect();
			}

			return {
				'init' : init,
				'destroy' : destroy
			}
		})();

		// external usage
		return {
		};

	})();


	//===================================================================
	//== MISC
	var misc = (function(){
		
		function hasOwnProperty(obj, prop) {
			var proto = obj.__proto__ || obj.constructor.prototype;
			return (prop in obj) &&
			(!(prop in proto) || proto[prop] !== obj[prop]);
		}
		
		return {
			'hasOwnProperty' : hasOwnProperty
		}
	})();
				
	// external usage
	return {
	}
})(jQuery);