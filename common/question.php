<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of question
 *
 * @author webdev
 */
class Question {
    
    public $inputType = 'text';
    
    function __construct($settings) {
        
        
        foreach($settings as $key => $val) {
            $this->$key = $val;
        }
        
                                        
        $this->prepareQuestion();
        $this->prepareInput();
        
        $this->prepareHint();
        if(isset($this->video))
            $this->prepareVideo();

        global $user;
        if(isset($user->id)) { 
            
            $this->params = [
                'quiz_id' => $this->quiz_id,
                'user_id' => $user->id,
                'question_id' => $this->id
            ];

            $this->loadOtherAnswers($user->group);
            $this->loadUserAnswer();
        }
        
    }
    
    function prepareHint() {
        if(!isset($this->hint)) return;
        
        if(!is_array($this->hint)) {   
            $this->hint = $this->hintUrlToHtml($this->hint);
            return;
        }
        
        //Ha többszintű tipp van, akkor csak azt mutatjuk meg neki, ami neki kell.
        global $user;        
        if(isset($user->level) and isset($this->hint[($user->level - 1) ])) {
            $this->hint = $this->hintUrlToHtml($this->hint[($user->level - 1) ]);
        } else {
            unset($this->hint);
        }
    }
    
    function hintUrlToHtml($url) {
        
        if(preg_match('/\/maps\//i', $url)) {            
            return 'Talán errefelé érdemes körülnézni: <a class="text-decoration-none" target="_blank" href="'.$url.'">Google Street View</a>.';                                            
            
        } elseif (preg_match('/youtube/i', $url )) {                                
            return  'Itt egy videó, ami segíthet: <a class="text-decoration-none" target="_blank" href="'.$url.'">YouTube</a>.';           
                                                
        } elseif (preg_match('/^http/i', $url )) {
            return 'Itt egy link, ami segíthet: <a class="text-decoration-none" target="_blank"  href="'.$url.'">KATTINTS</a>!';
                    
        } else {
               return $url; 
        }             
                
    }
    
    function prepareVideo() {

        if(preg_match('/youtube/i', $this->video)) $src = preg_replace('/watch\?v=/','embed/',$this->video);
        else $src = $this->video;
        
        $this->video_embed = ''
                . '<div class="embed-responsive embed-responsive-16by9">'
                . '<iframe class="embed-responsive-item" src="'.$src.'" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
                . '</div>';
    }
    
    function prepareQuestion() {
        
    }
    
    function prepareInput() {
        
    }
    
    function loadUserAnswer() {
        global $connection;
        
        $old_answer = $this->getOldAnswer();
        $new_answer = $this->getNewAnswer();
        
        if(!$new_answer) $new_answer = $old_answer;
        
        
        $result = $this->getUserResult($new_answer);
        
        if($new_answer != false and $new_answer != $old_answer) {
            if($old_answer != false ) {
                $stmt = $connection->prepare("UPDATE answers SET "
                . "answer = :answer, result = :result, timestamp = CURRENT_TIMESTAMP() "
                . " WHERE quiz_id = :quiz_id AND question_id = :question_id AND user_id = :user_id ");
            } else {
                $stmt = $connection->prepare("INSERT INTO answers (quiz_id, question_id, user_id, answer, result)"
                        . "VALUES (:quiz_id, :question_id, :user_id, :answer, :result)");
                
            }            
            $stmt->execute(array_merge($this->params, ['answer' => $new_answer, 'result' => $result]));
        }
        
        $this->user_answer = $new_answer;
        $this->user_result = $result;
                    
    }
    
    function getOldAnswer() {
        global $connection;
                   
        $stmt = $connection->prepare("SELECT * FROM answers WHERE quiz_id = :quiz_id AND user_id = :user_id AND question_id = :question_id  LIMIT 1");
        $stmt->execute($this->params); 
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->old_answer = $result;
        $old_answer = isset($result[0]) ? $result[0]['answer'] : false;
        
        return $old_answer;        
    }
    
    function getNewAnswer() {
        if(isset($_REQUEST['questions']) AND isset($_REQUEST['questions'][$this->id])) {
                return $_REQUEST['questions'][$this->id];            
        } else {
            return false;
        }
    }
    
    /**
     * 
     * @param type $user_answer
     * @return int -1 = wrong, 0 = null, 1 = manual validation needed, 2 = ok
     */
    function getUserResult($user_answer) {
        if($user_answer == '') return 0;
        
        if(!is_array($this->answer)) $this->answer = [$this->answer];
        
        foreach($this->answer as $good_answer) {
            if( strcasecmp(trim($user_answer),trim($good_answer)) == 0 ) return 2;    
        }
        
        return -1;
    }
    
    function createUserAnswer($result) {
      
        if($result == '2') {
            
            if(!is_array($this->answer)) $this->answer = [$this->answer];            
            return $this->answer[array_rand($this->answer)];
            
        } elseif( $result == '-1') {
            return readable_random_string(rand(4,6));
        }
        
    }
    
    function loadOtherAnswers($groupName = false) {
        global $connection;
    
        $sql = "SELECT  
                    SUM(if(result = '2', 1, 0)) as 'right', 
                    SUM(if(result = '-1', 1, 0)) as 'wrong'
                FROM answers ";
        
        if($groupName) {
            $sql .= "LEFT JOIN users
                    ON users.id = answers.user_id
                LEFT JOIN groups
                    ON groups.id = users.group_id
                WHERE  
		groups.name = :group_name AND ";
            $params = array_merge($this->params,[':group_name' => $groupName]); 
        }                
        else {
            $sql .= " WHERE ";
            $params = $this->params;
        }
        
        $sql .= "   
                    quiz_id = :quiz_id AND
                    question_id = :question_id AND
                    user_id != :user_id
                GROUP BY 
                    question_id ";
                               
        $stmt = $connection->prepare($sql);
        if(!$stmt->execute($params)) printr($connection->erroInfo());       
        $this->others = $stmt->fetch(PDO::FETCH_ASSOC);  
    }
    
    function getDifferentAnswers($groupName = false) {
        global $connection;
          
        $sql = "SELECT  answer, COUNT(answer) as quantity
                FROM answers ";
        
        
        if($groupName) {
            $sql .= "LEFT JOIN users
                    ON users.id = answers.user_id
                LEFT JOIN groups
                    ON groups.id = users.group_id
                WHERE  
		groups.name = :group_name AND ";
            $params = array_merge($this->params,[':group_name' => $groupName]); 
        }
                
        else {
            $sql .= " WHERE ";
            $params = $this->params;
        }
        
        $sql .= "   quiz_id = :quiz_id AND
                    question_id = :question_id AND
                    ( user_id != :user_id OR user_id = :user_id ) AND
                    result IN ('-1','1','2') 
                GROUP BY answer 
                ORDER BY quantity DESC 
            ";
                       
        $stmt = $connection->prepare($sql);
        if(!$stmt->execute($params)) printr($connection->errorInfo());       
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);        

        $return = [];
        foreach($results as $result) {
            $return[$result['answer']] = $result['quantity'];
        }
        return $return;
    }
    
    function pseudoRandom($from, $to, $unique) {
        
        return  $from + ( bindec(md5( $unique )) % ($to - $from) );
                       
    }
    
}
