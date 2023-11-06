CREATE TABLE IF NOT EXISTS `b_transformercontroller_time_statistic` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `COMMAND_NAME` varchar(255) NOT NULL,
  `FILE_SIZE` int NULL,
  `DOMAIN` varchar(255) NOT NULL,
  `LICENSE_KEY` varchar(255) NULL,
  `ERROR` int NULL,
  `ERROR_INFO` TEXT NULL,
  `TIME_ADD` datetime NULL,
  `TIME_START` int NULL,
  `TIME_EXEC` int NULL,
  `TIME_UPLOAD` int NULL,
  `TIME_END` int NULL,
  `TIME_END_ABSOLUTE` datetime NULL,
  `QUEUE_ID` int NULL,
  `GUID` varchar(32) NULL,
  PRIMARY KEY (ID),
  index ix_trans_cont_time_stat_time_add (TIME_ADD),
  index ix_trans_cont_time_stat_time_end (TIME_END),
  index ix_trans_cont_time_stat_time_end_abs (TIME_END_ABSOLUTE),
  index ix_trans_cont_time_stat_command (COMMAND_NAME),
  index ix_trans_cont_time_stat_queue (QUEUE_ID)
);
CREATE TABLE IF NOT EXISTS `b_transformercontroller_usage_statistic` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `COMMAND_NAME` varchar(255) NOT NULL,
  `FILE_SIZE` int NULL,
  `DOMAIN` varchar(255) NOT NULL,
  `LICENSE_KEY` varchar(255) NULL,
  `TARIF` varchar(255) NULL,
  `DATE` DATETIME NOT NULL,
  `QUEUE_ID` int NULL,
  `GUID` varchar(32) NULL,
  PRIMARY KEY (ID),
  index ix_trans_cont_usage_stat_date (DATE),
  index ix_trans_cont_usage_stat_command (COMMAND_NAME),
  index ix_trans_cont_usage_stat_queue (QUEUE_ID),
  index ix_trans_cont_usage_stat_domain (DOMAIN)
);
CREATE TABLE IF NOT EXISTS `b_transformercontroller_ban_list` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DOMAIN` varchar(255) NOT NULL,
  `LICENSE_KEY` varchar(255) NULL,
  `DATE_ADD` DATETIME NOT NULL,
  `DATE_END` DATETIME NULL,
  `REASON` TEXT NULL,
  `QUEUE_ID` int NULL,
  PRIMARY KEY (ID),
  index ix_trans_cont_ban_list_domain (DOMAIN),
  index ix_trans_cont_ban_list_queue (QUEUE_ID)
);
CREATE TABLE IF NOT EXISTS `b_transformercontroller_limits` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `TARIF` varchar(255) NULL,
  `TYPE` varchar(255) NULL,
  `COMMAND_NAME` varchar(255) NULL,
  `DOMAIN` varchar(255) NULL,
  `LICENSE_KEY` varchar(255) NULL,
  `COMMANDS_COUNT` INT NULL,
  `FILE_SIZE` INT NULL,
  `PERIOD` INT NULL,
  `QUEUE_ID` int NULL,
  PRIMARY KEY (ID),
  index ix_trans_cont_limits_tarif (TARIF),
  index ix_trans_cont_limits_type (TYPE),
  index ix_trans_cont_limits_queue (QUEUE_ID)
);
CREATE TABLE IF NOT EXISTS `b_transformercontroller_queue` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NAME` varchar(255) NOT NULL,
  `WORKERS` INT NOT NULL,
  `SORT` INT NOT NULL DEFAULT 500,
  PRIMARY KEY (ID),
  UNIQUE transf_cont_queue_name(NAME)
);