<?php
return array(
	array(
		'title' => '开发' ,
		'menu' => array(
			'direction' => 'v' ,
			'tearoff' => true ,
			'items' => array(
					array(
							'title'=>'创建扩展' ,
							'link' => '?c=org.opencomb.development.toolkit.extension.CreateExtension' ,
					) ,
					array(
							'title'=>'清空缓存' ,
							'link' => '?c=org.opencomb.development.toolkit.platform.ClearCache' ,
					) ,
			) ,
		) ,
	) ,
) ;