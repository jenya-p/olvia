<?
namespace Admin\Forms\Content;

use Admin\Forms\ImageElements;
use Admin\Forms\UsersElements;
use Common\Form\Form;
use Common\Traits\Initializable;
use ZfAnnotation\Annotation\Service;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Users\UserDb;

/**
 * @Service(name="Admin\Forms\Content\DiplomForm")
 */
class DiplomForm extends Form implements ServiceManagerAware, Initializable{
	
	use ServiceManagerTrait, ImageElements, UsersElements;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		
		$userDb = $this->serv(UserDb::class);
		
		$this->field('title', 'text', 'Название')->validator('required', 'Заполните это поле');
		
		$this->field('status', 'checkbox', 'Опубликовано');
		
		$this->field('home', 'checkbox', 'Показывать на главной');
 		
 		$this->field('priority', 'number', 'Приоритет')
 			->description('Число. Чем больше, тем раньше показывается раздел');
 		 			
 		$this->field('master_id', 'user', 'Преподаватель')
 			->options($userDb);
 		
 		$this->field('image', 'image-upload', ['text' => null])->validator('required', 'Загрузите изображение диплома');
 		 		 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
