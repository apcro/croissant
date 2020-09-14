---
id: quickstart
title: Quickstart
---

## Quickstart
This document will guide you through Croissant's installation process.


## Introduction
The Croissant framework makes use of a distributed data service approach, so every installation of Croissant requires two components, the Website and the Dataserver.

Both of these components should be configured as an independent website.

Typically, the Croissant Website code is configured using a publicly-accessible URL (for example, www.croissant-framework.co.uk), and the Dataserver is configured using a local-only name by adding a configuration to /etc/hosts.

### Example Apache Configuration - myserver.conf
```
<virtualhost *:80>
 ServerAdmin webmaster@localhost
 ServerName www.myserver.co.uk
 DocumentRoot /var/www/Croissant/customers/myserver/docroot
 ErrorLog /var/log/apache2/croissant-error.log
 LogLevel warn
 CustomLog /var/log/apache2/myserver-access.log
 combined ServerSignature On 
</virtualhost>
<virtualhost *:80>
 ServerAdmin webmaster@localhost
 ServerName myserver.ds
 DocumentRoot /var/www/Croissant-Dataserver
 ErrorLog /var/log/apache2/myserver.ds-error.log
 LogLevel warn
 CustomLog /var/log/apache2/myserver.ds-access.log
 combined ServerSignature On 
</virtualhost>
```
### Example hosts file configuration
127.0.0.1 myserver.ds

## System requirements
Make sure your server meets the following requirements.

Apache 2.2+ or nginx
MySQL Server 5.1+
PHP Version 5.3+

## Installation
First of all, extract the downloaded archive and copy the contained folders and files to your webserver directory.

### 1. Set the permissions
Before you start with the installation process, ensure that the folders you've just uploaded have the right permission settings. We recommend CHMOD 755. Croissant needs to be able to write to the following files and directories and each of their subdirectories:

File / Folder | Description
--------------|------------
`/customers/[yourapp]/docroot/images` | Stores upload images.

The actual permission settings depend on the user that the webserver is running with and the owner of the folders. If your webserver has problems with the CHMOD 755, you can also try 775 and lastly 777 in this order.| 

You should always avoid 777 permissions on your production webserver, since this will allow anyone who has access to the machine to edit the files.

### 2. Create the database
Next thing we need to do is create an empty database for Croissant to work with using a tool like phpMyAdmin

### 3. Import Croissant Schema
Import the SQL file from `Croissant-Dataserver/trunk/croissant_schema.sql`

### 4. Setup config file
Step 1 - Website config file
Copy code from `Croissant-Web/customers/[yourapp]/configuration/sample.configuration.php` to `Croissant-Web/customers/[yourapp]/configuration/local.configuration.php`

Edit `local.configuration.php` to match your configuration

Step 2 - Dataserver config file
Copy code from `Croissant-Dataserver/sample.database.configuration.php` to `Croissant-Dataserver/database.configuration.php`

Edit `database.configuration.php` to match your configuration