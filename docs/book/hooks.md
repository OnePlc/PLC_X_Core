# hooks

From 1.0.6 onePlace Core supports hooks for different events. 

You can use it to customize core functions to your needs without 
changing core modules or code - which gives you more update compatibility for your
custom oneplace app.

## Registering a hook

To add a hook simply register it to the certain action you want.

We advice you to add your hooks in Module.php of your module, but
you can use the register function in every controller just make
sure you have an import for `Application\Controller\CoreEntityController`

```php
CoreEntityController::addHook('hook-name',(object)['sFunction'=>'your Function','oItem'=>new PluginController()]);
```

## Available hooks

Here you have a list of already existing hooks in onePlace Core

### skeleton-add-before

no parameters

### skeleton-add-before-save
available parameters: 
```
oItem - Skeleton Model with Data
aRawData - Raw Form Data
```
prototype for function 
```
function yourHook($oItem,array $aRawData) {}
```

### skeleton-add-after-save
available parameters: 
```
oItem - Skeleton Model with Data
aRawData - Raw Form Data
bSave - True if save function succeeded so far
```
prototype for function 
```
function yourHook($oItem,array $aRawData,$bSave) {}
```

### skeleton-edit-before
available parameters: 
```
oItem - Skeleton Model with Data
```
prototype for function 
```
function yourHook($oItem) {}
```

### skeleton-edit-before-save
available parameters: 
```
oItem - Skeleton Model with current Data
oNewItem - Skeleton Model with new Data
aRawData - Raw Form Data
```
prototype for function 
```
function yourHook($oItem,$oNewItem,array $aRawData) {}
```

### skeleton-edit-after-save
available parameters: 
```
oItem - Skeleton Model with Data
aRawData - Raw Form Data
bSave - True if save function succeeded so far
```
prototype for function 
```
function yourHook($oItem,array $aRawData,$bSave) {}
```
