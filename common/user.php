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
        $this->isAdmin = $d['admin'];//true;

    }
}
