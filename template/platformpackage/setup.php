<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>OpenComb系统安装程序</title>
  </head>
  <body>
<style>
#btnNext{
	display:block;
}
</style>
<?php
/*
<!--
*/
?>
<?php
if(1)
{
	// echo '-','-','>',"\n";
	$step = $_GET['step'];
	if(empty($step)){
		$step = 0;
	}
	$bHasNext = true;
	switch((int)$step){
	case 0:
		echo 'step 0';
?>
<script type='text/javascript'>
function agreeLicense(c){
	var btnNext = document.getElementById('btnNext');
	if(c){
		btnNext.style.display='block';
	}else{
		btnNext.style.display='none';
	}
}
window.onload = function(){
	var chkAgree = document.getElementById('chkAgree');
	agreeLicense(chkAgree.checked);
}
</script>
	<h1>您是否同意以下协议</h1>
	<div>
		<pre>
Copyright (C) 2012  JeCat.org

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <a href='http://www.gnu.org/licenses/'>http://www.gnu.org/licenses/</a>.
		</pre>
	</div>
	<input id='chkAgree' type='checkbox' onchange='agreeLicense(this.checked);' />已阅读GNU General Public License version 3并同意全部内容
<?php
		break;
	case 1:
		echo 'setup 1';
?>
<style>
.fail{
	color:red;
}
.succeed{
	color:green;
}
</style>
	<h1>正在检查运行环境</h1>
<?php
	$arrDependence = {=var_export($arrDependence,true)} ;
?>
	<ul>
		<li>语言
			<ul>
				<li>PHP。版本：<?php $sVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;var_dump($sVersion);?>。<em class='fail'>失败</em></li>
			</ul>
		</li>
		<li>语言扩展
			<ul>
				<li>GB。版本：xxx。<em class='succeed'>通过</em></li>
			</ul>
		</li>
		<li>数据库
			<ul>
				<li>mysql。<em class='succeed'>通过</em></li>
			</ul>
		</li>
		<li>文件写入权限
			<ul>
				<li>/<em class='succeed'>通过</em></li>
			</ul>
		</li>
	</ul>
<?php
		break;
	case 2:
		echo 'setup 2';
		$bHasNext = false;
?>
	<h1>请填入必要的信息</h1>
	<form method='get'>
		<input type='hidden' name='step' value='3' />
		<ul>
			<li>管理员用户
				<ul>
					<li>用户名：<input name='adminName' /></li>
					<li>密码：<input type='password' name='adminPswd' /></li>
				</ul>
			</li>
			<li>数据库
				<ul>
					<li>数据库地址：<input name='dbAddress' />端口：<input name='dbPort' value='' /></li>
					<li>数据库名：<input name='dbName' /></li>
					<li>登录名：<input name='dbUsername' /></li>
					<li>密码：<input type='password' name='dbPswd' /></li>
				</ul>
			</li>
		</ul>
		<input type='submit' value='下一步' />
	</form>
<?php
		break;
	case 3:
		echo 'setup 3';
?>
	<h1>完成</h1>
<?php
	default:
		$bHasNext = false;
		break;
	}
	if($bHasNext){
?>
	<a id='btnNext' href='/?step=<?php echo $step+1;?>'><button>下一步</button></a>
<?php
	}

file_get_contents(__FILE__) ;
/*
--ssdfsdfs---
bjgtfkjk;
--ssdfsdfs---
/platform:name

/platform/db:config = 'www'
/platform/db/www:dsn
/platform/db/www:username
/platform/db/www:password
/platform/db/www:options= array(1002 => "SET NAMES 'utf8'")
*/
} else {
?>-->
your server not support php
<!--<?php
}
echo '<','!','-','-';
?>-->

  </body>
</html>
