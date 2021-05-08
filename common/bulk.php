<?php

/**
 * Generate tons of users, groups, and answers
 *
 * @author admin
 */
class Bulk {
    private $date;
    private $prefix = '[bulk]';
    private $connection;
    
    public function __construct(Quiz $quiz) {
        global $bulkDate, $connection;
        $this->date = $bulkDate;
        $this->quiz = $quiz;
        $this->connection = $connection;
    }
    
    public function addAll() {
        $this->addGroups();
        $this->addUsers();
        //$this->addAnswers(10);
    }
 
    public function addGroups($count = 10, $param = []) {
        $stmt = $this->connection->prepare("INSERT IGNORE INTO groups (name, level) VALUES (:name, :level)");       
        $groups = [];
        for($i = 0; $i < $count; $i++ ) {
            $group = array(
                ':name' => $this->prefix." ".$this->readable_random_string(rand(6,14)),
                ':level' => rand(1,4)
            );
            $stmt->execute($group);
        }        
    }
    
    public function addUsers($count = 70, $param = []) {                
        $stmt = $this->connection->prepare("SELECT id FROM groups WHERE name LIKE '".$this->prefix."%' ");       
        $stmt->execute();        
        $groups = $stmt->fetchAll();
        $stmt = $this->connection->prepare("INSERT IGNORE INTO users (name, email, active, group_id) VALUES (:name, :email, :active, :group_id)");       
        
        for($i = 0; $i < $count; $i++ ) {
            $firstName = $this->readable_random_string(rand(6,14));
            $lastName = $this->readable_random_string(rand(6,14));
            $user = array(
                ':name' => $this->prefix." ".ucfirst($firstName). " " . ucfirst($lastName),
                ':email' => $firstName."_".$lastName."@bulkemail.not",
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
        
        for($i=0;$i<$count;$i++) {
            
            
        }        
    }  
    
    public function deleteAll() {
        $stmt = $this->connection->prepare("DELETE FROM users WHERE name LIKE '".$this->prefix."%';");
        $stmt->execute();
        
        $stmt = $this->connection->prepare("DELETE FROM groups WHERE name LIKE '".$this->prefix."%';");
        $stmt->execute();
    }
    
    /**
    * Generates human-readable string.
    * https://gist.github.com/sepehr/3371339
    * 
    * @param string $length Desired length of random string.
    * 
    * retuen string Random string.
    */ 
   static function readable_random_string($length = 6)
   {  
       $string = '';
       $vowels = array("a","e","i","o","u");  
       $consonants = array(
           'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 
           'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'
       );  

       $max = $length / 2;
       for ($i = 1; $i <= $max; $i++)
       {
           $string .= $consonants[rand(0,19)];
           $string .= $vowels[rand(0,4)];
       }

       return $string;
   }
    
}
