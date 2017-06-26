<?
namespace Admin\Forms\Courses;

use Common\Form\Form;
use Common\Traits\Initializable;
use Admin\Forms\ImageElements; 	
use ZfAnnotation\Annotation\Service;
use Admin\Model\Content\TagDb;
use Common\Traits\ServiceManagerAware;
use Admin\Forms\TagElements;
use Common\Traits\ServiceManagerTrait;

/**
 * @Service(name="Admin\Forms\Courses\CourseForm")
 */
class CourseForm extends Form implements ServiceManagerAware, Initializable{
	
	use ServiceManagerTrait, ImageElements, TagElements;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){

		$tagDb = $this->serv(TagDb::class);
		
		$this->field('title', 'text', 'Название')
			->validator('required', 'Заполните это поле');
			
		$this->field('alias', 'text', 'URL');
		
		$this->field('status', 'checkbox', 'Опубликовано');
			
		$this->field('priority', 'number', 'Приоритет')->description('Число. Чем больше, тем раньше показывается статья');
		
		$this->field('image', 'image-upload', 'Изображение');
			
		$this->field('summary', 'textarea', 'Краткое описание');
		
		$this->field('body', 'ckeditor', null, ['decorator' => 'simple']);
		 
		$this->field('tags', 'tags', null, ['decorator' => 'simple'])
			->options($tagDb);
		 		 		
 		$this->field('seo_title', 'text', 'SEO Title');
 			
 		$this->field('seo_description', 'textarea', 'SEO Description');
 			
 		$this->field('seo_keywords', 'textarea', 'SEO Keywords');
 		
 		
 		$this->field('shedule', 'text', 'Расписание мероприятий');
 		
 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
