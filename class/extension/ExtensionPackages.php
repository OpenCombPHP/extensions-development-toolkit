<?php
namespace org\opencomb\development\toolkit\extension ;

use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\jecat\framework\message\Message ;
use org\opencomb\platform\ext\ExtensionManager ;
use org\opencomb\platform\ext\Extension ;
use org\jecat\framework\fs\FileSystem ;
use org\jecat\framework\fs\FSIterator ;
use org\jecat\framework\fs\imp\LocalFSO ;
use org\jecat\framework\fs\IFolder ;
use org\opencomb\platform\Platform ;

// /?c=org.opencomb.development.toolkit.extension.ExtensionPackages
// 扩展打包之后的文件名：
// <extension name>-<version>.ocp.zip

class ExtensionPackages extends ControlPanel{
	public function createBeanConfig()
	{
		return array(
			'view:extensionPackages' => array(
				'template' => 'ExtensionPackages.html' ,
			),
		);
	}
	
	public function process(){
		$this->doActions() ;
		$this->extensionPackages->variables()->set('packageList',$this->packageList()) ;
	}
	
	protected function actionPackage(){
		$name = $this->params['name'];
		$includeGit = $this->params['includeGit'];
		
		$debug = $this->getDebug();
		$packageList = $this->packageList();
		$package = $packageList[$name];
		$bSuccess = true;
		if(!empty($package)){
			$aPackagedFSO = $this->getPackagedFSO($package['name'],$package['version']);
			if(! $aPackagedFSO instanceof LocalFSO){
				$this->extensionPackages->createMessage(Message::notice, '失败：扩展安装目录不在本地文件系统');
				return;
			}
			$aZip = new \ZipArchive();
			$filename = $aPackagedFSO->name();
			$filePath = $this->getPackagedFSO($package['name'],$package['version'])->url(false);
			if($debug){
				$this->extensionPackages->createMessage(Message::notice,'即将创建压缩文件:%s : %s',array($aPackagedFSO->path(),$filePath));
			}else{
				$this->extensionPackages->createMessage(Message::notice,'创建扩展包，在:%s',$aPackagedFSO->path());
			}
			if($aZip->open($filePath,\ZIPARCHIVE::CREATE) !== TRUE){
				$this->extensionPackages->createMessage(Message::notice,"can not open file <$filePath>");
			}else{
				$installFolder = FileSystem::singleton()->findFolder($package['installPath']);
				if($debug){
					$this->extensionPackages->createMessage(Message::notice,'扩展安装目录:%s',$installFolder->path());
				}
				if($debug){
					if($includeGit){
						$this->extensionPackages->createMessage(Message::notice,'包含git\svn\cvs目录');
					}
				}
				foreach($installFolder->iterator(FSIterator::FILE | FSIterator::FOLDER | FSIterator::RECURSIVE_SEARCH | FSIterator::RETURN_FSO) as $it){
					if(preg_match('`/\\.(git|svn|cvs)(/|$)`',$it->path())){
						if(empty($includeGit)){
							continue;
						}
					}
					if($it instanceof IFolder){
						$path = $it->path();
						$path = FileSystem::relativePath($installFolder,$it);
						$bSuccess = $bSuccess and $aZip->addEmptyDir($path);
						if($debug){
							$this->extensionPackages->createMessage(Message::notice, '创建目录：%s : %s',array($path,$aZip->getStatusString()));
						}
					}else{
						$path = $it->path();
						$path = FileSystem::relativePath($installFolder,$it);
						$bSuccess = $bSuccess and $aZip->addFile($it->url(false),$path);
						if($debug){
							$this->extensionPackages->createMessage(Message::notice, '压缩文件 %s 来自 %s : %s',array($path,$it->path(),$aZip->getStatusString()));
						}
					}
				}
				$bSuccess = $bSuccess and $aZip->close();
				if($debug){
					$this->extensionPackages->createMessage(Message::notice,'关闭压缩文件:%s',array($aPackagedFSO->path()));
				}
				if($debug){
					if($bSuccess){
						$this->extensionPackages->createMessage(Message::notice,'%s打包成功',array($name));
					}else{
						$this->extensionPackages->createMessage(Message::notice,'%s打包失败',array($name));
					}
				}else{
					if($bSuccess){
						$this->extensionPackages->createMessage(Message::notice,'%s打包成功',array($name));
					}else{
						$this->extensionPackages->createMessage(Message::notice,'%s打包失败',array($name));
					}
				}
			}
			
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
						'hasPackaged' => $this->hasPackaged($ext->name(),$ext->version()),
						'metainfo' => $ext,
						'link' =>
							array(
								'package' => $this->createLink('package',$ext->name()),
								'download' => $this->createLink('download',$ext->name(),$ext->version()),
							),
					);
			}
		}
		return $this->arrPackageList;
	}
	
	private function getDebug(){
		$aPlatform = Platform::singleton();
		return $aPlatform->isDebugging();
	}
	
	private function getPackageFolder(){
		return Extension::flyweight('development-toolkit')->publicFolder();
	}
	
	private function getPackagedFSO($name,$version){
		return $this->getPackageFolder()->findFile($name.'-'.$version.'.ocp.zip',FileSystem::FIND_AUTO_CREATE_OBJECT);
	}
	
	private function hasPackaged($name,$version){
		return $this->getPackagedFSO($name,$version)->exists();
	}
	
	private function createLink($type,$name,$version=''){
		switch($type){
		case 'package':
			return '/?c=org.opencomb.development.toolkit.extension.ExtensionPackages&act=package&name='.$name;
			break;
		case 'download':
			return $this->getPackagedFSO($name,$version)->path();
			break;
		}
	}
	
	private $arrPackageList = null;
}
