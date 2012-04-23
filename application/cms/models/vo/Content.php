<?php

require_once ('Zend/Db/Table/Row/Abstract.php');
require_once ('user/models/vo/Comment.php');
require_once ('user/models/UserUtil.php');
require_once ('cms/models/MyContents.php');
class Content extends Zend_Db_Table_Row_Abstract {
	const IMAGE_ICON = 0;
	const IMAGE_THUMBNAIL = 1;
	const IMAGE_LARGE = 2;
	const IMAGE_PROFILE = 3;
	const IMAGE_251 = 4;
	const IMAGE_271 = 5;
	const IMAGE_517 = 6;
	
	const PATH_PHOTOS = '/uploads/photo/';
	const PATH_PROFILE_PICS = '/uploads/profilepic/';
	const PATH_RESOURCES = '/uploads/resource/';
	const PATH_THUMBNAILS = '/uploads/thumb/';
	const PATH_ICONS = '/uploads/icon/';
	const PATH_TMP = '/uploads/tmp/';
	const PATH_CONTENT_IMAGE_251 = '/uploads/content/images/251/';
	const PATH_CONTENT_IMAGE_271 = '/uploads/content/images/271/';
	const PATH_CONTENT_IMAGE_517 = '/uploads/content/images/517/';
	
	const WIDTH_ICON = 50;
	const WIDTH_THUMBNAIL = 130;
	const WIDTH_PROFILE_PIC = 180;
	const WIDTH_LARGE = 465;
	const WIDTH_LOGO = 100;
	const WIDTH_IMAGE_251 = 251;
	const WIDTH_IMAGE_271 = 271; 
	const WIDTH_IMAGE_517 = 517;
	
	/**
	 * get the content id
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * get the title property
	 * This is typically used as the headline for news, and title for articles.
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}
	/**
	 * get the summary
	 * @return string
	 */
	public function getSummary()
	{
		if(Precurio_Utils::isNull($this->summary) && !Precurio_Utils::isNull($this->getContent(true)) )
		{
			$this->summary = addslashes(strip_tags(substr($this->getContent(true),0,300))).'...';
		}
		return strip_tags($this->summary,'<a><b><i><u>');
	}
	/**
	 * gets the content of the article
	 * @param $fromGetSummary internal flag used to determine if call was made from getSummary(), leave as default.
	 * @return string
	 */
	public function getContent($fromGetSummary = false)
	{
		if(!$fromGetSummary)
			MyContents::increaseHits($this->id);
		if(Precurio_Utils::isNull($this->body))
		{
			if(!Precurio_Utils::isNull($this->summary))
			{
				return $this->summary;
			}
			else
			{
			//TODO if body is null, fetch content from url.
				
			}
		}
		return $this->body;
	}
/**
	 * gets the content of the article
	 * proxies to getContent()
	 * @return string
	 */
	public function getBody()
	{
		return $this->getContent();
	}
/**
	 * get the user_id of person who created the content
	 * @return int
	 */
	public function getUserId()
	{
		return $this->user_id;
	}
/**
	 * gets the url
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}
/**
	 * gets the keword
	 * @return string
	 */
	public function getKeywords()
	{
		$str = " ";
		$translate = Zend_Registry::get('Zend_Translate');
		if($this->isAdvert())$str .= $translate->translate("advert").' '.$translate->translate("advertisment").' ';
		if($this->isNews())$str .= $translate->translate("news").' ';
		if($this->isArticle())$str .= $translate->translate("featured").' '.$translate->translate("article").' '; 
		if($this->is_photo)$str .= $translate->translate("picture").' '.$translate->translate("photo").' '; 

		return $this->keyword.$str;
	}
/**
	 * gets the image path of the article
	 * @return string
	 */
	public function getImagePath($type=100)
	{
		$filename = basename($this->image_path);
		$filepath = "";
		switch($type)
		{
			case self::IMAGE_LARGE:
				$filepath = self::PATH_PHOTOS;
				break;
			case self::IMAGE_THUMBNAIL:
				$filepath = self::PATH_THUMBNAILS;
				break;
			case self::IMAGE_ICON:
				$filepath = self::PATH_ICONS;
				break;
			case self::WIDTH_IMAGE_251:
				$filepath = self::PATH_CONTENT_IMAGE_251;
				break;	
			case self::WIDTH_IMAGE_271:
				$filepath = self::PATH_CONTENT_IMAGE_271;
				break;	
			case self::WIDTH_IMAGE_517:
				$filepath = self::PATH_CONTENT_IMAGE_517;
				break;		
			default:
				$filename = $this->image_path;
				break;
			
		}
		
		$filename = $filepath.$filename;
		if(!is_file(getcwd().$filename))
			$filename = $this->image_path;
		return $filename;

	}
/**
	 * Whether or not the content is active
	 * Deleted contents are inactive
	 * @return boolean
	 */
	public function isActive()
	{
		return $this->active;
	}
/**
	 * Whether or not the content is a news
	 * Deleted contents are inactive
	 * @return boolean
	 */
	public function isNews()
	{
		return $this->publish_as_news;
	}
	/**
	 * Whether or not the content is an article
	 * Deleted contents are inactive
	 * @return boolean
	 */
	public function isArticle()
	{
		return $this->publish_as_article;
	}
	/**
	 * Whether or not the content is an advert
	 * @return boolean
	 */
	public function isAdvert()
	{
		return $this->publish_as_advert;
	}
	/**
	 * Whether or not the content is from an RSS source
	 * @return boolean
	 */
	public function isRSS()
	{
		return $this->publish_as_rss;
	}
/**
	 * get the rss source id
	 * @return int
	 */
	public function getRssSourceId()
	{
		return $this->rss_source_id;
	}
/**
	 * get the date_created of the content
	 * @return int
	 */
	public function getDateCreated()
	{
		return $this->date_created;
	}
	public function getDateAdded()
	{
		$date =  new Precurio_Date($this->getDateCreated());
		return $date;
	}
/**
	 * get the last updated of the content
	 * @return int
	 */
	public function getDateLastUpdated()
	{
		return $this->last_updated;
	}
	public function getSharedUsers()
	{
		$db = Zend_Registry::get('db');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::SHARED_CONTENTS));
		$select = $table->select(false);
		$select->setIntegrityCheck(false);
		$select = $select->from(array('a'=>PrecurioTableConstants::SHARED_CONTENTS),'user_id')
							->join(array('b'=>PrecurioTableConstants::USERS),'a.user_id = b.user_id',array('full_name'=>'concat (first_name," " ,last_name)'))
							->where('content_id = ?',$this->getId() );
		$user_ids = $db->fetchAll($select);
		return $user_ids;
	}
	public function setSharedUsers($user_ids)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::SHARED_CONTENTS));
		//delete all previous share.
		$where = $table->getAdapter()->quoteInto('content_id = ?',$this->getId() );
		$table->delete($where);
		$data = array();
		$data['content_id'] = $this->getId(); 
		$data['sharer_id'] = Precurio_Session::getCurrentUserId();
		$data['date_created'] = Precurio_Date::now()->getTimestamp();
		foreach($user_ids as $user_id)
		{
			$data['user_id'] = $user_id;
			$row = $table->createRow($data);
			$row->save();
		}
	}
	public function getComments()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::COMMENTS, 'rowClass'=>'Comment'));
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select->setTable($table); 
		$select = $select->from(array('a' => PrecurioTableConstants::COMMENTS))
						->join(array('c' => PrecurioTableConstants::USERS),'c.user_id = a.user_id',array('first_name','last_name','profile_picture_id'))
						->join(array('d' => PrecurioTableConstants::PROFILE_PICS),'c.profile_picture_id = d.id',array('image_path'))
						->where('a.content_id= ? ',$this->getId())
						->order('id ASC');
		
		$all = $table->fetchAll($select);
		return $all;
		
	}
	public function getContentId()
	{
		return isset($this->content_id) ? $this->content_id : $this->id;
	}
	public function getShortHeadline($n=36)
	{
		if(strlen($this->getTitle())>$n)
			return substr($this->getTitle(),0,36).'...';
		else
			return $this->getTitle();
	}
	public function getSinceWhen()
	{
		$date = new Precurio_Date($this->getDateCreated());
		return $date->getHowLongAgo();
	}
	public function getFullName()
	{
		$user = UserUtil::getUser($this->getUserId());
		return $user->getFullName();
	}
	/**
	 * Determines if the content also belongs to a particular group
	 * @param $group_id int
	 * @return Boolean
	 */
	public function belongsToGroup($group_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUP_CONTENTS));
		$row = $table->fetchRow($table->select()->where('content_id = ?',$this->getId())->where('group_id = ?',$group_id));
		return $row == null ? false : true;
	}
	public function userHasRated($user_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT_RATINGS));
		$row = $table->fetchRow($table->select()->where('content_id = ?',$this->getId())->where('user_id = ?',$user_id));
		return $row == null ? false : true;
	}
	
	public function isPublic()
	{
		return $this->is_public;
	}
	public function isDocument()
	{
		return $this->is_document;
	}
	/**
	 * Determines if a user has access to a particular content. 
	 * Note that this has no effect on any previously set access rules. I.e If a user
	 * has not been given "view" privilege to the content module, he will not be able
	 * to view the content even if this function returns TRUE.
	 * @param $user_id
	 * @return Boolean
	 */
	public function canAccess($user_id)
	{
		if($this->isPublic())return true;
		
		$user = UserUtil::getUser($user_id);
		$groups = $user->getGroups(); 
		foreach ($groups as $group)
		{
			if($this->belongsToGroup($group->id))
				return true;
		}
		return false;
	}
	public function addDocument($fileCtrl='document')
	{
		//return;
		$root = Zend_Registry::get('root');
		$upload = new Precurio_Upload;
		$upload->uploadFile(self::PATH_TMP);
		$filename = $upload->_files[$fileCtrl];
		$filePath = $root.'/public/'.self::PATH_TMP.$filename;
		if($fp = fopen($filePath,'rb'))
		{
			$file_content = fread($fp,filesize($filePath));
			fclose($fp);
			$data = array(
				'content_id'=>$this->getContentId(),
				'file_name'=>$upload->getFileTitle($fileCtrl),
				'file_type'=>$upload->getFileExt($fileCtrl),
				'file_size'=>filesize($filePath),
				'file_author'=>$upload->getFileOwner($filePath),
				'file_date_created'=>filemtime($filePath),
				'file_content'=>$file_content,
				'active'=>1,
				'date_created'=>Precurio_Date::now()->getTimestamp()
			);
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DOCUMENTS));
			$row = $table->createRow($data);
			$document_id = $row->save();
			
			//unlink($filePath);//delete the file
		}
		
	}
	/**
	 * @param $fileCtrl String - Name of the input file control used for the file upload, default='file' 
	 * @param $isProfilePhoto Boolean - Set whether this is a profile picture or not, default=false 
	 * @param $isLogo Boolean - Set whether this is a logo image, default = false
	 * @return String - Path to thumbnail version of the photo.
	 */
	public static function addPhoto($fileCtrl='file',$isProfilePhoto=false,$isLogo=false)
	{
		$upload = new Precurio_Upload;
		$upload->uploadFile(self::PATH_TMP);
		$filename = $upload->_files[$fileCtrl];
		if($isProfilePhoto)
		{
			$upload->setMaxWidth(self::WIDTH_PROFILE_PIC);
			$upload->imgResize($upload->_files[$fileCtrl],self::PATH_TMP, self::WIDTH_PROFILE_PIC,true,self::PATH_PROFILE_PICS);
		}
		
		if(!$isLogo)
		{
			$upload->setMaxWidth(self::WIDTH_LARGE);
			$upload->imgResize($filename,self::PATH_TMP, self::WIDTH_LARGE,true,self::PATH_PHOTOS);	
		}
		
		
		$upload->setMaxWidth(self::WIDTH_THUMBNAIL);
		$upload->imgResize($filename,self::PATH_TMP, self::WIDTH_THUMBNAIL,true,self::PATH_THUMBNAILS,true);
		
		$upload->setMaxWidth(self::WIDTH_ICON);
		$upload->imgResize($filename,self::PATH_TMP, self::WIDTH_ICON,true,self::PATH_ICONS,true);
		
		$filePath = $isProfilePhoto ? self::PATH_PROFILE_PICS.$filename : self::PATH_THUMBNAILS.$filename;
		
		return $filePath;
		
	}
	
	/**
	 * @param $fileCtrl String - Name of the input file control used for the file upload, default='file' 
	 * @return String - Path to 271 width version of the photo.
	 */
	public static function addContentImage($fileCtrl='file')
	{
		$upload = new Precurio_Upload;
		$upload->uploadFile(self::PATH_TMP);
		
		$filename = $upload->_files[$fileCtrl];
		
		$upload->setMaxWidth(self::WIDTH_IMAGE_251);
		$upload->imgResize($filename,self::PATH_TMP, self::WIDTH_IMAGE_251,true,self::PATH_CONTENT_IMAGE_251);
		
		$upload->setMaxWidth(self::WIDTH_IMAGE_271);
		$upload->imgResize($filename,self::PATH_TMP, self::WIDTH_IMAGE_271,true,self::PATH_CONTENT_IMAGE_271);
		
		$upload->setMaxWidth(self::WIDTH_IMAGE_517);
		$upload->imgResize($filename,self::PATH_TMP, self::WIDTH_IMAGE_517,true,self::PATH_CONTENT_IMAGE_517);
		
		
		$filePath = self::PATH_CONTENT_IMAGE_271.$filename;
		
		return $filePath;
		
	}
}

?>