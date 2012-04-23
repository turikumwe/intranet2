<?php
require_once ('visitor/models/vo/TelMessage.php');
class TelMessages
{
	private $telMessages;
	
	public function getTelephoneMessageSummary($page = 1, $mine=false)
	{		
		$telMessages = $this->merge($this->getTelephoneMessages($mine));
		
		$per_page = 10;
		$start = ($page-1)*$per_page;
					
		$telMessages = array_splice($telMessages,$start,$per_page);
		return $telMessages;	
		
	}
	
	public function getCount($mine=false)
	{			
		return count($this->getTelephoneMessages($mine));	
	}
	
	public function getTelephoneMessages($mine=false)
	{
		if(!Precurio_Utils::isNull($this->telMessages) )
				return $this->telMessages;
			
		$timestamp = Precurio_Date::now()->getTimestamp();
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TELEPHONE_MESSAGES, 'rowClass'=>'TelMessage'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
			
		$user_id = Precurio_Session::getCurrentUserId();
		$where = ( $mine ) ? "a.active = 1 AND logged_for = $user_id" : "a.active = 1";
		$select = $select->from(array('a' => PrecurioTableConstants::TELEPHONE_MESSAGES))
						->join(array('b' => PrecurioTableConstants::USERS),'a.logged_by = b.user_id',array('attendant_fname'=>'first_name','attendant_lname'=>'last_name'))
						->join(array('c' => PrecurioTableConstants::USERS),'a.logged_for = c.user_id',array('target_fname'=>'first_name','target_lname'=>'last_name'))						
						->join(array('d' => PrecurioTableConstants::CONTACTS), 'a.contact_id = d.id', array('full_name', 'company'))
						->where($where)
						->order("a.id desc");
						
		$this->telMessages = $table->fetchAll($select);		
		return $this->telMessages;
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
	
	public function addTelephoneMessage($data)
	{
		if(isset($data['id']))unset($data['id']);//you must do this else lastInsertId wont work
		$msg = "";
		$tr = Zend_Registry::get('Zend_Translate');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TELEPHONE_MESSAGES,'rowClass'=>'TelMessage'));
		$msg = "";
		try
		{
			if(Precurio_Utils::isNull($data['contact_id']))
			{				
				$cdata['full_name'] = $data['contact_name'];
				$cdata['user_id'] = Precurio_Session::getCurrentUserId();
				$cdata['company'] = $data['contact_company'];
					
				$data['contact_id'] = Visitor_Util::addUserContact($cdata);
			}
			
			$row = $table->createRow($data);
			$id = $row->save();
			if($id)
			{		
				//Precurio_Activity::newActivity($data['logged_for'],Precurio_Activity::TELEPHONE_MESSAGE_LOGGED,$id,'')	;
				$msg = $tr->translate(PrecurioStrings::ADDSUCESS);
			}
			else $msg = $id;
						
		}
		catch (Exception $e)
		{
			$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);;		
		}
		
		return ($msg);	
	}
	
	public function getTelMessage($msg_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::TELEPHONE_MESSAGES, 'rowClass'=>'TelMessage'));
		$select = $table->select(false);
		
		$select->setTable($table)->setIntegrityCheck(false);
		
		$select = $select->from(array('a' => PrecurioTableConstants::TELEPHONE_MESSAGES))
						->join(array('b' => PrecurioTableConstants::USERS),'a.logged_by = b.user_id',array('attendant_fname'=>'first_name','attendant_lname'=>'last_name'))
						->join(array('c' => PrecurioTableConstants::USERS),'a.logged_for = c.user_id',array('target_fname'=>'first_name','target_lname'=>'last_name'))						
						->join(array('d' => PrecurioTableConstants::CONTACTS), 'a.contact_id = d.id')	
						->where("a.id = $msg_id");
				
		return $table->fetchRow($select);
	}	
	
}
?>
