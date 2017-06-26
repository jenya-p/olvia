<?
namespace Admin\Forms\Content;

use Common\Form\Form;
use Common\Traits\Initializable;
use ZfAnnotation\Annotation\Service;

/**
 * @Service(name="Admin\Forms\Content\TagGroupForm")
 */
class TagGroupForm extends Form implements Initializable{
		
	var $defaultElementDecorator = 'default';
		
	public function init(){
		
		$this->field('name', 'text', 'Название');
 		 		 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
