ALTER TABLE `eqa`.`faqs` 
CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT ;

ALTER TABLE `eqa`.`faqs` 
CHANGE COLUMN `question` `question` TEXT NULL DEFAULT NULL ,
CHANGE COLUMN `answer` `answer` TEXT NULL DEFAULT NULL ;

ALTER TABLE `faqs` 
ADD COLUMN `status` INT NULL DEFAULT 1 AFTER `answer`;
