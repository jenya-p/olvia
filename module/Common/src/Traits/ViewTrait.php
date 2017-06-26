<?php

namespace Common\Traits;

use Zend\View\View;

trait ViewTrait{
	
	protected $view;	

	public function view(View $view = null){
		if($view === null){
			return $this->view;
		} else {
			$this->view = $view;
			return $this;	
		}		
	}
	
}