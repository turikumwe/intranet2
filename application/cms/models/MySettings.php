<?php
require_once ('cms/models/vo/Settings.php');
class MySettings {
	
	/**
	 * @param $group_id
	 * @return Settings
	 */
	public static function getGroupSettings($group_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUP_SETTINGS,'rowClass'=>'Settings'));
		$settings = $table->find($group_id)->current();
		return $settings;
	}

}

?>