# Home owner names - technical test

## Installation:
Run `git clone git@github.com:colzboppo/street_names.git` to clone repo.
Run `cd street_names` to correct directory
Run `composer install` to install dependancies
Run `php artisan test` to run tests, nb. sqlite database already has migrations.

## Brief:
You have been provided with a CSV from an estate agent containing an export of their
homeowner data. If there are multiple homeowners, the estate agent has been entering both
people into one field, often in different formats.

Write a program that can accept the CSV and output an array of people, splitting the name into
the correct fields, and splitting multiple people from one string where appropriate.

## Plan:
The most crucial part of the assessment seems to be the proper parsing of the data in different formats into consistent names in the database.
- Finding a PHP library/package that can parse and split names sensibily was first priority, then creating a service class that will carry out the basic tasks of reading a .CSV file and processing each line into a predictable format we can save to the database as an individual "Person".
- Creating PHPUnit tests that will run various formatting through the class and see how reliable we can make the name parsing is paramount.
- Creating a front-end interface, routes, and controllers to allow a usable demo for end-users will come secondary to these main tasks above.

## Implementation:
Quickly found TheIconic\NameParser to fall short of requirements for our name parsing. In particular splitting names such as "Mr & Mrs Butler" was problematic and inserted initials and inappropriate first names into the data. I had a cursory second look for packages that could accomplish this to no avail, and settled on writing some custom code to handle this behaviour on top of the library, this should be re-visited later, as the code written is un-elegant and will likely break with certain formatting beyond the unit tests written.

See `App\Services\NameParser` for details. It gets the job done, for now, in the appropriate time given 🔥

Initially testing in tinker to get the class running properly, switching to PHPunit tests to dial in the expected behaviour. 
You can run `php artisan test` to see the results.

## Considerations:
Aside from a front-end interface and supporting back-end parts, ie. routes, controllers, vue components, etc. it would perhaps be useful to consider some kind of review process for data-crunching where an end-user is prompted with an editable list of parsed names so they can final-review the data and adjust manually, no programming is perfect afterall, possible potential AI integration even.
A second data model/layer of processing batches would also be useful to allow batches of names to be processed, reviewed, and submitted finally into the database, rather than be inserted directly, this could be useful to allow imports to be rolled-back or re-processed differently later.
