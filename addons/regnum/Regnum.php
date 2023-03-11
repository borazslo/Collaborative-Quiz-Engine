<?php

class Regnum {
            
    static function login($result) {
        global $connection;
                
        $stmt = $connection->prepare("SELECT * FROM regnum_communities WHERE name = :name ");
        $stmt->execute([':name' => $result['group']]);
        $rmgroups = $stmt->fetch();
        
        if($rmgroups) {
            $result['group2'] = $rmgroups['group'];
            $result['group3'] = $rmgroups['localRM'];
        }
        
        return $result;
    }
    
    static function pairsNevHelyi() {
       global $connection, $user;
                     
       $sql = "SELECT * FROM regnum_communities";
       
       if(isset($user->group2) AND $user->group2 != '' and $user->level < 2)
           $sql .= " WHERE `group` = '".$user->group2."' ";
                      
       $stmt = $connection->prepare($sql);
       $stmt->execute();
       $groups = $stmt->fetchAll();
       
       $return = [];
       foreach($groups as $group) {
           if($group['localRM'] == "") $group['localRM'] = "Szórvány";
            $return[] = [$group['name'],$group['localRM']];
       }
       
       return $return;
       
   }

    static function pairsNevReteg() {
       global $connection, $user;
                     
       $sql = "SELECT * FROM regnum_communities ";
       
       if($user->group3 != '' and $user->level < 2)
           $sql .= " AND `localRM` = '".$user->group3."' ";
                      
       $stmt = $connection->prepare($sql);
       $stmt->execute();
       $groups = $stmt->fetchAll();
       
       $return = [];
       foreach($groups as $group) {
           if($group['group'] == "") $group['group'] = "rétegen kívül";
            $return[] = [$group['name'],$group['group']];
       }
       
       return $return;
       
   }   
   
}
