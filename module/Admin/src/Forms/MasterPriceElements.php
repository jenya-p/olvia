<?
namespace Admin\Forms;

use Common\Form\Element;

trait MasterPriceElements {
	
	protected function htmlMasterPrice(Element $element){
		$extras = $element->extraString();
		
		foreach ($element->options() as $key => $option){
			
			$tag = $element->option($tagId);
			$tagData = $tag->label();

			$ret .= '<tr class="'. ($tagData['status'] ? 'active': '') .'" data-id="'.$tag->value().'">
						<td class="name">'.$tagData['name'].'</td>
						<td class="group-name">'.$tagData['group_name'].'</td>
						<td class="options">
							<a href="javascript:;" class="fa fa-remove tag-refs-remove"></a>
						</td>
					</tr>';
			$ids[] = $tag->value();
		}
		
		$ret = '<table class="item-list">
					<tr>
						<th>Тег</th>		
						<th>Группа</th>
						<th></th>
					</tr>
					'.$ret.'</table>';
		
		$ret .= '<input type="hidden" name="'.$element->name().'" value="'.implode(',', $ids).'" id="'.$element->id().'_hidden" class="tag-refs-hidden"/>'."\n".
				'<input type="text" value="" '.$extras.' placeholder="Добавить тег"/>';
	
		return '<div class="tag-field-wrapper">'.$ret.'</div>';
	}
	
	protected function parseMasterPrice(Element $element, $data){
		if(!isset($data[$element->name()]) || empty($data[$element->name()])){
			$element->value([]);
			return ;
		}
		$val = explode(',', $data[$element->name()]);		
		$element->value($val);
	}
	
	
}