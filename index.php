<?php
session_start();//to be able to make difference between users
//print_r($_REQUEST); print_r($_SESSION);


$start = microtime(true);
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
  $page->data['base_url'] = preg_replace("/(\/){2,10}$/","/",$_SERVER['BASE']);  
  $page->data['base_url'] = trim($page->data['base_url'],'/');
}
if($development == true) $page->data['development'] = true;
$page->data['config']['debug'] = $config['debug'];


$quizId = getParam($_REQUEST, 'q', isset($config['defaultQuizId']) ? $config['defaultQuizId'] : '../empty'); // explode('/',str_replace($_SERVER['SERVER_NAME'], '', $_SERVER['REQUEST_URI']))[0];

require_once 'common/user.php';

$quiz = new Quiz($quizId);
$page->data['quiz'] = json_decode(json_encode($quiz), true);

$trans = loadTranslation('hu_HU'); //a new Quiz() után kell, mert az addonok fordítására is szükség van

if(isset($config['addons'])) {
	foreach($config['addons'] as $addon) {
		$twig->getLoader()->prependPath("addons/".strtolower($addon));	
	}
}

require_once('common/login.php');
$page->data['user'] = (array) $user;

$quiz->prepareQuestions();
$page->data['quiz'] = json_decode(json_encode($quiz), true);

//$bulk = new Bulk($quiz); 
//$bulk->addAnswers();
//$bulk->addAll();
//$bulk->addGroupOfGroups();
//$bulk->deleteGroupOfGroups();


if(isset($user->admin) and $user->admin == 1 ) {
     //$page->data['config']['debug'] = $config['debug'] = true;
	 $page->data['base_url'] = '';
	 
     $page->data['menu'] = [
        'játék' => $page->data['base_url'],
        'statisztika' => $page->data['base_url'].'?admin=stats',
        'ellenőrzés' => $page->data['base_url'].'?admin=verification',
        'válaszok' => $page->data['base_url'].'?task=Admin_answers'
        ];		
}
    
// Admin pages
if ( $admin = getParam($_REQUEST,'admin',false)  ) {
       
    include_once('common/admin.php');
    CheckLogin();
    if (isset($user->admin) and $user->admin == 1 ) {
    
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
	
	//Special pages, because why not.
	$task = getParam( $_REQUEST, "task");	
	if($task != '') {

		$task = explode('_',$task);
		if(count($task) > 2) die('A kért oldal nem található. (E132)');
		if(count($task) == 2) {
			if(!method_exists ($task[0], "public_".$task[1])) die('A kért oldal nem található. (E134)');		
			$result = $task[0]::{"public_".$task[1]}($page);
		}
	} elseif(empty((array) $user)) {
		CheckLogin();
		$page->data['error'] = true;
		$page->templateFile = 'koszonto';
		echo $twig->render($page->templateFile.".twig", $page->data);
		exit;   
	}
	
	
	
	
	//OR let's see the questions	
	if(!isset($page->templateFile))
		$page->templateFile = 'kerdesek';

    /* Fókusz oda, ahova nyomkodott */
    //TODO: csakko mükszik, ha gombra kattint. Egyébként miért nem?
    if(isset($_REQUEST['gomb']) AND is_numeric($_REQUEST['gomb'])) {
        $page->data['focusId'] = 'card'.$_REQUEST['gomb'];
    }

    if(!empty((array) $user) AND 
		( 
		( isset($config['rankingTablePublic']) AND $config['rankingTablePublic'] == true ) or $user->admin == 5)
		) {
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