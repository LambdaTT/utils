# lambdatt-php/utils

A SplitPHP Framework plugin which provides helpers and general utilities out of the box.

---

## Installation

Install via Composer:

```bash
composer require lambdatt-php/utils
```

Run the Migrations:
```bash
php console migrations:apply --module=utils
```
**PS: this can only be installed on a SplitPHP Framework project. For more information refer to: https://github.com/splitphp/core**

## Included Utilities

- General purpose procedures
- Service "utils/mail": provides a ready-to-use mailer.
- Service "utils/misc": a service with several general purpose utilities.

## Usage:

### General Purpose Procedures:

- **generate_dateseries:**
*(inside a service, CLI or webservice)*
```php
// returns a list of dates (YYYY-mm-dd) between $startDate and $endDate
$this->getDao('tablename')
    ->generate_dateseries($startDate, $endDate)
    ->find("SELECT * FROM dateseries");
```
---

### Service "utils/mail":

**Configs:**
Set in your environment the following variables:

```ini
# SMTP SETTINGS:
SMTP_HOST="your-smtp-host.address"
SMTP_PORT=587 # your smtp port (587) is the most commonly used for TLS
REQUIRE_TLS="on" # or 'off'
SMTP_USER="your_smtp_username"
SMTP_PASS="your_smtp_user_password"
SENDER_EMAIL="sender@your-domain.com"
SENDER_NAME="Sender Name"
```

**Send e-mail:**

```php
$this->getService('utils/mail')->send($msg, $recipientAddress, $subject);
```
---

### Service utils/misc:

This service is a miscelanea of functions for many purposes. heres a list of the available functions:

- `matrixUnique($matrix, $innerObj = false)`: 
Removes duplicate entries from a two-dimensional array (\$matrix) by serializing each sub-array. If \$innerObj is true, unserialized elements become objects; otherwise, they remain arrays.

- `validateCPF($cpf)`: 
Takes a string CPF and returns true if it’s a valid Brazilian CPF (11 digits, not a repeated sequence, correct check digits), or false otherwise.

- `validateCNPJ($cnpj)`: 
Takes a string CNPJ and returns true if it’s a valid Brazilian CNPJ (14 digits, not a repeated sequence, correct check digits), or false otherwise.

- `validateUF($data)`: 
Accepts a Brazilian state abbreviation (e.g. "SP") and returns true if it’s one of the 27 valid UFs, or false otherwise.

- `getUserIP()`: 
No parameters. Returns the client’s IP address, checking HTTP_CLIENT_IP, then HTTP_X_FORWARDED_FOR, then REMOTE_ADDR.

- `stringToSlug(string $string)`: 
Cleans up an input string to a URL-friendly slug: removes special characters, transliterates to ASCII, replaces spaces with hyphens, and lowercases everything.

- `dataBlackList($data, array $blacklist)`: 
Takes an array or object (\$data) and a list of forbidden keys (\$blacklist); returns an array with those keys removed.

- `dataWhiteList($data, array $whitelist)`: 
Takes an array or object (\$data) and a list of allowed keys (\$whitelist); returns an array containing only those keys.

- `dumpToXLS($data, $fileName = "data_dump.xls")`: 
Sends HTTP headers for downloading an Excel file and echoes \$data (array of arrays or objects) as tab-delimited rows, then exits.

- `secondsToTime($secs)`: 
Converts a total number of seconds (\$secs) into an object with integer properties
    ```javascript
    {
        h: hours
        m: minutes
        s: seconds
    }
    ```

- `readCsvFile($filename)`: 
Reads a CSV file at path \$filename, throwing an Exception if it doesn’t exist or isn’t readable. Returns an array of objects, using the first row as column headers.

- `readCsvFromString($csvString)`: 
Parses CSV data from a string (\$csvString) and returns an array of objects, using the first line as headers.

- `generateCPF($onlyNumbers = false)`: 
Generates a random CPF. If \$onlyNumbers is true, returns only digits; otherwise, returns formatted as ###.###.###-##.

- `generateRG($onlyNumbers = false)`: 
Generates a random RG (9 digits). If \$onlyNumbers is true, returns only digits; otherwise, returns formatted as ##.###.###-#.

- `share($name, $value = null)`: 
If \$value is provided, stores it in a static cache under key \$name; if not, returns the previously stored value or null if none exists.

- `formatAddress($addressObj)`: 
Accepts an object with address fields (e.g. ds_addressstreet, ds_addressnumber, etc.), verifies required fields are present and non-empty, and returns a single formatted string (Street, Number, [Complement,] Neighborhood, City – UF) or null if any required field is missing.