CREATE ALGORITHM=UNDEFINED DEFINER=`homestead`@`%` SQL SECURITY DEFINER VIEW `messages_v` AS select `m`.`id` AS `id`,`m`.`uuid` AS `uuid`,`m`.`from` AS `from`,`m`.`email` AS `email`,`m`.`to_uuid` AS `to_uuid`,`m`.`subject` AS `subject`,`m`.`message` AS `message`,`m`.`status` AS `status`,`m`.`date_sent` AS `date_sent`,`m`.`deleted` AS `deleted` from `messages` `m`