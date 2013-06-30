<?
if($config['enabled'])
{
  error_reporting($config['error_reporting']);
  ini_set('display_errors', $config['error_output_stream']);
}

require_once('DebugMixin.class.php');
W::add_mixin('DebugMixin');

set_error_handler('W::debug_error_handler');
set_exception_handler('W::debug_exception_handler');
register_shutdown_function('W::debug_shutdown_handler');

