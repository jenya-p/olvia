<?
namespace Admin\Forms\Content;

use Common\Form\Form;
use Common\Traits\Initializable;
use Admin\Forms\UsersElements; 	
use ZfAnnotation\Annotation\Service;
use Admin\Forms\ImageElements;
use Admin\Forms\ReviewRefsElement;

/**
 * @Service(name="Admin\Forms\Content\ReviewForm")
 */
class ReviewForm extends Form implements Initializable{
	
	use ImageElements;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		
 		$this->field('name', 'text', 'Имя автора');
 		$this->field('social', 'text', 'Ссылка в соцсети');
 		$this->field('date', 'date', 'Дата');
 		$this->field('body', 'textarea', 'Текст');
 		$this->field('status', 'checkbox', 'Опубликовано');
 		$this->field('home', 'checkbox', 'Показывать на главной странице');
 			
 		$this->field('userpic', 'image-upload', 'Юзерпик');
 		 		 		 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
