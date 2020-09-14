---
id: reserved
title: Reserved Variables
---

There are a number of reserved variable names that are used by the framework to set or display data.

### $function
The `$function` variable holds the value of the first URI component. This defaults to ‘homepage’ if the website root is requested.

### $template
The `$template` variable holds the path and filename of the display to be displayed. The path element should be from below the markup type. For example:
`$template = "path/to/template.tpl"`
will cause
`/var/www/customers/myserver/templates/html/path/to/template.tpl`
to be displayed (assuming the browser supports HTML) at the end of execution by the final `Core::Display()` call.

The variable passed to `Core::Fetch($template)` follows the same convention.

### $page_title
The value of `$page_title` will be exported to the surrounding page template and be used in the `<title>` tag.

### $page_meta
The value of `$page_meta` will be exported to the surrounding page template and be used in the `<meta keywords="">` tag.

### $args
`$args` is an array containing all elements of the URL following the initial URI component. This array does not contain any `$_GET`, `$_POST`, `$_SERVER` or `$_REQUEST` variables.

For example:
`www.myserver.com/controller/these/are/arguments`
will result in `$args` being:
`$args = array('these', 'are', 'arguments');`