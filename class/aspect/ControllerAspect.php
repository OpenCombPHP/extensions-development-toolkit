<?php
namespace org\opencomb\development\toolkit\aspect ;

use org\jecat\framework\mvc\model\IModel;
use org\jecat\framework\mvc\controller\IController;
use org\jecat\framework\pattern\composite\IContainer;
use org\jecat\framework\mvc\view\IView;
use org\jecat\framework\mvc\controller\Controller;
use org\jecat\framework\lang\aop\jointpoint\JointPointMethodDefine;

class ControllerAspect/* extends Controller*/
{
	/**
	 * @pointcut
	 */
	public function pointcutMainRun()
	{
		return array(
			new JointPointMethodDefine('org\\jecat\\framework\\mvc\\controller\\Controller','mainRun') ,
		) ;
	}
	
	/**
	 * @advice after
	 * @for pointcutMainRun
	 */
	public function reflectMvc()
	{
		if(!$this->params->bool('toolkit_mvcbrowser'))
		{
			return ;
		}
		
		$sJsCode = "\r\n<script>\r\n" ;
		$sJsCode.= "if( parent && typeof(parent.structBrowser)!='undefined' ){\r\n" ;
		$sJsCode.= "	var _mvcstruct = " . \org\opencomb\development\toolkit\aspect\ControllerAspect::generateControllerStructJcCode($this) . "; \r\n" ;
		$sJsCode.= "	parent.structBrowser.setMvcStruct(_mvcstruct) ;\r\n" ;
		$sJsCode.= "}\r\n" ;
		$sJsCode.= "</script>\r\n" ;
		
		echo $sJsCode ;
	}
	
	static public function generateModelStructJcCode(IModel $aModel,$sName,$nIndent=0)
	{
		$sIndent = str_repeat("\t",$nIndent) ;
		$sNameEsc = addslashes($sName) ;
		
		$sJsCode = "{\r\n" ;
		$sJsCode.= $sIndent."	name:\"{$sNameEsc}\"\r\n" ;
		
		$sJsCode.= $sIndent."	, models: [ " ;
		foreach($aModel->childNameIterator() as $idx=>$sModelName)
		{
			if($idx)
			{
				$sJsCode.= "\r\n{$sIndent}	, " ;
			}
			$sJsCode.= \org\opencomb\development\toolkit\aspect\ControllerAspect::generateModelStructJcCode($aModel->child($sModelName),$sModelName,$sIndent+1) ;
		}
		$sJsCode.= $sIndent." ]\r\n" ;
		
		$sJsCode.= $sIndent."}" ;
		
		return $sJsCode ;
	}
	
	static public function generateViewStructJcCode(IView $aView,$sName,$nIndent=0)
	{
		$sIndent = str_repeat("\t",$nIndent) ;
		
		$sViewNameEsc = addslashes($sName) ;
		$sTemplateEsc = addslashes($aView->template()) ;
		
		$sJsCode = "{\r\n" ;
		$sJsCode.= "{$sIndent}	name:\"{$sViewNameEsc}\"\r\n" ;
		$sJsCode.= "{$sIndent}	, template:\"{$sTemplateEsc}\"\r\n" ;
		
		$sJsCode.= "{$sIndent}	, views:[" ;
		foreach($aView->nameIterator() as $idx=>$sChildViewName)
		{
			if($idx)
			{
				$sJsCode.= "\r\n{$sIndent}	, " ;
			}
			$sJsCode.= self::generateViewStructJcCode($aView->getByName($sChildViewName),$sChildViewName,$nIndent+1) ;
		}
		$sJsCode.= "]\r\n" ;
		
		$sJsCode.= $sIndent."}" ;
		
		return $sJsCode ;
	}
	
	static public function generateControllerStructJcCode(IController $aController,$sName=null,$nIndent=0)
	{
		$sClass = get_class($aController) ;
		if(!$sName)
		{
			$sName = $aController->name()?: $sClass ;
		}
		$sNameEsc = addslashes($sName) ;
		$sIndent = str_repeat("\t",$nIndent) ;
		
		$sJsCode = "{\r\n" ;
		$sJsCode.= $sIndent."	name: \"{$sNameEsc}\"\r\n" ;
		$sJsCode.= $sIndent."	, class: \"{$sClass}\"\r\n" ;
		
		// models
		$sJsCode.= $sIndent."	, models: [ " ;
		foreach($aController->modelNameIterator() as $idx=>$sModelName)
		{
			if($idx)
			{
				$sJsCode.= "\r\n{$sIndent}	, " ;
			}
			$sJsCode.= \org\opencomb\development\toolkit\aspect\ControllerAspect::generateModelStructJcCode($aController->modelByName($sModelName),$sModelName,$sIndent+1) ;
		}
		$sJsCode.= $sIndent." ]\r\n" ;
		
		// views
		$sJsCode.= $sIndent."	, views: [ " ;
		foreach($aController->mainView()->nameIterator() as $idx=>$sViewName)
		{
			if($idx)
			{
				$sJsCode.= "\r\n{$sIndent}	, " ;
			}
			$sJsCode.= \org\opencomb\development\toolkit\aspect\ControllerAspect::generateViewStructJcCode($aController->mainView()->getByName($sViewName),$sViewName,$sIndent+1) ;
		}
		$sJsCode.= $sIndent." ]\r\n" ;
		
		// controllers
		$sJsCode.= $sIndent."	, controller: [ " ;
		foreach($aController->nameIterator() as $idx=>$sChildControllerName)
		{
			if($idx)
			{
				$sJsCode.= "\r\n{$sIndent}	, " ;
			}
			$sJsCode.= \org\opencomb\development\toolkit\aspect\ControllerAspect::generateControllerStructJcCode($aController->getByName($sChildControllerName),$sChildControllerName,$sIndent+1) ;
		}
		$sJsCode.= $sIndent." ]\r\n" ;
		
		$sJsCode.= $sIndent."}" ;
		
		return $sJsCode ;
	}
}

?>