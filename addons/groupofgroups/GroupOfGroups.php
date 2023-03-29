<?php

class GroupOfGroups {
            

	/*
		Adter loginhelper->login() has found the rught user
	*/
    static function User___construct_after(&$user) {
		return;
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
		return;
		global $user;
		
		if($quiz->timing->start > time() ) return;
				
		if($user->groupstart == '') {
			$quiz->timing->start = strtotime("+10 years"); // Elvileg megy enélkül is, hiszen tök más oldalt hoz be. De biztosra megyünk.
		}
						
		$quiz->timing->start = $user->groupstart;
		
	}
	static function Quiz_deleteInactiveQuestions_after(&$quiz) {
		return;
		global $user;
		
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
		return;
		global $connection, $user, $quiz;
		
		if($user->groupstart == '') {
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
	
	static function public_enter(&$page) {
		global $config;
	
		$code = getParam($_REQUEST,'code',false);
		if(!$code) { $page->templateFile = 'groupownerlogin'; return; }
		
		global $connection;
		$stmt = $connection->prepare("SELECT * FROM groupofgroups WHERE code = :code LIMIT 1");
		$stmt->execute([':code'=>$code]);
		$page->data['supergroup'] = $stmt->fetch();
		if(!$page->data['supergroup']) { $page->data['error'] = "Hibás kód"; $page->templateFile = 'groupownerlogin'; return; }
		
		$stmt = $connection->prepare(" SELECT 
			users.name as user, users.id as user_id, 
			`groups`.name as `group`,
			groupofgroups.name as groupofgroups,
			answers.answers,
			groups.id as group_id, users.email, users.active, groups.level 
			
		 FROM users 
			LEFT JOIN `groups` ON users.group_id = groups.id 
			LEFT JOIN lookup_groupofgroups ON users.group_id = lookup_groupofgroups.group_id
			LEFT JOIN groupofgroups ON lookup_groupofgroups.groupofgroups_id = groupofgroups.id
			LEFT JOIN ( SELECT user_id, count(*) as answers FROM answers WHERE quiz_id = :quiz_id GROUP BY user_id ) as answers ON answers.user_id = users.id
		WHERE groupofgroups.code = :code
		ORDER BY `group`, user ");		
		$stmt->execute([':code'=>$code,':quiz_id'=>$page->data['quiz']['id']]);
		$results = $stmt->fetchAll();
		
		$levels = ['','5-8. évfolyam','9-12. évfolyam','felnőtt'];
		$page->data['groups'] = [];
		foreach($results as $result) {
			$result['level'] = $levels[$result['level']];
			//Generate simple password with pseudo random.
			$result['pwd'] = readable_random_string(6,$result['user']."-".$result['group_id']);
			$stmt = $connection->prepare("UPDATE users SET password = :pwd WHERE id=:id");
			$stmt->execute([':pwd'=>crypt($result['pwd'], $config['authentication']['salt']),':id'=>$result['user_id']]);
		
		
			$page->data['groups'][$result['group_id']][] = $result;
		
		}
		
		

		
		
		$page->templateFile = 'groupofgroups';
		
	}
	
	// BULK operation
	static function bulkFunctions() {
		return ["addGroupOfGroups","deleteGroupOfGroups"];
	}
	
	static function addGroupOfGroups($args) {
		$institution = ["Általános Iskola", "Gimnázium", "Szakközépiskola", "Technikum", "Egyetem", "R.K. Plébánia"];
	
	
		$bulk = $args[count($args)-1];
		
		$stmt = $bulk->connection->prepare("SELECT id FROM `groups` WHERE name LIKE '".$bulk->prefix."%' ");       
        $stmt->execute();        
        $groups = $stmt->fetchAll();
						
		$stmt = $bulk->connection->prepare("INSERT IGNORE INTO groupofgroups (name, code) VALUES (:name, :code)");
		
        $count = count($groups) / rand(4,8); //How many group of groups        
		$newids = [];
        for($i = 0; $i < $count; $i++ ) {
            $groupofgroups = array(
                ':name' => $bulk->prefix." ".ucfirst(readable_random_string(rand(6,14))). " " . ucfirst(readable_random_string(rand(6,14))). " " . $institution[rand(0,count($institution)-1)],
                ':code' => readable_random_string(6)
            );
			$stmt->execute($groupofgroups);
			$lastId = $bulk->connection->lastInsertId();
			if($lastId) $newids[] = $lastId;			
        }
		
		// LOOKUP TABLE
		$stmt = $bulk->connection->prepare("INSERT IGNORE INTO lookup_groupofgroups (groupofgroups_id, group_id) VALUES (:groupofgroups_id, :group_id)");
		foreach($newids as $id) {		
			$rand_groups = array_rand($groups, rand(4,10)); //How big random groups
			foreach($rand_groups as $rand_group) {				
				$stmt->execute([":groupofgroups_id" => $id, ":group_id" => $groups[$rand_group][0] ]);
			}					
		}
		
	}
    
	static function deleteGroupOfGroups($args) {   
		$bulk = $args[count($args)-1];
	
        $sql = "DELETE lookup_groupofgroups FROM lookup_groupofgroups "
                . "LEFT JOIN groupofgroups ON groupofgroups.id = lookup_groupofgroups.groupofgroups_id "
                . "WHERE "
                . " groupofgroups.name LIKE '".$bulk->prefix."%' ;";        
        $stmt = $bulk->connection->prepare($sql);
        $stmt->execute();
      
        $stmt = $bulk->connection->prepare("DELETE FROM groupofgroups WHERE name LIKE '".$bulk->prefix."%';");
        $stmt->execute();
        
    }
       
       static function Ranking_getTable_after(&$ranking) {
       		global $connection; 

       		$group_ids = [];
       		foreach($ranking->results as $result) {
       			$group_ids[] = $result['group_id'];
       		}

       		$sql = "SELECT * FROM `lookup_groupofgroups` 
       			LEFT JOIN `groupofgroups` ON groupofgroups.id = lookup_groupofgroups.groupofgroups_id 
       			WHERE group_id IN (".implode(",",$group_ids)."); ";       			

       		$stmt = $connection->prepare($sql);
        	$stmt->execute();
        	$results = $stmt->fetchAll();

        	$tmp = [];
        	foreach($results as $result) {
        		$tmp[$result['group_id']] = $result;

        	}
        	$results = $tmp;
        	
        	foreach($ranking->results as &$result ) {
        		$result['parent'] = $tmp[$result['group_id']]['name'];

        	}
       		
       }
    
   
}
