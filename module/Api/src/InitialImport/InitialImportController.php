<?
namespace Api\InitialImport;

use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Zend\Mvc\Console\Controller\AbstractConsoleController;
use Common\Db\Adapter;
use Common\Utils;
use Sunra\PhpSimple\HtmlDomParser;
use Admin\Model\Content\VideoDb;
use Common\Traits\LoggerAware;
use Common\Traits\LoggerTrait;
use Common\ImageService;
use Admin\Model\Content\PhotoDb;
use Admin\Model\Content\DiplomDb;
use Admin\Model\Courses\CourseDb;
use Admin\Model\Content\TagDb;
use Admin\Model\Courses\EventDb;
use Zend\Db\Adapter\Exception\InvalidQueryException;
use Admin\Model\Content\ContentDb;
 
class InitialImportController extends AbstractConsoleController 
	implements ServiceManagerAware, LoggerAware{
	
	use ServiceManagerTrait, LoggerTrait;
	
	/**@var Adapter */ 
	var $db;
	
	var $fileBase = 'D:/work/bkps/olvia/files/';
	
	/**@var ImageService */
	var $imageService ;
	
	public function processAction(){		
		
		$this->imageService = $this->serv(ImageService::class);
		$this->imageService->setOverwrite(true);
		 
		$this->db = $this->serv('DbAdapter');
//  	$this->createTempTable();
//  	$this->createContentDivisions();
//  	$this->createContent();
// 	 	$this->createVideoAlbums();
// 		$this->createVideos();
		
// 		$this->createPhotoAlbums();
// 		$this->createPhotos();
		
//		$this->importMasters();

// 		$this->importCourses();
// 		$this->importEvents();
// 		$this->processTags();
		
		$this->createArticles();
		
		echo 'done';
	}
	
	public function createContentDivisions(){
 		
 		$this->db->query('delete from content_division', Adapter::QUERY_MODE_EXECUTE);
		
		$sql = "select cat1_id as id, cat1_name as title_ru from temp_content t
			where (type = 'article' || type = 'page') && cat2_id != 2
			group by cat1_id";
		$result = $this->db->sql($sql);
		
		foreach ($result as $item){
			if(empty($item['title_ru'])){
				continue;
			}
			$item['alias'] = Utils::urlify($item['title_ru']);
			$item['status'] = 1;
			$item['priority'] = $item['id'] ?:0;
			$item['seo_title_ru'] = $item['title_ru'];
			$item['created'] = strtotime('01-01-20017');
			$this->db->insert('content_division', $item);
		}
	}
	
	public function createContent(){			
	
		$this->db->query('delete from content', Adapter::QUERY_MODE_EXECUTE);
		$this->db->query('delete from content_tag_refs where entity = "content"', Adapter::QUERY_MODE_EXECUTE);
		
		$sql = "select 
				id as id,
				type,
				status, 
				created,
				body as body_ru, 
				cat1_id as division_id,
				title as title_ru, 
				title as seo_title_ru
			from temp_content t
			where (type = 'article' || type = 'page') && (cat2_id != 2 || cat2_id is null)";
		
		$result = $this->db->sql($sql);
		
		foreach ($result as $item){
			
			if(empty($item['alias'])){
				$item['alias'] = Utils::urlify($item['title_ru']);
			} else {
				$item['alias'] = Utils::urlify(str_replace('article/', '', $item['alias']));
			}
			$item['priority'] = $item['id'];						
			$this->db->insert('content', $item);
			$this->createTags($item['id'], $item['id'], 'content');
		}
		
	}

	public function createVideoAlbums(){
		
		$this->db->query('delete from content_videoalbums', Adapter::QUERY_MODE_EXECUTE);
	
		$sql = "select cat1_id as id, cat1_name as title_ru from temp_content t
			where (type = 'article') && cat2_id = 2
			group by cat1_id";
		$result = $this->db->sql($sql);
	
		foreach ($result as $item){
			$item['alias'] = Utils::urlify($item['title_ru']);
			$item['status'] = 1;
			$item['priority'] = $item['id'] ?:0;
			$item['seo_title_ru'] = $item['title_ru'];
			$this->db->insert('content_videoalbums', $item);
		}
	}
	
	public function createVideos(){
		$this->db->query('delete from content_videos', Adapter::QUERY_MODE_EXECUTE);
		
		/* @var $videoDb VideoDb */
		$videoDb = $this->serv(VideoDb::class);
		
		$sql = "select
				id as id,
				status,
				created,
				body as body_ru,
				cat1_id as videoalbum_id,
				title as title_ru,
				title as seo_title_ru
			from temp_content t
			where (type = 'article') && cat2_id = 2";
	
		$result = $this->db->sql($sql);
	
		foreach ($result as $item){

			$item['alias'] = Utils::urlify(str_replace('article/', '', $item['title_ru']));
			if(empty($item['alias'])){
				$item['alias'] = Utils::urlify($item['title_ru']);
			} else {
				$item['alias'] = Utils::urlify(str_replace('article/', '', $item['alias']));
			}
			$item['priority'] = $item['id'];
			
			$html = HtmlDomParser::str_get_html($item['body_ru']);
			$htmlIframe = $html->find('iframe', 0);
			if(!empty($htmlIframe)){
				$p = $htmlIframe->parent();
				$p->outertext = '';
				
				try{					
					$item = $item + $videoDb->import($htmlIframe->src);
					$videoDb->importRemoteThumb($item);
					if($item['source'] == VideoDb::SOURCE_YOUTUBE){
						$videoDb->importYoutubeRemote($item);
					}
				} catch (\Exception $e){
				}
				
			} 
			
			if(empty($item['code'])){
				$htmlObject = $html->find('object', 0);
				if(empty($htmlObject)){
					$htmlObject = $html->find('iframe', 0);
				}
				if(empty($htmlObject)){
					$this->logError('Видео не детектировано', ['id' => $item['id'], 'url' => $htmlObject->data]);
					continue;
				}
				$item['html'] = $htmlObject->outertext();
				$item['source'] = VideoDb::SOURCE_HTML;
			}
			
			$htmlH = $html->find('h2, h3', 0);
			if(!empty($htmlH) && strpos(mb_strtolower($htmlH->plaintext), 'содержание') !== false){
				$htmlH->outertext = '';				
			} else {				
				$this->logWarn('Не найден H2', ['id' => $item['id']]);				
			}
			$item['body_ru'] = $html->outertext;
			$item['body_ru'] = str_replace('<p>&nbsp;</p>', '', $item['body_ru']);

			$this->db->insert('content_videos', $item);
		}
	
	}
	
	
	
	
	public function createPhotoAlbums(){
	
		$this->db->query('delete from content_photoalbums', Adapter::QUERY_MODE_EXECUTE);
	
		$sql = "select 
			n.title as title_ru,
			n.title as seo_title_ru,
			n.created,
			0 as priority,
			1 as 'status',
			b.body_value as body_ru
		from olvia_old.ok_field_data_field_gallery g 
		left join olvia_old.ok_node n on n.nid = g.entity_id and g.entity_type = 'node'
		left join olvia_old.ok_field_data_body b on b.entity_id = n.nid
				where n.type = 'training'
			group by n.nid 
			order by n.type, n.created";
		$result = $this->db->sql($sql);
	
		foreach ($result as $item){
			$item['alias'] = Utils::urlify($item['title_ru']);
			$this->db->insert('content_photoalbums', $item);
		}
	}
	
	
	public function createPhotos(){
		
		/* @var $photoDb PhotoDb */
		$photoDb = $this->serv(PhotoDb::class);
		
		$this->db->query('delete from content_photos', Adapter::QUERY_MODE_EXECUTE);
		
		$sql = "
		select
		n.title as photoalbum,
		g.field_gallery_alt as title_ru,
		f.filename,
		f.`status`
		from olvia_old.ok_field_data_field_gallery g
		left join olvia_old.ok_node n on n.nid = g.entity_id and g.entity_type = 'node'
				left join olvia_old.ok_file_managed f on f.fid = g.field_gallery_fid
		
				left join olvia_old.ok_field_data_body b on b.entity_id = n.nid
		
				where n.type = 'training'
				order by n.nid, g.delta asc";
		
		$result = $this->db->sql($sql);
		
		foreach ($result as $item){
			$item['photoalbum_id'] = $this->getPhotoalbumByName($item['photoalbum']);
			unset($item['photoalbum']);
			if(!is_file($this->fileBase.$item['filename'])){
				$this->logError('Не файл', ['filename' => $item['filename']]);
				continue;
			}
			$item['image'] = $this->imageService->import($this->fileBase.$item['filename'], 'photos/'.$item['photoalbum_id'].'/'.$photoDb->getNextId());
			unset($item['filename']);
			$this->db->insert('content_photos', $item);
		}
		
	}
	
	
	public function importMasters(){
		
		$this->db->query('delete from users_masters', Adapter::QUERY_MODE_EXECUTE);
		$this->db->query('delete from content_diplomas where master_id != null', Adapter::QUERY_MODE_EXECUTE);
		$this->db->query('delete from users_master_prices', Adapter::QUERY_MODE_EXECUTE);
		
		$files = glob('D:\work\projects\olvia\public\images\masters\*');
		$files = array_merge($files, glob('D:\work\projects\olvia\public\images\diplomas\*'));
		array_map(unlink, $files);
		
		
 		$sql = "select * from temp_content t where type = 'master'";
 		$result = $this->db->sql($sql);
		$ttlCount = count($result);
		foreach ($result as $item){
			
			$alias = str_replace('master/', '', $item['alias']);
			
			$id = $this->db->fetchOne('select id from users_accounts where displayname =  :name',['name' => $item['title']]);			
			
			if(empty($id)){
				$account = [
						'login' => $alias,
						'password' => null,
						'displayname' => $item['title'],
						'status' => 'new',
						'email' => null,						
						'created' => time()];
				$id = $this->db->insert('users_accounts', $account);
 			}
			
 			$education = $this->db->fetchOne('select e.field_education_value from olvia_old.ok_field_data_field_education e where e.entity_id = :id',  ['id' => $item['id']]);
 			$consultText = $this->db->fetchOne('select e.field_consults_value from olvia_old.ok_field_data_field_consults e where e.entity_id = :id',  ['id' => $item['id']]);
 			$consultText = trim($consultText);
 			$courseCount = $this->db->fetchOne('select count(m.entity_id) from olvia_old.ok_field_data_field_master m where m.field_master_nid = :id', ['id' => $item['id']]);
 			
 			$image = $this->db->fetchOne('select f.uri 
	 					from olvia_old.ok_field_data_field_photo r 
	 					left join olvia_old.ok_file_managed f on f.fid = r.field_photo_fid
						where r.entity_id = :id', ['id' => $item['id']]);
 			
 			if(!empty($image)){
 				$image = str_replace('public://', '', $image);
 				if(!is_file($this->fileBase.$image)){
 					$this->logWarn('Не файл', ['filename' => $image]);
 					$image = null;
 				} else {
 					$image = $this->imageService->import($this->fileBase.$image, 'masters/'.$id);
 				}
 			}
 			
 			$summary = trim(str_replace(["<p></p>","<p></p>\n"], '', $item['body_summary']));
 			if(strpos($summary, '<p>') === false && !empty($summary)){
 				$summary = '<p>'. $summary . '</p>';
 			} 
 			if(empty($summary)){
 				$summary = null;
 			}
 			if(empty($item['body'])){
 				$item['body'] = null;
 			}

 			$master = [ 		
 					'id' => $id, 					
 					'active' => 1, 				
 					'status' => $item['status'],
 					'priority' => $ttlCount - $item['priority'], 				
 					'name_ru' => $item['title'], 				
 					'name_en' => null, 					
 					'summary_ru' => $summary, 			
 					'summary_en' => null, 			
 					'body_ru' => $item['body'], 				
 					'body_en' => null, 				
 					'consultation_ru' => null, 		
 					'consultation_en' => null, 		
 					'education_ru' => $education, 			
 					'education_en' => null, 			
 					'seo_title_ru' => $item['title'], 			
 					'seo_description_ru' => $item['title'], 	
 					'seo_keywords_ru' => $item['title'], 		
 					'seo_title_en' => null, 			
 					'seo_description_en' => null, 	
 					'seo_keywords_en' => null, 		
 					'alias' => $alias,
 					'personal' => !empty($consultText), 				
 					'group' => $courseCount == 0 ? 0 : 1,
 					'image' => $image
 					];
 					
 			$this->db->insert('users_masters', $master);
 			$this->importMasterDiplomas($item['id'], $id);
 			if(!empty($consultText)){
 				$this->importMasterPrices($consultText, $id);
 			}
		}
	}
	
	
	private function importMasterDiplomas($remoteId, $id){
		/* @var $diplomasDb DiplomDb */ 
		$diplomasDb = $this->serv(DiplomDb::class);
		$maxPriority = $this->db->fetchOne('select max(g.delta) FROM olvia_old.ok_field_data_field_gallery g
				left join olvia_old.ok_file_managed f on f.fid = g.field_gallery_fid
				WHERE entity_id = :id', ['id' => $remoteId]);
		
		$items = $this->db->fetchAll('SELECT g.delta, f.uri
				FROM olvia_old.ok_field_data_field_gallery g
				left join olvia_old.ok_file_managed f on f.fid = g.field_gallery_fid
				WHERE entity_id = :id', ['id' => $remoteId]);

		foreach ($items as $item){
			$image = str_replace('public://', '', $item['uri']);
			if(!is_file($this->fileBase.$image)){
				$this->logWarn('Не файл', ['filename' => $image]);
				$image = null;
			} else {
				$nextId = $diplomasDb->getNextId();
				$image = $this->imageService->import($this->fileBase.$image, 'diplomas/'.$nextId);
				$item = [
						'master_id' => $id,
						'priority' => $maxPriority - $item['delta'],
						'title_ru' => 'Диплом',
						'image' => $image
					];				
				$this->db->insert('content_diplomas', $item);
			}			
		}		
	}
	
	
	private function importMasterPrices($consultText, $id){
		$html = HtmlDomParser::str_get_html($consultText);
		$trs = $html->find('tr');
		$priority = count($trs);
		$i = 0;
		foreach ($trs as $tr){
			
			$tds = $tr->find('td');
			if(count($tds) == 2){	
				$name = $tds[0]->text();
				$name = substr(trim(str_replace('&nbsp;', '', $name)), 0, 128);
				$price = $tds[1]->text();
				$price = trim(str_replace('&nbsp;', '', $price));
				$price2 = trim(preg_replace('/(\d)\s(\d)/', '$1$2', $price));
				$price2 = intval($price2);
				$insert = [
						'master_id' => $id,
						'status' => 1,
						'priority' => $priority--,
						'name_ru' => $name,
						'price' => $price2,
						'price_desc_ru' => $price
				];
				$this->db->insert('users_master_prices', $insert);
			}
		}		
	}
	
	public function importCourses(){
		
		$this->db->query('delete from courses', Adapter::QUERY_MODE_EXECUTE);
		$this->db->query('delete from content_tag_refs where entity = "course"', Adapter::QUERY_MODE_EXECUTE);
	
		$sql = "select 
				id, body, body_summary, title
			from temp_content t
			where  (type = 'type_training')";
			
		$result = $this->db->sql($sql);
	
		foreach ($result as $item){
			$this->importCourseOne($item);			
		}
		
	}
	
	public function importCourseOne($item){
		/* @var $courseDb CourseDb */
		$courseDb = $this->serv(CourseDb::class);
		
		$image = $this->db->fetchOne('select f.uri
	 						 	from olvia_old.ok_field_data_field_pictus r
	 					left join olvia_old.ok_file_managed f on f.fid = r.field_pictus_fid
						where r.entity_id = :id', ['id' => $item['id']]);
		
		if(!empty($image)){
			$image = str_replace('public://', '', $image);
			if(!is_file($this->fileBase.$image)){
				$this->logWarn('Не файл', ['filename' => $image]);
				$image = null;
			} else {
				$id = $courseDb->getNextId();
				$image = $this->imageService->import($this->fileBase.$image, 'courses/'.$id);
			}
		}
		
		$alias = Utils::urlify($item['title']);
		$insert = [
				'status' => 1,
				'priority' => 0,
				'title_ru' => 	$item['title'],
				'body_ru' =>    trim($item['body']),
				'summary_ru' =>   trim($item['body_summary']),
				'seo_title_ru' => $item['title'],
				'alias' => $courseDb->uniqueAlias($alias),
				'image' => $image
		];
			
		if(empty($insert['summary_ru']) && !empty($insert['body_ru'])){
			$insert['summary_ru'] = $this->firstParagraphFromHtml($insert['body_ru']);
		}
		
		$id = $this->db->insert('courses', $insert);
			
		$this->createTags($item['id'], $id, 'course');
		
		return $id;
	}
	
	
	public function importEvents(){
		/* @var $courseDb CourseDb */
		$courseDb = $this->serv(CourseDb::class);
		$this->db->query('delete from course_events', Adapter::QUERY_MODE_EXECUTE);
		$this->db->query('delete from course_event_shedule', Adapter::QUERY_MODE_EXECUTE);
		$this->db->query('delete from course_tarifs', Adapter::QUERY_MODE_EXECUTE);
		$this->db->query('delete from course_event2master', Adapter::QUERY_MODE_EXECUTE);
		$this->db->query('delete from course_event2tarif', Adapter::QUERY_MODE_EXECUTE);
	
		$sql = "select
				
				t.id, t.body, t.body_summary, t.title,
				
				t2.title 								as course_title,				
				date.field_date_value 					as date,
				FROM_UNIXTIME(date.field_date_value) 	as date1,
				day.field_day_value 					as day_value,
				cost.field_cost_value 					as cost_value,
				cost.field_cost_summary 				as cost_summary,
				duration.field_duration_value 			as duration_value,
				timeof.field_time_of_value 				as timeof_value	
				
				from olvia.temp_content t				
				left join olvia_old.ok_field_data_field_type_training c on c.entity_id = t.id	
				left join olvia.temp_content t2 on c.field_type_training_nid = t2.id				
				left join olvia_old.ok_field_data_field_date date on date.entity_id = t.id	
				left join olvia_old.ok_field_data_field_day 	day on day.entity_id = t.id	
				left join olvia_old.ok_field_data_field_cost cost on cost.entity_id = t.id	
				left join olvia_old.ok_field_data_field_duration duration on duration.entity_id = t.id	
				left join olvia_old.ok_field_data_field_time_of timeof on  timeof.entity_id = t.id		
				
				where  (t.type = 'training') and date.field_date_value > 1494014400;";
			
		$result = $this->db->sql($sql);
	
		foreach ($result as $item){
			$courseRow = $this->db->fetchRow('select
					t.id, t.body, t.body_summary, t.title
					from olvia.temp_content t
					inner join olvia_old.ok_field_data_field_type_training c on c.field_type_training_nid = t.id
					where  t.type = \'type_training\' and c.entity_id = :cid', ['cid' => $item]);
			if(!empty($courseRow)){
				$courseId = $this->db->fetchOne('select id from courses where title_ru = :title', ['title' => $courseRow['title']]);
				if(empty($courseId)){
					$this->logger->err('Не найден курс', [ 'temp_title' => $courseRow['title'], 'temp_id' => $courseRow['id']]);
				}
			} else {
				$courseId = $this->db->fetchOne('select id from courses where title_ru = :title', ['title' => $item['title']]);
				if(empty($courseId)){
					$courseId = $this->importCourseOne($item);
				}
			}
			
			$day = $item['day_value'];
			
			$insert = [
				'course_id' => $courseId,
				'title_ru' => $item['title'],
				'status' => 1,
				'date_text_ru' => $day,				
				'count' => null,
				'expiration_date' => $item['date']
			];
			
			$insert['time_text_ru'] = $item['timeof_value'];
			if(!empty($item['duration_value']) && !empty($item['timeof_value'])){
				$insert['time_text_ru'] .= ' ';
			}
			if(!empty($item['duration_value'])){
				$insert['time_text_ru'] .= $item['duration_value'];
			}
		
			if(preg_match('/^\d/', $day)){
				$insert['type'] = EventDb::TYPE_SINGLE;
			} else {
				$insert['type'] = EventDb::TYPE_ANNOUNCE;
			};
				
			$eventId = $this->db->insert('course_events', $insert);
			
			if($insert['type'] = EventDb::TYPE_SINGLE){
				$this->db->insert('course_event_shedule', [
					'event_id' => $eventId,
					'date' => $item['date']
				]);
			}
			
			// Добавляем мастеров
			$this->db->query('insert ignore into course_event2master (event_id, master_id)
				select '.$eventId.' as event_id, m1.id as master_id
				from olvia_old.ok_field_data_field_master m
				inner join olvia_old.ok_node mm on mm.nid = m.field_master_nid
				inner join users_masters m1 on m1.name_ru = mm.title
				where m.entity_id = '.$eventId, Adapter::QUERY_MODE_EXECUTE);
			
			$htmlCost  = HtmlDomParser::str_get_html($item['cost_value']);
			
			foreach ($htmlCost->find('p') as $p){
				$txt = str_replace('&nbsp;', '', $p->text());
				if(strlen($txt) > 120){
					$this->logger->warn('3 Пропуск тарифа "'.$txt.'"');
					continue;
				}
				if(strpos(mb_strtolower($txt), "бесплат")){
					$price = 0;
				} else {
					$re = '/(\D|^)(\d{1,2}\s?\d{3})(\D|$)/';
					$matches = [];
					if(preg_match_all($re, $txt, $matches)){
						
						if(count($matches[2]) == 1){							
							$price = $matches[2][0];							
							$price = intval(preg_replace('/\s/','', $price));							
						} else {							
							$this->logger->warn('1 Пропуск тарифа "'.$txt.'"');
							continue;
						}
					} else {
						$this->logger->warn('2 Пропуск тарифа "'.$txt.'"');
						continue;
					}
				}
				
				
				$tarifId = $this->db->fetchOne('select id from course_tarifs t where t.course_id = :courseId and price = :price', ['courseId' => $courseId, 'price' => $price]);
				if(empty($tarifId)){
					$tarifId = $this->db->insert('course_tarifs', [
							'course_id' => $courseId,
							'price' => $price,
							'title_ru' => $txt
					]);
				}
				try{
					$this->db->insert('course_event2tarif', [
							'event_id' => $eventId,
							'tarif_id' => $tarifId ]);
				} catch (InvalidQueryException $e) {}
				
			}
			
			
		}
	
	}
	
	
	public function createTags($nid, $id, $entity){
		
		$otherGroupId =null; 
		
		$result = $this->db->fetchAll('select cat.name from
			olvia_old.ok_taxonomy_index ti
			left join olvia_old.ok_taxonomy_term_data cat on ti.tid = cat.tid
			where ti.nid = :nid', ['nid' => $nid]);
		
		foreach ($result as $row){
			$tagName = $row['name'];
			$tagId = $this->db->fetchOne('select id from content_tags t where t.name_ru = :name', ['name' => $tagName]);
			if(empty($tagId)){
				if(empty($otherGroupId)){
					$otherGroupId = $this->db->fetchOne('select id from content_tag_groups g where g.name_ru = "Другое"');
				}				
				$tagId = $this->db->insert('content_tags', [
						'name_ru' => $tagName,
						'alias' => Utils::urlify($tagName),
						'seo_title_ru' => $tagName,
						'group_id' => $otherGroupId
				]);
			}
		
			$this->db->insert('content_tag_refs', [
					'entity' => 	$entity,
					'item_id' => 	$id,
					'tag_id' => 	$tagId
			]);
				
				
		}	
	}
	
	
	public function processTags(){
		/* @var $tagDb TagDb */
		$tagDb  =$this->serv(TagDb::class ); 
		$result = $this->db->fetchAll("select id, name_ru from content_tags t where t.alias is null || t.alias = ''");
		foreach ($result as $tag){
			$alias = $tagDb->uniqueAlias(Utils::urlify($tag['name_ru']), $tag['id']);			
			$this->db->updateOne('content_tags', $tag['id'], ['alias' => $alias]);
		}
	}
	
	public function getVideoalbumByName($title){
		$sql = "select id from content_videoalbums where title_ru = :title";
		return  $this->db->fetchOne($sql, ['title' => $title]);
	}
	
	public function getContentDivisionByName($title){
		$sql = "select id from content_division where title_ru = :title";
		return  $this->db->fetchOne($sql, ['title' => $title]);
	}
	
	public function getPhotoalbumByName($title){
		$sql = "select id from content_photoalbums where title_ru = :title";
		return  $this->db->fetchOne($sql, ['title' => $title]);
	}
	
	
	public function createTempTable(){
		
		$sql = " 	
drop table  if exists olvia.temp_content;
create table olvia.temp_content
	select n.nid as id, n.type as type, n.title as title, n.status as status, 
	n.created as created,
	b.body_value as body,
	b.body_summary as body_summary,
	a.alias as alias,
	td.name as tag, td.description as tag_description,
	tad.name as type_name, tad.description as type_description,
	sb.field_sortby_value as priority,
	
	GROUP_CONCAT(DISTINCT cat1.tid) as cat1_id,
	GROUP_CONCAT(DISTINCT cat1.name) as cat1_name,
	
	GROUP_CONCAT(DISTINCT cat2.tid) as cat2_id,
	GROUP_CONCAT(DISTINCT cat2.name) as cat2_name,
	
	GROUP_CONCAT(DISTINCT cat3.tid) as cat3_id,
	GROUP_CONCAT(DISTINCT cat3.name) as cat3_name,
	
	GROUP_CONCAT(DISTINCT cat4.tid) as cat4_id,
	GROUP_CONCAT(DISTINCT cat4.name) as cat4_name
	
	from olvia_old.ok_node n
	left join olvia_old.ok_field_data_body b on b.entity_id = n.nid

	left join olvia_old.ok_field_data_field_sortby sb on sb.entity_id = n.nid 

	left join olvia_old.ok_field_data_field_tags t on t.entity_id = n.nid
	left join olvia_old.ok_taxonomy_term_data td on td.tid = t.field_tags_tid
	
	left join olvia_old.ok_field_data_field_type_article ta on ta.entity_id = n.nid
	left join olvia_old.ok_taxonomy_term_data tad on tad.tid = ta.field_type_article_tid

	left join olvia_old.ok_taxonomy_index ti1 on ti1.nid = n.nid
   left join olvia_old.ok_taxonomy_term_data cat1 on ti1.tid = cat1.tid and cat1.vid = 1
	
	left join olvia_old.ok_taxonomy_index ti2 on ti2.nid = n.nid
   left join olvia_old.ok_taxonomy_term_data cat2 on ti2.tid = cat2.tid and cat2.vid = 2
	
   left join olvia_old.ok_taxonomy_index ti3 on ti3.nid = n.nid
   left join olvia_old.ok_taxonomy_term_data cat3 on ti3.tid = cat3.tid and cat3.vid = 3
	
	left join olvia_old.ok_taxonomy_index ti4 on ti4.nid = n.nid
   left join olvia_old.ok_taxonomy_term_data cat4 on ti4.tid = cat4.tid and cat4.vid = 4
	
	left join olvia_old.ok_url_alias a on a.source = CONCAT('node/', n.nid)
--  where n.type = 'article' --	b.body_value as body,
	group by n.nid 
	order by n.nid asc;";
		$this->db->sql($sql);
	}
	
	private function firstParagraphFromHtml($body){
		$html = HtmlDomParser::str_get_html($body);
		$p = $html->find('p,h3,h4,h5', 0);
		if(!empty($p)){
			return trim($p->text());
		} else {
			return null;
		}
				
	}
	
	
	public function createArticles(){
		
		$this->createArticle("Работа с кармой");
		$this->createArticle("Для женщин");
		$this->createArticle("Травматерапия");
		$this->createArticle("Cемейные расстановки");
		$this->createArticle("Узнать свое предназначение");
		$this->createArticle("Структурные расстановки");
		$this->createArticle("Услуги психолога");
		$this->createArticle("Расстановки по Хеллингеру");
		$this->createArticle("Семейный психолог");
		$this->createArticle("Психологический портрет");
		$this->createArticle("Семейная терапия");
		$this->createArticle("Метод расстановок");		
				
		
	}
	
	public function createArticle($srch, $title = null){
		/* @var $contentDb ContentDb */ 
		$contentDb = $this->serv(ContentDb::class);
		if($title == null){
			$title = $srch;
		}
		
		$srch2 = '%'.str_replace(' ', '%', $srch).'%';
		$articles = $this->db->fetchAll('SELECT id, type, title_ru, alias FROM content WHERE title_ru like "'.$srch2.'" and type = "page"');
		
		if(!empty($articles)){
			foreach ($articles as $article){
				echo "\n";
				echo $srch."\t".$article['alias']."\t".$article['title_ru'];
			}			
		} else {			
			$alias = $contentDb->uniqueAlias(Utils::urlify($title));
			$contentDb->insert([
					'title_ru' => $title,
					'seo_title_ru' => $title,
					'alias' => $alias,
					'type' => 'page',
					'body_ru' => '<p>Раздел в разработке</p>'
			]);
			echo "\n";
			echo $srch."\t".$alias;
		}
	}	
	
}