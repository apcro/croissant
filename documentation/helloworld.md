# Hello World

Writing <em>Hello World</em> in the Croissant framework is very simple.

By default, since the framework loads the `homepage.php` worker from the `workers` folder, the simplest <em>Hello World</em> is as follows:

```php
<?php
namespace Croissant;
echo 'Hello World';
die();
```

This will cause the framework to echo "Hello World" and then halt execution.

## Hello World with style
To create a simple <em>Hello World</em> example using a template, create a new file in the `templates\html` folder called `helloworld.tpl` (if it doesn't already exist). 

Add the following markup.

```html
<h1>Hello World</h1>
```

In your `homepage.php` file, use:

```php
<?php
namespace Croissant;
$template = 'helloworld.tpl'
```
This will display the content of the `helloworld.tpl` template using default browser styling.

## Hello World with <em>more</em> style
To add custom styling, create a new css file in the `docroot\css` folder called `helloworld.css`, and add the following line to `homepage.php`

```php
<?php
namespace Croissant;
$template = 'helloworld.tpl'
Core::AddCSS('helloworld.css');
```

In your new `homepage.css` file add whatever style you want for your `<h1>` tag.

```css
h1 {
	font-size: 2em;
	color: blue;
}
```