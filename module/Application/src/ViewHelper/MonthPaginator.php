<?php
namespace Application\ViewHelper;

use Zend\Form\View\Helper\AbstractHelper;
use ZfAnnotation\Annotation\ViewHelper;
use Common\ViewHelper\Html;
use Zend\View\HelperPluginManager;

class MonthPaginator extends AbstractHelper{
	
	var $url = null;
	
	var $currentYear;
	
	/** @var Html */
	protected $html;
	
	public function __construct(HelperPluginManager $vhm){	
		$this->html = $vhm->get('html');	
	}
		
	public function __invoke($current, $bounds, $count = 1, $url){
		$this->url = $url;
				
		$ret.= '';
		
		if(empty ($current)){
			$current = time();
		}
		
		
		$current = strtotime(date('y-m', $current).'-01'); // first day of month
		$this->currentYear = date('Y', $current);
		
		$ret.= '<li><span>'.$this->html->date($current,'M Y', '', '') .'</span></li>';
		
		$start = null;
		$end = null;
		if(!empty($bounds) && !empty($bounds['start'])){
			$start = strtotime(date('y-m', $bounds['start']).'-01');
		}
		
		if(!empty($bounds) && !empty($bounds['end'])){
			$end = $bounds['end'];
		}
		
		for ($i = 1; $i <= $count; $i++){
			$dt = strtotime('- '.$i.' month', $current);
			if($start == null ||  $start <= $dt){
				$ret = $this->_onepage($dt) . $ret;
			}			
			
			$dt = strtotime('+ '.$i.' month', $current);
			if($end == null ||  $end >= $dt){
				$ret .= $this->_onepage($dt);
			}
		}
				
		return '<ul>'.$ret.'</ul>';
		
	}	
	
	
	private function _onepage($dt){
		$monthNum = date('m', $dt);		
		if(($monthNum == '01' || $monthNum == '12') && $this->currentYear != date('Y', $dt)){
			$text = $this->html->date($dt,'M Y', '', '');
		} else {
			$text = $this->html->date($dt,'M', '', '');
		}
		
		if(is_callable($this->url)){
			$url = call_user_func($this->url, $dt);
		} else if(is_string($this->url)){
			$url = $this->getView()->url($this->url,[], ['query' => ['p' => $i]], true);
		} else {
			$url = 'javascript:;';
		}
		return '<li><a href="' . $url . '">'.$text.'</a></li>';
		
	}
	
	

}