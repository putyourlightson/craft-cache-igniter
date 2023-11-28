# Testing

## Usage

To run Pest tests, first install [Craft Pest](https://craft-pest.com/) core by running `composer require markhuot/craft-pest-core:^2.0.0-rc2 --dev` and then run the
following command from the root of your project.

```shell
php craft pest -- --configuration=vendor/putyourlightson/craft-cache-igniter/tests/pest/phpunit.xml --test-directory=vendor/putyourlightson/craft-cache-igniter/tests/pest
```

Or to run a specific test.

```shell
php craft pest -- --configuration=vendor/putyourlightson/craft-cache-igniter/tests/pest/phpunit.xml --test-directory=vendor/putyourlightson/craft-cache-igniter/tests/pest --filter=RefreshTest
```
