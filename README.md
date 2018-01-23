# Fortify on Demand scan status badges

A simple PHP app that generates shields.io badges for Fortify security scan
releases that look like this:

![Fortify badge](https://img.shields.io/badge/fortify-7%20critical,%201%20high-red.svg)

## Requirements

* A Micro Focus [Fortify on Demand](https://software.microfocus.com/en-us/software/application-security)
account.
* API client ID and client secret.

## Usage

You can run it from cli with environment variables:

```sh
$ clientId="123456" clientSecret="supersecret" release="1.2.0" php index.php
Redirecting to https://img.shields.io/badge/fortify-3%20critical,%202%20high-red.svg
```

Or serve it and pass in the version as a parameter:

```
$ curl -vs localhost/fortify/?release=1.2.0 2>&1 | grep Location
< Location: https://img.shields.io/badge/fortify-7%20critical,%201%20high-red.svg
```

If you do this, you'll still need to inject the credentials as environment
variables. If you're using apache, just add `SetEnv clientId "blah"` to your
vhost config.

Or you can run it from the docker image:

```
$ docker run -p 80:80 -e "clientId=123456" -e "clientSecret=supersecret" caseyfw/fortify
$ curl -vs localhost/?release=1.2.0 2>&1 | grep Location
< Location: https://img.shields.io/badge/fortify-7%20critical,%201%20high-red.svg
```

## Credentials as env vars or read from file

If you don't like passing in your credentials as env vars, you can add 'File' to
the parameter name and the values will be read from the desired path. This is
handy for containerised deployments that support secret injection. For example:

```
$ docker run -p 80:80 -e "clientIdFile=/etc/fortify/clientId" -e "clientSecret=/etc/fortify/clientSecret" caseyfw/fortify
$ curl -vs localhost/?release=1.2.0 2>&1 | grep Location
< Location: https://img.shields.io/badge/fortify-7%20critical,%201%20high-red.svg
```
