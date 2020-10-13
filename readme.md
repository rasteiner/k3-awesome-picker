![Animation showing the plugin in action](showme.gif)

Show and pick a free Font Awesome icon. 

# Install
## Download Zip file

Copy plugin folder into `site/plugins`

## Composer
Run `composer require rasteiner/k3-awesome-picker`.

# Usage
Add an `icon` field to your blueprint. You can also choose to filter the available icons by declaring the wanted styles: `brands` (Logos), `solid` (filled icons) and `regular` (outlined icons).

In your template you will get the class names for the chosen icon. 

## Example

Blueprint:

```yaml
fields:
  myicon:
    label: My Icon
    type: icon
    styles: 
      - brands 
      - solid
```

Template:

```php
<script src="https://kit.fontawesome.com/<yourkit>.js" crossorigin="anonymous"></script>

This is the chosen icon: <i class="<?= $page->icon() ?>"></i>
```
