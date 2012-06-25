<?php
namespace org\opencomb\development\toolkit\extension ;

use org\jecat\framework\fs\File;
use org\opencomb\coresystem\auth\Id;
use org\opencomb\platform\Platform;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\ui\xhtml\UIFactory;
use org\jecat\framework\message\Message;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\opencomb\coresystem\system\ExtensionSetupFunctions ;
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
	protected $arrConfig = array(
			'title'=>'创建扩展',
			'view' => array(
				'template' => 'CreateExtension.html' ,
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

	public function process()
	{
		$this->checkPermissions('您没有使用这个功能的权限,无法继续浏览',array()) ;
		
		$this->doActions();
	}
	
	public function form(){
		do{
			$this->view()->loadWidgets( $this->params ) ;
			
			if( !$this->view()->verifyWidgets() )
			{
				break ;
			}
			
			$sExtName = trim($this->view()->widget('extName')->value()) ;
			$sExtVersion = $this->view()->widget('extVersion')->value() ;
			$sExtTitle = trim($this->view()->widget('extTitle')->value()) ;
			$sClassNamespace = $this->view()->widget('extClassNamespace')->value() ;
			
			// 检查扩展名称中的非法字符
			if( !self::isExtensionNameValid($sExtName) )
			{
				$this->view()->messageQueue()->create(Message::error,"扩展名称存在不合法的字符") ;
				break ;
			}
			
			$aExtMgr = $this->application()->extensions() ;
			
			// 检查扩展是否存在
			if( $aExtMgr->extensionMetainfo($sExtName) )
			{
				$this->view()->messageQueue()->create(Message::error,"无法创建新扩展，系统中已经安装了名为%s的扩展",$sExtName) ;
				break ;
			}
			
			$sInstallPath = oc\EXTENSIONS_FOLDER."/{$sExtName}/{$sExtVersion}" ;
			if( file_exists('$sInstallPath') )
			{
				$this->view()->messageQueue()->create(Message::error,"无法在路径上创建新扩展，目录已经存在：%s",$sInstallPath) ;
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
			
			$this->view()->messageQueue()->create(Message::success,"创建了新扩展：%s",$sInstallPath) ;
			
			// 立即安装
			if( $this->view()->widget('extInstallAtOnce')->value() )
			{
				$aExtFolder = Platform::singleton()->installFolder()->findFolder( 'extensions/'.$sExtName.'/'.$sExtVersion );
				
				$aExtSetupFun = new ExtensionSetupFunctions($this->view()->messageQueue() );
				$aExtSetupFun->installAndEnableExtension( $aExtFolder );
			}

		}while(0) ;
	}

	private function createFolder($sPath)
	{
		Folder::createFolder($sPath) ;
		$this->view()->messageQueue()->create(Message::notice,"创建目录：%s",$sPath) ;
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
			$this->view()->messageQueue()->create(
				Message::failed
				, "创建文件失败，".$e->getMessage()
				, $e->messageArgvs()
			) ;
		}
		
		$this->view()->messageQueue()->create(Message::notice,"创建文件：%s",$sPath) ;
	}
	
	static public function isExtensionNameValid($sName)
	{
		return preg_match("/^[\\w\\-_]{".self::extname_minlen.",".self::extname_maxlen."}$/",$sName) ;
	}
}
