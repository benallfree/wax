<?

W::add_mixin('HamlMixin');

W::register_filter('eval_haml', function($php, $vars=array()) {
  $fname = tempnam(sys_get_temp_dir(), "wicked_eval_haml");
  file_put_contents($fname, $php);
  $s = W::haml_eval_file($fname, $vars, true);
  return $s;
});