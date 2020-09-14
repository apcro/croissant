# Introduction

The Croissant framework is designed to be very simple to use and makes use of a number of non-standard patterns. Calling Croissant a framework is actually a bit of a misnomer, as in reality it is a collection of simple functions available within a classic OOP arrangement, allowing for websites to be built quickly and easily.

## Basics
Each of these elements will also be covered in more detail elsewhere in the documentation.

## Routing
Croissant does not use a routing module, and routes do not need to be predetermined. Instead, routes are implemented in PHP files that are included within the scope of `index.php` and have access to all variables available to `index.php`.

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

## Accessing framework methods and function
Croissant uses a static Singleton approach to it's internal classes, so no special steps are needed to access any of the libraries or methods within the Croissant framework, as the `index.php` preamble takes care of loading everything.

All classes and methods within the framework can be called immediately with no instantiation needed - this is taken care of the first time a class method is called.

For example, to set a variable into the `Session`, all that is needed is to call `Session::SetVariable($key, $data)` directly.