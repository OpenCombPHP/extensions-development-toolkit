<?php
return array(
	'development' => array(
		'title' => '开发' ,
		'link' => '?c=org.opencomb.development.toolkit.platform.ClearCache' ,
		'menu' => array(
			'direction' => 'v' ,
			'items' => array(
					'extension' => array(
						'title' => '扩展' ,
						'link' => '?c=org.opencomb.development.toolkit.extension.ExtensionPackages' ,
						'menu' => array(
							'items' => array(
								'create-extensions' => array(
										'title'=>'创建扩展' ,
										'link' => '?c=org.opencomb.development.toolkit.extension.CreateExtension' ,
										'query' => 'c=org.opencomb.development.toolkit.extension.CreateExtension' ,
								) ,
								'extensionpackage' => array(
										'title'=>'扩展打包' ,
										'link' => '?c=org.opencomb.development.toolkit.extension.ExtensionPackages' ,
										'query' => 'c=org.opencomb.development.toolkit.extension.ExtensionPackages' ,
								) ,
								'createsetup' => array(
										'title'=>'生成setup' ,
										'link' => '?c=org.opencomb.development.toolkit.extension.createsetup.SelectExtension' ,
										'query' => array(
												'c=org.opencomb.development.toolkit.extension.createsetup.SelectExtension' ,
												'c=org.opencomb.development.toolkit.extension.createsetup.SelectItem' ,
												'c=org.opencomb.development.toolkit.extension.createsetup.CreateSetup',
											),
								) ,
							),
						),
					),
					'workspace' => array(
							'title'=>'工作台' ,
							'link' => '?c=org.opencomb.development.toolkit.platform.ClearCache' ,
							'query' => 'c=org.opencomb.development.toolkit.platform.ClearCache' ,
							'menu' => array(
								'items'=> array(
									'clear-cache' => array(
											'title'=>'清空缓存' ,
											'link' => '?c=org.opencomb.development.toolkit.platform.ClearCache' ,
											'query' => 'c=org.opencomb.development.toolkit.platform.ClearCache' ,
									) ,
									'aop-manager' => array(
											'title'=>'AOP管理' ,
											'link' => '?c=org.opencomb.development.toolkit.compile.AOPManager' ,
											'query' => 'c=org.opencomb.development.toolkit.compile.AOPManager' ,
									) ,
									'template-weave-manager' => array(
											'title'=>'模板编织管理' ,
											'link' => '?c=org.opencomb.development.toolkit.compile.AOPManager' ,
											'query' => 'c=org.opencomb.development.toolkit.compile.AOPManager' ,
									) ,
								) ,
							) ,
					) ,
			) ,
		) ,
	) ,
) ;
