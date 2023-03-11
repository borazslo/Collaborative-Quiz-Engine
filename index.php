<?php
//echo 'RM Majális - "Újratervezés" - 2021. május 15. '; exit ;
$start = microtime(true);
require_once 'vendor/autoload.php';
require_once 'functions.php';

$loader = new \Twig\Loader\FilesystemLoader(['templates']);

if(isset($config['addons'])) {
	foreach($config['addons'] as $addon) {
		$loader->prependPath("addons/".strtolower($addon));	
	}
}
$twig = new \Twig\Environment($loader);

//$twig->getExtension(\Twig\Extension\CoreExtension::class)->setDateFormat('d/m/Y', '%d days');

$filter = new \Twig\TwigFilter('timeago', 'twigFilter_timeago');
$twig->addFilter($filter);
  
$filter = new \Twig\TwigFilter('t', 'twigFilter_t');
$twig->addFilter($filter);

$page = new stdClass();
$page->data = [];

if(isset($_SERVER['BASE'])){
  $page->data['base_url'] = preg_replace("/(\/){2,10}$/","/",$_SERVER['BASE']);  
}
if($development == true) $page->data['development'] = true;
$page->data['config']['debug'] = $config['debug'];


$quizId = getParam($_REQUEST, 'q', isset($config['defaultQuizId']) ? $config['defaultQuizId'] : '../empty'); // explode('/',str_replace($_SERVER['SERVER_NAME'], '', $_SERVER['REQUEST_URI']))[0];

require_once 'common/user.php';

require_once('common/login.php');

$quiz = new Quiz($quizId);
$page->data['quiz'] = json_decode(json_encode($quiz), true);

CheckLogin();
$page->data['user'] = (array) $user;



if(isset($user->isAdmin) and $user->isAdmin == 1 ) {
     $page->data['config']['debug'] = $config['debug'] = true;
     $page->data['menu'] = [
        'játék' => $page->data['base_url'],
        'statisztika' => $page->data['base_url'].'?admin=stats',
        'ellenőrzés' => $page->data['base_url'].'?admin=verification',
        'képek' => $page->data['base_url'].'?admin=photos'
        ];
}

// There is no user. 
if(empty((array) $user)) {
    $page->data['error'] = true;
    $page->templateFile = 'koszonto';
    echo $twig->render($page->templateFile.".twig", $page->data);
    exit;   
    
// Admin pages
} elseif ( $admin = getParam($_REQUEST,'admin',false)  ) {
       
    include_once('common/admin.php');
    
    if (isset($user->isAdmin) and $user->isAdmin == 1 ) {
    
    switch ($admin) {
        
        case 'stats':
            Admin::stats();             
            break;
        
        case 'photos':
            Admin::photos();             
            break;

        case 'verification':
            Admin::verification();             
            break;

        case 'verify':
            Admin::verify();             
            exit;
            break;        
        
        default:
            die('A kért oldal nem található.');
            break;
    }
    
    } elseif( $admin = 'photos') {
        Admin::photos();             
    }
        
// Questionaire
} else {    
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
}

//foreach($page->data['quiz']['questions'] as $question) {
    //echo $question['id']." __". str_pad($question['type'], 10, '_') ." ". strip_tags($question['question'])."<br>";
//}
//exit;

echo $twig->render($page->templateFile.".twig", $page->data);
if($development)  echo "Lefutott: ".(microtime(true) - $start);