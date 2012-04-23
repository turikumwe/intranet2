<?php
require_once ('Zend/Db/Table/Row/Abstract.php');
require_once 'user/models/vo/Group.php';
require_once 'user/models/UserUtil.php';
require_once 'cms/models/vo/Category.php';
class GroupToCategory extends Zend_Db_Table_Row_Abstract{
	/**
	 * The Group object
	 * @var Group
	 */
	private $group;
	
	/**
	 * The Category object
	 * @var Category
	 */
	private $category;
	public function init()
	{
		$this->group = UserUtil::getGroup($this->group_id);
		$this->category = Category::getCategory($this->category_id);
	}
	
	/**
	 * Returns date group was Group to Category mapping was done.
	 * @return Precurio_Date
	 */
	public function getDateShared()
	{
		$date = new Precurio_Date($this->date_created);
		return $date;
	}
	
	/**
	 * Returns user who did the Group to Category mapping
	 * @return User
	 */
	public function getSharedBy()
	{
		return UserUtil::getUser($this->user_id);
	}
	/* 
	 * Direct non existing function calls to the 'Group' object eg getTitle()
	 * @params string $methodName
	 * @params array $args
	 */
	public function __call($methodName, $args) {
       return call_user_func_array(array($this->group, $methodName), $args);
    }
	/* 
	 * Direct non existing static function calls to the 'Group' class.
	 * Will hardly be useful though
	 * @params string $methodName
	 * @params array $args
	 */
    public static function __callStatic($methodName, $args) {
        return call_user_func(array('Group', $methodName),$args);
    }
}
?>
