<?php
namespace org\opencomb\development\toolkit\platform\createpackage ;

use org\opencomb\coresystem\mvc\controller\ControlPanel ;
use org\opencomb\platform\ext\Extension ;
use org\opencomb\platform\ext\ExtensionManager ;
use org\opencomb\platform\ext\dependence\RequireItem ;
use org\jecat\framework\fs\IFile ;
use org\jecat\framework\fs\IFolder ;
use org\jecat\framework\fs\FileSystem ;
use org\jecat\framework\fs\FSIterator ;
use org\jecat\framework\io\IOutputStream ;
use org\jecat\framework\ui\xhtml\UIFactory ;

class CreatePackage extends ControlPanel
{
	public function createBeanConfig(){
		return array(
			'view:view' => array(
				'template' => 'platformpackage/CreatePackage.html' ,
			)
		) ;
	}
	
	public function process(){
		// input
		$arrExtName = $this->params['ext'];
		$git = array() ;
		$git['framework'] = $this->params['gitframework'] ;
		$git['platform'] = $this->params['gitplatform'] ;
		// stamp
		$sStamp = date("Y-m-d_G-i-s") ;
		// create zip
		$aZipFileFramework =
			Extension::flyweight('development-toolkit')
				->publicFolder()
					->findFile('framework_'.$sStamp.'.zip',FileSystem::FIND_AUTO_CREATE_OBJECT);
		$aZipFramework = $this->createZip($aZipFileFramework);
		$aZipFilePlatform =
			Extension::flyweight('development-toolkit')
				->publicFolder()
					->findFile('platform_'.$sStamp.'.zip',FileSystem::FIND_AUTO_CREATE_OBJECT);
		$aZipPlatform = $this->createZip($aZipFilePlatform);
		// package framework
		$this->packageFramework($aZipFramework , $git['framework'] );
		// package platform
		$this->packagePlatform($aZipPlatform , $git['platform'] );
		// close
		$bCloseFramework = $aZipFramework->close();
		$bClosePlatform = $aZipPlatform->close();
		// file name
		$sFrameworkFileName = $aZipFileFramework -> path() ;
		$sPlatformFileName = $aZipFilePlatform -> path() ;
		$this->view->variables()->set('sFrameworkFileName',$sFrameworkFileName);
		$this->view->variables()->set('sPlatformFileName',$sPlatformFileName);
		// test data
		$this->testData($aZipFileFramework);
		// dependence
		$this->calcDependence($arrExtName);
		// create setup
		$aWriter = $this->createWriter();
		$this->createSetup($aWriter);
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
		return $this->package($aZip,$aFolder,'framework',$sExcludePattern);
	}
	
	private function packagePlatform(\ZIPARCHIVE $aZip , $git ){
		$aFolder = FileSystem::singleton()->findFolder('/');
		$sExcludePattern = '';
		if(empty($git)){
			$sExcludePattern = '`^(\\.(git|svn|cvs)|(framework|data|extensions|settings|.settings)(/|$))`' ;
		}else{
			$sExcludePattern = '`^(framework|data|extensions|settings|.settings)(/|$)`' ;
		}
		return $this->package($aZip,$aFolder,'platform',$sExcludePattern);
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
	
	private function createSetup(IOutputStream $aDevice){
		$aUI = UIFactory::singleton()->create();
		$variables = array(
			'arrDependence' => $this->arrDependence,
		);
		$aUI->display('development-toolkit:platformpackage/setup.php',$variables,$aDevice);
	}
	
	private function testData(IFile $aFile){
		$aReader = $aFile->openReader();
		$str = $aReader->read();
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
	
	private $arrDependence = null ;
	private $arrExtension = array();
}
