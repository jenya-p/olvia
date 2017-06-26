<?
namespace Common\Form;

trait BaseValidators{
	
	private function validatorRequired(Element $element, $message){		
		$val = $element->value();
		if(empty($val)){
			$element->error(sprintf($message, $val));
		}
		return true;
	}
	
	private function validatorRegexp(Element $element, $message, $regexp){
		$val = $element->value();
		
		if(!preg_match($regexp, $val)){
			$element->error(sprintf($message, $val));
		}
		
		return true;
	}
	
	private function validatorEmail(Element $element, $message){
		$val = $element->value();
	
		if(!empty($val) && !filter_var($val, FILTER_VALIDATE_EMAIL)){
			$element->error(sprintf($message, $val));
		}
	}
	
	private function validatorNumber(Element $element, $message, $min = null, $max = null){
		$val = $element->value();		
		if(!is_numeric($val)){
			$m = $this->extractMuliMessage($message, Constants::VALIDATOR_NUMBER_NOT_NUMERIC);
			$element->error(sprintf($m, $val));
			return;
		}
		
		if($min !== null && $val < $min){
			$m = $this->extractMuliMessage($message, Constants::VALIDATOR_NUMBER_TOO_SMALL);
			$element->error(sprintf($m, $val, $min));
			return;
		}
		
		if($max !== null && $val > $max){
			$m = $this->extractMuliMessage($message, Constants::VALIDATOR_NUMBER_TOO_BIG);
			$element->error(sprintf($m, $val, $max));
			return;
		}
	}
	
	
	private function extractMuliMessage($message, $index){
		if(is_string($message)){
			return $message;
		} else if(is_array($message) && array_key_exists($index, $message)){
			return $message[$index];
		} else {
			return $index;
		}
	}
}