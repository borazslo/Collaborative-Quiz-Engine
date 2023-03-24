<?php

class GroupStart {
            

	/*
		Adter loginhelper->login() has found the rught user
	*/
    static function User___construct_after(&$user) {
        global $connection, $quiz;
        
        if(isset($user->id)) {
			$stmt = $connection->prepare("SELECT groupstart FROM groupstart WHERE group_id = :group_id AND quiz_id = :quiz_id LIMIT 1");
			$stmt->execute([':group_id' => $user->group_id, ':quiz_id' => $quiz->id]);
			$start = $stmt->fetch();
			
			if($start == false) $user->groupstart = '';
			else $user->groupstart = strtotime($start['groupstart']);
        }		
    }

	
	static function Quiz_loadQuestionsStartEnd_before(&$quiz) {		
		global $user;
		
		if($quiz->timing->start > time() ) return;
			
		if($user->groupstart == '') {
			//$quiz->timing->start = strtotime("+10 years"); // Elvileg megy enélkül is, hiszen tök más oldalt hoz be. De biztosra megyünk.
		} else {
			$quiz->timing->start = $user->groupstart;
		}

	}
	static function Quiz_deleteInactiveQuestions_after(&$quiz) {
		global $user;
		
		if ( $quiz->timing->start > time() ) return; 
		// The group has not started the quiz yet
		
		if($user->groupstart == '') {
			foreach($quiz->questions as $key => $value) {
				$quiz->questions[$key]->active = false;
			}
			global $page;
			$page->templateFile = 'groupstart';
			
		
		}
		
	}
	
	static function public_startform(&$page) {
		global $connection, $user, $quiz;
		
		if($user->groupstart == '' AND !empty((array) $user)) {
			$stmt = $connection->prepare("SELECT groupstart FROM groupstart WHERE group_id = :group_id AND quiz_id = :quiz_id LIMIT 1");
			$stmt->execute([':group_id' => $user->group_id, ':quiz_id' => $quiz->id]);
			$start = $stmt->fetch();
			
			$timestamp = date("Y-m-d H:i:s");
			if($start == false) {
				$stmt = $connection->prepare("INSERT INTO groupstart (group_id, quiz_id, groupstart ) VALUE (:group_id, :quiz_id, :groupstart)");
				$stmt->execute([':group_id' => $user->group_id, ':quiz_id' => $quiz->id, ':groupstart' => $timestamp ]);				
			}
			else {
				$stmt = $connection->prepare("UPDATE groupstart SET groupstart = :groupstart WHERE group_id = :group_id AND quiz_id = :quiz_id LIMIT 1");
				$stmt->execute([':group_id' => $user->group_id, ':quiz_id' => $quiz->id, ':groupstart' => $timestamp]);						
			}			
			$user->groupstart = $timestamp;
		}
		
		header("Location: index.php");
		exit;
	}
    
	
    
   
}
