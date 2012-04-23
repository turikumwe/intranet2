<?php
require_once 'Zend/View/Helper/FormElement.php';
class Workflow_View_Helper_FormPlainText  extends Zend_View_Helper_FormElement {
  /**
   * Generates text.
   *
   * @access public
   *
   * @param string|array $name If a string, is set as the "value" and rendered. The
   * real "value" setting will take precidence if set. In effect, the "name" value
   * become the default for "value" when "value" is not set. If an array, all other
   * parameters are ignored, and the array elements are extracted in place of added
   * parameters.
   *
   * @param mixed $value The element value.
   *
   * @param array $attribs Attributes for the element tag.
   *
   * @return string The element XHTML.
  */
  public function formPlainText($name, $value = null, $attribs = null) {
    $info = $this->_getInfo($name, $value, $attribs);
    extract($info); // name, value, attribs, options, listsep, disable
    if (!$value) {$value = '';}

    return $info['escape'] ? $this->view->escape($value) : $value;
  }

}

?>