<?php

if(!file_exists('config.php')) {
	copy('config.php.default', 'config.php');
}
include_once 'config.php';

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$imageFolder = 'images';
$connection = new PDO($config['dbconnection']['dsn'], $config['dbconnection']['username'], $config['dbconnection']['passwd']);

function t($string, $arg = false) {
    global $trans;
    
    if(isset($trans[$string]) AND array_key_exists(1, $trans[$string])) {
            $newstring = $trans[$string][1];
    }  else
        $newstring = $string;
    
    if($arg != false ) {
        if(is_numeric($arg)) {
            $newstring = preg_replace('/%d/', $arg, $newstring);
        } elseif (is_string($arg)) {
            $newstring = preg_replace('/%s/', htmlentities($arg, ENT_QUOTES, 'UTF-8'), $newstring);
        }                
    }
    
    return $newstring;  
}

function twigFilter_t($string, $arg = false) {
  return t($string, $arg);       
}

function twigFilter_timeago($datetime) {
   
  if(is_numeric($datetime)) $time = time() - $datetime;
  else $time = time() - strtotime($datetime); 

 
  $units = array (
    31536000 => ['év', 'éve'],
    2592000 => ['hónap', 'hónapja'],
    604800 => ['hét', 'hete'],
    86400 => ['nap', 'napja'],
    3600 => ['óra', 'órája'],
    60 => ['perc', 'perce'],
    1 => ['másodperc', 'másodperce']
  );

  foreach ($units as $unit => $val) {
    if($time > 0) {  
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits." ".$val[1];
        
    } else {
        if ($time < (-1 * $unit  ) ) {
        $numberOfUnits = floor($time / ( -1 * $unit) );
        return $numberOfUnits." ".$val[0]. " múlva";
        }
        
    }
  }
  
  return 'xx';

  };

function loadTranslation($lang) {
		
    $filePath = 'locale/'.$lang.'.csv';
	$csv = loadTranslationFile($filePath);
    
	global $config;
	if(isset($config['addons'])) {
		foreach($config['addons'] as $addon) {
			$added = loadTranslationFile("addons/".strtolower($addon)."/".$filePath);
			if($added)
				$csv = array_merge($csv,$added);
		}
	}
	return $csv;
}

function loadTranslationFile($filePath) {
	
	if(!file_exists($filePath)) 
        return false;
    
	//echo $filePath."<br/>";   
    
	$rows = array_map(function($v){return str_getcsv($v, ";","\"");}, file($filePath));
    
    $csv = [];
    foreach($rows as $row) {
        $csv[$row[0]] = $row; 
    }   
    return $csv;
}

function str_split_unicode($str, $l = 0) {
	if ($l > 0) {
		$ret = array();
		$len = mb_strlen($str, "UTF-8");
		for ($i = 0; $i < $len; $i += $l) {
			$ret[] = mb_substr($str, $i, $l, "UTF-8");
		}
		return $ret;
	}
	return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

function recursiveupdate($original, $new) {
	foreach( $new as $key => $value ) {
		if(is_array($original)) {
			if(!isset($original[$key])) $original[$key] = $value;
			else {
				if(!is_array($value)) $original[$key] = $value;
				else {
					$original[$key] = recursiveupdate($original[$key],$value);
				}						
			}
		}
		elseif(is_object($original)) {
			if(!isset($original->$key)) $original->$key = $value;
			else {
				if(!is_array($value)) $original->$key = $value;
				else {
					$original->$key = recursiveupdate($original->$key,$value);
				}						
			}
		}
	}
	return $original;
}

// TODO: Biztonsági rés? A belépési oldalon rögtön be lehet lépni ha küldik a haselt változatot
// Ezt felülírta már a loginHelpre->login() oda át kéne írni mindezeket!!
function getUser($username, $passwd) {     
    global $config;
    
    /* 
     * Authentication with array defined in config.php 
     */
    if(isset($config['authentication']['array'])) {
        // átjutott már!   
    }

    /* Authentication with CSV file */
    if(isset($config['authentication']['csv'])) {
        $csv = $config['authentication']['csv'];
        #defaults
        if(!isset($csv['delimeter'])) $csv['delimeter'] = ';';

        if(!isset($csv['path']) OR !file_exists($csv['path'])) {                    
            throw new Exception("Configuration error: Authentication by 'csv' is misconfigured.");
        }    


        if(isset($array['md5']) AND $array['md5'] != false ) {
            if($array['md5'] === true ) $salt = $config['authentication']['salt'];
            else  $salt = $array['md5'];                
        } else {
            $salt = $config['authentication']['salt'];
        }   

        $rows = str_getcsv(file_get_contents($csv['path']),"\n");
        foreach($rows as $row) {
            $data = str_getcsv($row,$csv['delimeter']);


        //    if($username = $data[0] AND ( $passwd = $data[1]  OR $passwd = md5($salt.$data[1]) )  )

            if ($data[0] == $username  AND ( $data[1] == $passwd OR $data[1] == md5($salt.$passwd) OR $passwd == md5($salt.$data[1]) ) ) {
        

                 # Hash nélkül van nálunk a jelszó. Az nem jó ötlet.
                if(!preg_match('/^[a-f0-9]{32}$/', $passwd)) {
                    $data[1] = md5($salt.$passwd) ;
                }

                return  [
                        'tanaz' => $data[0],
                        'tanazonosito' => $data[1],
                        'tannev' => $data[2],
                        'tanosztaly' => $data[3]
                    ];

            }

        }
        
        
    }


    /*
     * Authentication with any type of pdo like mysql
     * see: https://www.php.net/manual/en/pdo.construct.php
     */
    if(isset($config['authentication']['pdo'])) {
        
        $pdo = $config['authentication']['pdo'];
        if(!isset($pdo['dsn']) OR !isset($pdo['username']) OR !isset($pdo['passwd'])) {                    
            throw new Exception("Configuration error: Authentication by 'pdo' is misconfigured.");
        }        
        #defaults
        if(!isset($pdo['table'])) $pdo['table'] = 'users';
        if(!isset($pdo['mapping'])) $pdo['mapping'] = [];
        if(!isset($pdo['mapping']['username'])) $pdo['mapping']['username'] = 'username';
        if(!isset($pdo['mapping']['passwd'])) $pdo['mapping']['passwd'] = 'passwd';
        if(!isset($pdo['mapping']['name'])) $pdo['mapping']['name'] = 'name';
        if(!isset($pdo['mapping']['group'])) $pdo['mapping']['group'] = 'group';
        
        #hash and salt
        if(isset($pdo['md5']) AND $pdo['md5'] != false ) {            
            if($pdo['md5'] === true ) $salt = '';
            else  $salt = $pdo['md5'];                
        } else {
            $salt = $config['authentication']['salt'];
        }
        
        $connection = new PDO($pdo['dsn'], $pdo['username'], $pdo['passwd']);
        $stmt = $connection->prepare(""
                . "SELECT ".implode(',',$pdo['mapping'])." "
                . "FROM ".$pdo['table']." "
                . "WHERE "
                        . $pdo['mapping']['username']." = :username AND ( "
                        . "md5(concat('".$salt."',".$pdo['mapping']['passwd'].")) = :passwd OR " 
                        . $pdo['mapping']['passwd']." = :passwd OR "
                        . $pdo['mapping']['passwd']." = md5(concat('".$salt."',:passwd)) "
                        . ") LIMIT 1");                        
        $stmt->execute(['username' => $username,'passwd' => $passwd]);         
        if(($stmt->errorInfo())[1] != '') 
            throw new Exception("\nPDOStatement::errorInfo(): \n".($stmt->errorInfo())[2]);
        
        $data = $stmt->fetchAll();   
        if(count($data) == 1) {
            $row = $data[0];
            # Hash nélkül van nálunk a jelszó. Az nem jó ötlet.
            if(!preg_match('/^[a-f0-9]{32}$/', $passwd)) {
                $row[1] = md5($salt.$passwd) ;
            }

            return [
                'tanaz' => $row[0],
                'tanazonosito' => $row[1],
                'tannev' => $row[2],
                'tanosztaly' => $row[3]
            ];    
        }        
    }             
}

function getGroupSizes() {
   global $config, $connection, $development;
   
   $groups = [];
   
   $sql = "SELECT `groups`.*, count(*) as members 
	FROM users 
            LEFT JOIN `groups` 
                        ON `groups`.id = users.group_id 
            WHERE users.active = 1 ";
   
   if(!$development) $sql .= " AND users.name NOT LIKE '".Bulk::prefix()."%' AND `groups`.name NOT LIKE '".Bulk::prefix()."%' ";
   $sql .=" GROUP BY `groups`.id
            ORDER BY members
    ";
   
   $stmt = $connection->prepare($sql);
   $stmt->execute();
   $results = $stmt->fetchAll();
   
    foreach($results as $result) {
           $groups[$result['name']] = $result['members'];
    }
    
   return $groups;
   
   // TODO: alább
   # A $config-ot itt nem nagyon ellenőrizzük, mert a getUser már úgyis megtette
   
   if(isset($config['authentication']['array'])) { 
       foreach($config['authentication']['array'] as $user) {
           if(isset($user[3]) AND is_array($user)) {
               if(!isset($groups[$user[3]])) $groups[$user[3]] = 0;
               $groups[$user[3]]++;
           }
       }
   }

   if(isset($config['authentication']['pdo'])) {
        $pdo = $config['authentication']['pdo'];

        //TODO: egy helyen legyen a defaultsozás. Val a config-ba!
        #defaults
        if(!isset($pdo['table'])) $pdo['table'] = 'users';
        if(!isset($pdo['mapping'])) $pdo['mapping'] = [];
        if(!isset($pdo['mapping']['username'])) $pdo['mapping']['username'] = 'username';
        if(!isset($pdo['mapping']['passwd'])) $pdo['mapping']['passwd'] = 'passwd';
        if(!isset($pdo['mapping']['name'])) $pdo['mapping']['name'] = 'name';
        if(!isset($pdo['mapping']['group'])) $pdo['mapping']['group'] = 'group';
        

        $connection = new PDO($pdo['dsn'], $pdo['username'], $pdo['passwd']);
        $stmt = $connection->prepare(""
                . "SELECT ".$pdo['mapping']['group']." "
                . "FROM ".$pdo['table']." ");                        
        $stmt->execute(); 
        if(($stmt->errorInfo())[1] != '') 
            throw new Exception("\nPDOStatement::errorInfo(): \n".($stmt->errorInfo())[2]);
        
        $data = $stmt->fetchAll();
        foreach($data as $row) {
            if($row[0] != '') {            
                if(!isset($groups[$row[0]])) $groups[$row[0]] = 0;
                $groups[$row[0]]++;
            }
        }
   }


   if(isset($config['authentication']['csv'])) {
       
        $csv = $config['authentication']['csv'];
        #defaults
        if(!isset($csv['delimeter'])) $csv['delimeter'] = ';';

        if(file_exists($csv['path'])) {            
            $rows = str_getcsv(file_get_contents($csv['path']),"\n");
            foreach($rows as $row) {
                $data = str_getcsv($row,$csv['delimeter']);

                if(!isset($groups[$data[3]])) $groups[$data[3]] = 0;
                $groups[$data[3]]++;
            }
        } else if ($config['debug']) {
            throw new Exception(t('File not found: %s',$csv['path']));
        } 
    }
   return $groups;
}



function getRankingTable($quiz_id) {
    global $connection, $development, $config;
    
    /* Ranglista összeállítása */
        
    $sql = "
        SELECT 
            users.group_id, 
            groups.name , 
            count(distinct user_id) as members,
       
            count(if(result = '-1', 1, null)) * ".$config['scoring']['badAnswer']."
                +  ( count(if(result = '1', 1, null))* ".$config['scoring']['goodAnswer']." ) 
                    +  ( count(if(result = '2', 1, null))* ".$config['scoring']['goodAnswer']." ) 
                        as points 
        
        FROM `answers`
            LEFT JOIN users ON users.id = answers.user_id
            LEFT JOIN `groups` ON groups.id = users.group_id 
        
        WHERE quiz_id = :quiz_id 
            AND users.active = 1 

        ";      
    
    
    if(!$development)    $sql .= "AND "
            . "timestamp <> '".Bulk::date()."' AND "
            . "groups.name NOT LIKE '".Bulk::prefix()."%' AND "
            . "users.name NOT LIKE '".Bulk::prefix()."%' ";

    $sql .=  " GROUP BY group_id"
            . " ORDER BY points DESC";    
    //echo "<br>".$sql."<br>";
    $stmt = $connection->prepare($sql);
    $stmt->execute(array(':quiz_id'=> $quiz_id));
    $ranglista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    
    $groupSizes = getGroupSizes();    
    
    /* Eltávolítjuk azokat a ranglistából, akik most épp nem férnek hozzá az anyaghoz *
    foreach($ranglista as $key => $group) {        
        if(!array_key_exists($group['tanosztaly'], $groupSizes)) {
            unset($ranglista[$key]);
        }
    }
       
    /* Osztálylétszámmal korrigált változat */
    if(isset($config['scoring']['groupSizeCorrection']) AND $config['scoring']['groupSizeCorrection'] != false ) {
        $groupSizeCorrection = $config['scoring']['groupSizeCorrection'];
        if(is_numeric($groupSizeCorrection)) {
            $groupSizeCorrection;
        } elseif ($groupSizeCorrection == 'min') {
            $groupSizeCorrection = min($groupSizes);
        } elseif ($groupSizeCorrection == 'max') {
            $groupSizeCorrection = max($groupSizes);
        } elseif ($groupSizeCorrection == 'avg') {
            $groupSizeCorrection = (int) ( array_sum($groupSizes)/count($groupSizes) );
        } else {
            throw new Exception("Configuration error: invalid 'scoring/groupSizeCorrection'!");
        }
        
        foreach($ranglista as $key => $group) {       
            if(!array_key_exists($group['name'], $groupSizes)) $groupSizes[$group['name']] = $groupSizeCorrection;
            $ranglista[$key]['points'] = (int) ( ( $groupSizeCorrection / $groupSizes[$group['name']] ) * $ranglista[$key]['points'] );
        }
        
    }
    /* */
      
    /* Egy kis igazítás azzal, hogy hányan csináltak bármit az osztályból */
    foreach($ranglista as $key => $group) {
        $ranglista[$key]['points'] += ( $ranglista[$key]['members'] * $config['scoring']['forEachParticipants'] );
    }
    
    /* Ki szedjük a DEV csoportot */
    global $development;
    if(!$development) {
        foreach($ranglista as $key => $value) {
            if($value['name'] == 'DEV') {
                unset($ranglista[$key]);
            }
        }
    }
    
    /* Sorbarendezés */
    usort($ranglista, function ($item1, $item2) {
        return $item2['points'] <=> $item1['points'];
    });
    
    $return = [];
    foreach($ranglista as $key => $value) {
        $value['position'] = $key + 1;
        $return[$value['name']] = $value;
    }   
    return $return; 
   
}

function hook($class, $function, $postfix, $param) {
	global $config;
	if(isset($config['addons'])) {
		  foreach($config['addons'] as $addon ) {
			$function = $class."_".$function."_".$postfix;			
			if(method_exists ($addon, $function)) {
				$result = $addon::$function($param);
			}
		  }
	}


}

function printr($anything) {
    echo "<pre>".print_r($anything,1)."</pre>";
}

spl_autoload_register(function ($class_name) {
    $filename = $class_name . '.php';
    if(file_exists($filename)) include $filename;
    elseif(file_exists('common/'.$filename)) include 'common/'.$filename;
    elseif(file_exists(strtolower('common/'.$filename))) include strtolower('common/'.$filename);    
});


function getParam( &$arr, $name, $def=null, $type=null) {    
    if (isset( $arr[$name] )) {
        if ($type == 'int') return intval($arr[$name]);
        if ($type == 'f') return floatval($arr[$name]);
            return $arr[$name];
    } else {
        return $def;
    }
}

 /**
    * Generates human-readable string.
    * https://gist.github.com/sepehr/3371339
    * 
    * @param string $length Desired length of random string.
    * 
    * retuen string Random string.
    */ 
   function readable_random_string($length = 6, $seed = false)
   {  
       $string = '';
       $vowels = array("a","e","i","o","u");  
       $consonants = array(
           'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 
           'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'
       );  

       $max = $length / 2;
       for ($i = 1; $i <= $max; $i++)
       {
			if($seed != false) { srand( crc32($seed) + ( $i * 1000000 ) ); }
			else srand();
			
           $string .= $consonants[rand(0,19)];
           $string .= $vowels[rand(0,4)];
       }

       return $string;
   }


   function slugify($text, string $divider = '-')
{

  $search = ['Ș', 'Ț', 'ş', 'ţ', 'Ş', 'Ţ', 'ș', 'ț', 'î', 'í', 'â', 'á', 'ă', 'Î', 'Ă', 'ë', 'Ë','é','É','ö','Ö','ő','Ő','ó','Ó','ú','Ú','ü','Ü','ű','Ű'];
  $replace = ['s', 't', 's', 't', 's', 't', 's', 't', 'i', 'i', 'a', 'a', 'a', 'i', 'a', 'e', 'E','e','e','o','o','o','o','o','o','u','u','u','u','u','u'];
  $text = str_ireplace($search, $replace, strtolower(trim($text)));

  // replace non letter or digits by divider
  $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

  // transliterate
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

  // remove unwanted characters
  $text = preg_replace('~[^-\w]+~', '', $text);

  // trim
  $text = trim($text, $divider);

  // remove duplicate divider
  $text = preg_replace('~-+~', $divider, $text);

  // lowercase
  $text = strtolower($text);

  if (empty($text)) {
    return 'n-a';
  }

  return $text;
}

/**
 * TextToImage class
 * This class converts text to image
 * https://www.codexworld.com/convert-text-to-image-php/
 *
 * @author    CodexWorld Dev Team
 * @link    http://www.codexworld.com
 * @license    http://www.codexworld.com/license/
 */
class TextToImage {
    private $img;
    
    /**
     * Create image from text
     * @param string text to convert into image
     * @param int font size of text
     * @param int width of the image
     * @param int height of the image
     */
    function createImage($text, $fontSize = 20){

		$angle = 0;
        $font = dirname(__FILE__).'/Montserrat-Bold.ttf';
		$padding = 40; 
		$lineheight = 2;
		
		$text = str_replace("<br/>","\\n",$text);
		//break lines
        $splitText = explode ( "\\n" , $text );
        $lines = count($splitText);
				
		$textsHeight = 0;
		$textsMaxWidth = 0;
		foreach($splitText as $row) {
			$textBox = imagettfbbox($fontSize,$angle,$font,$row);
            $textWidth = abs(max($textBox[2], $textBox[4]));
			$textHeight = abs(max($textBox[5], $textBox[7]));
			if($textWidth > $textsMaxWidth) $textsMaxWidth = $textWidth;
			$textsHeight += ( $textHeight * $lineheight );            
		}
				
        //create the image
		$imgWidth = $textsMaxWidth + (2 * $padding );
		$imgHeight = ( $lines * $lineheight * $fontSize ) + (2 * $padding ) - $fontSize;
        $this->img = imagecreatetruecolor($imgWidth, $imgHeight );
        
        //create some colors
        $white = imagecolorallocate($this->img, 255, 255, 255);
        $grey = imagecolorallocate($this->img, 128, 128, 128);
        $black = imagecolorallocate($this->img, 0, 0, 0);
		
		imagefilledrectangle($this->img, 0, 0, $imgWidth, $imgHeight, $white);
		//$this->addBorder(2, 5);
		        
        foreach($splitText as $i => $txt){
			//add some shadow to the text            
			imagettftext($this->img, $fontSize, $angle, $padding, $padding + ( $fontSize ) + ( $i * $fontSize * $lineheight), $grey, $font, $txt);

            //add the text
            imagettftext($this->img, $fontSize, $angle, $padding, $padding + ( $fontSize ) + ( $i * $fontSize * $lineheight), $black, $font, $txt);
        }
	return true;
    }
	
	function addBorder($thickness, $padding ) {
		$imgWidth = imagesx($this->img);
		$imgHeight = imagesy($this->img);
		
		$color = imagecolorallocate($this->img, 0, 0, 0);
	
		imagefilledrectangle($this->img, $padding, $padding, $imgWidth - $padding, $padding + $thickness, $color);
		imagefilledrectangle($this->img, $padding, $padding, $padding + $thickness, $imgHeight - $padding, $color);
		imagefilledrectangle($this->img, $imgWidth - $padding - $thickness, $padding + $thickness, $imgWidth - $padding, $imgHeight - $padding, $color);
		imagefilledrectangle($this->img, $padding, $imgHeight - $padding - $thickness, $imgWidth - $padding, $imgHeight - $padding, $color);
		
	
	}
    
    /**
     * Display image
     */
    function showImage(){		
        header('Content-Type: image/png');
        return imagepng($this->img);
    }
    
    /**
     * Save image as png format
     * @param string file name to save
     * @param string location to save image file
     */
    function saveAsPng($fileName = 'text-image', $location = ''){
        $fileName = $fileName.".png";
        $fileName = !empty($location)?$location.$fileName:$fileName;
        return imagepng($this->img, $fileName);
    }
    
    /**
     * Save image as jpg format
     * @param string file name to save
     * @param string location to save image file
     */
    function saveAsJpg($fileName = 'text-image', $location = ''){
        $fileName = $fileName.".jpg";
        $fileName = !empty($location)?$location.$fileName:$fileName;
        return imagejpeg($this->img, $fileName);
    }
}