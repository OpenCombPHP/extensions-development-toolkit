{='<?php'} 
namespace org\opencomb\platform ;

ini_set('display_errors',1) ;
error_reporting(E_ALL^E_STRICT) ;

define('org\\opencomb\\platform\\ROOT',__DIR__) ;

// 配置目录
define('org\\opencomb\\platform\\FRAMEWORK_FOLDER',ROOT.'/framework') ;
define('org\\opencomb\\platform\\PLATFORM_FOLDER',ROOT.'/platform') ;
define('org\\opencomb\\platform\\EXTENSIONS_FOLDER',ROOT.'/extensions') ;
define('org\\opencomb\\platform\\EXTENSIONS_URL','extensions') ;
define('org\\opencomb\\platform\\SERVICES_FOLDER',{=$sServicesFolder}) ;
define('org\\opencomb\\platform\\PUBLIC_FILES_FOLDER',{=$sPublicFilesFolder}) ;
define('org\\opencomb\\platform\\PUBLIC_FILES_URL',{=$sPublicFileUrl}) ;

// 加载 jecat framework
require_once FRAMEWORK_FOLDER."/inc.entrance.php" ;

// 检查是否完成安装
if( !is_dir(SERVICES_FOLDER) and is_file(__DIR__.'/setup/setup.php') )
{
	echo '<a href="setup/setup.php">Install ... </a>' ;
	exit() ;
}

// load jecat core class
require_once \org\jecat\framework\CLASSPATH."/system/HttpAppFactory.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/ISetting.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/Setting.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/IKey.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/Key.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/imp/FsSetting.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/imp/FsKey.php" ;
require_once \org\jecat\framework\CLASSPATH.'/cache/Cache.php' ;
require_once \org\jecat\framework\CLASSPATH.'/cache/FSCache.php' ;
require_once \org\jecat\framework\CLASSPATH.'/cache/EmptyCache.php' ;

// load opencomb core class
require_once PLATFORM_FOLDER."/packages/org.opencomb.platform/Platform.php" ;
require_once PLATFORM_FOLDER."/packages/org.opencomb.platform/service/Service.php" ;
require_once PLATFORM_FOLDER."/packages/org.opencomb.platform/service/ServiceFactory.php" ;
require_once PLATFORM_FOLDER."/packages/org.opencomb.platform/service/ServiceSerializer.php" ;

{= isset($arrPlatformInfo['sOcInitCodes'])? $arrPlatformInfo['sOcInitCodes']: '' }

// 初始化 platform
$aPlatform = Platform::singleton() ;


// 创建 service
$aService = $aPlatform->createService($_SERVER['HTTP_HOST']) ;
// Service::setInstance($aService) ;


// 检查 service 状态 (是否关闭)
/*if( is_file(__DIR__.'/lock.shutdown.html') )
{
	// 检查”后门“密钥，方便管理员进入
	if( empty($_REQUEST['shutdown_backdoor_secret_key']) or !is_file(__DIR__.'/lock.shutdown.backdoor.php') or include(__DIR__.'/lock.shutdown.backdoor.php')!=$_REQUEST['shutdown_backdoor_secret_key'] )
	{
		// ”后门密钥“检查失败，关闭系统
		include __DIR__.'/lock.shutdown.html' ;
		exit() ;
	}
}*/


// 检查 service 升级
/*$aDataUpgrader = PlatformDataUpgrader::singleton() ; 
if(TRUE === $aDataUpgrader->process()){
	$aDataUpgrader->relocation();
	exit();
}*/

// 
return $aService ;
