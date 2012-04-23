<?php

/**
 * AddController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('task/models/Tasks.php');
require_once ('task/models/vo/Task.php');
require_once ('cms/models/MySettings.php');
require_once ('cms/models/MyContents.php');
class Cms_AddController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		return $this->_forward('add');
	}
	public function addAction() {
		
	}
	public function submitAction()
	{
		//first get all parameters
		$params = $this->getRequest()->getParams();
//		Precurio_Utils::debug($params);
//		$this->_helper->layout->disableLayout();
//		$this->_helper->viewRenderer->setNoRender();
//		return ;
		if(isset($_FILES['document']) && $_FILES['document']['size'] > 0)
		{
			$params['is_document'] = 1;//so that the content knows it is attached to a document	
			if(isset($_FILES['file']))unset($_FILES['file']);// for some weird reason, upload function can't handle multiple file uploads
		}
		
		if(isset($_FILES['file']) && $_FILES['file']['size'] > 0)
		{
			$filePath = Content::addContentImage();//puts images in the right places if there is any
		}
		
		$user_id = Precurio_Session::getCurrentUserId();//get the current user.
		
		$content_id = $this->getRequest()->getParam('content_id',0);
		
		//we didnt get group_ids from $params because that will cause an error
		//if there happens to be no 'group_id'.
		$group_ids = $this->getRequest()->getParam('group_id',array());
		//same goes for category_id
		$category_ids = $this->getRequest()->getParam('category_id',array());
		
		//clean up $params for database insert
		if(isset($params['group_id']))unset($params['group_id']);
		if(isset($params['file']))unset($params['file']);
		if(isset($params['category_id']))unset($params['category_id']);
		
		
		//set other params.
		if(isset($_FILES['file']) && $_FILES['file']['size'] > 0)$params['image_path'] = $filePath;
		$params['active'] = 1;//we are assuming no approvals are necessary
		$date_created = Precurio_Date::now()->getTimestamp();
		$params['date_created'] = $date_created;
		$params['user_id'] = $user_id;
		
		$params['publish_as_news'] = $this->getRequest()->getParam('is_news',0);
		//since there are no articles in precurio for now, all featured contents are considered
		//articles internally. when the article module comes, there would be a function 
		//isFeatured() in the Content model
		$params['publish_as_article'] = $this->getRequest()->getParam('is_featured',0);
		
		$params['publish_as_advert'] = $this->getRequest()->getParam('is_advert',0);
		
		$params['is_public'] = $this->getRequest()->getParam('is_public',0);
		
		//this is where we determine if it was an edit or add operation
		$myContent = new MyContents();
		if($content_id == 0)
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT));
			$row = $table->createRow($params);
			$content_id = $row->save();
			if(isset($_FILES['document']) && $_FILES['document']['size'])
			{
				$content = $myContent->getContent($content_id);
				$content->addDocument();
			}
		}
		else
		{
			$content = $myContent->getContent($content_id);
			$content->setFromArray($params);
			$content_id = $content->save();
		}
		$requiresApproval = false;//flag to determine if content requires approval, so we dont create an activity for it.
		//insert group access
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUP_CONTENTS));
		foreach($group_ids as $group_id)
		{
			$data = array(
			'group_id'=>$group_id,
			'content_id'=>$content_id,
			'date_created'=>$date_created,
			'active'=>1
			);
			//if precurio groups has been configured to require approval for contents
			$setting = MySettings::getGroupSettings($group_id);
			if($setting != null && $setting->contentRequiresApproval())
			{
				$data['active'] = 0;
				$tasks = new Tasks();
				$task_id = $tasks->addContentTask($user_id,$content_id,Task::TYPE_GROUP_CONTENT,$group_id);
				$data['task_id'] = $task_id;
				$requiresApproval = true;
			}
			
			$row = $table->createRow($data);
			$row->save();
		}
		//now add content at category(s) 
		foreach($category_ids as $category_id)
		{
			$category = Category::getCategory($category_id);
			$category->addContents(array($content_id));
		}
		
		$config = Zend_Registry::get('config');
		if($this->getRequest()->getParam('is_featured',0))
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ARTICLES));
			
			$data = array(
			'is_featured'=>1,
			'content_id'=>$content_id,
			'date_created'=>$date_created,
			'active'=>1
			);
			//if precurio has been configured to require approval for contents
			if($config->content_requires_approval)
			{
				$data['active'] = 0;
				$tasks = new Tasks();
				$task_id = $tasks->addContentTask($user_id,$content_id,Task::TYPE_FEATURED);
				$data['task_id'] = $task_id;
				$requiresApproval = true;
			}
			
			$row = $table->createRow($data);
			$row->save();
		}
		
		if($this->getRequest()->getParam('is_news',0))
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NEWS));
			$data = array(
			'content_id'=>$content_id,
			'date_created'=>$date_created,
			'active'=>1
			);
			
			//if precurio has been configured to require approval for contents
			if($config->content_requires_approval)
			{
				$data['active'] = 0;
				$tasks = new Tasks();
				$task_id = $tasks->addContentTask($user_id,$content_id,Task::TYPE_NEWS);
				$data['task_id'] = $task_id;
				$requiresApproval = true;
			}
			
			$row = $table->createRow($data);
			$row->save();
		}
		
		if($this->getRequest()->getParam('is_advert',0))
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ADVERTS));
			$data = array(
			'content_id'=>$content_id,
			'date_created'=>$date_created,
			'active'=>1
			);
			
			if($config->content_requires_approval)
			{
				$data['active'] = 0;
				$tasks = new Tasks();
				$task_id = $tasks->addContentTask($user_id,$content_id,Task::TYPE_ADVERT);
				$data['task_id'] = $task_id;
				$requiresApproval = true;
			}
			
			$row = $table->createRow($data);
			$row->save();
		}
		
		if(!$requiresApproval)
		{
			$url = '/cms/view/details/c_id/'.$content_id;
			$type = $this->getRequest()->getParam('content_id',0) == 0 ? Precurio_Activity::NEW_CONTENT : Precurio_Activity::EDIT_CONTENT;		
			//now create new activity., which also triggers notifications.
			Precurio_Activity::newActivity($user_id,$type,$content_id,$url);
		}
				
		
		$dict = new Precurio_Search();
		$dict->indexContent($content_id);
		$this->_redirect('/cms/view/index');
	}
}
?>