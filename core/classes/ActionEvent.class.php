<?
require_once('EventMixin.class.php');

class ActionEvent extends EventMixin
{
  static function register_action($action_name, $callback, $weight=10)
  {
    self::register_event('Action', $action_name, $callback, $weight);
  }

  static function action($action_name)
  {
    $args = func_get_args();
    array_unshift($args, 'Action');
    return call_user_func_array('parent::do_event', $args);
  }
}