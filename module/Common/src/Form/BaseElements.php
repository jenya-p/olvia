<?
namespace Common\Form;

use Common\ViewHelper\Phone;
use Common\Utils;

trait BaseElements{
	
	private function htmlSelect(Element $element){
		$options = $element->options();
		$extras = $element->extraString();
		$ret = '<select name="'.$element->name().'" value="'.$element->value().'" '.$extras.'>';		
		foreach ($options as $option){
			$ret .= "\n".$this->htmlOption($option);
		}		
		return $ret.'</select>';
	}
	
	
	private function htmlOption(Option $option){		
		$element = $option->parent();
		$value = $element->value();
		$label = htmlspecialchars($option->label());
		return '<option value="'.$option->value().'" label="'.$label.'"'
				.($option->value() == $value ? ' selected="selected"': '')
				.$option->extraString()
				.'>'.$label.'</option>';
	}
	
	private function htmlCheckboxGroup(Element $element){
		$ret = '';		
		$value = (array)$element->value();
		$name = $element->name().'[]';
		foreach ($element->options() as $option){
			$label = $option->label();			
			$ret .= '<label for="'.$option->id().'" class="checkbox"><input type="checkbox" name="'.$name.'" value="'.$option->value().'" ' 
				.(in_array($option->value(), $value) ? ' checked="checked"': '')
				.$option->extraString()
				.' /><span>'.htmlspecialchars($label).'</span></label>'."\n";
		}
		return $ret;
	}
	
	private function htmlCheckbox(Element $element){
		$label = $element->extraParam('option-label', null) ;
		if(empty($label)){
			$label = $element->label();
			$element->extra(['option-label' => $label]) ;			
			$element->label(null);
		}
		$name = $element->name();
		$value = $element->value();
		$optionValue = $element->extraParam('option-value','1');
		$checked = $optionValue == $value;
		
		return '<label for="'.$element->id().'" class="checkbox"><input type="checkbox" name="'.$name.'" value="'.$optionValue.'" ' 
				.($checked ? ' checked="checked"': '')
				.$element->extraString()
				.' /><span>'.htmlspecialchars($label).'</span></label>';				
	}
		
	
	private function parseCheckbox(Element $element, $data){
		if(!isset($data[$element->name()])){
			$element->value(0);
		}
		$val = $data[$element->name()];
		if(empty($val)){
			$val = 0;
		}
		$element->value($val);
	}
	
	
	private function htmlRadioGroup(Element $element){
		$ret = '';	
		foreach ($element->options() as $option){
			$ret .= '<label for="'.$option->id().'" class="radio"><input type="radio" name="'.$element->name().'" value="'.$option->value().'" '
					.($option->value() == $element->value() ? ' checked="checked"': '')
					.$option->extraString()
					.' /><span>'.htmlspecialchars($option->label()).'</span></label>'."\n";			
		}
		return $ret;
	}
	
	private function htmlSubmit(Element $element){
		$extras = $element->extraString();
		return '<input type="submit" name="'.$element->name().'" value="'.$element->label().'" '.$extras.' />';
	}
	
	private function htmlButton(Element $element){
		$extras = $element->extraString();
		return '<input type="button" name="'.$element->name().'" value="'.$element->label().'" '.$extras.' />';
	}
	
	private function htmlReset(Element $element){
		$extras = $element->extraString();
		return '<input type="reset" name="'.$element->name().'" value="'.$element->label().'" '.$extras.' />';
	}
	
	
	private function htmlSubmitGroup(Element $element){		
		$element->addClass('group');
		$element->addClass('group-buttons');
		$element->addClass('sticky');
		$extras = $element->extraString();
		$cancelUrl = $element->extraParam('cancel-url', null);
		if(!empty($cancelUrl)){
			$ret = '<a href="'. $cancelUrl .'" class="button cancel-button" title="Отмена"><i class="fa fa-arrow-left"></i></a>';
		} else {
			$ret = '';
		}		
		$label = $element->extraParam('label-save');
		$name = $element->name();
		if($label){
			$ret .= ' <input type="submit" name="'.$name.'_save" value= "'.htmlspecialchars($label).'" />';
		}
		$label = $element->extraParam('label-apply');
		if($label){
			$ret .= ' <button type="submit" name="'.$name.'_apply"><i class="fa fa-save"></i>'.htmlspecialchars($label).'</submit>';
		}
		return '<div '.$extras.'>'.$ret.'</div>';
	}
	
	private function parseSubmitGroup(Element $element, $data){
		if(array_key_exists($element->name().'_apply', $data)){
			$element->value('apply');
		} else if(array_key_exists($element->name().'_save', $data)){
			$element->value('save');
		}
	}
	
	private function htmlDate(Element $element){
		$element->addClass('datepicker');
		$extras = $element->extraString();
		$format = $element->extraParam('format', 'd.m.Y');
		$value =$element->value();
		if(!empty($value)){
			$value = date($format, $element->value());
		}		
		return '<input type="text" name="'.$element->name().'" value="'.$value.'" '.$extras.' />';
	}
		
	private function parseDate(Element $element, $data){
		$val = $data[$element->name()];		
		if(empty($val)){
			$element->value(null);
		} else {
			$format = $element->extraParam('format', 'd.m.Y');
			/* @var $dt \DateTime */			
			$dt = \DateTime::createFromFormat($format, $val);
			$element->value($dt->getTimestamp());
		}		
	}
	
	private function htmlHidden(Element $element){
		$extras = $element->extraString();
		return '<input type="hidden" name="'.$element->name().'" value="'.$element->value().'" '.$extras.'/>';
	}
	
	private function htmlPassword(Element $element){
		$extras = $element->extraString();
		return '<input type="password" name="'.$element->name().'" value="'.$element->value().'" '.$extras.'/>';
	}
	
	private function htmlText(Element $element){
		$extras = $element->extraString();
 		return '<input type="text" name="'.$element->name().'" value="'.$element->value().'" '.$extras.'/>';
	}
	
	private function htmlNumber(Element $element){
		return $this->htmlText($element);
	}
	
	private function htmlTextarea(Element $element){
		$extras = $element->extraString();
		return '<textarea name="'.$element->name().'" '.$extras.'>'.$element->value().'</textarea>';
	}
	
	private function htmlSummernote(Element $element){
		$element->addClass('summernote');
		$extras = $element->extraString();
		return '<textarea name="'.$element->name().'" '.$extras.'>'.$element->value().'</textarea>';
	}
	
	private function htmlCkeditor(Element $element){
		$id = $element->id();
		$extras = $element->extraString();
		$name = $element->name();
		return '<textarea name="'.$name.'" '.$extras.' style="min-height:800px;  resize: vertical;">'.htmlentities($element->value()).'</textarea>
<script type="text/javascript">
var waitCKEDITOR_'.$id.' = setInterval(function() {
    if (window.CKEDITOR) {
       clearInterval(waitCKEDITOR_'.$id.');
       CKEDITOR.replace( "'.$id.'", {\'removePlugins\': \'inlinesave\'} );
    }
}, 100/*milli*/);
</script>';		
	}
	
	private function parseCkeditor(Element $element, $data){
		$val = html_entity_decode($data[$element->name()]);
		$element->value($val);
	}
	
	
	private function htmlPhone(Element $element){
		$extras = $element->extraString();
		$value = $element->value();
		$ret = '';
		if(!empty($value)){
			$ret = '<a href="tel:'.$value.'" class="call-link"><i class="fa fa-phone"></i></a>';
		}
		$value = Phone::format($value);
		$ret = '<input type="text" name="'.$element->name().'" value="'.$value.'" '.$extras.'/>'.$ret;
		return $ret;
	}
		
	private function parsePhone(Element $element, $data){
		$val = $data[$element->name()];		
		$element->value(Phone::normalize($val));
	}

	private function htmlSkype(Element $element){
		$extras = $element->extraString();
		$value = $element->value();
		$ret = '';
		if(!empty($value)){
			$ret = '<a href="skype:'.$value.'?chat" title="Написать сообщение в скайп" class="call-link"><i class="fa fa-skype"></i></a>';
		}
		$ret = '<input type="text" name="'.$element->name().'" value="'.$value.'" '.$extras.'/>'.$ret;
		return $ret;
	}
	
	
	private function parseDefault(Element $element, $data){
		$val = $data[$element->name()];
		$element->value($val);
	}
	
	private function parseNumber(Element $element, $data){
		$val = $data[$element->name()];
		$element->value((int)$val);
	}
	
	private function decoratorDefault(\Common\Form\Element $element){
		
		$ret = $this->decoratorSimple($element);
		
		$error = $element->error();
		$ret.='';
		if(!empty($error)){
			$wrapperClass = ' has-error';
			$ret.='<p class="error">'.$error.'</p>';
		} else {
			$wrapperClass = '';		
			$ret.='<p class="error" style="display:none;"></p>';
		}
				
		$description = $element->description();
		if(!empty($description)){
			$ret.='<p class="description">'.$description.'</p>';
		}
		
		$ret = $this->decoratorLabel($element).
			'<div class="field-inner">'.$ret.'</div>';
		
		$id = preg_replace("/[^A-Za-z0-9_]/", '', $element->name());
		
		$ret = '<div class="field field-type-'.$element->type().' field-'.$id.$wrapperClass.'">'.$ret.'</div>';
		return $ret;
	}
	
	
	private function decoratorSimple(Element &$element){
		$methodName = 'html'.Utils::camelize($element->type());
						
		if(!method_exists($this, $methodName)){
			throw new \Exception('element type "'.$element->type().'" not defined' );	
		}
		return $this->$methodName($element);		
	}
	
	private function decoratorSimpleAndErrors(Element &$element){
		$ret = $this->decoratorSimple($element);
		$ret.= $this->decoratorOnlyErrors($element);
		return $ret; 
		
	}
	
	private function decoratorOnlyErrors(Element &$element){
		
		$error = $element->error();
		
		if(!empty($error)){
			return '<p class="error">'.$error.'</p>';
		} else {
			return '<p class="error" style="display:none;"></p>';
		}
	}
	
	function decoratorLabel(Element &$element){
		$label = $element->label();
		if(empty($label)){
			return '<label>&nbsp;</label>';
		}
		if(is_string($label)){
			return '<label>'.$label.'</label>';
		} else if(is_array($label)){
			if(empty($label['text'])){
				return '';
			}
			$tag = empty($label['tag']) ? 'label': $label['tag'];
			$class = empty($label['class']) ? '': ' class="'.$label['class'].'"';
			$extra = empty($label['extra']) ? '': ' '.$label['extra'].'';
			return '<'.$tag.$class.$extra.'>'.$label['text'].'</'.$tag.'>';
		} else if(is_object($label) && method_exists($label, '__toString')){
			return $label->__toString();
		}		
	}
	
}