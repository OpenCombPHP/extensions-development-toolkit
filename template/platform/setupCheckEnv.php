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
		if(false === singleSideRequire($sVersion , $sSubRequire) ) {
			return false;
		}
	}
	return true;
}





// has next
$bHasNext = true ;
// dependence
$arrDependence = {=var_export($arrDependence,true)} ;
// php
$arrPhpDep = $arrDependence['language']['php'] ;
$arrPhpRequire = array();
$sPhpVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
$bPhpSuccess = true;
if(!empty($arrPhpDep))
{
	foreach($arrPhpDep as $sPhpDep){
		if(false === bothSideRequire($sPhpVersion,$sPhpDep) ) {
			$bPhpSuccess = false;
			$bHasNext = false;
			break;
		}
	}
}
// language module
$arrLangMods = $arrDependence['language_module'] ;
if(!empty($arrLangMods))
{
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
}
unset($arrLangMod);

$sPhpVersion = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
$sPhpSuccess = '';
if(version_compare($sPhpVersion,'5.3.0')>=0){
	$sPhpSuccess = "<em class='succeed'>通过</em>";
}else{
	$sPhpSuccess = "<em class='fail'>失败</em>";
	$bHasNext = false;
}

$sLangMods = '';
if(!empty($sLangMods))
{
	foreach($arrLangMods as $name => $arrLangMod){
		$sLangModVersion = $arrLangMod['version'];
		$sLangModSuccess = '';
		if($arrLangMod['success']){
			$sLangModSuccess = "<em class='succeed'>通过</em>";
		}else{
			$sLangModSuccess = "<em class='fail'>失败</em>";
			$bHasNext = false;
		}
		$sLangMods .= <<<LANGMODS
				<li>$sLangModSuccess
					$name 。版本：$sLangModVersion 。
				</li>
LANGMODS;
	}
}

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
$sMysqlSuccess = '' ;
if($bMysql){
	$sMysqlSuccess = "<em class='succeed'>通过</em>";
}else{
	$sMysqlSuccess = "<em class='fail'>失败</em>";
}

<if $bCheckRootWritable>
$bWritable = is_writable('../');
if( false === $bWritable){
	$bHasNext = false;
}
$sWritableSuccess = '' ;
if($bWritable){
	$sWritableSuccess = "<em class='succeed'>通过</em>";
}else{
	$sWritableSuccess = "<em class='fail'>失败</em>";
}
</if>
?>

<div class="stepbar">
	<ul>
		<li class="this-step">1. 检查运行环境</li>
		<li>2. 确认协议</li>
		<li>3. 填入必要信息</li>
		<li>4. 完成</li>
	</ul>
</div>

<div class="bottombar step0">
	<h1><span>正在检查运行环境</span></h1>
	<div class="inner">
	<ul>
		<li>语言
			<ul>
				<li>
				{='<?php'} echo $sPhpSuccess {='?>'}
				PHP。版本：{='<?php'} echo $sPhpVersion {='?>'} 。
				</li>
			</ul>
		</li>
		<li>语言扩展
			<ul>
				{='<?php'} echo $sLangMods {='?>'}
			</ul>
		</li>
		<li>数据库
			<ul>
				<li>
				{='<?php'} echo $sMysqlSuccess {='?>'}
				mysql。
				</li>
			</ul>
		</li>
		<if $bCheckRootWritable>
		<li>
			文件写入权限
			<ul>
				<li>
					{='<?php'} echo $sWritableSuccess {='?>'}
					/
				</li>
			</ul>
		</li>
		</if>
	</ul>
	</div>
	
	{='<?php'} if($bHasNext){ {='?>'}
	<a id="btnNext" href="setup.php?action=licence" class="step_btn">下一步</a>
	{='<?php'} } {='?>'}
</div>