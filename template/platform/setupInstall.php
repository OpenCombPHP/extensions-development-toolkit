<?php 

{= isset($arrPlatformInfo['sSetupCodes'])? $arrPlatformInfo['sSetupCodes']: '' }

$arrMessageQueue = array() ;
function output($sMessage,$nType='success')
{
	//global $arrMessageQueue ;
	//$arrMessageQueue[] = "<div class='msg-{$nType}'>{$sMessage}</div>" ;
	echo "<div class='msg-{$nType}'>{$sMessage}</div>\r\n" ;
}

function checkDbSetting()
{
	if( !mysql_connect($_REQUEST['dbAddress'],$_REQUEST['dbUsername'],$_REQUEST['dbPswd']) )
	{
		output('无法连接到数据库服务器，请返回检查数据库配置是否正确。','error') ;
		output(mysql_error(),'error') ;
		return false ;
	}
	
	if( !mysql_select_db($_REQUEST['dbName']) )
	{
		if( !mysql_query("CREATE DATABASE `{$_REQUEST['dbName']}`") )
		{
			output("数据库 {$_REQUEST['dbName']} 无效，并且无法自动创建。",'error') ;
			output(mysql_error(),'error') ;
			return false ;
		}
		
		else
		{
			output("创建数据库 {$_REQUEST['dbName']} 成功。",'success') ;
		}
	}
	
	return true ;
}

function setupSetting($sService,$sDbTablePrefix)
{
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
		$sItemPath = install_service.'/'.$sService.'/setting/'.$sFile ;
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
	$sServiceSetting = '{='<?php'} return '.var_export(array(
			'default' => array(
					'domains' => array('*',$_REQUEST['websiteHost']) ,
			) ,
			'safemode' => array(
					'domains' => array('safemode') ,
			) ,
	),true).';' ;
	if( !file_put_contents(install_service.'/settings.inc.php',$sServiceSetting) )
	{
		output("无法将配置写入文件：{install_service}/settings.inc.php",'error') ;
		return false ;
	}
	
	return true ;
}

function installExtensions()
{
	global $arrExtensionFolders ;
	foreach($arrExtensionFolders as $sExtName=>$sExtFolder)
	{
		$sInstallFolder = install_root.'/'.$sExtFolder ;
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
	insertTableRow('coresystem_group',array(
			'gid' => 1 ,
			'name' => '系统管理员组' ,
			'lft' => 1 ,
			'rgt' => 2 ,
	)) ;
	insertTableRow('coresystem_purview',array(
			'type' => 'group' ,
			'id' => 1 ,
			'extension' => 'coresystem' ,
			'name' => 'PLATFORM_ADMIN' ,
	)) ;

	// 管理员帐号
	insertTableRow('coresystem_user',array(
			'uid' => 1 ,
			'username' => $_REQUEST['adminName'] ,
			'password' => md5( md5(md5($_REQUEST['adminName'])) . md5($_REQUEST['adminPswd']) ) ,
			'registerTime' => time() ,
			'registerIp' => $_SERVER['REMOTE_ADDR'] ,
	)) ;
	insertTableRow('coresystem_group_user_link',array(
			'uid' => 1 ,
			'gid' => 1 ,
	)) ;
	
	return true ;
}
function insertTableRow($sTable,$arrData)
{
	foreach($arrData as $sColumn=>&$value)
	{
		if(is_string($value))
		{
			$value = "'" . addslashes($value) . "'" ;
		}
	}
	$sSql = "insert into `{$sTable}` (" .implode(',',array_keys($arrData)) .') values (' . implode(',',$arrData). ") ;" ;

	try{
		if(!\org\jecat\framework\db\DB::singleton()->execute($sSql))
		{
			output('向数据库导入数据时遇到了错误:'.$sSQL,'error') ;
			return false ;
		}
	}catch(\org\jecat\framework\db\ExecuteException $e){
		if( $e->isDuplicate() )
		{
			output('写入数据时遇到重复数据:'.$sSql,'error') ;
			return false ;
		}
	}
	
	return true ;
}

function install()
{
	// 检查数据库设置
	if( !checkDbSetting() )
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
	$aLoader = require_once install_root.'/common.php';
	$aLoader->startup();
	
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
	
	// 禁止写入缓存
	\org\opencomb\platform\service\ServiceSerializer::singleton()->clearSystemObjects() ;

	
	output("系统安装完毕，感谢使用{=$sDistributionTitle}。") ;


	// \org\jecat\framework\db\DB::singleton()->executeLog(true) ;
	
	return true ;
}
?>

<div class="step3">
	<div class="bottombar">
		
		<div class="inner">
			{='<?php'}
			$bInstallSuccess = install() ;
			{='?>'}
		</div>
		
		
		{='<?php'}
		if($bInstallSuccess) {
		{='?>'}
		
		<h1>
			<span>完成</span>
		</h1>
		
		<a id="btnNext" href="../" class="step_btn">进入系统</a>
		
		{='<?php'}
		}
		{='?>'}
	</div>
</div>