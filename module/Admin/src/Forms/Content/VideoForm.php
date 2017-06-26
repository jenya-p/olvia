<?
namespace Admin\Forms\Content;

use Common\Form\Form;
use Common\Traits\Initializable;
use Admin\Forms\UsersElements; 	
use ZfAnnotation\Annotation\Service;
use Admin\Model\Users\UserDb;
use Common\Traits\ServiceManagerAware;
use Admin\Model\Content\VideoalbumDb;
use Common\Traits\ServiceManagerTrait;
use Admin\Forms\ImageElements;

/**
 * @Service(name="Admin\Forms\Content\VideoForm")
 */
class VideoForm extends Form implements ServiceManagerAware, Initializable{
	
	use UsersElements, ImageElements, ServiceManagerTrait;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		$userDb = $this->serv(UserDb::class);
		
		$this->field('title', 'text', 'Название');
 		$this->field('alias', 'text', 'Внутренний УРЛ');
 		$this->field('status', 'checkbox', 'Опубликовано');
 		$this->field('top', 'checkbox', 'Блок "Самое интересное"'); 			
 		
 		$this->field('priority', 'number', 'Приоритет')
 			->description('Число. Чем больше, тем раньше показывается фотография');
 		
 		$this->field('link', 'text', 'Ссылка на ролик')->description('Ссылка на Youtube или Vimeo');
 		
 		$this->field('html', 'textarea', 'HTML')->description('Код для вставки видео с других сайтов');
 		 		
 		$this->field('videoalbum_id', 'select', 'Альбом')
 			->options([$this, 'albumOptions']);
 		
 		$this->field('author', 'user', 'Автор')
 			->options($userDb);

 		$this->field('created', 'date', 'Дата');
 			
 		$this->field('seo_title', 'text', 'SEO Title');
 		$this->field('seo_description', 'textarea', 'SEO Descritpion');
 		$this->field('seo_keywords', 'textarea', 'SEO Keywords');
 		
 		$this->field('thumb', 'image-upload', 'Изображение');
 		  		
 		$this->field('body', 'ckeditor', null, ['decorator' => 'simple']);
 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
	
	
	public function albumOptions(){
		$albumDb = $this->serv(VideoalbumDb::class);
	
		$options = ['' => ['label' => 'Не определено', 'class' => 'empty']];
		$options = $options + $albumDb->options();
		return $options;
	}
} 
