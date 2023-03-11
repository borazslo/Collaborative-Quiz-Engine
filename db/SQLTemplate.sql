/* 
 * docker exec -i db mysql -uMYSQL_USER -pMYSQL_PASSWORD MY_DATABASE < db/SQLTemplate.sql
 */
/**
 * Author:  borazslo
 * Updated: May 4, 2021
 */

CREATE TABLE IF NOT EXISTS `groups` ( 
    `id` INT NOT NULL AUTO_INCREMENT ,
    `name` VARCHAR(100) NOT NULL , 
    `level` INT NOT NULL DEFAULT '1' , 

PRIMARY KEY (`id`),
UNIQUE KEY `name` (`name`)

) ENGINE = InnoDB;



CREATE TABLE IF NOT EXISTS `users` (
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
UNIQUE KEY `Email` (`email`)
);


CREATE TABLE IF NOT EXISTS  `regnum_communities` (
    `id` INT NOT NULL AUTO_INCREMENT , 
    `name` VARCHAR(60) NOT NULL , 
    `group` VARCHAR(40) NULL , 
    `localRM` VARCHAR(60) NULL , 
    `averAge` INT(2) NULL , 

PRIMARY KEY (`id`)) ENGINE = InnoDB;


CREATE TABLE IF NOT EXISTS  `answers` ( 
    `quiz_id` VARCHAR(50) NOT NULL , 
    `question_id` INT NOT NULL , 
    `user_id` INT(11) NOT NULL , 
    `answer` VARCHAR(255) NULL , 
    `result` ENUM('-1','0','1','2') NOT NULL , 
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

UNIQUE KEY `unique` ( `quiz_id`, `question_id`, `user_id`),
INDEX userId (user_id)
)