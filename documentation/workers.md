---
id: workers
title: Writing a Worker
sidebar_label: Hello World
---

## Adding CSS to a page
You can add a CSS file to a page by passing it’s path using `Core::AddCSS()`, for example `Core::AddCSS(‘/path/to/file.css’)`

By default, `Core::AddCSS()` reads files from the `docroot/css` folder. CSS files are added to the page in the order they are added using this call in a worker.

## Adding Javascript to a page
You can add a Javascript file to a page by passing it’s path using `Core::AddJavascript()`. This function has a second optional parameter to select the place on the page the file should be added, either within the `<head>` section, or at the end of the page just before the closing `</body>` tag.

```php
Core::AddJavascript(‘/path/to/file.js’[, ‘footer’]);
```

By default, `Core::AddJavascript()` looks for Javascript files in the `docroot/js` folder. Javascript files are added to the page in the order they are added using this call in a worker.

## Setting the template.
`$template = "{templatename}.tpl"`

## Adding data to a template for display
`Core::Assign()`

## Displaying templates
### Display
### Fetch