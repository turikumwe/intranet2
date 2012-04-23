<?php

require_once ('Zend/Db/Table/Row/Abstract.php');

class Settings extends Zend_Db_Table_Row_Abstract {
	
	/**
	 * @return Boolean
	 */
	public function contentRequiresApproval()
	{
		return $this->content_requires_approval;
	}

}

?>