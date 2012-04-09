<?php
namespace org\opencomb\development\toolkit\aspect ;

use org\jecat\framework\lang\aop\jointpoint\JointPointMethodDefine;

class ModelDataUsefulDetecter
{
	/**
	 * 在 Model 中记录被访问过的data
	 * 
	 * @advice before
	 * @for pointcutModelData
	 */
	private function data($sName)
	{
		$aPrototype = $this->prototype() ;
		if( !in_array($sName,$aPrototype->columns()) )
		{
			return ;
		}
		
		$sKey = Recordset\KEY_MARK_CHAR . '__used_datas' ;
		$sModelPath = $aPrototype->path() ;
		if( !isset($this->arrDataSheet[$this->nDataRow][$sKey][$sModelPath]) )
		{
			$this->arrDataSheet[$this->nDataRow][$sKey][$sModelPath] = array() ;
		}
		if( !in_array($sName,$this->arrDataSheet[$this->nDataRow][$sKey][$sModelPath]) )
		{
			$this->arrDataSheet[$this->nDataRow][$sKey][$sModelPath][] = $sName ;
		}
	}
	
	/**
	 * 在 Model::printStruct() 时 打印被访问过的data
	 *
	 * @advice before
	 * @for pointcutModelPrintStructData
	 */
	protected function printStructData(IOutputStream $aOutput = null, $nDepth = 0)
	{///////////////////////////////////////////////////////
		$sKey = Recordset\KEY_MARK_CHAR . '__used_datas' ;
		
		$arrUsefulDatas = @$this->arrDataSheet[$this->nDataRow][$sKey][$this->prototype()->path()]?: array() ;
		$sUsefulDatas = count($arrUsefulDatas)? "'".implode("','",$arrUsefulDatas)."'": '' ;
		$aOutput->write ( str_repeat ( "\t", $nDepth+1 ) . "[useful datas] : 'columns' => array({$sUsefulDatas})\r\n" );
		
		$arrUnusefulDatas = array_diff($this->prototype()->columns(),$arrUsefulDatas) ;
		$sUnusefulDatas = $arrUnusefulDatas? "'".implode("','",$arrUnusefulDatas)."'": '' ;
		$aOutput->write ( str_repeat ( "\t", $nDepth+1 ) . "[unuseful datas] : 'columns' => array({$sUnusefulDatas})\r\n\r\n" );
	}
}

?>