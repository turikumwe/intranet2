<?php

/**
 * Contact
 *  
 * @author Brain
 * @version 
 */

require_once 'Zend/Db/Table/Row/Abstract.php';

class Contact extends Zend_Db_Table_Row_Abstract {

/**Returns the user id.
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}
	/**
	 * First name
	 * @return string
	 */
	public function getFirstName($thirdPerson = false)
	{
		
	}
	public function getFullName()
	{
		return $this->full_name; 
	}
	/**Last name
	 * @return string
	 */
	public function getLastName()
	{
	
	}
	public function getWorkPhone()
	{
		return $this->work_phone;
	}
	public function getMobilePhone()
	{
		return $this->mobile_phone;
	}
	public function getAddress()
	{
		return $this->address;
	}
	public function getDateCreated()
	{
		return $this->date_created;
	}
	public function getLastUpdated()
	{
		return $this->last_updated;
	}
	public function getJobTitle()
	{
		return $this->job_title;
	}
	public function getFax()
	{
		return $this->getFax();
	}
	public function getWebsite()
	{
		return $this->website;
	}
	public function getCompanyName()
	{
		return $this->company;
	}
	public function toCSVString()
	{
		$data = array($this->full_name,$this->company,$this->email,$this->job_title, 
		$this->address,$this->mobile_phone,$this->work_phone,$this->fax,$this->website
		); 
		array_walk($data,'self::pdStr');
		return implode(",",$data);
		
	}
	private function pdStr(&$value,$key)
	{
		$value = ltrim($value);
		if($value[0] == '+' && stripos($value,' ')===FALSE)
		{
			str_ireplace('-',' ',$value);
			$i = stripos($value,'-');
			$value[$i] = ' ';
		}
		$value = '"'.(string)$value.'"';	
	}
}
