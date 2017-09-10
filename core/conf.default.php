<?php
$cfg['version.autoupgrade']=false;

$cfg['version.current']='4.00.0'; // 4.00.0 Current version will compare with version.install for upgrade database table
$cfg['version.release']='2017-07-29';

$cfg['error_reporting']=E_ALL & ~E_STRICT & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED;
//$cfg['error_reporting']=E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT;
//$cfg['error_reporting']=E_ALL ^ E_NOTICE ^ E_WARNING;

/* website charactor encoding */
$cfg['client.characterset']='utf-8'; // default is UTF-8 or tis-620

$cfg['lang']='th';

$cfg['web.title']='My Website';
$cfg['web.slogan']=null;

$cfg['web.primary']=true;
$cfg['web.secondary']=true;

$cfg['web.status']=1;
$cfg['web.offline_message']=$cfg['web.title'].' is currently under maintenance. We should be back shortly. Thank you for your patience.';

$cfg['web.readonly']=false;
$cfg['web.readonly_message']='ขออภัย ขณะนี้เว็บไซท์ของดการสร้างหัวข้อใหม่และการแสดงความคิดเห็นไว้ชั่วคราว';

$cfg['web.splash.time']=0; // in minute

$cfg['web.iefix']=true;

$cfg['server']=true;

/* encription key for user password */
$cfg['encrypt_key'] = '# place your key for password encryption here!!!.';

$cfg['dateformat'] = 'F,d Y H.i';

/* clean url */
$cfg['url.domain']=NULL;
$cfg['clean_url'] = true;
$cfg['clean_url_home'] = null;

$cfg['site.redirection']=null;

/* MySql database use mysql://user:password@host/database */
$cfg['db.prefix']='sgz_'; // set table prefix
$cfg['db']='mysql://username:password@localhost/database';

/* set mysql charactor set */
/* use utf8 charactor set */
$cfg['db.character_set_client']='utf8';
$cfg['db.character_set_connection']='utf8';
$cfg['db.collation_connection']='utf8_unicode_ci';

/* use thai charactor set */
// $cfg['db.character_set_client']='tis620';
// $cfg['db.character_set_connection']='tis620';
// $cfg['db.collation_connection']='tis620_thai_ci';

/* folder */
$cfg['library']='/library/';
$cfg['img']=$cfg['library'].'img/';
$cfg['theme.folder']='themes';
$cfg['theme.name']='default';
$cfg['upload']='file';

$cfg['ban.time']=24*60*60;

//$cfg['upload.folder.chmod']=0777;
//$cfg['upload.file.chmod']=0666;

$cfg['server.timezone.offset']=0; // server timezone offset in hour

$cfg['roles']=new stdClass();
$cfg['roles']->admin='all privileges';
$cfg['roles']->anonymous='access comments,access papers,access forums, post comments,post comments without approval,post paper without approval,register new member';
$cfg['roles']->member='access user profiles,change own profile,edit own comment,edit own paper,upload photo';


$cfg['encode.format']='nls2p'; // text to html encoding format : nls2p , nl2br

$cfg['member.registration.method']='auto'; // method on register is auto , email
$cfg['member.registration.email']='noreply@'.$_SERVER['HTTP_HOST'];
$cfg['member.signin.checkip']=false; // check ip address on member sign in
$cfg['member.signin.remembertime']=10*24*60; // time in minute = day*24*60 default is 10 days
$cfg['member.username.format']='/^[a-z][a-z0-9_\-]+\z/';
$cfg['member.username.format_text']='<ul><li><strong>ชื่อสมาชิก (username)</strong> เป็นชื่อสำหรับใช้ในการ <strong>sign in</strong> เข้าสู่ระบบสมาชิก</li><li>ขนาดความยาว <strong>4-15</strong> ตัวอักษร</li><li>ชื่อสมาชิกต้องเป็นตัวอักษร ภาษาอังกฤษตัวเล็ก (a-z) , ตัวเลข (0-9) , สัญลักษณ์ ( - _ ) และ ขึ้นต้นด้วยตัวอักษร ( a-z ) เท่านั้น</li><li>ห้ามมีการเว้นวรรคอย่างเด็ดขาด</li></ul>';

$cfg['member.username.name_text']='<strong>ชื่อสำหรับแสดง ( Name )</strong> เป็นชื่อที่จะนำไปแสดงในหน้าเว็บไซท์ เมื่อท่านส่งหัวข้อหรือแสดงความคิดเห็น (ท่านสามารถใช้ชื่อย่อ หรือชื่อเล่น หรือสมญานามอื่นๆ ได้)';

$cfg['member.menu.paper.add']=false; // show paper add topic of all right in member ribbon
$cfg['member.menu.paper.forum']=true; // show paper forum name in member ribbon
$cfg['member.name_alias']=false; // use name alias when post message

$cfg['photo.file_type']=array('image/gif','image/jpeg','image/png','image/pjpeg');
$cfg['photo.max_file_size']=200; // in Kbyte
$cfg['photo.resize.width']=640; // in pixel
$cfg['photo.resize.quality']=70; // in %
$cfg['photo.width']=400;

$cfg['photo.slide.width']=400;
$cfg['photo.slide.height']=269;

$cfg['counter.enable']=true;
$cfg['counter.new_user_method']='ip';
$cfg['counter.online_time']=20; //in second

$cfg['debug.timer.request']=false;
$cfg['debug.query.all']=false;

/* topic options */
$cfg['topic.doc.file_ext']=array('pdf','mmap','mm','ppt'); // For topic upload file extension check
$cfg['topic.allowedtags.normal']='<font><h3><h4><h5><h6><p><b><i><u><strong><em><a><ul><ol><li><pre><span><hr><blockquote><summary><!-->';
$cfg['topic.allowedtags.photo']='<img>';
$cfg['topic.allowedtags.video']='<object><embed><param><iframe>';
$cfg['topic.allowedtags.script']='<div><script>';

$cfg['topic.body.allpage']=true;

$cfg['topic.summary_length']=300;
$cfg['topic.photo.single.width']=300;
$cfg['topic.photo.multiple.width']=200;
$cfg['topic.photo.detail.class']=null; // set detail photo class value = null,left,center,right
$cfg['topic.photo.in_detail_section']=false;
$cfg['topic.property.show_photo']='all';
$cfg['topic.allow.script']=false;
$cfg['topic.allow.php']=true;

$cfg['topic.video.allow']=false;
$cfg['topic.video.downloadable']=false;

$cfg['topic.require.mail']=false;
$cfg['topic.require.homepage']=false;

/* topic option of each item */
$cfg['topic.input_format']='markdown';
$cfg['topic.list.table.rows_per_item']='double'; // single or double
$cfg['topic.option']=new stdClass();
$cfg['topic.option']->fullpage=false;
$cfg['topic.option']->secondary=true;
$cfg['topic.option']->header=true;
$cfg['topic.option']->title=true;
$cfg['topic.option']->container=true;
$cfg['topic.option']->ribbon=true;
$cfg['topic.option']->toolbar=true;
$cfg['topic.option']->timestamp=true;
$cfg['topic.option']->related=true;
$cfg['topic.option']->docs=true;
$cfg['topic.option']->footer=true;
$cfg['topic.option']->package=true;
$cfg['topic.option']->commentwithphoto=true;
$cfg['topic.option']->social=true;
$cfg['topic.option']->ads=true;
$cfg['topic.option']->show_video=true;

$cfg['topic.relate.items']=5;
$cfg['topic.relate.detail.length']=0;
$cfg['topic.remove_text']='***';
$cfg['topic.close.day']=0; // Day to show topic and topic was hide after,0 is alway show

$cfg['comment.items']=20;
$cfg['comment.page']='first';
$cfg['comment.order']=array_key_exists('corder',$_COOKIE)?$_COOKIE['corder']:'ASC';
$cfg['comment.photo.width']=300;
$cfg['comment.terms_of_service.location']='below';
$cfg['comment.require.mail']=false;
$cfg['comment.require.homepage']=false;
$cfg['comment.require.subject']=false;
$cfg['comment.close.day']=0; // Day to close comment ,0 is alway show

$cfg['spam.word']='บาคาร่า,bacara,playerwin88com,gamblevipcom,กลูตาไธโอน,Glutathione'; // เฉพาะตัวอักษรและตัวเลขเท่านั้น ไม่ต้องใส่เครื่องหมายอื่น ๆ ทั้งสิ้น

$cfg['social.share.type']='story,forum';
$cfg['social.comment.facebook.type']='';
$cfg['social.comment.facebook.width']='100%';
$cfg['social.comment.facebook.posts']=25;

$cfg['files.extension.allow']=array('pdf','odt','ppt','rar','zip','tar','gz','iso','jpg','png','gif','mmap','mm');
$cfg['files.extension.notallow']=array('php','html','htm','jsp','py');
$cfg['files.max_file_size']=5; // max file size in MB
$cfg['files.log']=false;

$cfg['search.google.framewidth']=800;

$cfg['calendar.today.blink']=false;
$cfg['calendar.month.event_title_field']='title';

$cfg['guestbook.default']='guestbook';

$cfg['ad.img_folder']='ad';

$cfg['page.folder']='themes';
$cfg['page.extension']=array('htm','html','php');

$cfg['sticky.category.items']=10;
$cfg['sticky']=array(_HOME_STICKY=>'Top of Home Page',_CATEGORY_STICKY=>'Top of Category Listing');

if ($_SERVER['HTTP_HOST']=='localhost') {
	$cookie_id=substr($_SERVER['HTTP_HOST'],0,2).substr(str_replace('/','',$_SERVER['SCRIPT_NAME']),0,strpos(str_replace('/','',$_SERVER['SCRIPT_NAME']),'.'));
} else {
	$cookie_id=substr($_SERVER['HTTP_HOST'],0,2);
}
$cfg['cookie.u']='u'.$cookie_id;
$cfg['cookie.id']='id'.$cookie_id;
$cfg['cookie.path']='/';

$cfg['markdown.linktarget']=NULL;

$cfg['clickable.make']=false;
$cfg['clickable.target']=''; // _blank, _self
$cfg['clickable.rel']=''; // nofollow

/* set common url link */
menu('*/version','module version',q(0),'version',NULL,true,'static');
menu('*/credit','module credit',q(0),'credit',NULL,true,'static');
menu('*/about','module about',q(0),'about',NULL,true,'static');
menu('*/help','module help',q(0),'help',2,true,'static');
menu('*/admin','Module Administrator','func.'.q(0).'.admin','__controller',2,'administer '.q(0).'s','static');

/*
menu('signin','User Signin','user','sign_in',1,true,'static');
menu('signout','User Signout','user','sign_out',1,true,'static');
menu('signform','Member sign form','model','signform',1,true,'dynamic');
menu('home','Home page','template','home',1,true,'dynamic');
*/

/* for package only */

$cfg['upload_folder'] = 'upload/';

$cfg['template.extension'][] = 'tpl.php';
$cfg['template.extension'][] = 'php';
$cfg['template.extension'][] = 'html';

/* when new post -> auto mail detail to this email */
//$cfg['alert.email'] = 'alert@softganz.com';
$cfg['alert.module'] = 'paper,topic,forum,blog,photo,comment';
$cfg['email.delete_message']='alert@softganz.com';


/* cookie variable for member signin */
// $cfg['cookie.variable = softganz
// $cfg['cookie.theme'] = 'softganz_theme';
$cfg['cookie_times'] = 604800;

$cfg['link_target'] = '_self';

$cfg['paper.promote.items']=10;
$cfg['paper.listing.field'] = 'detail,photo';

$cfg['paper.upload.folder']=cfg('folder.abs').'upload/';
$cfg['paper.upload.url']=_url.'upload/';
$cfg['paper.upload.photo.url']=_url.'upload/pics/';
$cfg['paper.upload.photo.folder']=cfg('folder.abs').'upload/pics/';
$cfg['paper.upload.document.url']=_url.'upload/forum/';
$cfg['paper.upload.document.folder']=cfg('folder.abs').'upload/forum/';

$cfg['encode.format']='nl2br';

$cfg['poll.duration']=24*60*60; // poll hit duration in second default=1 day

$cfg['iblog.length']=160; // charector length in post message

$cfg['robots.txt']='User-agent: *
Disallow: /admin
Disallow: /contents
Disallow: /db
Disallow: /paper/list
Disallow: /profile
Disallow: /stat
Disallow: /tags
Disallow: /watchdog
Disallow: /project/develop
Disallow: /user';

$cfg['ibuy.ordersep']='';
$cfg['ibuy.orderdigit']=4;

$cfg['web.msg.createnewusertext']='Verifiable contact information is required for domain registration. Please create an account to proceed.';

$cfg['theme.backandwhite']=false;
$cfg['theme.stylesheet.para']=NULL;
?>