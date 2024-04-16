# Geocoding API

A basic PHP client for working with [Google's Geocoding API](https://developers.google.com/maps/documentation/geocoding).

> [!NOTE]
> Only PHP7+ and the cURL extension are required; in a production environment there are no other dependencies

## Demo

If you'd like to see the library in action then you can execute `tests/geocode.php`, which runs a bunch of tests on the various `Geocode` methods.

> [!IMPORTANT]
> Before running `tests/geocode.php`, you must add a project's API key to `tests/.config.php`

## Return Value

The geocoding methods, including `Geocode::byAddress()` and `Geocode::byPostcode()`, return an array on success, or throw an exception otherwise.  The structure of the array is based on the [Schema.org *GeoCoordinates* type](https://schema.org/GeoCoordinates) and looks like the following.

```php
Array
(
    [address] => Array
        (
            [streetAddress] => 25 Old Gardens Close
            [addressLocality] => Tunbridge Wells
            [addressRegion] => Kent
            [postalCode] => TN2 5ND
            [addressCountry] => GB
        )

    [latitude] => 51.1172303
    [longitude] => 0.2635245
)
```

## Forward Geocoding

Examples of geocoding an address using the convenience method:

```php
$geocode = new Geocode('<your-api-key>');

// Without using region biasing
$geoCoordinates = $geocode->byAddress('25 Old Gardens Close Tunbridge Wells TN2 5ND');

// `byAddress()` accepts an *ISO 3166-1 alpha-2 code* to apply bias by a specific country
$geoCoordinates = $geocode->byAddress('25 Old Gardens Close Tunbridge Wells TN2 5ND', 'GB');
```

An example of geocoding a postcode using the convenience method:

```php
$geocode = new Geocode('<your-api-key>');

// `byPostcode()` *requires* a country; this is to help reduce ambiguity and, therefore, improve results
$geoCoordinates = $geocode->byPostcode('07001', 'ES');
  // => Palma, Majorca
```

## Reverse Geocoding

An example of reverse-geocoding using the convenience method:

```php
$geocode = new Geocode('<your-api-key>');
$geoCoordinates = $geocode->byLatLong('50.88916732998306', '-0.5768395884825535');
  // => Arundel, West Sussex, GB
```
