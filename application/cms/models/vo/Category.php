<?php
require_once ('user/models/UserUtil.php');
require_once ('cms/models/vo/Content.php');
require_once ('cms/models/vo/GroupToCategory.php');
require_once ('Zend/Db/Table/Row/Abstract.php');
class Category extends Zend_Db_Table_Row_Abstract{
	
	const ACCESS_PUBLIC = 0;
	const ACCESS_PRIVATE = -1;
	const ACCESS_SHARED = 1;
	
	/**
	 * Gets category owner
	 * @return User
	 */
	public function getUser()
	{
		return UserUtil::getUser($this->user_id);
	}
	/**
	 * Gets category owner (proxy to getUser())
	 * @return User
	 */
	public function getOwner()
	{
		return $this->getUser();
	}
	/**
	 * Returns the full name of the user that owns the category
	 * @return string
	 */
	public function getOwnersName()
	{
		return $this->getUser()->getFullName();
	}
	/**
	 * Returns the date created as a Date object
	 * @return Precurio_Date
	 */
	public function getDateCreated()
	{
		return new Precurio_Date($this->date_created);
	}
	/**
	 * Returns the access type (in string)
	 * @return string
	 */
	public function getAccessType()
	{
		return Category::getAccessStr($this->access_type);
	}
	/**
	 * Returns the parent category
	 * @return Category
	 */
	public function getParent()
	{
		$tr = Zend_Registry::get('Zend_Translate');
		if($this->parent_id == 0 )
			return '['.$tr->translate('None').']';
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CATEGORYS,'rowClass'=>'Category'));
		$item = $table->fetchRow($table->select()->where('id = ?',$this->parent_id)->where('active=1'));
		if($item==null)
			return $tr->translate('Orphaned Category');
		return $item;
	}
	/**
	 * Returns the sub-categorys on a category
	 * @param boolean $recursive |default true| - flag to determine if you want to recursively get sub categories of sub categories. 
	 * @param int $category_id |default 0| - id of category you want to get it's children. (Mostly used internaly when recurive=true)
	 * @return array
	 */
	public function getCategoryChildren($recursive = true,$category_id=0)
	{
		if($category_id == 0)$category_id = $this->id;
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CATEGORYS,'rowClass'=>'Category'));
		$items = $table->fetchAll($table->select()->where('parent_id = ?',$category_id)->where('active=1'));
		if(!isset($categorys))
			$categorys = array();
		foreach($items as $item)
		{
			$categorys[] = $item;
			if($recursive)
			{
				$categorys = array_merge($categorys,$this->getCategoryChildren($recursive,$item->id));
				
			}
		}
		return $categorys;
	}
	public function getContentChildren($recursive = true)
	{
		$categorys = array();
		$contents = array();
		//if you want to get all children (both direct and indirect), you have to first get all sub categorys
		if($recursive)
		{
			$categorys = $this->getCategoryChildren(true);
		}
		array_unshift($categorys,$this);//add this category

		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT,'rowClass'=>'Content'));
		foreach ($categorys as $category)
		{
			$select = new Zend_Db_Table_Select($table);
			$select->setIntegrityCheck(false);
			$select = $select->from(array('a' => PrecurioTableConstants::CONTENT))
							->join(array('b' => PrecurioTableConstants::CONTENT_CATEGORYS),'a.id = b.content_id',array())
							->where('b.category_id = ?',$category->id)
							->where('a.active=1')
							->where('b.active=1');
			$temp = $table->fetchAll($select);
			foreach($temp as $t)//get contents Zend_Db_Table_RowSet into array , by looping.
			{
				$contents[$t->id] = $t;//using $contents[$t->id] instead of  solves the issue of duplicate contents, since  already existing contents will replace themselves
			}
		}

		return $contents;
	}
	
	public function deActivateCategory()
	{
		$this->active = 0;
		$this->deleted = 1;
		$this->save();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT_CATEGORYS));
		$items = $table->fetchAll("category_id = $this->id");
		
		foreach($items as $obj)
		{
			$obj->active = 0;
			$obj->save();
		}
	}
	/**
	 * Add contents to a category
	 * @param array|int $content_ids 
	 * @return null
	 */
	public function addContents($content_ids)
	{
		if(!is_array($content_ids))$content_ids = array($content_ids);
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT_CATEGORYS));
		$user_id = Precurio_Session::getCurrentUserId();
		foreach($content_ids as $id)
		{
			$table->insert(array(
				'category_id'=>$this->id,
				'content_id'=>$id,
				'user_id'=>$user_id,
				'date_created'=>time(),
				'active'=>1
			));
		}
		return;
	}
	/**
	 * @param array|int $content_ids 
	 * @return null
	 */
	public function removeContents($content_ids)
	{
		if(!is_array($content_ids))$content_ids = array($content_ids);
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT_CATEGORYS));
		foreach($content_ids as $content_id)
		{
			$table->delete("content_id = $content_id");
		}
		return;
	}
	/**
	 * @param array|int $group_ids 
	 * @return null
	 */
	public function removeGroups($group_ids)
	{
		if(!is_array($group_ids))$group_ids = array($group_ids);
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUP_CATEGORYS));
		foreach($group_ids as $group_id)
		{
			$table->delete("group_id = $group_id");
		}
		return;
	}
	/**
	 * Indicates whether category already contains a particular content
	 * @param int $content_id
	 * @return boolean
	 */
	public function hasContent($content_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT_CATEGORYS));
		$item = $table->fetchRow($table->select()->where('content_id = ?',$content_id)->where('active = 1'));
		return $item == null ? false : true;
	}
	
	/**
	 * Returns an array of group objects that have been given access to shared category
	 * @return array
	 */
	public function getGroups()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUP_CATEGORYS));
		$items = $table->fetchAll($table->select()->where('category_id = ?',$this->id)->where('active = 1'));
		$groups = array();
		foreach ($items as $item)
		{
			$group = UserUtil::getGroup($item->group_id);
			if(!empty($group))$groups[] = $group;
		}
		return $groups;
	}
	/**
	 * Add groups to a shared category
	 * @param array|int $group_ids 
	 * @return null
	 */
	public function addGroups($group_ids)
	{
		if(!is_array($group_ids))$group_ids = array($group_ids);
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUP_CATEGORYS));
		$user_id = Precurio_Session::getCurrentUserId();
		foreach($group_ids as $id)
		{
			$table->insert(array(
				'category_id'=>$this->id,
				'group_id'=>$id,
				'user_id'=>$user_id,
				'date_created'=>time(),
				'active'=>1
			));
		}
		return;
	}
	/**
	 * Gets a rowset of GroupToCategory objects
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getGroupsToCategory()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUP_CATEGORYS,'rowClass'=>'GroupToCategory'));
		return $table->fetchAll($table->select()->where('category_id = ?',$this->id)->where('active = 1'));
	}
	public function __toString()
	{
		return $this->title;
	}
	
	/**
	 * Determines is a category is publicly accessible
	 * @return boolean
	 */
	public function isPublic()
	{
		return $this->access_type == self::ACCESS_PUBLIC;
	}
	/**
	 * Determines is a category is only accessible by the creator
	 * @return boolean
	 */
	public function isPrivate()
	{
		return $this->access_type == self::ACCESS_PRIVATE;
	}
	/**
	 * Determines is a category is accessible by selected groups
	 * @return boolean
	 */
	public function isShared()
	{
		return $this->access_type == self::ACCESS_SHARED;
	}
	/**
	 * Returns folders/categorys that are public
	 * @return Zend_Db_Table_Rowset
	 */
	public static function getPublicCategorys()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CATEGORYS,'rowClass'=>'Category'));
		$items = $table->fetchAll($table->select()->where('active=1')->where('access_type=?',Category::ACCESS_PUBLIC));
		return $items;
	}
	/**
	 * Returns folders/categorys that are shared and accessible by the current user.
	 * 
	 * For a shared category to be accessible by a user, the user must be a member of one
	 * of the groups given access to the category.
	 * @param $user_id;
	 * @return array
	 */
	public static function getSharedCategorys($user_id=0)
	{
		if(empty($user_id))$user_id = Precurio_Session::getCurrentUserId();
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CATEGORYS,'rowClass'=>'Category'));
		$items = $table->fetchAll($table->select()->where('active=1')->where('access_type=?',Category::ACCESS_SHARED));
		$result = array();
		foreach($items as $category)
		{
			$groups = $category->getGroups();
			foreach($groups as $group)
			{
				if($group->containsMember($user_id))
				{
					$result[] = $category;
					break;//check next category.
				}
			}
		}
		return $result;
	}
	/**
	 * Returns folders/categorys that are only accessible by the user
	 * @param $user_id
	 * @return Zend_Db_Table_Rowset
	 */
	public static function getPrivateCategorys($user_id=0)
	{
		if(empty($user_id))$user_id = Precurio_Session::getCurrentUserId();
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CATEGORYS,'rowClass'=>'Category'));
		$items = $table->fetchAll($table->select()->where('active=1')
												->where('access_type=?',Category::ACCESS_PRIVATE)
												->where('user_id = ?',$user_id));
		return $items;
	}
	/**
	 * Get the string representation of an access type
	 * @param $accessType int
	 * @return string
	 */
	public static function getAccessStr($accessType)
	{
		$tr = Zend_Registry::get('Zend_Translate');
		if($accessType == Category::ACCESS_SHARED)return $tr->translate('Shared');
		if($accessType == Category::ACCESS_PUBLIC)return $tr->translate('Public');
		if($accessType == Category::ACCESS_PRIVATE) return $tr->translate('Private');
	}
	/**
	 * @param int $id id of category
	 * @return Category
	 */
	public static function getCategory($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CATEGORYS,'rowClass'=>'Category'));
		$item = $table->fetchRow($table->select()->where('id = ?',$id));
		return $item;
	}

	/**
	 * Returns all categorys
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public static function getAll()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CATEGORYS,'rowClass'=>'Category'));
		$items = $table->fetchAll($table->select()->where('active=1'));
		return $items;
	}
}

?>