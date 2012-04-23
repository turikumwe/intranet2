<?php

class SuggestedContents {
	
	public $contents;
	public function __construct()
	{
		if($this->contents == null)
		{	
			$ns = new Zend_Session_Namespace(Precurio_Session::NAMESPACE_CONTENT);
			if(isset($ns->suggested))
			{
				$this->contents = $ns->suggested;
				shuffle($this->contents);
			}
			else
				$this->getSuggestedContents();
		}
		return $this;
	}
	
	private function getSuggestedContents()
	{
		
		$temp = $this->getBestContents();
		$num = ($temp->count() > 5) ? 5 : $temp->count();
		
		$this->contents = array();
		$chosen = array();
		for($i=0; $i< $num; $i++)
		{
			
			do
			{
				$pos = rand(0,$temp->count()-1);
			}while(array_search($pos,$chosen) !== false);
			
			$chosen[] = $pos;
			$this->contents[] = $temp->getRow($pos);	
		}
		
		$ns = new Zend_Session_Namespace(Precurio_Session::NAMESPACE_CONTENT);
		$ns->setExpirationSeconds(3600);//expire after 1 hr
		$ns->suggested = $this->contents;
		
	}
	/**
	 * @param $num int OPTIONAL default=20
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	private function getBestContents($num=20)
	{
//		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT,'rowClass'=>'Content'));
//		
//		$select = $table->select();
//		$select->setIntegrityCheck(false);
//		$select->setTable($table); 
//		$select =  $select->from(array('a' => PrecurioTableConstants::CONTENT))
//						->join(array('b' => PrecurioTableConstants::COMMENTS),'a.id = b.content_id',array('content_id','num_of_comments'=>'count(*)'))
//						->where('active=1')
//						->where('title <> ""')
//						->group('b.content_id')
//						->having('b.content_id <> 0')
//						->order('rating DESC')
//						->order('num_of_comments DESC')
//						->order('num_of_hits DESC')
//						->order('last_updated DESC')
//						->limit($num);
//		
//		$rows = $table->fetchAll($select);
//		if($rows->count() < 1)
//		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT,'rowClass'=>'Content'));
			$rows = $table->fetchAll($table->select()->where('active=1')
						->where('title <> ""')
						->order('rating DESC')
						->order('num_of_hits DESC')
						->order('last_updated DESC')
						->limit($num));
//		}
		
		if($num == 1)return $rows->current();
		return $rows;
	}
	/**
	 * @param $num int OPTIONAL default=5
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getMostRead($num=5)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT,'rowClass'=>'Content'));
		$row = $table->fetchAll($table->select()->where('active=1')->where('title <> ""')
						->order('num_of_hits DESC')
						->limit($num));
		if($num == 1)return $row->current();
		return $row;
	}
	/**
	 * @param $num int OPTIONAL default=5
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getMostRecent($num=5)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT,'rowClass'=>'Content'));
		$row = $table->fetchAll($table->select()->where('active=1')->where('title <> ""')
						->order('last_updated DESC')
						->limit($num));
		if($num == 1)return $row->current();
		return $row;
	}
	/**
	 * @param $num int OPTIONAL default=5
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getMostDiscussed($num=5)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NEWS, 'rowClass'=>'Content'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::COMMENTS),'a.id = b.content_id',array('content_id','num_of_comments'=>'count(*)'))
						->where('a.active = 1')
						->where('a.title <> ""')
						->group('b.content_id')
						->having('b.content_id <> 0')
						->order('num_of_comments DESC')
						->limit($num);
		$row = $table->fetchAll($select);
		if($num == 1)return $row->current();
		return $row;
	}
	/**
	 * @param $num int OPTIONAL default=5
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getMostRated($num=5)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT,'rowClass'=>'Content'));
		$row = $table->fetchAll($table->select()->where('active=1')->where('rating > 0')->where('title <> ""')
						->order('rating DESC')
						->limit($num));
		if($num == 1)return $row->current();
		return $row;
	}

}

?>