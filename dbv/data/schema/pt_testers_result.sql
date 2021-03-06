CREATE TABLE `pt_testers_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `pt_round_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `pt_tester_id` int(11) NOT NULL,
  `pt_sample_id` int(11) NOT NULL,
  `result` varchar(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1