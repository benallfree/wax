<?

require('UrlMixin.class.php');
W::ensure_writable_folder($config['cache_fpath']);
W::add_mixin('UrlMixin');
