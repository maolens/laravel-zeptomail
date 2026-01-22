# ZeptoMail Driver for Laravel

Professional Laravel mail driver for Zoho ZeptoMail - a transactional email service.

## Features

- ✅ Full support for Laravel 9.x, 10.x, 11.x, and 12.x
- ✅ Support for all ZeptoMail regions (US, EU, IN, AU, JP, CA, SA, CN)
- ✅ Custom endpoint support
- ✅ Comprehensive error handling with custom exceptions
- ✅ Optional logging for debugging
- ✅ Support for HTML and text emails
- ✅ Attachments support
- ✅ CC and BCC recipients
- ✅ Reply-To support
- ✅ Type-safe configuration
- ✅ PSR-4 autoloading

## Requirements

- PHP >= 8.0
- Laravel >= 9.0
- Zoho ZeptoMail account with verified domain
- ZeptoMail Send Mail token

## Installation

Install the package via Composer:

```bash
composer require zohomail/laravel-zeptomail:dev-main
```

### Publish Configuration (Optional)

You can publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag=zeptomail-config
```

This will create `config/zeptomail.php` in your application.

## Configuration

### Step 1: Environment Variables

Add the following variables to your `.env` file:

```env
# Required: Your ZeptoMail Send Mail token
ZEPTOMAIL_API_KEY=your-send-mail-token-here

# Required: Your ZeptoMail region (us, eu, in, au, jp, ca, sa, cn)
ZEPTOMAIL_REGION=eu

# Optional: Custom endpoint (overrides region)
# ZEPTOMAIL_ENDPOINT=https://custom.zeptomail.com

# Optional: API version (default: v1.1)
# ZEPTOMAIL_API_VERSION=v1.1

# Optional: Request timeout in seconds (default: 30)
# ZEPTOMAIL_TIMEOUT=30

# Optional: Enable detailed logging (default: false)
# ZEPTOMAIL_LOGGING=true

# Set ZeptoMail as your mail driver
MAIL_MAILER=zeptomail

# Your verified sender address
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Your App Name"
```

**Important:** Replace `your-send-mail-token-here` with your actual Send Mail token from ZeptoMail.

### Step 2: Mail Configuration

Add ZeptoMail mailer to `config/mail.php`:

```php
'mailers' => [
    // ... other mailers

    'zeptomail' => [
        'transport' => 'zeptomail',
    ],
],
```

## Supported Regions

| Region Code | Endpoint Domain        | Description             |
| ----------- | ---------------------- | ----------------------- |
| `us`        | `api.zeptomail.com`    | United States (default) |
| `eu`        | `api.zeptomail.eu`     | European Union          |
| `in`        | `api.zeptomail.in`     | India                   |
| `au`        | `api.zeptomail.com.au` | Australia               |
| `jp`        | `api.zeptomail.jp`     | Japan                   |
| `ca`        | `api.zeptomail.ca`     | Canada                  |
| `sa`        | `api.zeptomail.sa`     | Saudi Arabia            |
| `cn`        | `api.zeptomail.com.cn` | China                   |

### European Region Example

For EU region (https://api.zeptomail.eu/v1.1/email):

```env
ZEPTOMAIL_REGION=eu
```

### Custom Endpoint Example

If you need a custom endpoint:

```env
ZEPTOMAIL_ENDPOINT=https://custom.zeptomail.com
```

## Usage

### Basic Email

```php
use Illuminate\Support\Facades\Mail;

Mail::raw('This is a test email', function ($message) {
    $message->to('recipient@example.com')
            ->subject('Test Email');
});
```

### HTML Email with Mailable

```php
use Illuminate\Mail\Mailable;

class WelcomeEmail extends Mailable
{
    public function build()
    {
        return $this->view('emails.welcome')
                    ->subject('Welcome to Our App')
                    ->to('user@example.com');
    }
}

// Send the email
Mail::send(new WelcomeEmail());
```

### Email with Attachments

```php
Mail::send('emails.invoice', $data, function ($message) use ($user) {
    $message->to($user->email)
            ->subject('Your Invoice')
            ->attach('/path/to/invoice.pdf');
});
```

### Email with CC and BCC

```php
Mail::raw('Important announcement', function ($message) {
    $message->to('primary@example.com')
            ->cc('manager@example.com')
            ->bcc('archive@example.com')
            ->subject('Announcement');
});
```

### Testing with Tinker

```bash
php artisan tinker
```

```php
Mail::raw('This is a test email', function ($message) {
    $message->to('test@example.com')
            ->subject('Testing Laravel ZeptoMail');
});
```

## Error Handling

The package provides custom exceptions for better error handling:

```php
use ZohoMail\LaravelZeptoMail\Exceptions\ConfigurationException;
use ZohoMail\LaravelZeptoMail\Exceptions\ConnectionException;
use ZohoMail\LaravelZeptoMail\Exceptions\ApiException;

try {
    Mail::send(new WelcomeEmail());
} catch (ConfigurationException $e) {
    // Handle configuration errors (missing API key, invalid region, etc.)
    Log::error('ZeptoMail configuration error: ' . $e->getMessage());
} catch (ConnectionException $e) {
    // Handle connection errors
    Log::error('Failed to connect to ZeptoMail: ' . $e->getMessage());
} catch (ApiException $e) {
    // Handle API errors
    Log::error('ZeptoMail API error: ' . $e->getMessage(), [
        'status_code' => $e->getHttpStatusCode(),
        'response' => $e->getResponseBody(),
    ]);
}
```

## Logging

Enable logging to debug email sending issues:

```env
ZEPTOMAIL_LOGGING=true
```

Logs will be written to your Laravel log file with the `[ZeptoMail]` prefix.

## Advanced Configuration

If you published the configuration file, you can customize settings in `config/zeptomail.php`:

```php
return [
    'api_key' => env('ZEPTOMAIL_API_KEY'),
    'region' => env('ZEPTOMAIL_REGION', 'us'),
    'endpoint' => env('ZEPTOMAIL_ENDPOINT'),
    'api_version' => env('ZEPTOMAIL_API_VERSION', 'v1.1'),
    'timeout' => env('ZEPTOMAIL_TIMEOUT', 30),
    'logging' => env('ZEPTOMAIL_LOGGING', false),

    // Custom region domains (if ZeptoMail adds new regions)
    'region_domains' => [
        'us' => 'com',
        'eu' => 'eu',
        // ... etc
    ],
];
```

## Getting Your ZeptoMail Token

1. Log in to your [ZeptoMail account](https://www.zoho.com/zeptomail/)
2. Navigate to **Settings** → **Mail Agents**
3. Select your Mail Agent
4. Click **Setup Instructions**
5. Copy the **Send Mail Token**
6. Paste it in your `.env` file as `ZEPTOMAIL_API_KEY`

## Troubleshooting

### Email Not Sending

1. Verify your API token is correct
2. Ensure your domain is verified in ZeptoMail
3. Check your sender email is from a verified domain
4. Enable logging to see detailed error messages:
    ```env
    ZEPTOMAIL_LOGGING=true
    ```
5. Check Laravel logs at `storage/logs/laravel.log`

### API Endpoint Issues

- For EU region, ensure you're using `ZEPTOMAIL_REGION=eu`
- The package automatically builds the correct endpoint: `https://api.zeptomail.eu/v1.1/email`
- If using a custom endpoint, ensure it's the full base URL without the API version

### Configuration Errors

```bash
# Clear config cache
php artisan config:clear

# Verify configuration
php artisan tinker
>>> config('zeptomail')
```

## Testing

Send a test email:

```bash
php artisan tinker
```

```php
Mail::raw('Test email from ZeptoMail', function ($message) {
    $message->to('your-email@example.com')
            ->subject('ZeptoMail Test');
});
```

Check the recipient inbox and your ZeptoMail dashboard for delivery status.

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Support

For issues related to:

- **This package**: Open an issue on GitHub
- **ZeptoMail service**: Contact [ZeptoMail Support](https://www.zoho.com/zeptomail/help/)
- **Laravel mail**: Check [Laravel Mail Documentation](https://laravel.com/docs/mail)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Credits

- Originally created by Zoho Mail Team
- Enhanced and maintained by the Laravel community

## Links

- [ZeptoMail Official Website](https://www.zoho.com/zeptomail/)
- [ZeptoMail API Documentation](https://www.zoho.com/zeptomail/help/api/)
- [Laravel Mail Documentation](https://laravel.com/docs/mail)
