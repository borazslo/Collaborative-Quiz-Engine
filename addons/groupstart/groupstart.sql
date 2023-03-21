CREATE TABLE `groupstart` (
  `group_id` BIGINT NOT NULL,
  `quiz_id` VARCHAR(50) NOT NULL,
  `groupstart` TIMESTAMP NULL);
  
ALTER TABLE `groupstart` 
ADD UNIQUE INDEX `nn` (`quiz_id` ASC, `group_id` ASC);

