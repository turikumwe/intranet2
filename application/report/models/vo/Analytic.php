<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
class Analytic extends Zend_Db_Table_Row_Abstract {
	
	function getParam($key)
	{
		try
		{
			$params = @unserialize($this->params);
			
			
			if($params == false)
				$params = @unserialize(base64_decode($this->params));
		}
		catch(Exception $e)
		{
			return null;
		}
		return  isset($params[$key]) ? $params[$key] : null;
	}
}

?>