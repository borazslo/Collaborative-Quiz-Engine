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

$page->templateFile = "kepellenorzes";

//Ez igazából nem biztonságos. Mert ha egyszer valaki bejut, akkor kinézheti a forrásból és átveheti.
$page->data['auth'] = $config['admin']['key'];

if(isset($_REQUEST['auth'])) {
    
    if($_REQUEST['auth'] == $page->data['auth']) {

        if(isset($_REQUEST['action'])) {
          if(!isset($_REQUEST['id']) OR !is_numeric($_REQUEST['id'])) {
              exit;
          }            
          if($_REQUEST['action'] == "no") {
              $helyes = 0;
          } elseif($_REQUEST['action'] == "ok") {
              $helyes = 2;
          } 
          $stmt = $connection->prepare("UPDATE valaszok SET helyes = :helyes, timestamp = :timestamp WHERE id = :id");                
          $stmt->execute(['helyes'=>$helyes,'id'=>$_REQUEST['id'],'timestamp' => date('Y-m-d H:i:s')]);
          echo "#cardid".$_REQUEST['id'];
          
        } else if ($_REQUEST['update']) {
                        
            $stmt = $connection->prepare("SELECT id FROM valaszok WHERE id IN (".$_REQUEST['ids'].") AND helyes IN (0,2)");                        
            $stmt->execute();
            $torlendok = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "#cardid".implode(', #cardid',$torlendok);
        }
        
        exit;
    } else {
        exit;
    }
}

if(!isset($_REQUEST['jelszo']) OR $_REQUEST['jelszo'] != $config['admin']['pwd']) {
    echo $twig->render($page->templateFile.".twig", $page->data);
    exit;
}


$page->data['isadmin'] = true;

//Összes diák
#global $connection;
$connectionJezsu = $connection; 
    
$stmt = $connectionJezsu->prepare("SELECT tanaz, tanaz, tannev FROM tanulok");
$stmt->execute();
$page->data['users'] = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);


$kepesKerdes = [];
$kerdesek = loadKerdesek($config['game']['localFile']);
foreach($kerdesek as $key => $kerdes) {
    // Csak azok érdekelnek minket, ahol képeket kellett feltölteni
    if($kerdes['answer'] == '[file]' OR $kerdes['answer'] == '[manual]') {
        
        
        $stmt = $connection->prepare("SELECT id, id, tanaz, tanosztaly, kerdesid, valasz, timestamp FROM valaszok WHERE kerdesid = :kerdesid AND helyes = 1 ORDER BY RAND()");
        $stmt->execute(['kerdesid'=>$key]);
        $kerdes['answersToCheck'] = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
        foreach($kerdes['answersToCheck'] as $k => $answer) {
            $kerdes['answersToCheck'][$k]['timestamp'] = strtotime($answer['timestamp']);
        }
        $kepesKerdes[] = $kerdes;
    }
}

$page->data['kerdesek'] = $kepesKerdes;

$page->data['ranglista'] = getScores();

$page->data['jatekosok'] = 0;
foreach ($page->data['ranglista'] as $rang ) {
	$page->data['jatekosok']	+= $rang['jatekos'];
}

echo $twig->render($page->templateFile.".twig", $page->data);


