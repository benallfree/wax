<?php
W::add_mixin('DbMixin');

if($config['debug'])
{
  add_filter('query', function($sql) {
    dprint($sql);
    return $sql;
  });
}