<?
namespace Admin\Forms\Content;

use Common\Form\Form;
use Common\Traits\Initializable;
use ZfAnnotation\Annotation\Service;
use Common\Traits\ServiceManagerTrait;
use Common\Traits\ServiceManagerAware;
use Admin\Model\Users\UserDb;
use Admin\Forms\UsersElements;

/**
 * @Service(name="Admin\Forms\Content\PhotoalbumForm")
 */
class PhotoalbumForm extends Form implements Initializable,ServiceManagerAware{
		
	use ServiceManagerTrait, UsersElements;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		
		//$userDb = $this->serv(UserDb::class);
		
		$this->field('title', 'text', 'Название'); 		

		$this->field('alias', 'text', 'URL');			
		
 		$this->field('status', 'checkbox', 'Опубликовано');
 		
 		$this->field('priority', 'number', 'Приоритет')->description('Число. Чем больше, тем раньше показывается фотография');
 			 		
 		$this->field('seo_title', 'text', 'SEO Title');
 		$this->field('seo_description', 'textarea', 'SEO Descritpion');
 		$this->field('seo_keywords', 'textarea', 'SEO Keywords');
 		
 		$this->field('body', 'ckeditor', ['label' => null])->decorator('simple');
 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
