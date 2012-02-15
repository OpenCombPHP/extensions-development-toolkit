<?php
namespace org\opencomb\development\toolkit\platform\createpackage ;

use org\opencomb\coresystem\auth\Id;

use org\opencomb\coresystem\mvc\controller\ControlPanel ;
use org\opencomb\platform\ext\Extension ;
use org\opencomb\platform\ext\ExtensionManager ;
use org\opencomb\platform\ext\dependence\RequireItem ;
use org\jecat\framework\fs\IFile ;
use org\jecat\framework\fs\IFolder ;
use org\jecat\framework\fs\FileSystem ;
use org\jecat\framework\fs\FSIterator ;
use org\jecat\framework\io\IOutputStream ;
use org\jecat\framework\io\OutputStreamBuffer ;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\opencomb\development\toolkit\extension\ExtensionPackages ;
use org\opencomb\platform\Platform ;
use org\jecat\framework\util\Version ;

class CreatePackage extends ControlPanel
{
	public function createBeanConfig(){
		return array(
			'view:view' => array(
				'template' => 'platformpackage/CreatePackage.html' ,
			),
			'perms' => array(
					// 权限类型的许可
					'perm.purview'=>array(
							'name' => Id::PLATFORM_ADMIN,
					) ,
			) ,
		) ;
	}
	
	public function process(){
		
		$this->checkPermissions('您没有使用这个功能的权限,无法继续浏览',array()) ;
		
		// input
		$arrExtName = $this->params['ext'];
		$git = $this->params['git'] ;
		
		if(empty($arrExtName)){
			$arrExtName = array();
		}
		
		// stamp
		$sStamp = '';
		
		// arr zip files
		$arrZipFiles = 
		array(
			'framework' =>
			array(
				'name'=>'framework',
				'path'=>'framework',
			),
			'platform' =>
			array(
				'name'=>'platform',
				'path'=>'.',
			),
		);
		
		// packagePublicSetup
		$arrZipFiles[] = $this->packagePublicSetup();
		 
		// package framework and platform
		$arr = array('framework','platform') ;
		foreach($arr as $s){
			if(!isset($git[$s])){
				$git[$s] = 0;
			}
			$uc = ucwords($s);
			$aZipFile = ExtensionPackages::getPackagedFSO($s , 'version' ,$git[$s] ) ;
			$aZip = $this->createZip($aZipFile);
			$sFun = 'package'.$uc;
			$this->$sFun($aZip , $git[$s]);
			$aZip->close();
			$arrZipFiles[$s]['reader'] = $aZipFile->openReader();
			$arrZipFiles[$s]['localpath'] = $aZipFile->path();
			$arrZipFiles[$s]['git'] = $git[$s] ;
		}
		// dependence
		$this->calcDependence($arrExtName);

		$aExtensionManager = ExtensionManager::singleton();
		foreach($arrExtName as $sExtName){
			$aMetainfo = $aExtensionManager->extensionMetainfo($sExtName);
			$sName = $aMetainfo->name();
			$sVersion = $aMetainfo->version()->toString();
			$aFile = ExtensionPackages::getPackagedFSO($sName , $sVersion ,$git[$sName] ) ;
			$arrZipFiles [] = 
			array(
				'name' => $sName ,
				'path' => 'extensions/'.$sName ,
				'reader' => $aFile->openReader() ,
				'localpath' => $aFile->path(),
				'git' => $git[$sName] ,
			);
		}
		// create setup
		$aWriter = $this->createWriter();
		$this->createSetup($aWriter , $arrZipFiles);
		
		// template
		$this->view->variables()->set('arrZipFiles',$arrZipFiles);
	}
	
	private function createZip(IFile $aFile){
		if($aFile->exists()){
			$aFile->delete();
		}
		$aZip = new \ZipArchive;
		$sFilePath = $aFile->url(false);
		if($aZip->open($sFilePath,\ZIPARCHIVE::CREATE) !== TRUE){
			throw new Exception("can not open file <%s>",$sFilePath);
		}
		return $aZip ;
	}
	
	private function packageFramework(\ZIPARCHIVE $aZip , $git ){
		$aFolder = FileSystem::singleton()->findFolder('/framework');
		$sExcludePattern = '';
		if(empty($git)){
			$sExcludePattern = '`^\\.(git|svn|cvs)(/|$|ignore)`' ;
		}else{
			$sExcludePattern = '' ;
		}
		return $this->package($aZip,$aFolder,'',$sExcludePattern);
	}
	
	private function packagePlatform(\ZIPARCHIVE $aZip , $git ){
		$aFolder = FileSystem::singleton()->findFolder('/');
		$sExcludePattern = '';
		if(empty($git)){
			$sExcludePattern = '`^(\\.(git|svn|cvs)|(framework|data|extensions|settings|.settings)(/|$))`' ;
		}else{
			$sExcludePattern = '`^(framework|data|extensions|settings|.settings)(/|$)`' ;
		}
		return $this->package($aZip,$aFolder,'',$sExcludePattern);
	}
	
	private function package(\ZIPARCHIVE $aZip,IFolder $aFolder , $sPrefix , $sExcludePattern){
		$aIterator = $aFolder->iterator( FSIterator::FILE | FSIterator::FOLDER | FSIterator::RECURSIVE_SEARCH ) ;
		foreach( $aIterator as $sPath){
			if( !empty($sExcludePattern) and preg_match($sExcludePattern,$sPath)){
				continue;
			}
			$sInZipPath = $sPrefix.'/'.$sPath;
			if( $aIterator->isFolder() ){
				$bR = $aZip->addEmptyDir($sInZipPath);
				if($bR === false){
					echo $aZip->getStatusString();
				}
			}else{
				$sLocalPath = $aFolder->url(false).'/'.$sPath;
				$bR = $aZip->addFile($sLocalPath , $sInZipPath);
				if($bR === false){
					echo $aZip->getStatusString();
				}
			}
		}
	}
	
	private function createWriter(){
		$aFile = Extension::flyweight('development-toolkit')
				->publicFolder()
					->findFile('setup.php',FileSystem::FIND_AUTO_CREATE);
		return $aFile->openWriter();
	}
	
	private function createSetup(IOutputStream $aDevice , array $arrZipFile){
		$aUI = UIFactory::singleton()->create();
		// checkEnv
		$aCheckEnvBuffer = new OutputStreamBuffer;
		$arrVariables = array(
			'arrDependence' => $this->arrDependence,
		);
		$aUI->display('development-toolkit:platformpackage/setupCheckEnv.php',$arrVariables,$aCheckEnvBuffer);
		
		// licence
		$aLicenceBuffer = new OutputStreamBuffer ;
		$arrVariables = array(
			'licenceList' => array(
				// array(
				//     'title' =>
				//     'extname' =>
				//     'extversion' =>
				//     'licencename' =>
				//     'licencecontent' =>
				// )
			) ,
		);
		
		function generateLicence(array &$arrLicenceList , array $arrExtInfo){
			$aExtFolder = FileSystem::singleton()->findFolder($arrExtInfo['installPath']) ;
			$aLicenceFolder = $aExtFolder->findFolder('licence');
			if( null !== $aLicenceFolder ){
				$aLicenceIterator = $aLicenceFolder->iterator( FSIterator::CONTAIN_FILE | FSIterator::RETURN_FSO ) ;
				foreach($aLicenceIterator as $aFSO){
					$arrLicence = array(
						'title' => $arrExtInfo['title'] ,
						'extname' => $arrExtInfo['extname'] ,
						'extversion' => $arrExtInfo['extversion'] ,
						'licencename' => $aFSO->name() ,
						'licencereader' => $aFSO->openReader(),
					);
					$arrLicenceList [] = $arrLicence ;
				}
			}
		}
		// ext
		foreach($this->arrExtension as $sExtName => $aExtMetainfo){
			$sInstallPath = $aExtMetainfo->installPath() ;
			$arrExtInfo = array(
				'title' => $aExtMetainfo->title() ,
				'extname' => $aExtMetainfo->name() ,
				'extversion' => $aExtMetainfo->version() ,
				'installPath' => $sInstallPath ,
			);
			generateLicence( $arrVariables['licenceList'] , $arrExtInfo );
		}
		
		// framework
		$arrExtInfo = array(
			'title' => 'JeCat框架' ,
			'extname' => 'framework' ,
			'extversion' => Version::FromString(\org\jecat\framework\VERSION) ,
			'installPath' => '/framework' ,
		);
		generateLicence( $arrVariables['licenceList'] , $arrExtInfo );
		
		// platform
		$arrExtInfo = array(
			'title' => '蜂巢平台' ,
			'extname' => 'platform' ,
			'extversion' => Platform::singleton()->version() ,
			'installPath' => '/' ,
		);
		generateLicence( $arrVariables['licenceList'] , $arrExtInfo );
		
		$aUI->display('development-toolkit:platformpackage/setupLicence.php',$arrVariables,$aLicenceBuffer);
		
		// input
		$aInputBuffer = new OutputStreamBuffer ;
		$arrVariables = array(
		);
		$aUI->display('development-toolkit:platformpackage/setupInput.php',$arrVariables,$aInputBuffer);
		
		// install
		$sZipKey = md5(date("Y-m-d_G-i-s"));
		$aInstallBuffer = new OutputStreamBuffer ;
		$arrVariables = array(
			'sZipKey' => $sZipKey ,
		);
		$aUI->display('development-toolkit:platformpackage/setupInstall.php',$arrVariables,$aInstallBuffer);
		
		// main
		$aMainBuffer = new OutputStreamBuffer;
		
		$arrVariables = array(
			'code_checkEnv' => $aCheckEnvBuffer->__toString(),
			'code_licence' => $aLicenceBuffer->__toString(),
			'code_input' => $aInputBuffer->__toString(),
			'code_install' => $aInstallBuffer->__toString(),
			'sZipKey' => $sZipKey ,
			'arrZips' => $arrZipFile,
		);
		$aUI->display('development-toolkit:platformpackage/setup-main.php',$arrVariables,$aMainBuffer);
		
		// frame
		$arrVariables = array(
			'main_code' => $aMainBuffer->__toString(),
		);
		$aUI->display('development-toolkit:platformpackage/setup-frame.php',$arrVariables,$aDevice);
		
		
		$variables = array(
			
			'sZipKey' => $sZipKey ,
			'arrZips' => $arrZipFile,
		);
		
	}
	
	private function calcDependence(array $arrExtName){
		if(empty($this->arrDependence)){
			$this->arrDependence =
				array(
					'language' => array(
						'php'=>array(
						),
					),
					'language_module' => array(
					),
					'framework' => array(
						'' => array(
						),
					),
					'platform' => array(
						'' => array(
						),
					),
					'extension' => array(
					),
				);
		}
		$arrRequireExtension = array();
		foreach($arrExtName as $sExtensionName){
			$aExtension = ExtensionManager::singleton()->extensionMetainfo($sExtensionName);
			$this->arrExtension[$sExtensionName] = $aExtension ;
			if($aExtension){
				$aDependence = $aExtension->dependence();
				foreach($aDependence->iterator() as $aRequireItem){
					$this->arrDependence [$aRequireItem->type()][$aRequireItem->itemName()][] = $aRequireItem->versionScope()->toString(true) ;
					if($aRequireItem->type() === RequireItem::TYPE_EXTENSION){
						$sExtName = $aRequireItem->itemName() ;
						if(! isset($this->arrExtension[$sExtName]) ){
							$aExtMetainfo = ExtensionManager::singleton()->extensionMetainfo($sExtName) ;
							$this->arrExtension[$sExtName] = $aExtMetainfo ;
							$arrRequireExtension[] = $sExtName ;
						}
					}
				}
			}
		}
		if(!empty($arrRequireExtension)){
			$this->calcDependence($arrRequireExtension);
		}
	}
	
	private function packagePublicSetup(){
		$sName = 'public' ;
		$git = 0 ;
		// file list and folder list
		$aPlatform = Platform::singleton();
		$arrFileList = array();
		foreach($aPlatform->publicFolders()->folderIterator('development-toolkit.oc.setup') as $aFolder){
			$aFSIterator = $aFolder->iterator(FSIterator::CONTAIN_FILE | FSIterator::CONTAIN_FOLDER | FSIterator::RETURN_FSO | FSIterator::RECURSIVE_SEARCH );
			foreach($aFSIterator as $aFSO){
				$sRelativePath = $aFSIterator->relativePath();
				$arrFileList[$sRelativePath] = $aFSO ;
			}
		}
		
		// create zip
		$aZipFile = ExtensionPackages::getPackagedFSO( $sName , 'noversion' ,$git ) ;
		$aZip = $this->createZip($aZipFile);
		
		// zip 
		foreach($arrFileList as $sInZipPath => $aFSO){
			if( $aFSO instanceof IFolder ){
				$bR = $aZip->addEmptyDir($sInZipPath);
				if($bR === false){
					echo $aZip->getStatusString();
				}
			}else{
				$sLocalPath = $aFSO->url(false);
				$bR = $aZip->addFile($sLocalPath , $sInZipPath);
				if($bR === false){
					echo $aZip->getStatusString();
				}
			}
		}
		$aZip->close();
		
		$arr = array(
			'name' => $sName ,
			'path' => $sName ,
			'reader' => $aZipFile->openReader() ,
			'localpath' => $aZipFile->path(),
			'git' => $git ,
		);
		return $arr ;
	}
	
	private $arrDependence = null ;
	private $arrExtension = array();
}
