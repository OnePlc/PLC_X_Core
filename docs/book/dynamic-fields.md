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

### Date 

### Datetime

### Time

### Tel

### E-Mail

### Partial