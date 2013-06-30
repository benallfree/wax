<?

class WickedBase
{
  protected static function &ensure_key(&$a, $k, $default=array())
  {
    if(isset($a[$k])) return $a[$k];
    $a[$k] = $default;
    return $a[$k];
  }  
}