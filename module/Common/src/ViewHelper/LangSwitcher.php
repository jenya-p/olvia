<?
namespace Common\ViewHelper;

use Zend\Form\View\Helper\AbstractHelper;
use ZfAnnotation\Annotation\ViewHelper;
use Common\Form\Form;

/**
 * @ViewHelper(name="langSwitcher")
 */
class LangSwitcher extends AbstractHelper{
	
	var $langArray = ['ru' => 'Рус', 'en' => 'Eng'];
	var $lang = "ru";
	
	
	public function __invoke($langArray = null , $lang = null){
		
		if($langArray !== null){
			if($langArray instanceof Form){
				// $this->langArray = $langArray->i18n();
				$this->lang($langArray->lang());
			} else {
				$this->langArray = $langArray;
				$this->lang($lang);
			}			
		}		
		return $this;
	}
	
	public function lang($lang){
		if($lang !== null){
			$this->lang = $lang;
		}
		return $this;
	}
	
	
	public function __toString(){
		$ret = '<ul class="form-lang-switcher">';
		foreach ($this->langArray as $key => $lable){
			$url = $this->getView()->url(null, ['lang' => $key], null, true);
			if ($this->lang == $key){
				$ret .= '<li><span>'.$lable.'</span></li>';
			} else {
				$ret .= '<li><a href="'.$url.'" >'.$lable.'</a></li>';
			}
				
		}
		$ret .= '</ul>';
		return $ret;
	}
	
	
}