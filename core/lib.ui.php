<?php
/**
* SOFTGANZ :: lib.ui.php
*
* Copyright (c) 2000-2006 The SoftGanz Group By Panumas Nontapan
* Authors : Panumas Nontapan <webmaster@softganz.com>
*                : http://www.softganz.com/
* ============================================
* This module is core of web application
*
* This program is free software. You can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License.
* ============================================

--- Created 2007-07-09
--- Modify   2017-07-09
*/

/********************************************
 * Class :: BaseUi
 * BaseUi class for base of all Ui
********************************************/
class BaseUi extends stdClass {
	var $config;
	function addClass($class) {
		$this->config->class.=' '.$class;
	}

	function addConfig($key,$value) {
		$this->config->{$key}=$value;
	}

	function addAttr($key,$value) {
		$this->config->attr[$key]=$value;
	}

	function addData($key,$value) {
		$this->config->data['data-'.$key]=$value;
	}
}





/********************************************
 * Class :: Ui
 * Ui class for create ui
********************************************/
class Ui extends BaseUi {
	var $actions=array();
	var $seperator=' · ';
	var $join='ul';
	var $class='ui-action';
	var $uiItemClass='uiitem ui-item';
	var $wrapperType=array('ul'=>'li','span'=>'span','div'=>'div');
	function ui($join=NULL,$class=NULL) {
		if($join) $this->join=$join;
		if ($class) $this->class=(substr($class,0,1)=='-'?$this->class.' ':'').$class;
	}
	function add($link,$options='{}') {
		if ($link) {
			$this->actions[]=$link;
			$this->options[]=$options;
		}
	}
	function clear() {$this->actions=array();}
	function count() {return count($this->actions);}
	function show($join=NULL,$class=NULL) {
		$attrText='';
		if (!$join) $join=$this->join;
		$class=$this->class.($class?' '.$class:'');
		$ret='';
		if ($this->actions) {
			$attrs=array();
			if (is_string($class)) $attrs['class']=$class;
			else if (is_array($class)) $attrs=$class;
			foreach ($attrs as $key => $value) $attrText.=$key.'="'.$value.'" ';
			$attrText=trim($attrText);
			$ret.='<'.$join.' '.$attrText.'>';
			foreach ($this->actions as $key=>$value) {
				if ($this->options[$key]) {
					$options=sg_json_decode($this->options[$key]);
				}
				$uiItemClass=$this->uiItemClass.($options->class?' '.$options->class:'');
				if ($value=='<sep>') {
					$uiItemClass.=' -sep';
					$value='<hr size="1" />';
				}
				$uiItemTag=$this->wrapperType[$join];
				$ret.=($uiItemTag?'<'.$uiItemTag.' class="'.$uiItemClass.'">':'').$value.($uiItemTag?'</'.$uiItemTag.'>':'');
			}
			$ret.='</'.$join.'>'._NL;
		}
		return $ret;
	}
}





/********************************************
 * Class :: Form
 * Form class for create form
********************************************/
class Form extends BaseUi {
	var $config;

	function __construct($variable=NULL,$action=NULL,$id=NULL,$class='form') {
		if ($variable) $this->config->variable=$variable;
		if ($action) $this->config->action=$action;
		if ($id) $this->config->id=$id;
		$this->config->method='post';
		if (isset($class)) $this->config->class=$class;
	}

	function addField($key,$value) {
		$this->{$key}=$value;
	}

	function show($formId=NULL) {
		return R::View('form',$formId,$this);
	}

	function get($id) {
		$result=R::View('form',$formId,$this,NULL,'array');
		return $id?$result[$id]:$result;
	}
} // End of class Form





/********************************************
 * Class :: Table
 * Table class for show datable in table style
********************************************/
class Table extends BaseUi {
	var $config=array('showHeader'=>true,'repeatHeader'=>0);
	var $class='item';
	var $rows=array();

	function __construct($class) {
		if ($class) $this->class=$class;
	}

	function addClass($class) {
		$this->class.=' '.$class;
	}

	function addConfig($key,$value) {
		$this->config[$key]=$value;
	}

	function show() {
		return R::View('table',$this);
	}
} // End of class Table



/********************************************
 * Class :: PageNavigator
 * PageNavigator class for show page vavigator
********************************************/

class PageNavigator {
var $items_per_page = 10;
var $total_items = 0;
var $current_page = 1;
var $page_to_show = 5;
var $link_url = '';
var $total_page=0;
var $cleanurl=true;

function PageNavigator($itemsPerPage=NULL,$currentPage=NULL,$total=NULL,$url=NULL,$cleanurl=true,$linkPara=array()) {
	if ( IsSet($total) ) $this->TotalItems($total);
	if ( IsSet($itemsPerPage) ) $this->ItemsPerPage($itemsPerPage);
	if ( IsSet($currentPage) ) $this->CurrentPage($currentPage);
	if ( IsSet($url) ) $this->LinkURL($url);
	if ( IsSet($cleanurl)) $this->cleanurl=$cleanurl;
	if ( $linkPara ) $this->linkPara=$linkPara;
	$this->_make();
}

/** Public Property ItemsPerPage : items per page */
function ItemsPerPage($newValue=NULL) {
	if ( IsSet($newValue) ) {
		if ( $newValue == "all" ) $this->items_per_page = $this->TotalItems();
		else $this->items_per_page = IntVal($newValue);
	}
	if ( $this->items_per_page == 0 ) $this->items_per_page = 5;
	return $this->items_per_page;
}

/** Public Property TotalItems : Total items */
function TotalItems($newValue=NULL) {
	if ( IsSet($newValue) ) $this->total_items = IntVal($newValue);
	return $this->total_items;
}

/** Public Property CurrentPage : current page */
function CurrentPage($newValue=NULL) {
	if ( IsSet($newValue) ) $this->current_page = IntVal($newValue);
	return $this->current_page;
}

/** Public Property PageToShow : page to show */
function PageToShow($newValue=NULL) {
	if ( IsSet($newValue) ) $this->page_to_show = IntVal($newValue);
	return $this->page_to_show;
}

/** Public Property TotalPage : find total page */
function TotalPage() {
	if ( $this->ItemsPerPage() == 0 ) $this->ItemsPerPage();
	$this->total_page=Ceil($this->TotalItems() / $this->ItemsPerPage());
	return $this->total_page;
}
function FirstItem() {
	return $this->first_item=($this->CurrentPage()-1)*$this->ItemsPerPage();
}

/** Public Property IsDisplayItem */
function IsDisplayItem($no) {
	return $this->is_display_item=Ceil(++$no/$this->ItemsPerPage()) == $this->CurrentPage();
}

/** Public Property IsOverCurrentPage */
function IsOverCurrentPage($no) {
	return Ceil(++$no/$this->ItemsPerPage()) > $this->CurrentPage();
}

/** Public Property LinkUrl */
function LinkURL($newValue=NULL) {
	if ( IsSet($newValue) ) $this->link_url = $newValue;
	return $this->link_url;
}

function LinkAddress($page='1') {
	if ($this->linkPara) {
		$url=$this->linkUrl();
		$linkPara=$this->linkPara;
		$linkPara['page']=$page;
	} else if (preg_match('/\%page\%/',$this->LinkURL())) {
		$linkUrl = str_replace("%page%",$page,$this->LinkURL());
		$url = (preg_match("/ /",$linkUrl) ? Preg_Replace("/ /","%20",$linkUrl) : $linkUrl);
	} else if (preg_match('/page\/[0-9]*/',$this->LinkURL())) {
		$url=preg_replace('/page\/[0-9]*/','page/'.$page,$this->LinkURL());
//	echo 'page : '.$page.' of '.$this->LinkUrl().' = '.$url.'<br />';
	} else {
		$url=$this->LinkURL().'/page/'.$page;
	}
	return url($url,$linkPara);
}

/** Public Method Show */
function Show() { echo $this->ShowString(); }

/** Public Property ShowString */
function ShowString() {
	$totalPage=$this->TotalPage();
	$currentPage = $this->CurrentPage() == 0 ? 1 : $this->CurrentPage();
	$startPage = $currentPage - Floor($this->PageToShow() / 2);
	if ( $startPage < 1 ) $startPage = 1;
	$endPage = $startPage + $this->PageToShow() - 1;
	if ( $endPage > $totalPage ) $endPage = $totalPage;
	$showStr = '<!-- start of page Navigator -->'._NL;
	$showStr .= '<div class="page-nv">'._NL;
	$showStr .= '<span class="page-items">'.$this->TotalItems().' items</span>';
	if ( $currentPage > 1 ) {
		$showStr .= '<a class="page-first active" href="'.$this->LinkAddress('1').'" title="first page">';
		$showStr .= '|&laquo; First';
		$showStr .= '</a>'._NL;
		$showStr .= '<a class="page-prev active" href="'.$this->LinkAddress($currentPage-1).'" title="previous page is '.($currentPage-1).'">';
		$showStr .= '&laquo; Prev';
		$showStr .= '</a>'._NL;
	}
	for ( $i = $startPage; $i <= $endPage; $i++ ) {
		if ( $i == $this->CurrentPage() ) {
			$showStr .= '<span class="page-current">('.$i.'/'.$totalPage.')</span>'._NL;
		} else {
			$showStr .= '<a class="page-other active" href="'.$this->LinkAddress($i).'" title="page '.$i.' from '.$totalPage.'">'.$i.'</a>'._NL;
		}
	}
	if ( $currentPage < $totalPage ) {
		$showStr .= '<a class="page-next active" href="'.$this->LinkAddress($currentPage+1).'" title="next page is '.($currentPage+1).'">';
		$showStr .= 'Next &raquo;';
		$showStr .= '</a>'._NL;
		$showStr .= '<a class="page-last active" href="'.$this->LinkAddress($totalPage).'" title="last page is '.$totalPage.'">';
		$showStr .= 'Last &raquo;|';
		$showStr .= '</a>'._NL;
	}
	$showStr .= '</div><!--page-nv-->';
	$showStr .= '<!-- end of page Navigator -->'._NL;
	return $showStr;
}

function _make() {
	if ( $this->TotalPage() > 1 ) $this->show = $this->ShowString();
}
}//--- End Of Class PageNavigator
?>