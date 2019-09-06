webvaloa-core
========

Webvaloa core application.

## Installation

This package is part of Webvaloa platform.

```json
{
    "require": {
        "sundflux/webvaloa-core": "^3.0.0"
    }
}
```

## Requirements

- PHP 7.2.19 or newer.

## Features

- 

## Copyright and license

Copyright (C) 2019 Tarmo Alexander Sundström & contributors.

Libvaloa is licensed under the MIT License - see the LICENSE file for details.

## Contact

- http://www.webvaloa.com/

## Change Log
All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](http://semver.org/).

Changes are grouped by added, fixed or changed feature.

### [3.0.10] - 2019-09-06
- Catch and ignore exceptions in component.

### [3.0.9] - 2019-09-05
- Copy .env.dist as .env if it doesn't exist.

### [3.0.8] - 2019-09-03
- Move /.env to /config/.env.

### [3.0.7] - 2019-09-02
- Refactor filesystem and path out of Helpers.

### [3.0.6] - 2019-08-26
- Refactoring to get rid of Webvaloa::config.
- Add configuration as part of core package.

### [3.0.5] - 2019-08-25
- Refactor autoloading again, include dotenv loading.

### [3.0.4] - 2019-08-25
- Refactor autoloading.

### [3.0.3] - 2019-08-24
- Hotfix, refactoring and CS style fixer.

### [3.0.2] - 2019-08-24
- Hotfix for bootstrapping.

### [3.0.1] - 2019-08-24
- Hotfix for autoloader names.

### [3.0.0] - 2019-08-24
- First version separated from Webvaloa main repository as a package. 
- Move setting default controllers from webvaloa index.php to FrontController.
