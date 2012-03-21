<?php
namespace org\opencomb\development\toolkit\aspect ;

use org\jecat\framework\lang\Object;
use org\jecat\framework\lang\aop\jointpoint\JointPointMethodDefine;

class SysteExecuteTimeLog extends Object
{ 
	/**
	 * @advice around 
	 * @use org\opencomb\platform\debug\ExecuteTimeWatcher
	 */
	private function executeTimeLogger()
	{
		$sObjId = spl_object_hash($this) ;
		
		$aCallState = aop_calling_state() ;
		
		// 记录 controller 初始化的时间
		if( $aCallState->originMethod() == 'org\\jecat\\framework\\mvc\\controller\\Controller->__construct()' )
		{
			$this->setName($sName) ;
			$sName = $this->name() ;
			$aExecuteTimeWatcher = \org\opencomb\platform\debug\ExecuteTimeWatcher::singleton() ;
			
			$aExecuteTimeWatcher->start("/controller/{$sName}/{$sObjId}/initialize") ;
			
			aop_call_origin($params,$sName,$bBuildAtonce) ;
			
			$aExecuteTimeWatcher->finish("/controller/{$sName}/{$sObjId}/initialize") ;

			return  ;
		}
		else if( $aCallState->sOriginMethod == 'process' )
		{
			$sName = $this->name() ;
			$aExecuteTimeWatcher = \org\opencomb\platform\debug\ExecuteTimeWatcher::singleton() ;
			
			$aExecuteTimeWatcher->start("/controller/{$sName}/{$sObjId}/process") ;
			
			aop_call_origin() ;
			
			$aExecuteTimeWatcher->finish("/controller/{$sName}/{$sObjId}/process") ;
			
			return ;			
		}
	}
}

?>