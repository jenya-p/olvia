<?php
namespace Application\ViewHelper;

use Zend\Form\View\Helper\AbstractHelper;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Application\Model\MasterDb;
use ZfAnnotation\Annotation\ViewHelper;
use Zend\View\Renderer\PhpRenderer;

/**
 * @ViewHelper(name="popupSigne")
 */
class PopupSigne extends AbstractHelper implements ServiceManagerAware{
	
	use ServiceManagerTrait;
	
	public function __invoke(){

		$view = $this->view;
		if(!$view instanceof PhpRenderer){return;}
		
		$popupHelper = $view->getHelperPluginManager()->get('popup');
		if(!$popupHelper instanceof Popup){return;}
		
		/* @var $masterDb MasterDb */
		$masterDb = $this->serv(MasterDb::class);
		$masters = $masterDb->getPersonalConsultants();
		
		$popupHelper('signe', ['masters' => $masters]);
		
		
	}	
	
	

}