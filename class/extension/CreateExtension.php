<?php
namespace org\opencomb\development\toolkit\extension ;

use org\jecat\framework\fs\File;
use org\opencomb\coresystem\auth\Id;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\ui\xhtml\UIFactory;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\opencomb\platform as oc;

class CreateExtension extends ControlPanel
{
	const extname_minlen = 6 ;
	const extname_maxlen = 30 ;
	
	/**
	 * @example /MVC模式/视图/表单控件(Widget)
	 * @forwiki /MVC模式/视图/表单控件(Widget)
	 * @forwiki /MVC模式/视图/表单控件/文字输入框(Text)
	 * @forwiki /MVC模式/视图/表单控件/选项(CheckBtn)
	 * 
	 * 控件bean的写法
	 */
	/**
	 * @example /校验器/字符长度校验器(Length):Bean格式演示[2]
	 * @forwiki /校验器/字符长度校验器(Length)
	 */
	public function createBeanConfig()
	{
		return array(
			'title'=>'创建扩展',
			'view:Extension' => array(
				'template' => 'CreateExtension.html' ,
				'class' => 'form' ,
				'widgets' => array(
					'extName' => array(
						'class' => 'text' ,
						'title' => '扩展名称' ,
						'value' => 'extname' ,
						'verifier:length' => array('min'=>self::extname_minlen,'max'=>self::extname_maxlen) ,
					) ,
					'extVersion' => array(
						'class' => 'text' ,
						'title' => '版本' ,
						'value' => '0.1' ,
						'verifier:version' => array() ,
					) ,
					'extTitle' => array(
						'class' => 'text' ,
						'title' => '扩展标题' ,
						'value' => '我的扩展' ,
						'verifier:length' => array('min'=>6,'max'=>60) ,
					) ,
					'extClassNamespace' => array(
						'class' => 'text' ,
						'value' => 'com.doname.extname' ,
						'title' => 'PHP命名空间' ,
					) ,
					'extInstallAtOnce' => array(
						'class' => 'checkbox' ,
						'title' => '立即安装新扩展' ,
						'checked' => 1 ,
					) ,
				) ,
			) ,
			'perms' => array(
				// 权限类型的许可
				'perm.purview'=>array(
					'namespace'=>'coresystem',
					'name' => Id::PLATFORM_ADMIN,
				) ,
			) ,
		) ;
	}

	public function process()
	{
		$this->checkPermissions('您没有使用这个功能的权限,无法继续浏览',array()) ;
		
		if( $this->viewExtension->isSubmit( $this->params ) )
		{do{
			$this->viewExtension->loadWidgets( $this->params ) ;
			
			if( !$this->viewExtension->verifyWidgets() )
			{
				break ;
			}
			
			$sExtName = trim($this->viewExtension->widget('extName')->value()) ;
			$sExtVersion = $this->viewExtension->widget('extVersion')->value() ;
			$sExtTitle = trim($this->viewExtension->widget('extTitle')->value()) ;
			$sClassNamespace = $this->viewExtension->widget('extClassNamespace')->value() ;
			
			// 检查扩展名称中的非法字符
			if( !self::isExtensionNameValid($sExtName) )
			{
				$this->viewExtension->messageQueue()->create(Message::error,"扩展名称存在不合法的字符") ;
				break ;
			}
			
			$aExtMgr = $this->application()->extensions() ;
			
			// 检查扩展是否存在
			if( $aExtMgr->extensionMetainfo($sExtName) )
			{
				$this->viewExtension->messageQueue()->create(Message::error,"无法创建新扩展，系统中已经安装了名为%s的扩展",$sExtName) ;
				break ;
			}
			
			$sInstallPath = oc\EXTENSIONS_FOLDER."/{$sExtName}/{$sExtVersion}" ;
			if( file_exists('$sInstallPath') )
			{
				$this->viewExtension->messageQueue()->create(Message::error,"无法在路径上创建新扩展，目录已经存在：%s",$sInstallPath) ;
				break ;
			}
			
			// 创建目录
			$this->createFolder($sInstallPath."/template") ;
			$this->createFolder($sInstallPath."/public") ;
			$this->createFolder($sInstallPath."/public/css") ;
			$this->createFolder($sInstallPath."/public/image") ;
			$this->createFolder($sInstallPath."/public/js") ;
			
			$nPos = strrpos($sExtName,'.') ;
			$sClassName = $nPos===false? $sExtName: substr($sExtName,$nPos+1) ;
			$sClassName = ucfirst($sClassName) ;
				
			// 创建 metainfo.xml 文件
			$this->createFile($sInstallPath."/metainfo.xml",'metainfo.xml',array(
				'sExtName' => $sExtName ,
				'sExtVersion' => $sExtVersion ,
				'sExtTitle' => $sExtTitle ,
				'sClassName' => $sClassName ,
				'sClassNamespace' => $sClassNamespace ,
			)) ;
			
			// 创建扩展文件
			if( $sClassNamespace )
			{
				$this->createFile("{$sInstallPath}/class/{$sClassName}.php",'Extension.class',array(
					'sClassName' => $sClassName ,
					'sClassNamespace' => $sClassNamespace ,
				)) ;
			}
			
			$this->viewExtension->messageQueue()->create(Message::success,"创建了新扩展：%s",$sInstallPath) ;
			
			// 立即安装
			if( $this->viewExtension->widget('extInstallAtOnce')->value() )
			{
				$aSetting = Setting::singleton() ;
				
				// 安装
				$arrInstalleds = $aSetting->item('/extensions','installeds',array()) ;
				$arrInstalleds[] = $sExtName.'/'.$sExtVersion ;
				$aSetting->setItem('/extensions','installeds',$arrInstalleds) ;
				
				// 激活
				$arrEnable = $aSetting->item('/extensions','enable',array()) ;
				$arrEnable[3][] = $sExtName ;
				$aSetting->setItem('/extensions','enable',$arrEnable) ;
				
				$aSetting->saveKey('/extensions') ;
				
				$this->viewExtension->messageQueue()->create(Message::success,"新扩展 %s 已经安装到系统中",$sExtName) ;
			}

		}while(0) ;}
	}

	private function createFolder($sPath)
	{
		Folder::createFolder($sPath) ;
		$this->viewExtension->messageQueue()->create(Message::notice,"创建目录：%s",$sPath) ;
	}
	
	private function createFile($sPath,$sTemplate,$arrVariables=null)
	{
		try{
			
			$aFile = new File($sPath) ;
			$aFile->create() ;			
			UIFactory::singleton()->create()->display(
				'development-toolkit:'.$sTemplate
				, $arrVariables
				, $aFile->openWriter()
			) ;
		}
		catch (Exception $e)
		{
			$this->viewExtension->messageQueue()->create(
				Message::failed
				, "创建文件失败，".$e->getMessage()
				, $e->messageArgvs()
			) ;
		}
		
		$this->viewExtension->messageQueue()->create(Message::notice,"创建文件：%s",$sPath) ;
	}
	
	static public function isExtensionNameValid($sName)
	{
		return preg_match("/^[\\w\\-_]{".self::extname_minlen.",".self::extname_maxlen."}$/",$sName) ;
	}
}
