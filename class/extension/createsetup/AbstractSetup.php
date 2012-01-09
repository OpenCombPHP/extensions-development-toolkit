<?php
namespace org\opencomb\development\toolkit\extension\createsetup ;

use org\jecat\framework\db\DB ;
use org\jecat\framework\db\sql\StatementFactory ;
use org\opencomb\platform\ext\ExtensionMetainfo ;
use org\opencomb\platform\ext\Extension ;
use org\jecat\framework\fs\FileSystem ;

abstract class AbstractSetup implements ISetup{
	protected function setMetainfo(ExtensionMetainfo $aMetainfo){
		$this->aMetainfo = $aMetainfo ;
		$this->aExtension = new Extension($this->aMetainfo);
	}
	
	protected function executeSQL($strSQL){
		$aDB = DB::singleton();
		return $aDB->execute($strSQL);
	}
	
	protected function insertTableData($arrData , $tableName = ''){
		$aDB = DB::singleton() ;
		$aStatementFactory = StatementFactory::singleton() ;
		$aInsert = $aStatementFactory->createInsert($tableName) ;
		foreach($arrData as $row){
			$aInsert->clearData();
			foreach($row as $column => $value){
				$aInsert->setData($column,$value);
			}
			$aDB->execute($aInsert);
		}
	}
	
	protected function setting(){
		return $this->aExtension->setting();
	}
	
	protected function copyFolder($sfrom,$sto){
		return FileSystem::singleton()->copy($sfrom,$sto);
	}
	
	protected function getToFolder(){
		return $this->aExtension ->publicFolder()->path();
	}
	
	private $aMetainfo = null ;
	private $aExtension = null ;
}
