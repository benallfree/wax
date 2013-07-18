<?
require_once('Mixin.class.php');
require_once('Mixable.class.php');
require_once('ModuleLoader.class.php');

class W extends Mixable
{
  static $config_defaults = array(
    'mixins'=>array(
      'ModuleLoader',
    )
  );
  
  static $root_fpath;
  static $root_vpath;
  static $lock_fp;
  
  static function init($config=array())
  {
    self::$root_fpath = WP_CONTENT_DIR.'/wax';
    self::$root_vpath = dirname($_SERVER['SCRIPT_NAME']);
    $config = array_merge(self::$config_defaults, $config);
    foreach($config['mixins'] as $class_name)
    {
      self::add_mixin($class_name);
    }
  } 

}