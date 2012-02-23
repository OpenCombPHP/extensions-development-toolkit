<?php
return array(
	'item:development' => array(
		'title' => '开发' ,
		'link' => '?c=org.opencomb.development.toolkit.platform.ClearCache' ,
		
		// items
		'menu' => 1,
		'item:extension' => array(
			'title' => '扩展' ,
			'link' => '?c=org.opencomb.development.toolkit.extension.ExtensionPackages' ,
			
			// items
			'menu' => 1 ,
			'item:create-extensions' => array(
					'title'=>'创建扩展' ,
					'link' => '?c=org.opencomb.development.toolkit.extension.CreateExtension' ,
					'query' => 'c=org.opencomb.development.toolkit.extension.CreateExtension' ,
			) ,
			'item:extensionpackage' => array(
					'title'=>'扩展打包' ,
					'link' => '?c=org.opencomb.development.toolkit.extension.ExtensionPackages' ,
					'query' => 'c=org.opencomb.development.toolkit.extension.ExtensionPackages' ,
			) ,
			'item:createsetup' => array(
					'title'=>'生成setup' ,
					'link' => '?c=org.opencomb.development.toolkit.extension.createsetup.SelectExtension' ,
					'query' => array(
							'c=org.opencomb.development.toolkit.extension.createsetup.SelectExtension' ,
							'c=org.opencomb.development.toolkit.extension.createsetup.SelectItem' ,
							'c=org.opencomb.development.toolkit.extension.createsetup.CreateSetup',
						),
			) ,
		),
		'item:platform' => array(
			'title' => '平台' ,
			'link' => '?c=org.opencomb.development.toolkit.platform.createpackage.SelectItem' ,
			'query' => 'c=org.opencomb.development.toolkit.platform.createpackage.SelectItem' ,
			'menu' => 1 ,
			'item:create-package' => array(
				'title' => '二次发布' ,
				'link' => '?c=org.opencomb.development.toolkit.platform.createpackage.SelectItem' ,
				'query' => array(
						'c=org.opencomb.development.toolkit.platform.createpackage.SelectItem' ,
						'c=org.opencomb.development.toolkit.platform.createpackage.ShowVersion' ,
						'c=org.opencomb.development.toolkit.platform.createpackage.CreatePackage' ,
				),
			),
		),
		'item:workspace' => array(
			'title'=>'工作台' ,
			'link' => '?c=org.opencomb.development.toolkit.platform.ClearCache' ,
			'query' => 'c=org.opencomb.development.toolkit.platform.ClearCache' ,
			'menu' => 1 ,
			'item:clear-cache' => array(
					'title'=>'清空缓存' ,
					'link' => '?c=org.opencomb.development.toolkit.platform.ClearCache' ,
					'query' => 'c=org.opencomb.development.toolkit.platform.ClearCache' ,
			) ,
			'item:aop-manager' => array(
					'title'=>'AOP管理' ,
					'link' => '?c=org.opencomb.development.toolkit.compile.AOPManager' ,
					'query' => 'c=org.opencomb.development.toolkit.compile.AOPManager' ,
			) ,
			'item:template-weave-manager' => array(
					'title'=>'模板编织管理' ,
					'link' => '?c=org.opencomb.development.toolkit.compile.AOPManager' ,
					'query' => 'c=org.opencomb.development.toolkit.compile.AOPManager' ,
			) ,
		) ,
	) ,
) ;
