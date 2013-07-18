<?

// Adds autoloading class capability to Wicked
class ClassLazyLoaderMixin extends Mixin
{
  static $__prefix = 'lazyload';
  private static $autoloads = array();
  
  static function init()
  {
    add_action('wax_before_module_loaded', function($module_name, $config) {
      // Add autoloads
      $autoload_fpath = $config['fpath']."/classes";
      if(file_exists($autoload_fpath))
      {
        self::$autoloads[] = $autoload_fpath;
      }
    },10,2);
    spl_autoload_register('ClassLazyLoaderMixin::autoload');    
    parent::init();
  }
  
  static function add_path($fpath)
  {
    self::$autoloads[] = $fpath;
  }
  
  private static function autoload($class_name)
  {
    foreach(self::$autoloads as $fpath)
    {
      $fname = $fpath."/{$class_name}.class.php";
      if(file_exists($fname))
      {
        require($fname);
        return true;
      }
    }
    return false;
  }
  
}