<?php

global $WooZoneLite;

//=======================================================
// :: Search Params
$WooZoneLite_search_params = array();

//------------------------------
//:: AMAZON
/*
// MinimumPrice & MaximumPrice
$WooZoneLite_search_params['MinimumPrice'] = array(
	"" 	        => "None",
	"100"		=> "£1.00",
	"200"		=> "£2.00",
	"500"		=> "£5.00",
	"1000"	    => "£10.00",
	"2000"	    => "£20.00",
	"5000"	    => "£50.00",
	"10000"	    => "£100.00",
	"20000"	    => "£200.00",
	"50000"	    => "£500.00",
	"any"	    => "Any"
);
$WooZoneLite_search_params['MaximumPrice'] = $WooZoneLite_search_params['MinimumPrice'];
*/

// Condition
$WooZoneLite_search_params['Condition'] = array(
	"" 				=> "All Conditions",
	"Any" 			=> "Any",
	"New" 			=> "New",
	"Used" 			=> "Used",
	"Collectible" 	=> "Collectible",
	"Refurbished" 	=> "Refurbished",
);

// MinPercentageOff
$WooZoneLite_search_params['MinPercentageOff'] = array(
	"" 		    => "All Min Percentage Off",
	"10"		=> "10%",
	"20"		=> "20%",
	"30"		=> "30%",
	"40"		=> "40%",
	"50"		=> "50%",
	"60"		=> "60%",
	"70"		=> "70%",
	"80"		=> "80%",
	"90"		=> "90%",
	"100"		=> "100%",
);

if ( 'newapi' === $WooZoneLite->amzapi ) {
//[new in api v5]
// MinReviewsRating
$WooZoneLite_search_params['MinReviewsRating'] = array(
	0 		    => "Don't use it",
);
$WooZoneLite_search_params['MinReviewsRating'] = array_merge( $WooZoneLite_search_params['MinReviewsRating'], range(1, 5, 1) );

//[new in api v5]
// MinReviewsRating
$WooZoneLite_search_params['MinSavingPercent'] = array(
	0 		    => "Don't use it",
);
$WooZoneLite_search_params['MinSavingPercent'] = array_merge( $WooZoneLite_search_params['MinSavingPercent'], range(1, 100, 1) );
}

// Sort
$WooZoneLite_search_params['Sort'] = array();
$WooZoneLite_search_params['Sort']['relevancerank'] = 'Relevance rank.';
$WooZoneLite_search_params['Sort']['salesrank'] = "Best selling";
$WooZoneLite_search_params['Sort']['pricerank'] = "Price: low to high";
$WooZoneLite_search_params['Sort']['inverseprice'] = "Price: high to low";
$WooZoneLite_search_params['Sort']['launch-date'] = "Newest arrivals: low to high";
$WooZoneLite_search_params['Sort']['-launch-date'] = "Newest arrivals: high to low";
$WooZoneLite_search_params['Sort']['sale-flag'] = "On sale";
$WooZoneLite_search_params['Sort']['pmrank'] = "Featured items";
$WooZoneLite_search_params['Sort']['price'] = "Price: low to high";
$WooZoneLite_search_params['Sort']['-price'] = "Price: high to low";
$WooZoneLite_search_params['Sort']['reviewrank'] = "Average customer review: high to low";
$WooZoneLite_search_params['Sort']['titlerank'] = "Alphabetical: A to Z";
$WooZoneLite_search_params['Sort']['-titlerank'] = "Alphabetical: Z to A";
$WooZoneLite_search_params['Sort']['pricerank'] = "Price: low to high";
$WooZoneLite_search_params['Sort']['inverse-pricerank'] = "Price: high to low";
$WooZoneLite_search_params['Sort']['daterank'] = "Publication date: newer to older";
$WooZoneLite_search_params['Sort']['psrank'] = "Bestseller ranking - projected sales.";
$WooZoneLite_search_params['Sort']['orig-rel-date'] = "Original Release date: newer to older";
$WooZoneLite_search_params['Sort']['-orig-rel-date'] = "Original Release date: older to newer";
$WooZoneLite_search_params['Sort']['releasedate'] = "Release date: newer to older";
$WooZoneLite_search_params['Sort']['-releasedate'] = "Release date: older to newer";
$WooZoneLite_search_params['Sort']['songtitlerank'] = "Most popular";
$WooZoneLite_search_params['Sort']['uploaddaterank'] = "Date added";
$WooZoneLite_search_params['Sort']['-video-release-date'] = "Video Release date: newer to older";
$WooZoneLite_search_params['Sort']['-edition-sales-velocity'] = "Quickest to slowest selling products.";
$WooZoneLite_search_params['Sort']['subslot-salesrank'] = "Bestselling";
$WooZoneLite_search_params['Sort']['release-date'] = "Latest release date: from newer to older.";
$WooZoneLite_search_params['Sort']['-age-min'] = "Age: high to low";

if ( 'newapi' === $WooZoneLite->amzapi ) {
//[new in api v5]
$WooZoneLite_search_params['Sort'] = array();
$WooZoneLite_search_params['Sort']['AvgCustomerReviews'] = 'Sorts results according to average customer reviews';
$WooZoneLite_search_params['Sort']['Featured'] = 'Sorts results with featured items having higher rank';
$WooZoneLite_search_params['Sort']['NewestArrivals'] = 'Sorts results with according to newest arrivals';
$WooZoneLite_search_params['Sort']['PriceHighToLow'] = 'Sorts results according to most expensive to least expensive';
$WooZoneLite_search_params['Sort']['PriceLowToHigh'] = 'Sorts results according to least expensive to most expensive';
$WooZoneLite_search_params['Sort']['Relevance'] = 'Sorts results with relevant items having higher rank';
}

$__tmp = $WooZoneLite_search_params;
$WooZoneLite_search_params = array();
$WooZoneLite_search_params['amazon'] = $__tmp;

//------------------------------
//:: EBAY
/*
http://developer.ebay.com/DevZone/finding/CallRef/types/ItemFilterType.html
http://developer.ebay.com/DevZone/finding/CallRef/findItemsByCategory.html#Request.itemFilter  
*/
//ListingType
$WooZoneLite_search_params['ebay'] = array_merge(array(
	'sortOrder' 			=> array(
		'BestMatch' 				=> 'Sorts items by Best Match',
		'BidCountFewest' 			=> 'Sorts items by the number of bids (fewest bids first)',
		'BidCountMost' 				=> 'Sorts items by the number of bids (most bids first)',
		'CurrentPriceHighest' 		=> 'Sorts items by their current price (highest price first)',
		'DistanceNearest' 			=> 'Sorts items by distance from the buyer (ascending order)',
		'EndTimeSoonest' 			=> 'Sorts items by end time (soonest first)',
		'PricePlusShippingHighest' 	=> 'Sorts items by the combined cost of the item price plus the shipping cost (highest first)',
		'PricePlusShippingLowest' 	=> 'Sorts items by the combined cost of the item price plus the shipping cost (lowest first)',
		'StartTimeNewest' 			=> 'Sorts items by the start time (newest first)',
	),
	'AuthorizedSellerOnly'	=> array(
		'' 						=> 'AuthorizedSellerOnly?',
		'true' 				 	=> 'yes',
		'false' 				=> 'no',
	),
	'BestOfferOnly'			=> array(
		'' 						=> 'BestOfferOnly?',
		'true' 				 	=> 'yes',
		'false' 				=> 'no',
	),
	'CharityOnly'			=> array(
		'' 						=> 'CharityOnly?',
		'true' 				 	=> 'yes',
		'false' 				=> 'no',
	),
	//http://developer.ebay.com/DevZone/finding/CallRef/Enums/conditionIdList.html
	'Condition'			=> array(
		'' 						=> 'Condition?',
		'1000'					=> 'New',
		'1500' 				 	=> 'New other (see details)',
		'1750'				 	=> 'New with defects',
		'2000' 				 	=> 'Manufacturer refurbished',
		'2500' 				 	=> 'Seller refurbished',
		'3000' 				 	=> 'Used',
		'4000' 				 	=> 'Very Good',
		'5000' 				 	=> 'Good',
		'6000' 				 	=> 'Acceptable',
		'7000' 				 	=> 'For parts or not working',
	),
	//http://developer.ebay.com/DevZone/finding/CallRef/Enums/currencyIdList.html
	'Currency'			=> array(
		'' 						=> 'Currency?',
		'AUD'					=> 'AUD',
		'CAD' 				 	=> 'CAD',
		'CHF'				 	=> 'CHF',
		'CNY' 				 	=> 'CNY',
		'EUR' 				 	=> 'EUR',
		'GBP' 				 	=> 'GBP',
		'HKD' 				 	=> 'HKD',
		'INR' 				 	=> 'INR',
		'MYR' 				 	=> 'MYR',
		'PHP' 				 	=> 'PHP',
		'PLN' 				 	=> 'PLN',
		'SEK' 				 	=> 'SEK',
		'SGD' 				 	=> 'SGD',
		'TWD' 				 	=> 'TWD',
		'USD' 				 	=> 'USD',
	),
	'ExpeditedShippingType'	=> array(
		'' 						=> 'ExpeditedShippingType?',
		'Expedited' 		 	=> 'Expedited',
		'OneDayShipping'		=> 'OneDayShipping',
	),
	'FeaturedOnly'			=> array(
		'' 						=> 'FeaturedOnly?',
		'true' 				 	=> 'yes',
		'false' 				=> 'no',
	),
	'FreeShippingOnly'		=> array(
		'' 						=> 'FreeShippingOnly?',
		'true' 				 	=> 'yes',
		'false' 				=> 'no',
	),
	'GetItFastOnly'			=> array(
		'' 						=> 'GetItFastOnly?',
		'true' 				 	=> 'yes',
		'false' 				=> 'no',
	),
	'HideDuplicateItems'	=> array(
		'' 						=> 'HideDuplicateItems?',
		'true' 				 	=> 'yes',
		'false' 				=> 'no',
	),
	'ListingType'	=> array(
		'' 						=> 'ListingType?',
		'All' 				 	=> 'All',
		'Auction' 				=> 'Auction',
		'AuctionWithBIN' 		=> 'AuctionWithBIN',
		'Classified' 			=> 'Classified',
		'FixedPrice' 			=> 'FixedPrice',
	),
	'LocalSearchOnly'	=> array(
		'' 						=> 'LocalSearchOnly?',
		'true' 				 	=> 'yes',
		'false' 				=> 'no',
	),
	'OutletSellerOnly'	=> array(
		'' 						=> 'OutletSellerOnly?',
		'true' 				 	=> 'yes',
		'false' 				=> 'no',
	),
	'PaymentMethod'	=> array(
		'' 						=> 'PaymentMethod?',
		'PayPal' 				=> 'PayPal',
		'PaisaPay' 				=> 'PaisaPay',
		'PaisaPayEMI' 			=> 'PaisaPayEMI',

	),
	'ReturnsAcceptedOnly'	=> array(
		'' 						=> 'ReturnsAcceptedOnly?',
		'true' 				 	=> 'yes',
		'false' 				=> 'no',
	),
	'WorldOfGoodOnly'	=> array(
		'' 						=> 'WorldOfGoodOnly?',
		'true' 				 	=> 'yes',
		'false' 				=> 'no',
	),
));


//=======================================================
// :: Params description
$WooZoneLite_search_params_desc = array();
$WooZoneLite_search_params_sort = array();
$WooZoneLite_search_params_sel = array();

//------------------------------
//:: AMAZON
$WooZoneLite_search_params_sort['amazon'] = $WooZoneLite_search_params['amazon']['Sort'];
$WooZoneLite_search_params_sort['amazon']["relevancerank"] = 'Items ranked according to the following criteria: how often the keyword appears in the description, where the keyword appears (for example, the ranking is higher when keywords are found in titles), how closely they occur in descriptions (if there are multiple keywords), and how often customers purchased the products they found using the keyword.';
$WooZoneLite_search_params_sort['amazon']["psrank"] = 'Bestseller ranking taking into consideration projected sales. The lower the value, the better the sales.';
$WooZoneLite_search_params_sort['amazon']["release-date"] = 'Sorts by the latest release date from newer to older. See orig-rel-date, which sorts by the original release date.';

$WooZoneLite_search_params_desc['amazon'] = array(
    'Sort'              => 'An optional parameter <br />Means by which the items in the response are ordered.',
    'BrowseNode'        => 'An optional parameter <br />Browse nodes are identify items categories',
    'Brand'             => 'An optional parameter <br />Name of a brand associated with the item. You can enter all or part of the name. For example, Timex, Seiko, Rolex.',
    'Condition'         => 'An optional parameter <br />Use the Condition parameter to filter the offers returned in the product list by condition type. By default, Condition equals "New". If you do not get results, consider changing the value to "All. When the Availability parameter is set to "Available," the Condition parameter cannot be set to "New."',
    'Manufacturer'      => 'An optional parameter <br />Name of a manufacturer associated with the item. You can enter all or part of the name.',
    'MaximumPrice'      => 'An optional parameter <br />Specifies the maximum price of the items in the response. Prices are in terms of the lowest currency denomination, for example, pennies. For example, 3241 represents $32.41.',
    'MinimumPrice'      => 'An optional parameter <br />Specifies the minimum price of the items to return. Prices are in terms of the lowest currency denomination, for example, pennies, for example, 3241 represents $32.41.',
    'MerchantId'        => 'An optional parameter <br/>You can use to filter search results and offer listings to only include items sold by Amazon. By default, Product Advertising API returns items sold by various merchants including Amazon. Use the Amazon to limit the response to only items sold by Amazon. Valid values include: All, Amazon.', //, Featured, FeaturedBuyBoxMerchant
    'MinPercentageOff'  => 'An optional parameter <br />Specifies the minimum percentage off for the items to return.',

	//[new in api v5]
    'MinReviewsRating' 	=> 'Filters search results to items with customer review ratings above specified value.', //Positive Integer less than 5
    'MinSavingPercent' 	=> 'Filters search results to items with at least one offer having saving percentage above the specified value.', //Positive Integer less than 100
);

//------------------------------
//:: EBAY
$WooZoneLite_search_params_sel['ebay']['sortOrder'] = array_merge(array(
		'BestMatch' 				=> 'Sorts items by Best Match, which is based on community buying activity and other relevance-based factors. Note: eBay site search results sorted by Best Match may not match the API search results sorted by Best Match. The site Best Match algorithm takes into account additional factors, such as user information, not available to the API.',
		'BidCountFewest' 			=> 'Sorts items by the number of bids they have received, with items that have received the fewest bids first. To sort by bid count, you must specify a listing type filter to limit results to auction listings only (such as, & itemFilter.name=ListingType&itemFilter.value=Auction).',
		'BidCountMost' 				=> 'Sorts items by the number of bids they have received, with items that have received the most bids first. To sort by bid count, you must specify a listing type filter to limit results to auction listings only (such as, & itemFilter.name=ListingType&itemFilter.value=Auction).',
		'CurrentPriceHighest' 		=> 'Sorts items by their current price, with the highest price first.',
		'DistanceNearest' 			=> 'Sorts items by distance from the buyer in ascending order. The request must also include a buyerPostalCode.',
		'EndTimeSoonest' 			=> 'Sorts items by end time, with items ending soonest listed first.',
		'PricePlusShippingHighest' 	=> 'Sorts items by the combined cost of the item price plus the shipping cost, with highest combined price items listed first. Items are returned in the following groupings: highest total-cost items (for items where shipping was properly specified) appear first, followed by freight- shipping items, and then items for which no shipping was specified. Each group is sorted by price.',
		'PricePlusShippingLowest' 	=> 'Sorts items by the combined cost of the item price plus the shipping cost, with the lowest combined price items listed first. Items are returned in the following groupings: lowest total-cost items (for items where shipping was properly specified) appear first, followed by freight- shipping items, and then items for which no shipping was specified. Each group is sorted by price.',
		'StartTimeNewest' 			=> 'Sorts items by the start time, the most recently listed (newest) items appear first.',
));

$WooZoneLite_search_params_sel['ebay']['Condition'] = array_merge(array(
		'1000'					=> 'A brand-new, unused, unopened, unworn, undamaged item. Most categories support this condition (as long as condition is an applicable concept).',
		'1500' 				 	=> 'A brand-new new, unused item with no signs of wear. Packaging may be missing or opened. The item may be a factory second or have defects.',
		'1750'				 	=> 'A brand-new, unused, and unworn item. The item may have cosmetic defects, and/or may contain mismarked tags (e.g., incorrect size tags from the manufacturer). Packaging may be missing or opened. The item may be a new factory second or irregular.',
		'2000' 				 	=> 'An item in excellent condition that has been professionally restored to working order by a manufacturer or manufacturer-approved vendor. The item may or may not be in the original packaging.',
		'2500' 				 	=> 'An item that has been restored to working order by the eBay seller or a third party who is not approved by the manufacturer. This means the seller indicates that the item is in full working order and is in excellent condition. The item may or may not be in original packaging.',
		'3000' 				 	=> 'An item that has been used previously. The item may have some signs of cosmetic wear, but is fully operational and functions as intended. This item may be a floor model or store return that has been used. Most categories support this condition (as long as condition is an applicable concept).',
		'4000' 				 	=> 'An item that is used but still in very good condition. No obvious damage to the cover or jewel case. No missing or damaged pages or liner notes. The instructions (if applicable) are included in the box. May have very minimal identifying marks on the inside cover. Very minimal wear and tear.',
		'5000' 				 	=> 'An item in used but good condition. May have minor external damage including scuffs, scratches, or cracks but no holes or tears. For books, liner notes, or instructions, the majority of pages have minimal damage or markings and no missing pages.',
		'6000' 				 	=> 'An item with obvious or significant wear, but still operational. For books, liner notes, or instructions, the item may have some damage to the cover but the integrity is still intact. Instructions and/or box may be missing. For books, possible writing in margins, etc., but no missing pages or anything that would compromise the legibility or understanding of the text.',
		'7000' 				 	=> 'An item that does not function as intended and is not fully operational. This includes items that are defective in ways that render them difficult to use, items that require service or repair, or items missing essential components. Supported in categories where parts or unworking items are of interest to people who repair or collect related items.',
));

$WooZoneLite_search_params_sel['ebay']['Currency'] = array_merge(array(
		'AUD'					=> 'AUD (Australian Dollar. For eBay, you can only specify this currency for listings you submit to the Australia site (global ID EBAY-AU, site ID 15).)',
		'CAD' 				 	=> 'CAD (Canadian Dollar. For eBay, you can only specify this currency for listings you submit to the Canada site (global ID EBAY-ENCA, site ID 2) (Items listed on the Canada site can also specify USD.))',
		'CHF'				 	=> 'CHF (Swiss Franc. For eBay, you can only specify this currency for listings you submit to the Switzerland site (global ID EBAY-CH, site ID 193).)',
		'CNY' 				 	=> 'CNY (Chinese Chinese Renminbi.)',
		'EUR' 				 	=> 'EUR (Euro. For eBay, you can only specify this currency for listings you submit to these sites: Austria (global ID EBAY-AT, site 16), Belgium_French (global ID EBAY-FRBE, site 23), France (global ID EBAY-FR, site 71), Germany (global ID EBAY-DE, site 77), Italy (global ID EBAY-IT, site 101), Belgium_Dutch (global ID EBAY-NLBE, site 123), Netherlands (global ID EBAY-NL, site 146), Spain (global ID EBAY-ES, site 186), Ireland (global ID EBAY-IE, site 205).)',
		'GBP' 				 	=> 'GBP (Pound Sterling. For eBay, you can only specify this currency for listings you submit to the UK site (global ID EBAY-GB, site ID 3).)',
		'HKD' 				 	=> 'HKD (Hong Kong Dollar. For eBay, you can only specify this currency for listings you submit to the Hong Kong site (global ID EBAY-HK, site ID 201).)',
		'INR' 				 	=> 'INR (Indian Rupee. For eBay, you can only specify this currency for listings you submit to the India site (global ID EBAY-IN, site ID 203).)',
		'MYR' 				 	=> 'MYR (Malaysian Ringgit. For eBay, you can only specify this currency for listings you submit to the Malaysia site (global ID EBAY-MY, site ID 207).)',
		'PHP' 				 	=> 'PHP (Philippines Peso. For eBay, you can only specify this currency for listings you submit to the Philippines site (global ID EBAY-PH, site ID 211).)',
		'PLN' 				 	=> 'PLN (Poland, Zloty. For eBay, you can only specify this currency for listings you submit to the Poland site (global ID EBAY-PL, site ID 212).)',
		'SEK' 				 	=> 'SEK (Swedish Krona. For eBay, you can only specify this currency for listings you submit to the Sweden site (global ID EBAY-SE, site 218).)',
		'SGD' 				 	=> 'SGD (Singapore Dollar. For eBay, you can only specify this currency for listings you submit to the Singapore site (global ID EBAY-SG, site 216).)',
		'TWD' 				 	=> 'TWD (New Taiwan Dollar. Note that there is no longer an eBay Taiwan site.)',
		'USD' 				 	=> 'USD (US Dollar. For eBay, you can only specify this currency for listings you submit to the US (site ID 0), eBayMotors (site 100), and Canada (site 2) sites.)',
));

$WooZoneLite_search_params_sel['ebay']['ListingType'] = array_merge(array(
		'All' 				 	=> 'Retrieves matching items for any listing type.',
		'Auction' 				=> 'Retrieves matching auction listings (i.e., listings eligible for competitive bidding at auction) only. Excludes auction items with Buy It Now.',
		'AuctionWithBIN' 		=> 'Retrieves all matching auction listings with Buy It Now available. Excludes auction listings without Buy It Now. An auction listed with Buy It Now will not be returned if a valid bid has been placed on the auction.',
		'Classified' 			=> 'Retrieves Classified Ad format (i.e., Classified and AdFormat listing type) listings only.',
		'FixedPrice' 			=> 'Retrieves matching fixed price items only.',
));

$WooZoneLite_search_params_sel['ebay']['PaymentMethod'] = array_merge(array(
		'PayPal' 				=> 'PayPal payment method.',
		'PaisaPay' 				=> 'PaisaPay payment method. The PaisaPay payment method is only for the India site (global ID EBAY-IN).',
		'PaisaPayEMI' 			=> 'PaisaPayEscrow EMI (Equal Monthly Installment) payment method. The PaisaPayEscrowEMI payment method is only for the India site (global ID EBAY-IN).',
));

$WooZoneLite_search_params_desc['ebay'] = array_merge(array(
		'sortOrder' 			=> 'Sort the returned items according to a single specified sort order. Default: BestMatch. ',
		'AuthorizedSellerOnly'	=> 'If set to true, returns only items listed by authorized sellers.',
		'BestOfferOnly'			=> 'If true, the search results are limited to only items that have Best Offer enabled. Default is false.',
		'CharityOnly'			=> 'If true, the search results are limited to items for which all or part of the proceeds are given to a charity. Each item in the search results will include the ID of the given charity. Default is false.',
		//http://developer.ebay.com/DevZone/finding/CallRef/Enums/conditionIdList.html
		'Condition'				=> 'Limits items to those that have the matching item condition. The order of the results depends on the sortOrder you specify (not ordered by conditions).Mostly useful to filter items where the seller used one of eBay\'s structured item condition formats (conditionId or item specifics) to specify the item condition. If the seller used item specifics, the condition is only returned in conditionDisplayName.',
		//http://developer.ebay.com/DevZone/finding/CallRef/Enums/currencyIdList.html
		'Currency'				=> 'Limits results to items listed with the specified currency only.',
		'ExpeditedShippingType'	=> 'Specifies the type of expedited shipping. You can specify either Expedited or OneDayShipping. Only items that can be shipped by the specified type are returned.',
		'FeaturedOnly'			=> 'If true, the search results are limited to featured item listings only. Default is false.',
		'FeedbackScoreMax'		=> 'Specifies the maximum feedback score of a seller whose items can be included in the response. If FeedbackScoreMin is also specified, the FeedbackScoreMax value must be greater than or equal to the FeedbackScoreMin value. (Integer greater than or equal to 0.)',
		'FeedbackScoreMin'		=> 'Specifies the minimum feedback score of a seller whose items can be included in the response. If FeedbackScoreMax is also specified, the FeedbackScoreMax value must be greater than or equal to the FeedbackScoreMin value. (Integer greater than or equal to 0.)',
		'FreeShippingOnly'		=> 'If true, the search results are limited to only items with free shipping to the site specified in the request (see Global ID Values). Default is false.',
		'GetItFastOnly'			=> 'If true, the search results are limited to only Get It Fast listings. Default is false.',
		'HideDuplicateItems'	=> 'If true, and there are duplicate items for an item in the search results, the subsequent duplicates will not appear in the results. Default is false.
	Item listings are considered duplicates when all of the following conditions are met:
	1. Items are listed by the same seller
	2. Items have exactly the same item title
	3. Items have similar listing formats: 
    	- Auctions (Auction Items and Auction BIN items)
    	- Fixed Price (Fixed Price, Multi-quantity Fixed Price, and Fixed Price with Best Offer Format items)
    	- Classified Ads
	For Auctions, items must also have the same price and number of bids to be considered duplicates.',
		'ListingType'			=> 'Filters items based listing type information. Default behavior is to return all matching items.',
		'LocalSearchOnly'		=> 'If true, the search results are limited to only matching items with the Local Inventory Listing Options (LILO). Must be used together with the MaxDistance item filter, and the request must also specify buyerPostalCode. Currently, this is only available for the Motors site (global ID EBAY- MOTOR).',
		'MaxBids'				=> 'Limits the results to items with bid counts less than or equal to the specified value. If MinBids is also specified, the MaxBids value must be greater than or equal to the MinBids value. (Integer greater than or equal to 0.)',
		'MaxDistance'			=> 'Specifies the maximum distance from the specified postal code (buyerPostalCode) to search for items. The request must also specify buyerPostalCode. 
- The minimum distance supported is 5 miles or 10 kilometers, depending upon whether the distance unit supported for the site to which the request is submitted is miles (mi) or kilometers (km). For example, the smallest MaxDistance for searches submitted to the US eBay site (global ID EBAY-US) is 5 (miles). The smallest MaxDistance for searches submitted to the Germany eBay site (global ID EBAY-DE) is 10 (kilometers). 
- Values are rounded up to the nearest 5 (mi) or 10 (km) increment. For example, a value of 21 will be rounded up to 25 (mi) on the eBay US site and to 30 (km) on the eBay Germany site. (Integer greater than or equal to 5.)',
		'MaxPrice'				=> 'Specifies the maximum current price an item can have to be included in the response. Optionally, you can also specify a currency ID, using the paramName and paramValue fields (for example, ¶mName=Currency¶mValue=EUR). If using with MinPrice to specify a price range, the MaxPrice value must be greater than or equal to MinPrice. (Decimal values greater than or equal to 0.0.)',
		'MinBids'				=> 'Limits the results to items with bid counts greater than or equal to the specified value. If MaxBids is also specified, the MaxBids value must be greater than or equal to the MinBids value. (Integer greater than or equal to 0.)',
		'MinPrice'				=> 'Specifies the minimum current price an item can have to be included in the response. Optionally, you can also specify a currency ID, using the paramName and paramValue fields (for example, ¶mName=Currency¶mValue=EUR). If using with MaxPrice to specify a price range, the MaxPrice value must be greater than or equal to MinPrice. (Decimal values greater than or equal to 0.0.)',
		'OutletSellerOnly'		=> 'If set to true, returns only items listed by sellers at eBay\'s outlet stores, such as the <a href=\'http://www.ebay.com/fashion/outlet\' target=\'_blank\'>Fashion Outlet</a>.',
		'PaymentMethod'			=> 'Limits results to items that accept the specified payment method.',
		'ReturnsAcceptedOnly'	=> 'If set to true, returns only items where the seller accepts returns. 
ExpeditedShippingType is used together with the MaxHandlingTime and ReturnsAcceptedOnly filters to filter items for certain kinds of gifting events such as birthdays or holidays where the items must be delivered by a certain date. If you wish to mimic the behavior of the eBay holiday filters, you would use ExpeditedShippingType set to either Expedited or OneDayShipping, MaxHandlingTime to 1, ReturnsAcceptedOnly set to true, and for the Germany site, set PaymentMethod to PayPal. (The holiday filters may not always be available in the eBay UI, depending on the season; however, the equivalent filter behavior continues to be available in the API.) ',
		'Seller'				=> 'Specify one or more seller names. Search results will include items from the specified sellers only. The Seller item filter cannot be used together with either the ExcludeSeller or TopRatedSellerOnly item filters. (Valid seller names.)',
		'WorldOfGoodOnly'		=> 'If true, the search results are limited to only items listed in the World of Good marketplace. Defaults to false.',
));

?>