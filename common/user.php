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
        
	if (!isset($d) || count($d) < 6)
		return;
        $this->name = $d['name'];//'Kis Elemér';
        $this->id = $d['id'];//1487;
        $this->level = $d['level'];//2;
        $this->group = $d['group'];//'Medve';
        $this->group2 = $d['group2'];//"Emlős";
        if(isset($d['group3'])) $this->group3 = $d['group3'];//"Emlős";
        $this->isAdmin = $d['admin'];//true;
        
    }
}

function companionsOfCurrentUsers() {
    global $user, $connection;
    
    $stmt = $connection->prepare("SELECT users.* FROM users LEFT JOIN groups ON users.group_id = groups.id WHERE groups.name = :group_name AND users.active = 1 ");
    $stmt->execute(array(":group_name" => $user->group));
    $groups = $stmt->fetchAll();
    $return = [];
    foreach($groups as $group)
        $return[] = $group['name'];
    
    return $return;
    
}