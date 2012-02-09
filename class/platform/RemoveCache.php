<?php
namespace org\opencomb\development\toolkit\platform ;

use org\opencomb\coresystem\auth\Id;

use org\jecat\framework\fs\FSIterator;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\fs\FileSystem;
use org\jecat\framework\setting\Setting;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class RemoveCache extends ControlPanel
{
	public function createBeanConfig()
	{
		return array(
			'title'=>'清理缓存',
			'view:form' => array(
				'template' => 'RemoveCache.html' ,
			),
			'perms' => array(
					// 权限类型的许可
					'perm.purview'=>array(
							'namespace' => 'coresystem' ,
							'name' => Id::PLATFORM_ADMIN,
					) ,
			) ,
		) ;
	}

	public function process()
	{
		$this->checkPermissions('您没有使用这个功能的权限,无法继续浏览',array()) ;
		//ajax的清理请求
		if( $this->params->has('deletePaths') )
		{
			$sMessage = '成功清理以下缓存文件 : <br/>';
			if($dataFolder = FileSystem::singleton()->findFolder('/data/compiled/class')){
				foreach($dataFolder->iterator(FSIterator::FOLDER | FSIterator::RETURN_FSO) as $aFolder){
					foreach($this->params->get('deletePaths') as $sPath){
						$aFolder->deleteChild( $sPath ,true,true);
						$sMessage .=  $aFolder->localPath() . $sPath . "<br/>";
					}
				}
			}
			exit($sMessage);
		}
		$this->viewForm->variables()->set('classJson',json_encode( $this->getTree(ClassLoader::singleton()->packageIterator()) )) ;
	}
	
	private function getTree($aPackageIterator){
		$arrTree =  array();
		foreach($aPackageIterator as $package){
			$ns = $package->ns();
			$arrNs = explode('\\',$ns);
			$arrExp = &$arrTree;
			foreach($arrNs as $ns_cl){
				$bFound= false;
				for($i = 0; $i < count($arrExp) ;$i++){
					if( isset($arrExp[$i]['name']) && $arrExp[$i]['name'] == $ns_cl){
						$arrExp = &$arrExp[$i]['children'];
						$bFound = true;
						break;
					}
				}
				if(!$bFound){
					$arrExp[] = array('name'=>$ns_cl , 'children'=>array());
					$arrExp = &$arrExp[count($arrExp)-1]['children'];
				}
			}
			$aFolder = $package->folder();
			$arrExp = $this->buildNode($aFolder->url(false),$aFolder->path());
		}
		return $arrTree;
	}
	
	private function buildNode($sFolderUrl,$aFolderPath){
		$arrNode = array();
		$aDirectoryIterator = new \DirectoryIterator($sFolderUrl);
		foreach($aDirectoryIterator as $fileinfo){
			if($fileinfo->isDot()){
				continue;
			}
			if($fileinfo->isDir()){
				$arrNode[]['children'] = $this->buildNode( $fileinfo->getPathname() , $aFolderPath.'/'.$fileinfo->getFilename() );
				$arrNode[count($arrNode)-1]['name'] = $fileinfo->getFilename();
			}else{
				$arrNode[] = array(
					'name' => substr($fileinfo->getFilename() , 0 ,strlen($fileinfo->getFilename())-4) ,
					'filename' => $fileinfo->getFilename()
				) ;
			}
		}
		return $arrNode;
	}
}