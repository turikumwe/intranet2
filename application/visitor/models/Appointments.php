<?php
require_once ('visitor/models/vo/Appointment.php');
require_once ('visitor/models/VisitorUtil.php');
class Appointments
{
	const UPCOMING = 1;
	const CURRENT = 2;
	const PAST = 3;	
		
	private $pastAppointments;
	private $currentAppointments;
	private $upcomingAppointments;
		
	public function getAppointmentSummary($type, $page = 1, $mine = false)
	{
		switch ($type)
		{
			case self::UPCOMING:				
				$appointments = $this->merge($this->getUpcomingAppointments($mine));
				break;
			case self::CURRENT:
				$appointments = $this->merge($this->getCurrentAppointments($mine));
				break;
			case self::PAST:				
				$appointments = $this->merge($this->getPastAppointments($mine));
				break;			
		}
			
		$per_page = 10;
		$start = ($page-1)*$per_page;
					
		$appointments = array_splice($appointments,$start,$per_page);
		return $appointments;	
	}
	
	
	private function merge($a,$b=array())
	{
		$temp = array();
		foreach($a as $obj)
		{
			array_push($temp,$obj);
		}
		foreach($b as $obj)
		{
			array_push($temp,$obj);
		}
		return $temp;
	}
	
	
	public function getCount($type, $mine=false)
	{
		switch($type)
		{
			case self::CURRENT:
				$count = count($this->getCurrentAppointments());
				break;
			case self::UPCOMING:
				$count = count($this->getUpcomingAppointments($mine));
				break;
			case self::PAST:
				$count = count($this->getPastAppointments($mine));
				break;			
		}
		
		return $count;	
	}
	
	public function getPastAppointments($mine=false)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::APPOINTMENT, 'rowClass'=>'Appointment'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
		$timestamp = Precurio_Date::now()->getTimestamp();
			
		
		$user_id = Precurio_Session::getCurrentUserId();
		
		if( $mine ) 	
		{
		$select = $select->from(array('a' => PrecurioTableConstants::APPOINTMENT))
			->join(array('b' => PrecurioTableConstants::USER_APPOINTMENT),'a.id = b.appointment_id', array('date_assigned'=>'date_created','date_created','user_app_id'=>'id'))				
				->where("a.appointment_date < $timestamp AND b.active = 1  AND b.user_id = $user_id AND a.active = 1")
				->order('a.id desc');
		}
	
		else
		$select = $select->from(array('a' => PrecurioTableConstants::APPOINTMENT))
			->where("a.appointment_date < $timestamp AND a.active = 1")
			->order('a.id desc');
		
								
		$pastAppointments = $table->fetchAll($select);
		
		$this->pastAppointments = array();
		foreach($pastAppointments as $appointment)
			if( ! $appointment->isCurrent() )
				$this->pastAppointments[] = $appointment;
				
		return $this->pastAppointments;
	}
	
	public function getCurrentAppointments() // This refers to all appointments that are scheduled for today whether upcoming or ongoing
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::APPOINTMENT, 'rowClass'=>'Appointment'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
		$today =mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
		$tomorrow = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
		
		$user_id = Precurio_Session::getCurrentUserId();
		
		
		$select = $select->from(array('a' => PrecurioTableConstants::APPOINTMENT, 'rowClass'=>'Appointment'))
			->where("a.appointment_date > $today AND a.appointment_date < $tomorrow AND a.active = 1")
			->order('a.appointment_date desc');
								
				
		$this->currentAppointments = $table->fetchAll($select);
		
		/*
		foreach($appointments as $appointment)
			if( $appointment->isToday() )
				$this->currentAppointments[] = $appointment;
		*/
				
		return $this->currentAppointments;
	}
	
	public function getUpcomingAppointments($mine = false)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::APPOINTMENT, 'rowClass'=>'Appointment'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
		$tomorrow = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
		$timestamp = Precurio_Date::now()->getTimestamp();
				
		$user_id = Precurio_Session::getCurrentUserId();
		
		if( $mine ) 	
		{
			$select = $select->from(array('a' => PrecurioTableConstants::APPOINTMENT))
			->join(array('b' => PrecurioTableConstants::USER_APPOINTMENT),'a.id = b.appointment_id', array('date_assigned'=>'date_created','user_app_id'=>'id'))				
				->where("a.appointment_date > $timestamp AND b.active = 1  AND b.user_id = $user_id AND a.active = 1")
				->order('a.appointment_date desc');
		}
	
		else
		$select = $select->from(array('a' => PrecurioTableConstants::APPOINTMENT))
			->where("a.appointment_date >= $tomorrow AND a.active = 1")
			->order('a.appointment_date desc');
								
		$this->upcomingAppointments = $table->fetchAll($select);
		
		return $this->upcomingAppointments;
	}
	
	
	
	
	public function addAppointment($data)
	{
		if(isset($data['id']))unset($data['id']);//you must do this else lastInsertId wont work
		$msg = "";
		$tr = Zend_Registry::get('Zend_Translate');
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::APPOINTMENT,'rowClass'=>'Appointment'));
		$msg = "";
		try
		{
			$row = $table->createRow($data);
			$id = $row->save();
			if( ($this->addUserAppointment($data['host'], $id)) )
			{			
				if( ! Precurio_Utils::isNull($data['contacts']) )
				{
					$contacts = explode(',' , $data['contacts']);
					
					foreach($contacts as $contact_id)
						$this->addAppointmentContact($id, $contact_id);
				}
			
				if( ! Precurio_Utils::isNull($data['participants']) ) 
				{
					$participants = explode(',' , $data['participants']);
					
					foreach($participants as $part_id)
						$this->addUserAppointment($part_id, $id);
				}
				
				if( ! Precurio_Utils::isNull($data['newcontacts']) )
				{
					$contacts = explode('--' , $data['newcontacts']);
																				
					foreach($contacts as $contact)
					{
						$contact = explode(',' , $contact);
						$cdata['full_name'] = $contact[0];
						$cdata['company'] = $contact[1];
						$cdata['user_id'] = Precurio_Session::getCurrentUserId();
						$cdata['shared'] = Precurio_Utils::isNull($data['participants']) ? 0 : 1;
						
						$cont_id = VisitorUtil::addUserContact($cdata);
						$this->addAppointmentContact($id, $cont_id);
					}						
				}
				$url = "/visitor/index/index/id/{$id}";
				Precurio_Activity::newActivity($data['host'],Precurio_Activity::APPOINTMENT_ADDED,$id,$url);
				$msg = $tr->translate(PrecurioStrings::ADDSUCESS);
			}
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);			
		}
			
		return ($msg);	
	}
	
	public function addUserAppointment($user_id, $appointment_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_APPOINTMENT));
		
		$row = $table->fetchRow($table->select()->where('user_id = ?',$user_id)->where('appointment_id = ?',$appointment_id));
		if($row)return;
		
		$data['user_id'] = $user_id;
		$data['appointment_id'] = $appointment_id;
		$data['date_created'] = Precurio_Date::now()->getTimestamp();
		
		$row = $table->createRow($data);
		return $row->save();
		
	}
	
	public function removeParticipant($id)
	{
			
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_APPOINTMENT));
		$row = $table->fetchRow($table->select()->where('id = ?',$id));
												
		if($row)
		{
			$row->delete(); //delete user to appointment relationship			
		}
		
	}
	
	public function deleteAppointment($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::APPOINTMENT));
			
		$tr = Zend_Registry::get('Zend_Translate');	
		try
		{
			$data['active'] = 0;
			$this->updateAppointment($id, $data);
				
			$msg = $tr->translate(PrecurioStrings::DELETESUCCESS);
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
		}
			
		return ($msg);	
		
	}
	
	public function updateAppointment($appointment_id, $data)
	{
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::APPOINTMENT,'rowClass'=>'Appointment'));
		$tr = Zend_Registry::get('Zend_Translate');	
		try
		{
			$hostChanged = FALSE; // We assume the host has not changed
			
			$appointment = $table->find($appointment_id)->current();
			
			if( isset($data['host']) )
			if ( !Precurio_Utils::isNull($data['host']) && $appointment->host != $data['host'] ) // If the host is changed, there has to be entry for the host in case the host was never registered as a participant
			{
				$newHost = $data['host'];
				$hostChanged = TRUE;
			}
			
			$appointment->setFromArray($data);
			if( $appointment->save() )
				if( $hostChanged )
					$this->addUserAppointment($newHost, $appointment_id);
			
			$tr->translate($msg = PrecurioStrings::EDITSUCESS);			
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
		}
		return $msg;
	}
	
	public function getAppointment($appointment_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::APPOINTMENT, 'rowClass'=>'Appointment'));
		$select = $table->select(false);
		$select->setIntegrityCheck(false);
		$select->setTable($table);		
					
		$select = $select->from(array('a' => PrecurioTableConstants::APPOINTMENT))						
						->where("a.id = $appointment_id");
		$appointment = $table->fetchRow($select);
				
		
		return $appointment;
	}
	
	public function getUserAppointment($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::APPOINTMENT, 'rowClass'=>'Appointment'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
		
		$select = $select->from(array('a' => PrecurioTableConstants::APPOINTMENT))
			->join(array('b' => PrecurioTableConstants::USER_APPOINTMENT),'a.id = b.appointment_id', array('a_id'=>'id', 'user_id', 'appointment_id'))				
				->where("b.id = $id");
				
		return $table->fetchRow($select);
				
	}
	
	
	public function getStaffAppointments($staff_id, $all=TRUE)
	{
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::APPOINTMENT, 'rowClass'=>'Appointment'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
		$timestamp = Precurio_Date::now()->getTimestamp();
			
		
		
		$select = $select->from(array('a' => PrecurioTableConstants::APPOINTMENT))
			->join(array('b' => PrecurioTableConstants::USER_APPOINTMENT),'a.id = b.appointment_id', array('date_assigned'=>'date_created','user_app_id'=>'id'))				
				->where("a.appointment_date > $timestamp AND b.active = 1  AND b.user_id = $staff_id")
				->order('a.id desc');
						
		return $table->fetchAll($select);	
		
	}
	
		
	public function addAppointmentContact($a_id, $c_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR));
		
		$row = $table->fetchRow($table->select()->where('contact_id = ?',$c_id));
		if($row)
		$v_id = $row->id;
		else
		{
			$data['contact_id'] = $c_id;			
			$data['date_created'] = Precurio_Date::now()->getTimestamp();
		
			$row = $table->createRow($data);
			
			$id = $row->save();
			if($id)
			$v_id = $id;
			else return;			
		}
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR_APPOINTMENT));
		
		$row = $table->fetchRow($table->select()->where('visitor_id = ?',$v_id)->where('appointment_id = ?',$a_id));
		if($row) return;
		
		$data = array();
		$data['visitor_id'] = $v_id;
		$data['appointment_id'] = $a_id;
		$data['date_created'] = Precurio_Date::now()->getTimestamp();
		
		$row = $table->createRow($data);
			
		$row->save();
		
	}
	
	public function removeAppointmentContact($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR_APPOINTMENT));
		$row = $table->fetchRow($table->select()->where('id = ?',$id));
												
		if($row)
		{
			$row->delete(); //delete visitor to appointment relationship			
		}
	}
	
		
}
?>