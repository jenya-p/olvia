CREATE TABLE `content_content2course` (
	`content_id` INT NOT NULL,
	`course_id` INT NOT NULL,
	`priority` INT NULL,
	PRIMARY KEY (`content_id`, `course_id`)
)
COLLATE='utf8_general_ci'
;