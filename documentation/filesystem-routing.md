
# File-system Routing

At a basic level, the top level "route" matches the first part of a URI passed to the framework. This is implemented within the `/customers/[yourapp]/workers` folder as a PHP file. Any subsequent parts of the URI are made available to the matching `worker.php` file in the `$args` array.

## Example
To implement the "route" `www.myserver.com/homepage`, create a PHP file in `/customers/[yourapp]/workers` called `homepage.php`.

This file will contain all the business logic for displaying your homepage content, including any necessary logic to process any parameters passed to the worker.

## Execution scope
Croissant does not support globals by design, although globals may be used if desired. In general though globals are not needed, as the base "worker" implemented to handle all routing executes within the scope of `index.php`.

The basic execution path is:

* `index.php` preamble
* pass execution to selected `worker.php` (if it exists)
* `index.php` closing (unless the worker stops script execution)