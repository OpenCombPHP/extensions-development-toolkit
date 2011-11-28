<?php
namespace org\opencomb\development\toolkit\aspect ;

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
	public function mainRun()
	{
		if(!$this->params->bool('toolkit_mvcbrowser'))
		{
			return ;
		}

		// models
		foreach($this->modelContainer()->nameIterator() as $sModelName)
		{
			echo $sModelName, "<br />" ;
		}
		
		// views
		foreach($this->mainView()->nameIterator() as $sViewName)
		{
			echo $sViewName, "<br />" ;
		}
		
		// controllers
		foreach($this->nameIterator() as $sChildControllerName)
		{
			echo $sChildControllerName, "<br />" ;
		}
	}
}

?>