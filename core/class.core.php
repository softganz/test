<?php
/**
 * core function is a first file for process each request
 *
 * @package none
 * @version 1.2
 * @copyright Copyright (c) 2000-present , The SoftGanz Group By Panumas Nontapan
 * @author Panumas Nontapan <webmaster@softganz.com> , http://www.softganz.com
 * @created 2006-12-16
 * @modify 2012-02-14
 * ============================================
 * This program is free software. You can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License.
 * ============================================
 */

error_reporting(E_ALL);

$coreFolder=dirname(__FILE__).'/';

// Set core location on request
if (array_key_exists('core',$_GET)) {
	$setCore=$_GET['core'];
	if ($setCore=='clear') {
		setcookie('core',NULL,time()-1000,'/');
		unset($_COOKIE['core']);
	} else {
		setcookie('core',$setCore,time()+10*365*24*60*60,'/');
		$_COOKIE['core']=$setCore;
	}
}
if (array_key_exists('core', $_COOKIE)) {
	$include_path='/Users/httpdocs/cms/'.$_COOKIE['core'];
	if (file_exists($include_path)) {
		ini_set('include_path',$include_path);
		$coreFolder=$include_path.'/';
	}
}


define('_CORE_FOLDER', $coreFolder);
if (!defined('_CONFIG_FILE')) define('_CONFIG_FILE','conf.web.php');

cfg('core.version','Seti 4.00.0');
cfg('core.location',ini_get('include_path'));
cfg('core.release','16.12.28');
cfg('core.folder',_CORE_FOLDER);
cfg('core.config',_CONFIG_FILE);

if (!cfg('domain')) cfg('domain','http://'.$_SERVER['HTTP_HOST']);
if (!cfg('domain.short')) cfg('domain.short',$_SERVER['HTTP_HOST']);
//if (!cfg('cookie.domain')) cfg('cookie.domain',$_SERVER['HTTP_HOST']);

cfg('folder.abs',dirname(isset($_SERVER['PATH_TRANSLATED'])?$_SERVER['PATH_TRANSLATED']:$_SERVER['SCRIPT_FILENAME']).'/');
cfg('url',in_array(dirname($_SERVER['PHP_SELF']),array('/','\\'))?'/':dirname($_SERVER['PHP_SELF']).'/');

define('_DOMAIN',cfg('domain'));
define('_ON_LOCAL',cfg('domain.short')=='localhost');
define('_ON_HOST',cfg('domain.short')!='localhost');
define('_URL',cfg('url'));
define('_url',cfg('url'));

// set to the user defined error handler
set_error_handler("sgErrorHandler");

// Test error trigger
//echo 'error_reporting()='.error_reporting().'('.E_ALL.')'.'<br />';
//trigger_error("This is a test of trigger error", E_USER_WARNING);

unset($include_path,$coreFolder,$setCore);


$request = requestString();

$ext=strtolower(substr($request,strrpos($request,'.')+1));
if (in_array($ext,array('ico','jpg','gif','png','htm','html','php','xml','pdf','doc','swf'))) {
	die(fileNotFound());
} else if (in_array($ext,array('js','css')) && basename($request)!='theme.css') {
	die(loadJS($request,$ext));
} else {
	require('class.corefunction.php');
}

/**
 * Generate file not found from include file
 *
 * @return String
 */
function fileNotFound($msg=NULL) {
	ob_start();
	include('error/404.php');
	$ret=ob_get_contents();
	ob_end_clean();
	return $ret;
}

/** Load module JS file
 * @param String jsfile
 * @return String file content
 * */
function loadJS($jsFile,$ext) {
	if ($ext=='js') $headerType='text/javascript';
	else if ($ext=='css') $headerType='text/css';
	else $headerType='text/plain';

	header('Content-Type: '.$headerType.'; charset=utf-8');

	$list=explode('/',$jsFile);
	$jsFilename=end($list);
	$dir=explode('/', dirname($jsFile));
	$module=end($dir);
	$file=_CORE_FOLDER.($module=='js'?'':'modules/'.$module.'/').$jsFilename;
	//echo 'Dir='.print_r($dir,true).'Module='.$module.' Filename='.$jsFilename.' File='.$file;
	if (file_exists($file)) require($file);
	return;
}

/**
 * Get request string from first key of $_GET
 *
 * @return String
 */
function requestString() {
	$result=NULL;
	reset($_GET);
	list($key,$value)=each($_GET);
	reset($_GET);
	if ($key && empty($value)) {
		$request_string=$_SERVER['QUERY_STRING'];
		$request_string=ltrim($request_string,'/');
		//		if (preg_match('/^index.php/i',$request_string,$out)) $request_string=substr($request_string,10);
		list($key)=explode('&',$request_string);
		$result=str_replace('%2F','/',$key);
		if (substr($result,-1)=='=') $result=substr($result,0,-1);
	}
	return $result;
}

/**
 * Set & get config value
 *
 * @param Mixed $key
 * @param Mixed $value
 * @return Mixed
 */
function cfg($key=NULL,$new_value=NULL,$action=NULL) {
	static $cfg=array();
	// Set config with array
	if (is_array($key)) {
		$cfg=array_merge($cfg,$key);
		ksort($cfg);
		//		print_r($key);
		//		print_o($cfg,'$cfg',1);
	} else if (isset($key) && isset($new_value)) {
		$cfg[$key]=$new_value;
		ksort($cfg);
	}

	// Remove config name and value
	if ($action=='delete' && array_key_exists($key,$cfg)) unset($cfg[$key]);

	// Return value of key
	if (isset($key) && is_string($key)) {
		$ret = array_key_exists($key,$cfg) ? $cfg[$key] : null;
	} else $ret = $cfg;

	if (is_object($ret) || is_array($ret)) reset($ret);
	return $ret;
}

/**
 * send header
 *
 * @param String $type eg. text/html, text/css, text/xml,
 */
function sendHeader($type='text/html') {
	header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
	header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header ("Pragma: no-cache"); // HTTP/1.0
	header('Content-Type: '.$type.'; charset='.cfg('client.characterset'));
	cfg('Content-Type',$type);
}


/**
 * Store debug message and display in div class="debug" of page
 *
 * @param String $msg
 * @return String
 */
function debugMsg($msg=NULL,$varname) {
	static $debugMsg='';
	if (is_object($msg) || is_array($msg)) {
		if (function_exists('print_o')) {
			$msg=print_o($msg,$varname);
		} else {
			$msg=print_r($msg,1);
		}
	}
	if (isset($msg)) $debugMsg.='<p>'.$msg.'</p>';
	return $debugMsg;
}

/**
 * Custom error handler
 * @param integer $code
 * @param string $description
 * @param string $file
 * @param interger $line
 * @param mixed $context
 * @return boolean
 */
function sgErrorHandler($code, $description, $file = null, $line = null, $context = null) {
	$displayErrors = ini_get("display_errors");
	$displayErrors = strtolower($displayErrors);
	//echo '<p>Debug Error: code : '.$code.' display '.$displayErrors.' : ['.$code.'] : '.$description.' in [' . $file . ', line ' . $line . ']'.'<br />error_reporting : '.decbin(error_reporting()).' error code : '.decbin($code).'</p>';
	if (sgIsFatalError($code)) echo sgFatalError($code,$description,$file,$line);

	if ($displayErrors === "off") {
		return false;
	} else if (!(error_reporting() & $code)) {
		// This error code is not included in error_reporting
		return false;
	}

	//$errstr=str_replace("\n", "<br />\n", $errstr);
	list($error, $log) = sgMapErrorCode($code);
	$data = array(
		'level' => $log,
		'code' => $code,
		'error' => $error,
		'description' => $description,
		'file' => $file,
		'line' => $line,
		'context' => $context,
		'path' => $file,
		'message' => $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']'
	);
	debugMsg('<p class="error">'.$data['message'].'</p>'."\n");
	return true;
}

/**
 * Map an error code into an Error word, and log location.
 *
 * @param int $code Error code to map
 * @return array Array of error word, and log location.
 */
function sgMapErrorCode($code) {
	$error = $log = null;
	switch ($code) {
		case E_PARSE:
		case E_ERROR:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_USER_ERROR:
			$error = 'Fatal Error';
			$log = LOG_ERR;
			break;
		case E_WARNING:
		case E_USER_WARNING:
		case E_COMPILE_WARNING:
		case E_RECOVERABLE_ERROR:
			$error = 'Warning';
			$log = LOG_WARNING;
			break;
		case E_NOTICE:
		case E_USER_NOTICE:
			$error = 'Notice';
			$log = LOG_NOTICE;
			break;
		case E_STRICT:
			$error = 'Strict';
			$log = LOG_NOTICE;
			break;
		case E_DEPRECATED:
		case E_USER_DEPRECATED:
			$error = 'Deprecated';
			$log = LOG_NOTICE;
			break;
		default :
			break;
	}
	return array($error, $log);
}

function sgFatalError($code,$description,$file,$line) {
	$uid=function_exists('i')?i()->uid:NULL;
	$accessAdmin=function_exists('user_access')?user_access('access administrator pages'):NULL;
	$isAdmin=$uid==1 || $accessAdmin;
	$reportFileNmae=$file;
	if (!$isAdmin) {
		$reportFileNmae=basename($file);
		$reportFileNmae=preg_replace('/^class\.|func\./', '', $reportFileNmae);
		$reportFileNmae=preg_replace('/\.php$/', '', $reportFileNmae);
	}
	$msg='<b>Fatal error: </b>'.$description.' in <b>'.$reportFileNmae.'</b> line <b>'.$line.'</b>. Please <a href="http://www.softganz.com/bug?f='.$reportFileNmae.'&l='.$line.'&d='.date('Y-m-d H:i:s').'&u='.(isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'" target="_blank">report to webmaster</a>.';
	$msgHelp='';
	echo '<html><head><title>Fatal error</title></head>
	<body>
	<table width="100%" height="100%">
	<tr>
		<td></td>
		<td width="80%">
			<div style="border:1px solid rgb(210, 210, 210);background-color: rgb(241, 241, 241);padding:30px;-moz-border-radius:10px;">
			<h1>Fatal error</h1>
			<p>The requested URL <b>'.$_SERVER['REQUEST_URI'].'</b> was error.</p>
			<p>'.$msg.'</p>
			'.($msgHelp?'<p><font color="gray">'.$msgHelp.'</font></p>':'').'
			<hr>
			<address>copyright <a href="http://'.$_SERVER['HTTP_HOST'].'">'.$_SERVER['HTTP_HOST'].'</a> Allright reserved.</address>
			</div>
		</td>
		<td></td>
	</tr></table>
	</body>
	</html>';
}

function sgIsFatalError($code) {
	return in_array($code,array(E_PARSE,E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR));
}

function sgShutdown() {
	global $R;
	$error = error_get_last();
	if ( sgIsFatalError($error["type"]) ) {
		sgErrorHandler( $error["type"], $error["message"], $error["file"], $error["line"] );
	}
	if (is_object($R->mydb) && method_exists($R->mydb,'close')) {
		$R->mydb->close();
	}
}

register_shutdown_function('sgShutdown');
?>