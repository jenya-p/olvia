<?
namespace Common;

class Utils {

	public static function arrayMergePrefixed(array $values, $prefix, array &$src = null){
		if($src === null){
			$src = [];
		}
 		foreach ($values as $key => $value){
 			if(!array_key_exists($prefix.$key, $src)){
 				$src[$prefix.$key] = $value;
 			}
 		}
 		return $src;
	}

	
	public static function arrayExtractPrefixed(array $values, $prefix, array &$dst = null){
		if($dst === null){
			$dst = [];
		}
		$prefixLen = strlen($prefix);
		foreach ($values as $key => $value){			
			if(substr($key, 0, $prefixLen) == $prefix){
				$dst[substr($key, $prefixLen)] = $value;
			}
		}
		return $dst;
	}
	
	public static function arrayDetachPrefixed(array &$values, $prefix, array &$dst = null){
		if($dst === null){
			$dst = [];
		}
		$prefixLen = strlen($prefix);
		foreach ($values as $key => $value){
			if(substr($key, 0, $prefixLen) == $prefix){
				$dst[substr($key, $prefixLen)] = $value;
				unset($values[$key]);
			}			
		}
		return $dst;
	}
		
	public static function arrayFilter($src, $fields, callable $callback = null){
		$ret = [];
		foreach ($fields as $field){
			if(!empty($src[$field])){
				if($callback === null || call_user_func($callback, [$field])){
					$ret[$field] = $src[$field];
				}				
			}
		}		
		return $ret;
	}

	
	public static function arrayFilterAddition(&$src, &$fields, &$strictValues){
		$ret = [];
		foreach ($fields as $field){
			if(!empty($src[$field]) && empty($strictValues[$field])){
				$ret[$field] = $src[$field];
			}
		}
		return $ret;
	}
	
	
	
	public static function urlify($string){		 
		// переводим в транслит
		$string = self::translit($string);		
		// в нижний регистр
		$string = mb_strtolower($string);
		// заменям все ненужное нам на "-"
		$string = preg_replace('~[^-a-z0-9_/]+~u', '-', $string);
		
		// удаляем начальные и конечные '-'
		$string = trim($string, "-");
		
		return $string;
	}
	
	
	public static function translit($string) {
		$converter = array(
				'а' => 'a',   'б' => 'b',   'в' => 'v',
				'г' => 'g',   'д' => 'd',   'е' => 'e',
				'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
				'и' => 'i',   'й' => 'y',   'к' => 'k',
				'л' => 'l',   'м' => 'm',   'н' => 'n',
				'о' => 'o',   'п' => 'p',   'р' => 'r',
				'с' => 's',   'т' => 't',   'у' => 'u',
				'ф' => 'f',   'х' => 'h',   'ц' => 'c',
				'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
				'ь' => '',    'ы' => 'y',   'ъ' => '',
				'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
				'А' => 'A',   'Б' => 'B',   'В' => 'V',
				'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
				'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
				'И' => 'I',   'Й' => 'Y',   'К' => 'K',
				'Л' => 'L',   'М' => 'M',   'Н' => 'N',
				'О' => 'O',   'П' => 'P',   'Р' => 'R',
				'С' => 'S',   'Т' => 'T',   'У' => 'U',
				'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
				'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
				'Ь' => '',    'Ы' => 'Y',   'Ъ' => '',
				'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
		);
		return strtr($string, $converter);
	}

	public static function substrByWord($string, $length){
		if(strlen($string) <= $length){
			return $string; 
		}
		$substr = substr($string, 0, $length);
		$pos = strrpos($substr, " ");
		if($pos == 0){
			return $substr.'...'; 
		}		
		return substr($string, 0, $pos).'...';
	}
	
	
	public function camel2snake($camel){
		$camel=preg_replace('/(?!^)[[:upper:]][[:lower:]]/', '$0', preg_replace('/(?!^)[[:upper:]]+/', '-$0', $camel));
		return strtolower($camel);
	}
	
	public function camelize($dashedString){
		return str_replace('-', '', ucwords($dashedString, '-'));
	}

	
	public static function startOfWeek($time){
		$time = strtotime('midnight', $time);
		$w  = intval(date('w', $time));
		if($w == 0){
			$w = 6;
		} else {
			$w --;
		}
		return $time - 60*60*24*$w;
	}
	
}