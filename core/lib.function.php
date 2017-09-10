<?php
/**
 * Get date and time of today
 *
 * @return Object
 */
function today() {
	if (cfg('server.timezone.offset')) {
		$today= getdate(mktime(date('H')+cfg('server.timezone.offset') , date('i') , date('s'), date('m')  , date('d') , date('Y')));
	} else $today=getdate();
	$today['date']=$today['year'].'-'.sprintf('%02d',$today['mon']).'-'.sprintf('%02d',$today['mday']);
	$today['datetime']=$today['date'].' '.sprintf('%02d',$today['hours']).':'.sprintf('%02d',$today['minutes']).':'.sprintf('%02d',$today['seconds']);
	$today['time']=$today[0];
	unset($today[0]);
	return (object)$today;
}

/**
 * Set website title
 *
 * @param String $title
 */
function title($str=NULL) { $GLOBALS['title']=$str; };

/**
 * Set & get module data property
 *
 * @param Mixed $name
 * @param Mixed $value
 */
function property($name=NULL,$value=NULL) {
	static $property=array();
	if (is_string($name)) {
		list($module,$name,$propid,$item)=explode(':',$name);
	} else if (is_numeric($name)) {
		$propid=intval($name);
	} else if (is_array($name)) {
		list($module,$name,$propid,$item)=array($name);
	}
	if ($module=='') $module=NULL;
	if ($name=='') $name=NULL;
	if ($propid=='') $propid=NULL;
	if ($item=='') $item=NULL;
	//	echo 'module='.(is_null($module)?'NULL':$module).' name='.(is_null($name)?'NULL':$name).' propid='.(is_null($propid)?'NULL':$propid).' item='.(is_null($item)?'NULL':$item).' value='.$value.'<br />';
	if ($module && $name && isset($value)) {
		$property[$module][$propid][$name]=$ret=$value;
		$stmt='INSERT INTO %property% (`module`, `propid`, `name`, `item`, `value`) VALUES (:module, :propid, :name, :item, :value)
						ON DUPLICATE KEY UPDATE `value`=:value;';
		mydb::query($stmt,':module',$module, ':propid',is_null($propid)?0:$propid, ':name',$name, ':item', $item?$item:'', ':value',$value);
	//		echo '<br /><br />'.mydb()->_query;
	} else if ($module && $name && isset($propid) && isset($item)) {
		$stmt='SELECT `value` FROM %property% WHERE `module`=:module AND `propid`=:propid AND `name`=:name AND `item`=:item LIMIT 1';
		$property[$module][$propid][$name]=$ret=mydb::select($stmt,':module',$module, ':propid',$propid, ':name',$name, ':item',$item)->value;
	} else if ($module && $name && isset($propid)) {
		$stmt='SELECT `value` FROM %property% WHERE `module`=:module AND `propid`=:propid AND `name`=:name LIMIT 1';
		$property[$module][$propid][$name]=$ret=mydb::select($stmt,':module',$module, ':propid',$propid, ':name',$name)->value;
	} else if ($module && isset($propid)) {
		$stmt='SELECT `name`, `value` FROM %property% WHERE `module`=:module AND `propid`=:propid';
		foreach ($dbs=mydb::select($stmt,':module',$module, ':propid',$propid)->items as $rs) {
			$property[$module][$propid][$rs->name]=$rs->value;
		}
		$ret=$property[$module][$propid];
	} else if ($module && $name && isset($item)) {
		$stmt='SELECT `name`, `value` FROM %property% WHERE `module`=:module AND `name`=:name AND `propid`=0 AND `item`=:item LIMIT 1';
		$rs=mydb::select($stmt,':module',$module,':name',$name, ':item',$item);
		$ret=$rs->value;
	} else if ($module && $name) {
		$stmt='SELECT `name`, `item`, `value` FROM %property% WHERE `module`=:module AND `name`=:name AND `propid`=0';
		$dbs=mydb::select($stmt,':module',$module,':name',$name);
		foreach ($dbs->items as $rs) if ($rs->item) $ret[$rs->name][$rs->item]=$rs->value; else $ret[$rs->name]=$rs->value;
	} else if ($module) {
		$stmt='SELECT `name`, `item`, `value` FROM %property% WHERE `module`=:module AND `propid`=0 ';
		$dbs=mydb::select($stmt,':module',$module);
		foreach ($dbs->items as $rs) if ($rs->item) $ret[$rs->name][$rs->item]=$rs->value; else $ret[$rs->name]=$rs->value;
	}
	return $ret;
}

/**
 * Generate usermenu item
 *
 * @param Array $items
 * @param Boolean $is_first
 * @return String
 */
function _user_menu($items,$is_first=true) {
	$ret = '<ul'.($is_first?' id="user-menu" class="user-menu"':'').'>'._NL;
	foreach ($items as $mid=>$item) {
		$ret .= '<li'.($item->_level=='head'?' class="'.$item->_level.'"':'').' id="user-menu-'.$mid.'">';
		if ($item->_url) {
			$ret .= '<a'.($item->_level=='head'?' class="'.$item->_level.(isset($item->class)?' '.$item->class:'').'"':(isset($item->class)?' class="'.$item->class.'"':''));
			$ret .= ' href="'.$item->_url.'"';
			$ret .= isset($item->attr)?' '.$item->attr:'';
			$ret .= (isset($item->{"data-rel"})?' data-rel='.$item->{"data-rel"}:'');
			$ret .= (isset($item->rel)?' rel='.$item->rel:'');
			if (isset($item->title)) $ret.=' title="'.addslashes($item->title).'"';
			$ret .= '>';
		}
		$ret .= $item->_text;
		if ($item->_url) $ret .= '</a>';
		unset($submenus);
		foreach ($item as $key=>$submenu) if (is_object($submenu)) $submenus->$key=$submenu;
		if (isset($submenus)) $ret .= _NL._user_menu($submenus,false);
		$ret .= '</li>'._NL;
	}
	$ret .= '</ul>'._NL;
	return $ret;
}

/**
 * Generate user menu for ribbon
 *
 * @param Mixed
 * @return String on no parameter
 *
 * @usage
 * user_menu(name[:option] , text , url [ , attr1=value1 , attr2=value2 , ...) // add top level menu
 * user_menu(top_name[:option] , sub_name , text , url [ , attr1=value1 , attr2=value2 , ...) // add pulldown menu
 * user_menu()
 */
function user_menu() {
	static $items=NULL;
	$args=func_get_args();
	if (isset($args[0])) {
		$level='';
		$option='';
		if (strpos($args[0],':')===false) $level=$args[0]; else list($level,$option)=explode(':',$args[0]);
		if ($level && $option=='remove') {
			unset($items->{$level});
		} else if (!isset($items->{$level}) or $option=='replace') {
			$items->{$level}->_level='head';
			$items->{$level}->_text=$args[1];
			$items->{$level}->_url=$args[2];
			if (isset($args[3])) {
				$paras=array_slice($args,3);
				$paras=para($paras);
				foreach ($paras as $key=>$value) $items->{$level}->{$key}=$value;
			}
			if ($option=='first') property_reorder($items,$level,'top');
		} else {
			$items->{$level}->{$args[1]}->_level=$level;
			$items->{$level}->{$args[1]}->_text=$args[2];
			$items->{$level}->{$args[1]}->_url=$args[3];
			if (isset($args[4])) {
				$paras=array_slice($args,4);
				if (is_string($paras[0]) && substr($paras[0], 0,1)=='{') {
					$paras=sg_json_decode($paras[0]);
				} else {
					$paras=para($paras);
				}
				foreach ($paras as $key=>$value) $items->{$level}->{$args[1]}->{$key}=$value;
			}
		}
	} else if ($items && is_object($items) && count((array)$items)) {
		return _user_menu($items);
	}
}

function options($module=NULL,$orgid=NULL,$tpid=NULL) {
	static $optionsAll=array();
	$prevStr=$module.'.options.';
	if ($module && !array_key_exists($module, $optionsAll)) {
		$optionsAll[$module]=new stdClass();
		foreach (cfg() as $key => $value) {
			if (substr($key,0,strlen($prevStr))!=$prevStr) continue;
			$optionsAll[$module]->{substr($key,strlen($prevStr))}=$value;
		}
		//debugMsg(print_o($optionsAll[$module],'$optionsAll['.$module.']'));
	}
	if ($module) {
		$options=$optionsAll[$module];
		if ($orgid) {
			$options->getOptionFromOrg=true;
			$options->orgid=$orgid;
		}
		if ($tpid) {
			$options->getOptionFromProject=true;
			$options->tpid=$tpid;
		}
	} else {
		$options=$optionsAll;
	}
	return $options;
}

/**
 * Get api from external website
 *
 * @param String $host
 * @param Int $port
 * @param String $username
 * @param String $password
 *
 * @return String
 */
function getapi($host,$port=NULL,$username=NULL,$password=NULL) {
	// Get file from camera with curl function
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $host);
	if ($username && $password) curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_ALL);
	if ($port) curl_setopt($ch, CURLOPT_PORT, $port);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	//curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	//$headers = array("Cache-Control: no-cache",);
	//curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	//curl_setopt($ch, CURLOPT_FILE, $fh);

	$result = curl_exec($ch);
	$info = curl_getinfo($ch);
	$info['error'] = curl_error($ch);
	curl_close($ch);
	if (substr($result,0,1)=='{') {
		$info['result']=json_decode($result);
	} else {
		$info['result']=$result;
	}
	return $info;
}

/**
 * Get first value of parameter that not null and not empty string
 *
 * @param Mixed $arg1..$argn
 *
 * @return Mixed
 */
function get_first() {
	for ( $i=0; $i<func_num_args(); $i++ ) if (!(is_null(func_get_arg($i)) || func_get_arg($i)==='')) return func_get_arg($i);
	return NULL;
}

/**
 * Show notify box
 *
 * @param String $str
 *
 * @return String
 */
function notify($str='',$time=5000) {
	if (is_array($str)) $str=implode(' , ',$str);
	$ret='<script type="text/javascript">$(document).ready(function(){notify("'.addslashes($str).'",'.$time.');});</script>';
	return $ret;
}

/**
 * PHP Evaluate
 *
 * @param String $str
 * @param String $prefix
 * @param String $postfix
 *
 * @return String
 */
function eval_php($str=null,$prefix=NULL,$postfix=NULL) {
	if (!isset($str)) return false;
	ob_start();
	eval('?>'.$str);
	return $prefix.ob_get_clean().$postfix;
}

/**
 * Convert option string seperate by colon ( , ) into array with each option key
 *
 * @param Mixed $o
 * @param String $key
 * @return Mixed
 *
 * option->_src = source text
 * option->_text = text
 * option->_value = value in array
 * option->{$key} = true if key was exists
 */
function option($o=NULL,$key=NULL) {
	$option=NULL;
	if ($o && is_string($o)) {
		$option->_src=$o;
		$option->_text='\''.implode('\',\'',explode(',',$o)).'\'';
		$option->_value=explode(',',$o);
		foreach (explode(',',$o) as $ok) $option->$ok=true;
	} else if ($o && is_object($o)) {
		$option->_src=$o;
		foreach ($o as $k=>$v) {
			$option->_value[]=$v;
			$option->$k=$v;
		}
	}
	return isset($key) ? $option->$key:$option;
}

/**
 * Convert array parameter to name parameter and convert option string separate with , to array with option key is true
 *
 * @param Mixed
 * @return Object
 */
function para() {
	$result=new stdClass();
	$args=func_get_args();
	$argc=func_num_args();
	$from=0;
	if (is_numeric($args[$argc-1])) $from=array_pop($args);
	//echo print_o($args,'$args').'<br />From : '.$from.'<br />';

	// set first argument to main parameter
	$para=array_shift($args);
	if (is_array($para)) /*do nothing */;
	else if (is_string($para)) $para=explode('/',$para);
	else if (is_object($para)) $para=(array)$para;
	else $para=array();
	if ($from) $para=array_slice($para,$from);
	array_walk($para,'_trim');

	// set other argument to be default
	foreach ($args as $item) {
		if (!is_string($item)) continue;
		if (preg_match('/([a-zA-Z0-9\-_]*)=(.*)/',$item,$out)) $result->{$out[1]}=$out[2];
	}

	$_src='';
	while ($para) {
		$key=array_shift($para);
		// หาก key เป็น object ให้เอาค่าทั้งหมดมา
		if (is_object($key)) {
			$_src.='Object=(';
			foreach ($key as $k=>$v) {
				$result->{$k}=$v;
				$_src.=$k.'='.(is_object($v)?'(Object)':$v).',';
			}
			$_src=substr($_src,0,-1);
			$_src.=')/';
			continue;
		}
		// หาก key ไม่เป็น string หรือ ว่างเปล่า แสดงว่าผิดพลาด
		if (!is_string($key) || trim($key)=='') {
			$_src.='(*error key is '.gettype($key).'*)/';
			continue;
		}
		// หาก key มีเครื่องหมาย = ให้แยก key ออกเป็น key กับ value เช่น key คือ detail=สวัสดี
		if (preg_match('/^([a-zA-Z0-9\-_]*)=(.*)/s',$key,$out)) {
			$result->{$out[1]}=$out[2];
			$_src.=$key.',';
			continue;
		}
		$value=array_shift($para);
	/*
			echo 'Key='.$key.' Value='.$value.'<br />';
			if ($value && is_string($value) && preg_match('/^([a-zA-Z0-9\-_]*)=(.*)/s',$value,$out)) {
				echo 'Out = '.print_r($out,1).'<br />';
				if (empty($out[1])) continue;
				$result->{$out[1]}=$out[2];
				$_src.=$key.'='.(is_object($value)?'(Object)':$value).'/';
				continue;
			}
	*/
		$result->{$key}=$value;
		$_src.=$key.'/'.(is_object($value)?'(Object)':$value).'/';
	}

	$_src=substr($_src,0,-1);
	if (isset($result->_src)) unset($result->_src);
	$result->_src=$_src;
	if (isset($result->option) && is_string($result->option)) {
		$option=$result->option;
		unset($result->option);
		$result->option=option($option);
	}
	return $result;
}
?>