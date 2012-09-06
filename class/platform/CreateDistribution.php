<?php
namespace org\opencomb\development\toolkit\platform ;

use org\opencomb\development\toolkit\zip\ZipAdapter;
use org\jecat\framework\message\Message;
use org\opencomb\platform\Platform;
use org\opencomb\coresystem\auth\Id;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\opencomb\platform\ext\Extension;
use org\opencomb\platform\ext\ExtensionManager;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\fs\FSIterator;
use org\jecat\framework\io\OutputStreamBuffer;
use org\jecat\framework\ui\xhtml\UIFactory;
use org\opencomb\platform\service\Service;
use org\opencomb\platform\service\ServiceFactory;
use org\jecat\framework\util\Version;

class CreateDistribution extends ControlPanel
{
	protected $arrConfig = array(
			'title'=>'创建发行版本',
			'view' => array(
				'template' => 'platform/CreateDistribution.html' ,
			),
			'perms' => array(
					// 权限类型的许可
					'perm.purview'=>array(
							'name' => Id::PLATFORM_ADMIN,
					) ,
			) ,
		) ;
	
	const version = '1.0.7';
	public function process()
	{
		$this->checkPermissions('您没有使用这个功能的权限,无法继续浏览',array()) ;
		
		if( $this->doActions() )
		{
			$this->view->hideForm() ;
			return ;
		}
		
		// template
		$this->view->variables()->set('arrPlatforms',self::$arrPlatforms);
		$this->view->variables()->set('arrExtension',ExtensionManager::singleton()->metainfoIterator());
	}
	
	protected function form()
	{
		if(empty($this->params['platform']) or !isset(self::$arrPlatforms[$this->params['platform']]))
		{
			$this->createMessage(Message::error,"缺少有效的参数：platform") ;
			return ;
		}
		$arrPlatformInfo = self::$arrPlatforms[$this->params['platform']] ;
		
		$sDistrVersion = $this->params()->get('sDistributionVersion') ;
		$sDistrName = $this->params()->get('sDistributionName') ;
		
		$bIncludeRepos = $this->params->bool("debug-version") ;
		
		// 创建压缩包		
		$sDistributionZipFilename = $sDistrName.'-'.$sDistrVersion.'-'.$this->params['platform'].'.zip' ;
		$aDistributionFolder = Extension::flyweight('development-toolkit')->filesFolder()->findFolder('distributions',Folder::FIND_AUTO_CREATE) ; 
		$aPackageFile = $aDistributionFolder->findFile($sDistributionZipFilename,Folder::FIND_AUTO_CREATE_OBJECT) ;
		$sPackagePath = $aPackageFile->path() ;
		
		$aDistributionZip = new ZipAdapter() ;
		// $aDistributionZip = new ZipAdapter(ZipAdapter::Type_PclZip) ;
		$aDistributionZip->open($sPackagePath, ZipAdapter::CREATE | ZipAdapter::OVERWRITE);
		
		// 打包扩展
		$arrExtensionFolders = $arrLicenceList = array() ;
		foreach($this->params['arrExtensions'] as $sExtName=>$sExtPackagePath) 
		{
			$aExtMetainfo = ExtensionManager::singleton()->extensionMetainfo($sExtName) ;
			
			// 许可
			foreach($aExtMetainfo->licenceIterator() as $aLicenseFile)
			{
				$arrLicenceList[] = array(
						'exttitle' => $aExtMetainfo->title() ,
						'extname' => $aExtMetainfo->name() ,
						'extversion' => $aExtMetainfo->version() ,
						'title' => $aLicenseFile->title() ,
						'contents' => $aLicenseFile->openReader()->readToString() ,
				) ;
			}
			
			// 安装包
			$sSubPath = 'extensions/'.$sExtName.'/'.$aExtMetainfo->version() ;
			$this->packFolder($aExtMetainfo->installPath(),$sSubPath,$aDistributionZip,$bIncludeRepos) ;
			$arrExtensionFolders[$sExtName] = $sSubPath ;
		}
		$this->params['arrExtensionFolders'] = $arrExtensionFolders ;
		$this->params['arrLicenceList'] = $arrLicenceList ;
		
		// versions
		$this->params['platform_version'] = Platform::singleton()->version();
		$this->params['framework_version'] = Version::fromString( \org\jecat\framework\VERSION );
		
		// 打包系统文件
		$sPlatformRoot = Platform::singleton()->installFolder(true) ;
		$aDistributionZip->addFile($sPlatformRoot.'/index.php','index.php') ;
		$aDistributionZip->addFile($sPlatformRoot.'/Loader.php','Loader.php') ;
		$aDistributionZip->addFile($sPlatformRoot.'/common.php','common.php') ;
		$aDistributionZip->addFile($sPlatformRoot.'/defines.php','defines.php') ;
		$aDistributionZip->addFile($sPlatformRoot.'/PhpVersionError.php','PhpVersionError.php') ;
		$this->packFolder(\org\jecat\framework\PATH,'framework/'.$this->params['framework_version'],$aDistributionZip,$bIncludeRepos) ;
		$this->packFolder(\org\opencomb\platform\PATH,'platform/'.$this->params['platform_version'],$aDistributionZip,$bIncludeRepos) ;
		$this->packFolder($sPlatformRoot.'/vfs','vfs',$aDistributionZip,$bIncludeRepos) ;
		if( $bIncludeRepos ){
			$aDistributionZip->addFile($sPlatformRoot.'/.gitignore','.gitignore') ;
			$this->packFolder($sPlatformRoot.'/.git','.git',$aDistributionZip,$bIncludeRepos) ;
		}
		
		// 打包 setup ui fiels
		foreach(Service::singleton()->publicFolders()->folderIterator('development-toolkit.oc.setup') as $aFolder)
		{
			$aIterator = $aFolder->iterator() ;
			foreach( $aIterator as $sSubPath)
			{
				// 过滤已知版本库
				if( preg_match('`(^|/)(\\.svn|\\.git|\\.cvs)(/|$)`',$sSubPath) )
				{
					continue ;
				}
				$sSource = $aFolder->path().'/'.$sSubPath ;
				$sTarget = 'setup/ui/'.$sSubPath;
				if( $aIterator->isFile() ){
					$aDistributionZip->addFile($sSource,$sTarget) ;
				}else{
					$aDistributionZip->addEmptyFolder($sSource,$sTarget) ;
				}
			}
		}
		
		// 安装程序上默认的输入内容
		if( !empty($arrPlatformInfo['installer-default-input']) )
		{
			foreach($arrPlatformInfo['installer-default-input'] as $sName=>$sContent)
			{
				$this->params[$sName] = $sContent ;
			}
		}

		$this->params['arrPlatformInfo'] = $arrPlatformInfo ;
		$this->params['bCheckRootWritable'] = $arrPlatformInfo['bCheckRootWritable'] ;
		$this->params['sFileOcConfig'] = $arrPlatformInfo['sFileOcConfig'] ;
		

		// 打包前 的处理程序
		if(!empty($arrPlatformInfo['process-before-package']))
		{
			call_user_func($arrPlatformInfo['process-before-package'],$aDistributionZip) ;
		}
		
		$this->params['CreateDistributionVersion'] = Version::fromString(self::version);
		$this->params['extDevVersion'] = Extension::flyweight('development-toolkit')->metainfo()->version();
			
		// 生成文件安装程序并打包
		$this->packFileByTemplate('setup','setup.php','development-toolkit:platform/setup.php',$aDistributionZip) ;
		$this->packFileByTemplate('setup','setupCheckEnv.php','development-toolkit:platform/setupCheckEnv.php',$aDistributionZip) ;
		$this->packFileByTemplate('setup','setupLicence.php','development-toolkit:platform/setupLicence.php',$aDistributionZip) ;
		$this->packFileByTemplate('setup','setupInput.php','development-toolkit:platform/setupInput.php',$aDistributionZip) ;
		$this->packFileByTemplate('setup','setupInstall.php','development-toolkit:platform/setupInstall.php',$aDistributionZip) ;

		// 打包后 的处理程序
		if(!empty($arrPlatformInfo['process-after-package']))
		{
			call_user_func($arrPlatformInfo['process-after-package'],$this,$aDistributionZip) ;
		}
		
		$aDistributionZip->close();
		
		$this->createMessage(Message::success,"%s 安装程序制作完成 (<a href='%s'>下载</a>)",array($this->params['sDistributionTitle'],$aPackageFile->httpUrl())) ;
		
		return ;
	}
	
	public function packFileByTemplate($sPackageFolder,$sFileName,$sTemplate,ZipAdapter $aZip)
	{
		$aStream = new OutputStreamBuffer() ;
		UIFactory::singleton()->create()->display($sTemplate,$this->params(),$aStream) ;
		$aSetupTmp = Extension::flyweight('development-toolkit')->tmpFolder()->createChildFile($sFileName) ;
		$aSetupTmp->openWriter()->write($aStream) ;
		
		$sSourcePath = $aSetupTmp->path() ;
		$sTargetPath = $sPackageFolder.'/'.$sFileName;
		$aZip->addFile( $sSourcePath , $sTargetPath );
	}

	public function packFolder($sFolderPath,$sPackageFolder,ZipAdapter $aZip,$bRepo)
	{
		$sPath = null ;
		$aZip->addEmptyFolder($sFolderPath,$sPackageFolder) ;

		$aFolder = new Folder($sFolderPath) ;
		foreach($aIterator=$aFolder->iterator( FSIterator::FILE | FSIterator::FOLDER | FSIterator::RECURSIVE_SEARCH ) as $sPath)
		{
			if( !$bRepo and preg_match('`(^|/)\\.(git|svn|cvs|gitignore)(/|$)`',$sPath))
			{
				continue;
			}
			$sLocalPath = $aFolder->path().'/'.$sPath;
			$sPackagePath=$sPackageFolder.'/'.$sPath;
			if( $aIterator->isFile() ){
				$aZip->addFile($sLocalPath,$sPackagePath);
			}else{
				$aZip->addEmptyFolder($sLocalPath,$sPackagePath);
			}
		}
	}
	
	
	static public $arrPlatforms = array(
			
			'standard' => array(
				'title' => '标准安装包' ,	
				'essential-extensions' => array('coresystem') ,

				// 检查根目录的可写权限
				'bCheckRootWritable' => true ,
					
				// oc.config.php 文件的位置
				'sFileOcConfig' => "ROOT.'/oc.config.php'" ,
					
				// 安装程序上的默认输入
				'installer-default-input' => array(
						'sServicesFolder' => "ROOT.'/services'" ,
						'sPublicFilesFolder' => "ROOT.'/public/files'" ,
						'sPublicFileUrl' => "'public/files'" ,
						'sDBServer' => "127.0.0.1" ,
						'sDBUsername' => "root" ,
						'sDBPassword' => "" ,
						'sDBName' => "" ,
				) ,
				
				// service 安装位置
				'sInstallServiceFolder' => "install_root.'/services'" ,
				
				// serviceSetting
				'serviceSetting' => array(
					'type' => ServiceFactory::SCALABLE_SETTING,
					'innerSetting' => array(
						'type' => ServiceFactory::FS_SETTING,
					)
				),
			) ,
			

			'singlefile' => array(
				'title' => '单文件安装程序' ,
				'essential-extensions' => array('coresystem') ,
		
				// 检查根目录的可写权限
				'bCheckRootWritable' => true ,
				
				// oc.config.php 文件的位置
				'sFileOcConfig' => "ROOT.'/oc.config.php'" ,
					
				// 安装程序上的默认输入
				'installer-default-input' => array(
						'sServicesFolder' => "ROOT.'/services'" ,
						'sPublicFilesFolder' => "ROOT.'/public/files'" ,
						'sPublicFileUrl' => "'public/files'" ,
						'sDBServer' => "127.0.0.1" ,
						'sDBUsername' => "root" ,
						'sDBPassword' => "" ,
						'sDBName' => "" ,
				) ,
				
				// service 安装位置
				'sInstallServiceFolder' => "install_root.'/services'" ,
			) ,
			
			'sae' => array(
				'title' => '新浪云计算平台(SAE)应用包' ,
				'essential-extensions' => array('coresystem','saeadapter') ,
		
				// 检查根目录的可写权限
				'bCheckRootWritable' => false ,
				
				// oc.config.php 文件的位置
				'sFileOcConfig' => "'saestor://ocstor/oc.config.php'" ,
					
				// 安装程序上的默认输入
				'installer-default-input' => array(
						'sServicesFolder' => "'saestor://ocstor/services'" ,
						'sPublicFilesFolder' => "'saestor://ocstor/public/files'" ,
						'sPublicFileUrl' => "'http://{\$_SERVER['HTTP_APPNAME']}-ocstor.stor.sinaapp.com/public/files'" ,
						'sDBServer' => "<?php echo SAE_MYSQL_HOST_M ?>:<?php echo SAE_MYSQL_PORT ?>" ,
						'sDBUsername' => "<?php echo SAE_MYSQL_USER ?>" ,
						'sDBPassword' => "<?php echo SAE_MYSQL_PASS ?>" ,
						'sDBName' => "<?php echo SAE_MYSQL_DB ?>" ,
				) ,
			
				// service 安装位置
				'sInstallServiceFolder' => "'ocfs://oc/services'" ,
				
				'process-before-package' => array('org\\opencomb\\development\\toolkit\\platform\\CreateDistribution','packSaeAppWizard') ,
				
				// 插入到安装程序中的代码
				'sSetupCodes' => "
// 注册 SAE wrapper
require_once __DIR__.'/../defines.php';
" ,
				'finishSetupCheckCode' => " return false ",
				// 插入到oc.init.php文件中的代码
				'sOcInitCodes' => "
// 加载 SAE平台所需的类
require_once \\org\\jecat\\framework\\CLASSPATH.'/cache/SaeStorageCache.php' ;
// 注册 SAE wrapper
stream_wrapper_unregister('saestor') ;
stream_wrapper_register('saestor','org\\opencomb\\saeadapter\\wrapper\\SaeStorageWrapper') ;
// 注册 SaeServiceFactory
service\ServiceFactory::setSingleton(new \\org\\opencomb\\saeadapter\\service\\SaeServiceFactory) ;
" ,
				
				// serviceSetting
				'serviceSetting' => array(
					'type' => ServiceFactory::SCALABLE_SETTING,
					'innerSetting' => array(
						'type' => ServiceFactory::SAE_MEMCACHE_SETTING,
					)
				),
			),


			'debug' => array(
				'title' => '调式' ,
				'essential-extensions' => array('coresystem') ,
				'bCheckRootWritable' => true ,
				'sFileOcConfig' => "ROOT.'/oc.config.php'" ,
				'installer-default-input' => array(
						'sServicesFolder' => "ROOT.'/services'" ,
						'sPublicFilesFolder' => "ROOT.'/public/files'" ,
						'sPublicFileUrl' => "'public/files'" ,
						'sDBServer' => "192.168.1.1" ,
						'sDBUsername' => "root" ,
						'sDBPassword' => "111111" ,
						'sDBName' => "oc4" ,
				) ,
				'process-after-package' => array( __CLASS__, 'debugProcessAfterPackage' ) ,
				
				// service 安装位置
				'sInstallServiceFolder' => "install_root.'/services'" ,
			) ,
			

				
	) ;
	
	public static function debugProcessAfterPackage(self $aDistributionMaker, PclZip $aPackage)
	{
		// 解压到 测试安装程序的目录内
		Folder::createInstance('/local/d/project/otp/oc-setup')->deleteChild('*',true) ; ;
		$aPackage->extract('/local/d/project/otp/oc-setup/') ;
	}
	
	private function packSaeAppWizard(ZipAdapter $aPackage){
		// 生成 sae_app_wizard.xml
		$this->packFileByTemplate(
				null, 'sae_app_wizard.xml', 'development-toolkit:platform/sae_app_wizard.xml', $aPackage
		) ;
		
	}
}

