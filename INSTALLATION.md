# Croissant PHP Framework

## Base installation
For more detail, see the [documentation website](http://www.croissant-framework.co.uk/)

- Check out the repository to a folder on your computer.
- Rename the folder `/customers/croissant/` to whatever name you want. This is where all your website code will go.
- Rename the file `/customers/croissant/docroot/croissant.php` to match the name of your customer folder. For example, if you named your customer folder `myserver`, rename this file to `myserver.php`

## Server configuration
- Configure your webserver's virtual host to point to `{checkoutfolder}/customers/{customername}/docroot`
- Create a file called `local.configuration.php` inside the `configuration` folder. See `sample.local.configuration.php` for examples.
- See the following section for details on setting up the Dataserver component.

## Dataserver configuration
Croissant will not  operate properly without a configured [Dataserver](https://github.com/apcro/croissant-dataserver). This can be set up locally or on a remote server. See the [Dataserver documentation](https://github.com/apcro/croissant-dataserver/INSTALLATION.md) for more information.

Once you have configured your Dataserver, add the appropriate URI to `local.configuration.php`.

# Third Party Libraries

Croissant makes use of a number of third party libraries to operate properly. You will need to ad these to your local repository manually.

## Required

### The Smarty Template engine
Download the latest release of Smarty from [http://www.smarty.net/](http://www.smarty.net/)

Unpack the archive into the folder `/libraries/external` and edit the `customers/croissant/configuration/configuration.php` file to set the selected version of Smarty.

### Mobile Detect
Download the latest release of the [MobileDetect library](http://mobiledetect.net). Plase the file `Mobile_Detect.php` into the folder `libraries/external` and rename it to `MobileDetect.class.php`

## Optional

### Solr
If you want to use the Solr search engine, you will need the [Solr PHP client](https://code.google.com/p/solr-php-client/). Download the latest client software and upack the folder `SolrPhpClient/Apache/Solr/` to `libraries/external/Solr`

