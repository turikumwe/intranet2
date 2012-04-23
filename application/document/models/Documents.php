<?php
require_once ('document/models/vo/Document.php');
class Documents {
	
	/**
	 * @param $content_id int
	 * @return Document
	 */
	public static function getDocument($content_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DOCUMENTS,'rowClass'=>'Document'));
		$rows = $table->fetchAll($table->select()->where('content_id = ?',$content_id));
		$c = $rows->count();
		$tr = Zend_Registry::get('Zend_Translate');
		if($c == 0)
			throw new Precurio_Exception($tr->translate(PrecurioStrings::INVALIDCONTENT),Precurio_Exception::EXCEPTION_INVALID_CONTENT);
		$doc = $rows->getRow($c-1);
		return $doc;
	}
}

?>