<?php
namespace Application\ViewHelper;

use Zend\Form\View\Helper\AbstractHelper;
use Zend\View\Renderer\RendererInterface;
use ZfAnnotation\Annotation\ViewHelper;


class Popup extends AbstractHelper{
	
	var $popupToShow = [];
	/** @var RendererInterface */
	var $renderer = null;
	
	public function __construct(RendererInterface $renderer){
		$this->renderer = $renderer;
	}
	
	public function __invoke($name = null, $vars = null){
		if($name == null){
			return $this;
		}
		if(!array_key_exists($name, $this->popupToShow)){
			$this->popupToShow[$name] = $this->render($name, $vars); 
		}		
	}	
	
	public function add($name = null, $html = null){
		if(!array_key_exists($name, $this->popupToShow)){
			$this->popupToShow[$name] = $html;
		}
	}
	 
	public function __toString(){
		return implode("\n", $this->popupToShow);
	}
	
	private function render($name, $vars){
		$templateName = 'parts/popups/'.$name.'.phtml';
		 
		return $this->renderer->render($templateName, $vars);
	}

}