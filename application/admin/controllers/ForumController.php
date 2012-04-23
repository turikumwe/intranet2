<?php
require_once ('forum/models/Forums.php');
require_once ('admin/controllers/BaseController.php');
class Admin_ForumController extends Admin_BaseController {
	
	function generateHeader() {
		return array('',$this->translate('Title'),$this->translate('Created By'), $this->translate('Date Created'), $this->translate('Topics'), $this->translate('Replies'));
	}

	function generateList($searchText) 
	{
		$forums = new Forums();
		
		$forums = $forums->getForums();
		
		$arr = array();
		$i = 1;
		foreach($forums as $forum)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($forum->getTitle(),$searchText)===FALSE)
				{
					if(stripos($forum->last_name,$searchText)===FALSE)
					{
						if(stripos($forum->first_name,$searchText)===FALSE)
						{
							continue;
						}
					}
				}
				
			}
			
			$arr[] = array($i++,'title'=>$forum->title,'full_name'=>$forum->getCreator(),'date_created'=>$forum->getDateCreated(),'no_topics'=>$forum->getNumberOfTopics(),'no_replies'=>$forum->getNumberOfReplies(), 'id'=>$forum->getId());
		}
		return $arr;
	}
	
		
	function getPageTitle() {
		return $this->translate("Forums Management");
	}
	
	function getTableName() 
	{
		return PrecurioTableConstants::FORUMS;
	}
	
	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$forums = new Forums();
		
		$item = $forums::getForum($id);	
		if($item == null)return $this->_forward('index');
		
		$this->view->form = $this->getForm($item, TRUE);
		
		//$this->view->visitor = $item; I dont this it should be here, must be a copy and paste error from @kayfun
		$this->view->pageTitle = $this->getPageTitle();
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
		$this->view->searchText = $this->getRequest()->getParam('search','');
		
		
	}
	public function editAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$forums = new Forums();
		
		$item = $forums::getForum($id);	
		if($item == null)return $this->_forward('index');
		
		$this->view->edtop = 1;
		$this->view->forum = $item;		
		
		$this->view->form = $this->getForm($item);
				
		$this->view->pageTitle = $this->view->translate($this->getPageTitle());
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
		$this->view->searchText = $this->getRequest()->getParam('search','');
		$this->render('view');
	}
	
		
	
	
	public function submitAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$values = $this->getRequest()->getParams();
		
		$forums = new Forums();
		
		if( Precurio_Utils::isNull($values['id']) )
		{
			unset($values['id']);
		
			$values['creator'] = Precurio_Session::getCurrentUserId();
			$values['date_created'] = time();
			$forums->addForum($values);
		}
		else			
			$forums->updateForum($values['id'], $values);
		
		
		return $this->_redirect('/admin/forum');
	}
	
		
	
	
	
	function getForm($item = null, $viewMode = false) 
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/forum/submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm');
						
		
			
		$form->addElement('hidden', 'id', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$item['id'],
				));

						
				
		$form->addElement('text', 'title', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Title'),
				'value'=>$item['title']
				));
		$form->addElement('textarea', 'description', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Description'),
				'value'=>$item['description'],
				'style'=>'height:100px'
				));
		
				

		if(!$viewMode)
			$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
		return $form;
	}
	
	
}

?>