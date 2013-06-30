<?
require('Mixin.class.php');

class EventMixin extends Mixin
{
  protected static  $events;
  static function init()
  {
    self::$events = array();
  }
  
  static function register_event($event_type, $event_name, $callback, $weight=10)
  {
    
    //if(is_string($callback) && !call_user_func_array('method_exists', explode('::',$callback))) W::error("Callback $callback does not exist. Did you spell it correctly?");
    self::ensure_key(self::$events, $event_type);
    self::ensure_key(self::$events[$event_type], $event_name);
    self::$events[$event_type][$event_name][] = array($callback, $weight);
    usort(self::$events[$event_type][$event_name], 'self::event_sort');
  }

  protected static function event_sort($a,$b)
  {
    if($a[1]<$b[1]) return -1;
    if($a[1]>$b[1]) return 1;
    return 0;
  }  
  
  static function do_event($event_type, $event_name)
  {
    $args = func_get_args();
    array_shift($args);
    array_shift($args);
    if(count($args)==0) $args=array(null);
    if(!isset(self::$events[$event_type])) return $args[0];
    if(!isset(self::$events[$event_type][$event_name])) return $args[0];
    $f = self::$events[$event_type][$event_name];
    foreach(self::$events[$event_type][$event_name] as $callback_info)
    {
      $args[0] = call_user_func_array($callback_info[0], $args);
    }
    return $args[0];
  }

}