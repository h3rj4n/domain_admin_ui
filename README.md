# Domain Admin UI


This module allows users to switch domain to manage content and save configuration per domain.

## Dependencies

- Domain Config


## Setup

Add `'domain_admin_ui_selected_domain'` to `required_cache_contexts` in services.yml.


## Install

This module can be added to your Drupal project using composer. You'll have to add this git repo as a custom repository 
to your composer file:

```
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/h3rj4n/domain_admin_ui.git"
        }
    ]
```

This module is not defined as a default Drupal module so you can or have to define the install location. For example, 
all the modules that I pull from a custom repository will end up in the `/web/modules/composer` directory. You have to 
add an installer path to the composer file.

```
    "extra": {
        "installer-paths": {
            
            "web/modules/composer/{$name}": ["type:drupal-custom-module"],
            
        }
    }
```

Install can now be done with:

```
composer require drupal/domain_admin_ui
```

## TODO

* Add tests
* Reverting of Domain specific config files to match the main config, not sure if the config file will be deleted. 