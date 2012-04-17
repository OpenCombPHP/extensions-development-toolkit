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
		

		// extension list
		$arrExtension = ExtensionManager::singleton()->metainfoIterator() ;
		
		// package state
		$arrPackageState = array( );
		$aMetainfoIterator = ExtensionManager::singleton()->metainfoIterator() ;
		foreach($aMetainfoIterator as $aMetainfo)
		{
			$sExtName = $aMetainfo->name();
			$arrPackageState[$sExtName][0] = ExtensionPackages::getPackagedFSO($sExtName,$aMetainfo->version(),0,0) ;
			$arrPackageState[$sExtName][1] = ExtensionPackages::getPackagedFSO($sExtName,$aMetainfo->version(),1,0) ;			
		}
		
		// template
		$this->view->variables()->set('arrExtension',$arrExtension);
		$this->view->variables()->set('arrPackageState',$arrPackageState);
	}
	
	protected function actionSubmit()
	{
		$sDistrVersion = $this->params()->get('sDistributionVersion') ;
		$sDistrName = $this->params()->get('sDistributionName') ;
		
		// 创建压缩包		
		$sDistributionZipFilename = $sDistrName.'-'.$sDistrVersion.'.zip' ;
		$aDistributionFolder = Extension::flyweight('development-toolkit')->filesFolder()->findFolder('distributions',Folder::FIND_AUTO_CREATE) ; 
		$aPackageFile = $aDistributionFolder->findFile($sDistributionZipFilename,Folder::FIND_AUTO_CREATE_OBJECT) ;
		$sPackagePath = $aPackageFile->path() ;
		if(file_exists($sPackagePath))
		{
			unlink($sPackagePath) ;
		}
		$aDistributionZip = new PclZip($sPackagePath) ;
		
		// 打包 framework
		$sJcPackageName = 'jecat-framework-'.jc\VERSION.($this->params->bool("framework-debug")?'-repo':'').'.zip' ;
		$sJcPackagePath = Extension::flyweight('development-toolkit')->filesFolder()->path() . '/extensionPackages/'.$sJcPackageName ;
		$aZip = $this->packageFolder( $sJcPackagePath, jc\PATH, $this->params->bool("framework-debug") ) ;
		$aDistributionZip->add($sJcPackagePath,'/setup/packages/',dirname($sJcPackagePath)) ;
		$this->params['sFrameworkPackageFilename'] = $sJcPackageName ;
		
		// 打包 opencomb
		$sOcPackageName = 'opencomb-platform-'.Platform::singleton()->version(true).($this->params->bool("framework-debug")?'-repo':'').'.zip' ;
		$sOcPakcagePath = Extension::flyweight('development-toolkit')->filesFolder()->path() . '/extensionPackages/' . $sOcPackageName ; 
		$aZip = $this->packageFolder( $sOcPakcagePath, \org\opencomb\platform\PLATFORM_FOLDER, $this->params->bool("framework-debug") ) ;
		$aDistributionZip->add($sOcPakcagePath,'/setup/packages/',dirname($sOcPakcagePath)) ;
		$this->params['sPlatformPackageFilename'] = $sOcPackageName ;
		
		// 打包扩展
		if(!$this->params['arrExtensions'])
		{
			$this->params['arrExtensions'] = array() ;
		}
		$arrExtensionPackages = $arrLicenceList = array() ;
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
			$aDistributionZip->add($sExtPackagePath,'setup/packages/',dirname($sExtPackagePath)) ;
			$arrExtensionPackages[$sExtName.'/'.$aExtMetainfo->version()] = basename($sExtPackagePath) ;
		}
		$this->params['arrExtensionPackages'] = $arrExtensionPackages ;
		$this->params['arrLicenceList'] = $arrLicenceList ;
			
		// 打包常规文件
		$sPlatformRoot = Platform::singleton()->installFolder(true) ;
		foreach( array('index.php','oc.init.php') as $sFileSubpath )
		{
			$aDistributionZip->add($sPlatformRoot.'/'.$sFileSubpath,PCLZIP_OPT_REMOVE_PATH,$sPlatformRoot) ;
		}
		
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
		$arrLibClassCode = array() ;
		foreach(array("net\\phpconcept\\pclzip\\PclZip") as $sClass)
		{
			$sSourceCode = file_get_contents( \org\jecat\framework\lang\oop\ClassLoader::singleton()->searchClass(
					"net\\phpconcept\\pclzip\\PclZip"
					,\org\jecat\framework\lang\oop\Package::nocompiled
			) ) ;
			$sSourceCode = str_replace('<?php','',$sSourceCode) ;
			$sSourceCode = str_replace('<?','',$sSourceCode) ;
			$sSourceCode = str_replace('?>','',$sSourceCode) ;
			$sSourceCode = preg_replace('|namespace[^;]+;|','',$sSourceCode) ;
			$arrLibClassCode[$sClass] = $sSourceCode ;
		}
		$this->params['arrLibClassCode'] = $arrLibClassCode ;
		
		// 生成文件安装程序并打包
		$aStream = new OutputStreamBuffer() ;
		UIFactory::singleton()->create()->display('development-toolkit:platform/setup.php',$this->params(),$aStream) ;
		$aSetupTmp = Extension::flyweight('development-toolkit')->tmpFolder()->createChildFile('setup.php') ;
		$aSetupTmp->openWriter()->write($aStream) ;
		
		$aDistributionZip->add($aSetupTmp->path(),'/setup',$aSetupTmp->dirPath()) ;
		$aSetupTmp->delete() ;
		
		
		$this->createMessage(Message::success,"%s 安装程序制作完成 (<a href='%s'>下载</a>)",array($this->params['sDistributionTitle'],$aPackageFile->httpUrl())) ;
		
		//Folder::createInstance('/local/d/project/otp/oc-setup')->deleteChild('*',true) ; ;
		//$aDistributionZip->extract('/local/d/project/otp/oc-setup/') ;
		return ;
	}
	
	private function packageFolder($sZipPath,$sFolderPath,$bRepo)
	{
		if( file_exists($sZipPath) )
		{
			unlink($sZipPath) ;
		}
		
		$aZip = new PclZip($sZipPath);
		
		
		$aFolder = new Folder($sFolderPath) ;
		foreach($aIterator=$aFolder->iterator( FSIterator::FILE | FSIterator::FOLDER | FSIterator::RECURSIVE_SEARCH ) as $sPath)
		{
			if( !$bRepo and preg_match('`(^|/)\\.(git|svn|cvs|gitignore)(/|$)`',$sPath))
			{
				continue;
			}
			$sLocalPath = $aFolder->path().'/'.$sPath;
			if($aZip->add($sLocalPath,PCLZIP_OPT_REMOVE_PATH,$aFolder->path())===0)
			{
				$this->extensionPackages->createMessage(Message::error,'打包文件时出错:%s',$sPath);
				return ;
			}
		}
	}
	
}
