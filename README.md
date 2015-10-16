Klio
====

Just a thing.

## Modules

Module classes are in the same namespace as core classes.

Modules can not define routes.

### Controllers

For a URL of the form `/table/{table}/{action}` the first of the following will be used:
- `App\Controllers\Tables\{Table}Controller::{action}`
- `App\Controllers\Tables\{Table}Controller::index`
- `App\Controllers\TableController::{action}`
- `App\Controllers\TableController::index`

### Templates

Modules can add their own template directories with:

    $template = new \App\Template('some-template.twig');
    $template->addPath('vendor/org/module/templates_dir');

Tables and records have a few default templates each:
HTML table and CSV (for tables), and HTML table and editing form (for records).
