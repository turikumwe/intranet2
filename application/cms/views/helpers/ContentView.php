<?php
/**
 *
 * @author Brain
 * @version 
 */
require_once 'Zend/View/Interface.php';
require_once ('cms/models/MyContents.php');
require_once ('cms/models/SuggestedContents.php');
require_once ('cms/models/vo/Fact.php');
/**
 * ContentViews helper
 *
 * @uses viewHelper Cms_View_Helper
 */
class Cms_View_Helper_ContentView {
	public $translate;
	/**
	 * @var MyContents
	 */
	private $_myContent;
	/**
	 * @var Zend_View_Interface 
	 */
	public $view;
	
	/**
	 *  
	 */
	public function contentView() {
		$this->_myContent = new MyContents();
		$registry = Zend_Registry::getInstance();
		$this->translate = $registry->get('Zend_Translate');
		return $this;
	}
	/**
	 * Gets a promotional article in position $position
	 * @param $position int - Identifies which promotional articles you want, a value of 0 means the most recent one
	 * @return String
	 */
	public function getPromotionalArticle($position=0)
	{
		
		$article =  $this->_myContent->getPromotionalArticle($position=0);
		if(Precurio_Utils::isNull($article))return "<div id='noRecords'>
	                               	".$this->translate('No intranet content yet')."<br />
	                               </div>";
		
		$baseUrl = Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
		$root = Zend_Registry::get('root');
		
		$content = "<br/><span class='header'><a href='#'>".$article->getTitle()."</a></span>";
		
		if(!file_exists($root.'/public/'.$article->getImagePath()))
			$content.='';
		else
			$content.="<img src='".$baseUrl.$article->getImagePath()."' width='271' height='156'/>";
		
		$content .= "<br />
						".$this->translate($article->getSummary())."
           	   		<br /> ". "<a href='#' class='readmore'><strong>".$this->translate('read more')."...</strong></a>" ;
		return $content;
	}
	/**
	 * Gets a featured article in position $position
	 * @param $position int - Identifies which featured articles you want, a value of 0 means the most recent one
	 * @return String
	 */
	public function getFeaturedArticle($position=0)
	{
		$article =  $this->_myContent->getFeaturedArticle($position);
		if(Precurio_Utils::isNull($article))return "";
		$baseUrl = Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
		$root = Zend_Registry::get('root');
		$content = "";
		if(is_file($root.'/public/'.$article->getImagePath()))
			$content .= "<img src='".$baseUrl.$article->getImagePath()."' height='150'/>";
		$content .= 	$article->getSummary().
           	   		"<br /> <p align='right'>  <span class='readmore'>". 
           	   		"<a href='{$baseUrl}/cms/view/details/c_id/".$article->getId()."'>".$this->translate('read more')."...</a></span></p>" ;
		return $content;
	}
	/**Gets the advert that should appear on the home page
	 * @return String
	 */
	public function getWelcomeAdvert()
	{
		$advert = $this->_myContent->getWelcomeAdvert();
		if(Precurio_Utils::isNull($advert))
			return '<div  id="home_banner" > '.$this->getWelcomeText() . '</div>';
		
		//disregard welcome text, if not using default banner
		return '<img src="'. $advert->getImagePath().'"/>';
	}
	public function getAdvert()
	{
		$advert = $this->_myContent->getAdvert();
		return $advert;
	}
	private function getWelcomeText()
	{
		$config = Zend_Registry::get('config');
		$welcomeText = isset($config->welcome_text) ? $this->translate($config->welcome_text) : $this->translate('Welcome to your intranet');
		return '<div id="bannerText">'.$welcomeText.'</div>'; 
	}
	/**Gets the advert that should slide on the home page
	 * @return Advert
	 */
	public function getSlidingAdverts()
	{
		$adverts = $this->_myContent->getSlidingAdverts();
		if(Precurio_Utils::isNull($adverts))return '';
		$content = '<div id="slider">
                		<ul>';
		$baseUrl = Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
		foreach($adverts as $advert)
		{
			$content .= '<li> <img src="'.$baseUrl.$advert->getImagePath().'"/></li>';
		}
		return $content.'</ul>
                </div>';
	}
	public function getCompanyLinks()
	{
		return $this->_myContent->getLinks();
	}
	public function getCompanyFact()
	{
		$facts = $this->_myContent->getFacts();
		if($facts->count() < 1)return null;
		$i = rand(1,$facts->count());
		return $facts->getRow($i-1);
	}
	public function getSuggestedContents()
	{
		$suggested = new SuggestedContents();
		return $suggested->contents;
	}
	public function getMostRecent($num=5)
	{
		$suggested = new SuggestedContents();
		return $suggested->getMostRecent($num);
	}
	public function getMostCommented($num = 5)
	{
		$suggested = new SuggestedContents();
		return $suggested->getMostDiscussed($num);
	}
	public function getMostRead($num=5)
	{
		$suggested = new SuggestedContents();
		return $suggested->getMostRead($num);
	}
	
	/**
	 * Sets the view field 
	 * @param $view Zend_View_Interface
	 */
	public function setView(Zend_View_Interface $view) {
		$this->view = $view;
	}
	public function translate($str)
	{
		return $this->translate->translate($str);
	}
}
