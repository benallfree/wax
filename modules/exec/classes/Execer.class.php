<?php

class Execer
{
  var $command;
  var $output;
  var $retval;
  function __construct($cmd)
  {
    $this->command = $cmd;
    $this->output = '';
    $this->retval = null;
  }
  
  function exec()
  {
    exec($this->command . " 2>&1", $out, $ret);
    $this->output = $out;
    $this->retval = $ret;
    return $this;
  }
}