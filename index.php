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

if(isset($_SERVER['BASE'])){
  $page->data['base_url'] = $_SERVER['BASE'];
}
if($development == true) $page->data['development'] = true;
$page->data['config']['debug'] = $config['debug'];

/*
if(!isset($_REQUEST['tanaz']) OR !isset($_REQUEST['tanazonosito'])) {
    $page->templateFile = 'koszonto';
    echo $twig->render($page->templateFile.".twig", $page->data);
    exit;
} 

$user = getUser($_REQUEST['tanaz'],$_REQUEST['tanazonosito']);
*/

require_once 'common/user.php';
require_once('common/login.php');

$user = new User($_SESSION['user']);


CheckLogin();

//var_dump($user);
echo '<a href="index3.php?task=logout">'. t('Logout') . '</a>';

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

$quizId = getParam($_REQUEST, 'q', 'betlehem'); // explode('/',str_replace($_SERVER['SERVER_NAME'], '', $_SERVER['REQUEST_URI']))[0];
$quiz = new Quiz($quizId.'.json');



//$kerdesek = currentQuestions($kerdesek, $config['game']);

/*
    
    else if(isset($request['kerdes'][$key]) OR isset($regivalaszok[$key])) {

        
                     
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
       
 
/* */


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

$page->data['quiz'] = json_decode(json_encode($quiz), true);

echo $twig->render($page->templateFile.".twig", $page->data);