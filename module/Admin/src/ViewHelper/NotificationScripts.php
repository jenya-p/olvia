<?php
namespace Admin\ViewHelper;

use ZfAnnotation\Annotation\ViewHelper;
use Zend\View\Helper\InlineScript;
use Zend\View\Renderer\PhpRenderer;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Common\Db\Adapter;

/**
 * @ViewHelper(name="adminNotificationScripts")
 * */
class NotificationScripts extends \Zend\View\Helper\AbstractHelper implements ServiceManagerAware{
	
	use ServiceManagerTrait;
	
	public function __invoke(){
		
		$view = $this->getView();
		if(!$view instanceof PhpRenderer){return;}
		
		$helper = $view->getHelperPluginManager()->get('inlineScript');
		if(!$helper instanceof InlineScript){return;}

		$counts = $this->getCounts();
		
		$script = '';
		if(!empty($counts['total'])){
			$script .= "$('#side-menu .fa.fa-inbox').parent('a').find('span').append('<span class=\"count-notifier\">". $counts['total'] ."</span>')\n";
		}
		
		if(!empty($counts['call'])){
			$script .= "$('#side-menu .notification-call').parent('a').find('span').append('<span class=\"count-notifier\">". $counts['call'] ."</span>')\n";
		}
		
		if(!empty($counts['orders'])){
			$script .= "$('#side-menu .notification-orders').parent('a').find('span').append('<span class=\"count-notifier\">". $counts['orders'] ."</span>')\n";
		}
		
		if(!empty($counts['consultations'])){
			$script .= "$('#side-menu .notification-consultations').parent('a').find('span').append('<span class=\"count-notifier\">". $counts['consultations'] ."</span>')\n";
		}
		
		$helper->appendScript($script);
		
	}
	
	
	private function getCounts(){
		$sql = "select 'orders' as name, count(*) from order_orders o where o.`status` = 'new'
			union
			select 'call' as name, count(*) from order_call o where o.`status` =  'new'
			union
			select 'consultations' as name, count(*) from order_consultations o where o.`status` =  'new'";
		
		
		/* @var $db Adapter */
		$db = $this->serv('DbAdapter');
		$data = $db->fetchPairs($sql);
		$total = 0;
		foreach ($data as $item){
			$total  += intval($item);
		};
		$data['total'] = $total;
		
		return $data;
	}
	
}

