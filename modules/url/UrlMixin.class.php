<?

class UrlMixin extends Mixin
{
  static $__prefix = 'url';
  
  
  function multiRequest($data, $options = array()) {
    // http://www.phpied.com/simultaneuos-http-requests-in-php-with-curl/
    // array of curl handles
    $curly = array();
    // data to be returned
    $result = array();
  
    // multi handle
    $mh = curl_multi_init();
  
    // loop through $data and create curl handles
    // then add them to the multi-handle
    foreach ($data as $id => $d) {
  
      $curly[$id] = curl_init();
  
      $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
      curl_setopt($curly[$id], CURLOPT_URL,            $url);
      curl_setopt($curly[$id], CURLOPT_HEADER,         0);
      curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
  
      // post?
      if (is_array($d)) {
        if (!empty($d['post'])) {
          curl_setopt($curly[$id], CURLOPT_POST,       1);
          curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
        }
      }
  
      // extra options?
      if (!empty($options)) {
        curl_setopt_array($curly[$id], $options);
      }
  
      curl_multi_add_handle($mh, $curly[$id]);
    }
  
    // execute the handles
    $running = null;
    do {
      curl_multi_exec($mh, $running);
    } while($running > 0);
  
    // get content and remove handles
    foreach($curly as $id => $c) {
      $result[$id] = curl_multi_getcontent($c);
      curl_multi_remove_handle($mh, $c);
    }
  
    // all done
    curl_multi_close($mh);
  
    return $result;
  }
  
  
  static function sprintf()
  {
    $args = func_get_args();
    $template = array_shift($args);
    foreach($args as $k=>$v)
    {
      $args[$k] = u($v);
    }
    array_unshift($args, $template);
    $url = call_user_func_array('sprintf', $args);
    return $url;
  }

  static function fetch($url, $headers = array(), $http_user='', $http_pass='',$use_cache=null)
  {
    $config = W::module('url');
    if($use_cache===null) $use_cache = $config['use_cache'];
    $keys = array_merge(array($url, $http_user, $http_pass), array_values($headers));
    $md5 = md5(join('|',$keys));
    $fname = $config['cache_fpath']."/$md5";
    if($use_cache && file_exists($fname))
    {
      $data = json_decode(file_get_contents($fname),true);
      $data[0] = base64_decode($data[0]);
    } 
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_VERBOSE, true); // Display communication with server
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return data instead of display to std out
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if($http_user)
    {
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ; 
      curl_setopt($ch, CURLOPT_USERPWD, "{$http_user}:{$http_pass}");
    }                     
    $data = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $res = array($data, $error, $info);
    if($use_cache)
    {
      $save = $res;
      $save[0] = base64_encode($save[0]);
      file_put_contents($fname,json_encode($save));    
    } 
    return $res;
  }     
}