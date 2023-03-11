<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of user
 *
 * @author webdev
 */
class User {
    //put your code here
    public function __construct(&$d=array()) {
        
	if (!isset($d) || count($d) < 10) // TODO: miért van itt ez a count($d)?? Ami 6 volt sokáig.
		//return;
        foreach( ['name','id','level','group','group_id','group2','group3'=> false, 'admin' => false] as $key => $value) {
            $hasdefault = false;
            if(!is_numeric($key)) { $hasdefault = true; $default = $value; $value = $key; }

            if(isset($d[$value]) )
                $this->$value = $d[$value];
            elseif($hasdefault == true) {
                $this->$value = $default;
            }                

        }        
    }
}

function companionsOfCurrentUsers() {
    global $user, $connection;
    
    $stmt = $connection->prepare("SELECT users.* FROM users LEFT JOIN `groups` ON users.group_id = groups.id WHERE groups.name = :group_name AND users.active = 1 ");
    $stmt->execute(array(":group_name" => $user->group));
    $groups = $stmt->fetchAll();
    $return = [];
    foreach($groups as $group)
        $return[] = $group['name'];
    
    return $return;
    
}