# Introduction

The Croissant framework is designed to be very simple to use and makes use of a number of non-standard patterns. Calling Croissant a framework is actually a bit of a misnomer, as in reality it is a collection of simple functions available within a classic OOP arrangement, allowing for websites to be built quickly and easily.

# Basics
Each of these elements will also be covered in more detail elsewhere in the documentation.

## File-system Routing
Croissant uses file-system routing, and every `.php` file in the `workers` folder automatically becomes a route.

[Read the documentation &raquo;](filesystem-routing.md)

## Composer-based Routing
Croissant also supports composer-based router providers.

[Read the documentation &raquo;](composer-routing.md)

# Accessing framework methods and function
Croissant uses a static Singleton approach to it's internal classes, so no special steps are needed to access any of the libraries or methods within the Croissant framework, as the `index.php` preamble takes care of loading everything.

All classes and methods within the framework can be called immediately with no instantiation needed - this is taken care of the first time a class method is called.

For example, to set a variable into the `Session`, all that is needed is to call `Session::SetVariable($key, $data)` directly, there is no need to `use` the class by name in the route or method.
