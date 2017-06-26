<?
namespace Admin\Forms\Courses;

use Common\Form\Form;
use Common\Traits\Initializable;
use ZfAnnotation\Annotation\Service;
use Admin\Forms\CourseElements;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Courses\CourseDb;
use Admin\Forms\DiscountsElements;

/**
 * @Service(name="Admin\Forms\Courses\TarifsForm")
 */
class TarifsForm extends Form implements Initializable, ServiceManagerAware{
		
	use CourseElements, DiscountsElements, ServiceManagerTrait;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
	
		$courseDb = $this->serv(CourseDb::class);
		
		$this->field('course_id', 'course', 'Курс')
			->validator('required', 'Заполните это поле')
			->options($courseDb);
		
		$this->field('title', 'text', 'Название')->validator('required', 'Заполните это поле');
 		
		$this->field('status', 'checkbox', 'Опубликовано');
			
		$this->field('priority', 'number', 'Приоритет')->description('Число. Чем больше, тем раньше показывается тариф');
		 		
 		// $this->field('type', 'select', 'Тип тарифа');
 		
 		$this->field('price', 'number', 'Базовая цена');
 		
 		$this->field('subscription', 'number', 'Абонемент')->description('Количество встреч, работает только для постоянных мероприятий');
 		
 		$this->field('price_desc', 'text', 'Расшифровка цены');
 		
 		$this->field('discounts', 'discounts', 'Скидки')->label(['text' => 'Скидки']);
 		 		 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
