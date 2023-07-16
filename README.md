# JermBundle
[![Author](https://img.shields.io/badge/author-@musicjerm-blue.svg)](https://github.com/musicjerm)
[![Source Code](https://img.shields.io/badge/source-musicjerm/jermbundle-blue.svg)](https://github.com/musicjerm/jermbundle)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://github.com/musicjerm/jermbundle/blob/master/LICENSE)
![php 8.0+](https://img.shields.io/badge/php-min%207.2-red.svg)
---

JermBundle is a custom-made data focused CMS, built for use with Symfony PHP projects.  Included
are CRUD methods, data import / export tools, user role management, customizable front end
elements and more!

![App screenshot](/assets/screenshots/app_view.png)
---

## To install
`composer require musicjerm/jermbundle`

Please see requirements, configuration and other information below.

---

## Features
* A configurable CRUD controller that associates with Doctrine Entity mappings. Standard CRUD functionality streamlined into "Actions".
* Easy to use config based Import Controller that can handle large CSV files with hundreds of thousands of lines.
* Column and filter preset management which allows users to customize and save their data filters and column selection.
* Front end templates that include navigation, data tables, filters and pagination.  Intended to provide a clean workflow with an admin inspired look, these can be extended or replaced.
* Notification and subscriber events and methods.
* Base Doctrine entities.
* Easily exportable data with customizable columns for every entity.
* User role control for data visibility and actions.

---

## Requirements
* PHP 7.2 or higher
* Symfony 4 or Symfony 5
* Doctrine
* PHPOffice/PhpSpreadsheet for exporting data to Excel format

### Front end (recommended to utilize all functionality)
* Twig
* Bootstrap 3
* AdminLTE 3
* Fontawesome icon set
* JQuery 3.7.0
* JQuery Datatables
* Jquery Select2
* Bootstrap Datepicker
* Bootstrap Timepicker
* JQuery slimscroll
* JQuery inputmask
* ClipboardJs

---

## Setting up your application
After installation of the bundle, be sure to add: 
`Musicjerm\Bundle\JermBundle\JermBundle::class => ['all' => true],` to your
`/project/config/bundles.php` file.  The route `jerm_bundle_data_index` will require entity
config files to be created and will provide much of the bundle's base layout with navigation, 
data tables, filters and other customizable elements.

### Nav Config
If using the recommended front end libraries, you can take advantage of configurable route and 
user role associated navigation.  This file must be included in `src/JBConfig/nav.yaml`.
You may have up to 3 layers of subnav groups.
Example Below:
```yaml
# /project_dir/src/JBConfig/nav.yaml

Home:
  route: 'homepage'
  role: 'IS_AUTHENTICATED_ANONYMOUSLY'
  icon: 'fa-home'

Invites:
  route: 'jerm_bundle_data_index'
  parameters: {entity: 'invite'}
  role: 'ROLE_USER'
  icon: 'fa-envelope'

Booking:
  Appointments:
    route: 'jerm_bundle_data_index'
    parameters: {entity: 'appointment'}
    role: 'ROLE_BOOKING'
    icon: 'fa-book'
  Calendar:
    route: 'booking_calendar_index'
    role: 'ROLE_BOOKING'
    icon: 'fa-calendar'
```

### Entity Config
As JermBundle is designed around data management, it is necessary to create some configurable YAML
files that associate to a specific Doctrine entity.  These will be stored in your project's
`src/JBConfig/Entity/` directory.  An example will look like the following:
```yaml
# /project_dir/src/JBConfig/Entity/location.yaml

entity: 'App\Entity\Location'
role: 'ROLE_SUPERVISOR'
page_name: 'Locations'
template: 'dataIndex/location.html.twig'

# columns determine which Doctrine properties are displayed and
# each line must include the following:
# title - What the columns should be called, can be anything
# data - this is a getter minus the 'get' from the associated doctrine entity
# sort - passed to the data table for sorting
columns:
    - { title: 'ID', data: 'id', sort: 'l.id' }
    - { title: 'Parent Location', data: 'parentLocation', sort: 'l.parentLocation' }
    - { title: 'Default Job', data: 'defaultJob', sort: 'l.defaultJob' }
    - { title: 'Name', data: 'name', sort: 'l.name' }
    - { title: 'Address', data: 'address', sort: 'l.address' }
    - { title: 'City', data: 'city', sort: 'l.city' }
    - { title: 'State', data: 'state', sort: 'l.state' }
    - { title: 'Zip', data: 'zip', sort: 'l.zip' }
    - { title: 'Market', data: 'market', sort: 'l.market' }
    - { title: 'Contact ID', data: 'userContact.id', sort: 'l.userContact' }
    - { title: 'Contact Name', data: 'userContact.fullName', sort: 'c.userContact' }
    - { title: 'Contact E-mail', data: 'userContact.email', sort: 'c.userContact' }
    - { title: 'Contact Phone', data: 'userContact.phone', sort: 'c.phone' }
    - { title: 'Alternate Contact', data: 'altContactName', sort: 'l.altContactName' }
    - { title: 'Alternate Contact E-mail', data: 'altContactEmail', sort: 'l.altContactEmail' }
    - { title: 'Alternate Contact Phone', data: 'altContactPhone', sort: 'l.altContactPhone' }
    - { title: 'Buyer E-mail', data: 'buyer', sort: 'l.buyer' }
    - { title: 'Active', data: 'isActiveString', sort: 'l.isActive' }
    - { title: 'Default Skin', data: 'defaultSkin', sort: 'l.defaultSkin' }
    - { title: 'Created By', data: 'userCreated', sort: 'l.userCreated' }
    - { title: 'Updated By', data: 'userUpdated', sort: 'l.userUpdated' }
    - { title: 'Created On', data: 'dateCreated', sort: 'l.dateCreated' }
    - { title: 'Updated On', data: 'dateUpdated', sort: 'l.dateUpdated' }

# key is required for item and group actions and the associated value for an object is passed in the route parameters
key: 'id'

# default indexes that are initially available to a user
view: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 17, 18]

# default indexes for columns that might be exported by a user
dump: [1, 2, 3, 4, 7, 8, 10, 11, 12, 13, 14, 15, 16, 17, 18]

# default indexes for tooltips - must be one digit for every available column
# -1 indicates no tooltip for that position
tooltip: [-1, 4, 5, 6, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1]

# default sort key and direction
sortId: 23
sortDir: 'desc'

# filters can be assigned here, these can be text fields, selectors (with entity association),
# radios or checkboxes JermBundle does its best to support nearly any possible filter necessary
# and also supports a custom implementation.  For these to work a StandardQuery method must be
# included in the EntityRepository class.
filters:
    - { name: 'Search', type: 'Text' }
    -
        name: 'Active'
        type: 'Choice'
        array:
            choices: {Yes: true, No: false}
            placeholder: 'Any'

# actions are a form of customizable data manipulation method separated into 3 categories
# head actions will be placed in the admin panel at the top and are typically used for new
# object creation or mass edits.
# item actions are actions on a single entity such as a CRUD update.
# group actions are actions on a selection of items that will be updated together.
# These are keyed by the symfony route name.
actions:
    head:
        jerm_bundle_crud_create:
            role: 'ROLE_ADMIN'
            text: 'New'
            icon: 'fa-plus'
            btn: 'btn-primary'
            params: { entity: 'location' }
            front_load:
                - 'app/js/select2query.js'
    item:
        jerm_bundle_crud_update:
            role: 'ROLE_ADMIN'
            icon: 'fa-pencil'
            text: 'Edit'
            params: { entity: 'location' }
            front_load:
                - 'app/js/select2query.js'
    group:
        jerm_bundle_crud_delete:
            role: 'ROLE_ADMIN'
            text: 'Delete Selected'
            params: { entity: 'location' }

# importer configuration
# supply the namespace of a data transformer within the application and JermBundle will create
# a customized importer action.  Headers must map to Doctrine entity properties.
# unique, required and associated values are taken into consideration when data is handled.
# batch size can be set or a default of 1000 will be used
import:
  transformer: 'App\Transformer\LocationImportTransformer'
  headers:
    - 'name'
    - 'address'
    - 'city'
    - 'state'
    - 'zip'
    - 'market'
  keys: [0, 1]
  batch_size: 3000
```

### Additional info on Actions
JermBundle Actions relate to CRUD methods or can also be custom methods designed for manipulating
data in a specific way.  At a basic level, these are simply routing links to modules, forms or
functions that will present in a modal view.  Nav links are separated into 3 classes that are
configured initially in a JBConfig/Entity YAML file.  See the examples above. 

---

## Column and Filter presets
Preset configuration is included with the bundle and works out of the box once your entity config files
have been set up!  This allows users to define their own presets for which columns are displayed and
exported.

![column presets](/assets/screenshots/column_presets.png)
![filter_presets](/assets/screenshots/filter_presets.png)

![column_preset_config](/assets/screenshots/column_preset_config.png)

---

## Other use cases
JermBundle is capable of serving as a file management platform and has even been used to sync local
storage with Amazon's S3 cloud storage.
![aws_s3_example](/assets/screenshots/aws_s3_example.png)

---

## TODO:
* Refine dependency version requirements and update this readme.
* Update base Doctrine entities to use PHP 8 annotations.
* Clean up controllers and commenting throughout.
* Update Bootstrap and AdminLTE to version 4, or possibly implement a custom front end template.
* Add additional functionality to CRUD controller, specifically implement the use of data transformers instead of relying on strict entity mapping.

---

## Projects using this bundle
* www.bizzie.me
* www.antunezcoffee.com