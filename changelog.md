# Release notes

## [2.8.0 (2023-04-01)](https://github.com/llstarscreamll/kirby-api/compare/2.8.0..2.7.0)

- feat(novelties): new attribute to load attachments to novelties
- feat(novelties): default novelties for subtraction and addtion can be now configurable

## [2.7.0 (2022-12-22)](https://github.com/llstarscreamll/kirby-api/compare/2.7.0..2.6.0)

- feat(time-clock): prevent to use employee token codes for check in/out
- feat(production): require employee token on create log when user has permission to create logs on behalf another employees
- feat(production): validate token owner permission when creating production log
- fix(production): require employee code when user has permission to create logs on behalf another employees, if him does not have permission then attach the log to the current user and make not required said field
- feat(production): make required the employee code when updating a production log
- feat(production): make update log use employee identification codes instead of employee IDs
- feat(production): make create log endpoint use employee uuid identification codes instead of employee IDs
- feat(employee): new token attribute on Employee entity
- feat(employees): new ability to regenerate uuid identification when employee is updated
- feat(employees): new identification types with autogenerated codes (UUID v4)

## [2.6.0 (2022-07-09)](https://github.com/llstarscreamll/kirby-api/compare/2.6.0..2.5.0)

- time-clock: new endpoint to export time clock logs to csv format and send the file to authenticated user email
- time-clock: search endpoint can now filter by check in date ranges

## [2.5.0 (2022-06-28)](https://github.com/llstarscreamll/kirby-api/compare/2.5.0..2.4.0)

### Added

- time-clock: new ability to filter logs by employees and check in date range and those who are inside the company
- time-clock: new endpoint statistics that count people inside
- time-clock: let admis see time clock logs if they have all permisions
- increments default api rate limit to 200 request per second
- other security apudates

## [2.4.0 (2022-05-19)](https://github.com/llstarscreamll/kirby-api/compare/2.4.0..2.3.0)

## Added

-   When employees are created and updated, you can now set email, password and roles

## [2.3.0 (2022-04-08)](https://github.com/llstarscreamll/kirby-api/compare/2.3.0..2.2.0)

## Added

-   new "purpose" attribute to instruct if a production log purpose is for sales or consumption, the field is searchable and is added to csv export data

## [2.2.0 (2022-03-09)](https://github.com/llstarscreamll/kirby-api/compare/2.2.0..2.1.0)

### Added

-   new endpoint to get product by id
-   new endpoint to update product
-   new endpoint to create product
-   new "tag date" attribute on production logs to control when a tag is changed
-   new ability to search machines by cost centers
-   new endpoint for querying production reporting data about accumulated Kgs from product
-   add "tag date" field to production logs search and remove filter by "created at" field
-   add "tag date" field to export production logs to csv

### Changed

-   filter logs and report by cost centers instead of sub cost centers
-   search production logs improvements, search by many employees, many machines, many products and many sub cost centers

## [2.1.0 (2021-11-24)](https://github.com/llstarscreamll/kirby-api/compare/2.1.0..2.0.0)

### Added

-   Production logs now have ability to tagging records as InLine, Error and Rejected (default to InLine)
-   New ability to update production log records
-   New ability to search production logs by exact net weight, employee, machine and product id
-   New ability to filter data results of production logs csv export, same filter options as search production logs

### Changed

-   Improve Scrutinizer and Travis CI setup to make them faster
-   Add tagging info to production log export
-   Many stability and security improvements

## [2.0.0 (2021-09-05)](https://github.com/llstarscreamll/kirby-api/compare/2.0.0..v1.5)

### Added

-   Production: new module to handle production (manufacturing) data, this new functionality relies on employees, productos, machines and customers information in order to create production logs. Data can be exported as csv files.
-   Many security updates and bug fixes

## [v1.5 (2021-01-21)](https://github.com/llstarscreamll/kirby-api/compare/v1.5..v1.4)

### Changed

-   Novelties: return current user data based on ACL rules in report resume by novelty types 

## [v1.4 (2021-01-20)](https://github.com/llstarscreamll/kirby-api/compare/v1.4..v1.3)

### Changed

-   Novelties: refactor global and employee search 

## [v1.3 (2020-12-28)](https://github.com/llstarscreamll/kirby-api/compare/v1.3..v1.2.1)

### Added

-   Novelties, new endpoint to export resume by novelty type from all employees to csv, and email with a download link is send to the user who invoked the endpoint.

## [v1.2.1 (2020-11-11)](https://github.com/llstarscreamll/kirby-api/compare/v1.2.1..v1.2)

### Fixed

-   novelties: error using decimals to create balance novelties

## [v1.2 (2020-11-11)](https://github.com/llstarscreamll/kirby-api/compare/v1.2..v1.1.3)

### Added

-   time-clock: new ability to manually create checkouts only
-   employees: new ability to search by emails
-   time-clock: new ability to make users see only their own employee time clock logs
-   novelties: new ability to make users see only their own employee novelties

### Changed

-   novelties: change minimum time from 5 minutes to 2 minutes for deducting novelties from time clock log
-   work-shifts: sorting search results by id desc
-   time-clock: improve algorithm to deduce scheduled novelty for check in/out
-   use full pagination instead of simple pagination on all paginated resources

### Fixed

-   work shifts: many improvements deducting work shifts on check in
-   novelties: a lot of improvements solving novelties based in time clock logs
-   time clock: errors updating dates from scheduled novelties on check in/out
-   work shifts: error writing time zone data
-   work shifts: error writing days which a work shift applies

## [v1.1.4 (2020-08-21)](https://github.com/llstarscreamll/kirby-api/compare/v1.1.3..v1.1.4)

### Fixed

-   Update composer dependencies and remove dd() from employees controller.

## [v1.1.3 (2020-08-21)](https://github.com/llstarscreamll/kirby-api/compare/v1.1.2..v1.1.3)

### Fixed

-   Error with backup.php config file cleaning up old backups

## [v1.1.2 (2020-08-21)](https://github.com/llstarscreamll/kirby-api/compare/v1.1.1..v1.1.2)

### Fixed

-   Fix error serving phone data on employee resource

## [v1.1.1 (2020-08-21)](https://github.com/llstarscreamll/kirby-api/compare/v1.1..v1.1.1)

### Fixed

-   Fix error with the way that phone number and phone prefix are handled

## [v1.1 (2020-08-21)](https://github.com/llstarscreamll/kirby-api/compare/v1.0.2..v1.1)

### Added

-   feat(users): users entity now supports phone numbers and phone validation date, so when a users are signed up the phone number is now required and when a employee is created some phone number validations are mede in order to keep data consistency. Employee data and user data are in sync.

### Changed

-   data consistency guarded on employees data operations
-   test suite framework changed from Codeception to PHPUnit

## [v1.0.2 (2020-08-19)](https://github.com/llstarscreamll/kirby-api/compare/v1.0.1..v1.0.2)

### Fixed

## [v1.0.1 (2020-08-18)](https://github.com/llstarscreamll/kirby-api/compare/v1.0.0..v1.0.1)

### Fixed

-   CI error with missing class and miss phpunit config

## [v1.0.0 (2020-08-18)](https://github.com/llstarscreamll/kirby-api/compare/v0.3..v1.0.0)

### Changed

-   change test framework from Codeception to PHPUnit

### Fixed

-   fix error seeding default settings

## [v0.3 (2020-08-11)](https://github.com/llstarscreamll/kirby-api/compare/v0.2.2..v0.3)

### Added

-   hide sql errors from api responses and return generic 500 server error message
-   novelties: new endpoint to create balance novelty
-   novelties: add default novelty types B+ and B- for novelties balance purposes
-   novelties: new endpoint to get novelties settings
-   novelties: new novelties resume by novelty type endpoint
-   time-clock: update settings entity and time clock package settings keys names

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
