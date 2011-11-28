<?php
namespace org\opencomb\development\toolkit\struct ;

use org\jecat\framework\mvc\controller\WebpageFrame;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class StructBrowser extends ControlPanel
{
	public function createBeanConfig()
	{
		return array(
			'view:browser'=>array(
				'template' => 'StructBrowser.html' ,
			) ,
		) ;
	}

	public function createFrame()
	{ return new WebpageFrame() ; }

	public function process()
	{}
}
