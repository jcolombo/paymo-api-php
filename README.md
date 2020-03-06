#PaymoApp API for PHP
A Robust PHP implementation of the Paymo App API

PaymoApp is a powerful project management platform with an API that allows programmers to completely operate their account through a RESTful set of endpoints. This package creates a programmer friendly toolkit to simplify all interactions with the application.

The published REST api documentation can be found here:

https://github.com/paymoapp/api

***
 NOT RELEASED YET (@dev package only, changes daily)
 
 This Package is completely unstable and in active initial development as of March 1st, 2020.
 The planned 1.0.0 release is expected by April 2020. And is being built and supported by the development team at 360 PSG, Inc. released under the MIT License for all to use. Give us a little time to get it stable and finished.
***

## Package Features

- Object-Oriented Fetch, Create, Update and Delete classes for ALL Paymo objects
- Strict Type checking to insure proper data types are being used for each object type
- Select deep level relationships with a single call
- Easily upload and attach local image files for objects that have images (client logos, users, etc)
- Ability to extend and overload package objects with your own extensions
- Local file caching to avoid unneeded repeat calls to the API (helping avoid rate limits)
- Deep logging tools for debugging during integration and development

## Installation

The paymo-api-php package works best when installed directly from packagist using composer

```

composer require jcolombo/paymo-api-php

```

## Getting Started

```php

use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;

// Start a connection with the Paymo API
$paymo = Paymo::connect('YOUR_API_KEY');

// Load a specific project by its ID
$project = new Project();
$project -> fetch(12345);
// $project is now fully populated with the data from the matching Paymo project

// Load a project with all the client details attached to it
$project -> fetch(12345, ['client']);

// Load a list of all projects the API key can see
$projects = Project::list();

```
