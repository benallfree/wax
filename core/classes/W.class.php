<?
require_once('Mixable.class.php');
require_once('ActionEvent.class.php');
require_once('FilterEvent.class.php');
require_once('ModuleLoader.class.php');

class W extends Mixable
{
  static $config_defaults = array(
    'mixins'=>array(
      'ActionEvent',
      'FilterEvent',
      'ModuleLoader',
    )
  );
  
  static $root_fpath;
  static $root_vpath;
  static $lock_fp;
  
  static function init($config=array())
  {
    self::$root_fpath = realpath($_SERVER['SITE_HTMLROOT']);
    self::$root_vpath = '';
    self::$lock_fp = fopen(self::$root_fpath."/lock.txt", "c+");
    register_shutdown_function('W::shutdown');
    $config = array_merge(self::$config_defaults, $config);
    foreach($config['mixins'] as $class_name)
    {
      self::add_mixin($class_name);
    }
  } 
  
  static function readlock()
  {
    if(!flock(self::$lock_fp, LOCK_SH))
    {
      trigger_error("Failed to acquire shared lock");
    }
  }

  static function writelock()
  {
    if(!flock(self::$lock_fp, LOCK_EX))
    {
      trigger_error("Failed to acquire exclusive lock");
    }
    
  }
  
  static function unlock()
  {
    flock(self::$lock_fp, LOCK_UN);
  }
  
  static function shutdown()
  {
    self::unlock();
  }
}