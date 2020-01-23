# Dynamic Fields

Now that you have basic knowledge of onePlace Modules, and how they are structured,
we'll show you the easy way to add new fields to your modules.

## Add new field to database

The easiest way to add a new field is to add it to an existing tab within an existing
form. All you have to do, is to add it to the database. onePlace will take care of the rest,
to attach it to your Entity Model, have Getters and Setters, add corresponding HTML Element to
Forms, and display on Index Tables if wanted. 

No single line of code is needed for this.

The easiest type of field is a text field as shown below

```sql
INSERT INTO `core_form_field` (`Field_ID`, `type`, `label`, `fieldkey`, `tab`, `form`, `class`, `url_view`, `url_ist`, `show_widget_left`) VALUES (NULL, 'text', 'Label', 'label', 'module-base', 'module-single', 'col-md-3', '/module/view/##ID##', '', '0'); 
```

## Dynamic Field Types

### Text

### Textarea
```sql
ALTER TABLE `skeleton` ADD `description` TEXT NOT NULL DEFAULT '' AFTER `label`; 
INSERT INTO `core_form_field` (`Field_ID`, `type`, `label`, `fieldkey`, `tab`, `form`, `class`, `url_view`, `url_ist`, `show_widget_left`, `allow_clear`, `readonly`, `tbl_cached_name`, `tbl_class`, `tbl_permission`) VALUES (NULL, 'textarea', 'Description', 'description', 'skeleton-base', 'skeleton-single', 'col-md-12', '', '', '0', '1', '0', '', '', ''); 
```

### Date 

### Datetime
```sql
ALTER TABLE `skeleton` ADD `custom_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `modified_date`; 
INSERT INTO `core_form_field` (`Field_ID`, `type`, `label`, `fieldkey`, `tab`, `form`, `class`, `url_view`, `url_ist`, `show_widget_left`, `allow_clear`, `readonly`, `tbl_cached_name`, `tbl_class`, `tbl_permission`) VALUES (NULL, 'datetime', 'Custom Date', 'custom_date', 'skeleton-base', 'skeleton-single', 'col-md-3', '', '', '0', '1', '0', '', '', ''); 
```
### Time

### Tel

### E-Mail

### Partial

## After SQL installation of field

Then go to selected user, edit, and add the new fields so you can see/edit them
You can also add to index column if you like.

Have fun