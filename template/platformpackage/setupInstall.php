<?php

$arrExtensionList = array();
/**
 * @return array(
 *             'success' => true,
 *             'error' => array(
 *                  'error1',
 *             )
 *         );
 */
function extractFiles(){
	$arrResult = array(
		'success' => true,
		'error' => array(
		),
	);
	
	$fZip = null ;
	$sName = '' ;
	$sInstallPath = '';
	
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
		global $arrExtensionList ;
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
					$arrResult ['success'] = false;
					$arrResult ['error'] [] = 'open failed.';
				}
			}
		}else if( null !== $fZip ){
			fwrite($fZip , base64_decode($sLine) );
		}
	}
	$fp = fopen(__FILE__,'r');
	if(!$fp){
		$arrResult ['success'] = false;
		$arrResult ['error'] [] = 'Could not open file '.__FILE__;
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
	
	return $arrResult ;
}

function writeInfo(){
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
	
	global $arrExtensionList;
	
	// 简单配置启动 OC platform
	$aPlatform = require 'oc.init.php' ;
	
	// data upgrader
	$aDataUpgrader = \org\opencomb\platform\system\upgrader\PlatformDataUpgrader::singleton() ; 
	
	$aMessageQueue = new \org\jecat\framework\message\MessageQueue;
	
	$aDataUpgrader->process($aMessageQueue) ;
	
	foreach($arrExtensionList as $arrExtension){
		$sPath = $arrExtension['path'] ;
		if( !$aExtFolder = \org\jecat\framework\fs\Folder::singleton()->findFolder($sPath) )
		{
			$aMessageQueue->create(\org\jecat\framework\message\Message::error,'输入的路径不存在:%s',$sPath) ;
			break ;
		}
		
		try{
			// 清理缓存
			\org\opencomb\platform\service\ServiceSerializer::singleton()->clearRestoreCache();
			
			// 安装
			$aExtMeta = \org\opencomb\platform\ext\ExtensionSetup::singleton()->install($aExtFolder , $aMessageQueue ) ;
			
			$aMessageQueue->create(
					\org\jecat\framework\message\Message::success
					, "扩展% s(%s:%s) 已经成功安装到平台中。"
					, array( $aExtMeta->title(), $aExtMeta->name(), $aExtMeta->version() )
			) ;

			// 激活
			\org\opencomb\platform\ext\ExtensionSetup::singleton()->enable($aExtMeta->name()) ;
			
			$aMessageQueue->create(
					\org\jecat\framework\message\Message::success
					, "扩展 %s(%s:%s) 已经激活使用。"
					, array( $aExtMeta->title(), $aExtMeta->name(), $aExtMeta->version() )
			) ;
		}catch(Exception $e){
			$aMessageQueue->create(\org\jecat\framework\message\Message::error,$e->getMessage(),$e->messageArgvs()) ;
		}
	}
	
	return $aMessageQueue ;
}

function install(){
	$arrResult = extractFiles();
	
	$str = '';
	if( $arrResult['success'] ){
		$aMessageQueue = writeInfo();
		$aBuffer = new \org\jecat\framework\io\OutputStreamBuffer ;
		$aMessageQueue->display(null,$aBuffer);
		$str = $aBuffer -> __toString() ;
	}
	
	$sCode = {='<<<'}CODE

<div class="main">
	<div class="content">
		<div class="topbar">
			<h1>蜂巢平台 <span class="azxd">安装向导</span><span class="topversion">版本号</span></h1>
		</div>
	
		<div class="stepbar">
			<ul>
				<li>1. 检查运行环境</li>
				<li>2. 确认协议</li>
				<li>3. 填入必要信息</li>
				<li class="this-step">4. 完成</li>
			</ul>
		</div>
		<div class="step3">
			<div class="bottombar">
				<h1><span>完成</span></h1>
				<div class="inner">
					$str
				</div>
				<a id="btnNext" href="/" class="step_btn">进入系统</a>
			</div>
		</div>
	</div>
</div>
CODE;
	echo $sCode ;
}

?>
