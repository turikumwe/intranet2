<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once('user/models/vo/User.php');
require_once('task/models/vo/Transfer.php');
class Task extends Zend_Db_Table_Row_Abstract {
	const STATUS_OPEN = 0;
	const STATUS_COMPLETE = 1;
	const STATUS_ONHOLD = 2;
	const STATUS_REJECT = 3;
	const TYPE_ADVERT = 'ADVERT'; //this must correspond to feild names of content approval settings
	const TYPE_NEWS = 'NEWS';//this must correspond to feild names of content approval settings
	const TYPE_FEATURED = 'FEATURED';//this must correspond to feild names of content approval settings
	const TYPE_GROUP_CONTENT = "GROUP_CONTENT";
	const TYPE_WORKFLOW = "WORKFLOW";
	public function isComplete()
	{
		return ($this->status == self::STATUS_COMPLETE || $this->status == self::STATUS_REJECT);
	}
	/**
	 * @return string style name to set the task item.
	 */
	public function getStatusStyleStr()
	{
		if($this->status == self::STATUS_COMPLETE)
			return "closed";
		if($this->status == self::STATUS_OPEN)
			return "open";
		if($this->status == self::STATUS_ONHOLD)
			return "on_hold";
	}
	public function getStatusStr()
	{
		if($this->status == self::STATUS_COMPLETE)
			return "closed";
		if($this->status == self::STATUS_OPEN)
			return "open";
		if($this->status == self::STATUS_ONHOLD)
			return "on hold";
	}
	public function isDue()
	{
		$now = Precurio_Date::now()->getTimestamp();
		return  ($now >= $this->end_time);
	}
	public function isMine()
	{
		$my_id = Precurio_Session::getCurrentUserId();
		return $this->creator_user_id == $my_id;
	}
	public function isAssignedToMe()
	{
		return $this->user_id == Precurio_Session::getCurrentUserId();
	}
	/**
	 * Gets the name of the creator of the task
	 * @return string
	 */
	public function getTaskCreatorName()
	{
		return $this->first_name.' '.$this->last_name;
	}
	/**
	 * Gets the name of the user performing the task
	 * @return string
	 */
	public function getTaskUserName()
	{
		return $this->getUser()->getFullName();
	}
	private $user;
	/**
	 * Gets the user performing the task
	 * @return User
	 */
	public function getUser()
	{
		if(!Precurio_Utils::isNull($this->user))
			return $this->user;
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS, 'rowClass'=>'User'));
		$this->user = $table->fetchRow($table->select()->where('user_id = ?',$this->user_id));
		return $this->user; 
	}
	public function getDueDate()
	{
		$date = new Precurio_Date($this->end_time);
		return $date->get(Precurio_Date::DATE_LONG);
	}
	public function getDateCreated()
	{
		$date = new Precurio_Date($this->date_created);	
		return $date->get(Precurio_Date::DATE_LONG);
	}
	public function getLastModified()
	{
		$date = new Precurio_Date($this->last_updated,null,'en_AU');	
		return $date->get(Precurio_Date::DATE_LONG,'en_AU');
	}
	public function canAccess($user_id)
	{
		return ($user_id == $this->user_id) || ($user_id == $this->creator_user_id);
	}	
	
	public function getTransferHistory()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TASK_TRANSFERS, 'rowClass'=>'Transfer'));
		$all = $table->fetchAll($table->select()->where('task_id = ?',$this->id));
		return $all;
	}
	
	public function getComments()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::COMMENTS, 'rowClass'=>'Comment'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::COMMENTS))
						->join(array('b' => PrecurioTableConstants::ACTIVITY),'a.activity_id = b.id',array('actual_activity_id'=>'b.activity_id','type'))
						->join(array('c' => PrecurioTableConstants::USERS),'c.user_id = a.user_id',array('first_name','last_name','profile_picture_id'))
						->join(array('d' => PrecurioTableConstants::PROFILE_PICS),'c.profile_picture_id = d.id',array('image_path'))
						->where('b.activity_id= ? ',$this->id)
						->where('b.type = ?',Precurio_Activity::NEW_TASK)
						->order('id ASC');
		
		$all = $table->fetchAll($select);
		return $all;
	}
	private $_activity_id = 0;
	public function getActivityId($type = Precurio_Activity::NEW_TASK)
	{
		$tr = Zend_Registry::get('Zend_Translate');
		if($this->_activity_id == 0)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ACTIVITY));
			$all = $table->fetchAll($table->select()
						->where('activity_id= ? ',$this->id)
						->where('type = ?',$type));
			if(count($all) != 1)//the is an application logic error
				throw new Precurio_Exception($tr->translate(PrecurioStrings::APPLICATION_LOGIC_ERROR),Precurio_Exception::EXCEPTION_APPLICATION_ERROR,1001);
			$this->_activity_id =  $all[0]->id;
		}
		
		return $this->_activity_id; 	
	}
	
	public function isApproval()
	{
		return !Precurio_Utils::isNull($this->type);
	}
	public function getTitle()
	{
		return $this->title;
	}
	public function __toString()
	{
		return $this->title;
	}

}

?>