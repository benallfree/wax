<?

class EvalPhpMixin extends Mixin
{
  static $__prefix = 'php';
  static $module = '';
  
  static function init()
  {
    self::$module = W::module('php_sandbox');
  }
  
  static function sandbox($__path__, $__data__=array(), $__capture__ = false)
  {
    if($__data__) extract($__data__, EXTR_REFS);
    if($__capture__) ob_start();
    W::readlock();
    $__res = require($__path__);
    W::unlock();
    if($__capture__)
    {
      $__capture__ = ob_get_contents();
      ob_end_clean();
      return $__capture__;
    }
  }
  
  static function sandbox_string($html, $data=array(), $capture=false)
  {
    $unique_name = sha1($html);
    $php_path = self::$module['cache_fpath']."/$unique_name.php";
    if(!file_exists($php_path))
    {
      W::writelock();
      $res = file_put_contents($php_path, $html);
      W::unlock();
      if(!$res) W::error("Error writing $php_path.");
    }
    return self::sandbox($php_path, $data, $capture);
  }
  
}