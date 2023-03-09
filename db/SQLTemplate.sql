/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  borazslo
 * Updated: May 4, 2021
 */

CREATE TABLE `groups` ( 
    `id` INT NOT NULL AUTO_INCREMENT ,
    `name` VARCHAR(100) NOT NULL , 
    `level` INT NOT NULL DEFAULT '1' , 

PRIMARY KEY (`id`),
UNIQUE KEY `name` (`name`)

) ENGINE = InnoDB;



CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `active` int NOT NULL DEFAULT '0',
  `admin` int NOT NULL DEFAULT '0',
  `token` varchar(128),
  `tokenexpire` DATETIME,
  `group_id` int NOT NULL,

PRIMARY KEY (`id`),
-- UNIQUE KEY `name` (`name`),
UNIQUE KEY `Email` (`email`),
INDEX group_ind (group_id),
    FOREIGN KEY (group_id)
        REFERENCES groups(id)
);


CREATE TABLE `regnum_communities` (
    `id` INT NOT NULL AUTO_INCREMENT , 
    `name` VARCHAR(60) NOT NULL , 
    `group` VARCHAR(40) NULL , 
    `localRM` VARCHAR(60) NULL , 
    `averAge` INT(2) NULL , 

PRIMARY KEY (`id`)) ENGINE = InnoDB;


CREATE TABLE `answers` ( 
    `quiz_id` VARCHAR(50) NOT NULL , 
    `question_id` INT NOT NULL , 
    `user_id` INT(11) NOT NULL , 
    `answer` VARCHAR(255) NULL , 
    `result` ENUM('-1','0','1','2') NOT NULL , 
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

UNIQUE KEY `unique` ( `quiz_id`, `question_id`, `user_id`),
INDEX userId (user_id),
    FOREIGN KEY (user_id) 
        REFERENCES users(id)
)