<?php
// precess web configuration
global $R;

$R=new R();
$R->request=$request;

$includeFileList=array(
									'lib.define.php',
									'lib.function.php',
									'func.common.php',
									'class.common.php',
									'func.sg.php',
									'class.module.php',
									'class.mydb.php',
									// Extend Library
									'lib.corefunction.v'.cfg('core.version.major').'.php',
									'lib.ui.php',
									'class.model.php',
									'class.view.php',
									'class.sg.php',
									'func.markdown.php',
									);

foreach ($includeFileList as $file) {
	if (file_exists(dirname(__FILE__).'/'.$file)) require(dirname(__FILE__).'/'.$file);
}

$httpDomain=str_ireplace('www.', '', parse_url(cfg('domain'), PHP_URL_HOST));
define('_HOST',$httpDomain);
$httpReferer=isset($_SERVER["HTTP_REFERER"])?str_ireplace('www.', '', parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST)):'';

if (isset($_SERVER['REDIRECT_QUERY_STRING']) && substr($_SERVER['REDIRECT_QUERY_STRING'],0,4) == 'app/') {
	// in application must define _AJAX=false for sum url
} else {
	define('_AJAX',((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest') && $httpDomain==$httpReferer) || preg_match('/^ajax\//i',$request) || isset($_GET['ajax']));
	define('_HTML',isset($_REQUEST['html']));
	define('_API',isset($_REQUEST['api']));
}

//die(_AJAX?'AJAX Call':'Normal call');

q($R->request);

$R->timer=new Timer();


/* clear module folder don't exists */
$old_error=error_reporting(0);
foreach (explode(PATH_SEPARATOR,ini_get('include_path')) as $_folder) {
	if (is_dir($_folder.'/modules')) $_module_folder[]=$_folder.'/modules';
	if (is_dir($_folder.'/modules/apps')) $_module_folder[]=$_folder.'/modules/apps';
}
error_reporting($old_error);

cfg('module.folder',$_module_folder);
unset($_module_folder,$_folder); // clear unused variable

/* load configuration file */
load_config('conf.default.php',_CORE_FOLDER); // load default config file
load_config('conf.web.release.php'); // load web config release
load_config(_CONFIG_FILE); // load web config file
load_config('conf.local.php'); // load local config file
error_reporting(cfg('error_reporting'));

//echo 'error after load config '.error_reporting().' : '.decbin(error_reporting()).'<br />';

if ($request=='robots.txt') die(cfg('robots.txt'));

if (cfg('web.status')==0) {
	die(R::View('site.maintenance'));
}

// create new mydb database constant
$R->mydb=new MyDb();
if (class_exists('MySql')) $R->mysql=new MySql();

// load config variable from table
load_config(cfg_db());

// Clear user_access
user_access('reset');
define('_img',cfg('img'));

// Redirect website to other site if set redirect and not admin page
if (cfg('site.redirection')!=''
	&& cfg('site.redirection')!=cfg('domain').$_SERVER['REQUEST_URI']
	&& !in_array(q(0),array('admin','signout')) ) {
	header('Location: '.cfg('site.redirection'));
	echo 'Site rediection to <a href="'.cfg('site.redirection').'">'.cfg('site.redirection').'</a>';
	flush();
	die;
}

// Return theme.css store in config theme.(theme.name).css
if (basename($request)=='theme.css') {
	sendheader('text/css');
	echo '/* Load theme : theme.'.cfg('theme.name').'.css */'._NL;
	die(cfg('theme.'.cfg('theme.name').'.css'));
}

// Start session handler register using database
$session=new Session();
session_set_save_handler(array($session,'open'),array($session,'close'),array($session,'read'),array($session,'write'),array($session,'destroy'),array($session,'gc'));

/* set the cache expire to 30 minutes */
//session_cache_expire(1);
//ini_set("session.gc_maxlifetime", "10");
//$cache_expire = session_cache_expire();

session_start();

cfg('url.abs',preg_match('/http\:/',cfg('url')) ? cfg('url') : '//'.$_SERVER['HTTP_HOST'].cfg('url'));
if (!cfg('upload.folder')) cfg('upload.folder',cfg('folder.abs').cfg('upload').'/');
if (!cfg('upload.url')) cfg('upload.url',cfg('url').cfg('upload').'/');

set_lang();

// Set header for AJAX request
if (_AJAX) {
	header('Content-Type: text/html; charset='.cfg('client.characterset'));
	header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
}





/**
 * Process normal and AJAX request
 *
 * do not call debug() before here , It posible mistake roles from table
 */

// load module initial that contain menu
$user=R::Model('user.checklogin');

// Check site status
if (cfg('web.status')==0 && !user_access('access administrator pages')) {
	include('error/site_maintenance.html');
	die;
}

// Hit counter and store counter/online
if (!_AJAX && mydb::table_exists('%counter_log%')) R::Model('counter.hit');
$R->counter=cfg('counter');
$R->online=cfg('online');

set_theme();

cfg('core.message',core_version_check());

// End of core process

/**
 * Core class and function library for core process
 */

/**
 * Class R :: Core resource
 */
class R {
	public $request;
	public $timer;
	public $mydb;
	public $counter;
	public $online;

	public static function Module($moduleName,$className) {
		$paraArgs=func_get_args();
		$rName=$paraArgs[0];
		$rName='module.'.$rName;
		$paraArgs[0]=$rName;
		$ret=call_user_func_array('load_resource', $paraArgs);
		return $ret;
	}

	public static function Model($modelName) {
		$paraArgs=func_get_args();
		$rName=$paraArgs[0];
		$rName='r.'.$rName;
		$paraArgs[0]=$rName;
		$ret=call_user_func_array('load_resource', $paraArgs);
		return $ret;
	}

	public static function View($viewName) {
		$paraArgs=func_get_args();
		$rName=$paraArgs[0];
		$rName='view.'.$rName;
		$paraArgs[0]=$rName;
		$ret=call_user_func_array('load_resource', $paraArgs);
		return $ret;
	}

	public static function Page($pageName) {
		$paraArgs=func_get_args();
		$rName=$paraArgs[0];
		$rName='page.'.$rName;
		$paraArgs[0]=$rName;
		$ret=call_user_func_array('load_resource', $paraArgs);
		return $ret;
	}

	public static function On($eventName) {
		$paraArgs=func_get_args();
		$ret=NULL;
		if (is_string($paraArgs[0])) {
			$paraArgs[0]='on.'.$paraArgs[0];
			$ret=call_user_func_array('load_resource', $paraArgs);
		}
		return $ret;
	}

	public static function Manifest($modulename) {
		$loadResult=load_resource_file('manifest.'.$modulename);
		return $loadResult;
	}
}

/**
 * Core class library
 */


/** Core function library : Core function for process request **/

/**
 * Check Core version and upgrade
 *
 * @return String
 */
function core_version_check() {
	if (!user_access('access administrator pages')) return;
	if (!mydb()->status) return 'MySql maybe down.';
	$version_current=cfg('version.current');
	$version_install=cfg('version.install');
	$version_force='';
	if (post('force')) {
		$version_force=post('force');
	}
	if ($version_install===$version_current && $version_force=='') return;
	if (post('upgrade')=='yes' || $version_force) cfg('version.autoupgrade',true);
	if (!cfg('version.autoupgrade')) return 'ระบบมีความต้องการปรับปรุงจากรุ่น <strong>'.$version_install.'</strong> เป็นรุ่น <strong>'.$version_current.'</strong> แต่การปรับปรุงอัตโนมัติถูกปิด. <a href="'.url(q(),'upgrade=yes').'">เริ่มปรับปรุงรุ่น?</a>';

	$upgrade_folder=dirname(__FILE__).'/upgrade/';
	if (!file_exists($upgrade_folder)) return 'Upgrade folder not extsts.';

	set_time_limit(0); // run very long time

	$d = dir($upgrade_folder);
	while (false !== ($entry = $d->read())) {
		if ( $entry=='.' || $entry=='..' ) continue;
		$upver=substr($entry,0,strrpos($entry,'.'));
		$upgrade_file[$upver] = $entry;
	}
	asort($upgrade_file);
	$d->close();

	$ret.='<h3>Start upgrading from '.$version_install.' to '.$version_current.'</h3>';
	if ($version_force) $ret.='<p>Force upgrade version of '.$version_force.'</p>';
	foreach ($upgrade_file as $upver=>$file) {
		if ($version_force && $upver==$version_force) {
			// Do nothing
		} else if ($upver<=$version_install) {
			$ret.='<p>Upgrade version '.$upver.' from file '.$file.' is unnecessary.</p>';
			continue;
		}
		$ret.='<h4>Upgrade to version '.$upver.'</h4>';
		include_once($upgrade_folder.$file);
		$ret.='<dl>';
		foreach ($result[$upver] as $upgrade_result) {
			$ret.='<dt>'.$upgrade_result[0].'</dt>';
			$ret.='<dd>'.$upgrade_result[1].'</dd>';
		}
		$ret.='</dl>'._NL;
	}
	cfg('web.message',$ret);
	if (!$version_force) cfg_db('version.install',cfg('version.current'));
	return;
}

/**
 * Trim only string type in array
 *
 * @param Mixed $value
 * @return Mixed
 */
function _trim(&$value) {$value=is_string($value) ? trim($value):$value;}
function _htmlspecialchars(&$value) {$value=is_string($value) ? htmlspecialchars($value):$value;}

/**
 * Replace %tablename% with prefix and tablename
 *
 * @param Array $m
 * @return String
 */
function _mydb_db_replace($m) {return ' '.db($m[1]);}

/**
 * Check IP was ban
 *
 * @param String $ip
 * @param Int $start_time
 * @param Int $ban_time
 * @return boolean
 */
function banip($ip,$start_time=0,$ban_time=0) {
	$banips=cfg('ban.ip');

	// Ban this ip
	if ($ip && $start_time && user_access('access administrator pages')) {
		if (!$ban_time) $ban_time=cfg('ban.time');
		$banips[$ip]=array($start_time,$start_time+$ban_time);
		cfg_db('ban.ip',$banips);
		return '<script type="text/javascript">$("notify").innerHTML="Ban IP '.$ip.' for 1 day was completed."</script>';
	}

	$is_ban=false;

	if (empty($banips)) return $is_ban;

	foreach ($banips as $idx=>$ban) {
		if (time()>$ban[1]) unset($banips[$idx]);
		if ($idx==$ip && time()<=$ban[1]) {
			$is_ban=true;
			break;
		}
	}
	if (count($banips) != count(cfg('ban.ip'))) cfg_db('ban.ip',$banips);
	return $is_ban;
}

/**
 * Get current web viewer information and set value to key
 *
 * @param String $key
 * @param String  $value
 * @return object
 */
function i($key=null,$value=null) {
	static $i;
	if (!isset($i->ok)) {
		$i=&$GLOBALS['user'];
		$i->admin=user_access('access administrator pages');
	}
	if (!isset($i->am)) {
		if ($i->ok && module_install('ibuy')) $i->am=mydb::select('SELECT `shop_type` FROM %ibuy_franchise% WHERE `uid`=:uid LIMIT 1',':uid',$i->uid)->shop_type;
		if (empty($i->am)) $i->am='';
	}
	if (!$i->server) {
		$i->ip=GetEnv('REMOTE_ADDR');
		$i->server=&$_SERVER;
	}
	if (isset($key) && isset($value)) $i->{$key}=$value;
	return $i;
}

/**
 * Translate text from English to current language used my dictiondary
 *
 * @param String $text
 * @param String $translatetext
 * @return String
 */
function tr($text,$translatetext=NULL) {
	static $dict=array();
	static $load=array();
	$lang=cfg('lang');
	if (empty($load)) {
		$load[]='core.en.po';
		include(_CORE_FOLDER.'po/core.en.po');
	}
	$current_lang_po=strtolower('core.'.$lang.'-'.cfg('client.characterset').'.po');
	if (!in_array($current_lang_po,$load)) {
		$load[]=$current_lang_po;
		include(_CORE_FOLDER.'po/'.$current_lang_po);
	}
	if ($text=='load' && $translatetext) {
		$load_lang_po=strtolower($translatetext.'.'.$lang.'-'.cfg('client.characterset').'.po');
		$load_lang_file=_CORE_FOLDER.'po/'.$load_lang_po;
		if (!in_array($load_lang_po,$load) && file_exists($load_lang_file)) {
			$load[]=$load_lang_po;
			include($load_lang_file);
		}
	} if (is_array($text)) {
		foreach ($text as $lang=>$v) {
			foreach ($v as $k=>$vv) $dict[$lang][$k]=$vv;
		}
	}

	$key=strtolower(strip_tags($text));
	$result=$lang!='en' && $translatetext ? $translatetext : (array_key_exists($key,$dict[$lang])?$dict[$lang][$key]:$text);
	return $result;
}

/**
 * Add string to website header section
 *
 * @param String $str
 */
function head($key=NULL,$value=NULL,$pos=NULL) {
	static $items=array();
	if ($value===NULL) $value=$key;
	if (preg_match('/[\n]/',$key)) unset($key);
	if (!in_array($value,$items)) {
		if ($pos==-1) {
			$items=array($key=>$value)+$items;
		} else if (isset($key) && $key) $items[$key]=$value;
		else $items[]=$value;
	}
	if ((!isset($key) || $key===NULL) && $value===NULL) return $items;
	else if (isset($key) && $key) return $items[$key];
}

function is_serialized($val){
    if (!is_string($val)){ return false; }
    if (trim($val) == '') { return false; }
    if (preg_match('/^(i|s|a|o|d|b):(.*);/si',$val)) { return true; }
    return false;
}

/**
 * Set & set configuration to database
 *
 * @param String $name
 * @param Mixed $value
 * @return Mixed
 */
function cfg_db($name=NULL,$value=NULL) {
	if (isset($name) && isset($value)) {
		// Update $value to db config by key $name
		$write_value=is_string($value) ? $value : serialize($value);
		mydb::query('INSERT INTO %variable% ( `name` , `value` ) VALUES ( :name, :value) ON DUPLICATE KEY UPDATE `value`=:value',':name',$name,':value',$write_value);

		$result=$value;
		cfg($name,$value);
	} else if (isset($name)) {
		$rs=mydb::select('SELECT `name`, `value` FROM %variable% WHERE name=:name LIMIT 1',':name',$name);
		$result = ($rs->_num_rows) ? is_serialized($rs->value)?unserialize($rs->value) : $rs->value : NULL;
	} else {
		$dbs=mydb::select('SELECT `name`, `value` FROM %variable%');
		$conf=array();
		if (isset($dbs->items) && $dbs->items) {
			foreach ($dbs->items as $item) {
				$conf[$item->name]=is_serialized($item->value) ? unserialize(trim($item->value)) : $item->value;
			}
		}
		$result = $conf;
	}
	return $result;
}

/**
 * Delete configuration from database
 *
 * @param String $name
 */
function cfg_db_delete($name) {
	if (isset($name)) {
		mydb::query('DELETE FROM %variable% WHERE name=:name LIMIT 1',':name',$name);
		cfg($name,NULL,'delete');
	}
}

/**
 * Load configuration from file and store into cfg
 *
 * @param Mixed $config_file
 * @param Mixed $folder
 */
function load_config($config_file=NULL,$folders=array('.')) {
	if (is_array($config_file)) {
		// merge array config to current config value
		cfg($config_file);
	} else if (is_string($config_file)) {
		if (is_string($folders)) $folders=explode(';',$folders);
		foreach ($folders as $folder) {
			$each_config_file=$folder.'/'.$config_file;
			if ( file_exists($each_config_file) and is_file($each_config_file) ) {
				include($each_config_file);
				if (isset($cfg) && is_array($cfg)) cfg($cfg);
				break;
			}
		}
	}
}

/**
 * Set & get table with keyword
 *
 * @param String $key
 * @param String $new_value
 * @param String $prefix
 * @param String $db
 * @return String
 */
function db($key=NULL,$new_value=NULL,$prefix=NULL,$db=NULL) {
	static $items=array();
	static $src=array();
	$ret=NULL;
	if (isset($key) && isset($new_value)) {
		$src[$key]=$new_value;
		$tablename=(isset($db)?'`'.$db.'`.':''); // Set database name
		if (preg_match('/\`([a-zA-Z0-9_].*)\`/',$new_value,$out)) {
			$tablename.=$new_value; // Use new_value on `table` or `db`.`table` format
		} else {
			$tablename.='`'.cfg('db.prefix').$new_value.'`'; // Add prefix on table format
		}
		$items[$key]=$tablename;
		$ret=$items;
	} else if (!isset($key) && isset($prefix)) {
		// Change all table items to new prefix value
		foreach ($src as $key=>$value) $items[$key]=$prefix.$value;
		$ret=$items;
	} else if (isset($key)) {
		// if key return value in condition
		// key format `table_name` return `table_name`
		// key format %table_name% return value in `$items[table_name]`
		// key format table_name and key in array items return items[table_name]
		// key format table_name and key not in items return db.prefix+table_name
		if (preg_match('/\`([a-zA-Z0-9_].*)\`/',$key,$out)) {
			$ret=$key;
		} else if (preg_match('/\%([a-zA-Z0-9_].*)\%/',$key,$out)) {
			$ret=array_key_exists($out[1],$items)?$items[$out[1]]:'`'.cfg('db.prefix').$out[1].'`';
		} else {
			$ret=array_key_exists($key,$items)?$items[$key]:'`'.cfg('db.prefix').$key.'`';
		}
	} else {
		$ret=$items;
	}
	return $ret;
}

/**
 * Convert request string into array and get each index
 *
 * @param Mixed $from explode into array if is string
 * @param Mixed $all numeric or 'all'
 * @param String $return_type
 * @return Mixed
 *
 * If first param is string -> explode request string to array and store in $q
 * If only first param (from) -> get all value start from first param
 * If first param and second param (from,to) -> get all value between from and to
 */
function q($from=NULL,$to=NULL,$return_type='array') {
	static $q=array();
	static $rq=NULL;
	if (is_string($from)) {
		$rq=$from;
		$q= explode('/',$from);
		foreach ($q as $k=>$v) if (trim($v)=='') unset($q[$k]); else $q[$k]=trim($v);
		return;
	}
	if ($to==='all') $to=count($q);
	if (isset($from) && !isset($to)) $ret = array_key_exists($from,$q) ? $q[$from]:NULL;
	else if (isset($from) && isset($to)) $ret = array_slice($q,$from,$to);
	else $ret = $rq;
	if ($return_type==='string' && is_array($ret)) $ret = implode('/',$ret);
	return $ret;
}

/**
 * Set current language
 *
 * @param String $lang
 * @param String
 */
function set_lang($lang=NULL) {
	//echo 'lang='.$_GET['lang'].'='.$_REQUEST['lang'].'='.post('lang').'='.$lang.'='.$_COOKIE['lang'];
	if ($lang) {
		// do nothing
	} else if ($lang=$_GET['lang']) {
		if ($lang=='clear') {
			setcookie('lang',NULL,time()-100,cfg('cookie.path'),cfg('cookie.domain'));
		} else {
			setcookie('lang',$lang,time()+10*365*24*60*60,cfg('cookie.path'),cfg('cookie.domain'));
		}
		//$_COOKIE['lang']=$lang;
		//echo 'lang='.$_REQUEST['lang'].'='.post('lang').'='.$lang.'='.$_COOKIE['lang'];
		//echo cfg('cookie.path').' '.cfg('cookie.domain');
	} else if (array_key_exists('lang', $_COOKIE) && $lang=$_COOKIE['lang']) {
		$lang=$_COOKIE['lang'];
	}
	cfg('lang',$lang);
	return $lang;
}

/**
 * Set theme folder
 *
 * @param String $name Theme name
 * @param String $style Stylesheet file
 * @return Array $template_folder
 *
 * set theme configuration etc theme , theme.name , theme.absfolder , theme.stylesheet
 */
function set_theme($name=NULL,$style='style.css') {
	/*
	themes -> current url+themes
	./themes -> current url+./themes
	../themes -> current url+../themes
	/themes -> /themes
	/folder/themes -> /folder/themes
	*/

	$themes=array();
	if (isset($name)) $themes[]=$name;
	if ($_GET['theme']) $themes[]=$_GET['theme'];
	if ($_COOKIE['theme']) $themes[]=$_COOKIE['theme'];
	$themes[]=cfg('theme.name');
	$themes[]='default';

	foreach ($themes as $name) {
		$theme_folder=cfg('folder.abs').cfg('theme.folder').'/'.$name.'/';
		$css_file=$theme_folder.$style;
		if (is_dir($theme_folder)) break;
	}

	cfg('theme.name',$name);
	cfg('theme.absfolder',cfg('folder.abs').cfg('theme.folder').'/'.cfg('theme.name'));
	cfg('theme',(substr(cfg('theme.folder'),0,1)=='/' ? cfg('theme.folder') : cfg('url').cfg('theme.folder')).'/'.$name.'/');

	//set style sheet
	if (isset($_GET['style']) && $_GET['style']=='') {
		cfg('theme.stylesheet',cfg('theme').$style);
		setcookie('style','',time()-3600,cfg('cookie.path'),cfg('cookie.domain'));
	} else if (isset($_GET['style']) && $_GET['style']!='') {
		cfg('theme.stylesheet',$_GET['style']);
		setcookie('style',$_GET['style'],time()+365*24*60*60,cfg('cookie.path'),cfg('cookie.domain'));
	} else if ($_COOKIE['style']) {
		cfg('theme.stylesheet',cfg('theme.name',$_COOKIE['style']));
	} else {
		cfg('theme.stylesheet',file_exists($css_file)?cfg('theme').$style:'/themes/'.$name.'/'.$style);
	}

	// create theme folder list

	// add current theme folder
	$template_folder[] = cfg('theme.absfolder').'/';
	// add default theme folder
	$template_folder[] = cfg('folder.abs').cfg('theme.folder').'/default/';
	// add root theme folder
	$template_folder[] = cfg('folder.abs').cfg('theme.folder').'/';
	$template_folder[] = _CORE_FOLDER;
	$template_folder=array_unique($template_folder);

	foreach ($template_folder as $key=>$_folder) if (!is_dir($_folder)) unset($template_folder[$key]);
	cfg('theme.template',$template_folder);

	return $template_folder;
}

/**
 * Set and get program debug
 *
 * @param String $key
 * @return Mixed
 */
function debug($key=NULL) {
	static $items=array();
	if (empty($items)) {
		$debug='';
		if (isset($_GET['debug'])) $debug = $_GET['debug'];
		if (preg_match('/debug\/([a-z,0-9_]*)/',q(),$out)) $debug.=($debug?',':'').$out[1] ;
		$items['debug']=$debug?$debug:'none';
		foreach (explode(',',$items['debug']) as $ok) $items[$ok]=true;
	}
	$is_debug=cfg('debug') || user_access('access debugging program');
	return ($is_debug && $key=='debug') ? $items : ($is_debug && array_key_exists($key,$items));
}

/**
 * Generate module menu
 *
 * @param String $path
 * @param String $title
 * @param String $module
 * @param Array $arg
 * @param String $access
 * @param String $type
 * @return Mixed True/False/Array
 */
function menu($path=NULL,$title=NULL,$module=NULL,$method=NULL,$arg=array(),$access=NULL,$type=NULL,$default=NULL) {
	/**
	* Items store format
	* array(
	*		path'=>'paper/list', // eg. *=any , *0=all numeric
	*	 	'title'=>'Paper topic listing',
	* 	'call'=>array(	'module'=>'topic',
	* 						'class'=>'topic',
	* 						'method'=>'listing',
	*							'arg'=>array()),
	*		'access'=>'access papers',
	*		'type'=>'static');
	*/
	static $items=array();
	static $is_sort=false;

	if ( $path && isset($title) && isset($module) ) {
		$q=q(0,'all');
		//		if ($path=='ibuy/*0') echo $path.print_o($q,'$q');
		if (!(strpos($path,'*')===false)) {
			$paths=explode('/',$path);
			foreach ($paths as $i=>$v) {
				if ($v==='*' && isset($q[$i])) $paths[$i]=$q[$i];
				else if ($v=='*0' && isset($q[$i]) && is_numeric($q[$i])) $paths[$i]=$q[$i];
			}
			$path=implode('/',$paths);

			// not set menu if path not equal to left of request string
			if (!preg_match('/^'.preg_quote($path,'/').'/',q())) return false;

			// not set menu if this path was ready set
			if (array_key_exists($path,$items)) return false;
		}

		if (is_string($arg)) $arg=explode('/',$arg);
		else if (is_null($arg)) $arg=array();
		$items[$path]=array(
								'path'=>$path,
								'title'=>$title,
								'call'=>array('module'=>$module,'method'=>$method,'arg'=>$arg),
								'access'=>$access,
								'type'=>$type,
								'default'=>$default,
								);
		$is_sort=false;
		return true;
	}

	if (!$is_sort) {
		krsort($items);
		$is_sort=true;
	}
	if (!isset($path)) return $items;

	if (isset($path)) {
		foreach ($items as $mnu) {
			$pat=preg_quote($mnu['path'],'/');
			if (preg_match('/^('.$pat.')/',$path)) {
				$mnu['call']['arg']=is_numeric($mnu['call']['arg']) ? q($mnu['call']['arg'],'all') : $mnu['call']['arg'];
				return $mnu;
			}
		}
	}
	return false;
}

/**
 * This function will return the name string of the function that called $function. To return the
 * caller of your function, either call get_caller(), or get_caller(__FUNCTION__).
 * @param String $function
 * @param Array $use_stack
 * @param String $key
 * @return String
 **/
function get_caller($function = NULL, $use_stack = NULL,$key=NULL) {
	if ( is_array($use_stack) ) {
		// If a function stack has been provided, used that.
		$stack = $use_stack;
	} else {
		// Otherwise create a fresh one.
		$stack = debug_backtrace();
		//echo "\nPrintout of Function Stack: \n\n";
		//print_o($stack,'$stak',1);
		//echo "\n";
	}

	if ($function == NULL) {
		// We need $function to be a function name to retrieve its caller. If it is omitted, then
		// we need to first find what function called get_caller(), and substitute that as the
		// default $function. Remember that invoking get_caller() recursively will add another
		// instance of it to the function stack, so tell get_caller() to use the current stack.
		$function = get_caller(__FUNCTION__, $stack,$key);
	}


	//echo $function.' level='.$level.'<br />';print_o($stack,'$stack',1);
	if ( is_string($function) && $function != "" ) {
		// If we are given a function name as a string, go through the function stack and find
		// it's caller.
		for ($i = 0; $i < count($stack); $i++) {
			$curr_function = $stack[$i];
			// Make sure that a caller exists, a function being called within the main script
			// won't have a caller.
			if ($key=='stack') {
				if ($i==0) continue;
				$stackList.=$curr_function['function'].'() line '.$curr_function['line'].' of file '.$curr_function['file'].'<br />';
				//echo '$stackList='.$stackList.'<br />'._NL;
			} else {
				if ( $curr_function["function"] == $function && ($i + 1) < count($stack) ) {
					$stack[$i + 1]['from']=(!empty($stack[$i + 1]['class'])?$stack[$i + 1]['class'].($stack[$i + 1]['type']?$stack[$i + 1]['type']:'.'):'').$stack[$i + 1]['function'].'() line '.$stack[$i]['line'].' of file '.$stack[$i]['file'];
					//print_o($stack[$i + $level],'$return['.($i + $level).']',1);
					unset($stack[$i + 1]['args']);
					return $key ? $stack[$i + 1][$key]: $stack[$i + 1];
				}
			}
		}
		if ($key=='stack') return $stackList;
	}

	// At this stage, no caller has been found, bummer.
	return "";
}

/**
 * Find template location
 *
 * @param String $filename
 * @param String ext_folder Each folder seperate by ;
 * @return Mixed False on file not found and file location on found
 */
function get_template($filename=NULL,$ext_folder=NULL) {
	if (empty($filename)) return false;
	$theme_folder=array();
	if ( $ext_folder ) {
		foreach ( explode(';',_CORE_FOLDER) as $folder ) {
			$theme_folder[] = $folder.'/'.$ext_folder.'/'.$GLOBALS['theme'].'/';
			$theme_folder[] = $folder.'/'.$ext_folder.'/default/';
		}
	}
	$theme_folder = array_unique(array_merge($theme_folder,cfg('theme.template')));
	$result=false;
	foreach ( $theme_folder as $folder) {
		$load_file = $folder.'tpl.'.$filename.'.php';
		if ( file_exists($load_file) ) {
			$result=$load_file;
			break;
		}
	}

	if (debug('template')) {
		echo '<br />load template <b>'.$filename.'</b>'.($ext_folder?' width extension folder '.$ext_folder:'');
		echo $result ? ' found <b>'.$result.'</b><br />' : ' <font color=red>not found</font><br />';
		print_o($theme_folder,'$theme_folder',1);
	}
	return $result;
}

/**
 * Load function in module folder and return parameter
 * @param String $module exp [form/]module[.submodule].method
 * @param String $func
 * @return Mixed
 */
function load_resource_file($packageName) {
	static $loadCount=0;
	static $debugFunc=array();
	static $loadFiles=array();

	$found=false;
	$res='';
	$ret=array();
	$isDebugable=true;
	$template=cfg('template');
	if (cfg('template.add')) $template=cfg('template.add').';'.$template;

	$debugLoadfile=debug('loadfile');

	$loadCount++;

	if(preg_match('/^(r\.|view\.|page\.|on\.|manifest\.|module\.)(.*)/i',$packageName,$out)) {
		$res=substr($out[1],0,-1);
		$package=$out[2];
	} else {
		return false;
	}

	if ($debugLoadfile) $caller=get_caller(__FUNCTION__);
	$debugStr.='<!-- Debug of load_resource_file -->'._NL.'<br /><br />'._NL.'<div style="margin:10px; padding:10px; border:1px gray solid;">'._NL.'<p style="font-weight:bold;">Debug of '.__FUNCTION__.'() #'.$loadCount.' in '.__FILE__.'</p>'._NL.'Start load function from <b>'.($res?' Resource '.$res.'':'Page').'</b> Package=<b>'.$package.'</b><br />'._NL;

	$request=explode('.',$package);

	$module=$request[0];
	$mainFolder=dirname(__FILE__);
	$filename=$funcname='';

	if ($res=='module') {
		$filename='module.';
		$funcname='module_';
		$paths[]='modules/'.$module;
	} else if ($res=='r') { // Model Resource
		$filename='r.';
		$funcname='r_';
		$paths[]='modules/'.$module.'/r';
		$paths[]='system/model';
	} else if ($res=='view') { // View Resource
		$filename='view.';
		$funcname='view_';
		$paths[]='modules/'.$module;
		if (isset($request[1])) $paths[]='modules/'.$module.'/'.$request[1];
		$paths[]='modules/'.$module.'/default';
		$paths[]='system/view';
	} else if ($res=='on') { // Event Resource
		$filename='on.';
		$funcname='on_';
		$paths[]='modules/'.$module.'/r';
		$paths[]='system/model';
	} else if ($res=='manifest') { // Manifest Resource
		$filename='manifest.';
		$funcname=NULL;
		$paths[]='modules/'.$module;
		$paths[]='system';
	} else { // Page Resource
		$filename='page.';
		$funcname='';
		$paths[]='modules/'.$module;
		if (isset($request[1])) $paths[]='modules/'.$module.'/'.$request[1];
		$paths[]='modules/'.$module.'/default';
		$paths[]='system';
	}


	$filename.=implode('.',$request).'.php';
	if(!is_null($funcname)) $funcname.=implode('_',$request);

	if (function_exists($funcname) && array_key_exists($packageName, $loadFiles)) {
		$found=true;
		$debugStr.='<font color="green">Function '.$funcname.'() was already load.</font><br />'._NL;
		//$isDebugable=$debugFunc[$funcname];
	} else {
		$debugStr.='template.add='.cfg('template.add').'<br />';
		$debugStr.='<h4>Caller</h4><a href="#" onclick="$(this).next().toggle();return false;">detail >></a><div class="loadfunction__detail hidden">'.(isset($caller['from'])?'Call from '.$caller['from']:'').'</div>'._NL;
		$debugStr.='<p>request='.implode('/',$request).'</p>';

		$debugStr.='Load filename : <strong>'.$filename.'</strong> and call function <strong>'.$funcname.'()</strong><br />'._NL;

		if (in_array($res,array('manifest','module'))) {
			$folders[]='.';
		} else {
			if ($template) {
				foreach (explode(';', $template) as $item) if ($item) $folders[]=$item;
			}
			$folders[]='default';
			$folders[]='.';
		}

		$debugStr.='Main folder='.$mainFolder.'<br />';
		$debugStr.='Path='.implode(';',$paths).'<br />'._NL.'Folders='.implode(';',$folders).'<br />'._NL;

		foreach ($paths as $path) {
			$path=$mainFolder.'/'.$path;
			$isPathExists=is_dir($path);
			$debugStr.='<h3>Locate file in path '.$path.' ('.($isPathExists?'OK':'not exists').')</h3>'._NL;
			if (!$isPathExists) continue;
			foreach ($folders as $folder) {
				if (empty($folder)) continue;
				$folderName=$path.'/'.$folder;
				$isFolderExists=is_dir($folderName);
				$debugStr.='Locate in folder : ['.$folder.'] => '.$folderName.($isFolderExists?' (<strong>is exists</strong>)':' (not exists)').'<br />';
				if (!$isFolderExists) {continue;}

				$funcFile=$folderName.'/'.$filename;

				// Check function exists, if not set function return found
				if (file_exists($funcFile)) {
					$loadFiles[$packageName]=$funcFile;
					require_once($funcFile);
					//debugMsg($loadFiles,'$loadFiles');
					$debugFunc[$funcname]=$isDebugable=!(isset($debug) && $debug===false);
					$debugStr.='<strong style="color:green;">Found and load file '.$funcFile.'</strong><br />'._NL;

					// Check function exists, if not set function return found
					if ($funcname) {
						if (function_exists($funcname)) {
							$debugStr.='<strong style="color:green;">Execute function '.$funcname.'() complete.</strong><br />'._NL;
							$found=true;
							break;
						} else {
							$debugStr.='<strong style="color:red;">Execute function '.$funcname.'() is not exist.</strong><br />'._NL;
						}
					} else {
						$found=true;
					}
				}
			}
			if ($found) break;
		}
	}

	$debugStr.='</div>'._NL;
	if ($debugLoadfile && ($isDebugable || debug('force'))) debugMsg($debugStr);
	$ret=array($funcname,$found,$funcFile);
	return $ret;
}

/**
 * Load function in module folder and call it
 * @param String $module exp [form/]module[.submodule].method as package name
 * @param Mixed $parameter
 * @return Mixed
 */
function load_resource($packageName) {
	$ret='';
	$paraArgs=func_get_args();

	$loadResult=list($funcname,$found)=load_resource_file($packageName);

	array_shift($paraArgs);
	if ($found && function_exists($funcname)) {
		$ret=call_user_func_array($funcname, $paraArgs);
	}

	//debugMsg('Load resource '.$funcname.' '.(function_exists($funcname) ? 'FUNCTION EXISTS':'FUNCTION NOT EXISTS'));
	//debugMsg($loadResult,'$loadResult');
	return $ret;
}

/**
 * Load widget request from tag <div class="widget" ></div>
 *
 * @param String $name , widget-request , widget-addons
 * @param Object $para
 * @return String
 */
function load_widget($name,$para) {
	static $lists=array();
	static $folders=array();
	//$result.='name='.$name.'<br />'.print_o($para,'$para(widget)');
	if (!empty($para->{'data-url'})) {
		// Load widget from resource url
		$paraArgs=array();
		$rName='page.'.str_replace('/', '.', $para->{'data-url'});
		$paraArgs[0]=$rName;
		$paraArgs[]=NULL;
		foreach ($para as $k=>$v) {
			if ($k=='data-url') continue;
			if (preg_match('/data-para-/',$k)) {
				$paraArgs[]=$v;
			}
		}
		list($module)=explode('/',$para->{'data-url'});
		R::Manifest($module);
		$widget_result=call_user_func_array('load_resource', $paraArgs);
		//$widget_result=print_o($paraArgs,'$paraArgs').$widget_result;
	} else {
		// Load widget from filename widget.name.php function=widget_name
		if (empty($folders)) {
			$folders=cfg('theme.template');
			$folders[]=cfg('core.folder').'system/widget/';
		}

		$is_debug=debug('widget');

		if ($is_debug) debugMsg('<b>Start load widget '.$name.'</b> from widget folders '.implode(' , ',$folders));

		foreach ($folders as $folder) {
			$filename=$folder.'widget.'.$name.'.php';
			if (file_exists($filename)) {
				$lists[]=$name;
				if ($is_debug) debugMsg('<em style="color:#f60;font-weight:bold;">Load widget file '.$filename.' found</em>');
				include_once($filename);
				break;
			}
			if ($is_debug) debugMsg('Load widget file '.$filename.' not found.');
		}

		$func_name='widget_'.$name;
		if (function_exists($func_name)) {
			list($widget_result,$para)=call_user_func($func_name,$para);
		}
	}

	if (!empty($para->{'data-header'}) && !in_array(strtolower($para->{'option-header'}),array('0','no'))) {
		$header='<h2>'.($para->{'data-header-url'}?'<a href="'.$para->{'data-header-url'}.'">':'').'<span>'.get_first($para->{'data-header'},$para->id).'</span>'.($para->{'data-header-url'}?'</a>':'').'</h2>'._NL;
	}

	if ($para->{'data-option-replace'}=='yes') {
		$result=trim($widget_result);
	} else {
		$result.=_NL.'<!-- Start widget '.$name.' -->'._NL.($header).'<div class="widget-content">'._NL.trim($widget_result)._NL.'<br clear="all" class="clear" />'._NL.'</div>'._NL.'<!-- End of widget '.$name.' -->'._NL;
	}
	return $result;
}

/**
 * Find and load template
 *
 * @param String $filename
 * @param String ext_folder Each folder seperate by ;
 * @param Boolean $show_result
 * @return String Result from template file
 */
function load_template($filename=NULL,$ext_folder=NULL,$show_result=true) {
	$template_file = get_template($filename,$ext_folder);
	if (!$template_file) return;
	$ret='';
	if ($show_result) {
		require($template_file);
	} else {
		ob_start();
		require($template_file);
		$ret=ob_get_contents();
		ob_end_clean();
	}
	return $ret;
}

/**
 * Load extension file
 *
 * @param String $name
 */
function load_extension($name) {
	static $lists=array();
	static $folders=array();
	if (in_array($name,$lists)) return;
	if (empty($folders)) {
		$folders=cfg('theme.template');
		$folders[]=cfg('core.location').'extensions/';
	}
	$is_debug=debug('extension');
	if ($is_debug) echo 'load extension '.$name.'<br />';
	if ($is_debug) print_o($folders,'extension folders',1);
	foreach ($folders as $folder) {
		$file=$folder.$name.'.extension.php';
		if ($is_debug) echo 'load extension file '.$file;
		if (file_exists($file)) {
			$lists[]=$name;
			if ($is_debug) echo ' <em style="color:#f60;">found</em><br />';
			include_once($file);
			break;
		}
		if ($is_debug) echo ' not found<br />';
	}
}

/**
 * Generate even tricker
 *
 * @param String $event
 * @param Mixed $arg1 ... $arg9
 */
function event_tricker($event=NULL,&$arg1=NULL,&$arg2=NULL,&$arg3=NULL,&$arg4=NULL,&$arg5=NULL,&$arg6=NULL,&$arg7=NULL,&$arg8=NULL,&$arg9=NULL) {
	//debugMsg($event);
	static $extensions=null;
	if (!isset($extensions)) $extensions=cfg('extensions')?cfg('extensions'):array();
	/* do extension_event_tricker */
	if ($event && preg_match('/(.*)\.(.*)$/',$event,$out)) {
		$event_name=$out[1];
		$event_id=$out[2];
		if (array_key_exists($event_name,$extensions)) {
			$events=$extensions[$event_name];
			// set for pass by reference
			$args=array($event_id,&$arg1,&$arg2,&$arg3,&$arg4,&$arg5,&$arg6,&$arg7,&$arg8,&$arg9);
			foreach ($events as $e) {
				$event_func=str_replace('.','_',$e);
				load_extension($event_func);
				if (function_exists($event_func)) $ret=call_user_func_array($event_func,$args);
				else cfg('extension_error',$event_func.' not exists');
			}
		}
	}
	return $ret;
}

/**
 * Check user permission to each menu from roles or user's role or is owner's content
 *
 * @param String $role
 * @param String $urole
 * @param Integer $uid
 * @param Boolean $debug
 * @return Boolean
 */
function user_access($role,$urole=NULL,$uid=NULL,$debug=false) {
	global $user;
	static $roles;
	static $member_roles=array();
	if ($role=='reset') $roles=NULL;
	if (!isset($roles)) {
		foreach (cfg('roles') as $name=>$item) $roles[$name]=trim($item)==='' ? array() : explode(',',$item);
		array_walk_recursive($roles,'_trim');
	}
	if ($role==='reset') return false;

	if ($uid) $uid=intval($uid);

	if ($debug) echo '<br />user access debug role <b>'.$role.($urole?','.$urole:'').($uid?','.$uid:'').'</b> of <b>'.($user->ok?$user->name.'('.$user->uid.($user->roles?',':'').implode(',',$user->roles).')':'anonymous').'</b><br />';
	// menu for everyone
	if ($role===true) return true;

	// root have all privileges
	if (isset($user->uid) && $user->uid==1) return true;

	// admin have all privileges
	if ($user && $user->ok && in_array('admin',$user->roles)) return true;

	$role=explode(',',$role);

	// need method check privileges
	if (in_array('method permission',$role)) return true;

	// check for member
	if ($user && $user->ok) {
		// collage all member roles
		if (!array_key_exists($user->uid,$member_roles)) {
			$member_roles[$user->uid]=array_merge($roles['anonymous'],$roles['member']);
			foreach ($user->roles as $name) if (is_array($roles[$name])) $member_roles[$user->uid]=array_merge($member_roles[$user->uid],$roles[$name]);
			$roles_user=cfg('roles_user');
			if (is_array($roles_user) && array_key_exists($user->uid,$roles_user)) {
				$member_roles[$user->uid]=array_merge($member_roles[$user->uid],explode(',',$roles_user[$user->uid]));
			}
			$member_roles[$user->uid]=array_unique($member_roles[$user->uid]);
			asort($member_roles[$user->uid]);
		}

		if ($debug) echo '$member_roles['.$user->uid.']='.implode(',',$member_roles[$user->uid]).'<br />';

		/* user have permission in roles */
		if ($debug && $str=implode(',',array_intersect($role,$member_roles[$user->uid]))) echo 'roles permission is <b>'.$str.'</b><br />';
		if (array_intersect($role,$member_roles[$user->uid])) return true;

		/* check permission of owner content */
		if ($urole) {
			if ($debug) echo in_array($urole,$member_roles[$user->uid]) ? 'user role is <b>'.$urole.'</b>'.($uid===$user->uid?' and is owner permission':' but not owner').'<br />':'';
			if ($uid===$user->uid && in_array($urole,$member_roles[$user->uid])) return true;
		}

		if ($debug) echo 'no role permission<br />';
		return false;
	}

	// anonymous user role
	if ($debug) echo '$roles[anonymous]='.implode(',',$roles['anonymous']).'<br />';
	if ($debug && $str=implode(',',array_intersect($role,$roles['anonymous']))) echo 'roles intersection=<b>'.$str.'</b><br />';
	if (is_array($roles['anonymous']) && array_intersect($role,$roles['anonymous'])) return true;

	if ($debug) echo 'no role permission<br />';
	return false;
}

/**
 * Check module was install
 *
 * @param String $module
 * @return Boolean
 */
function module_install($module) {
	if (empty($module) && !is_string($module)) return false;
	$perms=cfg('perm');
	return isset($perms->$module);
}

/**
 * Check homepage was requested
 *
 * @return Boolean
 */
function is_home() {
	global $R;
	return !isset($R->request) || empty($R->request) || $R->request=='home';
}

/**
 * Check current user is admin or module admin
 * @param String $module
 * @return Boolean
 */
function is_admin($module) {
	$is_right=false;
	if (user_access('access administrator pages,administer contents')) $is_right=true;
	else if ($module && user_access('administrator '.$module.'s')) $is_right=true;
	return $is_right;
}

/**
 * Generate url for anchor
 *
 * @param String $q
 * @param String $get
 * @param String $frement
 * @return String
 */
function url($q= NULL,$get=NULL,$frement=NULL,$subdomain=NULL) {
	$ret='';
	if (isset($get) && is_array($get)) {
		foreach ($get as $k=>$v) if (!is_null($v)) $get_a.=$k.'='.$v.'&';
		$get=rtrim($get_a,'&');
		if (empty($get)) unset($get);
	}
	if (substr($q,0,2)==='//') ; // do nothing
	else if (substr($q,0,1)==='/') $q=substr($q,1);

	$url = preg_match('/^(\/\/|http:\/\//|https:\/\//)',$q) ? '' : cfg('url');
	if (cfg('clean_url')) {
		$ret .= isset($q)?$q:cfg('clean_url_home');
		if ( isset($get) ) $ret .= '?'.$get;
	} else {
		$ret .= $q ? '?'.$q:'';
		if ( isset($get) ) $ret .= ($q?'&':'?').$get;
	}
	if ($frement) $ret .= '#'.$frement;
	//	echo 'url alias of '.$ret.' = '.url_alias($ret)->system.'<br >';
	if ($url_alias=url_alias_of_system($ret)) $ret=$url_alias->system;
	//	echo 'url ret='.$ret.'<br />';
	$ret=cfg('url.domain').(cfg('url.domain')?'':$url).$ret;
	return $ret;
}

/**
 * Get url alias
 *
 * @param String $request
 * @return String or False on not found
 */
function url_alias($request=NULL) {
	static $alias=NULL;
	if (!isset($alias)) $alias=mydb::select('SELECT * FROM %url_alias% ORDER BY `alias` ASC');
	if (!isset($request)) return $alias;

	$result=NULL;
	foreach ($alias->items as $item) {
		$reg_url_alias=str_replace('/','\/',preg_quote($item->alias));
		$reg='/^('.$reg_url_alias.'\z|'.$reg_url_alias.'\/)(.*)/';
		$system=preg_replace($reg,$item->system.'/\\2',$request);

		if ($system!=$request) {
			$system=trim($system,'/');
			$result->alias=$request;
			$result->system=$system;

			return $result;
		}
	}
	return false;
}

/**
 * Get url alias of system
 *
 * @param String $request
 * @return String or False on not found
 */
function url_alias_of_system($request) {
	$alias=url_alias();

	if (!$alias) return false;
	$result=new stdClass();
	foreach ($alias->items as $item) {
		$reg_url_system=str_replace('/','\/',preg_quote($item->system));
		$reg='/^('.$reg_url_system.'\z|'.$reg_url_system.'\/)(.*)/';
		$reg_result=preg_replace($reg,$item->alias.'/\\2',$request);
		$reg_result=trim($reg_result,'/');
		if ($reg_result!=$request) {
			$result->system=$reg_result;
			$result->alias=$item->alias;
			return $result;
		}
	}
	return false;
}

/**
 * Set header to new location and die
 *
 * @param String $url
 * @param String $get
 * @param String $frement
 * @return relocation to new url address
 */
function location($url=NULL,$get=NULL,$frement=NULL,$str=NULL) {
	//	echo $url;
	//	if (_AJAX) return $str;
	if (!preg_match('/^(http:\/\/|https:\/\/|ftp:\/\/)/i',$url)) {
		// $url=cfg('domain').url($url,$get,$frement); // Not use cfg('domain') if bug , uncomment this line
		$url=url($url,$get,$frement);
	}
	header('Location: '.$url);
	die;
}

/**
 * Process variable and replace with value
 *
 * @param String $html
 *
 * @return String
 */
function process_variable($html) {
	$vars = array(
						'q'=>q(),
						'domain'=>cfg('domain'),
						'url'=>cfg('url'),
						'upload_folder'=>cfg('upload_folder'),
						'theme'=>cfg('theme'),
						'_HOME_STICKY'=>_HOME_STICKY,
					);

	// Searching textarea and pre
	preg_match_all('#\<textarea.*\>.*\<\/textarea\>#Uis', $html, $foundTxt);
	preg_match_all('#\<pre.*\>.*\<\/pre\>#Uis', $html, $foundPre);

	// replacing both with <textarea>$index</textarea> / <pre>$index</pre>
	$html=str_replace($foundTxt[0], array_map(function($el){ return '<textarea>'.$el.'</textarea>'; }, array_keys($foundTxt[0])), $html);
	$html=str_replace($foundPre[0], array_map(function($el){ return '<pre>'.$el.'</pre>'; }, array_keys($foundPre[0])), $html);

	// Replace {$var} with $vars[var]
	$html=preg_replace_callback('#{\$(.*?)}#',
												function($match) use ($vars){
													return $vars[$match[1]];
												},
												$html);

	// Replace {url:} with url()
	$html=preg_replace_callback('#{(url\:)(.*?)}#',
												function($match){
													return url($match[2]);
												},
												$html);

	// Replace {tr:} with url()
	$html=preg_replace_callback('#{(tr\:)(.*?)}#',
												function($match){
													$para=preg_split('/,/', $match[2]);
													return tr($para[0],$para[1]);
												},
												$html);

	// Replacing back with content
	$html=str_replace(array_map(function($el){ return '<textarea>'.$el.'</textarea>'; }, array_keys($foundTxt[0])), $foundTxt[0], $html);
	$html=str_replace(array_map(function($el){ return '<pre>'.$el.'</pre>'; }, array_keys($foundPre[0])), $foundPre[0], $html);
	return $html;
}

/**
 * Process widget tag <div class="widget WidgetName" ...></div>
 *
 * @param String $html
 *
 * @return String
 */
function process_widget($html) {
	if (!is_string($html)) return $html;

	// Replace {$var} with vars and {url:} with url()
	$html=process_variable($html);

	$html_recent=$html;
	$result='';
	$widget_ldq='<div class="widget ';
	$widget_rdq='</div>';


	/*
	 * $matches[0] = ข้อความก่อนหน้า+widget
	 * $matches[1] = ข้อความก่อนหน้า (ไม่รวม widget)
	 * $matches[2] = widget tag <div class="widget ....></div>
	 * $matches[3] = widget name
	 * $matches[4] = widget attribute
	 * $matches[5] = widget inner html
	 */
	preg_match_all('/(.*)('.$widget_ldq.'(.*)\"[\s](.*)>(.*)'.preg_quote($widget_rdq,'/').')(.*)/msU',$html,$matches);

	//	$pattern_short = '{<div\s+class="widget\s*>((?:(?:(?!<div[^>]*>|</div>).)++|<div[^>]*>(?1)</div>)*)</div>}misU';
	//	$matchcount = preg_match_all($pattern_short, $html, $matches);

	//	print_r($matches);
	if ($matches[2]) {
		foreach ($matches[1] as $idx=>$htmltag) {
			$widget_tag=$matches[2][$idx];
			$widget_attr=trim($matches[4][$idx]);
			list($widget_name)=explode(' ',trim(strtolower($matches[3][$idx])));

			$pattern = '/([\\w\-]+)\s*=\\s*("[^"]*"|\'[^\']*\'|[^"\'\\s>]*)/';
			preg_match_all($pattern, $widget_attr, $attr_matches, PREG_SET_ORDER);
			$attrs = array();
			foreach ($attr_matches as $attr) {
				if (($attr[2][0] == '"' || $attr[2][0] == "'") && $attr[2][0] == $attr[2][strlen($attr[2])-1]) {
					$attr[2] = substr($attr[2], 1, -1);
				}
				$name = strtolower($attr[1]);
				$value = html_entity_decode($attr[2]);
				$attrs[$name] = $value;
			}

			$widget_request=load_widget($widget_name,(object)$attrs);
			//			$widget_request.='Loading widget <strong>'.$widget_name.'</strong> with '.$widget_attr;
			//			$widget_request.=print_o($attrs,'$attrs');
			if ($attrs["data-option-replace"]=='yes') {
				$widet_result=$widget_request;
			} else if (preg_match('/<div class=\"widget\-content\"><\/div>/i',$widget_tag)) {
				$widet_result=preg_replace('/<div class=\"widget\-content\"><\/div>/i',$widget_request,$widget_tag);
			} else {
				$widet_result=preg_replace('/<\/div>$/i',$widget_request.'</div>',$widget_tag);
			}
			$result.=$matches[1][$idx].$widet_result;
			$html_recent=substr($html_recent,strlen($matches[0][$idx]));
		}
		$result.=$html_recent;
	} else {
		$result=$html;
	}

	//	$result.=htmlview($html);
	//	$result.=str_replace("\n",'<br />',htmlspecialchars(print_r($matches,1)));
	return $result;
}

/**
 * Do module method from request menu item
 *
 * @param Array $menu
 * @return String
 */
function process_menu($menu) {
	$module=$menu['call']['module'];
	$isFunctionCall=false;
	$auth_code=$menu['access'];
	$is_auth=user_access($auth_code);
	if ($is_auth===false) return array(NULL,true,message('error','Access denied'));

	// Create self object
	if (class_exists($module)) {
		$exeClass=new $module($module);
	} else {
		$exeClass=new Module($module);
	}

	R::Module($module.'.init',$exeClass);

	$menuArgs=array_merge(array($module),$menu['call']['arg']);

	// Load request from package function file func.package.method[.method].php
	$call_method='';
	$funcName=$funcArg=array();
	foreach ($menuArgs as $value) {
		if (is_numeric($value)) break;
		$funcName[]=$value;
	}

	$found=false;
	do {
		$funcArg=array_slice($menuArgs,count($funcName));
		$pageFile='page.'.implode('.', $funcName);
		$loadResult=list($retFunc,$found,$filename)=load_resource_file($pageFile);

		/*
		debugmsg('<h3>Check page '.$pageFile.'</h3>');
		debugMsg(''.($found?'Found ':'Not found ').'<b>'.$retFunc.'</b> in <b>'.$pageFile.'</b><br />');
		debugMsg($funcName,'$funcName');
		debugMsg($funcArg,'$funcArg');
		debugMsg($loadResult,'$loadResult');
		debugMsg('--------------');
		*/

		array_pop($funcName);
	} while (!$found && count($funcName)>=1);

	if ($found) {
		$args=array_merge(array($exeClass),$funcArg);
		$ret=call_user_func_array($retFunc, $args);
	}

	return array($exeClass,$found,$ret);
}

/**
 * Do request process from url address and return result in string
 *
 * @return String
 */
function process_request($loadTemplate=true,$pageTemplate=NULL) {
	global $R,$request_result,$page;

	$request=$R->request;
	$method_result='';
	$ret='';
	if ($GLOBALS['site_message']) {
		$ret.='<p class="notify">'.tr('<h2>Website temporary out of service.</h2><p>My Website is currently out of service ('.$GLOBALS['site_message'].'). We should be back shortly. Thank you for your patience.</p>','<strong>อุ๊บ!!! เว็บไซท์ของเราให้บริการไม่ทันเสียแล้ว</strong><br /><br />ขออภัยด้วยนะคะ มีความเป็นไปได้สูงว่าเครื่องเซิร์ฟเวอร์กำลังทำงานหนักจนไม่สามารถให้บริการได้ทัน เว็บไซท์จึงหยุดการบริการชั่วคราว อีกสักครู่ขอให้ท่านแวะมาดูใหม่นะคะ').'</p>';
			ob_start();
			load_template('home');
			$ret.=ob_get_contents();
			ob_end_clean();
			return $ret;
	}

	if (banip(getenv('REMOTE_ADDR'))) return message('error','Sorry!!!! You were banned.');

	if (isset($GLOBALS['message'])) $ret .= $GLOBALS['message'];

	$R->timer->start($request);

	$process_debug = 'process debug of <b>'.$request.'</b> request<br />'._NL;

 	// Show splash if not visite site in 1 hour
	$webhomepage=cfg('web.homepage');
	if (cfg('web.readonly')) $ret.=message('status',cfg('web.readonly_message'));

	if (!isset($request) || empty($request) || ($request=='home') || ($request==$webhomepage)) {
		// Check for splash page
		if (cfg('web.splash.time')>0 && $splash=url_alias('splash') && empty($_COOKIE['splash'])) {
			cfg('page_id','splash');
			location('splash');
		}

		$home=cfg('web.homepage');
		cfg('page_id','home');
		if (empty($home)) {
			ob_start();
			load_template('home');
			$ret.=ob_get_contents();
			ob_end_clean();
			$request='';
		} else {
			$R->request=$request=$home;
			q($request);
			//debugMsg('$home='.$home.' '.$request.q());
			$manifest=R::Manifest(q(0));
			$menu=menu($request);
		}
	} else if ($request) {
		// Load Module Manifest
		if (q(0)) $manifest=R::Manifest(q(0));
		if ($url_alias=url_alias($request)) {
			// check url alias
			$process_debug.='<p><strong>'.$request.'</strong> is url alias of <strong>'.$url_alias->system.'</strong></p>';
			$request=$url_alias->system;
			q($request);
			$process_debug.=print_o(q(0,'all'),'$q');
			$manifest=R::Manifest(q(0));
			$menu=menu($request);
		} else if ($menu=menu($request)) {
			//debugMsg('Do request menu');
		} else {
			// Do request from R::page
			menu(q(0),q(0).' page',q(0),'__controller',1,true,'static');
			$menu=menu($request);
		}
	}

	if ($manifest) $process_debug .= 'Manifest module file : '.print_o($manifest,'$manifest').'<br />';

	if ($manifest[1] && $menu) { // This is a core version 4
		$process_debug.='Load core version 4 <b>'.$request.'</b><br />';
		list($exeClass,$found,$ret)=process_menu($menu);
	//} else if ($init_module_file=load_init_module(q(0))) { // This is a core version 3
	//	$process_debug.='Load core version 3<br />';
	//	list($exeClass,$ret,$found)=process_request_v3($request);
	} else { // Page no manifest
		$process_debug.='Load core version 4 on no manifest and no class<br />';
		list($exeClass,$found,$ret)=process_menu($menu);
	}

	// Page not found
	if (!$found) {
		R::Model('watchdog.log','system','Page not found');
		// Set header to no found and noacrchive when url address is load function page
		if ($q==str_replace('.', '/', $package)) {
			header('HTTP/1.0 404 Not Found');
			head('<meta name="robots" content="noarchive" />');
		}
		$ret.='<div class="pagenotfound">
		<h1>Not Found</h1>
		<p>ขออภัย ไม่มีหน้าเว็บนี้อยู่ในระบบ</p><p>The requested URL <b>'.$_SERVER['REQUEST_URI'].'</b> was not found on this server.</p>'.(user_access('access debugging program')?'<p><strong> Load file detail</strong><br />'.print_o($menuArgs,'$request').'<br />File : <strong>'.$mainFolder.'['.implode(';', $folders).']/'.$pageFile.'</strong><br />Routine : <strong>function '.$retFunc.'()</strong></p>':'').'
		<hr>
		<address>copyright <a href="http://'.$_SERVER['HTTP_HOST'].'">http://'.$_SERVER['HTTP_HOST'].'</a> Allright reserved.</address>
		</div>'._NL;
	}

	$R->timer->stop($request);



	// Set splash page was show
	if (cfg('web.splash.time')) {
		setcookie('splash',true,time()+cfg('web.splash.time')*60,cfg('cookie.path'),cfg('cookie.domain')); // show splash if not visite site
	}
	//echo $process_debug;
	//print_o($menu,'$menu',1).print_o(i(),'i()',1);

	if (cfg('Content-Type')=='text/xml') {
		die(process_widget($ret));
	} else if (!_AJAX && is_array($ret) && isset($ret['location'])) {
		call_user_func_array('location',$body['location']);
	} else if (_HTML && (is_array($ret) || is_object($ret))) {
		die(process_widget(print_o($ret,'$ret')));
	} else if (_AJAX || is_array($ret) || is_object($ret)) {
		if (is_array($ret) || is_object($ret)) {
			$ret=json_encode($ret);
		}
		die(debugMsg().process_widget($ret));
	} else if (_HTML) {
		die(process_widget($ret));
	}

	$request_result=R::View('module',$exeClass,$ret);
	$request_result=process_widget($request_result);

	if (debug('menu')) debugMsg(menu(),'$menu');
	$process_debug.= print_o($menu,'$menu');
	$process_debug.= print_o(q(0,'all'),'$q');
	if (debug('process')) debugMsg($process_debug.(isset($GLOBALS['process_debug'])?print_o($GLOBALS['process_debug']):''));
	$GLOBALS['request_time'][$request]=$R->timer->get($request,5);
	$GLOBALS['request_process_time']=$GLOBALS['request_process_time']+$R->timer->get($request);
	if (debug('timer')) debugMsg('Request process time : '.$GLOBALS['request_process_time'].' ms.'.print_o($GLOBALS['request_time']));


	if ($GLOBALS['R']->mydb and !$GLOBALS['R']->mydb->status) $request_result = error($GLOBALS['R']->mydb->status_msg).$request_result;

	if (debug('html')) debugMsg(htmlview($request_result,'html tag'));
	if (debug('config')) {
		$cfg=cfg();
		array_walk_recursive($cfg, '_htmlspecialchars');
		debugMsg($cfg,'cfg');
	}

	if ($pageTemplate) $page=$pageTemplate;
	else if (empty($page)) $page='index';
	if ($loadTemplate) {
		$request_result=load_template($page,NULL,false);
		$request_result=process_widget($request_result);
		echo $request_result;
	}
	return $request_result;
}

/**
 * Process installation new module
 *
 * @param String $module
 * @return String
 */
function process_install_module($module) {
	if (empty($module)) return;

	$manifest_module_file=R::Manifest($module);

	if (empty($manifest_module_file)) {
		$ret=false;
	} else {
		// Add Permission
		$perm=cfg('perm');
		$perm->{$module}=cfg($module.'.permission');
		cfg_db('perm',$perm);

		$ret.=$module.'.permission='.$perm->{$module};

		// Process installation
		$ret.=R::Model($module.'.install');
	}
	return $ret;
}

/**
 * Get post value from $_POST
 *
 * @param String $key
 * @param Integer $flag
 *
 * @return Array
 */
function post($key=NULL,$flag=NULL) {
	$result=NULL;
	$magic_quote=get_magic_quotes_gpc();
	$post = $_REQUEST;
	if ( is_Long($key) ) {
		$flag = $key;
		unset($key);
	}
	if ( $magic_quote == 1 ) $post = arrays::convert($post,_STRIPSLASHES);
	if ( $flag ) $post = arrays::convert($post,$flag);
	if ( isset($key) ) {
		$result = isset($post[$key]) ? $post[$key] : NULL;
	} else $result=$post;
	return $result;
}

/**
 * View html text in plain text with highlight
 *
 * @param String $html
 * @param String $title
 * @param Boolean $line_no
 *
 * @return String
 */
function htmlview($html,$title=NULL,$line_no=true) {
	$code = explode("\n", trim($html));
	$i = 1;
	$ret .= '<div style="background:#fff;color:#000;">';
	if ($title) $ret .= '<h2>'.stripslashes($title).'</h2>';
	foreach ($code as $line => $syntax) {
		if ($line_no) $ret .= '<font color="gray"><em>'.$i.' : </em></font> ';
		$ret .= str_replace('&nbsp;',' ',highlight_string($syntax,true)).'<br />';
		$i++;
	}
	$ret .= '</div>';
	return $ret;
}

/**
 * Get error message from error code
 *
 * @param String $code
 * @param String $ext_msg
 *
 * @return String
 */
function error($code=NULL,$ext_msg=NULL) {
	$ret=R::View('error',$code,$ext_msg);
	return $ret;
}

/**
 * Generate message box
 *
 * @param String $class
 * @param Mixed $message_list
 * @param String $watch_module
 * @return String
 */
function message($class='status',$message_list=array(),$watch_module=NULL) {
	if (empty($message_list)) return;

	$is_accessdenied=false;
	/* add watchdog log on Access denied */
	if (is_string($message_list) && strtolower($message_list)=='access denied') R::Model('watchdog.log',$watch_module,'Access denied');

	if (is_string($message_list)) $message_list=array($message_list);

	$ret='<div class="messages messages-'.$class.'">'._NL;
	$errmsg='<dl>'._NL;
	foreach ($message_list as $item) {
		list($key)=explode(':',$item);
		$description=trim(substr($item,strlen($key)+1));
		if (strtolower($key)=='access denied') {
			$is_accessdenied=true;
			break;
		}
		$key=tr(trim($key));
		if (empty($description)) $description = error($key);
		$errmsg .= '<dt>'.$key.'</dt>'._NL;
		if ($description) $errmsg .= '<dd>'.$description .'</dd>'._NL;
	}
	$errmsg.='</dl>'._NL;
	$ret.=$errmsg;
	$ret.='</div><!--message-->'._NL._NL;

	// Show signform for access denied
	if ($is_accessdenied) {
		if (i()->ok) {
			$ret='<div id="login" class="login -accessdenied">';
			if ($description) $ret.='<p class="notify">'.$description.'</p>';
			$ret.='<div class="-form"><h3>'.tr('Hello').' '.i()->name.'</h3></div><div><h3>'.tr('I already have an account.','เป็นสมาชิกเว็บอยู่แล้ว').'</h3></div><p class="notify">'.tr('Access denied. Please contact web administrator.','สิทธิ์ในการเข้าใช้งานถูกปฏิเสธ กรุณาติดต่อผู้ดูแลเว็บ').'</p>';
			$ret.='</div>';
		} else {
			$ret=R::View('signform','{class="-accessdenied"}');
		}
	}
	return $ret;
}
?>
