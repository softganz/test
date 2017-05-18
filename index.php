<?php
$include_path=array('core',ini_get('include_path'));
ini_set('include_path',implode(PATH_SEPARATOR,$include_path));

require 'class.core.php';

$request_result=request_process();
if (empty($page)) $page='index';
// start normal process
load_template($page);
?>
