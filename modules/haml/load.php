<?php

W::add_mixin('HamlMixin');

add_filter('wax_eval_haml', function($php, $vars=array()) {
  $fname = tempnam(sys_get_temp_dir(), "wax_eval_haml");
  file_put_contents($fname, $php);
  $s = W::haml_eval_file($fname, $vars, true);
  return $s;
});