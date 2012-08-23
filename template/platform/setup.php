<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link href="ui/css/platformsetup.css" ignore="true" rel="stylesheet"
	type="text/css" />
<link href="ui/css/platformdataupgrader.css" ignore="true"
	rel="stylesheet" type="text/css" />
<script type="text/javascript" src="ui/js/jquery.js" ignore="true"></script>


<title>{=$sDistributionTitle} 安装程序</title>

</head>
<body>

	<div class="main">
		<div class="content">
			<div class="topbar">
				<h1>
					{=$sDistributionTitle} <span class="azxd">安装向导</span> <span
						class="topversion">{=$sDistributionVersion}</span>
				</h1>
			</div>
	
<?php 
if(false){
?>
	<h1>你的服务器不支持php，无法进行安装</h1>
			<!-- 
<?php } else {
#################################################################################################################

// 这个文件是由扩展 development-toolkit 的 CreateDistribution 模块自动生成
// 扩展 development-toolkit 版本：{=$extDevVersion}
// CreateDistribution 模块版本：{=$CreateDistributionVersion}

define('setup_folder',dirname(__FILE__)) ;
define('install_root',dirname(setup_folder)) ;
define('install_service',{=$arrPlatformInfo['sInstallServiceFolder']}) ;
define('ROOT',install_root) ;
$arrExtensionFolders = {=var_export($arrExtensionFolders,true)} ;
$framework_version = '{=$framework_version}';
$platform_version = '{=$platform_version}';

function isFinishedSetup(){
	{=$finishSetupCheckCode}
}

if( isFinishedSetup() )
{
?>
	<div>
		系统已经安装完毕，请尽快删除系统根目录下的 setup 目录，以消除安全隐患。
	</div>
	
<?php
}
else
{
	switch(@$_GET['action'])
	{
		
		
	// ---------------------------------------------------------------------------------
	// 第一步 检查服务器环境 -----------------------------------------------------------------
	default:
		include_once setup_folder.'/setupCheckEnv.php' ;
		break ;
		
	// -------------------------------------------------------------------------------
	// 第二步 确认协议 -----------------------------------------------------------------
	case 'licence' :
		include_once setup_folder.'/setupLicence.php' ;
		break ;
		
	// ---------------------------------------------------------------------------------
	// 第三步 输入信息 -----------------------------------------------------------------
	case 'input' :
		include_once setup_folder.'/setupInput.php' ;
		break ;
	
	// ---------------------------------------------------------------------------------
	// 第四步 执行安装 -------------------------------------------------------------------
	case 'install' :
		include_once setup_folder.'/setupInstall.php' ;
		break ;
		
		
	}
	
	
	
	
	
#################################################################################################################
	
}

	echo '<!--' ;
}
?>
-->

		</div>
	</div>


</body>
</html>
