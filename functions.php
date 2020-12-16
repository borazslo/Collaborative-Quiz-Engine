<?php

include_once 'config.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$imageFolder = 'images';
$connection = new PDO($config['dbconnection']['dsn'], $config['dbconnection']['username'], $config['dbconnection']['passwd']);


$bulkDate = '2010-01-01 12:12:12' ;

$trans = loadTranslation('hu_HU');

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

function loadTranslation($lang) {
    $filePath = 'locale/'.$lang.'.csv';
    if(!file_exists($filePath)) 
        return false;
       
    $rows = array_map(function($v){return str_getcsv($v, ";","\"");}, file($filePath));
    
    $csv = [];
    foreach($rows as $row) {
        $csv[$row[0]] = $row; 
    }   
    return $csv;
}

// TODO: Biztonsági rés? A belépési oldalon rögtön be lehet lépni ha küldik a haselt változatot
function getUser($username, $passwd) {     
    global $config;
    
    /* 
     * Authentication with array defined in config.php 
     */
    if(isset($config['authentication']['array'])) {
        $array = $config['authentication']['array'];
        
        if(!is_array($array) OR count(array_values($array)[0]) != 4) {
            throw new Exception("Configuration error: Authentication by 'array' is misconfigured.");
        }  
        
        if(isset($array['md5']) AND $array['md5'] != false ) {
            if($array['md5'] === true ) $salt = $config['authentication']['salt'];
            else  $salt = $array['md5'];                
        } else {
            $salt = $config['authentication']['salt'];
        }

        foreach($array as $row) {
            if ($row[0] == $username  AND ( $row[1] == $passwd OR $row[1] == md5($salt.$passwd) OR $passwd == md5($salt.$row[1]) ) ) {
                              
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
   global $config;
   
   $groups = [];
   
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

function getValaszok($tanaz) {
    global $connection;
    
    $stmt = $connection->prepare("SELECT kerdesid, kerdesid, valasz, helyes, timestamp FROM valaszok WHERE tanaz = :tanaz ");
    $stmt->execute(['tanaz' => $tanaz]); 
    return $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
            
}

function loadCSV(string $filename) {
    global $config;
    if(!file_exists($filename)) {
        if($config['debug'])
            throw new Exception (t("File not found: %s",$filename));
        else
            return [];
    }
    $lines = explode( "\n", file_get_contents( $filename ) );   
    $headers = str_getcsv( array_shift( $lines ) );
    $data = array();
    foreach ( $lines as $line ) {

            $row = array();

            foreach ( str_getcsv( $line ) as $key => $field )
                    $row[ $headers[ $key ] ] = $field;

            $row = array_filter( $row );

            $data[] = $row;

    }
    return $data;
}

function loadKerdesek(string $filename) {
   global $user, $imageFolder, $development;

   $kerdesek = loadCSV($filename);   
   unset($kerdesek[0]); unset($kerdesek[1]);
   
   $scores = getScores();
      
   foreach($kerdesek as $key => $kerdes) {
       if(!array_key_exists('question', $kerdes) OR !array_key_exists('answer', $kerdes)) {
           unset($kerdesek[$key]);
       } else {
        
           
        /* Ha van menne [puzzle] kép, akkor azt belerakjuk */
           if(preg_match('/\[puzzle\:(.*?)\]/',$kerdes['question'],$match)) {

               $dir = $imageFolder."/puzzle_".$match[1];
               if(is_dir($dir)) {
                    $files = preg_grep('~\.(JPG|jpeg|jpg|png)$~', scandir($dir));
                    $files = array_values($files);
                    
                    //Összerakjuk a válsztható állományt
                    $choices = [];
                    foreach($files as $file) {
                        $tmp = explode('.',$file);
                        unset($tmp[count($tmp)-1]);                    
                        $choices[] = implode('.',$tmp);                        
                    }
                    sort($choices);                    
                    $kerdesek[$key]['choices'] = $kerdes['choices'] = implode(';',$choices);
                                                                            
                    // Kicsiknek osztályonként más. Nagyoknál mindenkinek más.
                    if(preg_match('/^[5-8]{1}(A|B|S)$/', $user['tanosztaly'])) {
                        $file = $files[bindec(md5($user['tanosztaly'])) % count($files)];
                    } else {
                        $file = $files[bindec(md5($user['tanaz'])) % count($files)];
                    }
                                        
                    //$file = $files[array_rand($files)];                                        
                    
                    $path = $dir.'/'.$file;

                        if(!isset($scores[$user['tanosztaly']]) AND ( $user['tanosztaly'] == 'DEV' OR $user['tanosztaly'] == '')) {
                            $percentage = rand(1,50);
                        }
                        else 
                        if(!array_key_exists($user['tanosztaly'], $scores)) {
                            $percentage = 1;
                        } else 
                            $percentage = $scores[$user['tanosztaly']]['jatekos'];
                    
                    //TODO: Ez csak a tanároknak extra!    
                    $percentage = 8; //rand(1,15);
                    
                    $filename = $imageFolder."/puzzle_".md5($path."_".$percentage).'.jpg';
                    if(!file_exists($filename)) {
                       //Nem az igazi, mert egészen újat generál mindig, nem pedig növekszik szépen.
                       //TODO: nem törli a régit
                       $newFile = createImagePuzzle($path,$percentage);
                    }
                    //TODO: csak tanároknak
                    else 
                        $newFile = createImagePuzzle($path,$percentage);

                    $imgTag = '<img src="'.$filename.'" class="img-thumbnail mx-auto d-block">';                    
                    $kerdesek[$key]['question'] = str_replace($match[0], $imgTag, $kerdes['question']);
                    
                    $tmp = explode('.',$file);
                    unset($tmp[count($tmp)-1]);                    
                    $kerdesek[$key]['answer'] = implode('.',$tmp);
               }
                                     
           }
           
        /* Van, hogy több kérdés van egy sorban, több válasszal. Akkor választ egyet. */
        if(preg_match('/\|/',$kerdes['question'])) {
            $questions = explode('|',$kerdes['question']);
            $answers = explode('|',$kerdes['answer']);
            #$variation = round(1 + (hexdec(md5($user['tanazonosito'])) / hexdec("FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")) * (count($questions) - 1));            
            $variation = bindec(md5($user['tanazonosito'])) % count($questions);
            
            
            $kerdesek[$key]['question'] = $questions[$variation];
            $kerdesek[$key]['answer'] = $answers[$variation];            
        }
           
           
        $kerdesek[$key]['id'] = $key;
        $kerdesek[$key]['inputType'] = 'text';
        
        /* Ha van link, akkor beformázzuk a tippet */
        if(array_key_exists('url1', $kerdes)) {
            
            //Ha van könnyebb url, akkor azt kapják a kicsik         
            if(array_key_exists('url2', $kerdes) AND preg_match('/^[5-8]{1}(A|B|S)$/', $user['tanosztaly'])) {
                $kerdesek[$key]['url1'] = $kerdes['url1'] = $kerdesek[$key]['url2'];
            }

            
            
            if(preg_match('/\/maps\//i', $kerdes['url1'])) {
                $kerdesek[$key]['hint'] = 'Talán errefelé érdemes körülnézni: <a class="text-decoration-none" target="_blank" href="'.$kerdes['url1'].'">Google Street View</a>.';
                                
            } elseif (preg_match('/youtube/i', $kerdes['url1'])) {
                                
                $kerdesek[$key]['hint'] = ''
                        . '<div class="embed-responsive embed-responsive-16by9">'
                        . '<iframe class="embed-responsive-item" src="'.preg_replace('/watch\?v=/','embed/',$kerdes['url1']).'" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
                        . '</div>';
                $kerdesek[$key]['hint'] .= '<br/>Itt egy videó, ami segíthet: <a class="text-decoration-none" target="_blank" href="'.$kerdes['url1'].'">YouTube</a>.';           
                                
                
            } elseif (preg_match('/^http/i', $kerdes['url1'])) {
                $kerdesek[$key]['hint'] = 'Itt egy link, ami segíthet: <a class="text-decoration-none" target="_blank"  href="'.$kerdes['url1'].'">KATTINTS</a>!';
            } else {
               $kerdesek[$key]['hint'] = 'Egy tipp: '.$kerdes['url1']; 
            }             
                    
        }
        
        
       /* Ha listából lehet választani, akkor betöltjük */
        if(array_key_exists('choices', $kerdes)) {
             $options = explode(';',$kerdes['choices']);
            foreach($options as $option) {
                $kerdesek[$key]['options'][] = trim($option);
            }
            $kerdesek[$key]['inputType'] = 'select';
        }
        
        /* Hi file feltöltős a dolog */
        if($kerdes['answer'] == '[file]') {
            $kerdesek[$key]['inputType'] = 'file';
        }
        
       }
       
       
       
   }
   //return ['37'=> $kerdesek[37] ];
   return $kerdesek;
   
    
}

function currentQuestions($kerdesek, $gameConfig) {
    global $config;
    
    $gameConfig['startTime'];
    $c = 0;
    foreach($kerdesek as $key => $kerdes ) {
        if($c == 0) {
            $lastStart = $currentStart = $kerdesek[$key]['startTime'] = $gameConfig['startTime'];            
        } else {
            if(!isset($kerdes['relativeStart'])) {
                $currentStart = strtotime('+'.$gameConfig['questionDefaultFrequency'], $lastStart);
            } else {
                $currentStart = strtotime('+'.$kerdes['relativeStart'], $lastStart);
            }
            $lastStart = $kerdesek[$key]['startTime'] = $currentStart;                                                
        }
        
        if(!isset($kerdes['duration'])) {
            $currentEnd = $kerdesek[$key]['endTime'] = strtotime('+'.$gameConfig['questionDefaultDuration'], $currentStart);
        } else {
            $currentEnd = $kerdesek[$key]['endTime'] = strtotime('+'.$kerdes['duration'], $currentStart);
        }        
        $c++;
        
        /*
         * Delete non active questions
         */
        $now = time();
        if( ( $currentStart > $now OR $currentEnd < $now ) AND !$config['debug']) {
            unset($kerdesek[$key]);
        }                
    }
    return $kerdesek;
        
}

function uploadImage($_file) {
    
    if( ! file_exists($_file['tmp_name']) ) {
        return ['error' => 'Nem található az ideiglenesen feltöltött file. Mi hibánk.'];
    }
    
    //Képség ellenőrzése, mert másképp át lehetne verni.
    $image_data = @getimagesize($_file['tmp_name']);    
    if( $image_data === false ) {
        return ['error' => 'Uupsz, '.$_file['name'].' nem is kép. Képet kérünk!'];
    }
       
    // Ha nincs GD, akkor gond van. TODO: nagy képekkel de úgyis működjön
    if(!function_exists('imagecreatefromstring')) {
       return ['error' => 'Nem tudunk képeket  átméretezni. Mihibánk.'];
       // Megoldás: GD azaz apt-get install php7.3-dev ÉS? apt-get install libjpeg-dev libfreetype6-dev
    }
 
    // Átméretezés, ha szükséges
    $image = imagecreatefromstring( file_get_contents( $_file['tmp_name']));
    $box = 1200;
    if($image_data[1] > $image_data[0] AND $image_data[1] > $box ) { //álló és túl nagy
        $newWidth = $image_data[0] * ( $box / $image_data[1]);
        $image = imagescale($image, $newWidth);
    } else if($image_data[0] > $image_data[1] AND $image_data[0] > $box ) { // fekvő és túl nagy
       $image = imagescale($image, $box);
    }    
    //Mentés
    global $imageFolder;
    $filename = md5(date('Y-m-d H:i:s')."-".rand(100,999)).".jpg"; //Igénytelen random név
    imagejpeg ($image,$imageFolder."/".$filename);
    return $imageFolder."/".$filename;    
}

function bulkAnswers() {
    global $connection;
    
    global $server, $dbname2, $dbuser, $dbpassword;
    $connectionJezsu = new PDO("mysql:host=$server;dbname=$dbname2;charset=utf8", $dbuser, $dbpassword);
    
    $kerdesek = loadKerdesek('kerdesek.csv');
    
    
    global $bulkDate, $imageFolder;
    
    $stmt = $connectionJezsu->prepare("SELECT * FROM tanulok ORDER BY RAND() LIMIT :random ");
    $stmt->bindValue(':random', (int) rand(30,140), PDO::PARAM_INT);  

    $stmt->execute() or die(print_r($stmt->errorInfo()));
    $tanulok = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
    
    $stmt = $connection->prepare("DELETE FROM valaszok WHERE timestamp = :timestamp");
    $stmt->execute(['timestamp'=>$bulkDate]);
    
 
    foreach($tanulok as $tanulo) {
        $keys = array_rand($kerdesek, rand(5,count($kerdesek)));
        foreach ($keys as $key) {
            switch (rand(1,4)) {
                case 1:
                    $valasz = "";
                    $helyes = 0;
                    break;

                case 2:
                    $valasz = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ -_'),1,rand(5,20));
                    // Ne adjon hibás válaszokat fájl esetén.
                    if($kerdesek[$key]['answer'] == "[file]") $valasz = "";
                    $helyes = 0;
                    break;

                default:
                    //Egyébként ez nem jó ott, ahol | -al elválasztott változatok vannak.
                    $helyesValaszok = explode(';',$kerdesek[$key]['answer']);
                    $valasz = $helyesValaszok[rand(0,count($helyesValaszok)-1)];
                    $helyes = 1;
                    //Kép esetén nehezebb helyes választ generálni
                    if($valasz == '[file]') {
                        $valasz =uploadImage(['tmp_name'=>$imageFolder.'/empty.jpg','name'=>'ures']);
                        $newValasz = str_ireplace($imageFolder, $imageFolder."/bulk",$valasz);
                        rename($valasz, $newValasz);
                        $valasz = $newValasz;
                        $helyes = rand(0,2);
                    }
                    
                    break;
            }
            
         
            $stmt = $connection->prepare("INSERT INTO valaszok (tanaz, tanosztaly, kerdesid, valasz, helyes, timestamp)"
                . "VALUES (:tanaz, :tanosztaly, :kerdesid, :valasz, :helyes, :timestamp)");
            $stmt->execute([
                'tanaz' => $tanulo['tanaz'], 
                'tanosztaly'=>$tanulo['tanosztaly'], 
                'kerdesid' => $key, 
                'valasz' => $valasz, 
                'helyes' => $helyes,
                'timestamp' => $bulkDate
                ]);            
        }        
        
    }
    
    return true;
}

function getEredmenyek(string $osztaly) {
    global $connection, $development, $bulkDate;
    
    $sql = "select kerdesid, 
	count(if(helyes = 1, 1, null)) as helyes, 
        count(if(helyes = 0 AND valasz='', 1, null)) as ures, 
        count(if(helyes = 0 AND valasz<>'', 1, null)) as hibas 
		from valaszok where tanosztaly = :osztaly ";
    if(!$development) $sql .= "AND timestamp <> '$bulkDate'";
    $sql .= " group by kerdesid  ";
    $stmt = $connection->prepare($sql);
    $stmt->execute(['osztaly'=>$osztaly]);
    $eredmenyek = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
    return $eredmenyek;
}

function addOsztalyValaszok($kerdesek, $osztaly) {    
    
    $eredmenyek = getEredmenyek($osztaly);
    foreach($kerdesek as $key => $kerdes) {
        if(array_key_exists($key,$eredmenyek)) {
            $kerdesek[$key]['tarsak'] = $eredmenyek[$key];
        }               
    }
    return $kerdesek;
}

function getScores() {
    global $connection, $development, $bulkDate, $config;
    
    /* Ranglista összeállítása */
    $sql = "
            select tanosztaly, 
                count(distinct tanaz) as jatekos,
                count(if(helyes = 1, 1, null)) * ".$config['scoring']['goodAnswer']." +  ( count(if(helyes = 0, 1, null))* ".$config['scoring']['badAnswer']." ) as pont 
                        from valaszok          
        ";            
    if(!$development)    $sql .= "WHERE timestamp <> '$bulkDate'";
	$sql .= " group by tanosztaly  ";
    $sql .=  " order by pont DESC";    
    //echo $sql;
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $ranglista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $groupSizes = getGroupSizes();    
    
    /* Eltávolítjuk azokat a ranglistából, akik most épp nem férnek hozzá az anyaghoz */
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
            print_r($osztalyletszamok);
            $groupSizeCorrection = max($groupSizes);
        } elseif ($groupSizeCorrection == 'avg') {
            $groupSizeCorrection = (int) ( array_sum($groupSizes)/count($groupSizes) );
        } else {
            throw new Exception("Configuration error: invalid 'scoring/groupSizeCorrection'!");
        }
        
        foreach($ranglista as $key => $osztaly) {       
            if(!array_key_exists($osztaly['tanosztaly'], $groupSizes)) $groupSizes[$osztaly['tanosztaly']] = $groupSizeCorrection;
            $ranglista[$key]['pont'] = (int) ( ( $groupSizeCorrection / $groupSizes[$osztaly['tanosztaly']] ) * $ranglista[$key]['pont'] );
        }
    }
      
    /* Egy kis igazítás azzal, hogy hányan csináltak bármit az osztályból */
    foreach($ranglista as $key => $osztaly) {
        $ranglista[$key]['pont'] += ( $ranglista[$key]['jatekos'] * $config['scoring']['forEachParticipants'] );
    }
    
    /* Ki szedjük a DEV csoportot */
    global $development;
    if(!$development) {
        foreach($ranglista as $key => $value) {
            if($value['tanosztaly'] == 'DEV') {
                unset($ranglista[$key]);
            }
        }
    }
    
    /* Sorbarendezés */
    usort($ranglista, function ($item1, $item2) {
        return $item2['pont'] <=> $item1['pont'];
    });
    
    $return = [];
    foreach($ranglista as $key => $value) {
        $value['rang'] = $key + 1;
        $return[$value['tanosztaly']] = $value;
    }   
    return $return; 
   
}

function osszehasonlit($valasz, $helyes) {
    
    if( strcasecmp($valasz,trim($helyes)) == 0 ) return true;
    $helyesek = explode(';',$helyes);
    if(count($helyesek) > 1 ) {
        foreach($helyesek as $helyes) {
            if( strcasecmp($valasz,trim($helyes)) == 0 ) return true;
        }
    }
               
    return false;
}



    
define("TMPFOLDER", 'tmp/');     
function getGoogleSheetCSV($fileId, $filename, $cache = false) {	
        if($cache == false) $cache = '10 minutes';
        $tmpfilename = $filename;
	if (file_exists($tmpfilename) AND filemtime($tmpfilename) > strtotime("-".$cache) AND (!isset($_REQUEST['update']) OR $_REQUEST['update'] == false)) {

	} else {
		//echo "kell nekünk";
		//Download the file.
		$content = file_get_contents('https://docs.google.com/spreadsheets/d/'.$fileId.'/export?format=csv');
		file_put_contents($tmpfilename, $content);
		//echo "$tmpfilename has been downloaded ";
	}
	$return = file_get_contents($tmpfilename);	
        
	return array(
		'fileId' => $fileId,
		'content' => $return,
		'filemtime' => filemtime($tmpfilename)
	);
}

function insertValasz($valasz, $user) {
    global $connection;
    $stmt = $connection->prepare("INSERT INTO valaszok (tanaz, tanosztaly, kerdesid, valasz, helyes)"
                        . "VALUES (:tanaz, :tanosztaly, :kerdesid, :valasz, :helyes)");
    return $stmt->execute([
        'tanaz' => $user['tanaz'], 
        'tanosztaly'=>$user['tanosztaly'], 
        'kerdesid' => $valasz['id'], 
        'valasz' => $valasz['valasz'], 
        'helyes' => $valasz['helyes']
            ]);    
}

function updateValasz($valasz, $user) {
    global $connection;    
    $stmt = $connection->prepare("UPDATE valaszok SET "
                . "valasz = :valasz, helyes = :helyes, timestamp = CURRENT_TIMESTAMP() "
                . " WHERE tanaz = :tanaz AND kerdesid = :kerdesid ");
    return $stmt->execute([
        'tanaz' => $user['tanaz'], 
        'kerdesid' => $valasz['id'], 
        'valasz' => $valasz['valasz'], 
        'helyes' => $valasz['helyes']
            ]);
}

function randomNumber($darab, $max) {
    $numbers = []; for($i=1;$i<=$max;$i++) $numbers[] = $i;
    shuffle($numbers);
    $numbers = array_slice($numbers, 0, $darab);
    sort($numbers);
    
    return $numbers;    
}


function createImagePuzzle($path,$percentage,$felosztas = [6,12]) {
    $visible = $felosztas[0]*$felosztas[1] * ( $percentage / 100 );
    
    if(!file_exists($path)) {
        die('Hiányzik a képfájl: '.$path."!");        
        return false;
    }
    
    $image_data = @getimagesize($path);    
    if( $image_data === false ) {
        die('Ez nem is képfájl: '.$path."!");        
        return false;
    }                  
    $image = imagecreatefromstring( file_get_contents( $path));

    $color = imagecolorallocate ($image, 211,211,211);

    $whites = randomNumber($felosztas[0]*$felosztas[1] - $visible,$felosztas[0]*$felosztas[1]);
    
    //Egy-egy négyzet mérete
    $block = [ $image_data[0] / $felosztas[0], $image_data[1] / $felosztas[1]  ];
    
    foreach($whites as $id ) {
        $x1 = ( ( $id-1 ) % $felosztas[0] ) * $block[0];
        $y1 = floor( ( $id - 1 ) / $felosztas[0]  ) * $block[1];
        $x2 = $x1 + $block[0];
        $y2 = $y1 + $block[1];
        imagefilledrectangle($image, $x1,$y1,$x2,$y2, $color);
    }

    global $imageFolder;
    $newPath = $imageFolder.'/puzzle_'.md5($path.'_'.$percentage).'.jpg';
    imagejpeg ($image,$newPath);
    return $newPath;
}