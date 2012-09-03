<?php 
// 这个文件是由扩展 development-toolkit 的 CreateDistribution 模块自动生成
// 扩展 development-toolkit 版本：{=$extDevVersion}
// CreateDistribution 模块版本：{=$CreateDistributionVersion}

{= isset($arrPlatformInfo['sSetupCodes'])? $arrPlatformInfo['sSetupCodes']: '' }
function output($sMessage,$nType='success')
{
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

function setupServiceSettings(){
	global $framework_version,$platform_version;
	// 写入 services setting --------------------------
	$sServiceSetting = '{='<?php'} return '.var_export(array(
			'default' => array(
					'domains' => array('*',$_REQUEST['websiteHost']) ,
					'framework_version' => $framework_version,
					'platform_version' => $platform_version,
					'serviceSetting' => {=var_export($arrPlatformInfo['serviceSetting'],true)},
			) ,
			'safemode' => array(
					'domains' => array('safemode') ,
					'framework_version' => $framework_version,
					'platform_version' => $platform_version,
					'serviceSetting' => {=var_export($arrPlatformInfo['serviceSetting'],true)},
			) ,
	),true).';' ;
	$sServicePath = install_service;
	if( !file_exists($sServicePath) and !mkdir($sServicePath,0775,true) )
	{
		output("无法创建目录：{$sServicePath}",'error') ;
		return false ;
	}
	output("创建目录：{$sServicePath}",'success') ;
	
	$sServiceSettingFilePath = install_service.'/settings.inc.php';
	if( !file_put_contents($sServiceSettingFilePath,$sServiceSetting) )
	{
		output("无法将配置写入文件：{$sServiceSettingFilePath}",'error') ;
		return false ;
	}
	output("将配置写入文件：{$sServiceSettingFilePath}",'success') ;
	
	return true;
}

function setupSetting($sService,$sDbTablePrefix)
{
	// 写入 setting -----------------------------
	$arrSettings = array(
		'service/name' => $_REQUEST['websiteName'] ,
		'service/db/config' => 'www',
		'service/db/www/dsn' => "mysql:host={$_REQUEST['dbAddress']};dbname={$_REQUEST['dbName']}",
		'service/db/www/username' => $_REQUEST['dbUsername'],
		'service/db/www/password' => $_REQUEST['dbPswd'],
		'service/db/www/options' => array (1002 => "SET NAMES 'utf8'",) ,
		'service/db/www/table_prefix' => $sDbTablePrefix ,
	);
	
	$aSetting = \org\jecat\framework\setting\Setting::singleton();
	foreach($arrSettings as $sKey=>$sValue)
	{
		$aSetting->setValue($sKey,$sValue);
		output("写入配置：{$sKey} => {$sValue}",'error') ;
	}
	
	return true ;
}

function upgradePlatform(\org\jecat\framework\message\MessageQueue $aMsgQue){
	// 检查 service 升级
	$aDataUpgrader = \org\opencomb\platform\service\upgrader\PlatformDataUpgrader::singleton() ; 
	if(TRUE === $aDataUpgrader->process($aMsgQue)){
	}
	return true;
}

function installExtensions(\org\jecat\framework\message\MessageQueue $aMessageQueue)
{
	global $arrExtensionFolders ;
	foreach($arrExtensionFolders as $sExtName=>$sExtFolder)
	{
		$sInstallFolder = install_root.'/'.$sExtFolder ;
		if( !$aDomMetainfo = simplexml_load_file($sInstallFolder.'/metainfo.xml') )
		{
			$aMessageQueue->create(
				\org\jecat\framework\message\Message::error,
				'无法读取扩展包 `%s` 中的 metainfo.xml 文件',
				$sInstallFolder
			);
			return false ;
		}
		
		// 安装扩展
		try{
			$aExtMeta = \org\opencomb\platform\ext\ExtensionSetup::singleton()->install(new \org\jecat\framework\fs\Folder($sInstallFolder),$aMessageQueue) ;
		}catch(\org\jecat\framework\db\ExecuteException $e){
			$aMessageQueue->create(
				\org\jecat\framework\message\Message::error,
				'数据库错误: %s',
				$e->message()
			);
			return false ;
		}catch(\org\jecat\framework\lang\Exception $e){
			$aMessageQueue->create(
				\org\jecat\framework\message\Message::error,
				'%s',
				$e->message()
			);
			return false ;
		}
		
		$aMessageQueue->create(
			\org\jecat\framework\message\Message::success,
			'安装扩展: %s(%s:%s)',
			array(
				$aExtMeta->title(),
				$aExtMeta->name(),
				$aExtMeta->version()
			)
		);
		
		// 激活扩展
		try{
			\org\opencomb\platform\ext\ExtensionSetup::singleton()->enable($aExtMeta->name()) ;
		}catch(\Exception $e)
		{
			$aMessageQueue->create(
				\org\jecat\framework\message\Message::error,
				'%s',
				$e->message()
			);
			return false ;
		}
		
		$aMessageQueue->create(
			\org\jecat\framework\message\Message::success,
			'激活扩展: %s(%s:%s)',
			array(
				$aExtMeta->title(),
				$aExtMeta->name(),
				$aExtMeta->version()
			)
		);
	}

	// 加载所有扩展
	\org\opencomb\platform\ext\ExtensionLoader::singleton()->loadAllExtensions() ;
	
	return true ;
}

function insertAdminUser(\org\jecat\framework\message\MessageQueue $aMessageQueue)
{
	$aDB = \org\jecat\framework\db\DB::singleton() ;
	
	// 管理员用户组
	insertTableRow(
		'coresystem:group',
		array(
			'gid' => 1 ,
			'name' => '系统管理员组' ,
			'lft' => 1 ,
			'rgt' => 2 ,
		),
		$aMessageQueue
	) ;
	insertTableRow(
		'coresystem:purview',
		array(
			'type' => 'group' ,
			'id' => 1 ,
			'extension' => 'coresystem' ,
			'name' => 'PLATFORM_ADMIN' ,
		),
		$aMessageQueue
	) ;
	
	// 管理员帐号
	insertTableRow(
		'coresystem:user',
		array(
			'username' => $_REQUEST['adminName'] ,
			'password' => md5( md5(md5($_REQUEST['adminName'])) . md5($_REQUEST['adminPswd']) ) ,
			'registerTime' => time() ,
			'registerIp' => $_SERVER['REMOTE_ADDR'] ,
		),
		$aMessageQueue
	) ;
	$uid = $aDB->lastInsertId();
	insertTableRow(
		'coresystem:group_user_link',
		array(
			'uid' => $uid ,
			'gid' => 1 ,
		),
		$aMessageQueue
	) ;
	
	return true ;
}
function insertTableRow($sTable,$arrData,\org\jecat\framework\message\MessageQueue $aMessageQueue)
{
	$aInsert = new \org\jecat\framework\db\sql\Insert ( $sTable );
	foreach($arrData as $sColumn=>&$value)
	{
		$aInsert->setData ( $sColumn , $value );
	}

	try{
		if(!\org\jecat\framework\db\DB::singleton()->execute($aInsert))
		{
			$aMessageQueue->create(
				\org\jecat\framework\message\Message::error,
				'向数据库导入数据时遇到了错误'
			);
			return false ;
		}
	}catch(\org\jecat\framework\db\ExecuteException $e){
		if( $e->isDuplicate() )
		{
			$aMessageQueue->create(
				\org\jecat\framework\message\Message::error,
				'写入数据时遇到重复数据:%s',
				$e->message()
			);
			return false ;
		}else{
			$aMessageQueue->create(
				\org\jecat\framework\message\Message::error,
				'%s',
				$e->message()
			);
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
	
	// 写入 settings.inc.php
	if( !setupServiceSettings() ){
		return false;
	}
	
	// 启动系统
	$aLoader = require_once install_root.'/common.php';
	$aLoader->startBaseSystem();
	
	// 写入 setting
	if( !setupSetting('default',$_REQUEST['dbPrefix']) )
	{
		return false ;
	}
	/*
		safemode会造成BUG，暂时先删去
	*/
	/*
	if( !setupSetting('safemode','ocsafe_') )
	{
		return false ;
	}
	*/
	
	$aLoader->startup();
	
	$aMsgQue = new \org\jecat\framework\message\MessageQueue() ;
	$rtn = true ;
	do{
		// 执行平台升级程序
		if(!upgradePlatform($aMsgQue)){
			$rtn = false ;
			break;
		}
		
		// 安装扩展
		if(!installExtensions($aMsgQue))
		{
			$rtn = false ;
			break;
		}
		
		// 设置管理员用户
		if(!insertAdminUser($aMsgQue))
		{
			$rtn = false ;
			break;
		}
		
		// 禁止写入缓存
		\org\opencomb\platform\service\ServiceSerializer::singleton()->clearSystemObjects() ;
		// 清空缓存
		\org\opencomb\platform\service\ServiceSerializer::singleton()->clearRestoreCache();
		
		$aMsgQue->create(
			\org\jecat\framework\message\Message::success,
			'系统安装完毕，感谢使用%s。',
			'{=$sDistributionTitle}'
		);
		
		$rtn = true ;
	}while(false);
	
	$aMsgQue->display();
	
	return $rtn ;
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
