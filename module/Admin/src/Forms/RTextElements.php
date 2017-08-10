<?
namespace Admin\Forms;

use Common\Form\Element;
use Admin\Model\Users\UserDb;
use Common\Traits\ServiceManagerAware;
use Zend\View\HelperPluginManager;
use Zend\View\Helper\Url;

/**
 * TODO
 * @author Ev
 *
 */

trait RTextElements {
	
	protected function htmlRText(Element $element){
	
		$ret =  '<div contenteditable="true" class="r-text-input" name="'.$element->name().'" '.$extras.'>'.htmlentities($element->value()).'</div>';
		$ret .= '<div  class="r-text-attachments" ></div>';
		return $ret;
	
	}
	
	protected function parseRText(Element $element, $data){
		$val = $data[$element->name()];
		if(empty($val)){
			$val = null;
		}	
		$element->value($val);
	}
	
	
	
}