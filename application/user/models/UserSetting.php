<?php

class UserSetting {
	
	private $user_id;
	public function __construct($user_id)
	{
		$this->user_id = $user_id;
		return $this;
	}
	/**
	 * Gets a user widget by the specified name. Throws an exception if user is not using the widget
	 * @param string $name
	 * @return Precurio_Widget
	 */
	public function getWidget($name)
	{
		$widgets = $this->getWidgets();
		foreach($widgets as $widget)
		{
			if($widget->getName() == $name)return $widget;
		}
		throw new Exception('Widget not being used by specified user');
	}
	/**
	 * Returns an array of Precurio_Widget objects that are currently in use by the user
	 * @return array
	 */
	public function getWidgets()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SETTING));
		$settings = $table->fetchRow($table->select()->where('user_id= ? ',$this->user_id));
		if($settings == null)
		{
			$widgets = Bootstrap::getWidgets(true);
		}
		else
			$widgets = unserialize($settings->widgets);

		if(!count($widgets))return array();//not necessary, but so that the next line will never fail
		
		// check if the array contains Precurio_Widget objects, 
		//if it doesn't, then convert.This isfor backward compatibility with earlier versions,
		$widget = $widgets[array_rand($widgets)];
		if(!is_a($widget,'Precurio_Widget'))
		{
			$config = Zend_Registry::get('config');
   			$arr = $config->widget->toArray();//$arr = Array([wgt_poll] => 1,[wgt_link] => 1,....
			$temp = array();
			foreach($widgets as $key=>$value)
			{
				if($key == 'employee')$key = 'featured-employee';
				if($key == 'news')$key = 'recent-content';
				if($key == 'links')$key = 'link';
				if(isset($arr['wgt_'.$key]) && $value)
				{
					$widget = new Precurio_Widget($key,$value);
   					$temp[] = $widget;
				}
			}
			//now add the others that were there before. Note, this is for backward compatible
			//so that upgrading users will not see an empty homepage
			$widgets = Bootstrap::getWidgets(true);
			foreach($widgets as $aWidget)
			{
				$key = $aWidget->getName(); 
				if($key == 'suggested-content' || $key == 'my-profile' || $key == 'featured-article' || $key =='portal-update' || $key == 'reminder' || $key == 'ads' || $key == 'group-resource')
				{
   					$temp[] = $aWidget;
				}
			}
			
			$widgets = $temp;
		}
		return $widgets;
	}
	/**
	 * @param array $data // Array([poll] => 1,[link] => 1,..
	 * @return null
	 */
	public function setWidgets($data)
	{
		$widgets = Bootstrap::getWidgets(true);
		$myWidgets = $this->getWidgets();
		$temp = array();
		foreach($widgets as $widget)
		{
			if(isset($data[$widget->getName()]))
			{
				//ok see if widget is already part of my widgets
				//if it is, copy the properties.
				foreach($myWidgets as $myWidget)
				{
					if($myWidget->getName() == $widget->getName())
					{
						$widget->cloneProperties($myWidget);
						break;
					}
				}
				$temp[] = $widget;
			}
		}
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SETTING));
		$settings = $table->fetchRow($table->select()->where('user_id= ? ',$this->user_id));
		$settings['widgets'] = serialize($temp);
		$settings->save();
	}
	
	/**
	 * update widget properties and saves.
	 * @param string $name - Name of the widget
	 * @param array $data - key/value pairs of properties to set
	 * @return null
	 */
	public function updateWidget($name,$data)
	{
		$myWidgets = $this->getWidgets();
		foreach($myWidgets as $myWidget)
		{
			if($myWidget->getName() == $name)
			{
				foreach($data as $key=>$value)
				{
					$myWidget->{$key} = $value;
				}
				break;
			}
		}
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SETTING));
		$settings = $table->fetchRow($table->select()->where('user_id= ? ',$this->user_id));
		$settings['widgets'] = serialize($myWidgets);
		$settings->save();
		return;
	}
	
	public function removeWidget($name)
	{
		$myWidgets = $this->getWidgets();
		foreach($myWidgets as $key=>$myWidget)
		{
			if($myWidget->getName() == $name)
			{
				unset($myWidgets[$key]);
			}
		}
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SETTING));
		$settings = $table->fetchRow($table->select()->where('user_id= ? ',$this->user_id));
		$settings['widgets'] = serialize($myWidgets);
		$settings->save();
		return;
	}
	
	public function getBlockedUsers()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SETTING));
		$settings = $table->fetchRow($table->select()->where('user_id= ? ',$this->user_id));
		if($settings == null)return $this->createUserSettings('blocked_users');
		return unserialize($settings->blocked_users);
	}
	public function setBlockedUsers($data)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SETTING));
		$settings = $table->fetchRow($table->select()->where('user_id= ? ',$this->user_id));
		$settings['blocked_users'] = serialize($data);
		$settings->save();
	}
	private function createUserSettings($param)
	{
		$config = Zend_Registry::get('config');
		$data = array();
		$data['user_id'] = $this->user_id;
		$data['widgets'] = serialize(Bootstrap::getWidgets(true));
		$data['blocked_users'] = serialize(array());
		$data['date_created'] = Precurio_Date::now()->getTimestamp();
		$data['locale'] = $config->default_locale;
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SETTING));
		$row = $table->createRow($data);
		$row->save();
		return ($param == 'widgets' || $param == 'blocked_users') ? unserialize($data[$param]) : $data[$param];
	}
	
	public function getLocale()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SETTING));
		$settings = $table->fetchRow($table->select()->where('user_id= ? ',$this->user_id));
		if($settings == null)return $this->createUserSettings('locale');
		return $settings->locale;
	}
	public function setLocale($locale)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SETTING));
		$settings = $table->fetchRow($table->select()->where('user_id= ? ',$this->user_id));
		$settings['locale'] = $locale;
		$settings->save();
	}
	
	public function __set($name,$value)
	{
		$this->{$name} = value;
	}

}

?>