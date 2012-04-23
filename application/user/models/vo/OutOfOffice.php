<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once ('user/models/vo/User.php');
require_once ('user/models/UserUtil.php');
class OutOfOffice extends Zend_Db_Table_Row_Abstract {
	
	public function getId()
	{
		return $this->id;
	}
	/**Get leave date
	 * @param $raw Boolean - indicated whether to return the unix timestamp (true) or a formated date (false). Default = false
	 * @return String 
	 */
	public function getLeaveDate($raw = false)
	{
		if($raw)return $this->leave_date;
		if($raw)return $this->leave_date;
		$date = new Precurio_Date($this->leave_date);
		return $date->get(Precurio_Date::DAY).'-'.$date->get(Precurio_Date::MONTH_NAME);
	}
	/**Messaget to show to other members, i.e a leave status message
	 * @return String
	 */
	public function getMessage()
	{
		return $this->summary;
	} 
	/**
	 * Get Return date
	 * @param $raw Boolean - indicated whether to return the unix timestamp (true) or a formated date (false). Default = false
	 * @return String 
	 */
	public function getReturnDate($raw = false)
	{
		if($raw)return $this->return_date;
		$date = new Precurio_Date($this->return_date);
		return $date->get(Precurio_Date::DAY).'-'.$date->get(Precurio_Date::MONTH_NAME);
	}
	
	/**
	 * @return User
	 */
	public function getProxy()
	{
		return UserUtil::getUser($this->proxy_id);
	}

}

?>