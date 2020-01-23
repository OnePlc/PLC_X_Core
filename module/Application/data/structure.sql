--
-- Core Form
--
CREATE TABLE `core_form` (
  `form_key` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `core_form`
  ADD PRIMARY KEY (`form_key`);

--
-- Core Form Button
--
CREATE TABLE `core_form_button` (
  `Button_ID` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `icon` varchar(100) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL,
  `href` varchar(255) NOT NULL,
  `class` varchar(100) NOT NULL,
  `append` varchar(100) NOT NULL DEFAULT '',
  `form` varchar(50) NOT NULL,
  `mode` varchar(10) NOT NULL DEFAULT 'link',
  `filter_check` varchar(50) NOT NULL,
  `filter_value` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `core_form_button`
  ADD PRIMARY KEY (`Button_ID`);

ALTER TABLE `core_form_button`
  MODIFY `Button_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Core Form Tab
--
CREATE TABLE `core_form_tab` (
  `Tab_ID` varchar(50) NOT NULL,
  `form` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `subtitle` varchar(100) NOT NULL,
  `icon` varchar(30) NOT NULL,
  `counter` varchar(50) NOT NULL DEFAULT '',
  `sort_id` int(4) NOT NULL,
  `filter_check` varchar(100) NOT NULL,
  `filter_value` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `core_form_tab`
  ADD PRIMARY KEY (`Tab_ID`);

--
-- Core Index Table
--
CREATE TABLE `core_index_table` (
  `table_name` varchar(50) NOT NULL,
  `form` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `core_index_table`
  ADD PRIMARY KEY (`table_name`);

--
-- Core Form Field
--
CREATE TABLE `core_form_field` (
  `Field_ID` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `label` varchar(100) NOT NULL,
  `fieldkey` varchar(100) NOT NULL,
  `tab` varchar(50) NOT NULL,
  `form` varchar(100) NOT NULL,
  `class` varchar(100) NOT NULL,
  `url_view` varchar(255) NOT NULL,
  `url_ist` varchar(255) NOT NULL,
  `show_widget_left` tinyint(1) NOT NULL,
  `allow_clear` tinyint(1) NOT NULL DEFAULT 1,
  `readonly` tinyint(1) NOT NULL DEFAULT 0,
  `tbl_cached_name` varchar(100) NOT NULL DEFAULT '',
  `tbl_class` varchar(200) NOT NULL,
  `tbl_permission` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `core_form_field`
  ADD PRIMARY KEY (`Field_ID`);

ALTER TABLE `core_form_field`
  MODIFY `Field_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Core Metric
--
CREATE TABLE `core_metric` (
  `user_idfs` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `date` datetime NOT NULL,
  `comment` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `core_metric`
  ADD PRIMARY KEY (`user_idfs`,`action`,`date`);

--
-- Core Performance Log
--
CREATE TABLE `core_perfomance_log` (
  `action` varchar(50) NOT NULL DEFAULT '',
  `utime` float NOT NULL,
  `stime` float NOT NULL,
  `date` datetime NOT NULL,
  `log_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `core_perfomance_log`
  ADD PRIMARY KEY (`log_id`);

ALTER TABLE `core_perfomance_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Settings
--
CREATE TABLE `settings` (
  `settings_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `settings_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `settings`
  ADD PRIMARY KEY (`settings_key`);

--
-- Save
--
COMMIT;