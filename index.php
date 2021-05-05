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


$quizId = getParam($_REQUEST, 'q', 'majalis'); // explode('/',str_replace($_SERVER['SERVER_NAME'], '', $_SERVER['REQUEST_URI']))[0];
$quiz = new Quiz($quizId.'.json');
$page->data['quiz'] = json_decode(json_encode($quiz), true);

CheckLogin();

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


$rankingTable = getRankingTable($quiz->id);
    if(!array_key_exists($user->group,$rankingTable)) {
    $rankingTable[$user->group] = [
        'position' => count($rankingTable) + 1,
        'points' => 1,
        'name' => $user->group,
        'members' => 1
    ];
}
$page->data['rankingTable'] = $rankingTable;



echo $twig->render($page->templateFile.".twig", $page->data);