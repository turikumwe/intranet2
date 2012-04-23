<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once ('cms/models/vo/Content.php');
require_once ('Precurio/ActivityInterface.php');
class Event extends Zend_Db_Table_Row_Abstract implements Precurio_ActivityInterface{
	 const ATTENDING = 3;
	 const NOTSURE = 2;
	 const NOTATTENDING = 1;
	public function getId()
	{
		return $this->id;
	}
	public function getTitle()
	{
		return $this->title;
	}
	public function getSummary()
	{
		return $this->summary;
	}
	public function getLocation()
	{
		return Location::getLocationName($this->location_id);
	}
	public function getDate()
	{
		$date =  new Precurio_Date($this->start_timestamp);
		return $date->get(Precurio_Date::DATE_LONG);
	}
	public function getEndDate()
	{
		$date = new Precurio_Date($this->end_date,null,'de');
		return $date->get(Precurio_Date::DATE_LONG);
	}
	public function getImagePath()
	{
		$root = Zend_Registry::get('root');
		if(!file_exists($root.'/public/'.$this->logo))
			return '/uploads/questionmark.jpg';
		return $this->logo;
	}
	public function isPast()
	{
		$date =  new Precurio_Date($this->start_timestamp);
		return !Precurio_Date::now()->isEarlier($date);
	}
	public function isPublic()
	{
		return $this->is_open;
	}
	public function getInvitees()
	{
		$db = Zend_Registry::get('db');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_EVENTS));
		$select = $table->select(false);
		$select->setIntegrityCheck(false);
		$select = $select->from(array('a'=>PrecurioTableConstants::USER_EVENTS),array('user_id'=>'invitee_id'))
							->join(array('b'=>PrecurioTableConstants::USERS),'a.invitee_id = b.user_id',array('full_name'=>'concat (first_name," " ,last_name)','email'))
							->where('invitee_id <> ?',$this->user_id )
							->where('event_id = ?',$this->getId() );
		$user_ids = $db->fetchAll($select);
		return $user_ids;
	}
	public function setInvitees($user_ids)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_EVENTS));
		//delete all previous invitees. remember to exempt the creator.
		$where = $table->getAdapter()->quoteInto('invitee_id <> ?', $this->user_id);
		$where2 = $table->getAdapter()->quoteInto('event_id = ?',$this->getId() );
		$n = $table->delete(array($where,$where2));//$n will be 0 for a new invitation
		$data = array();
		$data['event_id'] = $this->getId(); 
		$data['date_created'] = Precurio_Date::now()->getTimestamp();
		foreach($user_ids as $user_id)
		{
			$data['invitee_id'] = $user_id;
			$row = $table->createRow($data);
			$row->save();
			if($n==0)
			{
				//only send an invitation notification, if there has been no previous notification.
				//else , already invited people will keep getting invitation notifications  anytime there
				//is a new invite.
				$url = '/event/'.($this->isPast() ? 'past' : 'upcoming'). '/details/e_id/'.$this->getId();
				Precurio_Activity::newActivity($this->user_id,Precurio_Activity::EVENT_INVITED,$this->getId(),$url,$user_id);
			}
			
		}
		
		
	}
	public function getStatusStr()
	{
		//status string is different for upcoming and past event
		$tr = Zend_Registry::get('Zend_Translate');
		$status = $this->getStatus();
		if($status == Event::ATTENDING)
			return $tr->translate(PrecurioStrings::ATTENDINGEVENT);
		if($status == Event::NOTSURE)
			return $tr->translate(PrecurioStrings::UNSUREEVENT);
		if($status == Event::NOTATTENDING)
			return $tr->translate(PrecurioStrings::NOTATTENDINGEVENT);
		return $tr->translate("You did not attend this event");
	}
	private $_status = -1;
	public function getStatus()
	{
		if($this->_status == -1)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::EVENT_STATUS));
			$row = $table->fetchRow($table->select()->where('event_id = ?',$this->getId())
												->where('user_id = ?',Precurio_Session::getCurrentUserId()));
			$this->_status = $row == null ? 0 : $row->status;
		}
		return $this->_status;
		
	}
	public function getUsersAttending()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS, 'rowClass'=>'User'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->distinct()
						->from(array('a' => PrecurioTableConstants::USERS))
						->join(array('b' => PrecurioTableConstants::EVENT_STATUS),'a.user_id = b.user_id',array('date_invitation_accepted'=>'date_created'))
						->join(array('c' => PrecurioTableConstants::PROFILE_PICS),'a.profile_picture_id = c.id',array('image_path'))
						->where('b.event_id= ? ',$this->getId())
						->where('b.status= ? ',self::ATTENDING);
			
		$all = $table->fetchAll($select);
		return $all;
	}
	public function getUsersNotAttending()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS, 'rowClass'=>'User'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->distinct()
						->from(array('a' => PrecurioTableConstants::USERS))
						->join(array('b' => PrecurioTableConstants::EVENT_STATUS),'a.user_id = b.user_id',array('date_invitation_accepted'=>'date_created'))
						->join(array('c' => PrecurioTableConstants::PROFILE_PICS),'a.profile_picture_id = c.id',array('image_path'))
						->where('b.event_id= ? ',$this->getId())
						->where('b.status= ? ',self::NOTATTENDING);
			
		$all = $table->fetchAll($select);
		return $all;
	}
	public function getUsersNotSure()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS, 'rowClass'=>'User'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->distinct()
						->from(array('a' => PrecurioTableConstants::USERS))
						->join(array('b' => PrecurioTableConstants::EVENT_STATUS),'a.user_id = b.user_id',array('date_invitation_accepted'=>'date_created'))
						->join(array('c' => PrecurioTableConstants::PROFILE_PICS),'a.profile_picture_id = c.id',array('image_path'))
						->where('b.event_id= ? ',$this->getId())
						->where('b.status= ? ',self::NOTSURE);
			
		$all = $table->fetchAll($select);
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
						->where('b.activity_id= ? ',$this->getId())
						->where('b.type = ?',Precurio_Activity::ADD_EVENT)
						->order('id ASC');
		
		$all = $table->fetchAll($select);
		return $all;
	}
	private $_activity_id = 0;
	public function getActivityId($type = Precurio_Activity::ADD_EVENT)
	{
		$tr = Zend_Registry::get('Zend_Translate');
		if($this->_activity_id == 0)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ACTIVITY));
			$all = $table->fetchAll($table->select()
						->where('activity_id= ? ',$this->getId())
						->where('type = ?',$type));
			if(count($all) != 1)//the is an application logic error
				throw new Precurio_Exception($tr->translate(PrecurioStrings::APPLICATION_LOGIC_ERROR),Precurio_Exception::EXCEPTION_APPLICATION_ERROR,1001);
			$this->_activity_id =  $all[0]->id;
		}
		
		return $this->_activity_id; 	
	}
	
	public function getPhotos($lim=0)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT, 'rowClass'=>'Content'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::EVENT_CONTENTS),'a.id = b.content_id',array('content_id'))
						->join(array('c' => PrecurioTableConstants::USERS),'c.user_id = a.user_id',array('first_name','last_name','profile_picture_id'))
						->join(array('d' => PrecurioTableConstants::PROFILE_PICS),'c.profile_picture_id = d.id',array('profile_picture'=>'image_path'))
						->where('b.event_id= ? ',$this->getId())
						->where('a.is_photo = 1')
						->where('a.active = 1')
						->order('a.id DESC');
		if($lim != 0)
			$select = $select->limit($lim);
		$all = $table->fetchAll($select);
		return $all;
	}
	public function userIsInvited($user_id)
	{
		if($this->is_open)return true;
		//else i.e not an open event
		$invitees = $this->getInvitees();
		foreach($invitees as $invitee)
		{
			if($invitee->user_id == $user_id)
				return true;
		}
		//user is not in list
		return false;
	}
}

?>