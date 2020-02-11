--
-- Default Settings
--
INSERT INTO `settings` (`settings_key`, `settings_value`) VALUES
('app-title', 'OnePlace ##VERSION##'),
('app-url', 'https://oneplace.example.com'),
('noreply-email', 'no-reply@example.com'),
('noreply-footer-template', 'powered by onePlace'),
('noreply-from', 'YOURNAME'),
('noreply-port', '587'),
('noreply-pw', 'EMAILACCOUNTPASS'),
('noreply-server', 'SMTPHOSTNAME');

INSERT INTO `permission` (`permission_key`, `module`, `label`, `nav_label`, `nav_href`, `show_in_menu`, `needs_globaladmin`) VALUES
('index', 'Application\\Controller\\IndexController', 'Home', 'Home', '/', 0, 0),
('addtheme', 'Application\\Controller\\UploadController', 'Upload Theme', '', '/application/addtheme', 0, 1),
('themes', 'Application\\Controller\\IndexController', 'Theme Selection', '', '/application/themes', 0, 0),
('filepond', 'Application\\Controller\\UploadController', 'Upload Featured Image', '', '', 0, 0),
('uppy', 'Application\\Controller\\UploadController', 'Gallery Upload', '', '', 0, 0),
('togglemediapub', 'Application\\Controller\\UploadController', 'Gallery Upload', '', '', 0, 0),
('updateuppysort', 'Application\\Controller\\UploadController', 'Gallery Upload', '', '', 0, 0),
('quicksearch', 'Application\\Controller\\IndexController', 'Quick Search', '', '', 0, 0),
('updatefieldsort', 'Application\\Controller\\IndexController', 'Update Form Field Sorting', '', '', 0, 0),
('selectbool', 'Application\\Controller\\IndexController', 'Select Yes/No', '', '', 0, 0),
('checkforupdates', 'Application\\Controller\\IndexController', 'Check for Updates', '', '', 0, 1);

--
-- Default Widgets
--
INSERT INTO `core_widget` (`Widget_ID`, `widget_name`, `label`, `permission`) VALUES
(NULL, 'manage_themes', 'Manage Themes', 'themes-Application\\Controller\\IndexController'),
(NULL, 'discover_modules', 'Discover Modules', 'update-Application\\Controller\\IndexController'),
(NULL, 'help_support', 'Help & Support', 'index-Application\\Controller\\IndexController'),
(NULL, 'welcome_default', 'Welcome Default', 'index-Application\\Controller\\IndexController');