<?

require('../../config.php');
require('lib/haml.php');
require('lib/HamlLexer.class.php');
require('../../lib/file.php');
require('../../lib/debug.php');

$src_fname = $_SERVER['DOCUMENT_ROOT'].$_SERVER['REQUEST_URI'];

$dst_fname = haml_to_php($src_fname);
chdir(dirname($src_fname));
require($dst_fname);