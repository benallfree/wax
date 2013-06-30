<?

class DebugMixin extends Mixin
{
  static $is_cli = false;
  
  static function init()
  {
    self::$is_cli = isset($_SERVER['SSH_CLIENT']);
  }
  
  static function debug_shutdown_handler() {
    $error = error_get_last();
    $config = W::module('debug');
    if($config['wicked_only'] && strpos($error['message'], 'Wicked')===false) return;
    if($error !== NULL)
    {
      self::debug_error_handler(0, 'SHUTDOWN ERROR: '.$error['message'], $error['file'], $error['line']);
    }
  }
  
  // error handler function
  static function debug_error_handler($errno, $errstr, $errfile, $errline)
  {
    $config = W::module('debug');

    if($config['wicked_only'] && strpos($errstr, 'Wicked')===false) return;
    
    if($config['should_display_errors'])
    {
      self::dprint($errstr . " in {$errfile}:{$errline}");
    }
    
    W::action('error', $errno, $errstr, $errfile, $errline);
  }
  
  static function debug_exception_handler($exception) {
    var_dump($exception);
    self::error( "Uncaught exception: " . $exception->getMessage());
  }

  static function toAscii($itemlist)
  {
    $i=0;
    foreach( $itemlist as $items)
    {
     foreach ($items as $key => $value)
     {
       if ($i++==0) // print header
       {
         printf("[%010s]|",   $key );
       }
       printf("[%010s]|",   $value);
     }
     echo "\n"; // don't forget the newline at the end of the row!
    }  
  }  
  
  static function dprint($s,$shouldExit=true)
  {
    if(!self::$is_cli) echo "\"><pre>";
    ob_start();
    var_dump($s);
    $out = ob_get_contents();
    ob_end_clean();
    if(self::$is_cli)
    {
      echo $out;
    } else {
      echo htmlentities($out,ENT_COMPAT,'UTF-8');
    }
    if(!self::$is_cli) echo "</pre>";
    if ($shouldExit) self::error('Development stop');
  }
  
  
  static function error($err, $data=null, $err_code=0)
  {
    $err = 'Wicked: '.$err;
    if ($data)
    {
      if(self::$is_cli)
      {
        $err .= W::s_var_export($data);
      } else {
        $err = $err."<br/><pre>".htmlentities(W::s_var_export($data))."</pre>";
      }
    }
    if(!self::$is_cli)
    {
      echo( "\"><table>");
      echo ("<tr>");
      echo("<td>");
      echo($err);
      echo("</td>");
      echo("</tr>");
    }
    foreach(debug_backtrace() as $trace)
    {
      if(!self::$is_cli)
      {
        echo( "<tr>");
        echo( "<td>");
      }
      if (array_key_exists('file', $trace)) echo( htmlentities($trace['file']));
      if(!self::$is_cli)
      {
        echo( "</td>");
        echo( "<td>");
      } else {
        echo "\t";
      }
      if (array_key_exists('line', $trace)) echo( htmlentities($trace['line']));
      if(!self::$is_cli)
      {
        echo( "</td>");
        echo( "<td>");
      } else {
        echo "\t";
      }
      if (array_key_exists('static function', $trace)) echo( htmlentities($trace['static function']));
      if(!self::$is_cli)
      {
        echo( "</td>");
        echo( "</tr>");
      } else {
        echo "\n";
      }
    }
    if(!self::$is_cli) echo( "</table>");
    exit($err_code);
    //trigger_error($err, E_USER_ERROR);
  }
  
  static function s_var_export($v)
  {
    ob_start();
    var_export($v);
    $s = ob_get_contents();
    ob_end_clean();
    return $s;
  }
}