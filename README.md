# neuFramework
## About

This is a bit of an unusual framework, as it was one that I initially developed 15 years ago with the intent to eventually release publicly and never got around to it, simply using each iteration and version on a variety of personal and specialty sites.

This is the 5th (v5) iteration of the framework, and one finally I've found time to release both for original intended purposes of the framework and as a general preview to my coding abilities.

v5 of neuFramework is built with a minimum of PHP 8.1 in mind, utilizing its features and capabilities deeply.

## Intended Use

I originally had built this framework both as a way of constantly improving my own skills with the evolving changes of PHP while also providing a framework that would be easy to jump into PHP for those wanting to learn it by offering a heavily commented and guided framework.

Largely I'm finally publishing it just so I have a public model of my code style for people to look at.

While this is still sort of in a fair bit of development, it is functionally usable for the most part and easily adapted to needs.

## Loading Styles

### Dynamic Loader

While my older versions of this framework had a custom template class, it had admittedly become a bit bloated and 'reinventing the wheel' kind of, so for v5 I decided to make use of Twig, as it's a very popular and common templating library utilized in a large variety of PHP software projects.

And because of its features and abilities it made it easier to implement something I had wanted to do for a while which was to create a very simple and generic routing loader as done in loader.php.

For example if you set up a rewrite (as shown in below httpd config examples) to point something like /loader/example at the loader.php script it would then look for an example.twig inside the /loader directory in the main twig template directory.  This is useful for templates that are more static and less dynamic you don't need to create a bunch of individual .php scripts for them.

In short this is just a very simplistic template router of sorts.

### Static Loader

However, say perhaps you have a template that may make use of dynamically loaded content pretty actively, while there are still ways you could do it within the dynamic loader you may find it easier to make a dedicated script to contain all that in, an example of just a basic dedicated script is index.php

## Configuring

neuFramework has a few methods of generating a config array internally, the default that this uses is .env style, however you can also use httpd environment variables, a .json config file or just hard code them directly into /app/core/config.php (not recommended) or your own class/script loader making use of config::setSetting()

### .env Method

This method uses the normal DotEnv syntax model and can be loaded as an example;
`config::initialize('dotenv', ['directory' => $_SERVER['DOCUMENT_ROOT'] . '/app', 'filename' => '.env']);`

Options available for this method are;
* `directory`: The directory path to where .env will be located at.  Defaults as /app directory.
* `filename`: What the filename would be.  Defaults as .env.
* `required`: An array of key names that must be loaded otherwise fail framework load.  No default.
* `lowercase_keys`: If set to true it will fully lowercase all key names.  Defaults true.
* `multidimensional`: Set to true, converts key names like FOO.BAR.ARR into a multidimensional array.  Defaults false.

### JSON method

This method simply uses PHP's built in json_decode() to format a valid JSON formatted file into an associative array.
`config::initialize('json', ['directory' => $_SERVER['DOCUMENT_ROOT'] . '/app', 'filename' => 'config.json']);`

Options available for this method are;
* `directory`: The directory path to where .env will be located at.  Defaults as /app directory.
* `filename`: What the filename would be.  Defaults as config.json.
* `required`: An array of key names that must be loaded otherwise fail framework load.  No default.
* `lowercase_keys`: If set to true it will fully lowercase all key names.  Defaults true.

### ENV method

This method pulls from the environment variables defined in the httpd, this is notably the most inefficient model. 
`config::initialize('env');`

Options available for this method are;
* `required`: An array of key names that must be loaded otherwise fail framework load.  No default.
* `lowercase_keys`: If set to true it will fully lowercase all key names.  Defaults true.

## Webserver Configuration Info

### lighttpd
These are the primary settings/options that need to go within your vhost block

```
    $HTTP["url"] =~ "^/app/" {
        url.access-deny = ("")
    }
 
    url.rewrite-if-not-file = (
    	"^/([a-zA-Z0-9-]+)/([a-zA-Z0-9-]+)/?$" => "/loader.php?section=$1&page=$2",
    	"^/([a-zA-Z0-9-]+)/?$" => "/loader.php?page=$1",
    )
```