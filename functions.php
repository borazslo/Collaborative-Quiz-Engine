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

function printr($anything) {
    echo "<pre>".print_r($anything,1)."</pre>";
}

spl_autoload_register(function ($class_name) {
    $filename = $class_name . '.php';
    if(file_exists($filename)) include $filename;
    elseif(file_exists('common/'.$filename)) include 'common/'.$filename;    
});