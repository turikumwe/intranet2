<?php
require_once ('contact/models/vo/Contact.php');
class Contacts 
{

		const TYPE_ALL = -1;
		const TYPE_MY = 0;
		const TYPE_SHARED = 1;
		const TYPE_CO = 2;
		
		private $myContacts;
		private $sharedContacts;
		private $coWorkers;
		
		public function getCount($type)
		{
			switch ($type)
			{
				case self::TYPE_MY:
					$count = count($this->getMyContact());
					break;
				case self::TYPE_SHARED:
					$count = count($this->getSharedContact());
					break;
				case self::TYPE_CO:
					$count = count($this->getCoWorkers());
					break;
				case self::TYPE_ALL:
					$count = count($this->getMyContact()) + count($this->getSharedContact()) + count($this->getCoWorkers());
					break;
				
			}
			return $count;	
		}
		
		public function getContactSummary($type, $page = 1)
		{
			switch ($type)
			{
				case self::TYPE_MY:
					$contacts = $this->getMyContact();
					break;
				case self::TYPE_SHARED:
					$contacts = $this->getSharedContact();
					break;
				case self::TYPE_CO:
					$contacts = $this->getCoWorkers();
					break;
				case self::TYPE_ALL:
					$contacts = array_merge($this->getMyContact()->toArray(),$this->getSharedContact()->toArray(), $this->getCoWorkers()->toArray());
					usort($contacts, 'self::sortFn');
					break;
			}
			
			$per_page = 10;
			$start = ($page-1)*$per_page;
			if(!is_array($contacts))
				$contacts = $contacts->toArray();
			
			$contacts = array_splice($contacts,$start,$per_page);
			return $contacts;
			
		}
	private function sortFn($x, $y)
	{
		 if ( $x['full_name'] == $y['full_name'] )
		  return 0;
		 else if ( $x['full_name'] < $y['full_name'] )
		  return -1;
		 else
		  return 1;
	}
		
		public function getContactDetails($id,$type)
		{
			if($type == self::TYPE_CO)
			{
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'Contact'));
				return $table->fetchRow($table->select(false)
								->from(PrecurioTableConstants::USERS,array('id','user_id','first_name','last_name','work_phone',
								'mobile_phone','email','job_title','address','website','full_name'=>'concat (first_name," " ,last_name)'))
								->where('id = ?',$id)
								->order('first_name ASC'));
				
			}
			else
				return $this->getContact($id);	
		}
		
		
		public function addContact ($data)
		{
			$user_id = Precurio_Session::getCurrentUserId();
			$data['user_id'] = $user_id;
			if(isset($data['id']))unset($data['id']);//you must do this else lastInsertId wont work
			$msg = "";
			$tr = Zend_Registry::get('Zend_Translate');
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTACTS));
			try
			{
				$row = $table->createRow($data);
				$id = $row->save();
				$msg = $tr->translate(PrecurioStrings::ADDSUCESS);
				if($data['shared'] == 1)//if it is a shared contact, add activity
				{
					$url = '/contact/list/all/type/'.self::TYPE_SHARED;
					Precurio_Activity::newActivity($user_id,Precurio_Activity::SHARED_CONTACT,$id,$url);	
				}
				$dict = new Precurio_Search();
				$dict->indexContact($id);
				
    	
			}
			catch (ZendX_Console_Exception $e)
			{
				$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			}
			
						
			return ($msg);
			
		}
		public function updateContact($id,$data)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTACTS,'rowClass'=>'Contact'));
			$tr = Zend_Registry::get('Zend_Translate');
			try
			{
				$contact = $table->find($id)->current();
				$contact->setFromArray($data);
				$contact->save();
				$msg = $tr->translate(PrecurioStrings::EDITSUCESS);
				$dict = new Precurio_Search();
				$dict->indexContact($contact);
			}
			catch (Exception $e)
			{
				$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			}
			return $msg;
		}
		
		public function deleteContact($id)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTACTS));
			
			$tr = Zend_Registry::get('Zend_Translate');
			try
			{
				$contact =  $table->fetchRow($table->select()
								->where('id = ? ',$id));
				$contact['active'] = 0; 
				$contact->save();
				$msg = $tr->translate(PrecurioStrings::DELETESUCCESS);
			}
			catch (Exception $e)
			{
				$msg = $tr->translate(PrecurioStrings::ERROR_PERFORMING_OPERATION);
			}
			
			return ($msg);	
			
		}
		public  function getMyContact()//we are assuming they cant have u
		{
			if(!Precurio_Utils::isNull($this->myContacts))
				return $this->myContacts;
			$user_id = Precurio_Session::getCurrentUserId();
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTACTS,'rowClass'=>'Contact'));
			$this->myContacts = $table->fetchAll($table->select()
								->where('user_id= ? ',$user_id)
								->where('active= 1 ')
								->order('full_name ASC'));
			return $this->myContacts;
				
			
		}
		public function getSharedContact()
		{
			if(!Precurio_Utils::isNull($this->sharedContacts))
				return $this->sharedContacts;
			$user_id = Precurio_Session::getCurrentUserId();
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTACTS,'rowClass'=>'Contact'));
			$this->sharedContacts = $table->fetchAll($table->select()
								->where('user_id <> ? ',$user_id)
								->where('shared = 1')
								->where('active= 1 ')
								->order('full_name ASC'));
			return $this->sharedContacts;
		}
		
		public function getCoWorkers()
		{
			if(!Precurio_Utils::isNull($this->coWorkers))
				return $this->coWorkers;
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'Contact'));
			$this->coWorkers = $table->fetchAll($table->select(false)
								->from(PrecurioTableConstants::USERS,array('id','user_id','first_name','last_name','work_phone',
								'mobile_phone','email','job_title','address','website','full_name'=>'concat (first_name," " ,last_name)'))
								->where('active = 1')
								->order('first_name ASC'));
			return $this->coWorkers;
		}
		/**
		 * @param $id int
		 * @return Contact
		 */
		public static function getContact($id)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTACTS,'rowClass'=>'Contact'));
			$contact = $table->find($id);
			return $contact->current();
		}

	
}

?>