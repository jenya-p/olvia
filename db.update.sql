ALTER TABLE `comments`
	ALTER `entity` DROP DEFAULT;
ALTER TABLE `comments`
	CHANGE COLUMN `entity` `entity` ENUM('order_call','customer','order_consultation','order_orders','todo') NOT NULL AFTER `id`;
ALTER TABLE `history`
	CHANGE COLUMN `entity` `entity` ENUM('content','user','content_division','videoalbum','video','photoalbum','photo','review','customer','master','banner','master-prices','order_orders','order_call','order_consultation','course','event','shedule','tarifs','todo') NULL DEFAULT NULL AFTER `id`;
	
	
CREATE TABLE `todos` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(128) NULL DEFAULT NULL,
	`body` LONGTEXT NULL,
	`status` ENUM('new','done','decline','deferred') NOT NULL DEFAULT 'new',
	`priority` INT(11) NOT NULL DEFAULT '0',
	`intensity` INT(11) NOT NULL DEFAULT '0',
	`user_id` INT(11) NOT NULL DEFAULT '0',
	`till_date` INT(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=5
;
