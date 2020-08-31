<?php
if ( !defined('ABSPATH') ) {
	/**
	 * mod		: sync | cronjob | report | assets | auto_import
	 * act	: (mod: actions associated)
	 * 		sync			: get_products | last_product | small_bulk | full_cycle
	 * 		cronjob 		: get_cron
	 * 		auto_import		: queue | search
	 */
	$req = array(
		'mod'		=> isset($_REQUEST['mod']) ? (string) $_REQUEST['mod'] : '',
		'act'		=> isset($_REQUEST['act']) ? (string) $_REQUEST['act'] : '',
	);
	extract($req);

	//echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

    $absolute_path = __FILE__;
    $path_to_file = explode( 'wp-content', $absolute_path );
    $path_to_wp = $path_to_file[0];

    /** Set up WordPress environment */
    require_once( $path_to_wp.'/wp-load.php' );
    global $WooZoneLite;

    @ini_set('max_execution_time', 0);
    @set_time_limit(0); // infinte
    //WooZoneLite_SyncProducts_event();
 
    // SYNC...
    if ( 'sync' == $mod ) {

		require_once( $path_to_wp.'/wp-content/plugins/woozonelite/modules/synchronization/init.php' );
		$sync = new wwcAmazonSyncronize($WooZoneLite);

		if ( 'get_products' == $act ) {
			$products = $sync->get_products();
			var_dump('<pre>', $products, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}
		if ( 'small_bulk' == $act ) {
			$cron_small_bulk = $sync->cron_small_bulk(array('recurrence' => 120, 'status' => 'new'));
			var_dump('<pre>', $cron_small_bulk, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}
		if ( 'full_cycle' == $act ) {
			$cron_full_cycle = $sync->cron_full_cycle(array('recurrence' => 120, 'status' => 'new'));
			var_dump('<pre>', $cron_full_cycle, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}
	}


    // CRONJOBS...
    if ( 'cronjob' == $mod ) {
	    require_once( $path_to_wp.'/wp-content/plugins/woozonelite/modules/cronjobs/cronjobs.core.php' );
    	$cronjobs = new WooZoneLiteCronjobs($WooZoneLite);

		if ( 'get_cron' == $act || empty($act) ) {
		    var_dump('<pre>','first time','</pre>'); 
		    $get_config = $cronjobs->get_config();
		    foreach ($get_config as $cron_id => $cron) {
		        if ( !in_array($cron_id, array('unblock_crons')) ) continue 1;
		        //if ( !in_array($cron_id, array('sync_products')) ) continue 1;
		        //if ( !in_array($cron_id, array('sync_products_cycle')) ) continue 1;
		        //if ( !in_array($cron_id, array('assets_download')) ) continue 1;
		
		        //$cronjobs->set_cron($cron_id, array('status' => 'new'));
		        
		        $cronjobs->run($cron_id);
		        $status = $cronjobs->get_cron($cron_id);
		        $status = $status['status'];
		        var_dump('<pre>', $cron_id, $status, '</pre>');
		    }
		
		    var_dump('<pre>','second time','</pre>');  
		    $get_config = $cronjobs->get_config();
		    foreach ($get_config as $cron_id => $cron) {
		        $status = $cronjobs->get_cron($cron_id);
		        $status = $status['status'];
		        var_dump('<pre>', $cron_id, $status, '</pre>');
		    }
		
		    echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
	    }
    }


    // REPORT...
    if ( 'report' == $mod ) {
	    require_once( $path_to_wp.'/wp-content/plugins/woozonelite/modules/report/init.php' );
	    $report = new WooZoneLiteReport($WooZoneLite);
	
	    $cronjob = $report->cronjob(array());
	    var_dump('<pre>', $cronjob, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
	}

    
    // ASSETS DOWNLOAD...
    if ( 'assets' == $mod ) {
	    require_once( $path_to_wp.'/wp-content/plugins/woozonelite/modules/assets_download/init.php' );
	    $assets = new WooZoneLiteAssetDownload();
	
	    $cronjob = $assets->cronjob(array());
	    var_dump('<pre>', $cronjob, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
	}

   
    // AUTO IMPORT - QUEUE...
    if ( 'auto_import' == $mod ) {
	    require_once( $path_to_wp.'/wp-content/plugins/woozonelite/modules/auto_import/init.php' );
	    $autoimport = new WooZoneLiteAutoImport();
   
		if ( 'queue' == $act ) {
	    	$cronjob = $autoimport->cronjob_queue(array());
	    	var_dump('<pre>', $cronjob, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}
		if ( 'search' == $act ) {
	    	$cronjob = $autoimport->cronjob_search(array());
	    	var_dump('<pre>', $cronjob, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
	    }
	}
}
die;   
