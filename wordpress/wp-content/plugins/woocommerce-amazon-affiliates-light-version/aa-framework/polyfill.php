<?php
! defined( 'ABSPATH' ) and exit;

if ( !function_exists('array_replace_recursive') ) {
	function array_replace_recursive( $base, $replacements ) {
			foreach (array_slice(func_get_args(), 1) as $replacements) {
					$bref_stack = array(&$base);
					$head_stack = array($replacements);

					do {
							end($bref_stack);

							$bref = &$bref_stack[key($bref_stack)];
							$head = array_pop($head_stack);

							unset($bref_stack[key($bref_stack)]);

							foreach (array_keys($head) as $key) {
									if (isset($key, $bref, $bref[$key], $head[$key]) && is_array($bref[$key]) && is_array($head[$key])) {
											$bref_stack[] = &$bref[$key];
											$head_stack[] = $head[$key];
									} else {
											$bref[$key] = $head[$key];
									}
							}
					} while(count($head_stack));
			}

			return $base;
	}
}

// Polyfill for array_key_last() available from PHP 7.3
if ( !function_exists('array_key_last') ) {
	function array_key_last($array) {
		return array_slice(array_keys($array),-1)[0];
	}
}

// Polyfill for array_key_first() available from PHP 7.3
if ( !function_exists('array_key_first') ) {
	function array_key_first($array) {
		return array_slice(array_keys($array),0)[0];
	}
}

if ( !function_exists('array_value_last') ) {
	function array_value_last($array) {
		//return array_values(array_slice($array, -1))[0];
		$_ = array_values($array);
		return end( $_ );
	}
}

if ( !function_exists('array_value_first') ) {
	function array_value_first($array) {
		//return array_values(array_slice($array, 0))[0];
		$_ = array_values($array);
		return reset( $_ );
	}
}