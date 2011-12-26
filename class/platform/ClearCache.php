<?php
namespace org\opencomb\development\toolkit\platform ;

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
		$this->viewForm->variables()->set('classJson',json_encode( $this->getNamespaceTree(ClassLoader::singleton()->packageIterator()) )) ;
	}
	
	
	private function getNamespaceTree($aPackageIterator){
		$arrTree =  array('name'=>"",'childs'=>array());
		foreach($aPackageIterator as $package){
			$ns = $package->ns();
			$arrNs = explode('\\',$ns);
			$arrExp = &$arrTree;
			$this->getEmptyTree(&$arrNs,&$arrExp);
// 			foreach($arrNs as $ns_cl){
// 				if(empty($arrExp)){
// 					$arrExp['name'] = $ns_cl;
// 				}else{
// 					$arrExp['childs']['name'] = $ns_cl;
// 				}
// 				$arrExp = &$arrExp;
// 			}
			$arrExp['namespace'] = $ns;
			$aFolder = $package->folder();
			$this->getFileTree($aFolder->url(false),$arrExp,$aFolder->path());
		}
		return $arrTree;
	}
	
	private function getEmptyTree($arrNs,$arrExp){
		if($sNs = array_shift($arrNs)){
			$arrExp['childs'][]['name'] = $sNs;
			$arrExp['childs'][]['clilds'] = array();
			$this->getEmptyTree(&$arrNs,&$arrExp);
		}else{
			return;
		}
	}
	
	private function getFileTree($pathname , &$arr , $path){
		$aDirectoryIterator = new \DirectoryIterator($pathname);
		foreach($aDirectoryIterator as $fileinfo){
			if($fileinfo->isDot()) continue;
				
			$arrChild = array();
			if($fileinfo->isDir()){
				$this->getFileTree($fileinfo->getPathname(),$arrChild,$path.'/'.$fileinfo->getFilename());
			}else{
				$arrChild['name'] = $fileinfo->getFileName();
				$arrChild['path'] = $path.'/'.$fileinfo->getFilename();
// 				$arrChild['fileinfo'] = $fileinfo;
			}
			$arr['childs'][] = $arrChild;
		}
	}

	
// 	private function getNamespaceTree($aPackageIterator){
// 		$arrTree =  array();
// 		foreach($aPackageIterator as $package){
// 			$ns = $package->ns();
// 			$arrNs = explode('\\',$ns);
// 			$arrExp = &$arrTree;
// 			foreach($arrNs as $ns_cl){
// 				if(empty($arrExp[$ns_cl])){
// 					$arrExp[$ns_cl] = array();
// 				}
// 				$arrExp = &$arrExp[$ns_cl];
// 			}
// 			$aFolder = $package->folder();
// 			$this->getFileTree($aFolder->url(false),$arrExp,$aFolder->path());
// 			$arrExp[] = $ns;
// 		}
// 		return $arrTree;
// 	}
	
// 	private function getFileTree($pathname , &$arr,$path){
// 		$aDirectoryIterator = new \DirectoryIterator($pathname);
// 		foreach($aDirectoryIterator as $fileinfo){
// 			if($fileinfo->isDot()) continue;
				
// 			$arrChild = array();
// 			if($fileinfo->isDir()){
// 				$this->getFileTree($fileinfo->getPathname(),$arrChild,$path.'/'.$fileinfo->getFilename());
// 			}else{
// 				$arrChild['ns'] = '';
// 				$arrChild['path'] = $path.'/'.$fileinfo->getFilename();
// 				$arrChild['fileinfo'] = $fileinfo;
// 			}
// 			$arr[$fileinfo->getFileName()] = $arrChild;
// 		}
// 	}
}