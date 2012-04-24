<?php
namespace org\opencomb\development\toolkit\extension\createsetup ;

use org\opencomb\coresystem\auth\Id;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\opencomb\platform\ext\ExtensionManager;

class SelectExtension extends ControlPanel{
	public function createBeanConfig(){
		return array(
			'title'=>'选择扩展',
			'view:selectExtension' => array(
				'template' => 'SelectExtension.html' ,
			),
			'perms' => array(
				// 权限类型的许可
				'perm.purview'=>array(
					'namespace'=>'coresystem',
					'name' => Id::PLATFORM_ADMIN,
				) ,
			) ,
		) ;
	}

	public function process()
	{
		$this->checkPermissions('您没有使用这个功能的权限,无法继续浏览',array()) ;
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

