--
-- Skeleton Form Tabs
--
INSERT INTO `core_form_tab` (`Tab_ID`, `form`, `title`, `subtitle`, `icon`, `counter`, `sort_id`, `filter_check`, `filter_value`) VALUES
('skeleton-dates', 'skeleton-single', 'Dates', 'Deadlines', 'fas fa-calendar', '', '1', '', ''),
('skeleton-finance', 'skeleton-single', 'Finance', 'Financial', 'fas fa-money-check-alt', '', '2', '', ''),
('skeleton-internal', 'skeleton-single', 'Internal', 'Internal Stuff', 'fas fa-user-secret', '', '3', '', ''),
('skeleton-gallery', 'skeleton-single', 'Gallery', 'Images', 'fas fa-images', '', '4', '', ''),
('skeleton-matching', 'skeleton-single', 'Matching', 'Related Pleas', 'fas fa-list', '', '5', '', '');

--
-- Skeleton Base Form Fields
--
INSERT INTO `core_form_field` (`Field_ID`, `type`, `label`, `fieldkey`, `tab`, `form`, `class`, `url_view`, `url_ist`, `show_widget_left`, `allow_clear`, `readonly`, `tbl_cached_name`, `tbl_class`, `tbl_permission`) VALUES
(NULL, 'select', 'Model', 'model_idfs', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '/tag/api/list/skeleton-single_3', '0', '1', '0', 'tag-single', 'OnePlace\\Tag\\Model\\TagTable','add-OnePlace\\Tag\\Controller\\ModelController'),
(NULL, 'select', 'System', 'system_idfs', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '/tag/api/list/skeleton-single_4', '0', '1', '0', 'tag-single', 'OnePlace\\Tag\\Model\\TagTable','add-OnePlace\\Tag\\Controller\\SystemController'),
(NULL, 'select', 'Coolant', 'coolant_idfs', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '/tag/api/list/skeleton-single_5', '0', '1', '0', 'tag-single', 'OnePlace\\Tag\\Model\\TagTable','add-OnePlace\\Tag\\Controller\\CoolantController'),
(NULL, 'select', 'Condition', 'condition_idfs', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '/tag/api/list/skeleton-single_6', '0', '1', '0', 'tag-single', 'OnePlace\\Tag\\Model\\TagTable','add-OnePlace\\Tag\\Controller\\ConditionController'),
(NULL, 'select', 'Loadbase', 'loadbase_idfs', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '/tag/api/list/skeleton-single_7', '0', '1', '0', 'tag-single', 'OnePlace\\Tag\\Model\\TagTable','add-OnePlace\\Tag\\Controller\\LoadbaseController'),
(NULL, 'select', 'Location', 'location_idfs', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '/tag/api/list/skeleton-single_8', '0', '1', '0', 'tag-single', 'OnePlace\\Tag\\Model\\TagTable','add-OnePlace\\Tag\\Controller\\LocationController'),
(NULL, 'select', 'Origin', 'origin_idfs', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '/tag/api/list/skeleton-single_9', '0', '1', '0', 'tag-single', 'OnePlace\\Tag\\Model\\TagTable','add-OnePlace\\Tag\\Controller\\OriginController'),
(NULL, 'select', 'State', 'state_idfs', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '/tag/api/list/skeleton-single_2', '0', '1', '0', 'tag-single', 'OnePlace\\Tag\\Model\\TagTable','add-OnePlace\\Tag\\Controller\\StateController'),
(NULL, 'multiselect', 'Categories', 'categories', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '/tag/api/list/skeleton-single_1', '0', '1', '0', 'tag-single', 'OnePlace\\Tag\\Model\\TagTable','add-OnePlace\\Tag\\Controller\\CategoryController'),
(NULL, 'select', 'Owner', 'owner_idfs', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '/api/contact/list', '0', '1', '0', 'contact-single', 'OnePlace\\Contact\\Model\\ContactTable','add-OnePlace\\Contact\\Controller\\ContactController'),
(NULL, 'select', 'Warranty', 'warranty_idfs', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '/tag/api/list/skeleton-single_10', '0', '1', '0', 'tag-single', 'OnePlace\\Tag\\Model\\TagTable','add-OnePlace\\Tag\\Controller\\WarrantyController'),
(NULL, 'text', 'Year of Construction', 'year_of_construction', 'skeleton-base', 'skeleton-single', 'col-md-1', '', '', '0', '1', '0', '', '', ''),
(NULL, 'text', 'Caliber', 'caliber', 'skeleton-base', 'skeleton-single', 'col-md-1', '', '', '0', '1', '0', '', '', ''),
(NULL, 'text', 'Descriptive Nr', 'descriptive_nr', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '', '0', '1', '0', '', '', ''),
(NULL, 'text', 'Lifetime', 'lifetime', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '', '0', '1', '0', '', '', ''),
(NULL, 'text', 'Weight', 'weight', 'skeleton-base', 'skeleton-single', 'col-md-2', '', '', '0', '1', '0', '', '', ''),
(NULL, 'text', 'Specialaddons', 'special_addons', 'skeleton-base', 'skeleton-single', 'col-md-1', '', '', '0', '1', '0', '', '', ''),
(NULL, 'textarea', 'Description', 'description', 'skeleton-base', 'skeleton-single', 'col-md-12', '', '', '0', '1', '0', '', '', '');

--
-- Skeleton Dates Form Fields
--
INSERT INTO `core_form_field` (`Field_ID`, `type`, `label`, `fieldkey`, `tab`, `form`, `class`, `url_view`, `url_ist`, `show_widget_left`, `allow_clear`, `readonly`, `tbl_cached_name`, `tbl_class`, `tbl_permission`) VALUES
(NULL, 'text', 'Ready to sell', 'sell_ready_date', 'skeleton-dates', 'skeleton-single', 'col-md-2', '', '', '0', '1', '0', '', '', ''),
(NULL, 'date', 'Info received', 'info_received_date', 'skeleton-dates', 'skeleton-single', 'col-md-2', '', '', '0', '1', '0', '', '', ''),
(NULL, 'date', 'Deadline received', 'deadline_received_date', 'skeleton-dates', 'skeleton-single', 'col-md-2', '', '', '0', '1', '0', '', '', ''),
(NULL, 'date', 'Price received', 'priceus_received_date', 'skeleton-dates', 'skeleton-single', 'col-md-2', '', '', '0', '1', '0', '', '', ''),
(NULL, 'select', 'Deadline received by', 'deadline_received_by', 'skeleton-dates', 'skeleton-single', 'col-md-2', '', '/api/contact/list', '0', '1', '0', 'contact-single', 'OnePlace\\Contact\\Model\\ContactTable','add-OnePlace\\Contact\\Controller\\ContactController'),
(NULL, 'select', 'Price received by', 'priceus_received_by', 'skeleton-dates', 'skeleton-single', 'col-md-2', '', '/api/contact/list', '0', '1', '0', 'contact-single', 'OnePlace\\Contact\\Model\\ContactTable','add-OnePlace\\Contact\\Controller\\ContactController'),
(NULL, 'select', 'Deliverytime', 'deliverytime_idfs', 'skeleton-dates', 'skeleton-single', 'col-md-2', '', '/tag/api/list/skeleton-single_11', '0', '1', '0', 'tag-single', 'OnePlace\\Tag\\Model\\TagTable','add-OnePlace\\Tag\\Controller\\DeliverytimeController');

--
-- Skeleton Finance Form Fields
--

INSERT INTO `core_form_field` (`Field_ID`, `type`, `label`, `fieldkey`, `tab`, `form`, `class`, `url_view`, `url_ist`, `show_widget_left`, `allow_clear`, `readonly`, `tbl_cached_name`, `tbl_class`, `tbl_permission`) VALUES
(NULL, 'currency', 'Our Price', 'price_us', 'skeleton-finance', 'skeleton-single', 'col-md-2', '', '', 0, 1, '0', '', '', ''),
(NULL, 'currency', 'Sell Price', 'price_sell', 'skeleton-finance', 'skeleton-single', 'col-md-2', '', '', 0, 1, '0', '', '', ''),
(NULL, 'currency', 'Retailer Price', 'price_retailer', 'skeleton-finance', 'skeleton-single', 'col-md-2', '', '', 0, 1, '0', '', '', ''),
(NULL, 'currency', 'New Price', 'price_new', 'skeleton-finance', 'skeleton-single', 'col-md-2', '', '', 0, 1, '0', '', '', ''),
(NULL, 'currency', 'Price Margin', 'price_margin', 'skeleton-finance', 'skeleton-single', 'col-md-2', '', '', 0, 1, '0', '', '', '');

--
-- Skeleton Gallery Form Fields
--

--
-- Skeleton Internal Form Fields
--
INSERT INTO `core_form_field` (`Field_ID`, `type`, `label`, `fieldkey`, `tab`, `form`, `class`, `url_view`, `url_ist`, `show_widget_left`, `allow_clear`, `readonly`, `tbl_cached_name`, `tbl_class`, `tbl_permission`) VALUES
(NULL, 'textarea', 'Internal Description', 'description_internal', 'skeleton-internal', 'skeleton-single', 'col-md-12', '', '', '0', '1', '0', '', '', '');

--
-- Skeleton Matching Form Fields
--

