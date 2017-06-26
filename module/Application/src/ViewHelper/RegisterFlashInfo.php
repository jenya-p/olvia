<?php
namespace Application\ViewHelper;

use ZfAnnotation\Annotation\ViewHelper;
use Zend\Form\View\Helper\AbstractHelper;
use Application\Model\Content\ContentDb;
use Zend\Session\Container;


class Content extends AbstractHelper{
	
	/* @var Container */
	var $session = null;
	
	public function __construct(ContentDb $contentDb){
		$this->session = new Container('RegisterFlashInfo');
	}
	
	public function __invoke(){
		if(!empty($this->session)){
			echo $this->session['message'];
		}		
	}	
	
	

}