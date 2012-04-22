<?php
namespace org\opencomb\development\toolkit\platform ;

use org\jecat\framework\lang\oop\Package;

use org\jecat\framework\lang\oop\ClassLoader;

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
		$this->view->variables()->set('arrPlatforms',self::$arrPlatforms);
		$this->view->variables()->set('arrExtension',ExtensionManager::singleton()->metainfoIterator());
	}
	
	protected function actionSubmit()
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
		
		// 安装程序上默认的输入内容
		if( !empty($arrPlatformInfo['installer-default-input']) )
		{
			foreach($arrPlatformInfo['installer-default-input'] as $sName=>$sContent)
			{
				$this->params[$sName] = $sContent ;
			}
		}

		$this->params['arrPlatformInfo'] = $arrPlatformInfo ;
		

		// 打包前 的处理程序
		if(!empty($arrPlatformInfo['process-before-package']))
		{
			call_user_func($arrPlatformInfo['process-before-package'],$this,$aDistributionZip) ;
		}
		
		// 生成文件安装程序并打包
		$this->packFileByTemplate(null,'oc.init.php','development-toolkit:platform/oc.init.php',$aDistributionZip) ;
		$this->packFileByTemplate('/setup','setup.php','development-toolkit:platform/setup.php',$aDistributionZip) ;
		$this->packFileByTemplate('/setup','setupCheckEnv.php','development-toolkit:platform/setupCheckEnv.php',$aDistributionZip) ;
		$this->packFileByTemplate('/setup','setupLicence.php','development-toolkit:platform/setupLicence.php',$aDistributionZip) ;
		$this->packFileByTemplate('/setup','setupInput.php','development-toolkit:platform/setupInput.php',$aDistributionZip) ;
		$this->packFileByTemplate('/setup','setupInstall.php','development-toolkit:platform/setupInstall.php',$aDistributionZip) ;

		// 打包后 的处理程序
		if(!empty($arrPlatformInfo['process-after-package']))
		{
			call_user_func($arrPlatformInfo['process-after-package'],$this,$aDistributionZip) ;
		}
		
		$this->createMessage(Message::success,"%s 安装程序制作完成 (<a href='%s'>下载</a>)",array($this->params['sDistributionTitle'],$aPackageFile->httpUrl())) ;
		
		return ;
	}
	
	public function packFileByTemplate($sPackageFolder,$sFileName,$sTemplate,PclZip $aZip)
	{
		$aStream = new OutputStreamBuffer() ;
		UIFactory::singleton()->create()->display($sTemplate,$this->params(),$aStream) ;
		$aSetupTmp = Extension::flyweight('development-toolkit')->tmpFolder()->createChildFile($sFileName) ;
		$aSetupTmp->openWriter()->write($aStream) ;

		$aZip->add($aSetupTmp->path(),$sPackageFolder,$aSetupTmp->dirPath()) ;
		$aSetupTmp->delete() ;
	}

	public function packFolder($sFolderPath,$sPackageFolder,PclZip $aZip,$bRepo)
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
	
	
	static public $arrPlatforms = array(
			
			'standard' => array(
				'title' => '标准安装包' ,	
				'essential-extensions' => array('coresystem') ,

				// 检查根目录的可写权限
				'bCheckRootWritable' => true ,

				// oc.init.php 上的常量定义
				'oc.init.php:define-const' => array(
						'FRAMEWORK_FOLDER' => "ROOT.'/framework'" ,
						'PLATFORM_FOLDER' => "ROOT.'/platform'" ,
						'EXTENSIONS_FOLDER' => "ROOT.'/extensions'" ,
						'EXTENSIONS_URL' => "'extensions'" ,
						'SERVICES_FOLDER' => "ROOT.'/services'" ,
						'PUBLIC_FILES_FOLDER' => "public/files" ,
						'PUBLIC_FILES_URL' => "public/files" ,
				) ,
					
				// 安装程序上的默认输入
					'installer-default-input' => array(
						'sDBServer' => "127.0.0.1" ,
						'sDBUsername' => "root" ,
						'sDBPassword' => "" ,
						'sDBName' => "" ,
				) ,

				// 打包前后的处理函数
				'process-before-package' => null ,
				'process-after-package' => null ,

				// 插入到安装程序开头的代码
				'sSetupCodes' => null ,
					
				// 插入到oc.init.php文件中的代码
				'sOcInitCodes' => null ,
			) ,
	) ;
	
	static public function debugProcessAfterPackage(CreateDistribution $aDistributionMaker, PclZip $aPackage)
	{
		// 解压到 测试安装程序的目录内
		Folder::createInstance('/local/d/project/otp/oc-setup')->deleteChild('*',true) ; ;
		$aPackage->extract('/local/d/project/otp/oc-setup/') ;
	}
}

// 单文件 版本----
CreateDistribution::$arrPlatforms['singlefile'] = CreateDistribution::$arrPlatforms['standard'] ;
CreateDistribution::$arrPlatforms['singlefile']['title'] = '单文件安装程序' ;

// 调式 版本----
CreateDistribution::$arrPlatforms['debug'] = CreateDistribution::$arrPlatforms['standard'] ;
CreateDistribution::$arrPlatforms['debug']['title'] = '调式' ;
CreateDistribution::$arrPlatforms['debug']['installer-default-input'] = array(
		'sDBServer' => "192.168.1.1" ,
		'sDBUsername' => "root" ,
		'sDBPassword' => "111111" ,
		'sDBName' => "oc4" ,
) ;
CreateDistribution::$arrPlatforms['debug']['process-after-package'] = array('org\\opencomb\\development\\toolkit\\platform\\CreateDistribution','debugProcessAfterPackage') ;