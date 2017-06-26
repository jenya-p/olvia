<?
namespace Admin\Forms;

use Common\Form\Element;

trait DiscountsElements {
	
	protected function htmlDiscounts(Element $element){
	
		$value = $element->value();
		if(!is_string($value)){
			$valueStr = json_encode($value);
		} else {
			$valueStr = $value;
			$value = json_decode($value);
		}
		$ret = '';
		foreach ($value as $valItem){
			$ret .= '<p>Скидка <input type="text" value="'. $valItem->discount .'" class="discount-inp discount"/> рублей за <input type="text" value="'. $valItem->days .'" class="discount-inp days"/> дня до начала
				<a href="javascript:" class="delete" title="Удалить скидку"><i class="fa fa-remove"></i></a></p>';
		}
		
		$ret = '<div class="discount-wrp">'.$ret.'</div>'; 
		$ret .= '<p><a href="javascript:" class="button add-discount">Добавить скидку</a></p>';
		$ret .= '<input type="hidden" name="'.$element->name().'" value="'. htmlspecialchars($valueStr) .'" id="'.$element->id().'_hidden" class="discounts-hidden"/>'."\n";
		
		return $ret;
	}
	
	protected function parseDiscounts(Element $element, $data){
		if(!isset($data[$element->name()]) || empty($data[$element->name()])){
			$element->value([]);
			return ;
		}
		$val = json_decode($data[$element->name()]);
		$element->value($val);
	}
	
	
}