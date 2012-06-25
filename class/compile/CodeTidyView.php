<?php
namespace org\opencomb\development\toolkit\compile ;

use org\jecat\framework\lang\oop\Package;
use org\opencomb\platform\Platform;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\fs\FSIterator;
use org\jecat\framework\io\OutputStreamBuffer;
use org\jecat\framework\lang\oop\ClassLoader;
use org\opencomb\platform\ext\ExtensionManager;
use org\jecat\framework\message\Message;

class CodeTidyView extends ControlPanel{
	protected $arrConfig = array(
			'view' => array(
				'template' => 'compile/CodeTidyView.html' ,
			)
		);
	
	public function process(){
		$this->doActions() ;
		
		// packages
		$aClassLoader = ClassLoader::singleton ();
		$aPackageIterator = $aClassLoader->packageIterator(Package::nocompiled);
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
				$sNs = str_replace('\\','.',$arrPackage[1]);
				$sPackagePath = $aExtMeta->installPath().$arrPackage[0] ;
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
	
	public function form(){
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
			$aFile = Platform::singleton()->installFolder()->findFile($sPath);
			
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
			$this->view->createMessage(
				Message::success,
				'处理文件成功：`%s`',
				array(
					$sPath,
				)
			);
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
				$arrSub['path'] = Folder::relativePath(Platform::singleton()->installFolder(true),$aSubFso->path());
			}
			$arr[]=$arrSub;
		}
		return $arr ;
	}
}

