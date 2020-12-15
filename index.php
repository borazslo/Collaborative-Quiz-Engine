<?php

 //
 //$startTime = strtotime(date('H').':00:00');
$startTime = strtotime('00:00:00');
 $questionFrequency = "1 sec";
 $questionVisibility = "1 day";
 

require_once 'vendor/autoload.php';
require_once 'functions.php';

 
$loader = new \Twig\Loader\FilesystemLoader(['templates']);
$twig = new \Twig\Environment($loader);

$filter = new \Twig\TwigFilter('t', 't');
$twig->addFilter($filter);


$page = new stdClass();
$page->data = [];
if($development == true) $page->data['development'] = true;

$page->data['game'] = $config['game'];

if(!isset($_REQUEST['tanaz']) OR !isset($_REQUEST['tanazonosito'])) {
    $page->templateFile = 'koszonto';
    echo $twig->render($page->templateFile.".twig", $page->data);
    exit;
} 

$user = getUser($_REQUEST['tanaz'],$_REQUEST['tanazonosito']);
if(!$user) {
    $page->data['tanaz'] = $_REQUEST['tanaz'];
    $page->data['tanazonosito'] = $_REQUEST['tanazonosito'];
    $page->data['error'] = true;
    $page->templateFile = 'koszonto';
    echo $twig->render($page->templateFile.".twig", $page->data);
    exit;   
}
$page->data['user'] = $user;

$page->templateFile = 'kerdesek';

/* Fókusz oda, ahova nyomkodott */
//TODO: csakko mükszik, ha gombra kattint. Egyébként miért nem?
if(isset($_REQUEST['gomb']) AND is_numeric($_REQUEST['gomb'])) {
    $page->data['focusId'] = 'card'.$_REQUEST['gomb'];
}

if($development) {
    $page->data['csv'] = getGoogleSheetCSV('ID', 'kerdesek.csv');
    $page->data['csv']['updated_at'] = date('Y-m-d H:i:s',$page->data['csv']['filemtime']);    
}

$kerdesek = loadKerdesek('kerdesek.csv');

$kerdesek = addOsztalyValaszok($kerdesek, $user['tanosztaly']);

ksort($kerdesek);
$regivalaszok = getValaszok($user['tanaz']);

$request = filter_input_array(INPUT_POST | INPUT_GET);
$request = $_REQUEST;

/* 
 * Kérdések kitakarítása láthatóság alapján 
 */
$frequency = strtotime("+".$questionFrequency) - time();
$lastQuestion = (int) ( ( time() - $startTime ) / $frequency );
$visibleQuestions = (int) ( (time() - strtotime("-".$questionVisibility) ) / $frequency );
$page->data['devMessages'][] = "Kezdő időpont: ".date('Y-m-d H:i:s',$startTime)." (Most: ".date('Y-m-d H:i:s').")<br/>"
        . " Új kérdések sűrűsége: ".$questionFrequency.". Egy-egy kérdés láthatóság: ".$questionVisibility.". <br/>"
        . "Összes kérdés: ".count($kerdesek).". Ebből megjelenítve: ".($lastQuestion - $visibleQuestions).". -> ".$lastQuestion.".";
$i = 1;
foreach($kerdesek as $key => $kerdes) {
    if($i < $lastQuestion - $visibleQuestions OR $i > $lastQuestion ) {
        unset($kerdesek[$key]);        
    }        
    $i++;
}


/*
 * Mivel a megjelenő kérdéseket pörgetjük végig, így, 
 * ha valaki régen beöltötte az oldat, de későn kattint
 * akkor a már nem élő kérdésekre nem tud válaszolni.
 */
foreach($kerdesek as $key => $kerdes) {
    /* FILE feltöltős kérdések tökre mások */
    if($kerdes['answer'] == '[file]') {
         // TOGO ha még semmi nem volt elköldve        
         
        // TODO ha már van korábbról kép megérkezve.
        if(isset($regivalaszok[$key])) {
            $kerdesek[$key]['valasz'] = $regivalaszok[$key]['valasz'];
            $kerdesek[$key]['helyes'] = $regivalaszok[$key]['helyes'];
            if($regivalaszok[$key]['helyes'] == 1) {               
                $kerdesek[$key]['messages'][] = ['warning','Még le kell ellenőriznünk képet, de addig is megelőlegeztük a pontokat.'];
            }
            $kerdesek[$key]['eredmeny'] = ( $regivalaszok[$key]['helyes'] >= 1 ? 1 : -1);
            if($kerdesek[$key]['eredmeny'] == -1) {
                $kerdesek[$key]['messages'][] = ['danger','Megnéztük és sajnos nem tudtuk elfogadni ezt a képet. Készíts másikat!'];
            }                    
        }        
        /* Ha volt beküldés akár csak üresen */
        if(isset($_FILES['kerdes_'.$key])) {
            if($_FILES['kerdes_'.$key]['error'] == 4) {
                // Nincs kép feltöltve. Valószínűleg egyszerűen azért, mert nem nyomott még rá.
            } elseif ($_FILES['kerdes_'.$key]['error'] == 1) {
                $kerdesek[$key]['messages'][] = ['danger',"Átlépted a megengedett legnagyobb méretet ami ".ini_get('upload_max_filesize')."."];
            } elseif ( $_FILES['kerdes_'.$key]['error'] > 0 ) {
                die("uuuupsz. mi történt? Annzi fontos, hogy: ".$_FILES['kerdes_'.$key]['error'].", de mit jelenthet ez?");
                
            } else { 
                // Akkor dolgozzuk fel, mert az jó
                $return = uploadImage($_FILES['kerdes_'.$key]);
                if(isset($return['error'])) $kerdesek[$key]['messages'][] = ['danger',$return['error']];
                else {                    
                    $kerdesek[$key]['valasz'] = $return;
                    $kerdesek[$key]['eredmeny'] = 1;
                    if(!isset($kerdesek[$key]['helyes'])) $kerdesek[$key]['helyes'] = 1;                    
                    // TODO, jaj ...
                    unset($kerdesek[$key]['messages']);
					if($kerdesek[$key]['helyes'] < 2 ){
						$kerdesek[$key]['messages'][] = ['warning','Még le kell ellenőriznünk képet, de addig is megelőlegeztük a pontokat.'];
					}
                    if(isset($regivalaszok[$key])) {
                           unlink($regivalaszok[$key]['valasz']);
                           updateValasz($kerdesek[$key],$user);
                    } else {
                            
                           insertValasz($kerdesek[$key], $user);
                    }
                }                
            }                 
        }
        
        
        
    }
    
    
    else if(isset($request['kerdes'][$key]) OR isset($regivalaszok[$key])) {

        if(isset($regivalaszok[$key]) AND ( !isset($request['kerdes'][$key]) OR $request['kerdes'][$key] == ''    ) ) {          
            $kerdesek[$key]['valasz'] = $regivalaszok[$key]['valasz'];
            
        } else {
            $kerdesek[$key]['valasz'] = trim($request['kerdes'][$key]);
        }
                     
        /* Válasz ellenőrzése */
        //echo "<pre>"; print_R($kerdesek[$key]);
        if( $kerdesek[$key]['valasz'] == '') {
            $kerdesek[$key]['eredmeny'] = 0; 
        } else if(osszehasonlit ($kerdesek[$key]['valasz'],$kerdesek[$key]['answer']) )  {
            $kerdesek[$key]['eredmeny'] = 1; 
        } else {
            $kerdesek[$key]['eredmeny'] = -1; 
        }         
        
        if($kerdesek[$key]['eredmeny'] == 1 ) $helyes = 1;
        else $helyes = 0;
        $kerdesek[$key]['helyes'] = $helyes;
        
        // UPDATE ha már érkezett korábban válasz
        if(!isset($regivalaszok[$key]) AND $kerdesek[$key]['valasz'] != '') {                        
            insertValasz($kerdesek[$key], $user);
        }

        if(isset($regivalaszok[$key]) AND $kerdesek[$key]['valasz'] != $regivalaszok[$key]['valasz']  AND $kerdesek[$key]['valasz'] != '') {
            updateValasz($kerdesek[$key],$user);
        }
        if(isset($stmt)) {
            $error = $stmt->errorInfo(); 
            if($error[0] != '0000')
                print_r($error);
        }
 
        /*
        $stmt = $connection->prepare("INSERT INTO valaszok (tanaz, tanosztaly, kerdesid, valasz, helyes)"
                . "VALUES (:tanaz, :tanosztaly, :kerdesid, :valasz, :helyes)"
                . "ON DUPLICATE KEY UPDATE valasz = :valasz, helyes = :helyes, timestamp="
                );
    $stmt->execute(['tanaz' => $tanaz]); 
        INSERT INTO AggregatedData (datenum,Timestamp)
VALUES ("734152.979166667","2010-01-14 23:30:00.000")
ON DUPLICATE KEY UPDATE 
  Timestamp=VALUES(Timestamp)
        ű*/
    } 
}

$page->data['kerdesek'] = $kerdesek;
//$page->data['user']['tanosztaly'] = '11B';

$ranglista = getScores();
//echo "<pre>"; print_r($ranglista); exit;
if(!array_key_exists($user['tanosztaly'],$ranglista)) {
    $ranglista[$user['tanosztaly']] = [
        'rang' => count($ranglista) + 1,
        'pont' => 1,
        'tanosztaly' => $user['tanosztaly']
    ];
}
$page->data['ranglista'] = $ranglista;



echo $twig->render($page->templateFile.".twig", $page->data);