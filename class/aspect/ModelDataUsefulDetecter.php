<?php
namespace org\opencomb\development\toolkit\aspect ;

use org\jecat\framework\lang\aop\jointpoint\JointPointMethodDefine;

class ModelDataUsefulDetecter
{
	/**
	 * @pointcut
	 */
	public function pointcutModelData()
	{
		return array(
			new JointPointMethodDefine('org\\jecat\\framework\\mvc\\model\\db\\Model','data') ,
		) ;
	}
	
	/**
	 * 在 Model 中记录被访问过的data
	 * 
	 * @advice before
	 * @for pointcutModelData
	 */
	private function data($sName)
	{
		$sKey = Recordset\KEY_MARK_CHAR . '__used_datas' ;
		if( !isset($this->arrDataSheet[$this->nDataRow][$sKey][$this->prototype()->path()]) )
		{
			$this->arrDataSheet[$this->nDataRow][$sKey][$this->prototype()->path()] = array() ;
		}
		if( !in_array($sName,$this->arrDataSheet[$this->nDataRow][$sKey][$this->prototype()->path()]) )
		{
			$this->arrDataSheet[$this->nDataRow][$sKey][$this->prototype()->path()][] = $sName ;
		}
		
		
	}

}

?>