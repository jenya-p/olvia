<?
namespace Admin\Forms\Content;

use Common\Form\Form;
use Common\Traits\Initializable;
use ZfAnnotation\Annotation\Service;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Content\TagGroupDb;

/**
 * @Service(name="Admin\Forms\Content\TagForm")
 */
class TagForm extends Form implements Initializable, ServiceManagerAware{
		
	use ServiceManagerTrait;
	
	var $defaultElementDecorator = 'default';
	
	public function init(){
	
		$tagGroupsDb = $this->serv(TagGroupDb::class);
		$tagGroups = array_merge([0 => 'Без группы'], $tagGroupsDb->options());
		
		$this->field('name', 'text', 'Название');
 		$this->field('alias', 'text', 'УРЛ');
 		$this->field('group_id', 'select', 'Группа')
 			->options($tagGroups);
 		$this->field('status', 'checkbox', 'Опубликовано');
 		$this->field('filter', 'checkbox', 'В фильтре')->description('Тег отображается в фильтре курсов на сайте');
 		$this->field('seo_title', 'text', 'SEO Title');
 		$this->field('seo_description', 'textarea', 'SEO Descritpion');
 		$this->field('seo_keywords', 'textarea', 'SEO Keywords');
 		 		 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
	
} 
