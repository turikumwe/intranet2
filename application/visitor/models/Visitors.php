<?php
require_once ('visitor/models/vo/Visitor.php');
require_once ('visitor/models/Appointments.php');
require_once('visitor/models/VisitorStrings.php');
class Visitors
{
		
	private $visitors;
	private $whoIsIn;
	
	public function getVisitorSummary($page = 1, $mine = false)
	{
		$visitors = $this->merge($this->getVisitors($mine));
		
		if($mine)
		{
			$expected = array();
			$unexpected = array();
			
			foreach($visitors as $visitor)
			{
				if($visitor->isExpected())				
				$expected[] = $visitor;				
				else
				$unexpected[] = $visitor;
			}
			
			$visitors = array_merge($expected, $unexpected);
		}
		
		$per_page = 10;
		$start = ($page-1)*$per_page;
					
		$visitors = array_splice($visitors,$start,$per_page);
		return $visitors;
			
		
	}
	
	public function whoIsIn($page = 1)
	{
				
		$whoIsIn = $this->merge($this->getWhoIsIn());
		
		$per_page = 4;
		$start = ($page-1)*$per_page;
					
		$whoIsIn = array_splice($whoIsIn,$start,$per_page);
		return $whoIsIn;	
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
	
	public function getCount($mine=false)
	{		
		return count($this->getVisitors($mine));
	}
	
	public function countIn($mine=false)
	{
		return count($this->whoIsIn($mine));
	}
	
	public function addVisitor($data)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR,'rowClass'=>'Visitor'));
		$tr = Zend_Registry::get('Zend_Translate');
		$msg = "";
		try
		{
			$row = $table->createRow($data);
			$id = $row->save();
			if($id)
			{
				Precurio_Activity::newActivity($data['who_to_see'],Precurio_Activity::VISITOR_LOGGED_IN,$id,'')	;
				$msg = $tr->translate(PrecurioStrings::ADDSUCESS);
			}
			else $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);			
		}
			
		return ($msg);	
	}
	
	public function getWhoIsIn()
	{
				
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR_APPOINTMENT, 'rowClass'=>'Visitor'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
			
		$select = $select->from(array('a' => PrecurioTableConstants::VISITOR_APPOINTMENT))
			->join(array('b' => PrecurioTableConstants::VISITOR),'a.visitor_id = b.id', array('DOB', 'car_reg_number', 'emergency_contact'))	
			->join(array('c' => PrecurioTableConstants::CONTACTS),'b.contact_id = c.id', array('full_name', 'company'))
			->join(array('d' => PrecurioTableConstants::APPOINTMENT),'a.appointment_id = d.id', array('host', 'appointment_date', 'title'))
			->join(array('e' => PrecurioTableConstants::USERS), 'd.host = e.user_id', array('first_name', 'last_name'))
			->where("a.time_in IS NOT NULL AND a.time_out IS NULL");
			
		$this->whoIsIn = array();		
		$whoIsIn = $table->fetchAll($select);
		
		foreach($whoIsIn as $visitor)
		{
			$date = new Precurio_Date($visitor->appointment_date);		
			if($date->isToday())
				$this->whoIsIn[] = $visitor;
		}
			
		return $this->whoIsIn;
	}
	
	/**
	 * Returns all visitors hosted or logged in by you. (For all visitors in the system use VisitorUtil::getAllVisitors())
	 * @param $mine flag to indicate if you need all visitors hosted by you (true), or all visitors logged in by you (false)
	 * @return Zend_Db_Table_Rowset
	 */
	public function getVisitors($mine=false)
	{
		$user_id = Precurio_Session::getCurrentUserId();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR_APPOINTMENT, 'rowClass'=>'Visitor'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
			
		$select = $select->distinct()->from(array('a' => PrecurioTableConstants::VISITOR_APPOINTMENT), array())
			->join(array('b' => PrecurioTableConstants::VISITOR),'a.visitor_id = b.id', array('id'))	
			->join(array('c' => PrecurioTableConstants::CONTACTS),'b.contact_id = c.id', array('full_name', 'company'))
			->join(array('d' => PrecurioTableConstants::APPOINTMENT),'a.appointment_id = d.id', array());
						
			if($mine)
			$select = $select->where("c.user_id = $user_id AND b.active = 1");
			else
			$select = $select->where("a.logged_by = $user_id AND b.active = 1 AND a.time_in IS NOT NULL");
				
		$this->visitors = $table->fetchAll($select);
		return $this->visitors;
	}
	
			
	public function updateVisitor($visitor_id, $data)
	{
		unset($data['id']);
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR,'rowClass'=>'Visitor'));
		$tr = Zend_Registry::get('Zend_Translate');	
		try
		{
			$visitor = $table->find($visitor_id)->current();
			$visitor->setFromArray($data);
			$visitor->save();
			$msg = $tr->translate(PrecurioStrings::EDITSUCESS);			
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
		}
		return $msg;
	}
	
	public function getVisit($visit_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR_APPOINTMENT, 'rowClass'=>'Visitor'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
			
		$select = $select->from(array('a' => PrecurioTableConstants::VISITOR_APPOINTMENT))
			->join(array('b' => PrecurioTableConstants::VISITOR),'a.visitor_id = b.id', array('DOB', 'car_reg_number', 'emergency_contact'))	
			->join(array('c' => PrecurioTableConstants::CONTACTS),'b.contact_id = c.id', array('full_name', 'company'))	
			->join(array('d' => PrecurioTableConstants::APPOINTMENT),'a.appointment_id = d.id', array('host', 'purpose', 'purpose_detail', 'appointment_date'))
			->join(array('e' => PrecurioTableConstants::USERS), 'd.host = e.user_id', array('first_name', 'last_name'))
			->where("a.id = $visit_id");
				
		return $table->fetchRow($select);	
	}
	
	public function getVisitor($visitor_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR, 'rowClass'=>'Visitor'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
			
		$select = $select->from(array('a' => PrecurioTableConstants::VISITOR))				
			->join(array('b' => PrecurioTableConstants::CONTACTS),'a.contact_id = b.id', array('full_name', 'company', 'contact_id'=>'id', 'user_id'))
			
			->where("a.id = $visitor_id");
		//return $select->__toString();
		
		return $table->fetchRow($select);
	}
	
	public function deleteVisitor($visitor_id)
	{
		$data['active'] = 0;
		$this->updateVisitor($visitor_id, $data);
		
		return "visitor has been deleted";
	}
	
	public function logVisitorIn($id, $v_data, $v_app_data) // performs an update on the visitor and visitor_appointment table
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR_APPOINTMENT,'rowClass'=>'Visitor'));
		$tr = Zend_Registry::get('Zend_Translate');	
		try
		{
			$v_app_data['logged_by'] = Precurio_Session::getCurrentUserId();
			$v_app = $table->find($id)->current();
			
			/* Set appointment as started if it hasnt been started */
			$a_data['status'] = 1;
			$appointments = new Appointments();
			$appointments->updateAppointment($v_app->appointment_id, $a_data);
			
			
			$v_app->setFromArray($v_app_data);
			
			if( $v_app->save() )
			{
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR,'rowClass'=>'Visitor'));
				
				$v = $table->find($v_app->visitor_id)->current();
				$v->setFromArray($v_data);
				$v->save();
			}
			
			$msg = $tr->translate(PrecurioStrings::EDITSUCESS);			
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
		}
		return $msg;
	}
	
	public function logVisitorOut($id, $data)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::VISITOR_APPOINTMENT,'rowClass'=>'Visitor'));
		$tr = Zend_Registry::get('Zend_Translate');	
		try
		{
			$v = $table->find($id)->current();
			$v->setFromArray($data);
			
			$v->save();
			$msg = $tr->translate(PrecurioStrings::EDITSUCESS);
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
		}
		return $msg;
	}	
	
	
}
?>