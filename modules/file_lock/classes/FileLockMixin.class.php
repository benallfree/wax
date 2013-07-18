<?php

class FileLockMixin extends Mixin
{
  static $keys = array();

  static function init()
  {
    parent::init();
    $config = W::module('file_lock');
    W::ensure_writable_folder($config['cache_fpath']);
  }  
  
  static function readlock($key)
  {
    if(!$key) trigger_error("Key required for write lock.", E_USER_ERROR);
    $key = sha1($key);
    $config = W::module('file_lock');
    $fname = $config['cache_fpath']."/{$key}.lock";
    dprint($fname,true);
    $fp = fopen($fname, "c");

    if (!flock($fp, LOCK_SH)) return false;
    self::$keys[$key] = $fp;
    return true;
  }
  
  static function writelock($key) 
  {
    if(!$key) trigger_error("Key required for write lock.", E_USER_ERROR);
    $key = sha1($key);
    $config = W::module('file_lock');
    $fname = $config['cache_fpath']."/{$key}.lock";
    $fp = fopen($fname, "c+");

    if (!flock($fp, LOCK_EX)) return false;
    ftruncate($fp, 0);      // truncate file
    fwrite($fp, "Locked");
    fflush($fp);            // flush output before releasing the lock
    self::$keys[$key] = $fp;
    return true;
  }

  static function unlock($key) 
  {
    if(!isset(self::$keys[$key])) return true;
    $fp = self::$keys[$key];
    flock($fp, LOCK_UN);
    fclose($fp);
    unset(self::$keys[$key]);
  }
};