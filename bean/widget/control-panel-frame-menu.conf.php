<?php
return array(
	'development' => array(
		'title' => '开发' ,
		'link' => '?c=org.opencomb.development.toolkit.platform.ClearCache' ,
		'menu' => array(
			'direction' => 'v' ,
			'items' => array(
					'create-extensions' => array(
							'title'=>'创建扩展' ,
							'link' => '?c=org.opencomb.development.toolkit.extension.CreateExtension' ,
							'quote' => 'c=org.opencomb.development.toolkit.extension.CreateExtension' ,
					) ,
					'workspace' => array(
							'title'=>'工作台' ,
							'link' => '?c=org.opencomb.development.toolkit.platform.ClearCache' ,
							'quote' => 'c=org.opencomb.development.toolkit.platform.ClearCache' ,
							'menu' => array(
								'items'=> array(
									'clear-cache' => array(
											'title'=>'清空缓存' ,
											'link' => '?c=org.opencomb.development.toolkit.platform.ClearCache' ,
											'quote' => 'c=org.opencomb.development.toolkit.platform.ClearCache' ,
									) ,
									'aop-manager' => array(
											'title'=>'AOP管理' ,
											'link' => '?c=org.opencomb.development.toolkit.compile.AOPManager' ,
											'quote' => 'c=org.opencomb.development.toolkit.compile.AOPManager' ,
									) ,
									'template-weave-manager' => array(
											'title'=>'模板编织管理' ,
											'link' => '?c=org.opencomb.development.toolkit.compile.AOPManager' ,
											'quote' => 'c=org.opencomb.development.toolkit.compile.AOPManager' ,
									) ,
								) ,
							) ,
					) ,
			) ,
		) ,
	) ,
) ;