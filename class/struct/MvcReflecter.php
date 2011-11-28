<?php
namespace org\opencomb\development\toolkit\struct ;

use org\jecat\framework\mvc\controller\IController;

class MvcReflecter
{
	public function __construct(IController $aController)
	{
		$this->aStartController = $aController ;
	}
	
	
	
	private $aStartController ;
}

?>