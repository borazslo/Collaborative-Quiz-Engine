<?php
require_once(realpath( dirname(__FILE__) . "/../config.php"));
require_once("loginHelper.php");

session_start();//to be able to make difference between users


$action = getParam( $_REQUEST, "task");
$loginHelper = new LoginHelper();

$next_page = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
$next_page = getParam( $_REQUEST, "next_page", $next_page);

$quizId = getParam($_REQUEST, 'q', 'majalis'); // explode('/',str_replace($_SERVER['SERVER_NAME'], '', $_SERVER['REQUEST_URI']))[0];
$quiz = new Quiz($quizId.'.json');
$page->data['quiz'] = json_decode(json_encode($quiz), true);
unset($page->data['quiz']['description_html']);

if ($action == "login"){
	$loginHelper->login($_REQUEST);
	if (!$loginHelper->authenticated_user()){
		$loginHelper->loginForm('', $next_page, t('WrongPassword'), $_REQUEST);
	}else{
		// success
		$user = new User($_SESSION['user']);
	}

}else if ($action == "modifyPassword"){
	//jelszo modositasa (reset)
	$loginHelper->login_reset_newPassword(getParam( $_REQUEST, "token"), getParam( $_REQUEST, "password"));
}else if ($action == "reset"){	
	if ($loginHelper->login_reset(getParam( $_REQUEST, "token")))
		exit();
}else if ($action == "rm"){
	header('Content-Type: application/json');
	echo $loginHelper->getRMgroupsJSON(getParam( $_REQUEST, "term"));
	exit;
}else if ($action == "reg"){
	$loginHelper->registrationForm();
}else if ($action == "registration"){
	$loginHelper->add($_REQUEST);
}else if ($action == "confirm"){
	$loginHelper->confirmReg($_REQUEST);
}else if ($action == "lostPassword"){
	$loginHelper->lostPasswordForm();
}else if ($action == "sendPassword"){
	$loginHelper->sendPassword($_REQUEST);
}else if ($action == "logout"){
	$loginHelper->logout();
	header("Location: index3.php");
}


function CheckLogin($level = 'normal'){
	global $loginHelper, $next_page, $relative_path, $config;
	
	if (!$config['loginHelper']) return;
	if ($loginHelper->authenticated_user()){
		if (!$loginHelper->get_access_level($level)){
			printr(t('AccessDenied') . " (". $_SESSION['login'] . ")");
			exit();
		}
		$next_page = getParam( $_REQUEST, "next_page");
		if (!empty($next_page)) header('Location: ' . $next_page);
	}else{

		$loginHelper->loginForm('', $next_page);
		exit();
	}
}
