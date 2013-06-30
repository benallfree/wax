<?


class DateMixin extends Mixin
{
  static $__prefix = '';

  static function date_at_timezone($format, $locale, $timestamp=null){
     
      if(is_null($timestamp)) $timestamp = time();
     
      //Prepare to calculate the time zone offset
      $current = time();
     
      //Switch to new time zone locale
      $tz = date_default_timezone_get();
      date_default_timezone_set($locale);
     
      //Calculate the offset
      $offset = time() - $current;
     
      //Get the date in the new locale
      $output = date($format, $timestamp - $offset);
     
      //Restore the previous time zone
      date_default_timezone_set($tz);
     
      return $output;
     
  }
  
  
  static function business_date_from($start_date, $business_days)
  {
    for($i=0;$i<$business_days;$i++)
    {
      $start_date += (60*60*24);
      $start_date = advance_to_weekday($start_date);
    }
    return $start_date;
  }
  
  static function advance_to_weekday($dt)
  {
    $parts = getdate($dt);
    while(is_weekend_day($dt))
    {
      $dt += (60*60*24);
      $parts = getdate($dt);
    }
    return $dt;
  }
  
  static function is_weekend_day($dt)
  {
    $parts = getdate($dt);
    return $parts['wday']==0 || $parts['wday']==6;
  }
  
  static function is_weekday($dt)
  {
    return !is_weekend_day($dt);
  }
  
  static function is_today($dt)
  {
    $parts = getdate($dt);
    $today_parts = getdate();
    return $parts['year'] == $today_parts['year'] && $parts['yday'] == $today_parts['yday'];
  }
  
  static function is_past($dt)
  {
    $parts = getdate($dt);
    $today_parts = getdate();
    return $parts['year'] < $today_parts['year'] || ($parts['year'] == $today_parts['year'] && $parts['yday'] < $today_parts['yday']);
  }
  
  static function is_future($dt)
  {
    return !is_today($dt) && !is_past($dt);
  }
  
  static function is_same_day($dt1, $dt2)
  {
    $p1 = getdate($dt1);
    $p2 = getdate($dt2);
    return $p1['year']==$p2['year'] && $p1['yday']==$p2['yday'];
  }
  
  static function date_format($timestamp, $include_time=false)
  {
    if (!$timestamp) return null;
    $config = W::module('date');
    $s = $config['date_format'];
    if($include_time) $s .= " @ {$config['time_format']}";
    return date($s, $timestamp);
  }
  
  static function time_format($timestamp, $include_date = false)
  {
    if (!$timestamp) return null;
    $config = W::module('date');
    $s = $config['time_format'];
    if($include_date) $s = "{$config['date_format']} @ " . $s;
    return date($s, $timestamp);
  }
  
  static function beginning_of_week($dt=null)
  {
    if(!$dt) $dt=time();
    $parts = getdate($dt);
    $wday = $parts['wday'] > 0 ? $parts['wday']-1 : 0;
    $dt = $dt - ($wday * ONE_DAY);
    $parts = getdate($dt);
    return mktime(0,0,0,$parts['mon'], $parts['mday'], $parts['year']);
  }
  
  static function end_of_week($dt=null)
  {
    if(!$dt) $dt=time();
    $parts = getdate($dt);
    $d = $parts['wday'];
    if ($d==0) $d=7;
    $dt = $dt + ( (7-$d) * ONE_DAY);
    $parts = getdate($dt);
    return mktime(23,59,59,$parts['mon'], $parts['mday'], $parts['year']);
  }
  
  static function beginning_of_month($dt=null)
  {
    if(!$dt) $dt=time();
    $parts = getdate($dt);
    $parts['mday'] = 1;
    return mktime(0,0,0,$parts['mon'], $parts['mday'], $parts['year']);
  }
  
  static function end_of_month($dt=null)
  {
    if(!$dt) $dt=time();
    $parts = getdate($dt);
    $parts['mday'] = date('t', $dt);
    return mktime(23,59,59,$parts['mon'], $parts['mday'], $parts['year']);
  }
  
  static function last_month($dt=null)
  {
    if(!$dt) $dt=time();
    $dt = strtotime('last month', $dt);
    return $dt;
  }
  
  static function next_month($dt=null)
  {
    if(!$dt) $dt=time();
    $dt = strtotime('next month', $dt);
    return $dt;
  }
  
  
  static function beginning_of_day($dt=null)
  {
    if(!$dt) $dt=time();
    $parts = getdate($dt);
    return mktime(0,0,0,$parts['mon'], $parts['mday'], $parts['year']);
  }
  
  static function end_of_day($dt=null)
  {
    if(!$dt) $dt=time();
    $parts = getdate($dt);
    return mktime(23,59,59,$parts['mon'], $parts['mday'], $parts['year']);
  }
  
  static function business_days_later($dt, $days)
  {
    while($days>0)
    {
      $dt += ONE_DAY;
      while(self::is_weekend_day($dt))
      {
        $dt+=ONE_DAY;
      }
      $days--;
    }
    return $dt;
  }
  
  static function timezone_offset_set($offset)
  {
    $timezones = array(
      '-12'=>'Pacific/Kwajalein',
      '-11'=>'Pacific/Samoa',
      '-10'=>'Pacific/Honolulu',
      '-9'=>'America/Juneau',
      '-8'=>'America/Los_Angeles',
      '-7'=>'America/Denver',
      '-6'=>'America/Mexico_City',
      '-5'=>'America/New_York',
      '-4'=>'America/Caracas',
      '-3.5'=>'America/St_Johns',
      '-3'=>'America/Argentina/Buenos_Aires',
      '-2'=>'Atlantic/Azores',// no cities here so just picking an hour ahead
      '-1'=>'Atlantic/Azores',
      '0'=>'Europe/London',
      '1'=>'Europe/Paris',
      '2'=>'Europe/Helsinki',
      '3'=>'Europe/Moscow',
      '3.5'=>'Asia/Tehran',
      '4'=>'Asia/Baku',
      '4.5'=>'Asia/Kabul',
      '5'=>'Asia/Karachi',
      '5.5'=>'Asia/Calcutta',
      '6'=>'Asia/Colombo',
      '7'=>'Asia/Bangkok',
      '8'=>'Asia/Singapore',
      '9'=>'Asia/Tokyo',
      '9.5'=>'Australia/Darwin',
      '10'=>'Pacific/Guam',
      '11'=>'Asia/Magadan',
      '12'=>'Asia/Kamchatka'
    );
    date_default_timezone_set($timezones[$offset]);
  }
  
  
  
  static function date_diff($date, $date2 = 0)
  {
      if(!$date2)
          $date2 = mktime();
  
      $date_diff = array('seconds'  => '',
                         'minutes'  => '',
                         'hours'    => '',
                         'days'     => '',
                         'weeks'    => '',
                         
                         'tseconds' => '',
                         'tminutes' => '',
                         'thours'   => '',
                         'tdays'    => '',
                         'tdays'    => '');
  
      ////////////////////
      
      if($date2 > $date)
          $tmp = $date2 - $date;
      else
          $tmp = $date - $date2;
  
      $seconds = $tmp;
  
      // Relative ////////
      $date_diff['years'] = floor($tmp/(604800*52));
      $tmp -= $date_diff['years'] * (604800*52);
  
      $date_diff['weeks'] = floor($tmp/604800);
      $tmp -= $date_diff['weeks'] * 604800;
  
      $date_diff['days'] = floor($tmp/86400);
      $tmp -= $date_diff['days'] * 86400;
  
      $date_diff['hours'] = floor($tmp/3600);
      $tmp -= $date_diff['hours'] * 3600;
  
      $date_diff['minutes'] = floor($tmp/60);
      $tmp -= $date_diff['minutes'] * 60;
  
      $date_diff['seconds'] = $tmp;
      
      // Total ///////////
      $date_diff['tweeks'] = floor($seconds/604800);
      $date_diff['tdays'] = floor($seconds/86400);
      $date_diff['thours'] = floor($seconds/3600);
      $date_diff['tminutes'] = floor($seconds/60);
      $date_diff['tseconds'] = $seconds;
  
      return $date_diff;
  }
  
  
  static function time_in_words($seconds, $suffix='')
  {
    $minute = 60;
    $hour = 60*$minute;
    $day = 24*$hour;
    $year = 365*$day;
    foreach( array('year', 'day', 'hour', 'minute') as $t)
    {
      $unit = (int)($seconds/$$t);
      if($unit>=1) return $unit.($unit==1 ? " {$t}" : " {$t}s").$suffix;
    }
    return "{$seconds} second" . ($seconds>1 ? "s" : "").$suffix;
  }
  
  static function ago($dt, $now=null, $suffix = ' ago')
  {
    if($now===null) $now = time();
    $diff = self::date_diff(time(), $dt);
  
    if ($diff['years']==0)
    {
      if ($diff['weeks']==0)
      {
        if ($diff['days']==0)
        {
          if ($diff['hours']==0)
          {
            if ($diff['minutes']==0)
            {
              if ($diff['seconds']==1) return '1 second'. $suffix;
              return $diff['seconds'] . ' seconds'. $suffix;
            } else {
              if ($diff['minutes']==1) return '1 minute'. $suffix;
              return $diff['minutes'] . ' minutes'. $suffix;
            }
          } else {
            if ($diff['hours']==1) return '1 hour'. $suffix;
            return $diff['hours'] . ' hours'. $suffix;
          }
        } else {
          if ($diff['days']==1) return '1 day'. $suffix;
          return $diff['days'] . ' days'. $suffix;
        }
      } else {
        if ($diff['weeks']==1) return '1 week'. $suffix;
        return $diff['weeks'] . ' weeks'. $suffix;
      }    
    } else {
      if ($diff['years']==1) return '1 year'. $suffix;
      return $diff['years'] . ' years'. $suffix;
    }  
  }
}