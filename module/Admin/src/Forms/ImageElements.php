<?
namespace Admin\Forms;

use Common\Form\Element;
use Zend\View\View;
use Common\Traits\ServiceManagerAware;
use Common\ImageService;

trait ImageElements {
	
	protected function htmlImageUpload(Element $element){

		$element->addClass('image-upload');
		$extras = $element->extraString();
			
		$preview = $element->extraParam('preview', null);
		$full = $element->extraParam('full', null);
		if(empty($full)){
			$originalClass = ' hidden';
		}
		$ret = '<input type="hidden" name="'.$element->name().'" value="'.$element->value().'"  '.$extras.' data-url="'.$element->extraParam('url', '').'"/>'."\n".
			'<input type="file" class="image-upload_file"/>'."\n".
			'<a href="javascript:;" style="background-image: url('.$preview.')" class="image-upload_img"></a>
			<span class="loading"><i class="fa fa-upload"></i> Загрузка</span>
			<a href="'.$full.'" class="original'.$originalClass.'"><i class="fa fa-eye"></i> оригинал</a>';
		
		return $ret;
	
	}
	
	
}