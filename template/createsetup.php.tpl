<?php
namespace {=$namespace}\setup;

use org\jecat\framework\db\DB ;
use org\jecat\framework\message\Message;
use org\jecat\framework\message\MessageQueue;
use org\opencomb\platform\ext\Extension;
use org\opencomb\platform\ext\ExtensionMetainfo ;
use org\opencomb\platform\ext\IExtensionDataInstaller ;

class DataInstaller implements IExtensionDataInstaller
{
	public function install(MessageQueue $aMessageQueue,ExtensionMetainfo $aMetainfo)
	{
		$aExtension = new Extension($aMetainfo);
		
		// 1 . create data table
		<foreach for="$arrTableInfoList" item="tableinfo" key="sTableName">
		DB::singleton()->execute( "{=$tableinfo['Create Table']}" );
		$aMessageQueue->create(Message::success,'新建数据表： %s',"{=$sTableName}");
		
		</foreach>
		
		// 2. insert table data<clear />
		<foreach for="$arrTableInfoList" item="tableinfo" key="sTableName"><clear />
			<if "!empty($tableinfo['keys'])"><clear />
		$nDataRows = 0 ;<clear />
				<foreach for="$tableinfo['data']" item='arrRowData'>
		$nDataRows+= DB::singleton()->execute( "REPLACE INTO `{=$tableinfo['Table']}` ({=implode(',',$tableinfo['keys'])}) VALUES ({=implode(',',$tableinfo['factors'])}) ", array({=implode(',',$arrRowData)}) ) ;<clear />
				</foreach>
		$aMessageQueue->create(Message::success,'向数据表%s插入了%d行记录。',array("{=$sTableName}",$nDataRows));
			</if>
		</foreach>
		
		// 3. settings
		<if '!empty($setting)' >
		$aSetting = $aExtension->setting() ;
			<foreach for="$setting" key='path' item="items">
				<foreach for="$items" key='item' item='value'>
		$aSetting->setItem('{=$path}','{=$item}',{=var_export($value,true)});
				</foreach>
		$aMessageQueue->create(Message::success,'保存配置：%s',"{=$path}");
			</foreach>
		</if>
		
		// 4. files
		<if '!empty($dataFolder)' >
		// $sDataFolder = '{=$dataFolder}';
		// $sToFolder = $aExtension ->filesFolder()->path();
		// Folder::singleton()->copy($sDataFolder,$sToFolder);
		// $aMessageQueue->create(Message::success,'复制文件夹： `%s` to `%s`',array($sDataFolder,$sToFolder));
		</if>
	}
}
