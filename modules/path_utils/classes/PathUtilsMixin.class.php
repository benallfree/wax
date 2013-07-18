<?

class PathUtilsMixin extends Mixin
{
  static function normalize_path()
  {
    $args = func_get_args();
    $path = join("/",$args);
    $parts = explode('/', $path);
    $new_path = array();
    foreach($parts as $part)
    {
      if($part=='') continue;
      $skip = false;
      if ($part == "..")
      {
        array_pop($new_path);
        continue;
      }
      $new_path[] = $part;
    }
    $new_path = "/".join('/',$new_path);
    return $new_path;
  }
  
  
  static function ensure_writable_folder($path)
  {
    $path = self::normalize_path($path);
    if (!file_exists($path))
    {
      if (!mkdir($path, 0775, true)) trigger_error("Failed to mkdir on $path", E_USER_ERROR);
      chmod($path,0775);
      if (!file_exists($path)) trigger_error("Failed to verify $path", E_USER_ERROR);
    }
  }
  
  static function glob()
  {
    $args = func_get_args();
    $res = call_user_func_array('glob', $args);
    if(!is_array($res)) $res = array();
    return $res;
  }
  
  static function is_newer($src,$dst)
  {
    if (!file_exists($dst)) return true;
    if(!file_exists($src)) return false;
    $ss = stat($src);
    $ds = stat($dst);
    $st = max($ss['mtime'], $ss['ctime']);
    $dt = max($ds['mtime'], $ds['ctime']);
    return $st>$dt;
  }
  
  
  static function ftov($fpath)
  {
    $vpath = realpath($fpath);
    if(!$vpath) trigger_error("$fpath is not a valid path for realpath()", E_USER_ERROR);
    $path = substr($vpath, strlen(W::$root_fpath));
    return $path;
  }
  
  static function vtof($vpath)
  {
    return W::$root_fpath.$vpath;
  }
  
  static function vpath($path)
  {
    self::normalize_path(W::$root_vpath,$path);
    return $path;
  }
  
  static function folderize()
  {
    $args = func_get_args();
    for($i=0;$i<count($args);$i++) $args[$i] = strtolower(preg_replace("/[^A-Za-z0-9]/", '_', $args[$i]));
    return join('_',$args);
  }
  
  static function clear_cache($fpath)
  {
    if(strstr($fpath, '/cache/')==false) trigger_error("$fpath doesn't look like a cache path.", E_USER_ERROR);
    $cmd = "rm -rf $fpath";
    W::cmd_or_die($cmd);
    self::ensure_writable_folder($fpath);
  }

}