<?
class Execer
{
  var $command;
  var $output;
  var $retval;
  function __construct($cmd)
  {
    $this->command = $cmd;
    $this->output = '';
    $this->retval = null;
  }
  
  function exec()
  {
    exec($this->command . " 2>&1", $out, $ret);
    $this->output = $out;
    $this->retval = $ret;
    return $this;
  }
}

class ExecMixin extends Mixin
{

  static function interpolate()
  {
    $args = func_get_args();
    $s = array_shift($args);
    foreach($args as $arg)
    {
      $s = preg_replace_callback("/([!?])/", function($matches) use ($arg) {
        if(count($matches)<=1) return;
        switch($matches[1])
        {
          case '?':
            return escapeshellarg($arg);
            break;
          case '!':
            return $arg;
            break;
          default:
            W::error("Unknown type $type in interpolate");
        }
        
      }, $s, 1);
    }
    return $s;
  }
  
  static function cmd_or_die($cmd, $expected_retval=0)
  {
    $args = func_get_args();
    array_shift($args); // cmd
    array_shift($args); // retval
    array_unshift($args, $cmd); // put cmd back
    $s = call_user_func_array('W::interpolate', $args);
    $exec = new Execer($s);
    $exec->exec();
    if($exec->retval!=$expected_retval)
    {
      W::error("Execution failed.", $exec, $exec->retval);
    }
    return $exec;
  }
  
  static function cmd($cmd)
  {
    $args = func_get_args();
    $s = call_user_func_array('W::interpolate', $args);
    $exec = new Execer($s);
    $exec->exec();
    return $exec;
  }
}