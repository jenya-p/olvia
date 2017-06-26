<?php

namespace Common\Traits;

use Zend\View\View;

interface ViewAware{

	public function view(View $view = null);
    
}