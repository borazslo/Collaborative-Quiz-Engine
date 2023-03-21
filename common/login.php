<?php
require_once(realpath( dirname(__FILE__) . "/../config.php"));
require_once("loginHelper.php");


$action = getParam( $_REQUEST, "task");
$loginHelper = new LoginHelper();

$next_page = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
$next_page = getParam( $_REQUEST, "next_page", $next_page);

$continue = false;
if ($action == "login"){

	$loginHelper->login($_REQUEST);
	
	if (!$loginHelper->authenticated_user()){            
                if($_SESSION['login'] == 'denied') 
                    $loginHelper->loginForm(t('WrongPassword'), $_REQUEST, $next_page);
                elseif($_SESSION['login'] == 'inactive') 
                    $loginHelper->lostPasswordForm(t("InActvie"));
		
	}else{		
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
	if( $config['usermanagement']['allowregistration'] == false ) die('Registration is not allowed');
	$loginHelper->registrationForm();
}else if ($action == "registration"){
	if( $config['usermanagement']['allowregistration'] == false ) die('Registration is not allowed');
	$loginHelper->add($_REQUEST);
}else if ($action == "confirm"){
	$loginHelper->confirmReg($_REQUEST);
}else if ($action == "lostPassword"){
	$loginHelper->lostPasswordForm();
}else if ($action == "sendPassword"){
	$loginHelper->sendPassword($_REQUEST);
}else if ($action == "logout"){
	$loginHelper->logout();
	header("Location: index.php");
        
} else {
	
    $user = new User($_SESSION['user']);
}


if(! (array) $user ) { 
    $loginHelper->loginForm(false, $_REQUEST, $next_page);
    exit;
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
		//if (!empty($next_page)) header('Location: ' . $next_page);
	}else{

		$d = array();
		$loginHelper->loginForm('', $d, $next_page);
		exit();
	}
}
