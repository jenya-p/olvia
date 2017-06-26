<?php
namespace Application\ViewHelper;

use Zend\Form\View\Helper\AbstractHelper;
use ZfAnnotation\Annotation\ViewHelper;

/**
 * @ViewHelper("sitePaginator")
 */
class SitePaginator extends AbstractHelper{
	
	var $url = null;
		
	public function __invoke($current, $total, $url){
		$this->url = $url;
		
		$delta = 4;
		$ret.= '';
		
 		if($current > $delta + 1){
 			$ret.= $this->_onepage(1, $current);
 			if($current > $delta + 2){
 				$ret.='<li><span>... </span></li>';
 			}
 		}
		
		for ($i = max(1, $current - $delta); $i <= min($total, $current + $delta); $i++) {			
			$ret .= $this->_onepage($i, $current);
		}
		
		if($current < $total - $delta ){
			if($current < $total - $delta -1){
				$ret.='<li><span>...</span></li>';
			}
			$ret.= $this->_onepage($total, $current);
		}
		
		return '<ul>'.$ret.'</ul>';
		
	}	
	
	
	private function _onepage($i, $current){
		if ($current == $i) {
			return '<li><span>'.$i.'</span></li>';
		} else {
			if(is_callable($this->url)){
				$url = call_user_func($this->url, $i);
			} else if(is_string($this->url)){
				$url = $this->getView()->url($this->url,[], ['query' => ['p' => $i]], true);
			} else {
				$url = 'javascript:;';
			}
			return '<li><a href="' . $url . '" class="pager-link" data-page="'.$i.'">'.$i.'</a></li>';
		}
	}
	
	

}