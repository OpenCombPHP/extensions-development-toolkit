<?php
namespace org\opencomb\development\toolkit\compile ;

use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\fs\Folder ;
use org\jecat\framework\fs\FSIterator ;
use org\jecat\framework\io\OutputStreamBuffer ;
use org\jecat\framework\lang\oop\ClassLoader;
use org\opencomb\platform\ext\ExtensionManager;
use org\jecat\framework\message\Message;

class CodeTidyView extends ControlPanel{
	public function createBeanConfig(){
		return array(
			'view:view' => array(
				'template' => 'CodeTidyView.html' ,
			)
		);
	}
	
	public function process(){
		$this->doActions() ;
		
		// packages
		$aClassLoader = ClassLoader::singleton ();
		$aPackageIterator = $aClassLoader->packageIterator ();
		$arrNsFolder = array () ;
		foreach($aPackageIterator as $aPackage){
			$sNs = $aPackage->ns() ;
			$sNs = str_replace('\\','.',$sNs);
			$aFolder = $aPackage->folder() ;
			$arrNsFolder[$sNs] = $this->getTree($aFolder);
		}
		
		$this->view->variables()->set('arrNsFolder',$arrNsFolder);
		
		// extensions
		$arrExtPackageList = array() ;
		$aExtMetaIter = ExtensionManager::singleton()->enableExtensionMetainfoIterator() ;
		// $this->view->variables()->set('aExtMetaIter',$aExtMetaIter);
		foreach($aExtMetaIter as $aExtMeta){
			$sExtName = $aExtMeta->name() ;
			$arrExtPackageList[$sExtName] = array(
				'meta' => $aExtMeta,
			) ;
			
			$arrPackages = array() ;
			foreach($aExtMeta->packageIterator() as $arrPackage){
				$sNs = str_replace('\\','.',$arrPackage[0]);
				$sPackagePath = $aExtMeta->installPath().$arrPackage[1] ;
				$aFolder = Folder::singleton()->findFolder($sPackagePath);
				$arrPackages [] = array(
					'ns' => $sNs,
					'path'=>$sPackagePath,
				);
			}
			
			$arrExtPackageList[$sExtName] ['packages'] = $arrPackages ;
		}
		$this->view->variables()->set('arrExtPackageList',$arrExtPackageList);
	}
	
	public function actionTidy(){
		$arrConf = $this->params['arrConf'] ;
		if( !is_array($arrConf) ){
			$arrConf = array() ;
		}
		// var_dump($arrConf);
		$path = $this->params['paths'];
		$arrPath = explode(',',$path);
		// var_dump($arrPath);
		
		$aTidy = SourceCodeTidy::singleton();
		foreach($arrPath as $sPath){
			$aFile = Folder::singleton()->findFile($sPath);
			
			if(!$aFile){
				$this->view->createMessage(
					Message::failed,
					'找不到文件：`%s`',
					array(
						$sPath,
					)
				);
				continue ;
			}
			
			$aBuffer = new OutputStreamBuffer;
			$aTidy->tidy($aFile->openReader(),$aBuffer,$arrConf);
			
			@$aWriter = $aFile->openWriter();
			if( $aWriter === null){
				$this->view->createMessage(
					Message::failed,
					'无法打开文件：`%s`，请检查权限',
					array(
						$sPath,
					)
				);
				continue ;
			}
			$aWriter->write($aBuffer);
		}
	}
	
	private function getTree(Folder $aFolder){
		$arr = array() ;
		
		$aFolderIter = $aFolder->iterator( FSIterator::FILE_AND_FOLDER | FSIterator::RETURN_FSO);
		foreach($aFolderIter as $aSubFso){
			$arrSub = array(
				'name' => $aSubFso->name(),
			);
			if($aSubFso instanceof Folder){
				$arrSub['children']=$this->getTree($aSubFso);
			}else{
				$arrSub['path'] = Folder::relativePath(Folder::singleton(),$aSubFso->path() );
			}
			$arr[]=$arrSub;
		}
		return $arr ;
	}
}
