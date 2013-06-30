<?

require_once('WickedBase.class.php');

class Mixable extends WickedBase
{
  static $mixin_classes = array();
  
  static function export($mixin_name)
  {
    $methods = get_class_methods($mixin_name);
    $prefix = eval("return {$mixin_name}::\$__prefix;");
    foreach($methods as $m)
    {
      $f = $m;
      if($prefix)
      {
        $f = "{$prefix}_{$m}";
      }
      if(function_exists($f)) continue;
      $php = "
        function $m()
        {
          \$args = func_get_args();
          return call_user_func_array('W::$f', \$args);
        }
      ";
      eval($php);
    }
  }
  
  static function add_mixin($class_name)
  {
    self::$mixin_classes[] = $class_name;
    call_user_func("$class_name::init");
  }
  
  static function __callstatic($name, $args)
  {
    foreach(self::$mixin_classes as $class_name)
    {
      $prefix = $class_name::$__prefix;
      if(!$prefix || substr($name, 0, strlen($prefix)) == $prefix)
      {
        $internal_name = preg_replace("/^{$prefix}_/", '', $name);
        if(array_search($internal_name, get_class_methods($class_name))!==false)
        {
          return call_user_func_array(array($class_name, $internal_name), $args);
        }
      }
    }
    trigger_error('Wicked: Non-existent method was called in class '.__CLASS__.': '.$name, E_USER_ERROR);
  }
  
  
}
