<?php

class StringMixin extends Mixin
{
  static function startof($s, $chop)
  {
    if (!is_numeric($chop)) $chop = strlen($chop);
    return substr($s, 0, strlen($s)-$chop);
  }
  
  
  static function endof($s,$n)
  {
    return substr($s, -$n, $n);
  }
  
  static function spacify($camel, $glue = ' ') {
      return preg_replace( '/([a-z0-9])([A-Z])/', "$1$glue$2", $camel );
  }
  
  
     
  static function truncate ($str, $length=30, $trailing='...')
  {
  /*
  ** $str -String to truncate
  ** $length - length to truncate
  ** $trailing - the trailing character, default: "..."
  */
        // take off chars for the trailing
        $length-=mb_strlen($trailing);
        if (mb_strlen($str)> $length)
        {
           // string exceeded length, truncate and add trailing dots
           return mb_substr($str,0,$length).$trailing;
        }
        else
        {
           // string was already short enough, return the string
           $res = $str;
        }
   
        return $res;
  }
  
  
  static function endswith()
  {
    $args = func_get_args();
    $s = array_shift($args);
    foreach($args as $sub)
    {
      if (substr($s, strlen($sub)*-1) == $sub)
      {
        return true;
      }
    }
    return false;
  }
  
  static function startswith($s,$sub)
  {
    return substr($s,0, strlen($sub))==$sub;
  }
  
  static function u($s)
  {
    return urlencode($s);
  }
  
  static function ue($s)
  {
    echo self::u($s);
  }
  
  static function h($s)
  {
    return htmlentities($s, ENT_COMPAT, 'UTF-8');
  }
  
  static function he($s)
  {
    echo self::h($s);
  }
  
  static function j($s, $quote=false)
  {
    $s = preg_replace("/'/", "\\'", $s);
    $s = preg_replace("/\r/", "", $s);
    $s = preg_replace("/\n/", "\\n", $s);
    if($quote) $s = "'".$s."'";
    return $s;
  }
  
  static function je($s, $quote=false)
  {
    self::he(self::j($s, $quote));
  }
}