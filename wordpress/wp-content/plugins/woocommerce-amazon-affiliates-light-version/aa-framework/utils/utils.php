<?php
/*
* Define class WooZoneLite_Utils
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLite_Utils') != true) {
	class WooZoneLite_Utils
	{
		/*
		 * Some required plugin information
		 */
		const VERSION = '1.0';

		static protected $_instance;

		/*
		 * Store some helpers config
		 */
		public $the_plugin = null;
		
	
		/*
		 * Required __construct() function that initalizes the AA-Team Framework
		 */
		public function __construct( $parent )
		{
			$this->the_plugin = $parent;
		}
		
		/**
		 * Singleton pattern
		 *
		 * @return Singleton instance
		 */
		static public function getInstance( $parent )
		{
			if (!self::$_instance) {
				self::$_instance = new self($parent);
			}
			
			return self::$_instance;
		}
		

		/**
		 * Cache
		 */
		//use cache to limits search accesses!
		public function needNewCache($filename, $cache_life) {
		
			// cache file needs refresh!
			if (($statCache = $this->isCacheRefresh($filename, $cache_life))===true || $statCache===0) {
				return true;
			}
			return false;
		}
		
		// verify cache refresh is necessary!
		public function isCacheRefresh($filename, $cache_life) {
			// cache file exists!
			if ($this->verifyFileExists($filename)) {
				$verify_time = time(); // in seconds
				$file_time = filemtime($filename); // in seconds
				$mins_diff = ($verify_time - $file_time) / 60; // in minutes
				if($mins_diff > $cache_life){
					// new cache is necessary!
					return true;
				}
				// cache is empty! => new cache is necessary!
				if (filesize($filename)<=0) return 0;
	
				// NO new cache!
				return false;
			}
			// cache file NOT exists! => new cache is necessary!
			return 0;
		}
	
		// write content to local cached file
		public function writeCacheFile($filename, $content, $use_lock=false) {
			$folder = dirname($filename);
			if ( empty($folder) || $folder == '.' || $folder == '/' ) return false;
  
			// cache folder!
			if ( !$this->makedir($folder) ) return false;
			if ( !is_writable($folder) ) return false;

			$has_wrote = false;
			if ( $use_lock ) {

				$fp = @fopen($filename, "wb");
				if ( @flock($fp, LOCK_EX, $wouldblock) ) { // do an exclusive lock
					$has_wrote = @fwrite($fp, $content);
					@flock($fp, LOCK_UN, $wouldblock); // release the lock
				}
				@fclose( $fp );
			} else {

				$wp_filesystem = $this->the_plugin->wp_filesystem;
				$has_wrote = $wp_filesystem->put_contents( $filename, $content );
				if ( !$has_wrote ) {
					$has_wrote = file_put_contents($filename, $content);
				}
			}
			return $has_wrote;
		}
	
		// cache file
		public function getCacheFile($filename) {
			if ($this->verifyFileExists($filename)) {
				
				$wp_filesystem = $this->the_plugin->wp_filesystem;
				$has_wrote = $wp_filesystem->get_contents( $filename );
				if ( !$has_wrote ) {
					$has_wrote = file_get_contents($filename);
				}
				$content = $has_wrote;
				return $content;
			}
			return false;
		}
		
		// delete cache
		public function deleteCache($filename) {
			if ($this->verifyFileExists($filename)) {
				return unlink($filename);
			}
			return false;
		}
	
		// verify if file exists!
		public function verifyFileExists($file, $type='file') {
			clearstatcache();
			if ($type=='file') {
				if (!file_exists($file) || !is_file($file) || !is_readable($file)) {
					return false;
				}
				return true;
			} else if ($type=='folder') {
				if (!is_dir($file) || !is_readable($file)) {
					return false;
				}
				return true;
			}
			// invalid type
			return 0;
		}
	
		// make a folder!
		public function makedir($fullpath) {
			clearstatcache();
			if(file_exists($fullpath) && is_dir($fullpath) && is_readable($fullpath)) {
				return true;
			}else{
				$stat1 = @mkdir($fullpath, 0777, true); // recursive
				$stat2 = @chmod($fullpath, 0777);
				if (!empty($stat1) && !empty($stat2))
					return true;
			}
			return false;
		}
		
		// get file name/ dot indicate if a .dot will be put in front of image extension, default is not
		public function fileName($fullname)
		{
			$return = substr($fullname, 0, strrpos($fullname, "."));
			return $return;
		}
	
		// get file extension
		public function fileExtension($fullname, $dot=false)
		{
			$return = "";;
			if( $dot == true ) $return .= ".";
			$return .= substr(strrchr($fullname, "."), 1);
			return $return;
		}
	
		public function append_contents( $filename, $contents, $mode = '0777' ) {
			$folder = dirname($filename);
			if ( empty($folder) || $folder == '.' || $folder == '/' ) return false;
  
			// cache folder!
			if ( !$this->makedir($folder) ) return false;
			if ( !is_writable($folder) ) return false;

			if ( !($fp = @fopen($filename, 'ab')) ) {
				return false;
			}
			$stat1 = @fwrite($fp, $contents);
			@fclose($fp);
			$stat2 = @chmod($filename, $mode);
			if (!empty($stat1) && !empty($stat2))
				return true;
			return false;
		}
		
		public function put_contents_gzip( $filename, $contents ) {
			if ( !function_exists('gzcompress') ) return false;
				
			//$gzip = @gzopen($filename, "w9");
			//if ( $gzip ){
			//    gzwrite($gzip, $contents);
			//    gzclose($gzip);
			//}
			
			$gzip = @fopen( $filename, 'w' );
			if ( $gzip ) {
				//$contents = @gzcompress($contents, 9); //zlib (http deflate)
				$contents = @gzencode($contents, 9); //gzip
				//$contents = @gzdeflate($contents, 1); //raw deflate encoding
				@fwrite($gzip, $contents);
				@fclose($gzip);
			}
	
			return true;
		}

		public function get_folder_files_recursive($path) {
			if ( !$this->verifyFileExists($path, 'folder') ) return 0;

			$size = 0;
			$ignore = array('.', '..', 'cgi-bin', '.DS_Store');
			$files = scandir($path);
  
			foreach ($files as $t) {
				if (in_array($t, $ignore)) continue;
				if (is_dir(rtrim($path, '/') . '/' . $t)) {
					$size += $this->get_folder_files_recursive(rtrim($path, '/') . '/' . $t);
				} else {
					$size++;
				}   
			}
			return $size;
		}
		
		public function createFile($filename, $content='') {
			$has_wrote = false;
			if ( $fp = @fopen($filename,'wb') ) {
				$has_wrote = @fwrite($fp, $content);
				@fclose($fp);
			}
			return $has_wrote;
		}

		public function filesize($path) {
			$size = filesize($path);
			$units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
			$power = $size > 0 ? floor(log($size, 1024)) : 0;
			return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
		}


		// Replace last occurance of a String
		public function str_replace_last( $search , $replace , $str ) {
			if ( ( $pos = strrpos( $str , $search ) ) !== false ) {
				$search_length  = strlen( $search );
				$str = substr_replace( $str, $replace, $pos, $search_length );
			}
			return $str;
		}

		// Replace first occurance of a String
		public function str_replace_first( $search, $replace, $str ) {
			$pos = strpos($str, $search);
			if ( $pos !== false ) {
				return substr_replace($str, $replace, $pos, strlen($search));
			}
			return $str;
		}
		
		/**
		 * Pretty-prints the difference in two times.
		 *
		 * @param time $older_date
		 * @param time $newer_date
		 * @return string The pretty time_since value
		 * @original link http://binarybonsai.com/code/timesince.txt
		 */
		public function time_since( $older_date, $newer_date ) {
			return $this->interval( $newer_date - $older_date );
		}
		public function interval( $since ) {

			if ( $since <= 0 ) {
				return __('now', $this->the_plugin->localizationName);
			}

			// array of time period chunks
			$chunks = array(
				array( 60 * 60 * 24 * 365 , _n_noop('%s year', '%s years', $this->the_plugin->localizationName)),
				array( 60 * 60 * 24 * 30 , _n_noop('%s month', '%s months', $this->the_plugin->localizationName)),
				array( 60 * 60 * 24 * 7, _n_noop('%s week', '%s weeks', $this->the_plugin->localizationName)),
				array( 60 * 60 * 24 , _n_noop('%s day', '%s days', $this->the_plugin->localizationName)),
				array( 60 * 60 , _n_noop('%s hour', '%s hours', $this->the_plugin->localizationName)),
				array( 60 , _n_noop('%s minute', '%s minutes', $this->the_plugin->localizationName)),
				array( 1 , _n_noop('%s second', '%s seconds', $this->the_plugin->localizationName)),
			);

			// we only want to output two chunks of time here, eg:
			// x years, xx months
			// x days, xx hours
			// so there's only two bits of calculation below:

			// step one: the first chunk
			for ($i = 0, $j = count($chunks); $i < $j; $i++) {
				$seconds = $chunks[$i][0];
				$name = $chunks[$i][1];
	
				// finding the biggest chunk (if the chunk fits, break)
				if (($count = floor($since / $seconds)) != 0) {
					break;
				}
			}

			// set output var
			$output = sprintf(_n($name[0], $name[1], $count, $this->the_plugin->localizationName), $count);

			// step two: the second chunk
			if ($i + 1 < $j) {
				$seconds2 = $chunks[$i + 1][0];
				$name2 = $chunks[$i + 1][1];
	
				if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
					// add to output var
					$output .= ' '.sprintf(_n($name2[0], $name2[1], $count2, $this->the_plugin->localizationName), $count2);
				}
			}

			return $output;
		}

		// $in = format for $since ( seconds | miliseconds | microseconds )
		// $since = integer representing the duration
		// $how_many = how many chunks to display (ex. 2 = x hours x1 minutes; ex. 3 =  = x hours x1 minutes x2 seconds)
		public function duration_pretty( $since, $in='miliseconds', $how_many=2 ) {

			if ( $since <= 0 ) {
				return __('now', $this->the_plugin->localizationName);
			}
			if ( ! in_array($in, array('seconds', 'miliseconds', 'microseconds')) ) {
				return __('WRONG FORMAT!', $this->the_plugin->localizationName);
			}

			$sec2sub = array(
				'seconds' => array( 1, '' ),
				'miliseconds' => array( 1000, 'mili' ),
				'microseconds' => array( pow(10, 6), 'micro' ),
			);
			$in_ = (int) $sec2sub["$in"][0];

			// array of time period chunks
			$chunks = array(
				array( 60 * 60 , _n_noop('%s hour', '%s hours', $this->the_plugin->localizationName)),
				array( 60 , _n_noop('%s minute', '%s minutes', $this->the_plugin->localizationName)),
				array( 1 , _n_noop('%s second', '%s seconds', $this->the_plugin->localizationName)),
			);
			if ( $in_ > 1 ) {
				foreach ( $chunks as $kk => $vv ) {
					$chunks["$kk"][0] = $in_ * $vv[0];
				}

				switch ($in) {
					case 'miliseconds':
						$chunks = array_merge( $chunks, array(
							array( 1 , _n_noop('%s milisecond', '%s miliseconds', $this->the_plugin->localizationName)),
						));
						break;

					case 'microseconds':
						$chunks = array_merge( $chunks, array(
							array( 1000 , _n_noop('%s milisecond', '%s miliseconds', $this->the_plugin->localizationName)),
							array( 1 , _n_noop('%s microsecond', '%s microseconds', $this->the_plugin->localizationName)),
						));
						break;
				}
			}

			// find the chunks to be displayed
			$since_ = $since;
			$output = array();
			for ($i = 0, $j = count($chunks); $i < $j; $i++) {
				$seconds = $chunks[$i][0];
				$name = $chunks[$i][1];
	
				// finding the biggest chunk (if the chunk fits, break)
				if (($count = floor($since_ / $seconds)) != 0) {
					// add to output var
					$output[] = sprintf(_n($name[0], $name[1], $count, $this->the_plugin->localizationName), $count);

					$since_ = $since_ - ($seconds * $count);
				}
			}

			// here we show $how_many chunks
			$output = array_splice($output, 0, $how_many);
			$output = implode(' ', $output);

			return $output;
		}
	


		/**
		 * List available image sizes with width and height following
		 */		
		/**
		 * Get size information for all currently-registered image sizes.
		 *
		 * @global $_wp_additional_image_sizes
		 * @uses   get_intermediate_image_sizes()
		 * @return array $sizes Data for all currently-registered image sizes.
		 */
		public function get_image_sizes() {
			global $_wp_additional_image_sizes;
		
			$sizes = array();
		
			foreach ( get_intermediate_image_sizes() as $_size ) {
				if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
					$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
					$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
					$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
				}
				elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
					$sizes[ $_size ] = array(
						'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
						'height' => $_wp_additional_image_sizes[ $_size ]['height'],
						'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
					);
				}
			}
			//var_dump('<pre>', $sizes , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			return $sizes;
		}
		
		/**
		 * Get size information for a specific image size.
		 *
		 * @uses   get_image_sizes()
		 * @param  string $size The image size for which to retrieve data.
		 * @return bool|array $size Size data about an image size or false if the size doesn't exist.
		 */
		public function get_image_size( $size ) {
			$sizes = get_image_sizes();
		
			if ( isset( $sizes[ $size ] ) ) {
				return $sizes[ $size ];
			}
		
			return false;
		}
		
		/**
		 * Get the width of a specific image size.
		 *
		 * @uses   get_image_size()
		 * @param  string $size The image size for which to retrieve data.
		 * @return bool|string $size Width of an image size or false if the size doesn't exist.
		 */
		public function get_image_width( $size ) {
			if ( ! $size = get_image_size( $size ) ) {
				return false;
			}
		
			if ( isset( $size['width'] ) ) {
				return $size['width'];
			}
		
			return false;
		}
		
		/**
		 * Get the height of a specific image size.
		 *
		 * @uses   get_image_size()
		 * @param  string $size The image size for which to retrieve data.
		 * @return bool|string $size Height of an image size or false if the size doesn't exist.
		 */
		public function get_image_height( $size ) {
			if ( ! $size = get_image_size( $size ) ) {
				return false;
			}
		
			if ( isset( $size['height'] ) ) {
				return $size['height'];
			}
		
			return false;
		}


		//=============================================
		// 2018-april
		public function is_ajax() {
			if ( defined( 'DOING_AJAX' ) and DOING_AJAX ) {
				return true;
			}
			return false;
		}

		public function is_async() {
			if ( $this->is_ajax() ) {
				return true;
			}
			if ( isset($_SERVER['HTTP_X_REQUESTED_WITH'])
				&& 'xmlhttprequest' === strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] )
			) {
				return true;
			}
			return false;
		}

		public function is_multi_network() {
			global $wpdb;

			if ( function_exists( 'is_multi_network' ) ) {
				return is_multi_network();
			}

			if ( ! is_multisite() ) {
				return false;
			}

			$num_sites = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->site}" );
			return $num_sites > 1;
		}



		//=============================================
		// strip_tags 2018-may

		public function strip_tags( $html, $allowed_tags=array() ) {
			$allowed_tags = array_map( 'strtolower', $allowed_tags );
			
			$rhtml = preg_replace_callback('/<\/?([^>\s]+)[^>]*>/i', function ($matches) use ( &$allowed_tags ) {
				return in_array( strtolower( $matches[1] ), $allowed_tags ) ? $matches[0] : '';
			}, $html);

			return $rhtml;
		}

		/**
		 * Removes passed tags with their content.
		 *
		 * @param array $tagsToRemove List of tags to remove
		 * @param $haystack String to cleanup
		 * @return string
		 */
		public function removeTagsWithTheirContent( array $tagsToRemove, $haystack ) {
			$currTag = '';
			$currPos = false;

			$initSearch = function (&$currTag, &$currPos, $tagsToRemove, $haystack) {
				$currTag = '';
				$currPos = false;
				foreach ($tagsToRemove as $tag) {
					$tempPos = stripos($haystack, '<'.$tag);
					if ($tempPos !== false && ($currPos === false || $tempPos < $currPos)) {
						$currPos = $tempPos;
						$currTag = $tag;
					}
				}
			};

			$substri_count = function ($haystack, $needle, $offset, $length) {
				$haystack = strtolower($haystack);
				return substr_count($haystack, $needle, $offset, $length);
			};

			$initSearch($currTag, $currPos, $tagsToRemove, $haystack);
			while ($currPos !== false) {
				$minTagLength = strlen($currTag) + 2;
				$tempPos = $currPos + $minTagLength;
				$tagEndPos = stripos($haystack, '</'.$currTag.'>', $tempPos);
				// process nested tags
				if ($tagEndPos !== false) {
					$nestedCount = $substri_count($haystack, '<' . $currTag, $tempPos, $tagEndPos - $tempPos);

					for ($i = $nestedCount; $i > 0; $i--) {
						$lastValidPos = $tagEndPos;
						$tagEndPos = stripos($haystack, '</' . $currTag . '>', $tagEndPos + 1);
						if ($tagEndPos === false) {
							$tagEndPos = $lastValidPos;
							break;
						}
					}
				}

				if ($tagEndPos === false) {
					// invalid html, end search for current tag
					$tagsToRemove = array_diff($tagsToRemove, array($currTag));
				} else {
					// remove current tag with its content
					$haystack = substr($haystack, 0, $currPos)
						// get string after "</$tag>"
						.substr($haystack, $tagEndPos + strlen($currTag) + 3);
				}

				$initSearch($currTag, $currPos, $tagsToRemove, $haystack);
			}

			return $haystack;
		}

		public function strip_tags_content($text, $tags = '', $invert = FALSE) {

			preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
			$tags = array_unique($tags[1]);

			if (is_array($tags) AND count($tags) > 0) {
				if ($invert == FALSE) {
					return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
				}
				else {
					return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
				}
			}
			else if ($invert == FALSE) {
				return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
			}
			return $text;
		}



		//=============================================
		// prices functions /float numbers

		/**
		 * Truncate a float number, example: <code>truncate(-1.49999, 2); // returns -1.49
		 * truncate(.49999, 3); // returns 0.499
		 * float $val Float number to be truncate
		 * int f Number of precision
		 * return float
		 */
		public function truncate($val, $f="0") {
			if ( ( $p = strpos($val, '.') ) !== false ) {
				$val = floatval( substr( $val, 0, $p + 1 + $f ) );
			}
			return $val;
		}

		// http://php.net/manual/en/function.round.php /Mojo urk solution
		public function round_down($value, $precision) {
			$value = (float) $value;
			$precision = (int) $precision;

			if ($precision < 0) {
				$precision = 0;
			}

			$decPointPosition = strpos($value, '.');
			if ($decPointPosition === false) {
				return $value;
			}
			return (float) ( substr($value, 0, $decPointPosition + $precision + 1) );
		}

		public function round_up($value, $precision) {
			$value = (float) $value;
			$precision = (int) $precision;

			if ($precision < 0) {
				$precision = 0;
			}

			$decPointPosition = strpos($value, '.');
			if ($decPointPosition === false) {
				return $value;
			}

			$floorValue = (float) ( substr($value, 0, $decPointPosition + $precision + 1) );
			$followingDecimals = (int) substr($value, $decPointPosition + $precision + 1);

			if ($followingDecimals) {
				$ceilValue = $floorValue + pow(10, -$precision); // does this give always right result?
			}
			else {
				$ceilValue = $floorValue;
			}
			return $ceilValue;
		}



		// gets the current post type in the WordPress Admin
		public function get_current_post_type() {
			global $post, $typenow, $current_screen, $pagenow;
			
			//we have a post so we can just get the post type from that
			if ( $post && $post->post_type ) {
				return $post->post_type;
			}
			//check the global $typenow - set in admin.php
			elseif ( $typenow ) {
				return $typenow;
			}
			//check the global $current_screen object - set in sceen.php
			elseif ( $current_screen && $current_screen->post_type ) {
				return $current_screen->post_type;
			}
			//check the post_type querystring
			elseif ( isset( $_REQUEST['post_type'] ) ) {
				return sanitize_key( $_REQUEST['post_type'] );
			}
			//lastly check if post ID is in query string
			elseif ( isset( $_REQUEST['post'] ) ) {
				return get_post_type( $_REQUEST['post'] );
			}
			else if ( $pagenow == 'edit.php' ) {
				$type = 'post';
			}
			//we do not know the post type!
			return null;
		}

		public function product_force_external( $post_id ) {
			delete_transient( "wc_product_type_$post_id" );
			set_transient( "wc_product_type_$post_id", 'external');

			wp_set_object_terms( $post_id, 'external', 'product_type' );
		}



		// Retrieve remote image dimensions - getimagesize alternative (it needs GD & CURL libraries)
		public function getimagesize( $url ) {
			$ret = array(
				'status' 	=> 'invalid',
				'msg' 		=> '',
				'size' 		=> array(0, 0),
			);

			//phpinfo(); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
			if( !(extension_loaded("curl") && function_exists('curl_init')) ) {
				$ret = array_replace_recursive( $ret, array(
					'msg' 	=> 'CURL library is not installed!',
				));
				return $ret;
			}
			if( !(extension_loaded("gd") && function_exists('imagecreatefromstring')) ) {
				$ret = array_replace_recursive( $ret, array(
					'msg' 	=> 'GD library is not installed!',
				));
				return $ret;
			}

			$input_params = array(
				'followlocation' 	=> true,
			);
			$output_params = array(
				//'parse_headers'                 => true,
				//'resp_is_json'                  => true,
				'resp_add_http_code'            => true,
			);
			$output = $this->the_plugin->curl( $url, $input_params, $output_params, true );
			//var_dump('<pre>', $output , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			if ( $output['status'] === 'invalid' || (int) $output['http_code'] !== 200 ) {
				$msg = sprintf( __('curl error; http code: %s; details: %s', 'woozonelite'), $output['http_code'], $output['data'] );
				//var_dump('<pre>', $msg , '</pre>'); echo __FILE__ . ":" . __LINE__; die . PHP_EOL;

				$ret = array_replace_recursive( $ret, array(
					'msg' 	=> $msg,
				));
				return $ret;
			}
			//var_dump('<pre>', $output , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;

			$data = $output['data'];

			// Process image
			$image = imagecreatefromstring( $data );
			$dims = array( imagesx( $image ), imagesy( $image ) );
			imagedestroy($image);

			$ret = array_replace_recursive( $ret, array(
				'status' 	=> 'valid',
				'size' 		=> $dims,
				'msg' 		=> 'successfully retrieved remote image size.',
			));
			return $ret;
		}

		/**
		 * Add or update a WordPress option.
		 * The option will _not_ auto-load.
		 *
		 * @param string $name
		 * @param mixed  $value
		 */
		// Add or update a WordPress option without auto loading it.
		public function add_or_update( $name, $value ) {
			$success = add_option( $name, $value, '', 'no' );

			if ( ! $success ) {
				$success = update_option( $name, $value );
			}

			return $success;
		}


		//====================================================================================
		//== Unicode UTF8
		public function utf8_trim( $text ) {

			$patt_repl = array(
				// like mb_trim() -- remove all leading and trailing whitespace and control characters. DO NOT ADD m FLAG TO PATTERN, THAT WILL DAMAGE THE STRING
				'/^[\s\pC]+|[\s\pC]+$/u' => '',

				// cleans 2 or more new lines (uninterupted by non-white space characters)
				'/[\s\pC]*?(\R)[\s\pC]*?(\R)[\s\pC]*/u' => "\n\n",

				// cleans single new lines
				'/(?!\R)[\h\pC]*\R[\h\pC]*(?!\R)/u' => "\n",

				// convert 1 or more non-newline white-spaces and control characters to single space
				'/\n+(*SKIP)(*FAIL)|[\h\pC]+/u' => ' '
			);

			$text = preg_replace( array_keys($patt_repl), $patt_repl, $text );
			$text = htmlspecialchars( $text, ENT_HTML5, 'UTF-8' );
			return $text;
		}

		public function utf8_trim_v2( $str ) {

			return preg_replace('/^[\pZ\pC]+([\PZ\PC]*)[\pZ\pC]+$/u', '$1', $str);
		}
	}
}