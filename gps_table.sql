-- GPS positions table
CREATE TABLE IF NOT EXISTS `gps_positions` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `vehicle_id`  INT(11)      NOT NULL,
  `latitude`    DECIMAL(10,7) NOT NULL,
  `longitude`   DECIMAL(10,7) NOT NULL,
  `speed`       DECIMAL(5,1)  DEFAULT 0.0,
  `altitude`    DECIMAL(7,1)  DEFAULT NULL,
  `heading`     DECIMAL(5,1)  DEFAULT NULL,
  `recorded_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `recorded_at` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
