<?php
require_once ('Zend/Db/Table/Row/Abstract.php');
require_once('visitor/models/VisitorUtil.php');
require_once('visitor/models/vo/Visitor.php');

class Appointment extends Zend_Db_Table_Row_Abstract
{
	public function isToday()
	{
		$date = new Precurio_Date($this->appointment_date);		
		return $date->isToday();
	}
	
	public function getAppointmentDate()
	{		
		$date = new Precurio_Date($this->appointment_date);
		if($date->isYesterday()) return 'Yesterday';
		if($date->isToday()) return 'Today';
		if($date->isTomorrow()) return 'Tomorrow';
		return $date->get(Precurio_Date::DATE_MEDIUM);	
	}
	
	public function getCreationDate()
	{
		$date = new Precurio_Date($this->date_created);
		if($date->isYesterday()) $day = 'Yesterday';
		elseif($date->isToday()) $day = 'Today';		
		else $day = $date->get(Precurio_Date::DATE_MEDIUM);
				
		return $day.', '.$date->get(Precurio_Date::TIME_SHORT);
	}
	
	public function getHostContacts()
	{
		return VisitorUtil::getContacts($host);
	}
	
	public function getHost()
	{			
		return UserUtil::getUser($this->host)->getFullName();		
	}
	
	public function getCreator()
	{	
		return UserUtil::getUser($this->creator)->getFullName();		
	}
	/**
	 * Returns the type of appointment
	 * @return string
	 */
	public function getAppointmentType()
	{
		$timestamp = time();
			
		if( VisitorUtil::isReceptionist(Precurio_Session::getCurrentUserId()) )
			if( $this->isToday() )
				return Appointments::CURRENT;
			
		if( $this->appointment_date > time() )
			return Appointments::UPCOMING;
				
		return Appointments::PAST;
	}
		
	public function getAppointmentTime()
	{
		$date = new Precurio_Date($this->appointment_date);
		return $date->get(Precurio_Date::TIME_SHORT);	
	}
	
	public function getTimeIn()
	{
		$date = new Precurio_Date($this->time_in);
		return $date->get(Precurio_Date::TIME_SHORT);
	}
	
	public function getTimeOut()
	{
		if( Precurio_Utils::isNull($this->time_out) )
		return '';
		$date = new Precurio_Date($this->time_out);
		return $date->get(Precurio_Date::TIME_SHORT);
	}
	
	
	public function getParticipants()
	{
		$a_id = $this->id;
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_APPOINTMENT, 'rowClass'=>'Appointment'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
			
		$select = $select->from(array('a' => PrecurioTableConstants::USER_APPOINTMENT))
			->join(array('b' => PrecurioTableConstants::USERS),'a.user_id = b.user_id', array('first_name', 'last_name'))				
				->where("appointment_id = $a_id AND a.active = 1");
				
		$participants = $table->fetchAll($select)->toArray();
		
		
		for($i = 0; $i < count($participants); $i++)
			if($participants[$i]['user_id'] == $this->host) 
				unset($participants[$i]);		
		
		return $participants;
	}
	
	public function getContacts() // The use of contacts here synonymous to visitors
	{
		$a_id = $this->id;
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR_APPOINTMENT, 'rowClass'=>'Visitor'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
			
		$select = $select->from(array('a' => PrecurioTableConstants::VISITOR_APPOINTMENT))
			->join(array('b' => PrecurioTableConstants::VISITOR),'a.visitor_id = b.id', array('DOB', 'car_reg_number', 'emergency_contact'))	
			->join(array('c' => PrecurioTableConstants::CONTACTS),'b.contact_id = c.id', array('full_name', 'company'))	
			->where("appointment_id = $a_id");
				
		return $table->fetchAll($select);		
	}
	
	public function getComments()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::COMMENTS, 'rowClass'=>'Comment'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::COMMENTS))						
						->join(array('c' => PrecurioTableConstants::USERS),'c.user_id = a.user_id',array('first_name','last_name','profile_picture_id'))						
						->where('a.activity_id= ? ',$this->getActivityId())						
						->order('id ASC');
		
		$all = $table->fetchAll($select);
		return $all;
	}
	private $_activity_id = 0;
	public function getActivityId($type = Precurio_Activity::APPOINTMENT_ADDED)
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
	
	public function canAlter()
	{
		return TRUE; // any user that can view the appointmemt can edit alter it cince the user is either a receptionist or the host
	}
	
	public function isCurrent()
	{
		if(! $this->isToday() ) 
		return FALSE;
		
		return $this->status && Precurio_Date::now()->getTimestamp() > $this->appointment_date;
		
	}	

		
	
}
?>