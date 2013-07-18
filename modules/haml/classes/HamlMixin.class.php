<?php

class HamlMixin extends Mixin
{
  static $__prefix = 'haml';
  static $module = null;
  
  static function init()
  {
    parent::init();
    self::$module = W::module('haml');
  }
  
  static function eval_string($haml, $data=array(), $capture = false)
  {
    $php = self::to_php($haml);
    $out = W::php_sandbox_string($php,$data,$capture);
    dprint($out,true);
    return $out;
  }
  
  static function eval_file($path, $data=array(), $capture = false)
  {
    if(!file_exists($path)) trigger_error("File $path does not exist for HAMLfication.", E_USER_ERROR);
    $haml = file_get_contents($path);
    return self::eval_string(file_get_contents($path), $data, $capture);
  }
  
  
  static function to_php($haml_str)
  {
    $lex = new HamlLexer();
    $lex->N = 0;
    $lex->data = $haml_str;
    $s = $lex->render_to_string();
    return $s;
  }
  
  static function to_string($s)
  {
    $lex = new HamlLexer();
    $lex->N = 0;
    $lex->data = $s;
    $s = $lex->render_to_string();
    return $s;
  }
  
  static function generate_lexer()
  {
    if (W::is_newer($parser_src,$parser_dst))
    {
      require_once 'LexerGenerator.php';
      ob_start();
      $lex = new PHP_LexerGenerator($parser_src);
      ob_get_clean();
    }
  }
}