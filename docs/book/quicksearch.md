# Quicksearch

onePlace ships with a global quick search. each module with a single-form
gets added to quicksearch automatically.

there are several options to customize quicksearch to your needs

## Options

### quicksearch-skeleton-customattribute

The quicksearch customattribute lets you add an custom attribute to quicksearch to search
with, default is "label" - your extra field gets added as a 2nd query (or)

Just add the setting for your module.
Replace `skeleton` with your module and `custom_attribute` with your attribute (db column name)

```sql
INSERT INTO `settings` (`settings_key`, `settings_value`) VALUES
('quicksearch-skeleton-customattribute', 'custom_attribute');
```

it can also be an object of you have more than 1 attribute or you need options
`fields` array with fields e.G 

```json
{"fields":["custom_attribute","another_attribute"]}
```

`seperator` string with seperator between fields

```json
{"seperator": " - "}
```

`format` string with custom format type (date/datetime) default is text

```json
{"seperator": " - "}
```

example
```sql
INSERT INTO `settings` (`settings_key`, `settings_value`) VALUES
('quicksearch-skeleton-customresultattribute', '{\"fields\":[\"custom_date\"],\"seperator\":\" - \",\"format\":\"datetime\"}');
```

### quicksearch-skeleton-likeall

The quicksearch likeall lets you change the query for your module.
default search is "PATTERN%" (so the result MUST begin with PATTERN)
with this option enabled, it changes to "%PATTERN%" (so result must contain PATTERN no matter where)
Note that this options slows the query function down so use it only when necessary

Just add the setting for your module.
Replace `skeleton` with your module

```sql
INSERT INTO `settings` (`settings_key`, `settings_value`) VALUES
('quicksearch-skeleton-likeall', '1');
```

### quicksearch-skeleton-customresultattribute

The quicksearch customresultattribute lets you add a custom attribute to the displayed results
default is "LABEL", with this option enabled result will be shown as "LABEL (CUSTOM)"

Just add the setting for your module.
Replace `skeleton` with your module and `custom_attribute` with your attribute (db column name)

```sql
INSERT INTO `settings` (`settings_key`, `settings_value`) VALUES
('quicksearch-skeleton-customresultattribute', 'custom_attribute');
```

### quicksearch-skeleton-customlabel

The quicksearch customlabel lets you change the default attribute
for quicksearch for your module.

Just add the setting for your module.
Replace `skeleton` with your module and `custom_attribute` with your attribute (db column name)

```sql
INSERT INTO `settings` (`settings_key`, `settings_value`) VALUES
('quicksearch-skeleton-customlabel', 'custom_attribute');
```