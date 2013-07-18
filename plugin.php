<?php
/*
Plugin Name: WAX (Wordpress Application eXtensions)
*/

add_filter('wax_module_load_search_paths', function($paths) {
  $paths[] = dirname(__FILE__).'/modules';
  return $paths;
});

require('core/wax.php');
