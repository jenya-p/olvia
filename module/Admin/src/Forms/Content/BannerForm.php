<?
namespace Admin\Forms\Content;

use Common\Form\Form;
use Common\Traits\Initializable;
use Admin\Forms\ImageElements; 	
use ZfAnnotation\Annotation\Service;

/**
 * @Service(name="Admin\Forms\Content\BannerForm")
 */
class BannerForm extends Form implements Initializable{
	
	use ImageElements;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		
		$this->field('alias', 'text', 'Внутреннее название')
			->validator('required', 'Заполните это поле');
 		
 		$this->field('link', 'text', 'Ссылка')
 			->validator('required', 'Заполните это поле');
 		$this->field('status', 'checkbox', 'Опубликовано'); 		
 		$this->field('priority', 'number', 'Приоритет')->description('Число. Чем больше, тем раньше показывается статья');
 		$this->field('date_from', 'date', 'Начало показа')->format('d.m.Y H:i');
 		$this->field('date_to', 'date', 'Заверщить показ')->format('d.m.Y H:i');
 		$this->field('countdown', 'checkbox', 'Показывать обратный отсчет');
 		$this->field('body', 'textarea', 'HTML');
 		
 		$this->field('image', 'image-upload', ['text' => ''])
 			->description('Рекомендованная высота: 550px, ширина - от 1180px')
 			->validator('required', 'Загрузите изображение');
 		
 		$this->field('image_m', 'image-upload', ['text' => ''])
 			->description('Рекомендованная ширина - 500px, высота: 250px');
 			
 		$this->field('bg_color', 'text', 'Цвет фона')
 			->description('Валидный CSS цвет');
 		
 		 		 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
