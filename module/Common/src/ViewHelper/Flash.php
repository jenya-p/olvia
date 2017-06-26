<?php
namespace Common\ViewHelper;

use ZfAnnotation\Annotation\ViewHelper;

/**
 * @ViewHelper(name="flash")
 * */
class Flash extends \Zend\View\Helper\AbstractHelper{
	
	const INFO = 'info';
	const ERROR = 'error';
	const SUCCESS = 'success';
	const SESSION_KEY = 'flash_messages';
	
	public function __invoke($template = null, $vars = null){
		
		$items = $_SESSION[self::SESSION_KEY];
		
		if(!empty($items)){			
			if($template !== null){
				if(empty($vars)){
					$vars = [];
				}
				$vars ['items'] = $items;
				
				$ret = $this->getView()->render($template,$vars);
			} else {
				$ret = $this->renderSimple();
			}
			
		}		
		unset($_SESSION[self::SESSION_KEY]);
		return $ret;
	}	
	
	public function renderSimple($items) {
		foreach ($items as $item){
			if($item['class'] == self::ERROR){
				$ico = 'exclamation-circle';
			} else if ($item['class'] == self::SUCCESS){
				$ico = 'thumbs-up';
			} else{
				$ico = 'info';
			}
			$ret .= '<li class="alert '.$item['class'].'"><i class="fa fa-'.$ico.'"></i><span>'.$item['message'].'</span></li>';
		}
	}
	
}