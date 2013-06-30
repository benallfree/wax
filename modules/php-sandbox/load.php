<?

W::add_mixin('EvalPhpMixin');

W::register_filter('eval_php', function($php, $vars) {
  $fname = tempnam(sys_get_temp_dir(), "wicked_eval_php");
  file_put_contents($fname, $php);
  $s = W::php_sandbox($fname, $vars, true);
  return $s;
});

W::ensure_writable_folder($config['cache_fpath']);