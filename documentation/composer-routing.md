# Third-party routing through Composer

## Example
Using [https://github.com/bramus/router](https://github.com/bramus/router)

* Add `"bramus/router": "~1.4"` to `composer.json` in the `customers\croissant` folder.

```javascript
{
	"name": "Croissant/Croissant",
	"description": "The Croissant Framework.",
	"keywords": ["framework", "croissant"],
	"license": "private",
	"require": {
		...
		"bramus/router": "~1.4",
		...
		"smarty/smarty": "~3.1"
	},
	"config": {
	},
	"minimum-stability": "stable"
}
```

* There is a replacement `index.php` in the `docroot` folder called `router-index.php`. Remove the default `index.php` and rename `router-index.php` to `index.php`.
* Add your routes to this `index.php` file as per the documentation.
