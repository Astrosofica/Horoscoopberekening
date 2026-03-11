# Logic Errors Analysis - AstrologicalTimeManager

## Critical

1. **API Key exposed** (`config.php:2`, `index.php:16,24`) - Hardcoded API key visible in source and URLs

2. **Type confusion in DST lookup** (`AstrologicalTimeManager.php:174-176`) - Uses `date()` which respects server's timezone, not the timezone being calculated. If server isn't UTC, DST detection fails.

## Medium

3. **String comparison for dates** (`AstrologicalTimeManager.php:48,57,67,98,102,106`) - Uses string comparison `"1909-05-01 00:00:00"` instead of timestamp comparison. Works but fragile.

4. **Missing error handling** (`index.php:17,25`) - No check if `file_get_contents()` fails or returns false

5. **1942 DST entry malformed** (`AstrologicalTimeManager.php:205`) - Only has start date, will cause undefined index when checking end date:
   ```php
   1942 => ['1942-11-02 02:00:00', '1942-11-02 03:00:00'],  // Only 1 element
   ```

6. **1941 DST entry** - Year-round DST was historically correct during WWII German occupation (1941-1944). This entry is accurate.

## Minor

7. **No input validation** (`index.php:8-11`) - `$_POST` values used directly without sanitization
