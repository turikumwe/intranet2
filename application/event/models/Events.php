<?php
require_once ('event/models/vo/Event.php');
class Events {
	
	public static function getEvents($user_id,$location_id, $work_related,$past=true)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_EVENTS, 'rowClass'=>'Event'));
		$select = $table->select(false);
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->distinct()
						->from(array('a' => PrecurioTableConstants::EVENT))
						->join(array('b' => PrecurioTableConstants::USER_EVENTS),'a.id = b.event_id',array('date_invited'=>'date_created'))
						->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.is_open = 0 OR b.invitee_id= ? ',$user_id)
						->where('a.is_open = 1 OR b.invitee_id= ? ',$user_id)
						->where('a.active=1')
						->order('start_timestamp asc');
		if($location_id !== null)//i.e if user if filtering by event type
			$select = $select->where('a.location_id=?',$location_id);				
		if($work_related !== null)//i.e if user if filtering by event type
			$select = $select->where('a.work_related = ?',$work_related);
		if($past===true)
			$select = $select->where('a.start_timestamp < ?',Precurio_Date::now()->getTimestamp());
		else
			$select = $select->where('a.start_timestamp > ?',Precurio_Date::now()->getTimestamp());

//		$db = Zend_Registry::get('db');
//		$st = $db->query($select);
//		Precurio_Utils::debug($st);
			
		$all = $table->fetchAll($select);
		return $all;
	}
	public static function getMyEvents($user_id,$location_id, $work_related)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_EVENTS, 'rowClass'=>'Event'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->distinct()
						->from(array('a' => PrecurioTableConstants::EVENT))
						->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.user_id= ? ',$user_id)
						->where('a.active=1')
						->order('a.id desc');
		if($work_related !== null)//i.e if user if filtering by event type
			$select = $select->where('a.work_related = ?',$work_related);
		if($location_id !== null)//i.e if user if filtering by location
			$select = $select->where('a.location_id=?',$location_id);
			
		$all = $table->fetchAll($select);
		return $all;
	}
	/**
	 * Return event object whoose id is $event_id;s
	 * @param $event_id int
	 * @return Event
	 */
	public static function getEvent($event_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_EVENTS, 'rowClass'=>'Event'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->distinct()
						->from(array('a' => PrecurioTableConstants::EVENT))
						->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.id= ? ',$event_id);
	
			
		$event = $table->fetchRow($select);
		return $event;
	}
	public static function createEvent($data)
	{
		//insert into events table
		if(isset($data['id']))
		{
			unset($data['id']);
		}
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::EVENT));
		$newEvent = $table->createRow($data);
		$event_id = $newEvent->save();
		
		//immediately invite creator.
		$data = array();
		$user_id = Precurio_Session::getCurrentUserId();
		$date_created = Precurio_Date::now()->getTimestamp();
		$data['event_id'] = $event_id;
		$data['invitee_id'] = $user_id;
		$data['date_created'] = $date_created;
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_EVENTS));
		$invite = $table->createRow($data);
		$invite->save();
		
		$url = '/event/'.(Events::getEvent($event_id)->isPast() ? 'past' : 'upcoming'). '/details/e_id/'.$event_id;
		//now create new activity., which also triggers notifications.
		Precurio_Activity::newActivity($user_id,Precurio_Activity::ADD_EVENT,$event_id,$url);
		
		return $event_id;
	}
	public static function updateEvent($data)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::EVENT));
			
		try
		{
			$event = $table->find($data['id'])->current();
			$event->setFromArray($data);
			$event->save();
			Precurio_Activity::newActivity($event->user_id,Precurio_Activity::EDIT_EVENT,$event->id)	;	
		}
		catch (Exception $e)
		{
			$log = Zend_Registry::get('log');
			$log->err($e);
		}
		return $event;
	}
}

?>