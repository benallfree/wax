<?

class RequestMixin extends Mixin
{
  static $request = array();  
  static function init()
  {
    $path = substr($_SERVER['REQUEST_URI'], strlen('/'));
    $parts = explode('?', $path);
    $full_request_path = trim($_SERVER['REQUEST_URI'],"/");
    $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
    $current_url = "{$protocol}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    $request_path = W::vpath("/{$parts[0]}");
    $params = array_merge($_GET, $_POST);
    
    // Fix file struct
    $fields = array('name', 'type', 'tmp_name', 'error', 'size');
    foreach($_FILES as $k=>$v) {
      foreach($fields as $field)
      {
        if (count($v['name'])==0) break;
        W::array_merge_bottom($params[$k], $v[$field], $field);
      }
    }
    
    $host = $_SERVER['HTTP_HOST'];
    $parts = explode('.', $host);
    
    $domain = join('.', array_slice($parts, -2, 2));
    
    $subdomain = join('.', array_slice($parts, 0, count($parts)-2));
    
    $querystring = $_SERVER['QUERY_STRING'];
    
    if (strpos($subdomain, '_')) trigger_error("Subdomains with _ are not supported. They break sessions in IE7/8, possibly others.", E_USER_ERROR);
    
    $request = W::filter('parse_request', array(
      'domain'=>$domain,
      'subdomain'=>$subdomain,
      'host'=>$host,
      'querystring'=>$querystring,
      'path'=>$request_path,
      'params'=>$params,
      'current_url'=>$current_url,
      'protocol'=>$protocol,
    ));
    self::$request = $request;  
  }
  
  static function request($k=null)
  {
    if(!$k) return self::$request;
    return self::$request[$k];
  }
  
  static function params($k=null)
  {
    if(!$k) return self::$request['params'];
    return self::$request['params'][$k];
  }
  
  static function p($name, $val='')
  {
    $elems = preg_split("/[\[\]]/", $name);
    $name = '';
    foreach($elems as $e)
    {
      if(!$e) continue;
      $name.= "['$e']";
    }
    $p = self::$request;
    if (eval("return isset(\$p['params']$name);")) return eval("return \$p['params']$name;");
    return $val;
  }
  
  static function q($s, $default='')
  {
    if (!array_key_exists($s,$_REQUEST)) return $default;
    return $_REQUEST[$s];
  }
  
  static function redirect_to($url, $qs=array())
  {
    header("Location: ". self::build_url($url,$qs));
    die;
  }
  
  static function is_postback()
  {
    return count($_POST)>0;
  }
  
  static function this_url($qs = array())
  {
    return self::build_url(self::request('current_url'), $qs);
  }
  
  static function build_url($url, $qs = array())
  {
    $parts = explode('?', $url);
    $path = $parts[0];
    $query = array();
    if(count($parts)>1)
    {
      parse_str($parts[1], $query);
    }
    $qs = array_merge($query, $qs);
    $url = $path;
    if(count($qs)>0)
    {
      $url .= "?".http_build_query($qs);
    }
    return $url;
  }
}