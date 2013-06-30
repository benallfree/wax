<?

class CollectionsMixin extends Mixin
{
  static $__prefix = 'array';
  
  static function compact($coll)
  {
    $new = array();
    foreach($coll as $k=>&$v)
    {
      if(!$v) continue;
      $new[$k] = &$v;
    }
    return $new;
  }
  
  static function gather($coll, $name)
  {
    $arr=array();
    foreach($coll as $child_coll)
    {
      if (!array_key_exists($name, $child_coll)) continue;
      $arr = array_merge($arr, $child_coll[$name]);
    }
    return $arr;
  }
  
  static function wrap(&$arr, $wrap_with)
  {
    foreach($arr as $k=>$v)
    {
      $arr[$k] = $wrap_with . $v . $wrap_with;
    }
    return $arr;
  }
  
  static function wrap_and_join(&$arr, $wrap_with, $join_with)
  {
    array_wrap($arr, $wrap_with);
    return join($arr, $join_with);
  }
  
  static function in()
  {
    $haystack = func_get_args();
    $needle = array_shift($haystack);
    return array_search($needle, $haystack)!==FALSE;
  }
  
  static function &object_lookup(&$objs)
  {
    $obj_lookup=array();
    for($i=0;$i<count($objs);$i++)
    {
      $obj_lookup[$objs[$i]->id] = &$objs[$i];
    }
    return $obj_lookup;
  }
  
  static function keys($arr)
  {
    $ret = array();
    if (is_array($arr))
    {
      foreach($arr as $k=>$v)
      {
        $n = $k;
        if (is_numeric($k)) $n = $v;
        $ret[] = $n;
      }
    } else {
      $ret[] = $arr;
    }
    return $ret;
  }

  static function each(&$objs, $proc)
  {
    foreach($objs as $k=>&$obj)
    {
      $proc($obj,$k);
    }
  } 
  
  static function collect($objs, $proc)
  {
    $arr = array();
    foreach($objs as $k=>$v)
    {
      $arr[] = call_user_func($proc, $k, $v);
    }
    return $arr;
  }
  
  static function merge ($arr,$ins)
  {
    if(is_array($arr))
    {
      if(is_array($ins)) foreach($ins as $k=>$v)
      {
        if(isset($arr[$k])&&is_array($v)&&is_array($arr[$k]))
        {
          $arr[$k] = merge($arr[$k],$v);
        }
        else 
        {
          if (is_numeric($k))
          {
            if (array_search($v,$arr)===FALSE) $arr[] = $v;
          } else {
            $arr[$k] = $v;
          }
        }
      }
    }
    elseif(!is_array($arr)&&(strlen($arr)==0||$arr==0))
    {
      $arr=$ins;
    }
    return($arr);
  } 
  
  static function md5($arr)
  {
    $vals=array();
    foreach($arr as $k=>$v)
    {
      $vals[]=$k;
      if (is_array($v))
      {
        $v = array_md5($v);
      }
      if (is_object($v))
      {
        $v = spl_object_hash($v);
      }
      $vals[] = $v;
    }
    sort($vals);
    $s = join('|',$vals);
    $md5 = md5($s);
    return $md5;
  }
  
  static function sort_by($field, &$arr, $sorting=SORT_ASC, $case_insensitive=true){
    if(!is_array($arr) || count($arr)==0) return $arr;
    if (is_array($arr[0]) && !isset($arr[0][$field])) return $arr;
    if (is_object($arr[0]) && !isset($arr[0]->$field) ) return $arr;
    
    $strcmp_fn = "strnatcmp";
    if($case_insensitive==true) $strcmp_fn = "strnatcasecmp";
  
    if($sorting==SORT_ASC){
        $fn = create_function('$a,$b', '
            if(is_object($a) && is_object($b)){
                return '.$strcmp_fn.'($a->'.$field.', $b->'.$field.');
            }else if(is_array($a) && is_array($b)){
                return '.$strcmp_fn.'($a["'.$field.'"], $b["'.$field.'"]);
            }else return 0;
        ');
    }else{
        $fn = create_function('$a,$b', '
            if(is_object($a) && is_object($b)){
                return '.$strcmp_fn.'($b->'.$field.', $a->'.$field.');
            }else if(is_array($a) && is_array($b)){
                return '.$strcmp_fn.'($b["'.$field.'"], $a["'.$field.'"]);
            }else return 0;
        ');
    }
    usort($arr, $fn);
    return $arr;
  }  
  
  static function merge_bottom(&$target, $src, $name)
  {
  	if (is_array($src))
  	{
  		foreach($src as $k=>$v)
  		{
  			if (is_array($v))
  			{
  				self::merge_bottom($target[$k], $v, $name);
  			} else {
  				$target[$k][$name] = $v;
  			}
  		}
  	} else {
  		$target[$name] = $src;
  	}
  }
}