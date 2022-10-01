
DELIMITER $$
DROP PROCEDURE IF EXISTS `CreateError`$$
CREATE PROCEDURE `CreateError`(IN `message` VARCHAR(255))
    NO SQL
    SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT=message$$

DROP FUNCTION IF EXISTS `AuthorExists`$$
CREATE FUNCTION `AuthorExists` (`find_id` INT)
    RETURNS TINYINT(1)
    BEGIN
        DECLARE is_found BOOLEAN;
        SET is_found = (SELECT IF((SELECT id FROM authors WHERE id = find_id LIMIT 1), true, false));
    RETURN is_found;
END$$

DROP FUNCTION IF EXISTS `BookExists`$$
CREATE FUNCTION `BookExists` (`find_id` INT)
    RETURNS TINYINT(1)
    BEGIN
        DECLARE is_found BOOLEAN;
        SET is_found = (SELECT IF((SELECT id FROM books WHERE id = find_id LIMIT 1), true, false));
    RETURN is_found;
END$$

DROP FUNCTION IF EXISTS `OrderExists`$$
CREATE FUNCTION `OrderExists` (`find_id` INT)
    RETURNS TINYINT(1)
    BEGIN
        DECLARE is_found BOOLEAN;
        SET is_found = (SELECT IF((SELECT id FROM orders WHERE id = find_id LIMIT 1), true, false));
    RETURN is_found;
END$$

DROP FUNCTION IF EXISTS `ReadingExists`$$
CREATE FUNCTION `ReadingExists` (`find_id` INT)
    RETURNS TINYINT(1)
    BEGIN
        DECLARE is_found BOOLEAN;
        SET is_found = (SELECT IF((SELECT id FROM reading WHERE id = find_id LIMIT 1), true, false));
    RETURN is_found;
END$$

DROP FUNCTION IF EXISTS `UserExists`$$
CREATE FUNCTION `UserExists` (`find_id` INT)
    RETURNS TINYINT(1)
    BEGIN
        DECLARE is_found BOOLEAN;
        SET is_found = (SELECT IF((SELECT id FROM users WHERE id = find_id LIMIT 1), true, false));
    RETURN is_found;
END$$

DELIMITER ;

DROP TABLE IF EXISTS `authors`;
CREATE TABLE IF NOT EXISTS `authors` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name` varchar(255) DEFAULT NULL,
    `last_name` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE FULLTEXT INDEX idx_search ON authors (first_name, last_name);

DROP TRIGGER IF EXISTS `author_delete_authors`;
CREATE TRIGGER `author_delete_authors` AFTER DELETE ON `authors` FOR EACH ROW DELETE FROM `authors_books` WHERE author_id = OLD.id;

DROP TABLE IF EXISTS `authors_books`;
CREATE TABLE IF NOT EXISTS `authors_books` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `author_id` int(10) UNSIGNED DEFAULT NULL,
    `book_id` int(10) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_author_id` (`author_id`),
    KEY `fk_book_id` (`book_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TRIGGER IF EXISTS `author_book_insert`;
DELIMITER $$
CREATE TRIGGER `author_book_insert` BEFORE INSERT ON `authors_books` FOR EACH ROW
    IF      (!AuthorExists(NEW.author_id)) THEN CALL CreateError(CONCAT('Author ID: ', NEW.author_id, ' not found'));
    ELSEIF  (!BookExists(NEW.book_id))     THEN CALL CreateError(CONCAT('Book ID: ', NEW.book_id, ' not found'));
    END IF$$
DELIMITER ;

DROP TRIGGER IF EXISTS `author_book_update`;
DELIMITER $$
CREATE TRIGGER `author_book_update` BEFORE UPDATE ON `authors_books` FOR EACH ROW
    IF      (!AuthorExists(NEW.author_id)) THEN CALL CreateError(CONCAT('Author ID: ', NEW.author_id, ' not found'));
    ELSEIF  (!BookExists(NEW.book_id))     THEN CALL CreateError(CONCAT('Book ID: ', NEW.book_id, ' not found'));
    END IF$$
DELIMITER ;

DROP TABLE IF EXISTS `books`;
CREATE TABLE IF NOT EXISTS `books` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` varchar(255) DEFAULT NULL,
    `description` longtext,
    `quantity` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE FULLTEXT INDEX idx_search ON books (title, description);

DROP TRIGGER IF EXISTS `book_delete_authors`;
CREATE TRIGGER `book_delete_authors` AFTER DELETE ON `books` FOR EACH ROW DELETE FROM `authors_books` WHERE book_id = OLD.id;

DROP TRIGGER IF EXISTS `book_delete_orders`;
CREATE TRIGGER `book_delete_orders` AFTER DELETE ON `books` FOR EACH ROW DELETE FROM `orders` WHERE book_id = OLD.id;

DROP TRIGGER IF EXISTS `book_delete_readings`;
CREATE TRIGGER `book_delete_readings` AFTER DELETE ON `books` FOR EACH ROW DELETE FROM `reading` WHERE book_id = OLD.id;

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `book_id` int(10) UNSIGNED DEFAULT NULL,
    `user_id` int(10) UNSIGNED DEFAULT NULL,
    `reading_type` int(10) UNSIGNED NOT NULL,
    `status` smallint(5) UNSIGNED DEFAULT NULL,
    `start_at` date DEFAULT NULL,
    `end_at` date DEFAULT NULL,
    `quantity` int(11) NOT NULL DEFAULT '0',
    `created_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_book_id` (`book_id`),
    KEY `fk_user_id` (`user_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TRIGGER IF EXISTS `order_insert`;
DELIMITER $$
CREATE TRIGGER `order_insert` BEFORE INSERT ON `orders` FOR EACH ROW
    IF      (!BookExists(NEW.book_id)) THEN CALL CreateError(CONCAT('Book ID: ', NEW.book_id, ' not found'));
    ELSEIF  (!UserExists(NEW.user_id)) THEN CALL CreateError(CONCAT('User ID: ', NEW.user_id, ' not found'));
    END IF$$
DELIMITER ;

DROP TRIGGER IF EXISTS `order_update`;
DELIMITER $$
CREATE TRIGGER `order_update` BEFORE UPDATE ON `orders` FOR EACH ROW
    IF      (!BookExists(NEW.book_id)) THEN CALL CreateError(CONCAT('Book ID: ', NEW.book_id, ' not found'));
    ELSEIF  (!UserExists(NEW.user_id)) THEN CALL CreateError(CONCAT('User ID: ', NEW.user_id, ' not found'));
    END IF$$
DELIMITER ;


DROP TABLE IF EXISTS `reading`;
CREATE TABLE IF NOT EXISTS `reading` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `book_id` int(10) UNSIGNED DEFAULT NULL,
    `user_id` int(10) UNSIGNED DEFAULT NULL,
    `reading_type` int(10) UNSIGNED NOT NULL,
    `quantity` int(11) NOT NULL DEFAULT '0',
    `start_at` date DEFAULT NULL,
    `end_at` date DEFAULT NULL,
    `prolong_at` date DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_book_id` (`book_id`),
    KEY `fk_user_id` (`user_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TRIGGER IF EXISTS `reading_insert`;
DELIMITER $$
CREATE TRIGGER `reading_insert` BEFORE INSERT ON `reading` FOR EACH ROW
    IF      (!BookExists(NEW.book_id)) THEN CALL CreateError(CONCAT('Book ID: ', NEW.book_id, ' not found'));
    ELSEIF  (!UserExists(NEW.user_id)) THEN CALL CreateError(CONCAT('User ID: ', NEW.user_id, ' not found'));
    END IF$$
DELIMITER ;

DROP TRIGGER IF EXISTS `reading_update`;
DELIMITER $$
CREATE TRIGGER `reading_update` BEFORE UPDATE ON `reading` FOR EACH ROW
    IF      (!BookExists(NEW.book_id)) THEN CALL CreateError(CONCAT('Book ID: ', NEW.book_id, ' not found'));
    ELSEIF  (!UserExists(NEW.user_id)) THEN CALL CreateError(CONCAT('User ID: ', NEW.user_id, ' not found'));
    END IF$$
DELIMITER ;

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` varchar(60) NOT NULL,
    `password` varchar(64) NOT NULL,
    `email` varchar(60) DEFAULT NULL,
    `salt` varchar(255) NOT NULL,
    `roles` json NOT NULL,
    `first_name` varchar(255) DEFAULT NULL,
    `last_name` varchar(255) DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    KEY `idx_active` (`active`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE FULLTEXT INDEX idx_search ON users (username, email, first_name, last_name);

DROP TRIGGER IF EXISTS `user_delete_orders`;
CREATE TRIGGER `user_delete_orders` AFTER DELETE ON `users` FOR EACH ROW DELETE FROM `orders` WHERE user_id = OLD.id;

DROP TRIGGER IF EXISTS `user_delete_readings`;
CREATE TRIGGER `user_delete_readings` AFTER DELETE ON `users` FOR EACH ROW DELETE FROM `reading` WHERE `user_id` = OLD.id;
