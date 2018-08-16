# Installation

`composer require "tarasovych/csvartisan @dev"`  
Package has [discovery feature](https://laravel.com/docs/5.5/packages#package-discovery).

# Usage

## Importing csv to a model-related table

1. Upload csv file to your Laravel app root folder.  
Note, that csv file name must be UTF-8 latin.  
CSV file must have appropriate headers.  
E. g. if your model you want import to has `name` and `email` fillable fields, your csv file must have "name,email" at the header row.  
[CSV example](sample.csv).
2. Execute `php artisan csv:import`
3. Guide the dialog.
