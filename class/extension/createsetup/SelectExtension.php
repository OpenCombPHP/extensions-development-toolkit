<?php
namespace org\opencomb\development\toolkit\extension\createsetup ;

use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\opencomb\platform\ext\ExtensionManager ;

class SelectExtension extends ControlPanel{
	public function createBeanConfig(){
		return array(
			'view:selectExtension' => array(
				'template' => 'SelectExtension.html' ,
			),
		);
	}
	
	public function process(){
		$this->selectExtension->variables()->set('arrPackage',$this->getPackageList()) ;
	}
	
	public function getPackageList(){
		$arrPackageList = array();
		$aExtensionManager = ExtensionManager::singleton();
		foreach($aExtensionManager->metainfoIterator() as $ext){
			$name = (string)($ext->name());
			$arrPackageList[$name] =
				array(
					'name' => $ext->name(),
					'title' => $ext->title(),
					'version' => $ext->version(),
					'installPath' => $ext->installPath(),
				);
		}
		return $arrPackageList ;
	}
}
