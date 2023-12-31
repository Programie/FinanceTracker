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
    `id`            int(11)             NOT NULL AUTO_INCREMENT,
    `isin`          varchar(100)        NOT NULL,
    `name`          varchar(200)        NOT NULL,
    `priceType`     enum ('bid', 'ask') NOT NULL,
    `fetched`       datetime            NOT NULL,
    `updated`       datetime            NOT NULL,
    `price`         float               NOT NULL,
    `previousPrice` float DEFAULT NULL,
    `dayStartPrice` float DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `isin_priceType` (`isin`, `priceType`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `watchlistentries`
(
    `id`                        int(11)      NOT NULL AUTO_INCREMENT,
    `watchListId`               int(11)      NOT NULL,
    `stateId`                   int(11)               DEFAULT NULL,
    `isin`                      varchar(100) NOT NULL,
    `wkn`                       varchar(100)          DEFAULT NULL,
    `name`                      varchar(100) NOT NULL,
    `date`                      date                  DEFAULT NULL,
    `count`                     float        NOT NULL DEFAULT 1,
    `price`                     float        NOT NULL DEFAULT 0,
    `limitEnabled`              tinyint(1)   NOT NULL DEFAULT 0,
    `lowLimit`                  float                 DEFAULT NULL,
    `highLimit`                 float                 DEFAULT NULL,
    `lastLimitReached`          datetime              DEFAULT NULL,
    `fastUpdateIntervalEnabled` tinyint(1)   NOT NULL DEFAULT 0,
    `newsEnabled`               tinyint(1)   NOT NULL DEFAULT 1,
    `notificationDate`          datetime              DEFAULT NULL,
    `notificationType`          enum ('low', 'high')  DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `isin` (`isin`),
    KEY `stateId` (`stateId`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `watchlists`
(
    `id`                     int(11)            NOT NULL AUTO_INCREMENT,
    `name`                   varchar(100)       NOT NULL,
    `enabled`                tinyint(1)         NOT NULL DEFAULT 1,
    `priceType`              enum ('bid','ask') NOT NULL,
    `notificationRecipients` text                        DEFAULT NULL,
    `notificationsEnabled`   tinyint(1)         NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `watchlists` (`name`, `priceType`)
VALUES ('Watchlist', 'bid'),
       ('Some-list', 'ask'),
       ('Another-list', 'ask');
