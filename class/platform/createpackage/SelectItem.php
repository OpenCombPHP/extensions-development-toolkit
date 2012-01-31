<?php
namespace org\opencomb\development\toolkit\platform\createpackage ;

use org\opencomb\coresystem\mvc\controller\ControlPanel ;
use org\opencomb\platform\ext\ExtensionManager ;
use org\opencomb\platform\ext\Extension ;
use org\jecat\framework\fs\FileSystem ;
use org\opencomb\development\toolkit\extension\ExtensionPackages ;

class SelectItem extends ControlPanel
{
	public function createBeanConfig(){
		return array(
			'view:view' => array(
				'template' => 'platformpackage/SelectItem.html' ,
			)
		) ;
	}
	
	public function process(){
		// extension list
		$arrExtension = $this->getExtensionList() ;
		// package state
		$arrPackageState = $this->getExtensionPackageStateList();
		// template
		$this->view->variables()->set('arrExtension',$arrExtension);
		$this->view->variables()->set('arrPackageState',$arrPackageState);
	}
	
	private function getExtensionList(){
		return ExtensionManager::singleton()->metainfoIterator() ;
	}
	
	public function getExtensionPackageStateList(){
		$arrPackageState = array( );
		$aMetainfoIterator = ExtensionManager::singleton()->metainfoIterator() ;
		foreach($aMetainfoIterator as $aMetainfo){
			$sExtName = $aMetainfo->name();
			$sExtVersion = $aMetainfo->version();
			for($i=0;$i<=1;++$i){
				$arrPackageState[$sExtName][$i] = ExtensionPackages::hasPackaged($sExtName , $sExtVersion,$i) ;
			}
		}
		return $arrPackageState ;
	}
}
