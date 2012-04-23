<?php
/**
 *
 * @author Brain
 * @version 
 */
require_once ('news/models/MyNews.php');
require_once ('news/models/vo/News.php');
require_once 'Zend/View/Interface.php';

/**
 * News helper
 *
 * @uses viewHelper News_View_Helper
 */
class News_View_Helper_NewsView {
	
	public $myNews;
	
	/**
	 * @var Zend_View_Interface 
	 */
	public $view;
	
	/**
	 *  
	 */
	
	public function newsView() {
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NEWS,'rowClass'=>'News','rowsetClass'=>'MyNews'));
		$this->myNews = new MyNews();
		return $this;
	}
	public function getRecent($num=10)
	{
		return $this->myNews->getRecent($num);
	}
	private $recentNews;
	public function getMostRecent()
	{
		if(Precurio_Utils::isNull($this->recentNews))$this->recentNews = MyNews::getMostRecent();
		return $this->recentNews;
	}
	public function getMostCommented($num=10)
	{
		return $this->myNews->getMostCommented($num);
	}
	public function getMostRead($num=10)
	{
		return $this->myNews->getMostRead($num);
	}
	public function __call($method, $args) {
     	return call_user_func_array(array($this->myNews, $method), $args);
    }
	/**
	 * Sets the view field 
	 * @param $view Zend_View_Interface
	 */
	public function setView(Zend_View_Interface $view) {
		$this->view = $view;
	}
}
