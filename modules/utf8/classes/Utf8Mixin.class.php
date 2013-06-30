<?

class Utf8Mixin extends Mixin
{
  static function utf8_deep_encode($v)
  {
    if(is_string($v)) return self::is_utf8($v) ? $v : utf8_encode($v);
    if(is_array($v))
    {
      foreach($v as $k=>$junk)
      {
        $v[$k] = self::utf8_deep_encode($v[$k]);
      }
    }
    return $v;
  }

  static function is_utf8($string) {
      
      // From http://w3.org/International/questions/qa-forms-utf-8.html
      return preg_match('%^(?:
            [\x09\x0A\x0D\x20-\x7E]            # ASCII
          | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
          |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
          | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
          |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
          |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
          | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
          |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
      )*$%xs', $string);
      
  } // 
}