# Fortify on Demand scan status badges

A simple PHP app that generates shields.io badges for Fortify security scan releases that look like this:

![Fortify badge](https://img.shields.io/badge/fortify-7%20critical,%201%20high-red.svg)

## Requirements

* A Micro Focus [Fortify on Demand](https://software.microfocus.com/en-us/software/application-security) account.
* API client ID and client secret.

## Usage

You can run it from cli with environment variables:

```sh
$ clientId="123456" clientSecret="supersecret" release="1.2.0" php index.php
Redirecting to https://img.shields.io/badge/fortify-3%20critical,%202%20high-red.svg
```

Or serve it and pass in the version as a parameter:

```
$ curl -vs localhost/fortify/?r=1.2.0 2>&1 | grep Location
< Location: https://img.shields.io/badge/fortify-7%20critical,%201%20high-red.svg
```

If you do this, you'll still need to inject the credentials as environment variables. If you're using apache, just add `SetEnv clientId "blah"` to your vhost config.
