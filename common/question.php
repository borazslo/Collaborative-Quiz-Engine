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
                                
        $this->prepareHint();
        $this->prepareQuestion();
        $this->prepareInput();
        
        $this->getUserAnswer();
        
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
                                
            return  ''
                    . '<div class="embed-responsive embed-responsive-16by9">'
                    . '<iframe class="embed-responsive-item" src="'.preg_replace('/watch\?v=/','embed/',$url).'" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
                    . '</div>'
                    . '<br/>Itt egy videó, ami segíthet: <a class="text-decoration-none" target="_blank" href="'.$url.'">YouTube</a>.';           
                                                
        } elseif (preg_match('/^http/i', $url )) {
            return 'Itt egy link, ami segíthet: <a class="text-decoration-none" target="_blank"  href="'.$url.'">KATTINTS</a>!';
        
            
        } else {
               return $url; 
        }             
        
        
    }
    
    function prepareQuestion() {
        
    }
    
    function prepareInput() {
        
    }
    
    function getUserAnswer() {
        global $connection, $user;
    
        $params = [
            'quiz_id' => $this->quiz_id,
            'user_id' => $user->id,
            'question_id' => $this->id
            ];
        
        $stmt = $connection->prepare("SELECT * FROM answers WHERE quiz_id = :quiz_id AND user_id = :user_id AND question_id = :question_id  LIMIT 1");
        $stmt->execute($params); 
        
        $result = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);        
        $old_answer = isset($result[1]) ? $result[1]['answer'] : false;
        
                
        if(isset($_REQUEST['questions']) AND isset($_REQUEST['questions'][$this->id])) {
                $new_answer = $_REQUEST['questions'][$this->id];            
        } else {
            $new_answer = $old_answer;
        }
        
        //validet, get result
        // -1 = wrong answer
        // 0 = no answer
        // 2 = good answer
        // 1 = answer needs manual revision
        $result = rand(-1,2);
        
        if($new_answer != false and $new_answer != $old_answer) {
            if($old_answer != false ) {
                $stmt = $connection->prepare("UPDATE answers SET "
                . "answer = :answer, result = :result, timestamp = CURRENT_TIMESTAMP() "
                . " WHERE quiz_id = :quiz_id AND question_id = :question_id AND user_id = :user_id ");
            } else {
                $stmt = $connection->prepare("INSERT INTO answers (quiz_id, question_id, user_id, answer, result)"
                        . "VALUES (:quiz_id, :question_id, :user_id, :answer, :result)");

            }
            $params['answer'] = $new_answer;
            $params['result'] = $result;
            $stmt->execute($params);    
        }
        
        $this->user_answer = $new_answer;
        $this->user_result = $result;
                    
    }
}
