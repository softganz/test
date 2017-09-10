/**
 * SoftGanz JavaScript Library
 *
 * @package library
 * @version 3.10
 * @copyright Copyright (c) 2000-present , The SoftGanz Group By Panumas Nontapan
 * @author Panumas Nontapan <webmaster@softganz.com>
 * http://www.softganz.com
 * @created 2009-09-22
 * @modify  2016-12-06
 * ============================================
 * This program is free software. You can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License.
 * ============================================
*/

var thaiMonthName=["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
var defaultRelTarget="#main";
var debugSignIn=false;
var firebaseConfig;


function notify(text,delay) {
	var msg = $('#notify');
	var width = $(document).width();

	if (text==undefined || text==null) text='';
	if (text=='') {
		msg.hide().fadeOut();
		//console.log("Notify hide")
		return;
	}
	//console.log("Notify : "+text)
	msg.html(text)
	.fadeIn()
	.click(function() {$(this).hide()})
	.css({
		'display':'inline-block',
		'left' : width/2 - (msg.width() / 2), // half width - half element width
		'z-index' : 999999 // make sure element is on top
	})
	.show();

	if (delay) setTimeout(function() { msg.fadeOut(); }, delay);
}

fetch_unix_timestamp = function() { return parseInt(new Date().getTime().toString().substring(0, 10))}

/**
 * Editor functions
 */
editor = {
	version : '0.0.3b',
	controls : new Array() ,
	start_tag : '',
	end_tag : '',

	click : function (e) {
		e?evt=e:evt=event;
		var cSrc=evt.target?evt.target:evt.srcElement;
		var elem=document.getElementsByTagName('textarea');
		var ctrl=cSrc.parentNode;
		//		alert(ctrl);
		//alert('click '+ctrl+' : '+ctrl.id+' : '+ctrl.className);
		if (ctrl && ctrl.className=='editor') {
			myField=document.getElementById(ctrl.title);
			//			alert('set myField ',editor.myField.id);
			//			debug('<p>control parent id '+ctrl.id+' : '+ctrl.title+' : '+ctrl.className+' : '+ctrl.parentNode.id+'</p>',false);
			//			debug('insert into id '+myField.id+' tag = '+editor.start_tag+' | '+editor.end_tag);
			if (editor.start_tag) editor.insertCode(myField,editor.start_tag,editor.end_tag);
		}
		editor.start_tag='';
		editor.end_tag='';
	} ,

	insert : function (i,o) {
		if(i == undefined) { i=''; }
		if(o == undefined) { o=''; }
		this.start_tag=i;
		this.end_tag=o;
	} ,

	insertCode : function(myField,i,o) {
		// IE selection support
		if (document.selection) {
			myField.focus();
			sel = document.selection.createRange();
			if (sel.text.length > 0) {
				sel.text = i + sel.text + o;
			} else {
				sel.text = i + o;
			}
			myField.focus();
		// MOZILLA selection support
		} else if (myField.selectionStart || myField.selectionStart == '0') {
			var startPos = myField.selectionStart;
			var endPos = myField.selectionEnd;
			var cursorPos = endPos;
			var scrollTop = myField.scrollTop;
			if (startPos != endPos) {
				myField.value = myField.value.substring(0, startPos)
				+ i
				+ myField.value.substring(startPos, endPos)
				+ o
				+ myField.value.substring(endPos, myField.value.length);
				cursorPos = cursorPos + i.length + o.length;
			} else {
				myField.value = myField.value.substring(0, startPos)
				+ i
				+ o
				+ myField.value.substring(endPos, myField.value.length);
				cursorPos = startPos + i.length;
			}
			myField.focus();
			myField.selectionStart = cursorPos;
			myField.selectionEnd = cursorPos;
			myField.scrollTop = scrollTop;
		// SAFARI and others
		} else {
			myField.value += i+o;
			myField.focus();
		}
	} ,

	url : function (i) {
			var defaultValue = 'http://';
		var url = prompt('enter your url' ,defaultValue);
		if (url == undefined) return;

		// insert BBcode Link
		//		this.insert('[url='+ url + ']','[/url]');

		// insert Markdown Link
		this.insert('[',']('+url+')');
	} ,

	image : function (src) {
			var defaultValue = 'http://';
		if (src == undefined) {
			src = prompt('enter your image location', defaultValue);
			if (src == undefined || src==defaultValue) return;
		}
		// insert BBcode Image
		//		this.insert('[img]'+src,'[/img]');

		// insert Markdown Image
		this.insert('![คำอธิบายภาพ',']('+src+' "ชื่อภาพ")');
	} ,

	emotion : function (id) {
		dom.toggle(id);
	} ,

	color : function (id) {
		this.COLORS = [
			["ffffff", "cccccc", "c0c0c0", "999999", "666666", "333333", "000000"], // blacks
			["ffcccc", "ff6666", "ff0000", "cc0000", "990000", "660000", "330000"], // reds
			["ffcc99", "ff9966", "ff9900", "ff6600", "cc6600", "993300", "663300"], // oranges
			["ffff99", "ffff66", "ffcc66", "ffcc33", "cc9933", "996633", "663333"], // yellows
			["ffffcc", "ffff33", "ffff00", "ffcc00", "999900", "666600", "333300"], // olives
			["99ff99", "66ff99", "33ff33", "33cc00", "009900", "006600", "003300"], // greens
			["99ffff", "33ffff", "66cccc", "00cccc", "339999", "336666", "003333"], // turquoises
			["ccffff", "66ffff", "33ccff", "3366ff", "3333ff", "000099", "000066"], // blues
			["ccccff", "9999ff", "6666cc", "6633ff", "6600cc", "333399", "330099"], // purples
			["ffccff", "ff99ff", "cc66cc", "cc33cc", "993399", "663366", "330033"] // violets
			];

		var cell_width = 14;
		var html = "";
		for (var i = 0; i < this.COLORS.length; i++) {
			for (var j = 0; j < this.COLORS[i].length; j++) {
			html=html+'<img src="/library/img/none.gif" width="'+cell_width+'" height="'+cell_width+'" onClick="editor.insert(\'[color=#' + this.COLORS[i][j] + ']\',\'[/color]\')" style="background-color:#'+this.COLORS[i][j]+';margin:0 1px 1px 0;">';
			}
		}
		var elem=document.getElementById(id);
		if (elem==undefined) return;
		elem.innerHTML = html;
		dom.toggle(id);
	}
}

// drop-down menu
// this function is from gotoknow.org http://gotoknow.org//javascripts/application.js
// bug for IE
sfHover = function (id) {
	if (typeof document.attachEvent=='undefined') return;
	// set default id for user-menu
	if (id==null || id==undefined) id='user-menu';

	// add class property sfhover to LI tags of id
	if (document.getElementById(id)) {
		var sfEls = document.getElementById(id).getElementsByTagName("LI");
		for (var i=0; i<sfEls.length; i++) {
			sfEls[i].onmouseover=function() { this.className+=" sfhover"; }
			sfEls[i].onmouseout=function() { this.className=this.className.replace(new RegExp(" sfhover\\b"), "");}
		}
	}
}

String.prototype.getFuncBody = function(){
	var str=this.toString();
	str=str.replace(/[^{]+{/,"");
	str=str.substring(0,str.length-1);
	str = str.replace(/\n/gi,"");
	if(!str.match(/\(.*\)/gi))str += ")";
	return str;
}
String.prototype.left = function(n) { return this.substring(0, n); }
String.prototype.empty = function() { var str=this.toString().trim(); return str=="" || str=="0"; }

Date.prototype.toW3CString=function(){var f=this.getFullYear();var e=this.getMonth();e++;if(e<10){e="0"+e}var g=this.getDate();if(g<10){g="0"+g}var h=this.getHours();if(h<10){h="0"+h}var c=this.getMinutes();if(c<10){c="0"+c}var j=this.getSeconds();if(j<10){j="0"+j}var d=-this.getTimezoneOffset();var b=Math.abs(Math.floor(d/60));var i=Math.abs(d)-b*60;if(b<10){b="0"+b}if(i<10){i="0"+i}var a="+";if(d<0){a="-"}return f+"-"+e+"-"+g+"T"+h+":"+c+":"+j+a+b+":"+i};

/* init bb click */
if (typeof document.attachEvent!='undefined') {
	//	window.attachEvent("onload", ajax.linkRelationInit);
	document.attachEvent('onclick',editor.click);
} else {
	//	window.addEventListener('load',ajax.linkRelationInit,false);
	document.addEventListener('click',editor.click,false);
}

document.write( "<script type='text/javascript' src='/library/boxover.js'><\/script>" );

$(document).on('click',function(e) {
	window.onscroll=function(){};
});

$(document).on('submit','form.signform, .member-zone', function(e) {
	var $this=$(this);
	//if (typeof debugSignIn === 'undefined') var debugSignIn=false; 
	//var debugSignIn=false;

	//	alert($this.attr('id')+' u='+$this.find("#edit-username").val()+' p='+$this.find("#edit-password").val())
	if ($this.find("#edit-username").val()=="") {
		notify("กรุณาป้อน Username");
		$this.find("#edit-username").focus();
	} else if ($this.find("#edit-password").val()=="") {
		notify("กรุณาป้อน Password");
		$this.find("#edit-password").focus();
	} else {
		notify("กำลังเข้าสู่ระบบ");
		if (debugSignIn) notify("Signin request");
		$.post($this.attr("action"),$this.serialize(),function(html) {
			if (debugSignIn) notify("Start request complete");
			var error=html
			//alert("Sign in process");
			//window.location=document.URL;
			/*
			if(navigator.userAgent.match(/Android/i)) {
				alert("Sign 1");
				//document.location=document.URL;
				//window.open(document.URL);
			} else {
				alert("Sign 2");
				window.location.replace(document.URL);
			}
			*/

			if (html.search('signin-error')==-1) {
				// If Sign In Complete then redirect to current URL
				//alert("Sign in complete");
				if (debugSignIn) notify("Signin complete");
				//window.location=document.URL;
				if((navigator.userAgent.indexOf('Android') != -1)) {
					if (debugSignIn) notify("Sign in from Android "+document.URL);
					$("#primary").html(html);
					//document.location=document.URL;
					//window.open(document.URL);
					//document.location.reload();
					//window.location.href=document.URL;
					//if (debugSignIn) notify("Sign in from Android Complete."+document.URL);
				} else {
					if (debugSignIn) notify("Sign in from Web Browser");
					window.location=document.URL;
				}

			} else {
				// If Sign In Error then show error message
				if (debugSignIn) notify("Signin error");
				var matches = [];
				html.replace(/<div class="signin-error hidden">(.*?)<\/div>/g, function () {
					matches.push(arguments[1]);
				});
				if (debugSignIn) notify(matches);
				notify(matches);
			}
		});
	}
	return false;
});


$(document).tooltip({
	items: ".sg-tooltip",
	tooltipClass:'preview-tip',
	content: function(callback) {
		var $this = $(this)
		console.log("sg-tooltip")
		$.get($this.data('url'),function(html) {callback(html)})
		return 'กำลังโหลด'
	}
});


$(document).ready(function() {
	$('body').prepend('<div id="notify"></div><div id="tooltip"></div><div id="popup"></div><div id="dialog"></div><div id="debug"></div>');
	$("#notify").hide();

	sfHover();

	$('a[href$=".pdf"], a[href*="files/"]').addClass('pdflink');



	$('.sg-load,[data-load]').each(function(index) {
		var $this=$(this);
		var uri=$this.data('url');
		//console.log("Load data from url "+$this.attr("class")+" | "+$this.data("load")+" | "+uri);
		if (uri==undefined) uri=$this.data('load');
		if (uri) {
			if (uri.left(1)!='/') uri=url+uri;
			$.get(uri,function(html) {
				//console.log(html)
				$this.html(html);
			});
		}
	});
});




$(document).on('mousemove','[data-tooltip]', function(e) {
	var moveLeft = 0;
	var moveDown = 0;
	var target = '#tooltip';
	leftD = e.pageX+20// + parseInt(moveLeft);
	maxRight = leftD + $(target).outerWidth();
	windowLeft = $(window).width() - 40;
	windowRight = 0;
	maxLeft = e.pageX - (parseInt(moveLeft) + $(target).outerWidth() + 20);
	var text=$(this).attr("data-tooltip")
	$(target).html(text).show();

	if(maxRight > windowLeft && maxLeft > windowRight) {
		leftD = maxLeft;
	}
	topD = e.pageY +10;//parseInt(moveDown);
	maxBottom = parseInt(e.pageY + parseInt(moveDown) + 20);
	windowBottom = parseInt(parseInt($(document).scrollTop()) + parseInt($(window).height()));
	maxTop = topD;
	windowTop = parseInt($(document).scrollTop());
	if(maxBottom > windowBottom) {
		topD = windowBottom - $(target).outerHeight() - 20;
	} else if(maxTop < windowTop){
		topD = windowTop + 20;
	}
	$(target).css('top', topD).css('left', leftD);
}).on('mouseleave','[data-tooltip]', function(e) {
	var target = '#tooltip';
	$(target).hide();
});


/*

// Set body event
$(document).on('click','[rel]', function(event) {
	// Close dialog box on click
	//	alert($("#popup").dialog("option","modal"))
	//	if ($("#popup").dialog("option","modal")==false) $("#popup").dialog("close");
	//	alert('Rel click')
	//	$("#popup").dialog("close")

	// check click rel=toggle , theater , async , dialog , async-post
	var $target=$(event.target);
	var $linkTarget=$(event.target).closest("a, area");
	var para={};

	var confirmMsg=$linkTarget.data('confirm')?$linkTarget.data('confirm'):($linkTarget.attr("confirm")?$linkTarget.attr("confirm"):null)
	if (confirmMsg) {
		if (confirm(confirmMsg)) {
			para.confirm="yes";
		} else {
			event.stopPropagation();
			return false;
		}
	}

	// notify("Click id="+event.target.id+" tagname="+$target[0].tagName+" rel="+$target.attr("rel")+" rel-target="+$target.attr("rel-target")+"<br />linkTarget rel="+$linkTarget.attr("rel")+" ,linkTarget href="+$linkTarget.attr("href"));

	if ($linkTarget.attr("rel")) {
		var relTarget=$linkTarget.attr("rel-target")?$linkTarget.attr("rel-target"):defaultRelTarget;
		var dataType=$linkTarget.attr("data-type");

		// Load data from link url and show
		notify("กำลังโหลด..."+$linkTarget.attr("href"));
		$("#popup").html("");
		$.get($linkTarget.attr("href"), para, function(data) {
			notify();
			if (isRunOnHost && typeof(_gaq)!='undefined') {_gaq.push(['_trackPageview', $linkTarget.attr("href")]);}
			html=dataType=="json" ? data.html : data;
			if ($linkTarget.attr("rel")=="popup") {
				$("#popup").html(html).dialog({modal: true,position:"center",title:$linkTarget.attr("rel-title")}).mouseleave(function() {});
				$(".ui-dialog-titlebar").show();

				var width='30%';
				if ($linkTarget.data("width")!=undefined) width=$linkTarget.data("width");
				else if ($linkTarget.attr("rel-width")=="full") width=$("#content-wrapper").width();
				else width=$linkTarget.attr("rel-width");
				$("#popup").dialog("option","width",width);

				if ($linkTarget.attr("rel-height")) {
					var height=0;
					if ($linkTarget.attr("rel-height")=="full") height=$(window).height();
					else height=$linkTarget.attr("rel-height");
					$("#popup").dialog("option","height",height);
				}
				$("#popup").dialog("option","position","center");

			} else if ($linkTarget.attr("rel")=="click") {
				//						alert(html);
				$(relTarget).show().html(html);
			} else {
				//						location.hash = $linkTarget.attr("href");
				if ($linkTarget.attr("rel").substring(0,1)=="#") {
					$($linkTarget.attr("rel")).html("");
					//						alert(html);
					$($linkTarget.attr("rel")).show().html(html);
				} else {
					$("#"+$linkTarget.attr("rel")).show().html(html);
				}
			}
			var callbackFunction=$linkTarget.data("callback");
			if (callbackFunction && typeof window[callbackFunction] === 'function') {
				 window[callbackFunction]($linkTarget,html);
			}
			//					if ($linkTarget.attr("callback")) {
			//						var callbackFunction=$linkTarget.attr("callback");
			//						var func = (pastComplete)
			//						func(html);
			//						notify("func="+func+" callback="+callbackFunction);
			//					}
		},dataType);
		event.stopPropagation();
		return false;
	}
});
*/

/*
 * sg-action :: Softganz link action
 * written by Panumas Nontapan
 * http://softganz.com
 * Using <a class="sg-action" data-rel="" data-confirm="" data-removeparent="tag" data-do="" data-callback="">...</a>
 */
$(document).on('click','.sg-action', function(e) {
	var $this=$(this)
	var url=$this.attr('href')
	var rel=$this.data('rel')
	var ret=$this.data('ret')
	var para={}

	if (url=='javascript:void(0)') url=$this.data('url');
	if ($this.data('confirm')!=undefined) {
		if (confirm($this.data('confirm'))) {
			para.confirm='yes'
		} else {
			e.stopPropagation()
			return false
		}
	}
	if ($this.data('do')=='closebox') {
		if ($(e.target).closest('.sg-dropbox.box').length!=0) {
			//alert('Close box '+$(e.target).closest('.sg-dropbox.box').attr('class'))
			$('.sg-dropbox.box').children('div').hide()
			$('.sg-dropbox.box.active').removeClass('active')
		} else $.colorbox.close()
		//e.stopPropagation()
		return false
	}
	notify('กำลังโหลด.........');
	console.log("Load from url "+url)
	if (rel==undefined && ret==undefined) return true;
	$.get(url,para,function(html) {
		console.log("Load from url "+url+" completed.")
		if (ret) {
			$.get(ret,function(html) {
				if (rel=='box') $.colorbox({html:html,width:$('#colorbox').width()});
				else if (rel=='this') $this.html(html);
				else if (rel=='replace') $this.replaceWith(html);
				else if (rel=='after') $this.after(html);
				else if (rel=='notify') notify(html,20000);
				else $('#'+rel).html(html);
				notify()
			})
		} else {
			notify()
			if (rel=="none") ;
			else if (rel=='box') $.colorbox({html:html,width:$('#colorbox').width()});
			else if (rel=='this') $this.html(html);
			else if (rel.substr(0,6)=='parent') {
				//$this.parent().html(html);
				var $ele;
				if (rel=='parent') $ele=$this.parent();
				else {
					var target=rel.substr(7);
					console.log(target)
					$ele=$this.closest(target);
				}
				$ele.html(html);
			} else if (rel.substr(0,7)=='replace') {
				var $ele;
				if (rel=='replace') $ele=$this;
				else {
					var target=rel.substr(8);
					$ele=$(target);
				}
				$ele.replaceWith(html);
			} else if (rel=='after') {
				$this.after(html);
			} else if (rel=='notify') {
				notify(html,20000);
			} else $('#'+rel).html(html);

			if ($this.data('moveto')) {
				var moveto=$this.data('moveto').split(',');
				window.scrollTo(parseInt(moveto[0]), parseInt(moveto[1]));
			}
		}
		if ($this.data('removeparent')) {
			var removeTag=$this.data('removeparent');
			$this.closest(removeTag).remove();
		}
		if ($this.data('closebox')) {
			if ($(e.target).closest('.sg-dropbox.box').length===0) {
				$('.sg-dropbox.box').children('div').hide()
				$('.sg-dropbox.box.active').removeClass('active')
			} else $.colorbox.close()
		}

		// Process callback function
		var callback=$this.data("callback");
		if (callback && typeof window[callback] === 'function') {
			window[callback]($this,html);
		} else if (callback) {
			window.location=callback;
		}
	})
	return false;
});





/*
 * sg-form :: Softganz form
 * written by Panumas Nontapan
 * http://softganz.com
 * Using <form class="sg-form"></form>
 */
$(document).on('submit', 'form.sg-form', function(e) {
	var $this=$(this)
	var rel=$this.data('rel');
	var checkValid=$this.data('checkvalid');
	var errorField='';
	var errorMsg='';
	console.log('sg-form submit of '+$this.attr('id'));
	if (checkValid) {
		console.log('Form Check input valid start.');
		$this.find('.require, .-require').each(function(i) {
			var $inputTag=$(this);
			console.log('Form check valid input tag '+$inputTag.prop("tagName")+' type '+$inputTag.attr('type')+' id='+$inputTag.attr('id'))
			if (($inputTag.attr('type')=='text' || $inputTag.attr('type')=='password' || $inputTag.attr('type')=='hidden' || $inputTag.prop("tagName")=='TEXTAREA') && $inputTag.val().trim()=="") {
				errorField=$inputTag;
				errorMsg='กรุณาป้อนข้อมูลในช่อง " '+$('label[for='+errorField.attr('id')).text()+' "';
				$inputTag.focus();
			} else if ($inputTag.prop("tagName")=='SELECT' && ($inputTag.val()==0 || $inputTag.val()==-1 || $inputTag.val()=='')) {
				errorField=$inputTag;
				errorMsg='กรุณาเลือกข้อมูลในช่อง " '+$('label[for='+errorField.attr('id')).text()+' "';
			} else if (($inputTag.attr('type')=='radio' || $inputTag.attr('type')=='checkbox')
									&& !$("input[name=\'"+$inputTag.attr('name')+"\']:checked").val()) {
				errorField=$inputTag;
				errorMsg=errorField.closest('div').children('label').first().text();
			}
			if (errorField) {
				console.log('Invalid input '+errorField.attr('id'));
				var invalidId=errorField.attr('id');
				$('#'+invalidId).focus();
				$('html,body').animate({ scrollTop: errorField.offset().top-100 }, 'slow');
				notify(errorMsg);
				return false;
			}
		});
		if (errorField) return false;
	}


	if (rel!=undefined) {
		notify('กำลังดำเนินการ');
		console.log('Send form to '+$this.attr('action'));
		console.log('Result to '+rel);
		window.top.document.title='Test'
		$.post($this.attr('action'),$this.serialize(), function(html) {
			console.log('Form submit completed.');
			if ($this.data('complete')=='remove') {
				$this.remove()
			} else if ($this.data('complete')=='closebox') {
				if ($(e.rel).closest('.sg-dropbox.box').length!=0) {
					$('.sg-dropbox.box').children('div').hide()
					$('.sg-dropbox.box.active').removeClass('active')
					//alert($(e.rel).closest('.sg-dropbox.box').attr('class'))
				} else {
					$.colorbox.close()
				}
			}
			console.log('Form output to '+rel);

			if (rel=='none') {
				;//do nothing
			} else if (rel=='notify') {
				notify(html,5000);
			} else if (rel=='this') {
				$this.html(html);
			} else if (rel=='parent') {
				$this.parent().html(html);
			} else if (rel.substr(0,7)=='refresh') {
				var target=rel.substr(8);
				var $ele=$(target);
				if ($ele.data('url')) {
					console.log("Refresh "+target+$ele.data('url'));
					$.get($ele.data('url'),function(html){
						$ele.html(html);
					});
				}
			} else if (rel.substr(0,7)=='replace') {
				var $ele;
				if (rel=='replace') $ele=$this;
				else {
					var target=rel.substr(8);
					$ele=$(target);
				}
				$ele.replaceWith(html);
			} else if (rel=='box') {
				$.colorbox({html:html,width:$('#colorbox').width(),opacity:0.5});
			} else if (rel.substring(0,1)=='.') {
				$this.closest(rel).replaceWith(html);
			} else {
				$('#'+rel).html(html);
			}
			if (rel!='notify') notify()

			// Process callback function
			var callback=$this.data('callback');
			if (callback && typeof window[callback] === 'function') {
				window[callback]($this,html);
			} else if (callback) {
				window.location=callback;
			}
		},$this.data('dataType')==undefined?null:$this.data('dataType'));
		return false
	}
})
.on('keydown', 'form.sg-form input:text', function(event) {
	var n = $("input:text").length
	if(event.keyCode == 13) {
		event.preventDefault()
		var nextIndex = $('input:text').index(this) + 1
		if(nextIndex < n)
			$('input:text')[nextIndex].focus()
		return false
	}
});



/*
 * sg-tabs :: Softganz tabs
 * written by Panumas Nontapan
 * http://softganz.com
 * Using <div class="sg-tabs"><ul class="tabs">tab click</ul><div>tab container</div></div>
 */
$(document).on('click', '.sg-tabs>ul.tabs>li>a', function(e) {
	var $this=$(this)
	var $parent=$this.closest('.sg-tabs')
	var href=$this.attr('href')
	$this.closest('ul').children('li').removeClass('active')
	$this.closest('li').addClass('active')
	if ($this.attr('target')!=undefined) return true;
	if (href.left(1)=='#') {
		$parent.children('div').hide()
		$parent.children($this.attr('href')).show()
	} else {
		notify('กำลังโหลด....')
		//window.history.pushState({},$this.text(),href)
		$.get(href,function(html) {
			$parent.children('div').html(html)
			notify()
		})
	}
	return false
});



/*
 * sg-dropbox :: Softganz dropbox
 * written by Panumas Nontapan
 * http://softganz.com
 * Using sg_dropbox()
 */
$(document).on('click', '.sg-dropbox>a', function() {
	var $parent=$(this).parent()
	var $wrapper=$(this).next()
	var $target=$parent.find('.sg-dropbox--content')

	$('.sg-dropbox.click').not($(this).parent()).each(function() {
		$(this).children('div').hide()
	});
	if ($parent.data('type')=='box') {
		$parent.css('display',"block").addClass('active')
		if ($parent.data('url')!=undefined) {
			$target.html('กำลังโหลด....')
			$wrapper.show()
			$.get($parent.data('url'),function(html) {
				$target.html(html)
			});
		} else $wrapper.show()

	} else if ($parent.data('type')=='click') {
		$wrapper.show()
	} else $wrapper.toggle()
	var offset=$(this).offset()
	var width=$wrapper.width()
	var docwidth=$(document).width()
	var right=0
	if (offset.left+width>docwidth) {
		var right=docwidth-offset.left-$(this).width()-8;//offset.left
		$wrapper.css({'rightside':right+"px"})
	}
	//notify("left: " + offset.left + ", top: " + offset.top+", width="+width+", document width="+docwidth+", right="+right)
 	return false
})
.on('click','body', function(e) {
	$('.sg-dropbox.click').children('div').hide()
	//notify(e.target.className)
	if ($(e.target).closest('.sg-dropbox.box').length===0) {
		$('.sg-dropbox.box').children('div').hide()
		$('.sg-dropbox.box.active').removeClass('active')
	}
});



/*
 * sg-datepicker :: Softganz datepicker
 * written by Panumas Nontapan
 * http://softganz.com
 * Using <input class="sg-form" type="text" data-callback="" />
 */
$(document).on('focus', '.sg-datepicker', function(e) {
	var defaults={
		clickInput: true,
		dateFormat: "dd/mm/yy",
		altFormat: "yy-mm-dd",
		altField: "",
		disabled: false,
		monthNames: thaiMonthName,
		beforeShow: function( el ){
			// set the current value before showing the widget
			$(this).data('previous', $(el).val() );
		},
		onSelect: function(dateText,inst) {
			if( $(this).data('previous') != dateText ){
				if ($(this).data('diff')) {
					// Calculate for date diff into other field
					var $toDate=$('#'+$(this).data('diff'));
					console.log('Calculate date diff to '+$toDate.attr('id'));

					var $fromDate=$(this);
					//var $toDate=$(this).closest('form').find('.sg-checkdateto');
					if ($toDate.val()=='') {
						$toDate.val($fromDate.val());
					} else {
						var diff_date=0;
						var days=24*60*60*1000;
						var prevDateText=$(this).data('previous')?$(this).data('previous'):dateText;
						var prevDateArray=prevDateText.split("/");
						var fromDateArray=$(this).val().split("/");
						var toDateArray=$toDate.val().split("/");

						var prevDate=new Date(prevDateArray[2],prevDateArray[1]-1,prevDateArray[0]);
						var fromDate=new Date(fromDateArray[2],fromDateArray[1]-1,fromDateArray[0]);
						var toDate=new Date(toDateArray[2],toDateArray[1]-1,toDateArray[0]);

						diff_date = Math.round((toDate - prevDate)/days);
						console.log(prevDate+' to '+toDate+' is '+diff_date)
						toDate.setDate(fromDate.getDate()+diff_date);
						$toDate.val($.datepicker.formatDate("dd/mm/yy",toDate));
					}
				}
				$(this).trigger('dateupdated');
			}
			// Process call back
			var callback=$(this).data('callback');
			if (callback) {
				if (callback=='submit') {
					$(this).closest('form').submit()
				} else if (typeof window[callback]==='function') {
					 window[callback](dateText,$(this));
				} else {
					var url=callback+'/'
					window.location=url;
				}
			}
		},
		/*
		onClose: function(dateText,datePickerInstance) {
			console.log("On Close")
			var oldValue = $(this).data('oldValue') || "";
			if (dateText !== oldValue) {
				$(this).data('oldValue',dateText);
				$(this).trigger('dateupdated');
				}
		}
		*/
	}
	var options = $.extend(defaults, $(this).data());
	//if (options.onSelect) 

	$(this).datepicker(options)
});



/*
 * sg-address :: Softganz address
 * written by Panumas Nontapan
 * http://softganz.com
 * Using <input class="sg-address" type="text" />
 */
$(document).on('focus', '.sg-address', function(e) {
	var $this=$(this)
	$this
	.autocomplete({
		source: function(request, response){
			$.get(url+"api/address?q="+encodeURIComponent(request.term), function(data){
				response(data)
			}, "json");
		},
		minLength: 6,
		dataType: "json",
		cache: false,
		select: function(event, ui) {
			this.value = ui.item.label;
			// Do something with id
			if ($this.data('altfld')) $("#"+$this.data('altfld')).val(ui.item.value);
			return false;
		}
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
		if (item.value=='...') {
			return $('<li class="ui-state-disabled -more"></li>')
			.append(item.label)
			.appendTo( ul );
		} else {
			return $( "<li></li>" )
			.append( "<a><span>"+item.label+"</span>"+(item.desc!=undefined ? "<p>"+item.desc+"</p>" : "")+"</a>" )
			.appendTo( ul )
		}
	}

});



/*
 * sg-autocomplete :: Display autocomplete box
 * written by Panumas Nontapan
 * http://softganz.com
 * Using <form><input class="sg-autocomplete" type="text" /></form>
 */
$(document).on('focus', '.sg-autocomplete', function(e) {
	var $this=$(this)
	var minLength=1
	if ($this.data('minlength')) minLength=$this.data('minlength')
	$this
	.autocomplete({
		source: function(request, response){
			var para={}
			para.n=$this.data('item');
			para.q=$this.val();
			console.log("Query "+$this.data('query'))
			notify("กำลังค้นหา");
			$.get($this.data('query'),para, function(data){
				notify();
				response(data);
			}, "json");
		},
		minLength: minLength,
		dataType: "json",
		cache: false,
		open: function() {
			if ($this.data('class')) {
				$this.autocomplete("widget").addClass($this.data('class'));
			}
			if ($this.data('width')) {
				$this.autocomplete("widget").css({"width":$this.data('width')});
			}
		},
		focus: function(event, ui) {
			//this.value = ui.item.label;
			//event.preventDefault();
			return false
		},
		select: function(event, ui) {
			// Return in ui.item.value , ui.item.label
			// Do something with id
			console.log(ui.item.value);
			if ($this.data('altfld')) $("#"+$this.data('altfld')).val(ui.item.value);

			if ($this.data('select')!=undefined) {
				var selectValue=$this.data('select');
				if (typeof selectValue == 'object') {
					console.log(selectValue)
					var x;
					for (x in selectValue) {
						$('#'+x).val(ui.item[selectValue[x]]);
						console.log(x+" "+selectValue[x])
					}
				} else if (typeof selectValue == 'string') {
					$this.val(ui.item[selectValue]);
				}
			} else {
				$this.val(ui.item.label);
			}


			// Process call back
			var callback=$this.data('callback');
			if (callback) {
				if (callback=='submit') {
					//$this.closest('form').triger('submit');
					$(this).closest("form").trigger("submit");
				} else if (typeof window[callback]==='function') {
					 window[callback]($this,ui);
				} else {
					var url=callback+'/'+ui.item.value
					window.location=url;
				}
			}
			return false;
		}
	})
	.autocomplete( "instance" )._renderItem = function( ul, item ) {
		if (item.value=='...') {
			return $('<li class="ui-state-disabled -more"></li>')
			.append(item.label)
			.appendTo( ul );
		} else {
			return $( "<li></li>" )
			.append( "<a><span>"+item.label+"</span>"+(item.desc!=undefined ? "<p>"+item.desc+"</p>" : "")+"</a>" )
			.appendTo( ul )
		}
	}
});




/*
 * sg-box :: Load html from url and display in box
 * written by Panumas Nontapan
 * http://softganz.com
 * Using <a class="sg-box" href="">Text</a>
 */
$(document).on('click','.sg-box', function() {
	var defaults={
		fixed: true,
		opacity: 0.5,
		width: "90%",
		maxHeight: "90%",
		maxWidth: "90%",
	}

	// lock scroll position, but retain settings for later
	var x=window.scrollX;
	var y=window.scrollY;
	window.onscroll=function(){window.scrollTo(x, y);};

	var $this=$(this)
	var group=$this.data("group");
	var options = $.extend(defaults, $this.data());
	if (options.group) options.rel=options.group

	if ($this.data('confirm')!=undefined && !confirm($this.data('confirm'))) {
		return false
	}

	if ($this.attr('href')=='#close') {
		$.colorbox.close()
		return false
	}

	$('.sg-box[data-group="'+group+'"]').each(function(i){
		var $elem=$(this);
		$elem.colorbox(options);
	});
	options.open=true
	$this.colorbox(options);

	// Process callback function
	var callbackFunction=$this.data("callback");
	if (callbackFunction && typeof window[callbackFunction] === 'function') {
		window[callbackFunction]($this,'');
	}

	return false
});



/*
 * Softganz inline upload file
 * written by Panumas Nontapan
 * http://softganz.com
 * Using <form class="sg-upload"><input class="inline-uplaod" type="file" /></form>
 */
$(document).on('change', "form.sg-upload .inline-upload", function() {
	var $this=$(this)
	var $form=$this.closest("form")
	var target=$form.data('rel')
	console.log('Inline upload file start and show result in '+target)
	//notify("<p style=\"background-color:#fff;padding:16px;\"><img src=\"/library/img/loading.gif\" alt=\"Uploading....\"/> กำลังอัพโหลดไฟล์ กรุณารอสักครู่....<br />"+$this.val()+"<br /><img src=\""+$this.val()+"\" /></p>")
	notify("<img src=\"/library/img/loading.gif\" alt=\"Uploading....\"/> กำลังอัพโหลดไฟล์ กรุณารอสักครู่....")
	$form.ajaxForm({
		success: function(data) {
			console.log('Inline upload file complete.');
			if (target) {
				if ($form.data('append')) {
					var insertElement='<'+$form.data('append')+'>'+data+'</'+$form.data('append')+'>';
					$('#'+target).append(insertElement);
				} else if ($form.data('prepend')) {
					var insertElement='<'+$form.data('prepend')+'>'+data+'</'+$form.data('prepend')+'>';
					$('#'+target).prepend(insertElement);
				} else if ($form.data('before')) {
					var insertElement='<'+$form.data('before')+'>'+data+'</'+$form.data('before')+'>';
					console.log($form.data('before'));
					console.log(insertElement)
					$this.closest($form.data('before')).before(insertElement);
				} else {
					$('#'+target).html(data);
				}
			}
			notify("ดำเนินการเสร็จแล้ว.",5000)
			$this.val("")
			$this.replaceWith($this.clone(true))
		}
	}).submit()
});



/*
 * sg-chart :: Display Google chart
 * Written by Panumas Nontapan
 * http://softganz.com
 * Using <div class="sg-chart" data-chart-type="bar" data-options='{}'><h3>Chart Title</h3><table><tbody><tr><td>..</td><td>..</td></tr>...</tbody></table></div>
 */
$(document).ready(function() {
	$('.sg-chart').each(function(index) {
		var $container=$(this);
		var chartId=$container.attr("id");
		var chartTitle=$container.find("h3").text();
		var chartType=$container.data("chartType");
		var $chartTable=$(this).find("table");
		var chartData=[];
		var chartColumn=[];
		var options={};

		if (chartType==undefined) chartType="col"

		console.log("=== sg-chart create "+chartId+" ===")
		console.log('Chart Title : '+chartTitle+' Chart Type : '+chartType)


		var defaults={
						pointSize: 4,
						vAxis: {
							viewWindowMode: "explicit",
						},
						hAxis: {
							textStyle: {
								fontSize:10,
							}
						},
						annotations: {
							textStyle: {
								fontSize:9,
							},
						},
				};
		if ($container.data("series")==2) {
			defaults.series={
											0:{targetAxisIndex:0},
											1:{targetAxisIndex:1},
										}
		}
		var options = $.extend(defaults, $(this).data('options'));
		//options=$(this).data('options');
		//console.log(defaults);

		$.each($chartTable.find('tbody>tr'),function(i,eachRow){
			var $row=$(this)
			//console.log($row.text())
			var rowData=[]
			$.each($row.find('td'),function(j,eachCol){
				var $col=$(this)
				var colKey=$col.attr('class').split(':')
				if (i==0) chartColumn.push([colKey[0],colKey[1],colKey[2]==undefined?'':colKey[2]])
				var colValue
				if (colKey[0]=="string") colValue=$col.text()
				else colValue=Number($col.text().replace(/[^\d\.]/g,''))
				//console.log($col.attr('class')+$col.text())
				rowData.push(colValue)
			})
			chartData.push(rowData)
			//console.log(rowData)
		})
		//console.log('Chart Data')
		//console.log(chartData)
		//console.log('Chart Column : '+chartColumn)

		google.charts.load("current", {"packages":["corechart"]});
		google.charts.setOnLoadCallback(drawChart);

		function drawChart() {
			/*
			options = {
											pointSize: 4,
											vAxis: {
												viewWindowMode: "explicit",
											},
										};
			if ($container.data("series")==2) {
				options.series={
												0:{targetAxisIndex:0},
												1:{targetAxisIndex:1},
											}
			}
			if (chartType=="pie") {
				options = {
												legend: {position: "none"},
												// chartArea: {width:"100%",height:"80%"},
											};
				console.log($container.data("options"))
				if ($container.data("options")) options=$container.data("options");
				//options.legend="label";
				//options.pieSliceText="percent";
				//options.legend.position="labeled";
				//options.legend.position=$container.data("legendSeries")?$container.data("legendSeries"):"none";
												// chartArea: {width:"100%",height:"80%"},
			}
											*/
			options.title=chartTitle;
			//console.log(options);
			var data = new google.visualization.DataTable();
			// Add chart column
			$.each(chartColumn,function(i){
				if (chartColumn[i][2]=='role') data.addColumn({type: chartColumn[i][0], role: 'annotation'});
				else data.addColumn(chartColumn[i][0],chartColumn[i][1]);
			})
			// Add chart rows
			data.addRows(chartData);

			var chartContainer=document.getElementById(chartId)
			var chart
			//var chart = new google.visualization.PieChart(chartContainer);
			if (chartType=="line") {
				chart = new google.visualization.LineChart(chartContainer);
			} else if (chartType=="bar") {
				chart = new google.visualization.BarChart(chartContainer);
			} else if (chartType=="col") {
				chart = new google.visualization.ColumnChart(chartContainer);
			} else if (chartType=="pie") {
				chart = new google.visualization.PieChart(chartContainer);
			} else if (chartType=="combo") {
				chart = new google.visualization.ComboChart(chartContainer);
			}
			if ($container.data("image")) {
				google.visualization.events.addListener(chart, 'ready', function () {
					var imgUri = chart.getImageURI();
					// do something with the image URI, like:
					document.getElementById($container.data("image")).src = imgUri;
				});
			}
			chart.draw(data, options);
		}
	});
});





/*
 * 	Easy Slider 1.5 - jQuery plugin
 *	written by Alen Grakalic
 *	http://cssglobe.com/post/4004/easy-slider-15-the-easiest-jquery-plugin-for-sliding
 *
 *	Copyright (c) 2009 Alen Grakalic (http://cssglobe.com)
 *	Dual licensed under the MIT (MIT-LICENSE.txt)
 *	and GPL (GPL-LICENSE.txt) licenses.
 *
 *	Built for jQuery library
 *	http://jquery.com
 *
 *	markup example for $("#slider").easySlider();
 *
 * 	<div id="slider">
 *		<ul>
 *			<li><img src="images/01.jpg" alt="" /></li>
 *			<li><img src="images/02.jpg" alt="" /></li>
 *			<li><img src="images/03.jpg" alt="" /></li>
 *			<li><img src="images/04.jpg" alt="" /></li>
 *			<li><img src="images/05.jpg" alt="" /></li>
 *		</ul>
 *	</div>
 *
 */
(function($) {
	$.fn.easySlider = function(options){
		// default configuration properties
		var defaults = {
			prevId: 		'prevBtn',
			prevText: 		'Previous',
			nextId: 		'nextBtn',
			nextText: 		'Next',
			controlsShow:	true,
			controlsBefore:	'',
			controlsAfter:	'',
			controlsFade:	true,
			firstId: 		'firstBtn',
			firstText: 		'First',
			firstShow:		false,
			lastId: 		'lastBtn',
			lastText: 		'Last',
			lastShow:		false,
			vertical:		false,
			speed: 			800,
			auto:			false,
			pause:			2000,
			continuous:		false,
			numeric: 		false,
			numericId: 		'controls',
			hoverpause: false,
			beginSlide: {},
			debug: false,
		};
		var options = $.extend(defaults, options);
		var animateCount=0;
		this.each(function() {
			var obj = $(this);
			var $slideMain;
			if (obj.data('slider')==undefined) {
				$slideMain = obj.children().first();
			} else {
				$slideMain = obj.find('.'+obj.data('slider')).children().first();
			}
			var $slideTag = $slideMain.children().first();
			options = $.extend(options, obj.data());
			options.slideTag=$slideTag.prop('tagName');
			var s = $(options.slideTag, $slideMain).length;
			var w = $(obj).width();
			var h = $(obj).height();

			if (options.debug) notify('Slide Main='+$slideMain.prop('tagName')+' Slide Tag='+options.slideTag+' on '+s+' slide Option='+options);

			$(options.slideTag, $slideMain).width(w).css({'overflow':'hidden', 'position':'relative'});
			$(options.slideTag, $slideMain).height(h);
			obj.css("overflow","hidden");
			var ts = s-1;
			var t = 0;
			$slideMain.css({'width': s*w, 'margin':0, 'padding':0, 'list-style':'none'});
			if(!options.vertical) $(options.slideTag, $slideMain).css('float','left');

			if(options.controlsShow){
				var html = options.controlsBefore;
				if(options.firstShow) html += '<span id="'+ options.firstId +'"><a href=\"javascript:void(0);\">'+ options.firstText +'</a></span>';
				html += ' <span id="'+ options.prevId +'"><a href=\"javascript:void(0);\">'+ options.prevText +'</a></span>';
				html += ' <span id="'+ options.nextId +'"><a href=\"javascript:void(0);\">'+ options.nextText +'</a></span>';
				if(options.lastShow) html += ' <span id="'+ options.lastId +'"><a href=\"javascript:void(0);\">'+ options.lastText +'</a></span>';
				html += options.controlsAfter;
				$(obj).after(html);
			};
			$("a","#"+options.nextId).click(function(){ animate("next",true); });
			$("a","#"+options.prevId).click(function(){ animate("prev",true); });
			$("a","#"+options.firstId).click(function(){ animate("first",true); });
			$("a","#"+options.lastId).click(function(){ animate("last",true); });

			function animate(dir,clicked){
				if (options.debug) notify('Slide Main='+$slideMain.prop('tagName')+' Slide Tag='+options.slideTag+' on '+s+' slide '+(++animateCount)+' Option='+options.controlsShow);
				//options.onBeginSlide.call($slideMain);
				var ot = t;
				switch(dir){
					case "next":	t = (ot>=ts) ? (options.continuous ? 0 : ts) : t+1; break;
					case "prev":	t = (t<=0) ? (options.continuous ? ts : 0) : t-1; break;
					case "first":	t = 0; break;
					case "last":	t = ts; break;
					default:			break;
				};

				if($.isFunction(options.onBeginSlide)) {
					// call user provided method
					options.onBeginSlide.call(this,t);
				}

				var diff = Math.abs(ot-t);
				var speed = diff*options.speed;
				if(!options.vertical) {
					p = (t*w*-1);
					$slideMain.animate( { marginLeft: p }, speed );
				} else {
					p = (t*h*-1);
					$slideMain.animate( { marginTop: p }, speed );
				};
				if(!options.continuous && options.controlsFade){
					if(t==ts){
						$("a","#"+options.nextId).hide();
						$("a","#"+options.lastId).hide();
					} else {
						$("a","#"+options.nextId).show();
						$("a","#"+options.lastId).show();
					};
					if(t==0){
						$("a","#"+options.prevId).hide();
						$("a","#"+options.firstId).hide();
					} else {
						$("a","#"+options.prevId).show();
						$("a","#"+options.firstId).show();
					};
				};
				if(clicked) clearTimeout(timeout);
				if(options.auto && dir=="next" && !clicked){;
					timeout = setTimeout(function(){
						animate("next",false);
					},diff*options.speed+options.pause);
				};

			};
			// init
			var timeout;
			if(options.auto){;
				timeout = setTimeout(function(){
					animate("next",false);
				},options.pause);
			};
			if(options.hoverpause && options.auto){
            $(this).mouseover(function(){
                clearTimeout(timeout);                  
            }).mouseout(function(){
                animate("next",false);                  
            })
			}
			if(!options.continuous && options.controlsFade){
				$("a","#"+options.prevId).hide();
				$("a","#"+options.firstId).hide();
			};
		});
	};

	$(document).ready(function() {
		$(".sg-slider").easySlider({
			auto: true,
			continuous: true,
			pause: 5000,
			speed: 500,
			controlsShow: false,
			debug: false,
		});
	});

})(jQuery);







/*
 * A quick plugin which implements phpjs.org's number_format as a jQuery
 * plugin which formats the number, and inserts to the DOM.
 *
 * By Sam Sehnert, teamdf.com — http://www.teamdf.com/web/jquery-number-format/178/
 */
(function($){
	$.fn.number = function( number, decimals, dec_point, thousands_sep ){
			number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
			var n = !isFinite(+number) ? 0 : +number,
					prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
					sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
					dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
					s = '',
					toFixedFix = function (n, prec) {
							var k = Math.pow(10, prec);
							return '' + Math.round(n * k) / k;
					};
			// Fix for IE parseFloat(0.55).toFixed(0) = 0;
			s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
			if (s[0].length > 3) {
					s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
			}
			if ((s[1] || '').length > prec) {
					s[1] = s[1] || '';
					s[1] += new Array(prec - s[1].length + 1).join('0');
			}
			// Add this number to the element as text.
			this.text( s.join(dec) );
	};
})(jQuery);

/*!
 * Responsive Menu v0.0.0 by @softganz
 * Copyright 2013 Softganz Group.
 * Licensed under http://www.apache.org/licenses/LICENSE-2.0
 *
 * Designed and built with all the love in the world by @softganz.
 */

if (typeof jQuery === "undefined") { throw new Error("Responsive Menu requires jQuery") }

+function ($) { "use strict";

	// ResponsiveMenu PUBLIC CLASS DEFINITION
	// ==============================

	var ResponsiveMenu = function (element, options) {
		this.options		=
		this.enabled		=
		this.$element   = null

		this.init(element, options)
	}

	ResponsiveMenu.DEFAULTS = {
		test: 'loading...',
	}

	ResponsiveMenu.prototype.init = function (element, options) {
		this.enabled  = true
		this.$element = $(element)
		this.options  = this.getOptions(options)
		this.$element.prepend('<button type="button" class="sg-navtoggle" aria-hidden="true"><i aria-hidden="true" class="icon-menu"><span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span></i><span>Menu</span></button>')
		var $parent=this.$element
		$(this.$element).on('click','.sg-navtoggle', function() {
			$parent.toggleClass('active')
		})
	}

	ResponsiveMenu.prototype.getDefaults = function () {
		return ResponsiveMenu.DEFAULTS
	}

	ResponsiveMenu.prototype.getOptions = function (options) {
		options = $.extend({}, this.getDefaults(), this.$element.data(), options)
		return options
	}

	// ResponsiveMenu PLUGIN DEFINITION
	// ========================

	var old = $.fn.button

	$.fn.responsivemenu = function (option) {
		return this.each(function () {
			var $this			= $(this)
			var data			= $this.data('sg.responsivemenu')
			var options	= typeof option == 'object' && option

			if (!data) $this.data('sg.responsivemenu', (data = new ResponsiveMenu(this, options)))
			if (typeof option == 'string') data[option]()
		})
	}

	$.fn.responsivemenu.Constructor = ResponsiveMenu

	// ResponsiveMenu NO CONFLICT
	// ==================

	$.fn.responsivemenu.noConflict = function () {
		$.fn.responsivemenu = old
		return this
	}

	$(document).ready(function() {
		$('.sg-responsivemenu').responsivemenu()
	})
}(jQuery);

