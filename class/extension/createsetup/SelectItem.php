<?php
namespace org\opencomb\development\toolkit\extension\createsetup ;

use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\db\DB ;
use org\opencomb\platform\ext\Extension ;
use org\jecat\framework\fs\FileSystem ;

class SelectItem extends ControlPanel{
	public function createBeanConfig(){
		return array(
			'view:selectItem' => array(
				'template' => 'SelectItem.html' ,
			),
		);
	}
	
	public function process(){
		// input
		$extName = $this->params['extName'] ;
		// calc
		$tableList = $this->getExtDBTableList($extName,'') ;
		$aExtension = Extension::flyweight($extName);
		$sDataFileFolder = $aExtension->metainfo()->installPath().'/data/public';
		$aDataFileFolderIterator = FileSystem::singleton()->findFolder($sDataFileFolder)->iterator();
		// set to template
		$this->selectItem->variables()->set('extName',$extName) ;
		$this->selectItem->variables()->set('tableList',$tableList);
		$this->selectItem->variables()->set('aDataFileFolderIterator',$aDataFileFolderIterator);
	}
	
	public function getExtDBTableList($extName , $prefix){
		$arrTableList = array();
		
		$aDB = DB::singleton() ;
		$aReflecterFactory = $aDB->reflecterFactory() ;
		$strDBName = $aDB->driver(true)->currentDBName();
		$aDbReflecter = $aReflecterFactory->dbReflecter($strDBName);
		$sKey = 'Tables_in_'.$strDBName ;
		foreach( $aDbReflecter->tableNameIterator() as $value ){
			$tableName = $value[$sKey] ;
			if( self::startsWith($tableName,$prefix.$extName.'_')){
				$arrTableList [] = $tableName;
			}
		}
		return $arrTableList ;
	}
	
	static private function startsWith($haystack, $needle){
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
}
