<?php

/**
 * Generate tons of users, groups, and answers
 *
 * @author admin
 */
class Bulk {
    private $date;
    private $connection;

    static function prefix()  { return '[bulk]'; }
    static function date()    { return '2010-01-01 12:12:12'; }
    
    public function __construct(Quiz $quiz) {
        global $connection;
        $this->date = $this->date();
        $this->quiz = $quiz; //TODO: itt a konkrét User van, nem pedig a bulk-ban létrejövők
        $this->connection = $connection;
        $this->prefix = $this->prefix();
    }
    
    public function addAll() {
        $this->addGroups();
        $this->addUsers();
        $this->addAnswers();
    }
 
    public function addGroups($count = 10, $param = []) {
        $stmt = $this->connection->prepare("INSERT IGNORE INTO groups (name, level) VALUES (:name, :level)");       
        $groups = [];
        for($i = 0; $i < $count; $i++ ) {
            $group = array(
                ':name' => $this->prefix." ".readable_random_string(rand(6,14)),
                ':level' => rand(1,4)
            );
            $stmt->execute($group);
        }        
    }
    
    public function addUsers($count = 70, $param = []) {                
        $stmt = $this->connection->prepare("SELECT id FROM groups WHERE name LIKE '".$this->prefix."%' ");       
        $stmt->execute();        
        $groups = $stmt->fetchAll();
        $stmt = $this->connection->prepare("INSERT IGNORE INTO users (name, email, password, active, group_id) VALUES (:name, :email, :password, :active, :group_id)");       
        
        global $config;
        for($i = 0; $i < $count; $i++ ) {
            $firstName = readable_random_string(rand(6,14));
            $lastName = readable_random_string(rand(6,14));
            $user = array(
                ':name' => $this->prefix." ".ucfirst($firstName). " " . ucfirst($lastName),
                ':email' => $firstName."_".$lastName."@bulkemail.not",
                ':password' => crypt("1234", $config['authentication']['salt']),
                ':active' => 1,
                ':group_id' => $groups[rand(0,count($groups)-1)]['id']  
            );
            $stmt->execute($user);
        }                 
    }

    public function addAnswers($count = 300, $param = []) {
        $stmt = $this->connection->prepare("SELECT id FROM users WHERE name LIKE '".$this->prefix."%' ");       
        $stmt->execute();        
        $users = $stmt->fetchAll();
        
        
        $answers=[];
        $NoUsers = count($users);
        $NoQuestions = count($this->quiz->questions);
        $NoAll = $NoQuestions * $NoUsers;
        for($i=0;$i<$NoAll;$i++) { $answers[] = $i; }
        $stmt = $this->connection->prepare("INSERT INTO answers (quiz_id, question_id, user_id, answer, result, timestamp) VALUES (:quiz_id, :question_id, :user_id, :answer, :result, '".$this->date."')");       
               
        for($i=0;$i<$count;$i++) {                        
            $key = array_rand($answers);            
            
            $answer = [
                ':quiz_id' => $this->quiz->id,
                ':question_id' => ( $key % $NoQuestions ) + 1 ,
                ':user_id' => $users[( $key - ( $key % $NoQuestions ) ) / $NoQuestions]['id'] ,
                ':result' => ["-1","1","2","2"][rand(0,3)]
            ];
                    
            if($answer[':result'] == '1') $res = ["-1","2"][rand(0,1)];
            else $res = $answer[':result'];                    
            $answer[':answer'] = $this->quiz->questions[ ( $answer[':question_id'] - 1 ) ]->createUserAnswer( $res );
            
            if(!$stmt->execute($answer)) printr($stmt->errorInfo());
                        
            unset($answers[$key]);
                    
        }        
    }  
    
    public function deleteAll() {
        $stmt = $this->connection->prepare("DELETE FROM answers WHERE timestamp = '".$this->date."';");
        $stmt->execute();
        
        $stmt = $this->connection->prepare("DELETE FROM users WHERE name LIKE '".$this->prefix."%';");
        $stmt->execute();
        
        $stmt = $this->connection->prepare("DELETE FROM groups WHERE name LIKE '".$this->prefix."%';");
        $stmt->execute();
    }
        
}
