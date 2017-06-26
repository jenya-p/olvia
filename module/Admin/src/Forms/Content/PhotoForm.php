<?
namespace Admin\Forms\Content;

use Common\Form\Form;
use Common\Traits\Initializable;
use Admin\Forms\ImageElements; 	
use ZfAnnotation\Annotation\Service;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Content\PhotoalbumDb;

/**
 * @Service(name="Admin\Forms\Content\PhotoForm")
 */
class PhotoForm extends Form implements ServiceManagerAware, Initializable{
	
	use ImageElements, ServiceManagerTrait;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		
		
		$this->field('title', 'text', 'Заголовок');
		
 		$this->field('status', 'checkbox', 'Опубликовано');
 		
 		$this->field('priority', 'number', 'Приоритет')->description('Число. Чем больше, тем раньше показывается фотография');
 		
 		$this->field('photoalbum_id', 'select', 'Альбом')
 			->options([$this, 'albumOptions']);
 		
 		$this->field('image', 'image-upload', 'Изображение');
 		 		 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
	
	public function albumOptions(){
		$albumDb = $this->serv(PhotoalbumDb::class);

		$options = ['' => ['label' => 'Не определено', 'class' => 'empty']];
		$options = $options + $albumDb->options();
		return $options;
	}
	
} 
