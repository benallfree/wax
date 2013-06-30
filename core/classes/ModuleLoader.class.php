<?

class ModuleLoader extends Mixin
{
  static $modules = array();

  static function &module($module_name)
  {
    if(!isset(self::$modules[$module_name]))
    {
      trigger_error("Wicked Module '{$module_name}' is not loaded.");
    }
    return self::$modules[$module_name];
  }
  
  static function load($module_name, $config_override = array(), $version = null)
  {
    if(isset(self::$modules[$module_name] )) return;
    if(!is_array($config_override))
    {
      trigger_error("Wicked Module '{$module_name}' did not supply an array for custom config.", E_USER_ERROR);
    }
    $module_fpath = self::find_module($module_name, $version);
    $parts = pathinfo($module_name);
    $module_name = $parts['filename'];
    if(!$module_fpath) 
    {
      trigger_error("Wicked Module '{$module_name}' not found.", E_USER_ERROR);
    }
    
    $config_defaults = array(
      'format'=>'1.0.0',
      'fpath'=>$module_fpath,
      'vpath'=>substr($module_fpath, strlen(W::$root_fpath)),
      'cache_fpath'=>W::$root_fpath."/cache/{$module_name}",
      'cache_vpath'=>"/cache/{$module_name}",
      'requires'=>array(),
    );
    
    // Load the metadata file
    $config_fpath = $module_fpath."/Wicked";
    $config = array();
    if(file_exists($config_fpath))
    {
      require($config_fpath);
    }
    $config = array_merge($config, $config_override);
    $config = array_merge($config_defaults, $config);

    $config = W::filter('module_config', $config, $module_name);
    self::$modules[$module_name] = &$config;


    $load_fpath = $module_fpath."/preload.php";
    if(file_exists($load_fpath))
    {
      require($load_fpath);
    }


    // Handle requires
    foreach($config['requires'] as $req_info)
    {
      if(is_array($req_info))
      {
        list($req_name, $req_version) = $req_info;
      } else {
        $req_name = $req_info;
        $req_version = null;
      }
      if(!self::find_module($req_name, $req_version))
      {
        W::error("Module $module_name requires $req_name, but $req_name does not exist.");
      }
      self::load($req_name, array(), $req_version);
    }
    
    // Module loader
    W::action('before_module_loaded', $module_name, $config);
    $load_fpath = $module_fpath."/load.php";
    if(file_exists($load_fpath))
    {
      require($load_fpath);
    }
    W::action('after_module_loaded', $module_name, $config);
    
  }
  
  private static function find_module($module_name, $desired_version = null)
  {
    if(file_exists($module_name)) return $module_name; // If file path is passed, just return it
    $paths = explode(PATH_SEPARATOR, get_include_path());
    $latest_version_int = 0;
    foreach($paths as $path)
    {
      $module_glob = $path."/wicked/{$module_name}*";
      $files = glob($module_glob, GLOB_ONLYDIR);
      if(!$files) continue;
      foreach($files as $file)
      {
        list($name,$version) = explode('-', basename($file).'-');
        if($name != $module_name) continue; // Not a match;
        if(!$version && $desired_version == null)
        {
          return realpath($file);
        }
        
        W::dprint("Detected a version in $file");
        list($major, $minor, $dot) = explode('.', $version);
        $version_int = (int)sprintf("%03d%03d%03d", $major, $minor, $dot);
        
        if($version_int > $latest_version_int)
        {
          $latest_version = $version;
          $latest_version_int = $version_int;
          $module_fpath = $path."/{$module_name}-{$latest_version}";
        }
      }
    }
    if($latest_version_int==0) return null;
    return realpath($module_fpath);
  }
}