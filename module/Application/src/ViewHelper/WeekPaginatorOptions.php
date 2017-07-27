<?php
namespace Application\ViewHelper;

use Zend\Form\View\Helper\AbstractHelper;
use ZfAnnotation\Annotation\ViewHelper;
use Common\ViewHelper\Html;
use Zend\View\HelperPluginManager;
use Common\Utils;

class WeekPaginatorOptions extends AbstractHelper{
	
	var $url = null;
	
	const WEEK = 7*60*60*24;
	
	/** @var Html */
	protected $html;
	
	public function __construct(HelperPluginManager $vhm){	
		$this->html = $vhm->get('html');	
	}
		
	var $currentMonth = null;
	
	public function __invoke($current, $bounds, $count = 6, $url){
		$this->url = $url;
		
		$current  = Utils::startOfWeek($current);
		
		$this->currentMonth = date('m',$current);
		
		$start = $current - $count * self::WEEK;
		$end = $current + $count * self::WEEK;
		
		if(!empty($bounds) && !empty($bounds['start'])){
			$start = strtotime(date('y-m', $bounds['start']).'-01');
		}
		
		if(!empty($bounds) && !empty($bounds['end'])){
			$end = $bounds['end'];
		}
		
		$ret = $this->weekOption($current, date('y-m-d', $current));
		
		for ($i = 1; $i <= $count; $i++){
			$dt = $current - $i * self::WEEK;
			if($start == null ||  $start <= $dt){
				$ret = $this->weekOption($dt, false) . $ret;
				
			}			
			
			$dt = $current + $i*7*60*60*24;
			if($end == null ||  $end >= $dt){
				$ret = $ret . $this->weekOption($dt, false);
			}
		}
						
		return $ret;
		
	}	
	
	
	public function weekOption($dt, $value){
		$dt2 = $dt + self::WEEK - 60*60;
		$text = $this->html->date($dt2, 'd M');
		if(date('m', $dt2) != date('m', $dt)){
			$text = $this->html->date($dt, 'd M').' - '.$text;
		} else {
			$text = date('d', $dt).' - '.$text;
		}
		
		if(is_callable($this->url)){
			$url = call_user_func($this->url, $dt);
		} else if(is_string($this->url)){
			$url = $this->getView()->url($this->url,[], ['query' => ['p' => $dt]], true);
		} else {
			$url = '';
		}
		
		return $this->html->option($text, date('y-m-d', $dt), $value, 'data-url="'.$url.'"');
		
	}
	

}