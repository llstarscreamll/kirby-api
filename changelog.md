# Release notes

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
