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

INSERT INTO `permission` (`permission_key`, `module`, `label`, `nav_label`, `nav_href`, `show_in_menu`) VALUES
('index', 'Application\\Controller\\IndexController', 'Home', 'Home', '/', 0),
('update', 'Application\\Controller\\IndexController', 'Updates', '', '', 0),
('addtheme', 'Application\\Controller\\IndexController', 'Upload Theme', '', '/application/addtheme', 0),
('themes', 'Application\\Controller\\IndexController', 'Theme Selection', '', '/application/themes', 0);