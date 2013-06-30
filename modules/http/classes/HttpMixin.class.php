<?

class HttpMixin extends Mixin
{
  
  static function is_postback($param=null)
  {
    return (count($_POST)>0 || count($_FILES)>0) && ( $param ? p($param,false) : true );
  }
  
  
  static function redirect_to($location, $args=array())
  {
    $location .= "?".http_build_query($args);
    header('Location: ' . $location);
    header('Status: 302');
    die;
  }
  
  
  static function require_ssl()
  {
    global $__wicked;
    
    if(!$__wicked['use_ssl']) return;
    if($_SERVER['SERVER_PORT'] == 443) return;
    if(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) return; // kind of hackey - done for wildcard ssl support on *.painlessprogramming.com
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    header('Status: 301');
    die;
  }
  
  static function redirect_to_home()
  {
    redirect_to(home_url());
  }
  
  static function js_redirect_to($url)
  {
    global $__click;
    $pieces = parse_url($__click['current_url']);
    $new_pieces = parse_url($url);
    $pieces = array_merge($pieces, $new_pieces);
    $url = Url::implode($pieces);
    $url = j($url);
    $js = <<<JS
  <script>
  document.location = '$url';
  </script>
JS;
    echo $js;
  }
}