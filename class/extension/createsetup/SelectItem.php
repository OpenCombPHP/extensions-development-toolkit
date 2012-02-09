<?php
namespace org\opencomb\development\toolkit\extension\createsetup ;

use org\opencomb\coresystem\auth\Id;

use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\db\DB ;
use org\opencomb\platform\ext\Extension ;
use org\jecat\framework\fs\FileSystem ;

class SelectItem extends ControlPanel{
	public function createBeanConfig(){
		return array(
			'title'=>'选择安装程序内容',
			'view:selectItem' => array(
				'template' => 'SelectItem.html' ,
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
