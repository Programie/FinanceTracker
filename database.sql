SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `news`
(
    `id`    int(11)                                            NOT NULL AUTO_INCREMENT,
    `isin`  varchar(100)                                       NOT NULL,
    `name`  varchar(200)                                       NOT NULL,
    `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `states`
(
    `id`            int(11)      NOT NULL AUTO_INCREMENT,
    `isin`          varchar(100) NOT NULL,
    `name`          varchar(200) NOT NULL,
    `updated`       datetime     NOT NULL,
    `price`         float        NOT NULL,
    `dayStartPrice` float DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `watchlistentries`
(
    `id`           int(11)      NOT NULL AUTO_INCREMENT,
    `watchListId`  int(11)      NOT NULL,
    `stateId`      int(11)               DEFAULT NULL,
    `isin`         varchar(100) NOT NULL,
    `name`         varchar(100) NOT NULL,
    `date`         date                  DEFAULT NULL,
    `count`        float        NOT NULL DEFAULT 1,
    `price`        float        NOT NULL DEFAULT 0,
    `limitEnabled` tinyint(1)   NOT NULL DEFAULT 0,
    `lowLimit`     float                 DEFAULT NULL,
    `highLimit`    float                 DEFAULT NULL,
    `newsEnabled`  tinyint(1)   NOT NULL DEFAULT 1,
    `notified`     tinyint(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `watchListId_isin` (`watchListId`, `isin`),
    KEY `isin` (`isin`),
    KEY `watchlistentries_stateId` (`stateId`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `watchlists`
(
    `id`                     int(11)      NOT NULL AUTO_INCREMENT,
    `name`                   varchar(100) NOT NULL,
    `notificationRecipients` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;


ALTER TABLE `watchlistentries`
    ADD CONSTRAINT `watchlistentries_stateId` FOREIGN KEY (`stateId`) REFERENCES `states` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT `watchlistentries_watchListId` FOREIGN KEY (`watchListId`) REFERENCES `watchlists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `watchlists` (`name`)
VALUES ('Watchlist'),
       ('Some-list'),
       ('Another-list');
