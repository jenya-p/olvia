<?
namespace Common\ViewHelper;

use \Detection\MobileDetect as MobileDetectEngine;
use ZfAnnotation\Annotation\ViewHelper;

/**
 * @ViewHelper(name="mDetect")
 * */
class MobileDetect extends \Zend\View\Helper\AbstractHelper {
	
	/* @var MobileDetectEngine */
	private $engine;
	
	function __construct() {
		$this->engine = new MobileDetectEngine();
		
	}
	
	public function getBodyClass(){
		if($this->engine->isTablet()){
			return 'tablet'; 
		} else if($this->engine->isMobile()){
			return 'mobile';		
		} else {
			return 'desktop';
		}		
	}
	
}