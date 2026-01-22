# Upgrade Guide

## Upgrading from 1.x to 2.0

Version 2.0 includes significant improvements and breaking changes. This guide will help you upgrade smoothly.

### Breaking Changes

#### 1. Environment Variable Changes

**Before (v1.x):**
```env
ZEPTOMAIL_HOST=zoho.com
ZEPTOMAIL_TOKEN=your-token
```

**After (v2.0):**
```env
ZEPTOMAIL_REGION=us
ZEPTOMAIL_API_KEY=your-token
```

**Migration Steps:**

1. Replace `ZEPTOMAIL_HOST` with `ZEPTOMAIL_REGION`
2. Change region codes:
   - `zoho.com` → `us`
   - `zoho.eu` → `eu`
   - `zoho.in` → `in`
   - `zoho.com.au` → `au`
   - `zoho.jp` → `jp`
   - `zohocloud.ca` → `ca`
   - `zoho.sa` → `sa`
   - `zoho.com.cn` → `cn`

3. (Optional) Rename `ZEPTOMAIL_TOKEN` to `ZEPTOMAIL_API_KEY` (both are supported)

#### 2. Configuration Structure

If you have a published configuration file, you need to update it.

**Delete old config:**
```bash
rm config/laravel-driver.php  # or whatever your old config was named
```

**Publish new config:**
```bash
php artisan vendor:publish --tag=zeptomail-config
```

**Update your `.env` file:**
```env
ZEPTOMAIL_API_KEY=your-send-mail-token
ZEPTOMAIL_REGION=eu  # or your region
MAIL_MAILER=zeptomail
```

#### 3. Error Handling

Version 2.0 introduces custom exception classes. If you were catching generic exceptions, update your code:

**Before (v1.x):**
```php
try {
    Mail::send(new WelcomeEmail($user));
} catch (\RuntimeException $e) {
    Log::error('Mail error: ' . $e->getMessage());
}
```

**After (v2.0):**
```php
use ZohoMail\LaravelZeptoMail\Exceptions\ConfigurationException;
use ZohoMail\LaravelZeptoMail\Exceptions\ConnectionException;
use ZohoMail\LaravelZeptoMail\Exceptions\ApiException;

try {
    Mail::send(new WelcomeEmail($user));
} catch (ConfigurationException $e) {
    Log::critical('Configuration error: ' . $e->getMessage());
} catch (ConnectionException $e) {
    Log::error('Connection error: ' . $e->getMessage());
} catch (ApiException $e) {
    Log::error('API error: ' . $e->getMessage(), [
        'status' => $e->getHttpStatusCode(),
        'response' => $e->getResponseBody(),
    ]);
}
```

### New Features in 2.0

#### 1. Logging Support

Enable detailed logging for debugging:

```env
ZEPTOMAIL_LOGGING=true
```

Logs will appear in `storage/logs/laravel.log` with `[ZeptoMail]` prefix.

#### 2. Custom Endpoints

You can now use custom endpoints:

```env
ZEPTOMAIL_ENDPOINT=https://custom.zeptomail.com
```

This overrides the region setting.

#### 3. Request Timeout Configuration

Control how long to wait for API responses:

```env
ZEPTOMAIL_TIMEOUT=30  # seconds
```

#### 4. Better Error Messages

Errors now include more context and specific exception types, making debugging easier.

### Step-by-Step Upgrade Process

#### Step 1: Update Package

```bash
composer update zohomail/laravel-zeptomail
```

#### Step 2: Update Environment Variables

Edit your `.env` file:

```diff
- ZEPTOMAIL_HOST=zoho.eu
+ ZEPTOMAIL_REGION=eu

- ZEPTOMAIL_TOKEN=your-token
+ ZEPTOMAIL_API_KEY=your-token

+ ZEPTOMAIL_LOGGING=false
+ ZEPTOMAIL_TIMEOUT=30
```

#### Step 3: Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
```

#### Step 4: Update Error Handling (Optional but Recommended)

Update your exception handling to use the new exception classes for better error reporting.

#### Step 5: Test Email Sending

```bash
php artisan tinker
```

```php
Mail::raw('Upgrade test', function ($message) {
    $message->to('test@example.com')
            ->subject('ZeptoMail 2.0 Test');
});
```

If the email sends successfully, your upgrade is complete!

### Troubleshooting

#### Issue: "ZeptoMail API key is required"

**Solution:** Ensure `ZEPTOMAIL_API_KEY` is set in your `.env` file.

```env
ZEPTOMAIL_API_KEY=your-actual-token-here
```

Then clear config cache:
```bash
php artisan config:clear
```

#### Issue: "Invalid ZeptoMail region"

**Solution:** Use a valid region code: `us`, `eu`, `in`, `au`, `jp`, `ca`, `sa`, or `cn`.

```env
ZEPTOMAIL_REGION=eu  # not zoho.eu
```

#### Issue: Emails not sending after upgrade

**Solution:**

1. Enable logging to see detailed errors:
   ```env
   ZEPTOMAIL_LOGGING=true
   ```

2. Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Verify your API token is still valid in ZeptoMail dashboard

4. Ensure your domain is verified in ZeptoMail

#### Issue: "Class not found" errors

**Solution:** Update Composer autoloader:

```bash
composer dump-autoload
```

### Compatibility Notes

- **Laravel 9, 10, 11, 12**: Fully supported
- **PHP 8.0+**: Required (PHP 7.x is no longer supported)
- **Symfony Mailer 6.x, 7.x**: Supported

### Rolling Back

If you need to roll back to version 1.x:

```bash
composer require zohomail/laravel-zeptomail:^1.0
```

Then revert your `.env` changes:

```env
ZEPTOMAIL_HOST=zoho.eu  # or your old host
ZEPTOMAIL_TOKEN=your-token
```

And clear cache:
```bash
php artisan config:clear
```

### Getting Help

If you encounter issues during the upgrade:

1. Check the [README](README.md) for detailed configuration instructions
2. Review the [EXAMPLES](EXAMPLES.md) for usage patterns
3. Enable `ZEPTOMAIL_LOGGING=true` and check logs
4. Open an issue on GitHub with:
   - Laravel version
   - PHP version
   - Error messages from logs
   - Relevant configuration (with API key redacted)
