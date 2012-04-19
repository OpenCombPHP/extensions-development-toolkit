<?php
namespace org\opencomb\development\toolkit\platform ;

use net\phpconcept\pclzip\PclZip;
use org\jecat\framework\message\Message;
use org\jecat\framework\fs\LocalFolderIterator;
use org\jecat\framework as jc;
use org\jecat\framework\lang\Exception;
use org\opencomb\platform\Platform;
use org\opencomb\coresystem\auth\Id;
use org\opencomb\coresystem\mvc\controller\ControlPanel ;
use org\opencomb\platform\ext\Extension ;
use org\opencomb\platform\ext\ExtensionManager ;
use org\opencomb\platform\ext\dependence\RequireItem ;
use org\jecat\framework\fs\File ;
use org\jecat\framework\fs\Folder ;
use org\jecat\framework\fs\FSIterator ;
use org\jecat\framework\io\IOutputStream ;
use org\jecat\framework\io\OutputStreamBuffer ;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\opencomb\development\toolkit\extension\ExtensionPackages ;
use org\opencomb\platform\service\Service ;
use org\jecat\framework\util\Version ;

class CreateDistribution extends ControlPanel
{
	public function createBeanConfig(){
		return array(
			'title'=>'创建发行版本',
			'view:view' => array(
				'template' => 'platform/CreateDistribution.html' ,
				'class' => 'form' ,
			),
			'perms' => array(
					// 权限类型的许可
					'perm.purview'=>array(
							'name' => Id::PLATFORM_ADMIN,
					) ,
			) ,
		) ;
	}
	
	public function process()
	{
		$this->checkPermissions('您没有使用这个功能的权限,无法继续浏览',array()) ;
		
		if( $this->doActions() )
		{
			$this->view->hideForm() ;
			return ;
		}
		
		// template
		$this->view->variables()->set('arrExtension',ExtensionManager::singleton()->metainfoIterator());
	}
	
	protected function actionSubmit()
	{
		$sDistrVersion = $this->params()->get('sDistributionVersion') ;
		$sDistrName = $this->params()->get('sDistributionName') ;
		
		$bIncludeRepos = $this->params->bool("debug-version") ;
		
		// 创建压缩包		
		$sDistributionZipFilename = $sDistrName.'-'.$sDistrVersion ;
		if($this->params()->bool('sae-package'))
		{
			$sDistributionZipFilename.= '-sae' ;
		}
		$sDistributionZipFilename.= '.zip' ;
		
		$aDistributionFolder = Extension::flyweight('development-toolkit')->filesFolder()->findFolder('distributions',Folder::FIND_AUTO_CREATE) ; 
		$aPackageFile = $aDistributionFolder->findFile($sDistributionZipFilename,Folder::FIND_AUTO_CREATE_OBJECT) ;
		$sPackagePath = $aPackageFile->path() ;
		if(file_exists($sPackagePath))
		{
			unlink($sPackagePath) ;
		}
		$aDistributionZip = new PclZip($sPackagePath) ;
		
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

		// 打包系统文件
		$sPlatformRoot = Platform::singleton()->installFolder(true) ;
		$aDistributionZip->add($sPlatformRoot.'/index.php',PCLZIP_OPT_REMOVE_PATH,$sPlatformRoot) ;
		$this->packFolder($sPlatformRoot.'/framework','framework',$aDistributionZip,$bIncludeRepos) ;
		$this->packFolder($sPlatformRoot.'/platform','platform',$aDistributionZip,$bIncludeRepos) ;
		
		// 打包 setup ui fiels
		foreach(Service::singleton()->publicFolders()->folderIterator('development-toolkit.oc.setup') as $aFolder)
		{
			foreach($aFolder->iterator() as $sSubPath)
			{
				// 过滤已知版本库
				if( preg_match('`(^|/)(\\.svn|\\.git|\\.cvs)(/|$)`',$sSubPath) )
				{
					continue ;
				}
				$sSource = $aFolder->path().'/'.$sSubPath ;
				$aDistributionZip->add($sSource,'/setup/ui/',$aFolder->path()) ;
			}
		}
		
		// 打包安装时所需的工具类
		$arrLibClasses = array() ;
		$arrLibClassCode = array() ;
		if($this->params()->bool('sae-package'))
		{
			$arrLibClasses[] = "org\\jecat\\framework\\fs\\wrapper\\SaeStorageWrapperEx" ;
		}
		foreach($arrLibClasses as $sClass)
		{
			$sSourceCode = file_get_contents( \org\jecat\framework\lang\oop\ClassLoader::singleton()->searchClass(
					$sClass, \org\jecat\framework\lang\oop\Package::nocompiled
			) ) ;
			$sSourceCode = str_replace('<?php','',$sSourceCode) ;
			$sSourceCode = str_replace('<?','',$sSourceCode) ;
			$sSourceCode = str_replace('?>','',$sSourceCode) ;
			$sSourceCode = preg_replace('|namespace[^;]+;|','',$sSourceCode) ;
			$arrLibClassCode[$sClass] = $sSourceCode ;
		}
		$this->params['arrLibClassCode'] = $arrLibClassCode ;
		
		$this->params['bCheckRootWritable'] = true ;
		
		// 安装路径
		$this->params['sFileOcConfig'] = "ROOT.'/oc.config.php'" ;
		$this->params['sServicesFolder'] = 'services' ;
		$this->params['sPublicFilesFolder'] = 'public/files' ;
		$this->params['sPublicFileUrl'] = 'public/files' ;
		
		$this->params['sDBServer'] = "192.168.1.1" ;
		$this->params['sDBUsername'] = "root" ;
		$this->params['sDBPassword'] = "1" ;
		$this->params['sDBName'] = "oc".rand(0,999) ;

		// (新浪云平台)
		if($this->params()->bool('sae-package'))
		{
			$this->params['sFileOcConfig'] = "'saestor://ocstor/oc.config.php'" ;
			$this->params['sServicesFolder'] = 'saestor://ocstor/services' ;
			$this->params['sPublicFilesFolder'] = 'saestor://ocstor/public/files' ;
			$this->params['sPublicFileUrl'] = "http://<?php echo \$_SERVER['HTTP_APPNAME']?>-ocstor.stor.sinaapp.com/public/files" ;

			$this->params['sDBServer'] = "<?php echo SAE_MYSQL_HOST_M ?>:<?php echo SAE_MYSQL_PORT ?>" ;
			$this->params['sDBUsername'] = "<?php echo SAE_MYSQL_USER ?>" ;
			$this->params['sDBPassword'] = "<?php echo SAE_MYSQL_PASS ?>" ;
			$this->params['sDBName'] = "<?php echo SAE_MYSQL_DB ?>" ;
			
			// 生成 sae_app_wizard.xml
			$this->packFileByTemplate(null,'sae_app_wizard.xml','development-toolkit:platform/sae_app_wizard.xml',$aDistributionZip) ;

			$this->params['bCheckRootWritable'] = false ;
		}
		
		// 生成文件安装程序并打包
		$this->packFileByTemplate(null,'oc.init.php','development-toolkit:platform/oc.init.php',$aDistributionZip) ;
		$this->packFileByTemplate('/setup','setup.php','development-toolkit:platform/setup.php',$aDistributionZip) ;
		
		
		$this->createMessage(Message::success,"%s 安装程序制作完成 (<a href='%s'>下载</a>)",array($this->params['sDistributionTitle'],$aPackageFile->httpUrl())) ;
		
		Folder::createInstance('/local/d/project/otp/oc-setup')->deleteChild('*',true) ; ;
		$aDistributionZip->extract('/local/d/project/otp/oc-setup/') ;
		return ;
	}
	
	private function packFileByTemplate($sPackageFolder,$sFileName,$sTemplate,PclZip $aZip)
	{
		$aStream = new OutputStreamBuffer() ;
		UIFactory::singleton()->create()->display($sTemplate,$this->params(),$aStream) ;
		$aSetupTmp = Extension::flyweight('development-toolkit')->tmpFolder()->createChildFile($sFileName) ;
		$aSetupTmp->openWriter()->write($aStream) ;

		$aZip->add($aSetupTmp->path(),$sPackageFolder,$aSetupTmp->dirPath()) ;
		$aSetupTmp->delete() ;
	}

	private function packFolder($sFolderPath,$sPackageFolder,PclZip $aZip,$bRepo)
	{	
		$sPath = null ;
		foreach( explode('/',ltrim($sPackageFolder,'/')) as $sFolderName )
		{
			$sPath.= $sFolderName . '/' ;
			$aZip->add($sFolderPath,$sPath,$sFolderPath) ;
		}
			
		$aFolder = new Folder($sFolderPath) ;
		foreach($aIterator=$aFolder->iterator( FSIterator::FILE | FSIterator::FOLDER | FSIterator::RECURSIVE_SEARCH ) as $sPath)
		{
			if( !$bRepo and preg_match('`(^|/)\\.(git|svn|cvs|gitignore)(/|$)`',$sPath))
			{
				continue;
			}
			$sLocalPath = $aFolder->path().'/'.$sPath;
			if($aZip->add($sLocalPath,$sPackageFolder,$aFolder->path())===0)
			{
				$this->extensionPackages->createMessage(Message::error,'打包文件时出错:%s',$sPath);
				return ;
			}
		}

	}
		
}
