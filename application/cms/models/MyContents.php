<?php
require_once ('cms/models/vo/Article.php');
require_once ('cms/models/vo/Advert.php');
require_once ('cms/models/vo/Fact.php');
require_once ('cms/models/vo/Category.php');
class MyContents {
	
	private $_promotionalArticles;
	private $_featuredArticles;
	private function getFeaturedArticles($num=10)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ARTICLES,'rowClass'=>'Article'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::ARTICLES),'a.id = b.content_id',array('content_id'))
						->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.active=1')
						->where('b.active=1')
						->where('b.is_featured=1')
						->order('id DESC')
						->limit($num);
		$articles = $table->fetchAll($select);
			
		return $articles;
	}
	private function getPromotionalArticles()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ARTICLES,'rowClass'=>'Article'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::ARTICLES),'a.id = b.content_id',array('content_id'))
						->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.active=1')
						->where('b.active=1')
						->order('id DESC')
						->limit();
		$articles = $table->fetchAll($select);
		return $articles;
	}
	/**
	 * Gets a promotional article in position $position
	 * @param $position int - Identifies which promotional articles you want, a value of 0 means the most recent one
	 * @return Article
	 */
	public function getPromotionalArticle($position=0)
	{
		if(Precurio_Utils::isNull($this->_promotionalArticles))
			$this->_promotionalArticles = $this->getPromotionalArticles();
		
		if(count($this->_promotionalArticles)== 0 || count($this->_promotionalArticles) <= $position)return null;
		if(count($this->_promotionalArticles)== 0)return null;
		if($position == 0)$position = rand(0,count($this->_promotionalArticles)-1);
		$article = $this->_promotionalArticles[$position];
		if(!$article->isActive())return null;
		return $article;
	}
	/**
	 * Gets a featured article in position $position
	 * @param $position int - Identifies which featured articles you want, a value of 0 means the most recent one
	 * @return Article
	 */
	public function getFeaturedArticle($position=0)
	{
		if(Precurio_Utils::isNull($this->_featuredArticles))
			$this->_featuredArticles = $this->getFeaturedArticles();
		if(count($this->_featuredArticles)== 0 || count($this->_featuredArticles) <= $position )return null;
		$article = $this->_featuredArticles[$position];
		if(!$article->isActive())return null;
		return $article;
	}
	/**Gets the advert that should appear on the home page
	 * @return Advert
	 */
	public function getWelcomeAdvert()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ADVERTS,'rowClass'=>'Advert'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::ADVERTS),'a.id = b.content_id',array('content_id'))
						->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.active=1')
						->where('b.active=1')
						->where('b.is_welcome_advert=1')
						->order('id DESC');
		$advert = $table->fetchRow($select);
		if(Precurio_Utils::isNull($advert))return null;
		
		return $advert;
	}
	/**
	 * Randomly pics an advert as  long as it is not a sliding advert
	 * @return Advert
	 */
	public function getAdvert()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ADVERTS,'rowClass'=>'Advert'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::ADVERTS),'a.id = b.content_id',array('content_id'))
						->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.active=1')
						->where('b.active=1')
						->where('b.can_slide=0')
						->order('id DESC');
		$adverts = $table->fetchAll($select);
		if($adverts->count() == 0)return null;
		
		$pos = 0;
		if($adverts->count() > $pos)
			$pos = rand($pos,$adverts->count()-1);
		return $adverts->getRow($pos);
	}
	/**Gets the advert that should slide on the home page
	 * @return Advert
	 */
	public function getSlidingAdverts()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ADVERTS,'rowClass'=>'Advert'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::ADVERTS),'a.id = b.content_id',array('content_id'))
						->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.active=1')
						->where('b.active=1')
						->where('b.can_slide=1')
						->order('id DESC');
		$adverts = $table->fetchAll($select);
		if(count($adverts)== 0)return null;
		return $adverts;
	}
	/**
	 * @param $id int $id of the object you are finding
	 * @return Content
	 */
	public static function getContent($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT,'rowClass'=>'Content'));
		$content = $table->find($id);
		return $content->current();
	}
	public static function increaseHits($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT,'rowClass'=>'Content'));
		$content = $table->find($id)->current();
		$content->num_of_hits++;
		$content->save();
	}
	public function getAll()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT,'rowClass'=>'Content'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.active=1')
						->where('a.title <> ?',"")
						->where('a.is_photo = 0')
						->where('a.is_public = 1')
						->order('a.id DESC');
		$contents = $table->fetchAll($select);	
		return $contents;	
	}
	public function getGroupContent($group_id,$publicOnly=false)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT,'rowClass'=>'Content'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
						->join(array('b' => PrecurioTableConstants::GROUP_CONTENTS),'a.id = b.content_id',array())
						->join(array('c' => PrecurioTableConstants::USERS),'a.user_id = c.user_id',array('first_name','last_name','profile_picture_id'))
						->where('a.active=1')
						->where('b.group_id = ?',$group_id)
						->where('b.active=1')
						->where('a.title <> ?',"")
						->where('a.is_photo = 0')
						->order('a.id DESC');
		if($publicOnly)
			$select = $select->where('a.is_public=1');
			
		$contents = $table->fetchAll($select);	
		return $contents	;
	}
	
	public function getFacts()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::FACTS,'rowClass'=>'Fact'));
		$facts = $table->fetchAll($table->select()->where('active= ? ',1)
													->order('id DESC'));
		return $facts;											
	}
	
	public function getLinks()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LINKS));
		return $table->fetchAll($table->select()->where('active= ? ',1)
													->order('id DESC'));
	}
	
	public function getRss()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::RSS));
		return $table->fetchAll($table->select()->where('active= ? ',1)
													->order('id DESC'));
	}
	

}

?>