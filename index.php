<?php

require_once 'vendor/autoload.php';
require_once 'functions.php';

 
$loader = new \Twig\Loader\FilesystemLoader(['templates']);
$twig = new \Twig\Environment($loader);

$twig->addExtension(new Twig_Extensions_Extension_Date());

$filter = new \Twig\TwigFilter('t', 't');
$twig->addFilter($filter);

$page = new stdClass();
$page->data = [];
if($development == true) $page->data['development'] = true;
$page->data['config']['debug'] = $config['debug'];

$page->data['game'] = $config['game'];
/*
if(!isset($_REQUEST['tanaz']) OR !isset($_REQUEST['tanazonosito'])) {
    $page->templateFile = 'koszonto';
    echo $twig->render($page->templateFile.".twig", $page->data);
    exit;
} 

$user = getUser($_REQUEST['tanaz'],$_REQUEST['tanazonosito']);
*/

$user = new User();        

if(!$user) {
    $page->data['tanaz'] = $_REQUEST['tanaz'];
    $page->data['tanazonosito'] = $_REQUEST['tanazonosito'];
    $page->data['error'] = true;
    $page->templateFile = 'koszonto';
    echo $twig->render($page->templateFile.".twig", $page->data);
    exit;   
}
$page->data['user'] = $user;

if($user->isAdmin) {
    $page->data['config']['debug'] = $config['debug'] = true;
}

$page->templateFile = 'kerdesek';

/* Fókusz oda, ahova nyomkodott */
//TODO: csakko mükszik, ha gombra kattint. Egyébként miért nem?
if(isset($_REQUEST['gomb']) AND is_numeric($_REQUEST['gomb'])) {
    $page->data['focusId'] = 'card'.$_REQUEST['gomb'];
}
include('common/Quiz.php');
$quiz = new Quiz('szentignac.json');


/* 
//$kerdesek = addOsztalyValaszok($kerdesek, $user['tanosztaly']);
//ksort($kerdesek);

$regivalaszok = getValaszok($user['tanaz']);

$request = filter_input_array(INPUT_POST | INPUT_GET);
$request = $_REQUEST;

/* 
 * Kérdések kitakarítása láthatóság alapján 
 *
$kerdesek = currentQuestions($kerdesek, $config['game']);


/*
 * Mivel a megjelenő kérdéseket pörgetjük végig, így, 
 * ha valaki régen beöltötte az oldat, de későn kattint
 * akkor a már nem élő kérdésekre nem tud válaszolni.
 *
foreach($kerdesek as $key => $kerdes) {
    // FILE feltöltős kérdések tökre mások
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
        // Ha volt beküldés akár csak üresen 
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
                     
        // Válasz ellenőrzése 
        //echo "<pre>"; print_R($kerdesek[$key]);
        if( $kerdesek[$key]['valasz'] == '') {
            $kerdesek[$key]['eredmeny'] = 0; 
        
        // Manuálisan ellenőrizendő szöveges kérdések 
        } else if ( $kerdesek[$key]['answer'] == '[manual]' ) {
            
            //Új cucc ellenőrzésre
            if( $kerdesek[$key]['valasz'] != $regivalaszok[$key]['valasz'] ) {                
                $kerdesek[$key]['messages'][] = ['warning','Még le kell ellenőriznünk, de addig is megelőlegeztük a pontokat.'];
                $kerdesek[$key]['eredmeny'] = 1;                         
            // Semmi új nem érkezett.                
            } else {                
                if($regivalaszok[$key]['helyes'] == 2 ){
                    //$kerdesek[$key]['messages'][] = ['warning','Ellenőriztük és elfogatuk.'];
                    $kerdesek[$key]['eredmeny'] = 2;
                } else if($regivalaszok[$key]['helyes'] == 1 ){
                    $kerdesek[$key]['messages'][] = ['warning','Még le kell ellenőriznünk, de addig is megelőlegeztük a pontokat.'];
                    $kerdesek[$key]['eredmeny'] = 1;
                }  else {
                    $kerdesek[$key]['messages'][] = ['danger','Megnéztük és sajnos nem tudtuk elfogadni a választ. Küldj be egy másikat!'];
                    $kerdesek[$key]['eredmeny'] = -1;
                }                                
            }
            
        } else if(osszehasonlit ($kerdesek[$key]['valasz'],$kerdesek[$key]['answer']) )  {
            $kerdesek[$key]['eredmeny'] = 1; 
        } else {
            $kerdesek[$key]['eredmeny'] = -1; 
        }         
        
        if($kerdesek[$key]['eredmeny'] > 0 ) $helyes = $kerdesek[$key]['eredmeny'];
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
        
    } 
}
/* */
$page->data['kerdesek'] = json_decode(json_encode($quiz->questions), true);

//$page->data['user']['tanosztaly'] = '11B';
/*
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
*/


echo $twig->render($page->templateFile.".twig", $page->data);