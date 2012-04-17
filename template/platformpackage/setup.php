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

define('setup_folder',dirname(__FILE__)) ;
define('install_root',dirname(setup_folder)) ;
$sFrameworkFolder = install_root.'/framework' ;
$sPlatformFolder = install_root.'/platform' ;
$sExtensionsFolder = install_root.'/extensions' ;
$sServicesFolder = install_root.'/services' ;
$sPublicFolder = install_root.'/public' ;
$sPublicUrl = 'http://'.$_SERVER['HTTP_HOST']. dirname(dirname($_SERVER['SCRIPT_NAME'])) ;

	


<foreach for="$arrLibClassCode" item="sSourceCode">
	{=$sSourceCode}
</foreach>


switch(@$_GET['action'])
{
	
	
// ---------------------------------------------------------------------------------
// 第一步 检查服务器环境 -----------------------------------------------------------------
default:
	<include file="development-toolkit:platformpackage/setupCheckEnv.php" />
	break ;

	

// -------------------------------------------------------------------------------
// 第二步 确认协议 -----------------------------------------------------------------
case 'licence' :
	<include file="development-toolkit:platformpackage/setupLicence.php" />
	break ;

	
	
// ---------------------------------------------------------------------------------
// 第三步 输入信息 -----------------------------------------------------------------
case 'input' :
	<include file="development-toolkit:platformpackage/setupInput.php" />
	break ;

	
	
// ---------------------------------------------------------------------------------
// 第四步 执行安装 -------------------------------------------------------------------
case 'install' :
	<include file="development-toolkit:platformpackage/setupInstall.php" />
	break ;
	
	
}





#################################################################################################################
	echo '<!--' ;
}

?>
-->

		</div>
	</div>


</body>
</html>