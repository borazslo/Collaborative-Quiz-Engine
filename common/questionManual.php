<?php

class questionManual extends Question {
    
    /**
     * 
     * @param type $user_answer
     * @return int -1 = wrong, 0 = null, 1 = manual validation needed, 2 = ok
     */
    function getUserResult($user_answer) {  
        if(isset($this->old_answer[0]) AND $this->old_answer[0]['answer'] != "") {
            if($user_answer != $this->old_answer[0]['answer'] ) { //There was an old answer, but a new has arrived
                return $this->autoValidate($user_answer);
            } else { //There is no new answer but the old one.
                return $this->old_answer[0]['result'];
            }            
        } elseif($user_answer != '') { //New answer has arrived
            return $this->autoValidate($user_answer);
        } else {
            return 0;
        }
    } 
    
    function autoValidate($user_answer) {
        global $connection;
        $stmt = $connection->prepare("SELECT * FROM answers WHERE question_id = :question_id AND quiz_id = :quiz_id AND LOWER(answer) LIKE :user_answer  ORDER BY timestamp DESC LIMIT 1"); //AND LOWER(answer) LIKE '%:user_answer%'
        $stmt->execute(['quiz_id'=>$this->quiz_id, 'question_id' => $this->id,'user_answer' => '%'.$user_answer.'%']); 
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(isset($result[0])) {
            if( strcasecmp(trim($user_answer),trim($result[0]['answer'])) == 0 ) {
                return $result[0]['result'];    
            }
        }
        if($user_answer == "") return 0;
        
        return 1;        
    }
    
    
}



