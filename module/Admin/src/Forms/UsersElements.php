<?
namespace Admin\Forms;

use Common\Form\Element;
use Admin\Model\Users\UserDb;
use Common\Traits\ServiceManagerAware;
use Zend\View\HelperPluginManager;
use Zend\View\Helper\Url;

trait UsersElements {
	
	protected function htmlUser(Element $element){
	
		$element->addClass('user-select');
		$extras = $element->extraString();

		$option = $element->option($element->value());
		if(empty($option)){
			$label = '';
		} else {
			$label = $option->label();
			$roles = $option->extra()['roles'];			
		}
		
		$ret = '<input type="hidden" name="'.$element->name().'" value="'.$element->value().'" id="'.$element->id().'_hidden"/>'."\n".
			   '<input type="text" value="'.$label.'" '.$extras.'/><span class="roles">';
		if(!empty($roles) && !empty($element->value())){
			$ret .= $this->renderUserRoles($roles, $element->value());
		}		
		$ret .= '</span>';
		
		return $ret;
	
	}
	
	protected function parseUser(Element $element, $data){
		$val = $data[$element->name()];
		if(empty($val)){
			$val = null;
		}	
		$element->value($val);
	}
	
	protected function renderUserRoles($roles, $id){
		
		if($this instanceof ServiceManagerAware){
			/* @var $vhm HelperPluginManager */
			$vhm = $this->serv('ViewHelperManager');
			$helper = $vhm->get('url');
			
			if($helper instanceof Url){
				
				if(in_array(UserDb::ROLE_ADMIN, $roles)){
					$ret .= '<a href="'.$helper('private/user-edit', ['id' => $id]).'" title="Аккаунт администратора"><i class="fa fa-user-secret"></i></a>';
				} else {
					$ret .= '<a href="'.$helper('private/user-edit', ['id' => $id]).'" title="Аккаунт пользователя"><i class="fa fa-key"></i></a>';
				}
					
				if(in_array(UserDb::ROLE_MASTER, $roles)){
					$ret .= '<a href="'.$helper('private/master-edit', ['id' => $id]).'" title="Профиль специалиста"><i class="fa fa-user-md"></i></a>';
				}
					
				if(in_array(UserDb::ROLE_CUSTOMER, $roles)){
					$ret .= '<a href="'.$helper('private/customer-edit', ['id' => $id]).'" title="Профиль клиента"><i class="fa fa-graduation-cap"></i></a>';
				}
			}
			return $ret;
		}		
	}
	
	protected function htmlMultiUsers(Element $element){
		
		$element->addClass('multiuser-select');
		$extras = $element->extraString();
	
		$ret = '';
		$ids = [];
	
		foreach ($element->value() as $userId){
			$userRow = $element->option($userId);
	
			$ret .= '<tr data-id="'.$userRow->value().'">
						<td class="name">'.$userRow->label().'</td>
						<td class="options">
							'.$this->renderUserRoles($userRow->extra()['roles'], $userRow->value()).'
							<a href="javascript:;" class="fa fa-remove remove"></a>							
						</td>
					</tr>';
			$ids[] = $userRow->value();
		}
	
		$ret = '<table class="item-list">
					<tr>
						<th>Имя</th>
						<th></th>
					</tr>
					'.$ret.'</table>';
	
		$ret .= '<input type="hidden" name="'.$element->name().'" value="'.implode(',', $ids).'" id="'.$element->id().'_hidden" class="multiuser-hidden"/>'."\n".
				'<input type="text" value="" '.$extras.' placeholder="Добавить пользователя"/>';
	
		return '<div class="field-type-multi-user field-inner">'.$ret.'</div>';
	}
	
	protected function parseMultiUsers(Element $element, $data){
		if(!isset($data[$element->name()]) || empty($data[$element->name()])){
			$element->value([]);
			return ;
		}
		$val = explode(',', $data[$element->name()]);
		$element->value($val);
	}
	
	
}