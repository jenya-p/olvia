<?php
namespace Application\ViewHelper;

use ZfAnnotation\Annotation\ViewHelper;
use Zend\Form\View\Helper\AbstractHelper;
use Zend\View\HelperPluginManager;

class Seo extends AbstractHelper{

	
	/** @var HelperPluginManager */
	protected $vhm = null;
	
	/** @var HeadTitle */
	protected $headTitle;
	
	/** @var HeadMeta */
	protected $headMeta;
	
	/** @var HeadLink */
	protected $headLink;
	
	public function __construct(HelperPluginManager $vhm){
	
		$this->vhm = $vhm;
		$this->headTitle = $this->vhm->get('headTitle');
		$this->headMeta = $this->vhm->get('headMeta');
	
	}
	
	public function __invoke($item = null, $description = null, $keywords = null){
		if($item === null){
			
			return $this;
		}
		if(is_array($item)){
			
			$this->getView()->headTitle();
			$this->headTitle->__invoke($item['seo_title']);
			$this->headMeta->appendName("description", $item ['seo_description']);
			$this->headMeta->appendName("keywords", $item ['seo_keywords']);
							
		} else {
			
			$this->headTitle->__invoke($item);
			$this->headMeta->appendName("description", $description);
			$this->headMeta->appendName("keywords", $keywords);
			
		}
	}	
	
	

}