
$sFrameworkPackageFilename = '{=$sFrameworkPackageFilename}' ;
$sPlatformPackageFilename = '{=$sPlatformPackageFilename}' ;
$arrExtensionPackages = {=var_export($arrExtensionPackages,true)} ;


$arrMessageQueue = array() ;
function output($sMessage,$nType='success')
{
	global $arrMessageQueue ;
	$arrMessageQueue[] = "<div class='msg-{$nType}'>{$sMessage}</div>" ;
}

// 解压zip函数
function unzip($sPackagePath,$sToFolder)
{
	$aZip = new PclZip($sPackagePath) ;
	return $aZip->extract($sToFolder)>0 ;
}

function extractFiles()
{
	global $sFrameworkFolder, $sPlatformFolder, $sFrameworkPackageFilename, $sPlatformPackageFilename ;

	// 释放 jecat framework
	foreach( array(
		$sFrameworkPackageFilename => $sFrameworkFolder ,
		$sPlatformPackageFilename => $sPlatformFolder ,
	) as $sPackage=>$sFolder)
	{
		if( !unzip(setup_folder.'/packages/'.$sPackage,$sFolder) )
		{
			return false ;
		}
	}
	
	return true ;
}

function setupSetting($sService,$sDbTablePrefix)
{
	global $sServicesFolder ;
	
	// 写入 setting -----------------------------
	$name = $_REQUEST['websiteName'];
	$arrSettings = array(
		'service/items.php'		=> array('name'=>$_REQUEST['websiteName']) ,
		'service/db/items.php'		=> array('config'=>'www') ,
		'service/db/www/items.php'	=> array(
										'dsn' => "mysql:host={$_REQUEST['dbAddress']};dbname={$_REQUEST['dbName']}",
										'username' => $_REQUEST['dbUsername'],
										'password' => $_REQUEST['dbPswd'],
										'options' => array (1002 => "SET NAMES 'utf8'",) ,												
										'table_prefix' => $sDbTablePrefix ,
				) ,
	) ;
	foreach($arrSettings as $sFile=>$arrValue)
	{
		$sItemPath = $sServicesFolder.'/'.$sService.'/setting/'.$sFile ;
		$sItemFolderPath = dirname($sItemPath) ;
		if( !file_exists($sItemFolderPath) and !mkdir($sItemFolderPath,0775,true) )
		{
			output("无法创建目录：{$sItemFolderPath}/",'error') ;
			return false ;
		}
		if( !file_put_contents($sItemPath,'{='<?php'} return '.var_export($arrValue,true).';') )
		{
			output("无法将配置写入文件：{$sItemPath}",'error') ;
			return false ;
		}

		output("写入配置文件：{$sItemPath}",'error') ;
	}
	
	// 写入 services setting --------------------------
	if( !file_put_contents($sServicesFolder.'/settings.inc.php','{='<?php'} return '.var_export(array(
			'default' => array(
				'domains' => array('*',$_REQUEST['websiteHost']) ,
			) ,
			'safemode' => array(
				'domains' => array('safemode') ,
			) ,
	),true).';') )
	{
		output("无法将配置写入文件：{$sServicesFolder}/settings.inc.php",'error') ;
		return false ;
	}
	
	// 写入 oc.config.php
	if( !file_put_contents(install_root.'/oc.config.php',"{='<?php'}
namespace org\opencomb\platform ;

define('org\\opencomb\\platform\\ROOT',__DIR__) ;
define('org\\opencomb\\platform\\PLATFORM_FOLDER',ROOT.'/platform') ;
define('org\\opencomb\\platform\\EXTENSIONS_FOLDER',ROOT.'/extensions') ;
define('org\\opencomb\\platform\\SERVICES_FOLDER',ROOT.'/services') ;
define('org\\opencomb\\platform\\PUBLIC_UI_FOLDER',ROOT.'/public/ui') ;
define('org\\opencomb\\platform\\PUBLIC_UI_URL','public/ui') ;
define('org\\opencomb\\platform\\PUBLIC_FILES_FOLDER',ROOT.'/public/files') ;
define('org\\opencomb\\platform\\PUBLIC_FILES_URL','public/files') ;

") )
	{
		output("无法将配置写入文件：".install_root.'/oc.config.php','error') ;
		return false ;
	}
	
	return true ;
}

function installExtensions()
{
	global $sExtensionsFolder, $arrExtensionPackages ;
	foreach($arrExtensionPackages as $sExtFolder=>$sPackageName)
	{
		// 解压扩展
		$sPackagePath = setup_folder.'/packages/'.$sPackageName ;
		$sInstallFolder = $sExtensionsFolder.'/'.$sExtFolder ;
		if( !unzip($sPackagePath,$sInstallFolder) )
		{
			output("无法将扩展包 {$sPackagePath} 解压到目录 {$sInstallFolder}") ;
			return false ;
		}

		if( !$aDomMetainfo = simplexml_load_file($sInstallFolder.'/metainfo.xml') )
		{
			output("无法读取扩展包 {$sPackagePath} 中的 metainfo.xml 文件") ;
			return false ;
		}
		
		// 安装扩展
		$aMessageQueue = new \org\jecat\framework\message\MessageQueue() ;
		try{
			$aExtMeta = \org\opencomb\platform\ext\ExtensionSetup::singleton()->install(new \org\jecat\framework\fs\Folder($sInstallFolder),$aMessageQueue) ;
		}catch(\org\jecat\framework\db\ExecuteException $e){
			output("无法连接到数据库，数据库设置错误。",'error') ;
			return false ;
		}catch(\Exception $e){}
		
		foreach($aMessageQueue->iterator() as $aMessage)
		{
			output($aMessage->message(),$aMessage->type()) ;
		}
		if(!empty($e))
		{
			output($e->getMessage(),'error') ;
			return false ;
		}
		
		output('安装扩展：'.$aExtMeta->title().'('.$aExtMeta->name().':'.$aExtMeta->version().')','success') ;
		
		// 激活扩展
		$aMessageQueue = new \org\jecat\framework\message\MessageQueue() ;
		try{
			\org\opencomb\platform\ext\ExtensionSetup::singleton()->enable($aExtMeta->name()) ;
		}catch(\Exception $e)
		{
			output($e->getMessage(),'error') ;
			return false ;
		}
		
		output('激活扩展：'.$aExtMeta->title().'('.$aExtMeta->name().':'.$aExtMeta->version().')','success') ;
		
	}

	// 加载所有扩展
	\org\opencomb\platform\ext\ExtensionLoader::singleton()->loadAllExtensions() ;
	
	return true ;
}

function insertAdminUser()
{
	$aDB = \org\jecat\framework\db\DB::singleton() ;
	
	// 管理员用户组
	$arrSQL[] = "insert into `coresystem_group` (gid,name,lft,rgt) values (1,'系统管理员组',1,2)" ;
	$arrSQL[] = "insert into `coresystem_purview` (type,id,extension,name) values ('group',1,'coresystem','PLATFORM_ADMIN')" ;

	// 管理员帐号
	$sUsername = addslashes($_REQUEST['adminName']) ;
	$sPassword = md5( md5(md5($_REQUEST['adminName'])) . md5($_REQUEST['adminPswd']) ) ;
	$nNow = time() ;
	$sIp = $_SERVER['REMOTE_ADDR'] ;
	$arrSQL[] = "insert into `coresystem_user` (uid,username,password,registerTime,registerIp) values (1,'{$sUsername}','{$sPassword}',{$nNow},'{$sIp}')" ;
	$arrSQL[] = "insert into `coresystem_group_user_link` (uid,gid) values (1,1)" ;
	
	foreach($arrSQL as $sSQL)
	{
		if(!$aDB->execute($sSQL))
		{
			output('向数据库导入数据时遇到了错误:'.$sSQL,'error') ;
			return false ;
		}
	}
	return true ;
}

function install()
{
	// 解压文件
	if( !extractFiles() )
	{
		return false ;
	}
	
	// 写入 setting
	if( !setupSetting('default',$_REQUEST['dbPrefix']) )
	{
		return false ;
	}
	if( !setupSetting('safemode','ocsafe_') )
	{
		return false ;
	}
	
	// 启动系统
	$aService = include install_root.'/oc.init.php' ;
	if( !($aService instanceof \org\opencomb\platform\service\Service) )
	{
		output("无法启动系统，安装失败。",'error') ;
		return false ;
	}
	
	// 安装扩展
	if(!installExtensions())
	{
		return false ;
	}
	
	// 设置管理员用户
	if(!insertAdminUser())
	{
		return false ;
	}
	
	// 从各个扩展 导入 public ui 目录
	$aService->publicFolders()->importFromSourceFolders() ;
	
	// 禁止写入缓存
	\org\opencomb\platform\service\ServiceSerializer::singleton()->clearSystemObjects() ;

	
	output("系统安装完毕，感谢使用{=$sDistributionTitle}。") ;


	// \org\jecat\framework\db\DB::singleton()->executeLog(true) ;
	
	return true ;
}


$bInstallSuccess = install() ;
?>

<div class="step3">
	<div class="bottombar">
		
		<div class="inner">
			{='<?php'}
			foreach($arrMessageQueue as $sMessage)
			{
				echo $sMessage, "\r\n" ;
			}
			{='?>'}
		</div>
		
		
		{='<?php'}
		if($bInstallSuccess) {
		{='?>'}
		
		<h1>
			<span>完成</span>
		</h1>
		
		<a id="btnNext" href="/" class="step_btn">进入系统</a>
		
		{='<?php'}
		}
		{='?>'}
	</div>
</div>

<?php 