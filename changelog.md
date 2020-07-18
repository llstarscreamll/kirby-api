# Release notes

## [v0.2.2 (2020-07-06)](https://github.com/llstarscreamll/kirby-api/compare/v0.2.1..v0.2.2)

### Fixed

-   Setup Laravel Horizon configuration options to best performance
-   Remove unended git checkout from deploy command

## [v0.2.1 (2020-07-06)](https://github.com/llstarscreamll/kirby-api/compare/v0.2..v0.2.1)

### Fixed

-   Novelties: error solving novelties from time clock log and past novelties

## [v0.2 (2020-06-05)](https://github.com/llstarscreamll/kirby-api/compare/v0.1.3..v0.2)

### Added

-   Novelties: novelties can be searched by novelty types, employees, cost centers and start date range
-   Novelties: new novelty types CRUD endpoints
-   Novelties: searching by range date is applied to novelty end and time clock log checkout

### Changed

-   Time clock: check in endpoint doesn't show unlocalized dates in error messages

## [v0.1.3 (2020-06-05)](https://github.com/llstarscreamll/kirby-api/compare/v0.1.1..v0.1.3)

### Fixes

-   Time lock logs with less than 5 minutes and without checkout are now ignored to calculate novelties

## [v0.1.2 (2020-06-05)](https://github.com/llstarscreamll/kirby-api/compare/v0.1.1..v0.1.2)

### Fixes

-   Error with novelties export job running too long. Model relationships are eager loaded to improve job speed

## [v0.1.1 (2020-06-05)](https://github.com/llstarscreamll/kirby-api/compare/v0.1..v0.1.1)

Fix(novelties): error overwriting non scheduled novelty end time on time clock check in

## [v0.1 (2020-05-30)](https://github.com/llstarscreamll/kirby-api/compare/v0.1..7b3bec6560f3fbb1cd93c849861b3cb2b4df5859)

This is the first release involving minimal features for going to production.
The main goal is to provide a REST API to manage employees novelties
calculations based on time clock data:

### Feat

-   authentication: login and logout endpoints
-   authorization: each endpoint needs specific permission(s) by the user to be
    performed
-   employees: REST API with list/search, create, show, update endpoints
-   time clock: REST API with list/search, check in, check out, check in and
    check out simulator endpoints
-   work shifts: REST API with list/search endpoints
-   novelties: REST API with list/search, create many, show, show, update,
    destroy, single and batch approving, single and batch unapproving export to
    csv endpoints
-   novelties: automatic novelties calculation based on employee time clock logs
-   novelty types: REST API with list/search endpoints
-   sub cost centers: REST API with list/search endpoints
