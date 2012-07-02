<?php
namespace org\opencomb\development\toolkit\extension ;

use net\phpconcept\pclzip\PclZip;
use org\opencomb\platform\service\Service;
use org\opencomb\coresystem\auth\Id;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\message\Message;
use org\opencomb\platform\ext\ExtensionManager;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\fs\FSIterator;

// /?c=org.opencomb.development.toolkit.extension.ExtensionPackages

class ExtensionPackages extends ControlPanel{
	protected $arrConfig = array(
			'title'=>'扩展打包',
			'view' => array(
				'template' => 'extension/ExtensionPackages.html' ,
			),
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
		
		$this->doActions() ;
		
		$aPackageFolder = $this->getPackageFolder();
		
		$this->view()->variables()->set('packageFolder',$aPackageFolder->path());
		$this->view()->variables()->set('packageList',$this->packageList()) ;
	}
	
	protected function package(){
		$name = $this->params['name'];
		$includeGit = $this->params['includeGit'];
		
		$debug = $this->getDebug();
		$packageList = $this->packageList();
		$package = $packageList[$name];
		$bSuccess = true;
		if(!empty($package))
		{
			$aPackagedFSO = $this->getPackagedFSO($package['name'],$package['version'],$includeGit);
			
			if(file_exists($aPackagedFSO->path()))
			{
				if(!unlink($aPackagedFSO->path()))
				{
					$this->view()->createMessage(Message::error,'清除原有扩展包文件失败:%s',$aPackagedFSO->path());
					return ;
				}
			}
			
			$aZip = new PclZip($aPackagedFSO->path()) ;
			$installFolder = new Folder($package['installPath']);
			$arrPackagedFileList = array() ;
			foreach($installFolder->iterator(FSIterator::FILE|FSIterator::FOLDER|FSIterator::RECURSIVE_SEARCH) as $sSubPath)
			{
				if( empty($includeGit) and preg_match('`(^|/)\\.(git|svn|cvs)(/|$)`',$sSubPath) )
				{
					continue ;
				}
				$sPath = $package['installPath'].'/'.$sSubPath ;
				if( $aZip->add($sPath,PCLZIP_OPT_REMOVE_PATH,$package['installPath'])===0 )
				{
					$this->view()->createMessage(Message::error,'打包文件时出错:%s',$sPath);
					return ;
				}
				else
				{
					$arrPackagedFileList [] = $sPath ;
				}
			}

			$this->view()->createMessage(
				Message::success,
				'%s打包成功<button onclick=\'showPackagedFileList(this,%s)\'>详细</button>',
				array(
					$name,
					json_encode($arrPackagedFileList),
				)
			);
			
			// disable tempsave
			$this->arrPackageList = null;
		}
	}
	
	private function packageList(){
		if(empty($this->arrPackageList)){
			$this->arrPackageList = array();
			$aExtensionManager = ExtensionManager::singleton();
			foreach($aExtensionManager->metainfoIterator() as $ext){
				$name = (string)($ext->name());
				$this->arrPackageList[$name] =
					array(
						'name' => $ext->name(),
						'title' => $ext->title(),
						'version' => $ext->version(),
						'installPath' => $ext->installPath(),
						'hasPackaged' => $this->hasPackaged($ext->name(),$ext->version(),0),
						'hasPackagedVl' => $this->hasPackaged($ext->name(),$ext->version(),1),
						'metainfo' => $ext,
						'link' =>
							array(
								'package' => $this->createLink('package',$ext->name()),
								'packageVl' => $this->createLink('package',$ext->name(),'',1),
								'download' => $this->createLink('download',$ext->name(),$ext->version()),
								'downloadVl' => $this->createLink('download',$ext->name(),$ext->version(),1),
							),
					);
				
				$this->arrPackageList[$name]['link']['pkgbytes'] = self::formatBytes(self::getPackagedFSO($ext->name(),$ext->version(),0)->length()) ;
				$this->arrPackageList[$name]['link']['pkgbytesVl'] = self::formatBytes(self::getPackagedFSO($ext->name(),$ext->version(),1)->length()) ;
			}
		}
		return $this->arrPackageList;
	}
	
	static private function formatBytes($nBytes)
	{
		if( $nBytes>1024*1024 )
		{
			return round($nBytes/(1024*1024),2) . ' MB' ;
		}
		else if( $nBytes>1024 )
		{
			return round($nBytes/(1024),2) . ' KB' ;
		}
		else 
		{
			return $nBytes . ' Byte' ;
		}
	}
	
	static private function getDebug(){
		return Service::singleton()->isDebugging();
	}
	
	static private function getPackageFolder()
	{
		return Extension::flyweight('development-toolkit')->filesFolder()->findFolder('extensionPackages',Folder::FIND_AUTO_CREATE) ;
	}
	
	/**
	 * 扩展打包之后的文件名：
	 * 1.不含版本库
	 * <extension name>-<version>.ocp.zip
	 * 2.包含版本库
	 * <extension name>-<version>-repos.ocp.zip
	 */
	static public function getPackagedFSO($name,$version,$vl,$nFlag=Folder::FIND_AUTO_CREATE_OBJECT){
		if(empty($vl)){
			$sVl = '';
		}else{
			$sVl = '-repos';
		}
		return self::getPackageFolder()->findFile($name.'-'.$version.$sVl.'.zip',$nFlag);
	}
	
	static public function hasPackaged($name,$version , $vl){
		return self::getPackagedFSO($name,$version , $vl)->exists();
	}
	
	public function createLink($type,$name,$version='',$vl=''){
		switch($type){
		case 'package':
			if(empty($vl)){
				return '/?c=org.opencomb.development.toolkit.extension.ExtensionPackages&a='.$this->makeActionQuery("package",false).'&name='.$name;
			}else{
				return '/?c=org.opencomb.development.toolkit.extension.ExtensionPackages&a='.$this->makeActionQuery("package",false).'&name='.$name.'&includeGit=on';
			}
			break;
		case 'download':
			return self::getPackagedFSO($name, $version, $vl)->httpUrl() ;
			break;
		}
	}
	
	private $arrPackageList = null;
}

