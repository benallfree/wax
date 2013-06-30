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
      W::writelock();
      if (!mkdir($path, 0775, true)) W::error("Failed to mkdir on $path");
      chmod($path,0775);
      if (!file_exists($path)) W::error("Failed to verify $path");
      W::unlock();
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
    if(!$vpath) W::error("$fpath is not a valid path for realpath()");
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
    if(strstr($fpath, '/cache/')==false) W::error("$fpath doesn't look like a cache path.");
    $cmd = "rm -rf $fpath";
    W::writelock();
    W::cmd_or_die($cmd);
    W::unlock();
    self::ensure_writable_folder($fpath);
  }

}