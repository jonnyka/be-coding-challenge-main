# L1 Challenge Devbox

## Summary

- Dockerfile & Docker-compose setup with PHP8.1 and MySQL
- Symfony 5.4 installation with a /healthz endpoint
- After the image is started the app will run on port 9002 on localhost. You can try the existing
  endpoint: http://localhost:9002/healthz
- The default database is called `database` and the username and password are `root` and `root`
  respectively
- Makefile with some basic commands

## Installation

```
  make run && make install
```

## Run commands inside the container

```
  make enter
```

## Run tests

```
  make test
```

## Implementation

### Parser

- `src\Command\ParseLogsCommand.php`:
  - just a wrapper for the `parse()` function of the `LogService`
  - run it with
```
php bin/console app:parse-logs
```
- `src\Service\LogService.php` -> `parse()`:
  - parses the log file `public\logs.txt`
  - saves the lines in batches of 10
  - saves the current line, so if it's interrupted, it starts from where we left off (using `SplFileObject`)
  - it parses the correct parts with simple regex, and saves the lines into the database (entity defined in `src\Entity\Log.php`)

### Counter

- `src\Controller\LogController.php`: just a wrapper for the `getLogs()` function of the `LogService`
- `src\Service\LogService.php` -> `getLogs()`:
  - caches all the requests, using the file system cache for now (redis or memcached would be better)
  - builds a query with the `$params` query parameters
  - runs the query, returns the number of rows

### Tests

- `src\DataFixtures\AppFixtures.php`: sets up some mock data in the test database
  - load the data with:
```
php bin/console doctrine:fixtures:load --env=test
```
- `tests\Controller\LogControllerTest.php`: just a simple test to see if the API route is live, and if it returns a 200 status code, and a json response without any query parameters
- `tests\Service\LogServiceTest.php`: runs the counter with various parameters:
  - `[]`: empty array, meaning the count should be all the lines, which is `10` in the test db
  - `['serviceNames' => 'USER-SERVICE']`: get all `USER-SERVICE` type log entries, which is `6` in the test db
  - `['serviceNames' => 'USER-SERVICEE']`: typo in the serviceName, should give us `0`
  - `['serviceNames' => 'INVOICE-SERVICE', 'statusCode' => 201]`: get all `INVOICE-SERVICE` entries with the status code `201`, should be `3`
  - `['serviceNames' => 'INVOICE-SERVICE,USER-SERVICE', 'statusCode' => 400]`: similar to the one above, should be `3`
  - `['serviceNames' => 'NONEXISTANT-SERVICE,USER-SERVICE,A GIRAFFE']`: should ignore the non-existant service names, and give us the `USER-SERVICE` entries, which is `6`
  - `['startDate' => '2021-08-17 09:22']`: should give us `5`
  - `'gibberish nonarray'`: the query parameter is not an array, should give us all the lines without filtering, so `10`
  - `['startDate' => 'IamNotADate']`: because we can't parse the `startDate` parameter, it should give us all the lines, so `10` again
  - `['strange key' => 42, 'endDate' => '2021-08-17 09:22']`: should ignore the `strange key`, that's not in the API docs, should give us `5` with the `endDate` parameter
