CREATE TABLE IF NOT EXISTS `groupofgroups` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(10) NULL,
  `option1` VARCHAR(100) NULL,
  `option2` VARCHAR(100) NULL,
  `option3` VARCHAR(100) NULL,
  PRIMARY KEY (`id`)
  );
  
  CREATE TABLE IF NOT EXISTS `lookup_groupofgroups` (
  `groupofgroups_id` INT NOT NULL,
  `group_id` BIGINT NOT NULL,
  PRIMARY KEY (`groupofgroups_id`),
  UNIQUE INDEX `unique` (`groupofgroups_id` ASC, `group_id` ASC) VISIBLE);