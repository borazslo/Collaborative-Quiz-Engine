<?php


class Ranking {



	function __construct($quiz_id) {
			$this->quiz_id = $quiz_id;

	}

	function getTable() {
		global $connection, $development, $config;
		    
		    /* Ranglista összeállítása */
		        
		    $sql = "
		        SELECT 
		            users.group_id, 
		            groups.name , groups.level, 
		            count(distinct user_id) as members,
		       
		            count(if(result = '-1', 1, null)) * ".$config['scoring']['badAnswer']."
		                +  ( count(if(result = '1', 1, null))* ".$config['scoring']['goodAnswer']." ) 
		                    +  ( count(if(result = '2', 1, null))* ".$config['scoring']['goodAnswer']." ) 
		                        as points 
		        
		        FROM `answers`
		            LEFT JOIN users ON users.id = answers.user_id
		            LEFT JOIN `groups` ON groups.id = users.group_id 
		        
		        WHERE quiz_id = :quiz_id 
		            AND users.active = 1 

		        ";      
		    
		    
		    if(!$development)    $sql .= "AND "
		            . "timestamp <> '".Bulk::date()."' AND "
		            . "groups.name NOT LIKE '".Bulk::prefix()."%' AND "
		            . "users.name NOT LIKE '".Bulk::prefix()."%' ";

		    $sql .=  " GROUP BY group_id"
		            . " ORDER BY points DESC";    
		    //echo "<br>".$sql."<br>";
		    $stmt = $connection->prepare($sql);
		    $stmt->execute(array(':quiz_id'=> $this->quiz_id));
		    $ranglista = $stmt->fetchAll(PDO::FETCH_ASSOC);
		    
		    
		    
		    $groupSizes = getGroupSizes();    
		    
		    /* Eltávolítjuk azokat a ranglistából, akik most épp nem férnek hozzá az anyaghoz *
		    foreach($ranglista as $key => $group) {        
		        if(!array_key_exists($group['tanosztaly'], $groupSizes)) {
		            unset($ranglista[$key]);
		        }
		    }
		       
		    /* Osztálylétszámmal korrigált változat */
		    if(isset($config['scoring']['groupSizeCorrection']) AND $config['scoring']['groupSizeCorrection'] != false ) {
		        $groupSizeCorrection = $config['scoring']['groupSizeCorrection'];
		        if(is_numeric($groupSizeCorrection)) {
		            $groupSizeCorrection;
		        } elseif ($groupSizeCorrection == 'min') {
		            $groupSizeCorrection = min($groupSizes);
		        } elseif ($groupSizeCorrection == 'max') {
		            $groupSizeCorrection = max($groupSizes);
		        } elseif ($groupSizeCorrection == 'avg') {
		            $groupSizeCorrection = (int) ( array_sum($groupSizes)/count($groupSizes) );
		        } else {
		            throw new Exception("Configuration error: invalid 'scoring/groupSizeCorrection'!");
		        }
		        
		        foreach($ranglista as $key => $group) {       
		            if(!array_key_exists($group['name'], $groupSizes)) $groupSizes[$group['name']] = $groupSizeCorrection;
		            $ranglista[$key]['points'] = (int) ( ( $groupSizeCorrection / $groupSizes[$group['name']] ) * $ranglista[$key]['points'] );
		        }
		        
		    }
		    /* */
		      
		    /* Egy kis igazítás azzal, hogy hányan csináltak bármit az osztályból */
		    foreach($ranglista as $key => $group) {
		        $ranglista[$key]['points'] += ( $ranglista[$key]['members'] * $config['scoring']['forEachParticipants'] );

		    }
		    
		    /* Ki szedjük a DEV csoportot */
		    global $development;
		    if(!$development) {
		        foreach($ranglista as $key => $value) {
		            if($value['name'] == 'DEV') {
		                unset($ranglista[$key]);
		            }
		        }
		    }
		    
		    /* Sorbarendezés */
		    usort($ranglista, function ($item1, $item2) {
		        return $item2['points'] <=> $item1['points'];
		    });
		    
		    $return = [];
		    foreach($ranglista as $key => $value) {
		        $value['position'] = $key + 1;
		        //array_unshift($value,["position"=>$key + 1]);
		        $return[$value['name']] = $value;
		    }   

			$this->results = $return;

    		hook(__CLASS__,__FUNCTION__,'after',$this);

    		foreach($this->results as &$value) {
		        unset($value['group_id']);		        
		    }   

    		return true;
		    
	}




}