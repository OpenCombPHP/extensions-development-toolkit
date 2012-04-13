<?php
namespace org\opencomb\development\toolkit\platform\createpackage ;

use org\opencomb\coresystem\auth\Id;

use org\opencomb\coresystem\mvc\controller\ControlPanel ;
use org\opencomb\platform\ext\ExtensionManager ;
use org\opencomb\platform\ext\Extension ;
use org\jecat\framework\fs\Folder ;
use org\opencomb\development\toolkit\extension\ExtensionPackages ;

class SelectItem extends ControlPanel
{
	public function createBeanConfig(){
		return array(
			'title'=>'',
			'view:view' => array(
				'template' => 'platformpackage/SelectItem.html' ,
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
				if( $aPackage=ExtensionPackages::getPackagedFSO($sExtName,$sExtVersion,$i) and $aPackage->exists() )
				{
					$arrPackageState[$sExtName][$i] = $aPackage->path() ;
				}
				else
				{
					$arrPackageState[$sExtName][$i] = null ;
				}
			}
		}
		return $arrPackageState ;
	}
}
