<?php
namespace {=$namespace}\setup;

use org\jecat\framework\db\DB ;
use org\jecat\framework\message\Message;
use org\jecat\framework\message\MessageQueue;
use org\opencomb\platform\ext\Extension;
use org\opencomb\platform\ext\ExtensionMetainfo ;
use org\opencomb\platform\ext\IExtensionDataInstaller ;
use org\jecat\framework\fs\Folder;

class DataInstaller implements IExtensionDataInstaller
{
	public function install(MessageQueue $aMessageQueue,ExtensionMetainfo $aMetainfo)
	{
		$aExtension = new Extension($aMetainfo);
		
		// 1 . create data table
		$aDB = DB::singleton();
		<foreach for="$arrTableInfoList" item="tableinfo" key="sTableName">
		$aDB->execute( "{=$tableinfo['Create Table']}" );
		$aMessageQueue->create(Message::success,'新建数据表： `%s` 成功',$aDB->transTableName('{=$sTableName}') );
		
		</foreach>
		
		// 2. insert table data
		<foreach for="$arrTableInfoList" item="tableinfo" key="sTableName"><clear />
			<if "!empty($tableinfo['keys'])"><clear />
		$nDataRows = 0 ;<clear />
				<foreach for="$tableinfo['data']" item='arrRowData'>
		$nDataRows+= $aDB->execute( 'REPLACE INTO `' . $aDB->transTableName("{=$sTableName}") . '` ({=implode(',',$tableinfo['keys'])}) VALUES ({=implode(',',$arrRowData)}) ') ;<clear />
				</foreach>
		$aMessageQueue->create(Message::success,'向数据表%s插入了%d行记录。',array($aDB->transTableName("{=$sTableName}"),$nDataRows));
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
		$sFromPath = '{=$dataFolder}';
		$sDestPath = $aExtension ->filesFolder()->path();
		Folder::RecursiveCopy( $sFromPath , $sDestPath );
		$aMessageQueue->create(Message::success,'复制文件夹： `%s` to `%s`',array($sFromPath,$sDestPath));
		</if>
	}
}
