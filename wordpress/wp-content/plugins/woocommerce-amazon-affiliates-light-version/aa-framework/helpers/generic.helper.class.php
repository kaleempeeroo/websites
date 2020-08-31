<?php
/**
 *	Author: AA-Team
 *	Name: 	http://codecanyon.net/user/AA-Team/portfolio
 *	
**/
! defined( 'ABSPATH' ) and exit;

if(class_exists('WooZoneLiteGenericHelper') != true) {
	class WooZoneLiteGenericHelper extends WooZoneLite
	{
		public $the_plugin = null;
		public $amz_settings = array();

		static protected $_instance;

		const MSG_SEP = '—'; // messages html bullet // '&#8212;'; // messages html separator


		public function __construct( $the_plugin=array() ) 
		{
			$this->the_plugin = $the_plugin; 
			
			$this->init_settings( array(), true );
		}
		
		public function init_settings( $params=array(), $init_setup=true ) {
			// get all amazon settings options
			if ( !empty($this->the_plugin) && !empty($this->the_plugin->amz_settings) ) {
				$this->amz_settings = $this->the_plugin->amz_settings;
			} else {
				$this->amz_settings = @unserialize( get_option( $this->the_plugin->alias . '_amazon' ) );
			}
			$this->amz_settings = !empty($this->amz_settings) && is_array($this->amz_settings) ? $this->amz_settings : array();
		}
		
		static public function getInstance( $the_plugin=array() )
		{
			if (!self::$_instance) {
				self::$_instance = new self( $the_plugin );
			}

			return self::$_instance;
		}



		public function get_asset_by_id( $asset_id, $inprogress=false, $include_err=false, $include_invalid_post=false ) {
			require( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
			$WooZoneLiteAssetDownloadCron = new WooZoneLiteAssetDownload();
			
			return $WooZoneLiteAssetDownloadCron->get_asset_by_id( $asset_id, $inprogress, $include_err, $include_invalid_post );
		}
		
		public function get_asset_by_postid( $nb_dw, $post_id, $include_variations, $inprogress=false, $include_err=false, $include_invalid_post=false ) {
			require( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
			$WooZoneLiteAssetDownloadCron = new WooZoneLiteAssetDownload();
			
			$ret = $WooZoneLiteAssetDownloadCron->get_asset_by_postid( $nb_dw, $post_id, $include_variations, $inprogress, $include_err, $include_invalid_post );
			return $ret;
		}

		public function get_asset_multiple( $nb_dw='all', $inprogress=false, $include_err=false, $include_invalid_post=false ) {
			require( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
			$WooZoneLiteAssetDownloadCron = new WooZoneLiteAssetDownload();
			
			return $WooZoneLiteAssetDownloadCron->get_asset_multiple( $nb_dw, $inprogress, $include_err, $include_invalid_post );
		}



		// Category Slug clean duplicate & Other Bug Fixes
		public function category_slug_clean_all( $retType='die' ) {
			global $wpdb;
			
			$q = "SELECT 
 a.term_id, a.name, a.slug, b.parent, b.count
 FROM {$wpdb->terms} AS a
 LEFT JOIN {$wpdb->term_taxonomy} AS b ON a.term_id = b.term_id
 WHERE 1=1 AND b.taxonomy = 'product_cat'
;";
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) {
				$ret['status'] = 'valid';
				$ret['msg_html'] = __('could not retrieve category slugs!', $this->the_plugin->localizationName);
				if ( $retType == 'die' ) die(json_encode($ret));
				else return $ret;
			}
			
			$upd = 0;
			foreach ($res as $key => $value) {
				$term_id = $value->term_id;
				$name = $value->name;
				$slug = $value->slug;

				$__arr = explode( "-" , $slug );
				$__arr = array_unique( $__arr );
				$slug = implode( "-" , $__arr );

				// execution/ update
				$q_upd = "UPDATE {$wpdb->terms} AS a SET a.slug = '%s' 
 WHERE 1=1 AND a.term_id = %s;";
				$q_upd = sprintf( $q_upd, $slug, $term_id );
				$res_upd = $wpdb->query( $q_upd );

				if ( !empty($res_upd) ) $upd++;
			}
			
			$ret['status'] = 'valid';
			$ret['msg_html'] = $upd . __(' category slugs updated!', $this->the_plugin->localizationName);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}
		
		public function clean_orphaned_amz_meta_all( $retType='die' ) {
			global $wpdb;

			$ret = array();

			//DELETE a FROM wp_postmeta AS a LEFT OUTER JOIN wp_posts AS b ON a.post_id=b.ID WHERE a.meta_key='_amzASIN' AND (b.ID IS NULL OR b.post_type NOT IN ('product', 'product_variation'));
			//$get_amzASINS = "SELECT a.meta_id, a.post_id FROM ". $wpdb->postmeta ." AS a LEFT OUTER JOIN ". $wpdb->posts ." AS b ON a.post_id=b.ID WHERE a.meta_key='_amzASIN' AND b.ID IS NULL";
			$get_amzASINS = "SELECT pm.meta_id, pm.post_id FROM $wpdb->postmeta AS pm LEFT OUTER JOIN $wpdb->posts AS p ON pm.post_id = p.ID WHERE pm.meta_key regexp '^(_amzaff|_amzASIN)' AND ( isnull(p.ID) OR p.post_type NOT IN ('product', 'product_variation') );";
			$get_amzASINS = $wpdb->get_results($get_amzASINS);

			$deleteMetaASINS = array();
			foreach ($get_amzASINS as $meta_id) {
				$deleteMetaASINS[] = $meta_id->meta_id;
			}

			$deleteInvalidAmzMeta = 0;
			if( count($deleteMetaASINS) ) {
				$deleteInvalidAmzMeta = "DELETE FROM ".$wpdb->postmeta." WHERE meta_id IN (" . (implode(',', $deleteMetaASINS)) . ")";
				$deleteInvalidAmzMeta = $wpdb->query($deleteInvalidAmzMeta);
			}

			if ( $deleteInvalidAmzMeta ) {
				$ret['status'] = 'valid';
				$ret['msg_html'] = $deleteInvalidAmzMeta . ' orphaned amz meta cleared.';
			}
			elseif ( count($deleteMetaASINS) <= 0 ) {
				$ret['status'] = 'valid';
				$ret['msg_html'] = 'No orphaned amz meta to clean.';
			}
			else {
				$ret['status'] = 'invalid';
				$ret['msg_html'] = 'Error clearing orphaned amz meta.';
			}
			  
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		public function clean_orphaned_prod_assets_all( $retType='die' ) {
			global $wpdb;
			
			$ret = array(
				'status'        => 'invalid',
				'msg_html'      => 'found and deleted: %s orphaned products, %s assets associated to orphaned products.'
			);
			
			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products', 'posts' => $wpdb->prefix . 'posts');
			
			//SELECT COUNT(a.post_id) FROM wp_amz_products AS a LEFT JOIN wp_posts AS b ON a.post_id = b.ID WHERE 1=1 AND ISNULL(b.ID);
			$nb_products = (int) $wpdb->get_var("SELECT COUNT(a.post_id) as nb FROM ". $tables['products'] ." AS a LEFT JOIN ". $wpdb->posts ." AS b ON a.post_id = b.ID WHERE 1=1 AND ISNULL(b.ID);");
			
			//SELECT COUNT(a.post_id) FROM wp_amz_assets AS a LEFT JOIN wp_amz_products AS b ON a.post_id = b.post_id WHERE 1=1 AND ISNULL(b.post_id);
			$nb_assets = (int) $wpdb->get_var("SELECT COUNT(a.post_id) as nb FROM ". $tables['assets'] ." AS a LEFT JOIN ". $tables['products'] ." AS b ON a.post_id = b.post_id WHERE 1=1 AND ISNULL(b.post_id);");
			
			$ret['status'] = 'valid';
			$ret['msg_html'] = sprintf( $ret['msg_html'], (int) $nb_products, (int) $nb_assets);
 
			if ( $nb_products > 0 ) {
				//delete a FROM wp_amz_products AS a LEFT JOIN wp_posts AS b ON a.post_id = b.ID WHERE 1=1 AND ISNULL(b.ID);
				$delete_products = $wpdb->query("delete a FROM " . $tables['products'] . " as a LEFT JOIN " . $wpdb->posts . " AS b ON a.post_id = b.ID WHERE 1=1 AND ISNULL(b.ID);");
			}
			if ( $nb_assets > 0 ) {
				//delete a FROM wp_amz_assets AS a LEFT JOIN wp_amz_products AS b ON a.post_id = b.post_id WHERE 1=1 AND ISNULL(b.post_id);
				$delete_assets = $wpdb->query("delete a FROM " . $tables['assets'] . " as a LEFT JOIN " . $tables['products'] . " AS b ON a.post_id = b.post_id WHERE 1=1 AND ISNULL(b.post_id);");
			}
			//var_dump('<pre>', $delete_products, $delete_assets, '</pre>'); die('debug...'); 
			
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		public function clean_orphaned_prod_assets_all_wp( $retType='die' ) {
			global $wpdb;
			
			$ret = array(
				'status'        => 'invalid',
				'msg_html'      => '<div><span style="display: inline-block; width: 25rem;">orphaned posts</span>:<span style="display: inline-block; margin-left: 1.5rem;"><span>found = %s</span> | <span style="font-weight: bold; color: red;">deleted = %s</span></span></div>      <div><span style="display: inline-block; width: 25rem;">postmeta associated to orphaned posts</span>:<span style="display: inline-block; margin-left: 1.5rem;"><span style="font-weight: bold; color: red;">deleted = %s</span></span></div>'
			);

			$sql_chunk_limit = 100;            

			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products', 'posts' => $wpdb->prefix . 'posts', 'postmeta' => $wpdb->prefix . 'postmeta');

			$amz_imgpath = $this->the_plugin->get_amazon_images_path();
			$ebay_imgpath = $this->the_plugin->get_ebay_images_path();
			$provider_imgpath = $amz_imgpath . '|' . $ebay_imgpath;

			$ids = array(); $nbprods = 0; $nbprods_del = 0; $nbmetas_del = 0;

			/*
			select
			# 	count(p.ID) as nbfound
				p.*
			#	p.ID, p.guid, p.post_title
			 from wp_posts as p
				left join wp_posts as p2 on p.post_parent = p2.ID
				left join wp_postmeta as pm on ( p.ID = pm.post_id and pm.meta_key = '_wp_attached_file' )
			 where 1=1
				and isnull(p2.ID)
				and p.post_type = 'attachment'
				and p.post_mime_type regexp 'image'
				and (
					#\/product\/|attachment_id
					p.guid regexp '\/product\/'
					or
					pm.meta_value regexp 'images-amazon.'
				)
			 order by p.ID ASC;
			*/
			$sql = "
			select
				p.ID
			from " . $wpdb->posts . " as p
				left join " . $wpdb->posts . " as p2 on p.post_parent = p2.ID
				left join " . $wpdb->postmeta . " as pm on ( p.ID = pm.post_id and pm.meta_key = '_wp_attached_file' )
			where 1=1
				and isnull(p2.ID)
				and p.post_type = 'attachment'
				and p.post_mime_type regexp 'image'
				and (
					p.guid regexp '\/product\/'
					or
					pm.meta_value regexp '" . $provider_imgpath . "' 
				)
			order by p.ID ASC;
			";
			//var_dump('<pre>',$sql,'</pre>');
			$res = $wpdb->get_results( $sql, OBJECT_K );
			if ( $res && is_array($res) ) {
				$ids = array_keys( $res );
				$nbprods = count( $ids );
			}
	 
			if ( $nbprods > 0 ) {
				// clean posts from wp_posts
				$sql_del = "
				delete
					p
				from " . $wpdb->posts . " as p
					left join " . $wpdb->posts . " as p2 on p.post_parent = p2.ID
					left join " . $wpdb->postmeta . " as pm on ( p.ID = pm.post_id and pm.meta_key = '_wp_attached_file' )
				where 1=1
					and isnull(p2.ID)
					and p.post_type = 'attachment'
					and p.post_mime_type regexp 'image'
					and (
						p.guid regexp '\/product\/'
						or
						pm.meta_value regexp '" . $provider_imgpath . "' 
					);
				";
				//var_dump('<pre>',$sql_del,'</pre>');
				$nbprods_del = (int) $wpdb->query( $sql_del );

				// clean metas from wp_postmeta
				$nbmetas_del = array();
				foreach (array_chunk($ids, $sql_chunk_limit, true) as $current) {
	
					$currentP = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $current));
	
					if (1) {
						$sql_ = "delete pm from " . $wpdb->postmeta . " as pm where 1=1 and pm.post_id IN ($currentP);";
						//var_dump('<pre>',$sql_,'</pre>');
						$res_ = $wpdb->query( $sql_ );
						//$res_ = rand(10, 50); //debugging purpose...
						$nbmetas_del[] = (int) $res_;
					}
				}
				$nbmetas_del = (int) array_sum( $nbmetas_del );
			}
			//var_dump('<pre>', $nbprods, $nbprods_del, $nbmetas_del, '</pre>'); die('debug...');

			$ret['status'] = 'valid';
			$ret['msg_html'] = sprintf( $ret['msg_html'], $nbprods, $nbprods_del, $nbmetas_del );
			
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		public function fix_product_attributes_all( $retType='die' ) {
			global $wpdb;
			
			$ret = array(
				'status'		=> 'valid',
				'msg_html'		=> array(), 
			);
			
			$themetas = array('_product_attributes', '_product_version');
			foreach ($themetas as $themeta) { // foreach metas

				$q = "select * from $wpdb->postmeta as pm where 1=1 and meta_key regexp '$themeta' and post_id in ( select p.ID from $wpdb->posts as p left join $wpdb->postmeta as pm2 on p.ID = pm2.post_id where 1=1 and pm2.meta_key='_amzASIN' and !isnull(p.ID) and p.post_type in ('product') );";
				$res = $wpdb->get_results( $q );
				if ( !$res || !is_array($res) ) {
					//$ret['status'] = 'valid';
					if ( !is_array($res) ) {
						$ret['msg_html'][] = sprintf( __('%s fix: no products needed attributes fixing!', $this->the_plugin->localizationName), $themeta );
					} else {
						$ret['msg_html'][] = sprintf( __('%s fix: cannot retrieve products for attributes fixing!', $this->the_plugin->localizationName), $themeta );
					}
					//if ( $retType == 'die' ) die(json_encode($ret));
					//else return $ret;
				}
				else {
					$upd = 0;
					foreach ($res as $key => $value) {
						if ( '_product_attributes' == $themeta ) {
							$__ = maybe_unserialize($value->meta_value);
							$__ = maybe_unserialize($__);
							
							// execution/ update
							//$__ = serialize($__);
							//$q_upd = "UPDATE $wpdb->postmeta AS pm SET pm.meta_value = '%s' WHERE 1=1 AND pm.meta_id = %s;";
							//$q_upd = sprintf( $q_upd, $__, $value->meta_id );
							//$res_upd = $wpdb->query( $q_upd );
							
							$__orig = $__;
							if ( !empty($__) && is_array($__) ) {
								foreach ($__ as $k => $v) {
									if ( isset($v['is_visible'], $v['is_variation'], $v['is_taxonomy']) ) {
										if ( ($v['is_visible'] == '1') && ($v['is_variation'] == '1') && ($v['is_taxonomy'] == '1') ) {
											$__["$k"]['value'] = '';
										}
									}
								}
							}
			  
							$res_upd = update_post_meta($value->post_id, $themeta, $__);
							add_post_meta($value->post_id, '_amzaff_orig'.$themeta, $__orig, true);
							if ( !empty($res_upd) ) $upd++;
						}
						else {
							$__ = $this->the_plugin->force_woocommerce_product_version($value->meta_value, '2.4.0', '9.9.9');
							
							$res_upd = update_post_meta($value->post_id, $themeta, $__);
							if ( !empty($res_upd) ) $upd++;
						}
					}
					
					//$ret['status'] = 'valid';
					$ret['msg_html'][] = sprintf( __('%s fix: %s products needed attributes fixing!', $this->the_plugin->localizationName), $themeta, $upd );
				}
			} // end foreach themetas

			$ret['msg_html'] = implode('<br />', $ret['msg_html']);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		public function fix_node_childrens( $retType='die' ) {
			global $wpdb;
			
			$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';
   
			$ret = array(
				'status'		=> 'valid',
				'msg_html'		=> array(), 
			);
			
			if ( 'fix_node_childrens' == $action ) {
				$sql = "DELETE FROM $wpdb->options WHERE option_name LIKE 'WooZoneLite_node_children_%';";  
				$query = $wpdb->query($sql);

				//WooZoneLite_ebay_EBAY-IT_node_children_-1
				$sql = "DELETE FROM $wpdb->options WHERE option_name LIKE 'WooZoneLite_ebay_EBAY-%';";  
				$query = $wpdb->query($sql);
				
				$ret['msg_html'][] = 'Operation executed successfully.';
			}
			
			$ret['msg_html'] = implode('<br />', $ret['msg_html']);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		public function fix_issues( $retType='die' ) {
			global $wpdb, $WooZoneLite;
   
			$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';
   
			$ret = array(
				'status'		=> 'valid',
				'msg_html'		=> array(), 
			);
			
			if ( 'fix_issue_request_amazon' == $action ) {
				delete_option('WooZoneLite_insane_last_reports');
				$ret['msg_html'][] = 'Operation executed successfully.';
			}

			if ( 'unblock_cron' == $action ) {
				$cron_class = new WooZoneLiteCronjobs($this->the_plugin);
				
				$unblock = $cron_class->run('unblock_crons');
				
				$ret['msg_html'][] = is_array($unblock) ? $unblock['status'] : $unblock;
			}

			if ( 'options_prefix_change' == $action ) {

				$what = isset($_REQUEST['what']) ? $_REQUEST['what'] : '';

				// update cronjobs prefix
				$this->the_plugin->update_cronjobs();
   
				// update options prefix
				if ( 'use_new' == $what ) {
					$opStat = $this->the_plugin->update_options_prefix( 'use_new' );
				}
				else { // use_old
					$opStat = $this->the_plugin->update_options_prefix( 'use_old' );
				}
				$ret['msg_html'][] = $opStat['msg'];
			}

			if ( 'reset_products_stats' == $action ) {

				$tposts = $wpdb->posts;
				$tpostmeta = $wpdb->postmeta;

				$queries = array(
					"delete from $tpostmeta where 1=1 and meta_key in ('_amzaff_hits', '_amzaff_addtocart', '_amzaff_redirect_to_amazon');",
					"delete from $tpostmeta where 1=1 and meta_key in ('_amzaff_hits_prev', '_amzaff_addtocart_prev', '_amzaff_redirect_to_amazon_prev');",
				);
				$stat = 0;
				foreach ($queries as $query) {
					$stat += $wpdb->query( $query );
				}

				$ret['msg_html'][] = sprintf(
					'Deleted: %s postmetas.',
					$stat
				);
			}
			
			if ( 'sync_restore_status' == $action ) {

				$what = isset($_REQUEST['what']) ? $_REQUEST['what'] : '';
				$opStat = $this->issue_sync_restore( $what );

				$html = array();				
				if ( empty($what) || 'verify' == $what ) {
					$html[] = sprintf(
						'Found: %s products, %s product variations.',
						$opStat['prods']['parents'],
						$opStat['prods']['variations']
					);

					//if ( ! empty($opStat['prods']['parents']) && ! empty($opStat['prods']['variations']) ) {
					if ( ! empty($opStat['prods']['parents']) ) {
						$html[] = '&nbsp;&nbsp;';
						$html[] = '<input type="button" class="WooZoneLite-form-button-small WooZoneLite-form-button-primary" style="height: 3.8rem; background-color: #2980b9;" id="fix_issue_sync-fix_now_doit" value="' . ( __('DO IT', $WooZoneLite->localizationName) ) . '">';
						$html[] = '&nbsp;&nbsp;';
						$html[] = '<input type="button" class="WooZoneLite-form-button-small WooZoneLite-form-button-primary" style="height: 3.8rem; background-color: #c0392b;" id="fix_issue_sync-fix_now_cancel" value="' . ( __('Cancel', $WooZoneLite->localizationName) ) . '">';
					}
				}
				else {
					$html[] = sprintf(
						'Updated: %s products, %s product variations.',
						$opStat['prods']['parents'],
						$opStat['prods']['variations']
					);
				}
				
				$ret['msg_html'][] = implode('', $html);
			}

			if ( 'reset_sync_stats' == $action ) {

				$what = isset($_REQUEST['what']) ? $_REQUEST['what'] : '';

				//:: delete sync cycle options
				$optionsList = array(
					'WooZoneLite_sync_prod_notfound', // not used anymore: verified on 2018-march

					'WooZoneLite_sync_cycle_stats',
					'WooZoneLite_sync_last_updated_product',
					'WooZoneLite_sync_last_selected_product',
					'WooZoneLite_sync_first_updated_date',

					'WooZoneLite_sync_currentlist_last_product',
					'WooZoneLite_sync_currentlist_nb_products',
					'WooZoneLite_sync_currentlist_nb_parsed',
					'WooZoneLite_sync_currentlist_prod_trashed',
					'WooZoneLite_sync_currentlist_prod_trash_tries',
					'WooZoneLite_sync_last_bulk_code',
					'WooZoneLite_sync_fix_samebulk',

					'WooZoneLite_sync_witherror_last_updated_product',
					'WooZoneLite_sync_witherror_last_selected_product',
					'WooZoneLite_sync_witherror_nb_products',
					'WooZoneLite_sync_witherror_nb_parsed',
					'WooZoneLite_sync_witherror_tries',
					'WooZoneLite_sync_witherror_last_bulk_code',
					'WooZoneLite_sync_witherror_fix_samebulk',

					// new variable to save all sync stats: 2018-04
				);
				foreach ($optionsList as $opt_todel) {
					delete_option( $opt_todel );
				}

				//:: delete products meta related to a sync cycle
				$tposts = $wpdb->posts;
				$tpostmeta = $wpdb->postmeta;

				$metas_todel = array();
				if ( 'yes_all' == $what ) {
					$metas_todel = array(
						'_amzaff_sync_trash_tries',
						'_amzaff_sync_hits_prev',
						'_amzaff_sync_hits',
						'_amzaff_sync_last_date',
						'_amzaff_sync_last_status_msg',
						'_amzaff_sync_last_status',
						'_amzaff_sync_current_cycle',
					);
				}
				else if ( 'yes' == $what ) {
					$metas_todel = array(
						'_amzaff_sync_trash_tries',
						'_amzaff_sync_last_date',
					);
				}

				if ( empty($metas_todel) ) {
					$ret['msg_html'][] = 'You\'ve selected NO regarding reset sync stats for products, so no reset was made.';
				}
				else {
					$metas_todel2 = $metas_todel;
					$metas_todel2 = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $metas_todel2));

					$queries = array(
						"delete from $tpostmeta where 1=1 and meta_key in ($metas_todel2);",
					);
					$stat = 0;
					foreach ($queries as $query) {
						$stat += $wpdb->query( $query );
					}

					$ret['msg_html'][] = sprintf(
						'Deleted: %s postmetas.',
						$stat
					);
				}
			}

			$ret['msg_html'] = implode('<br />', $ret['msg_html']);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		public function issue_sync_restore( $what='' ) {
			global $wpdb;
			
			$ret = array(
				'status'		=> 'invalid',
				'prods'			=> array(
					'parents'		=> 0,
					'variations'	=> 0,
				),
			);
			
			$do_verify = empty($what) || 'verify' == $what ? true : false;
			if ( !$do_verify ) {
				$post_status = isset($_REQUEST['post_status']) ? $_REQUEST['post_status'] : 'publish';
			}
			
			$sql_chunk_limit = 1000;
			$tposts = $wpdb->posts;
			$tpostmeta = $wpdb->postmeta;
			
			// get parent products (from trash)
			$sql = "select p.ID from $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id where 1=1 and pm.meta_key='_amzASIN' and p.post_type = 'product' and p.post_status = 'trash' and !isnull(pm.post_id);";
			$res = $wpdb->get_results( $sql, OBJECT_K  );
			$ids = array_keys( $res );
			
			// get product variations (only childs, no parents) (from trash)
			$ids_childs = array();
			foreach (array_chunk($ids, $sql_chunk_limit, true) as $current) {

				$currentP = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $current));

				if ( $do_verify ) {
					$sql_ = "select p.ID from $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id where 1=1 and pm.meta_key='_amzASIN' and p.post_type = 'product_variation' and p.post_status = 'trash' and !isnull(pm.post_id) and p.post_parent > 0 and p.post_parent IN ($currentP);";
					$res_ = $wpdb->get_results( $sql_, OBJECT_K );
					$ids_childs = $ids_childs + $res_;
				}
				else {
					$sql_x = "select p.ID from $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id where 1=1 and pm.meta_key='_amzASIN' and p.post_type = 'product_variation' and p.post_status = 'trash' and !isnull(pm.post_id) and p.post_parent > 0 and p.post_parent IN ($currentP);";
					$res_x = $wpdb->get_results( $sql_x, OBJECT_K );

					$ids_childs_trash = array_keys( $res_x );
					$ids_childs_trash = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $ids_childs_trash));

					if ( ! empty($ids_childs_trash) ) {
						$ids_childs_trash = '0';
					}

					//reset _amzaff_sync_trash_tries
					$sql_trash = "update $tpostmeta as pm2 set pm2.meta_value = '0' where 1=1 and pm2.meta_key='_amzaff_sync_trash_tries' and pm2.post_id IN ($ids_childs_trash);";
					$wpdb->query( $sql_trash );

					//reset post status
					$sql_ = "update $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id set p.post_status = '$post_status' where 1=1 and pm.meta_key='_amzASIN' and p.post_type = 'product_variation' and p.post_status = 'trash' and !isnull(pm.post_id) and p.post_parent > 0 and p.post_parent IN ($currentP);";
					$res_ = $wpdb->query( $sql_ );
					$ids_childs[] = (int) $res_;
				}
			}
			
			if ( $do_verify ) {
				$ids_childs = array_keys( $ids_childs );
			}
			else {
				$ids_childs = (int) array_sum( $ids_childs );
			}
			
			if ( !$do_verify ) {
				//reset _amzaff_sync_trash_tries
				$ids_trash = $ids;
				$ids_trash = implode(',', array_map(array($this->the_plugin, 'prepareForInList'), $ids_trash));

				if ( ! empty($ids_trash) ) {
					$ids_trash = '0';
				}

				//reset _amzaff_sync_trash_tries
				$sql_trash = "update $tpostmeta as pm2 set pm2.meta_value = '0' where 1=1 and pm2.meta_key='_amzaff_sync_trash_tries' and pm2.post_id IN ($ids_trash);";
				$wpdb->query( $sql_trash );

				//reset post status
				$sql = "update $tposts as p left join $tpostmeta as pm on p.ID = pm.post_id set p.post_status = '$post_status' where 1=1 and pm.meta_key='_amzASIN' and p.post_type = 'product' and p.post_status = 'trash' and !isnull(pm.post_id);";
				$res = $wpdb->query( $sql );
				$ids = (int) $res;
			}
			//var_dump('<pre>', $ids, $ids_childs, '</pre>'); die('debug...');
			
			return array_merge($ret, array(
				'prods' => array(
					'parents'		=> $do_verify ? count($ids) : $ids,
					'variations'	=> $do_verify ? count($ids_childs) : $ids_childs,
				)
			));
		}

		// new version: from 2017-04-28
		public function delete_zeropriced_products_all( $retType='die' ) {
			global $wpdb;
			
			@ini_set('memory_limit', '512M');
			@ini_set('max_execution_time', 0);
			@set_time_limit(0); // infinte

			$ret = array(
				'status'			=> 'invalid',
				'html'			=> '',
				'nb_total'		=> 0,
				'nb_done'		=> 0,
				'nb_remained' => 0,
			);

			$query = "
select p.ID from {$wpdb->posts} as p
	left join {$wpdb->postmeta} as pm on p.ID = pm.post_id
	left join {$wpdb->postmeta} as pm2 on p.ID = pm2.post_id
	left join {$wpdb->postmeta} as pm3 on p.ID = pm3.post_id
	where 1=1
		and p.post_type = 'product' and p.post_status != 'trash'
		and ( pm.meta_key = '_amzASIN' and pm.meta_value != '' )
		and ( pm2.meta_key = '_regular_price' and pm2.meta_value = '' )
		and ( pm3.meta_key = '_price' and pm3.meta_value = '' )
	order by p.ID asc
;
			";
			$res = $wpdb->get_results( $query, OBJECT_K );
			//var_dump('<pre>', $res, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$query2 = "
select p.ID from {$wpdb->posts} as p
	left join {$wpdb->postmeta} as pm on p.ID = pm.post_id
	left join {$wpdb->postmeta} as pm2 on p.ID = pm2.post_id
	left join {$wpdb->postmeta} as pm3 on p.ID = pm3.post_id
	where 1=1
		and p.post_type = 'product' and p.post_status != 'trash'
		and ( pm.meta_key = '_amzaff_prodid' and pm.meta_value != '' )
		and ( pm2.meta_key = '_regular_price' and pm2.meta_value = '' )
		and ( pm3.meta_key = '_price' and pm3.meta_value = '' )
	order by p.ID asc
;
			";
			$res2 = $wpdb->get_results( $query2, OBJECT_K );
			//var_dump('<pre>', $res2, '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if ( ! empty($res2) ) {
				$res = $res + $res2;
			}

			$ret['nb_total'] = count($res);

			$cc = 0;
			foreach ($res as $post_id => $val) {
				if ( $this->the_plugin->products_force_delete ) {
					wp_delete_post( $post_id, true );
				}
				else {
					wp_trash_post( $post_id );
				}
				$cc++;
				if ( $cc >= 10 ) break 1;
			}

			$ret['nb_done'] = $cc;
			$ret['nb_remained'] = (int) ( $ret['nb_total'] - $ret['nb_done'] );
			$ret['nb_remained'] = $ret['nb_remained'] >= 0 ? $ret['nb_remained'] : 0;

			$ret['status'] = 'valid';
			if( ! $cc ) {
				$ret['msg_html'] = 'No zero priced posts found.';
			} else {
				$ret['msg_html'] = sprintf( '<strong>%s</strong> posts moved to trash! <strong>%s</strong> posts remained to be moved to trash.', $ret['nb_done'], $ret['nb_remained'] );
			}

			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		// old version
		public function delete_zeropriced_products_all__( $retType='die' ) {
			$ret = array();
			$args = array();
			$args['post_type'] = 'product';

			$args['meta_key'] = '_amzASIN';
			$args['meta_value'] = '';
			$args['meta_compare'] = '!=';

			// show all posts
			//$args['fields'] = 'ids';
			$args['posts_per_page'] = '-1';

			$loop = new WP_Query( $args );
			$cc = 0;
			$ret = array();
			while ( $loop->have_posts() ) : $loop->the_post();
				global $post;

				$post = (int) $post->ID;

				$sale_price = get_post_meta( $post, '_sale_price', true );
				$regular_price = get_post_meta( $post, '_regular_price', true );    
				$price = get_post_meta( $post, '_price', true );
			
				if( $regular_price == '' && $price == '' ){
					$cc++;
					//if regular price is not set or it`s zero, put the post into trash 
					if ( $this->the_plugin->products_force_delete ) {
						wp_delete_post( $post, true );
					}
					else {
						wp_trash_post( $post );
					}
				}
			endwhile;

			$ret['status'] = 'valid';
			if( $cc == 0 ) {
				$ret['msg_html'] = 'No zero priced posts found.';
			} else {
				$ret['msg_html'] = $cc.' posts moved to trash!';
			}

			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}


		//================================================================================
		// Attributes clean duplicate
		public function attrclean_getDuplicateList() {
			global $wpdb;

			// $q = "SELECT COUNT(a.term_id) AS nb, a.name, a.slug FROM {$wpdb->terms} AS a WHERE 1=1 GROUP BY a.name HAVING nb > 1;";
			$q = "SELECT COUNT(a.term_id) AS nb, a.name, a.slug, b.term_taxonomy_id, b.taxonomy, b.count FROM {$wpdb->terms} AS a
 LEFT JOIN {$wpdb->term_taxonomy} AS b ON a.term_id = b.term_id
 WHERE 1=1 AND b.taxonomy REGEXP '^pa_' GROUP BY a.name, b.taxonomy HAVING nb > 1
 ORDER BY a.name ASC
;";
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			foreach ($res as $key => $value) {
				$name = $value->name;
				$taxonomy = $value->taxonomy;
				$ret["$name@@$taxonomy"] = $value;
			}
			return $ret;
		}
		
		public function attrclean_getTermPerDuplicate( $term_name, $taxonomy ) {
			global $wpdb;
			
			$q = "SELECT a.term_id, a.name, a.slug, b.term_taxonomy_id, b.taxonomy, b.count FROM {$wpdb->terms} AS a
 LEFT JOIN {$wpdb->term_taxonomy} AS b ON a.term_id = b.term_id
 WHERE 1=1 AND a.name=%s AND b.taxonomy=%s ORDER BY a.slug ASC;";
			$q = $wpdb->prepare( $q, $term_name, $taxonomy );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			foreach ($res as $key => $value) {
				$ret[$value->term_taxonomy_id] = $value;
			}
			return $ret;
		}
		
		public function attrclean_removeDuplicate( $first_term, $terms=array(), $debug = false ) {
			if ( empty($terms) || !is_array($terms) ) return false;

			$term_id = array();
			$term_taxonomy_id = array();
			foreach ($terms as $k => $v) {
				$term_id[] = $v->term_id;
				$term_taxonomy_id[] = $v->term_taxonomy_id;
				$taxonomy = $v->taxonomy;
			}
			// var_dump('<pre>',$first_term, $term_id, $term_taxonomy_id, $taxonomy,'</pre>');  

			$ret = array();
			$ret['term_relationships'] = $this->attrclean_remove_term_relationships( $first_term, $term_taxonomy_id, $debug );
			$ret['terms'] = $this->attrclean_remove_terms( $term_id, $debug );
			$ret['term_taxonomy'] = $this->attrclean_remove_term_taxonomy( $term_taxonomy_id, $taxonomy, $debug );
			// var_dump('<pre>',$ret,'</pre>');  
			return $ret;
		}
		
		private function attrclean_remove_term_relationships( $first_term, $term_taxonomy_id, $debug = false ) {
			global $wpdb;
			
			$idList = (is_array($term_taxonomy_id) && count($term_taxonomy_id)>0 ? implode(', ', array_map(array($this->the_plugin, 'prepareForInList'), $term_taxonomy_id)) : 0);

			if ( $debug ) {
			$q = "SELECT a.object_id, a.term_taxonomy_id FROM {$wpdb->term_relationships} AS a
 WHERE 1=1 AND a.term_taxonomy_id IN (%s) ORDER BY a.object_id ASC, a.term_taxonomy_id;";
			$q = sprintf( $q, $idList );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			$ret[] = 'object_id, term_taxonomy_id';
			foreach ($res as $key => $value) {
				$term_taxonomy_id = $value->term_taxonomy_id;
				$ret["$term_taxonomy_id"] = $value;
			}
			return $ret;
			}
			
			// execution/ update
			$q = "UPDATE {$wpdb->term_relationships} AS a SET a.term_taxonomy_id = '%s' 
 WHERE 1=1 AND a.term_taxonomy_id IN (%s);";
			$q = sprintf( $q, $first_term, $idList );
			$res = $wpdb->query( $q );
			$ret = $res;
			return $ret;
		}
		
		private function attrclean_remove_terms( $term_id, $debug = false ) {
			global $wpdb;
			
			$idList = (is_array($term_id) && count($term_id)>0 ? implode(', ', array_map(array($this->the_plugin, 'prepareForInList'), $term_id)) : 0);

			if ( $debug ) {
			$q = "SELECT a.term_id, a.name FROM {$wpdb->terms} AS a
 WHERE 1=1 AND a.term_id IN (%s) ORDER BY a.name ASC;";
			$q = sprintf( $q, $idList );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			$ret[] = 'term_id, name';
			foreach ($res as $key => $value) {
				$term_id = $value->term_id;
				$ret["$term_id"] = $value;
			}
			return $ret;
			}
			
			// execution/ update
			$q = "DELETE FROM a USING {$wpdb->terms} as a WHERE 1=1 AND a.term_id IN (%s);";
			$q = sprintf( $q, $idList );
			$res = $wpdb->query( $q );
			$ret = $res;
			return $ret;
		}
		
		private function attrclean_remove_term_taxonomy( $term_taxonomy_id, $taxonomy, $debug = false ) {
			global $wpdb;
			
			$idList = (is_array($term_taxonomy_id) && count($term_taxonomy_id)>0 ? implode(', ', array_map(array($this->the_plugin, 'prepareForInList'), $term_taxonomy_id)) : 0);

			if ( $debug ) {
			$q = "SELECT a.term_id, a.taxonomy, a.term_taxonomy_id FROM {$wpdb->term_taxonomy} AS a
 WHERE 1=1 AND a.term_taxonomy_id IN (%s) AND a.taxonomy = '%s' ORDER BY a.term_taxonomy_id ASC;";
			$q = sprintf( $q, $idList, esc_sql($taxonomy) );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;

			$ret = array();
			$ret[] = 'term_id, taxonomy, term_taxonomy_id';
			foreach ($res as $key => $value) {
				$term_taxonomy_id = $value->term_taxonomy_id;
				$ret["$term_taxonomy_id"] = $value;
			}
			return $ret;
			}

			// execution/ update
			$q = "DELETE FROM a USING {$wpdb->term_taxonomy} as a WHERE 1=1 AND a.term_taxonomy_id IN (%s) AND a.taxonomy = '%s';";
			$q = sprintf( $q, $idList, $taxonomy );
			$res = $wpdb->query( $q );
			$ret = $res;
			return $ret;
		}
		
		public function log_tables_clean( $tables, $clean_option ) {
			global $wpdb, $WooZoneLite;
			 
			$tables = explode(',', $tables);
			
			foreach ( $tables as $table ) {
				$table_size = $WooZoneLite->WooZoneLite_show_table_status( $table );
				if( $clean_option == 'clear_all' ) {
					$wpdb->query('DELETE FROM ' . $wpdb->prefix . $table);
					$ret['msg_html'] = 'All logs have been cleared successfully! New table <b>' . $wpdb->prefix . $table . '</b> size is: ' . $table_size;
				} else if ( $clean_option == 'clear_but_keep_1w' ) {
					$wpdb->query('DELETE FROM ' . $wpdb->prefix . $table . ' WHERE date_add < DATE_SUB(NOW(),INTERVAL 1 WEEK)');
					$ret['msg_html'] = 'All logs older than 1 week have been cleared successfully! New table <b>' . $wpdb->prefix . $table . '</b> size is: ' . $table_size;
				} else if ( $clean_option == 'clear_but_keep_1m' ) {
					$wpdb->query('DELETE FROM ' . $wpdb->prefix . $table . ' WHERE date_add < DATE_SUB(NOW(),INTERVAL 1 MONTH)');
					$ret['msg_html'] = 'All logs older than 1 month have been cleared successfully! New table <b>' . $wpdb->prefix . $table . '</b> size is: ' . $table_size;
				}
				
				//var_dump('<pre>',$query_log_entries ,'</pre>'); 
			}
			
			$ret['status'] = 'valid';
			die(json_encode($ret));
		}

		public function attrclean_clean_all( $retType='die' ) {
			// :: get duplicates list
			$duplicates = $this->attrclean_getDuplicateList();
  
			if ( empty($duplicates) || !is_array($duplicates) ) {
				$ret['status'] = 'valid';
				$ret['msg_html'] = __('no duplicate terms found!', $this->the_plugin->localizationName);
				if ( $retType == 'die' ) die(json_encode($ret));
				else return $ret;
			}
			// html message
			$__duplicates = array();
			$__duplicates[] = '0 : name, slug, term_taxonomy_id, taxonomy, count';
			foreach ($duplicates as $key => $value) {
				$__duplicates[] = $value->name . ' : ' . implode(', ', (array) $value);
			}
			$ret['status'] = 'valid';
			$ret['msg_html'] = implode('<br />', $__duplicates);
			// if ( $retType == 'die' ) die(json_encode($ret));
			// else return $ret;

			// :: get terms per duplicate
			$__removeStat = array();
			$__terms = array();
			$__terms[] = '0 : term_id, name, slug, term_taxonomy_id, taxonomy, count';
			foreach ($duplicates as $key => $value) {
				$terms = $this->attrclean_getTermPerDuplicate( $value->name, $value->taxonomy );
				if ( empty($terms) || !is_array($terms) || count($terms) < 2 ) continue 1;

				$first_term = array_shift($terms);

				// html message
				foreach ($terms as $k => $v) {
					$__terms[] = $key . ' : ' . implode(', ', (array) $v);
				}

				// :: remove duplicate term
				$removeStat = $this->attrclean_removeDuplicate($first_term->term_id, $terms, false);
				
				// html message
				$__removeStat[] = '-------------------------------------- ' . $key;
				$__removeStat[] = '---- term kept';
				$__removeStat[] = 'term_id, term_taxonomy_id';
				$__removeStat[] = $first_term->term_id . ', ' . $first_term->term_taxonomy_id;
				foreach ($removeStat as $k => $v) {
					$__removeStat[] = '---- ' . $k;
					if ( !empty($v) && is_array($v) ) {
						foreach ($v as $k2 => $v2) {
							$__removeStat[] = implode(', ', (array) $v2);
						}
					} else if ( !is_array($v) ) {
						$__removeStat[] = (int) $v;
					} else {
						$__removeStat[] = 'empty!';
					}
				}
			}

			$ret['status'] = 'valid';
			$ret['msg_html'] = implode('<br />', $__removeStat);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}



		//================================================================================
		// Remote amazon images
		public function build_remote_images( $post_id ) {
			global $wpdb;
			
			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');

			$ret = array(
				'status'        => 'valid',
				'msg'           => '',
				'nb_found'      => 0,
				'nb_parsed'     => 0,
				'nb_remote_err' => 0,
			);

			$this->the_plugin->timer_start(); // Start Timer

			// get rows from assets table
			//$assetsList = $this->the_plugin->get_ws_object( 'generic' )->get_asset_by_postid( 'all', $post_id, true, true );
			$assetsList = $this->get_asset_by_postid( 'all', $post_id, true, true );
			if ( count($assetsList) <= 0 ) {
				$status = 'invalid';
				return array_merge($ret, array(
					'status'    => $status,
					'msg'       => $status . ': no images found (for remote).',
				));
			}
			//var_dump('<pre>', $assetsList , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			global $wpdb;
			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products', 'posts' => $wpdb->prefix . 'posts');

			$ret['nb_found'] = count($assetsList);

			//:: images supposed to be remote, but have an external url which we cannot identify (not amazon/ebay)
			$nb_remote_err = 0;

			$assetsIds = array();
			foreach ($assetsList as $k => $asset) {

				$asset_id = $asset->id;
				$assetsIds[] = $asset_id;

				$image_path  = isset($asset->asset) ? $asset->asset : '';
				//if ( empty($image_path) ) return false;
				$provider = $this->the_plugin->imagesfix->is_image_remote( $image_path );
				if ( false === $provider ) {
					$nb_remote_err++;
				}
			}

			if ( $nb_remote_err ) {

				$idList = implode(', ', array_map(array($this->the_plugin, 'prepareForInList'), $assetsIds));
				$qUpdStat = "update " . $tables['assets'] . " as a set a.download_status = 'new' where 1=1 and a.id in ( $idList );";
				$statUpdStat = $wpdb->query($qUpdStat); //COMMENT TO DEBUG

				$status = 'invalid';
				return array_merge($ret, array(
					'status'    => $status,
					'nb_remote_err' => $nb_remote_err,
					'msg'       => sprintf( $status . ': %s product assets with url not recognizable from %s images found => all product images need to be downloaded on your server.', $nb_remote_err, $ret['nb_found'] ),
				));
			}

			//:: good remote images ready to be inserted
			$status = 'valid';
			foreach ($assetsList as $k => $asset) {

				$new_status = 'remote';

				$asset_id = $asset->id;

				$createStatus = $this->create_attachment( $asset );
				//var_dump('<pre>',$asset_id, $createStatus ,'</pre>');
				if ( $createStatus ) {
					$ret['nb_parsed']++;
				}

				// update row in assets table
				$statUpdAsset = $wpdb->update(
					$tables['assets'],
					array(
						'download_status'	=> $new_status,
						'msg'				=> $new_status
					),
					array( 'id' => $asset_id ),
					array(
						'%s', '%s'
					),
					array( '%d' )
				);
			}
			//var_dump('<pre>', $ret , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			//if ( ! $nb_remote_err ) {
				$this->the_plugin->add_last_imports('last_import_images_remote', array(
					'duration'      => $this->the_plugin->timer_end(),
					'nb_items'      => count($assetsList),
				)); // End Timer & Add Report
			//}

			return array_merge($ret, array(
				'status'    => $status,
				'nb_remote_err' => $nb_remote_err,
				'msg'       => sprintf( $status . ': %s product assets prepared in database (for remote) from %s images found.', $ret['nb_parsed'], $ret['nb_found'] ),
			));
		}

		// asset mandatory fields: post_id, asset, image_sizes
		public function create_attachment( $asset ) {
			// Add image in the media library
			$post_id	 = isset($asset->post_id) ? $asset->post_id : 0;
			$image_path  = isset($asset->asset) ? $asset->asset : '';
			if ( empty($post_id) || empty($image_path) ) return false;

			$post_parent = isset($asset->post_parent) ? $asset->post_parent : 0;
			$type = isset($asset->type) ? $asset->type : ''; // empty | variation | post

			$wp_filetype = wp_check_filetype( basename( $image_path ), null );

			$image_name = preg_replace( '/\.[^.]+$/', '', basename( $image_path ) );
			$rename_image = isset($this->amz_settings["rename_image"]) ? $this->amz_settings["rename_image"] : 'product_title';
			if ( 'product_title' == $rename_image ) {
				$image_name = isset($asset->title) && !empty($asset->title) ? $asset->title : $image_name;
			}
			//else {
			//	$image_name = uniqid();
			//}
			$image_name = sanitize_file_name($image_name);
			$image_name = preg_replace("/[^a-zA-Z0-9-]/", "", $image_name);
			$image_name = substr($image_name, 0, 200);

			$attachment = array(
				// 'guid' 			=> $image_url,
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => $image_name,
				'post_content'   => '',
				'post_status'    => 'inherit'
			);

			// insert row in wp_posts & insert meta_key '_wp_attached_file' in wp_postmeta
			$attach_id = wp_insert_attachment( $attachment, $image_path, $post_id  );
			//var_dump('<pre>',$attach_id ,'</pre>');
			//$attach_id = 2526; //DEBUG!
			if ( !$attach_id ) return false;
   
			// insert meta_key '_wp_attachment_metadata' in wp_postmeta
			$this->set_attachment_metadata( $attach_id, $asset );

			// build attachment parent metadata
			$dwimg = array(
				'attach_id' 		=> $attach_id,
				'image_path' 		=> $image_path,
				//'hash'				=> $hash
			);

			// product featured image
			//if ( $first_item ) {
			//	update_post_meta($post_id, "_thumbnail_id", $dwimg['attach_id']);
			//} else {
			$current_thumb_id = get_post_meta($post_id, "_thumbnail_id", true);
			if ( empty($current_thumb_id) ) $current_thumb_id = 0;
			else $current_thumb_id = (int) $current_thumb_id;
			if ( $current_thumb_id == 0 || ( $current_thumb_id > $dwimg['attach_id'] ) ) {
				update_post_meta($post_id, "_thumbnail_id", $dwimg['attach_id']);
				$current_thumb_id = $dwimg['attach_id'];
			}
			//}

			// product gallery
			$current_prod_gallery = get_post_meta($post_id, "_product_image_gallery", true);
			if ( empty($current_prod_gallery) ) $__current_prod_gallery = array();
			else $__current_prod_gallery = explode(',', $current_prod_gallery);
			$__current_prod_gallery = array_merge( $__current_prod_gallery, array($dwimg['attach_id']) );
			$__current_prod_gallery = array_unique($__current_prod_gallery);
			update_post_meta($post_id, "_product_image_gallery", implode(',', $__current_prod_gallery));

			// _AVI_additional_images meta
			if ( $this->the_plugin->is_plugin_avi_active() && ( 'variation' == $type ) ) {
				$current_avi = get_post_meta($post_id, "_AVI_additional_images", true);
				if ( empty($current_avi) || ! is_array($current_avi) ) $current_avi = array();
				$current_avi[] = $dwimg['attach_id'];
				foreach ( $current_avi as $kk => $vv ) {
					if ( (int) $current_thumb_id === (int) $vv ) {
						unset( $current_avi["$kk"] );
						break;
					}
				}
				$current_avi = array_unique( array_filter( $current_avi ) );
				update_post_meta( $post_id, "_AVI_additional_images", $current_avi );
			}

			return true;			
		}

		private function set_attachment_metadata( $attach_id, $asset ) {

			$image_path  = isset($asset->asset) ? $asset->asset : '';

			$image_sizes = isset($asset->image_sizes) ? maybe_unserialize($asset->image_sizes) : array();

			$provider = $this->the_plugin->imagesfix->is_image_remote( $image_path );


			if ( empty($image_sizes) || !is_array($image_sizes) ) {
				$image_sizes = array();

				if ( 'amazon' == $provider ) {
					// populate default sizes for current amazon image
					$image_sizes['large'] = array(
						'url'			=> $asset->asset,
						'width'			=> 500,
						'height'		=> 500,
					);
					$image_sizes['thumbnail'] = array(
						'url'			=> $asset->thumb,
						'width'			=> 45,
						'height'		=> 45,
					);
				}
			}

			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			//$attach_data = wp_generate_attachment_metadata( $attach_id, $image_path );

			$attach_data = array(
				'file'			=> 0,
				'width'			=> 0,
				'height'		=> 0,

				 // array( 'file', 'width', 'height', 'mime-type' )
				'sizes'			=> array(),

				'image_meta' 	=> array(
					'aperture' => '0',
					'credit' => '',
					'camera' => '',
					'caption' => '',
					'created_timestamp' => '0',
					'copyright' => '',
					'focal_length' => '0',
					'iso' => '0',
					'shutter_speed' => '0',
					'title' => '',
					'orientation' => '0',
					'keywords' => array (),
				),
			);

			$wp_sizes = $this->the_plugin->get_image_sizes_allowed();
			//$wp_sizes = $this->the_plugin->u->get_image_sizes();
			//var_dump('<pre>', $wp_sizes , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			// don't use high resolution image, when selecting the right amazon sizes
			$image_sizes_ = $image_sizes;
			if ( isset($image_sizes_['hires']) ) {
				unset( $image_sizes_['hires'] );
			}

			//:: make original image compatible with wp size format
			$sizeid = 'large'; //works for: amazon & ebay
			$original = $this->the_plugin->imagesfix->amazon_choose_image_original( $sizeid, $image_sizes_ );
			$original2 = $this->the_plugin->imagesfix->amazon_format_size_to_wp( $original, array(
				'only_image_name' => false,
				'find_mime_type' => false,
			));
			if ( ! empty($original2) && isset($original2['file']) && ! empty($original2['file']) ) {
				$attach_data = array_replace_recursive($attach_data, $original2);
			}

			//:: build all wp image sizes for our image
			$sizes_new = $this->the_plugin->imagesfix->build_amazon_image_sizes( array(
				'image_path' 	=> $image_path,
				'wp_sizes' 		=> $wp_sizes,
				'image_sizes' 	=> array(),
				'do_ebay_size' 	=> true,
			));
			$attach_data['sizes'] = array_replace_recursive( $attach_data['sizes'], $sizes_new );
			//var_dump('<pre>', $attach_data , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			//:: high resolution image
			if ( isset($image_sizes['hires']) ) {

				$found_size = $image_sizes['hires'];
				$wp_filetype = wp_check_filetype( basename( $found_size['url'] ), null );

				$attach_data['_wzone'] = array();
				$attach_data['_wzone']["amzhires_url"] = $found_size['url'];
				$attach_data['_wzone']["amzhires_size"] = array(
					'file'			=> basename( $found_size['url'] ),
					'width'			=> $found_size['width'],
					'height'		=> $found_size['height'],
					'mime-type'		=> $wp_filetype['type'],
				);
			}
			//var_dump('<pre>', $attach_data , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			// insert meta_key '_wp_attachment_metadata' in wp_postmeta
			wp_update_attachment_metadata( $attach_id, $attach_data );
		}
	}
}