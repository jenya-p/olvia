<?php
namespace Common\ViewHelper;

use Zend\Form\Form;
use Zend\Form\Element;
use ZfAnnotation\Annotation\ViewHelper;


class Html extends \Zend\View\Helper\AbstractHelper{
	
	var $versionHash = '';
	public function __construct($config){
		if(!empty($config['versionHash'])){			
			$this->versionHash = '?'.$config['versionHash'];
		}
	}
	
	public function __invoke(){		
		return $this;
	}	
	
	static $month = array(
			'01' => 'Январь',   '07' => 'Июль',      '01s' => 'Января',   '07s' => 'Июля',
			'02' => 'Февраль',  '08' => 'Август',    '02s' => 'Февраля',  '08s' => 'Августа',
			'03' => 'Март',     '09' => 'Сентябрь',  '03s' => 'Марта',    '09s' => 'Сентября',
			'04' => 'Апрель',   '10' => 'Октябрь',   '04s' => 'Апреля',   '10s' => 'Октября',
			'05' => 'Май',      '11' => 'Ноябрь',    '05s' => 'Мая',      '11s' => 'Ноября',
			'06' => 'Июнь',     '12' => 'Декабрь',   '06s' => 'Июня',     '12s' => 'Декабря' );
	
	static $week = array('Воскресение', 'Понедельник','Вторник','Среда','Четверг','Пятница','Суббота');
	
	function hdate($date){
		if($date < 1000) {
			return '-';
		}
		$day = 86400;
		$ymd = date('ymd', $date);
		
		$minutesDelta = round((time() - $date) / 60);
		
		if ($minutesDelta >= -1 && $minutesDelta < 6) {
			$desc = 'только что';
		} else if ($minutesDelta > 0 && $minutesDelta < 60) {
			$desc = sprintf('%s минут назад', $minutesDelta);
		
		} else if ($minutesDelta > 0 && $minutesDelta < 60) {
			$desc = sprintf('Через %s минут', $minutesDelta);
		
		} else if (date('ymd', time()) == $ymd) {
			$desc = sprintf('Сегодня в %s', date('H:i', $date));
		
		} else if (date('ymd', time() - $day) == $ymd) {
			$desc = sprintf('Вчера в %s', date('H:i', $date));
		
		} else if (date('ymd', time() + $day) == $ymd) {
			$desc = sprintf('Завтра в %s', date('H:i', $date));
		
		} else if (date('y', time()) == date('y', $date)) {
			$desc = $this->date($date, 'j M');
		
		} else {
			$desc = $this->date($date, 'j M Y');
		}
		
		return '<span class="htime" data-time="'.$date.'" title="'.$this->date($date, 'l, j M Y в H:i').'">'.$desc.'</span>' ;
	}
	
	function date($date, $format = "j M Y", $alt = null, $padej = 's'){
		if(empty($date)) return $alt;
		$format = str_replace('M', '{-/-/0}', $format);
		$format = str_replace('l', '{-/-/1}', $format);		
		$str = date($format, $date);
		$str = str_replace('{-/-/0}', self::$month[date('m', $date).$padej], $str);		
		$str = str_replace('{-/-/1}', self::$week[date('w', $date)], $str);		
		return $str;
	}
	
	function hours($time){
		$h = floor($time / (60*60));
		$time = $time - $h*60*60;
		$m = floor($time / 60);
		return number_format($h, 0, '', ' ').":".date("i", $time);
	}
	
	function time($time){
		$h = floor($time / 3600);
		$time -= $h*3600;
		$m = floor($time / 60);
		$s = $time - $m*60;
		$ret = '';
		if($h != 0){
			$ret .= $h.':';
		}
		if($m != 0 || $h != 0){
			if($m < 10){
				$ret .= '0'.$m.':';
			} else {
				$ret .= $m.':';
			}			
		}
		if($s < 10){
			$ret .= '0'.$s;
		} else {
			$ret .= $s;
		}
		if($m == 0 && $h == 0){
			$ret .= ' сек.';
		}
		return $ret; 
	}
	
	var $odd = false;	
	function odd($odd = null){
		if($odd!==null){
			$this->odd = $odd;
		} else {
			$this->odd = !$this->odd;
		}
		if($this->odd){
			return ' odd';
		} else {
			return '';
		}
	}
	
	/**
	 *  Вывод ссылок для сортирвки, GET это склейка поля для сортировки и направления. разделенная "_". Например:
	 *  ?sort_by=date_desc  
	 * @param string $param имя параметра для GET
	 * @param string $alias название поля по которому происходит сортировка 
	 * @param string $value текущее значение параметра сортировки 
	 * @param string $cssClass доп класс для ссылки
	 * @return string: href="..." class="..."
	 */ 
	function orderHref($param, $alias, $value, $cssClass="test"){
		list($o, $d) = explode('_', $value);
		if($o != $alias){
			return (' href="?'.$param.'='.$alias.'_desc"').(empty($cssClass) ? '': ' class="'.$cssClass.'"');
		}else {
			if($d == 'desc'){
				return ' href="?'.$param.'='.$alias.'_asc" class="'.(empty($cssClass) ? 'desc"': $cssClass.' desc"');
			} else {
				return ' href="?'.$param.'='.$alias.'_desc" class="'.(empty($cssClass) ? 'asc"': $cssClass.' asc"');
			}
		}
	}
	
	
	function option($lbl, $option, $value, $ex=null){
		return '<option value="'.$option.'" lable="'.htmlspecialchars($lbl).'"'
				.($option == $value ? ' selected="selected"': '')
				.(empty($ex) ? '': ' '.$ex)
				.$ex.'>'.$lbl.'</option>';
	}
	
	function checkbox($lbl, $name, $option, $value, $ex=null){
		$id = preg_replace("/[^A-Za-z0-9_]/", '', $name.'_'.$option);
		if(is_bool($value)){
			$checked = $value;
		} if(is_array($value)){
			$checked = in_array($option, $value);
		} else {
			$checked = ($option == $value); 
		}
		return '<label for="'.$id.'" class="checkbox"><input type="checkbox" name="'.$name.'" value="'.$option.'" id="'.$id.'"'
				.($checked ? ' checked="checked"': '')
				.(empty($ex) ? '': ' '.$ex)
				.'><span>'.htmlspecialchars($lbl).'</span></label>';
	}
	
	function radio($lbl, $name, $option, $value, $ex=null){
		$id = preg_replace("/[^A-Za-z0-9_]/", '', $name.'_'.$option);
		
		return '<label for="'.$id.'" class="radio"><input type="radio" name="'.$name.'" value="'.$option.'" id="'.$id.'"'
				.($option == $value ? ' checked="checked"': '')
				.(empty($ex) ? '': ' '.$ex)
				.'><span>'.htmlspecialchars($lbl).'</span></label>';
	}
	
	public function iif($p1, $p2){
		if(!empty($p1)) return $p1;
		if(!empty($p2)) return $p2;
	}
	
	
	/** Склонение слова в зависимости от числа
	 * @param string $root - товар...
	 * @param int $count - количество
	 * @param string $c1 - 1 - '', товар
	 * @param string $c2 - 2 - 'а', товара
	 * @param string $c3 - 5 - 'ов', товаров
	 * @param boolean $showCount показывать ли кол-во перед словом
	 * @return string
	 */
	function grammar($root, $count, $c1, $c2, $c3, $showCount = false) {
		$num = $count % 10;
		$pre = $showCount ? $count . ' ' : '';
		if ($count < 10 || $count > 20) {
			switch ($num) {
				case 1: return $pre . $root . $c1;
				break;
				case 2: return $pre . $root . $c2;
				break;
				case 3: return $pre . $root . $c2;
				break;
				case 4: return $pre . $root . $c2;
				break;
				default: return $pre . $root . $c3;
			}
		} else {
			return $pre . $root . $c3;
		}
	}
	
	/**
	 * Ссылки для сортировки
	 */
	function sorter($param, $filter, $baseUrl='', $filterName = 'f'){
		$ret = ' data-sort="'.$param.'"';
		if($filter['sort'] == $param){
			$order = $filter['order'];
			$ret .= ' data-order="'.$order.'"';
			$order = ($order == 'asc')? 'desc': 'asc';			
		} else {
			$order = 'asc';
		}		
		$url = $baseUrl.'?'.$filterName.'[sort]='.$param;
		$url .= '&'.$filterName.'[order]='.$order;
		return $ret.' data-href="'.$url.'"';
	}
	
	function pages($current, $total, $showWrapper = true){
		$delta = 7;
		$ret.= '';
		if($current > $delta + 1){
			$ret.= $this->_onepage(1, $current);
			if($current > $delta + 2){
				$ret.='<span>... </span>';
			}
		}
				
		for ($i = max(1, $current - $delta); $i <= min($total, $current + $delta); $i++) {
			$ret .= $this->_onepage($i, $current);
		}
		
		if($current < $total - $delta ){
			if($current < $total - $delta -1){
				$ret.='<span>...</span>';
			}
			$ret.= $this->_onepage($total, $current);			
		}
		
		if($showWrapper){
			$ret = '<div class="pagination">
			<label>Страницы:</label> '.$ret.'
			</div>';
		}
		
		return $ret;
	}
	
	private function _onepage($i, $current){
		if ($current == $i) {
			return '<span >'.$i.'</span> ';
		} else {			
			return '<a href="' . $this->getView()->url(null,['p' => $i], [], true) . '" class="pager-link" data-page="'.$i.'">'.$i.'</a> ';
		}
	}
	
	public function ver(){
		return $this->versionHash;
	}
	
	public function translateFormMessage(Form $form, $element, $messageName, $message){
		if($form->has($element)){
			$element = $form->get($element);
			if($element instanceof  Element){
				if(!empty($element->getMessages()[$messageName])){
		    		$element->setMessages([$messageName => $message]);
		    	}
			}
		}		
		
		return $this;
	}
	
	
	public function plain2html($txt){
		if(strpos($txt, '<p') == false){
			return implode('\n', array_map(function($part){
				return '<p>'.$part.'</p>';
			}, explode('\n', $txt)));			
		} else {
			return $txt;
		}
	}
}