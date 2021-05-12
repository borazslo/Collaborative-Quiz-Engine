<?php
//echo 'RM Majális - "Újratervezés" - 2021. május 15. '; exit ;
require_once 'vendor/autoload.php';
require_once 'functions.php';

$loader = new \Twig\Loader\FilesystemLoader(['templates']);
$twig = new \Twig\Environment($loader);

//$twig->getExtension(\Twig\Extension\CoreExtension::class)->setDateFormat('d/m/Y', '%d days');

$filter = new \Twig\TwigFilter('timeago', 'twigFilter_timeago');
$twig->addFilter($filter);
  
$filter = new \Twig\TwigFilter('t', 'twigFilter_t');
$twig->addFilter($filter);

$page = new stdClass();
$page->data = [];

if(isset($_SERVER['BASE'])){
  $page->data['base_url'] = $_SERVER['BASE'];
}
if($development == true) $page->data['development'] = true;
$page->data['config']['debug'] = $config['debug'];


$quizId = getParam($_REQUEST, 'q', isset($config['defaultQuizId']) ? $config['defaultQuizId'] : '../empty'); // explode('/',str_replace($_SERVER['SERVER_NAME'], '', $_SERVER['REQUEST_URI']))[0];

require_once 'common/user.php';

require_once('common/login.php');

$quiz = new Quiz($quizId.'.json');
$page->data['quiz'] = json_decode(json_encode($quiz), true);


CheckLogin();

if(empty((array) $user)) {
    $page->data['error'] = true;
    $page->templateFile = 'koszonto';
    echo $twig->render($page->templateFile.".twig", $page->data);
    exit;   
}

$page->data['user'] = (array) $user;

if(isset($user->isAdmin) and $user->isAdmin == 1 ) {
     $page->data['config']['debug'] = $config['debug'] = true;
}

$page->templateFile = 'kerdesek';

/* Fókusz oda, ahova nyomkodott */
//TODO: csakko mükszik, ha gombra kattint. Egyébként miért nem?
if(isset($_REQUEST['gomb']) AND is_numeric($_REQUEST['gomb'])) {
    $page->data['focusId'] = 'card'.$_REQUEST['gomb'];
}

if(!empty((array) $user) AND ( isset($config['rankingTablePublic']) AND $config['rankingTablePublic'] == true )) {
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
}
echo $twig->render($page->templateFile.".twig", $page->data);