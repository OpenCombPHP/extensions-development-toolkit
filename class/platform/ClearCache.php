<?php
namespace org\opencomb\development\toolkit\platform ;

use org\jecat\framework\fs\FSIterator;

use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\fs\FileSystem;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\message\Message;
use org\jecat\framework\system\Application;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class ClearCache extends ControlPanel
{

	public function createBeanConfig()
	{
		return array(
			'view:form' => array(
				'template' => 'ClearCache.html' ,
			)		
		) ;
	}

	public function process()
	{
		if( $this->params->has('clear_system_cache') )
		{
			if( FileSystem::singleton()->delete('/data/cache/platform/system/objects',true,true) )
			{
				$this->form->createMessage(Message::success,'系统缓存 已经被清除') ;				
			}
			else
			{
				$this->form->createMessage(Message::failed,'系统缓存失败') ;
			}
		}
		
		if( $this->params->has('clear_class_compiled') )
		{
			if( FileSystem::singleton()->delete('/data/compiled/class',true,true) )
			{
				$this->form->createMessage(Message::success,'类编译缓存 已经被清除') ;
			}
			else
			{
				$this->form->createMessage(Message::failed,'清除类编译缓存失败') ;
			}	
		}
		
		if( $this->params->has('clear_template_compiled') )
		{
			if( FileSystem::singleton()->delete('/data/compiled/template',true,true) )
			{
				$this->form->createMessage(Message::success,'模板编译缓存 已经被清除') ;	
			}
			else
			{
				$this->form->createMessage(Message::failed,'清除模板编译缓存失败') ;
			}
		}
		//ajax的清理请求
		if( $this->params->has('deletePaths') )
		{
			$sMessage = '成功清理以下缓存文件 : <br/>';
			if($dataFolder = FileSystem::singleton()->findFolder('/data/compiled/class/')){
				foreach($dataFolder->iterator(FSIterator::FOLDER | FSIterator::RETURN_FSO) as $aFolder){
					foreach($this->params->get('deletePaths') as $sPath){
						$aFolder->deleteChild( $sPath ,true,true);
						$sMessage .=  $sPath . "<br/>";
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
						$arrExp = &$arrExp[$i]['childs'];
						$bFound = true;
						break;
					}
				}
				if(!$bFound){
					$arrExp[] = array('name'=>$ns_cl , 'childs'=>array());
					$arrExp = &$arrExp[count($arrExp)-1]['childs'];
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
				$arrNode[]['childs'] = $this->buildNode( $fileinfo->getPathname() , $aFolderPath.'/'.$fileinfo->getFilename() );
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