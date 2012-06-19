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
	// extract public
	$fZip = null ;
	$sName = '' ;
	$sInstallPath = '';
	
	function processLine1($sLine){
		global $fZip ;
		global $sName ;
		global $sInstallPath ;
		$sZipKey = '{=$sZipKey}';
		$nLenKey = strlen($sZipKey);
		if( substr( $sLine , 0 , $nLenKey ) === $sZipKey ){
			$arrLine = explode(':',$sLine);
			if( 3 === count($arrLine)){
				$sName = $arrLine[1];
				$sFileName = $sName.'.zip' ;
				if($sName === 'public'){
					$fZip = fopen($sFileName,'w');
				}
				$sInstallPath = $arrLine[2];
			}else if( 2 === count($arrLine)){
				if( null !== $fZip ){
					fclose($fZip);
					$fZip = null ;
				}
				
				if($sName === 'public'){
					$sFileName = $sName.'.zip' ;
					$zip = new ZipArchive;
					if ($zip->open($sFileName) === TRUE) {
						$zip->extractTo($sInstallPath);
						$zip->close();
						unlink($sFileName);
					} else {
					}
				}
			}
		}else if( null !== $fZip ){
			fwrite($fZip , base64_decode($sLine) );
		}
	}

	if(!file_exists('public/')){
		$fp = fopen(__FILE__,'r');
		if(!$fp){
			echo 'Could not open file ',__FILE__;
		}else{
			$sLine = '' ;
			while( false !== ($char = fgetc($fp))){
				if($char === "\n"){
					processLine1($sLine);
					$sLine = '';
				}else{
					$sLine .= $char ;
				}
			}
		}
	}
		
	// echo '-','-','>',"\n";
	if(isset($_GET['step'])){
		$step = $_GET['step'];
	}else{
		$step = 0;
	}
	$bHasNext = true;
	switch((int)$step){
	case 1:
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
	case 0:
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
	/**
	 * @retval -1 , 0 , 1
	 */
	function cmpVersion($sVersion1 , $sVersion2){
		return strnatcmp($sVersion1 , $sVersion2);
	}
	/**
	 * @return boolean
	 */
	function isStartWith($sLong , $sPrefix){
		$nPrefixLen = strlen($sPrefix);
		return substr( $sLong , 0 , $nPrefixLen ) === $sPrefix ;
	}
	/**
	 * @return boolean
	 *
	 * >=5.4.0.0
	 */
	function singleSideRequire($sVersion , $sRequire){
		if( isStartWith( $sRequire , '<=' ) ){
			if( cmpVersion( $sVersion , substr( $sRequire , 2 ) ) > 0 ){
				return false;
			}
		}else if( isStartWith( $sRequire , '>=' ) ){
			if( cmpVersion( $sVersion , substr( $sRequire , 2 ) ) < 0 ){
				return false;
			}
		}else if( isStartWith( $sRequire , '=' ) ){
			if( cmpVersion( $sVersion , substr( $sRequire , 1 ) ) !== 0 ){
				return false;
			}
		}else if( isStartWith( $sRequire , '<' ) ){
			if( cmpVersion( $sVersion , substr( $sRequire , 1 ) ) >= 0 ){
				return false;
			}
		}else if( isStartWith( $sRequire , '>' ) ){
			if( cmpVersion( $sVersion , substr( $sRequire , 1 ) ) <= 0 ){
				return false;
			}
		}
		return true;
	}
	/**
	 * @return boolean
	 *
	 * >=5.4.0.0,<6.0.0.0
	 */
	function bothSideRequire($sVersion , $sRequire){
		$arrRequire = explode(',',$sRequire) ;
		foreach($arrRequire as $sSubRequire){
			if(false === singleSideRequire($sVersion , $sSubRequire) ){
				return false;
			}
		}
		return true;
	}
	// dependence
	$arrDependence = {=var_export($arrDependence,true)} ;
	// php
	$arrPhpDep = $arrDependence['language']['php'] ;
	$arrPhpRequire = array();
	$sPhpVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
	$bPhpSuccess = true;
	foreach($arrPhpDep as $sPhpDep){
		if(false === bothSideRequire($sPhpVersion,$sPhpDep)){
			$bPhpSuccess = false;
			$bHasNext = false;
			break;
		}
	}
	// language module
	$arrLangMods = $arrDependence['language_module'] ;
	foreach($arrLangMods as $name => &$arrLangMod){
		$arrLangMod['success'] = true;
		$sVersion = phpversion($name);
		$arrLangMod['version'] = $sVersion ;
		foreach($arrLangMod as $key => $sRequire){
			if($key === 'success' or $key === 'version' ){
				continue;
			}
			if( false === bothSideRequire($sVersion,$sRequire)){
				$arrLangMod['success'] = false;
				$bHasNext = false;
				break;
			}
		}
	}
	unset($arrLangMod);
?>
	<ul>
		<li>语言
			<ul>
				<li>PHP。版本：<?php $sVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;echo($sVersion);?>。
<?php
	if($bPhpSuccess){
?>
					<em class='succeed'>通过</em>
<?php
	}else{
?>
					<em class='fail'>失败</em>
<?php
	}
?>
				</li>
			</ul>
		</li>
		<li>语言扩展
			<ul>
<?php
	foreach($arrLangMods as $name =>$arrLangMod){
?>
				<li>
<?php
	echo $name;
?>
				。版本：
<?php
	echo $arrLangMod['version'];
?>。<?php
	if($arrLangMod['success'] ){
?>
				<em class='succeed'>通过</em>
<?php
	}else{
?>
				<em class='fail'>失败</em>
<?php
	}
?>
				</li>
<?php
	}
?>
			</ul>
		</li>
		<li>数据库
			<ul>
<?php
	$arrMysqlMod = array(
		'mysql',
		'mysqli',
		'PDO',
		'pdo_mysql'
	);
	$bMysql = true;
	foreach($arrMysqlMod as $sMysqlMod){
		if(false === phpversion($sMysqlMod) ){
			$bMysql = false;
			$bHasNext = false;
		}
	}
?>
				<li>mysql。
<?php
	if($bMysql){
?>
				<em class='succeed'>通过</em>
<?php
	}else{
?>
				<em class='fail'>失败</em>
<?php
	}
?>
				</li>
			</ul>
		</li>
		<li>文件写入权限
			<ul>
<?php
	$bWritable = is_writable('.');
	if( false === $bWritable){
		$bHasNext = false;
	}
?>
				<li>/
<?php
	if($bWritable){
?>
				<em class='succeed'>通过</em>
<?php
	}else{
?>
				<em class='fail'>失败</em>
<?php
	}
?>
				</li>
			</ul>
		</li>
	</ul>
<?php
		break;
	case 2:
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
					<li>数据库地址：<input name='dbAddress' /></li>
					<li>数据库名：<input name='dbName' /></li>
					<li>登录名：<input name='dbUsername' /></li>
					<li>密码：<input type='password' name='dbPswd' /></li>
				</ul>
			</li>
			<li>网站
				<ul>
					<li>名称：<input name='websiteName' /></li>
				</ul>
			</li>
		</ul>
		<input type='submit' value='下一步' />
	</form>
<?php
		break;
	case 3:
		$bHasNext = false;
		
		$bFinish = true;
		$sError = '';
		$sDebugInfo = '';
		
		$fZip = null ;
		$sName = '' ;
		$sInstallPath = '';
		
		$arrExtensionList = array();
		
		function startsWith($haystack, $needle){
			$length = strlen($needle);
			return (substr($haystack, 0, $length) === $needle);
		}
		
		function isExtension($sPath){
			if( startsWith($sPath,'extensions/') ){
				return true;
			}
			return false;
		}
		
		function processLine($sLine){
			global $fZip ;
			global $sName ;
			global $sInstallPath ;
			$sZipKey = '{=$sZipKey}';
			$nLenKey = strlen($sZipKey);
			if( substr( $sLine , 0 , $nLenKey ) === $sZipKey ){
				$arrLine = explode(':',$sLine);
				if( 3 === count($arrLine)){
					$sName = $arrLine[1];
					$sFileName = $sName.'.zip' ;
					$fZip = fopen($sFileName,'w');
					$sInstallPath = $arrLine[2];
				}else if( 2 === count($arrLine)){
					fclose($fZip);
					$fZip = null ;
					
					$sFileName = $sName.'.zip' ;
					$zip = new ZipArchive;
					if ($zip->open($sFileName) === TRUE) {
						$zip->extractTo($sInstallPath);
						$zip->close();
						unlink($sFileName);
						
						if(isExtension($sInstallPath)){
							$arrExtensionList [] =
								array(
									'name' => $sName ,
									'path' => $sInstallPath ,
								);
						}
					} else {
						$bFinish = false;
						$sError .= 'open failed.'."\n";
					}
				}
			}else if( null !== $fZip ){
				fwrite($fZip , base64_decode($sLine) );
			}
		}
		$fp = fopen(__FILE__,'r');
		if(!$fp){
			$bFinish = false;
			$sError .= 'Could not open file '.__FILE__."\n";
		}else{
			$sLine = '' ;
			while( false !== ($char = fgetc($fp))){
				if($char === "\n"){
					processLine($sLine);
					$sLine = '';
				}else{
					$sLine .= $char ;
				}
			}
		}
		
		// settings
		// 1. /platform:name
		$name = $_GET['websiteName'];
		$str = <<<PLATFORMCONFIG
<?php
return array (
  'name' => '$name',
) ;
PLATFORMCONFIG;
		mkdir('settings/platform',0755,true);
		file_put_contents('settings/platform/items.php',$str);
		// 2. /platform/db:config = 'www'
		$str = <<<DBCONFIG
<?php
return array (
  'config' => 'www',
) ;
DBCONFIG;
		mkdir('settings/platform/db',0755,true);
		file_put_contents('settings/platform/db/items.php',$str);
		// 3. /platform/db/www:dsn
		//    /platform/db/www:username
		//    /platform/db/www:password
		//    /platform/db/www:options= array(1002 => "SET NAMES 'utf8'")
		$dbAddress = $_GET['dbAddress'];
		$dbName = $_GET['dbName'];
		$dbUsername = $_GET['dbUsername'];
		$dbPswd = $_GET['dbPswd'];
		$str = <<<DBSETTINGS
<?php
return array (
  'dsn' => 'mysql:host=$dbAddress;dbname=$dbName',
  'username' => '$dbUsername',
  'password' => '$dbPswd',
  'options' => 
  array (
    1002 => 'SET NAMES \'utf8\'',
  ),
) ;
DBSETTINGS;
		mkdir('settings/platform/db/www',0755,true);
		file_put_contents('settings/platform/db/www/items.php',$str);
		
		if($bFinish){
?>
	<h1>完成</h1>
<?php
		}else{
?>
	<h1>发生错误</h1>
	<p><?php echo $sError;?></p>
<?php
		}
?>
<?php
		break;
	default:
		$bHasNext = false;
		break;
	}
	if($bHasNext){
		if(isset($_SERVER["PATH_INFO"])){
			$path_info = $_SERVER["PATH_INFO"];
		}else{
			$path_info = '';
		}
?>
	<a id='btnNext' href='<?php echo $path_info;?>?step=<?php echo $step+1;?>'><button>下一步</button></a>
<?php
	}
	
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
<foreach for='$arrZips' item='arrZip'>
/*
{=$sZipKey}:{=$arrZip['name']}:{=$arrZip['path']}
<while "! $arrZip['reader']->isEnd()">{=base64_encode($arrZip['reader']->read(80))}
</while>{=$sZipKey}:{=$arrZip['name']}
*/
</foreach>
} else {
?>-->
your server not support php
<!--<?php
}
echo '<','!','-','-';
?>-->

  </body>
</html>