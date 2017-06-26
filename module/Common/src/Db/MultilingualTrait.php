<?
namespace Common\Db;

trait MultilingualTrait{

	protected $lang = false;
	protected $langFields = false;
	

	public function lang($lang = null) {
		if($lang === null){
			return $this->lang;
		} else {
			$this->lang = $lang;
			return $this;
		}
	}
	
	public function langField($fieldName){
		if(in_array($fieldName, $this->langFields)){
			return $fieldName.'_'.$this->lang();
		} else {
			return $fieldName;
		}
	}
	
	public function langFields($langFields = null){
		if($langFields === null){
			return $this->langFields;
		} else {
			$this->langFields = $langFields;
			return $this;
		}
	}

	
	public function abstractLanguage(array &$item){
		if(empty($item)) {
			return $item;
		}
		
		if($this->lang !== false){			
			foreach ($this->langFields as $field){				
				if(array_key_exists($field, $item)){	
					$item[$field.'_'.$this->lang] = &$item[$field];
					unset($item[$field]);
				}
			}
		}		
		return $item;
	}
	
	public function concretLanguage(array &$item){
		if(empty($item)) {
			return $item;
		}
		if($this->lang !== false){
			foreach ($this->langFields as $field){
				if(array_key_exists($field.'_'.$this->lang, $item)){
					$item[$field] = &$item[$field.'_'.$this->lang];
					//unset($item[$field.'_'.$this->lang]);
				}
			}
		}
		return $item;
	}
	
}