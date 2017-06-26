<?php
namespace Application\ViewHelper;

use Zend\Form\View\Helper\AbstractHelper;
use ZfAnnotation\Annotation\ViewHelper;
use Common\ViewHelper\Html;
use Zend\View\HelperPluginManager;

class WeekPaginator extends AbstractHelper{
	
	var $url = null;
	
	/** @var Html */
	protected $html;
	
	public function __construct(HelperPluginManager $vhm){	
		$this->html = $vhm->get('html');	
	}
		
	var $currentMonth = null;
	
	public function __invoke($current, $bounds, $count = 1, $url){
		$this->url = $url;
			
		$current = strtotime('midnight', $current);		
		$w  = intval(date('w', $current));
		if($w == 0){
			$w = 6;
		} else {
			$w --;
		}
		$current = $current - 60*60*24*$w;
		
		$this->currentMonth = date('m',$current);
		
		$ret.= '<li><span>'. $this->weekText($current) .'</span></li>';
		
		$start = null;
		$end = null;
		if(!empty($bounds) && !empty($bounds['start'])){
			$start = strtotime(date('y-m', $bounds['start']).'-01');
		}
		
		if(!empty($bounds) && !empty($bounds['end'])){
			$end = $bounds['end'];
		}
		
		for ($i = 1; $i <= $count; $i++){
			$dt = $current - $i*7*60*60*24;
			if($start == null ||  $start <= $dt){
				$ret = $this->_onepage($dt) . $ret;
			}			
			
			$dt = $current + $i*7*60*60*24;
			if($end == null ||  $end >= $dt){
				$ret .= $this->_onepage($dt);
			}
		}
						
		return '<ul>'.$ret.'</ul>';
		
	}	
	
	
	private function _onepage($dt){
				
		if(is_callable($this->url)){
			$url = call_user_func($this->url, $dt);
		} else if(is_string($this->url)){
			$url = $this->getView()->url($this->url,[], ['query' => ['p' => $dt]], true);
		} else {
			$url = 'javascript:;';
		}
		return '<li><a href="' . $url . '">'.$this->weekText($dt).'</a></li>';
		
	}
	
	
	public function weekText($dt){
		$dt2 = $dt+7*24*60*60 - 60*60;
		$ret = $this->html->date($dt2, 'd M');
		if(date('m', $dt2) != date('m', $dt)){
			$ret = $this->html->date($dt, 'd M').' - '.$ret;
		} else {
			$ret = date('d', $dt).' - '.$ret;
		}
		return $ret;
	}
	

}