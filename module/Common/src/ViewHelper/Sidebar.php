<?php
namespace Common\ViewHelper;

use ZfAnnotation\Annotation\ViewHelper;

/**
 * @ViewHelper(name="sidebar")
 * */
class Sidebar extends \Zend\View\Helper\AbstractHelper{
	
	var $vars = [];
	var $templates = [];
	var $currentPosition = 'left';
	
	public function __invoke($position = null, $template = null, $vars = null){
		if(!empty($position)){
			$this->currentPosition = $position;
		}
		if($template != null){
			$this->template[$this->currentPosition] = $template;
			$this->vars[$this->currentPosition] = $vars;
		}
		return 	$this;
	}		
	
	/**
	 * 
	 * @param string $template
	 * @param array $vars
	 * @return Sidebar
	 */
	public function left($template = null, $vars = null){
		return $this('left', $template, $vars);
	}

	/**
	 *
	 * @param string $template
	 * @param array $vars
	 * @return Sidebar
	 */
	public function right($template = null, $vars = null){
		return $this('right', $template, $vars);
	}
	
	public function __toString(){
		return '';
	}

	public function getVars() {
		return $this->vars[$this->currentPosition];
	}

	public function getTemplate() {
		return $this->template[$this->currentPosition];
	}
	
	public function exists(){
		return array_key_exists($this->currentPosition, $this->template);
	}
	
}