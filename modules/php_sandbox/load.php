<?php

W::add_mixin('EvalPhpMixin');

add_filter('wax_eval_php', function($php, $vars) {
  $fname = tempnam(sys_get_temp_dir(), "wax_eval_php");
  file_put_contents($fname, $php);
  $s = W::php_sandbox($fname, $vars, true);
  return $s;
});

W::ensure_writable_folder($config['cache_fpath']);