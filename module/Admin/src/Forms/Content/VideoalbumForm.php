<?
namespace Admin\Forms\Content;

use Common\Form\Form;
use Common\Traits\Initializable;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use ZfAnnotation\Annotation\Service;

/**
 * @Service(name="Admin\Forms\Content\VideoalbumForm")
 */
class VideoalbumForm extends Form implements ServiceManagerAware, Initializable{
	
	use ServiceManagerTrait;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		$this->field('title', 'text', 'Название');
 		$this->field('alias', 'text', 'УРЛ');
 		$this->field('status', 'checkbox', 'Опубликовано');
 		$this->field('seo_title', 'text', 'SEO Title');
 		$this->field('seo_description', 'textarea', 'SEO Descritpion');
 		$this->field('seo_keywords', 'textarea', 'SEO Keywords');
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
