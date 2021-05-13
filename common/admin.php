<?php

class Admin {
    //put your code here
    
    static function stats() {
        global $page, $quiz;
        
        $page->templateFile = 'stats';
        $page->data['rankingTable'] = getRankingTable($quiz->id);
        
        $players = 0;
        foreach($page->data['rankingTable'] as $position) {
            $players += $position['members'];
        }
       $page->data['players'] = $players;
        
    }
    
    static function verification() {
        global $page, $quiz, $connection;
        
        $page->templateFile = 'verification';
        
        foreach($quiz->questions as $key => $question) {
            if(!in_array($question->type, ['photo','manual'])) {
                unset($quiz->questions[$key]);
                continue;
            } 
                        
            $stmt = $connection->prepare("SELECT 
                    CONCAT(:quiz_id, '-', :question_id, '-', users.id) as id, 
                    users.name as `user`, 
                    groups.name as `group`, 
                    answers.answer, answers.result, answers.timestamp                    
                FROM answers 
                LEFT JOIN users ON users.id = answers.user_id 
                LEFT JOIN groups ON users.group_id = groups.id 
                WHERE 
                    quiz_id = :quiz_id AND
                    question_id = :question_id AND
                    result = '1' 
                ORDER BY RAND(); 
                ");
                                                                               
            if(!$stmt->execute(['quiz_id'=>$quiz->id, ':question_id' => $key])) printr($connection->errorInfo());
            $quiz->questions[$key]->answersToCheck = $stmt->fetchAll(PDO::FETCH_ASSOC);
            //printr($quiz->questions[$key]->answersToCheck );exit;
        }
        
        
        $page->data['quiz'] = json_decode(json_encode($quiz), true);
        
    }
    
    static function verify() {
        global $connection;
                
        if(!$action = getParam($_REQUEST,'action',false) OR !in_array($action,['ok','no','update'])) die('There is no such action');
        
        if($action == 'ok' OR $action == 'no' ) {
            if(! $id = getParam($_REQUEST, 'id') ) die('There is no id');
            $id = explode('-',$id);
            if(count($id) != 3) die('Invalid id');
            
            if($action == 'ok') $result = 2;
            elseif($action == 'no') $result = -1;
            
            $stmt = $connection->prepare("UPDATE answers SET result = :result, timestamp = :timestamp WHERE quiz_id = :quiz_id AND question_id = :question_id AND user_id = :user_id ");                
            if(! $stmt->execute([':result'=>$result,':timestamp' => date('Y-m-d H:i:s'),':quiz_id'=>$id[0], ':question_id' => $id[1], ':user_id' => $id[2]]) ) printr($connection->errorInfo());
            echo "#cardid".$_REQUEST['id'];
            exit;
        } elseif ($action == 'update') {
            if(! $ids = getParam($_REQUEST, 'ids') ) die('There is no id list.');
            if(preg_match('/(;|\'| )/i',$ids)) die('Invalid id list.'); // TODO: Elég ennyi egy mysql injection ellen?
            
            $sql =  "SELECT "
                    . " CONCAT( quiz_id, '-', question_id, '-', user_id ) as id  "
                    . "FROM answers "
                    . "WHERE  CONCAT( quiz_id, '-', question_id, '-', user_id )"
                    . " IN ('".implode("','",explode(',',$ids))."') AND result IN ('-1','2')";

            $stmt = $connection->prepare($sql);                        
            if(! $stmt->execute() ) printr($connection->errorInfo());
            $toDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if($toDelete)
                echo "#cardid".implode(', #cardid',$toDelete);
            
        }
        
                        
    }
    
}
