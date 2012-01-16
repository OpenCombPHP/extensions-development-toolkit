<?php
namespace org\opencomb\development\toolkit\platform\createpackage ;

use org\opencomb\coresystem\mvc\controller\ControlPanel ;
use org\opencomb\platform\ext\ExtensionManager ;
use org\jecat\framework\util\Version ;
use org\opencomb\platform\Platform ;
use org\opencomb\platform\ext\dependence\RequireItem ;
use org\jecat\framework\lang\Exception ;

class ShowVersion extends ControlPanel
{
	public function createBeanConfig(){
		return array(
			'view:view' => array(
				'template' => 'platformpackage/ShowVersion.html' ,
			)
		) ;
	}
	
	public function process(){
		// input
		$arrExtName = $this->params['ext'];
		$arrContainGit = array();
		if(!empty($this->params['gitframework'])){
			$arrContainGit['framework'] = $this->params['gitframework'];
		}
		if(!empty($this->params['gitplatform'])){
			$arrContainGit['platform'] = $this->params['gitplatform'];
		}
		// version list
		$arrVersion = $this->getPhpVersionList();
		$arrVersion['jecat'] = $this->getJeCatVersion();
		$arrVersion['opencomb'] = $this->getOpenCombVersion();
		// extension
		$this->arrExtension = array();
		foreach($arrExtName as $extName){
			$aMetainfo = $this->getExtensionMetainfo($extName);
			$this->arrExtension[$extName] = $aMetainfo;
			$arrVersion[$extName] = $aMetainfo->version()->toString();
		}
		$this->getDependenceList($arrExtName);
		// package state
		$aSelectItem = new SelectItem;
		$arrPackageState = $aSelectItem->getExtensionPackageStateList();
		// template
		$this->view->variables()->set('arrExtension',$this->arrExtension);
		$this->view->variables()->set('arrDependence',$this->arrDependence);
		$this->view->variables()->set('version',$arrVersion);
		$this->view->variables()->set('arrPackageState',$arrPackageState);
		$this->view->variables()->set('arrContainGit',$arrContainGit);
	}
	
	private function getExtensionMetainfo($extName){
		if( !is_string($extName) ){
			throw new Exception('extName is not string');
		}
		return ExtensionManager::singleton()->extensionMetainfo($extName) ;
	}
	
	private function getDependenceList(array $arrExtensionName){
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
		foreach($arrExtensionName as $sExtensionName){
			$aExtension = $this->getExtensionMetainfo($sExtensionName);
			if($aExtension){
				$aDependence = $aExtension->dependence();
				foreach($aDependence->iterator() as $aRequireItem){
					$this->arrDependence [$aRequireItem->type()][$aRequireItem->itemName()][] = $aRequireItem->versionScope() ;
					if($aRequireItem->type() === RequireItem::TYPE_EXTENSION){
						$sExtName = $aRequireItem->itemName() ;
						if(! isset($this->arrExtension[$sExtName]) ){
							$aExtMetainfo = $this->getExtensionMetainfo($sExtName) ;
							$this->arrExtension[$sExtName] = $aExtMetainfo ;
							$arrRequireExtension[] = $sExtName ;
						}
					}
				}
			}
		}
		if(!empty($arrRequireExtension)){
			$this->getDependenceList($arrRequireExtension);
		}
	}
	
	private function getPhpVersionList(){
		$arr = array();
		$arr['php'] = phpversion();
		foreach (get_loaded_extensions() as $i => $ext) 
		{
			$arr[$ext] = phpversion($ext) ;
		}
		return $arr ;
	}
	
	private function getJeCatVersion(){
		return Version::FromString(\org\jecat\framework\VERSION)->toString() ;
	}
	
	private function getOpenCombVersion(){
		return Platform::singleton()->version()->toString();
	}
	
	private $arrExtension = array();
	private $arrDependence = null ;
}
