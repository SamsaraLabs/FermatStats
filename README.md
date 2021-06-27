# Fermat Stats

[![Build Status](https://scrutinizer-ci.com/g/SamsaraLabs/FermatStats/badges/build.png?b=master)](https://scrutinizer-ci.com/g/SamsaraLabs/FermatStats/build-status/master) [![Code Coverage](https://scrutinizer-ci.com/g/SamsaraLabs/FermatStats/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/SamsaraLabs/FermatStats?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/SamsaraLabs/FermatStats/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/SamsaraLabs/FermatStats/?branch=master) [![Latest Stable Version](https://poser.pugx.org/samsara/fermat-stats/v/stable)](https://packagist.org/packages/samsara/fermat-stats) [![Total Downloads](https://poser.pugx.org/samsara/fermat-stats/downloads)](https://packagist.org/packages/samsara/fermat-stats) [![License](https://poser.pugx.org/samsara/fermat-stats/license)](https://packagist.org/packages/samsara/fermat-stats)

**This project is unit tested against 8.0, and merges are not accepted unless the tests pass.**

This is a module for the [Fermat](https://github.com/JordanRL/Fermat) math library.

## Installation

To install, simply require the package using composer:

    composer require samsara/fermat-stats "1.*"
    
Or include it in your `composer.json` file:

```json
{
    "require": {
        "samsara/fermat-stats": "1.*"
    }
}
```

The project namespace is `Samsara\Fermat\*`. You can view the project on [Packagist](https://packagist.org/packages/samsara/fermat).

## Documentation

For full documentation of all Fermat features, including its modules, please see the [full documentation](https://jordanrl.github.io/Fermat/).

## Contributing

Please ensure that pull requests meet the following guidelines:

- New files created in the pull request must have a corresponding unit test file, or must be covered within an existing test file.
- Your merge may not drop the project's test coverage below 70%.
- Your merge may not drop the project's test coverage by MORE than 5%.
- Your merge must pass Travis-CI build tests for PHP 8.X.

For more information, please see the section on [Contributing](CONTRIBUTING.md)
