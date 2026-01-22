# ZeptoMail Laravel Driver - Examples

This document provides comprehensive examples of how to use the ZeptoMail Laravel Driver.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Advanced Examples](#advanced-examples)
- [Error Handling](#error-handling)
- [Configuration Examples](#configuration-examples)

## Basic Usage

### Simple Text Email

```php
use Illuminate\Support\Facades\Mail;

Mail::raw('Hello, this is a test email!', function ($message) {
    $message->to('recipient@example.com')
            ->subject('Test Email');
});
```

### HTML Email with View

```php
Mail::send('emails.welcome', ['name' => 'John'], function ($message) {
    $message->to('john@example.com')
            ->subject('Welcome to Our Service!');
});
```

### Using Mailable Classes

```php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->view('emails.welcome')
                    ->subject('Welcome ' . $this->user->name)
                    ->with(['username' => $this->user->name]);
    }
}

// Send the email
use App\Mail\WelcomeEmail;

Mail::to($user->email)->send(new WelcomeEmail($user));
```

## Advanced Examples

### Email with Multiple Recipients

```php
Mail::send('emails.newsletter', $data, function ($message) {
    $message->to([
        'user1@example.com',
        'user2@example.com',
        'user3@example.com',
    ])->subject('Monthly Newsletter');
});
```

### Email with CC and BCC

```php
Mail::send('emails.report', $reportData, function ($message) {
    $message->to('manager@company.com')
            ->cc('supervisor@company.com')
            ->bcc('archive@company.com')
            ->subject('Monthly Sales Report');
});
```

### Email with Attachments

```php
Mail::send('emails.invoice', $invoiceData, function ($message) use ($invoicePath) {
    $message->to('customer@example.com')
            ->subject('Your Invoice')
            ->attach($invoicePath, [
                'as' => 'invoice.pdf',
                'mime' => 'application/pdf',
            ]);
});
```

### Email with Multiple Attachments

```php
Mail::send('emails.documents', $data, function ($message) {
    $message->to('recipient@example.com')
            ->subject('Requested Documents')
            ->attach('/path/to/document1.pdf')
            ->attach('/path/to/document2.pdf')
            ->attach('/path/to/spreadsheet.xlsx');
});
```

### Email with Reply-To Address

```php
Mail::send('emails.support', $data, function ($message) {
    $message->to('customer@example.com')
            ->replyTo('support@yourcompany.com', 'Support Team')
            ->subject('Re: Your Support Request');
});
```

### Queued Email

```php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderShipped extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->view('emails.order-shipped')
                    ->subject('Your Order Has Shipped!');
    }
}

// Send queued email
Mail::to($order->customer->email)->queue(new OrderShipped($order));
```

### Email with Custom Headers

```php
Mail::send('emails.notification', $data, function ($message) {
    $message->to('user@example.com')
            ->subject('Notification')
            ->getHeaders()
            ->addTextHeader('X-Custom-Header', 'CustomValue');
});
```

## Error Handling

### Basic Error Handling

```php
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

try {
    Mail::to('user@example.com')->send(new WelcomeEmail($user));
} catch (\Exception $e) {
    Log::error('Failed to send email: ' . $e->getMessage());
    // Handle the error appropriately
}
```

### Detailed Error Handling with Custom Exceptions

```php
use ZohoMail\LaravelZeptoMail\Exceptions\ConfigurationException;
use ZohoMail\LaravelZeptoMail\Exceptions\ConnectionException;
use ZohoMail\LaravelZeptoMail\Exceptions\ApiException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

try {
    Mail::to('user@example.com')->send(new WelcomeEmail($user));

} catch (ConfigurationException $e) {
    // Configuration is wrong (missing API key, invalid region, etc.)
    Log::critical('ZeptoMail configuration error', [
        'message' => $e->getMessage(),
        'context' => $e->getContext(),
    ]);

    // Notify admin about configuration issue
    $this->notifyAdminAboutConfigIssue($e);

} catch (ConnectionException $e) {
    // Network or connectivity issue
    Log::error('Failed to connect to ZeptoMail API', [
        'message' => $e->getMessage(),
        'user' => $user->email,
    ]);

    // Retry with exponential backoff or queue for later
    Mail::to($user->email)->later(now()->addMinutes(5), new WelcomeEmail($user));

} catch (ApiException $e) {
    // ZeptoMail API returned an error
    Log::error('ZeptoMail API error', [
        'message' => $e->getMessage(),
        'status_code' => $e->getHttpStatusCode(),
        'response' => $e->getResponseBody(),
        'user' => $user->email,
    ]);

    // Handle specific error codes
    if ($e->getHttpStatusCode() === 429) {
        // Rate limit exceeded, retry later
        Mail::to($user->email)->later(now()->addMinutes(10), new WelcomeEmail($user));
    } elseif ($e->getHttpStatusCode() === 401) {
        // Invalid API key, notify admin
        $this->notifyAdminAboutAuthIssue($e);
    }

} catch (\Throwable $e) {
    // Catch any other unexpected errors
    Log::error('Unexpected error sending email', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
```

### Retry Failed Emails

```php
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendWithRetry($mailable, $recipient, $maxAttempts = 3)
    {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                Mail::to($recipient)->send($mailable);
                return true; // Success

            } catch (\Exception $e) {
                $attempt++;

                if ($attempt >= $maxAttempts) {
                    // All attempts failed
                    Log::error("Failed to send email after {$maxAttempts} attempts", [
                        'recipient' => $recipient,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }

                // Wait before retry (exponential backoff)
                sleep(pow(2, $attempt));
            }
        }

        return false;
    }
}
```

## Configuration Examples

### Using EU Region

```env
ZEPTOMAIL_API_KEY=your-api-key-here
ZEPTOMAIL_REGION=eu
MAIL_MAILER=zeptomail
```

### Using Custom Endpoint

```env
ZEPTOMAIL_API_KEY=your-api-key-here
ZEPTOMAIL_ENDPOINT=https://custom.zeptomail.com
MAIL_MAILER=zeptomail
```

### Enable Logging for Debugging

```env
ZEPTOMAIL_API_KEY=your-api-key-here
ZEPTOMAIL_REGION=us
ZEPTOMAIL_LOGGING=true
MAIL_MAILER=zeptomail
```

### Using in Multi-Mailer Setup

In `config/mail.php`:

```php
'mailers' => [
    'smtp' => [
        'transport' => 'smtp',
        // SMTP configuration...
    ],

    'zeptomail' => [
        'transport' => 'zeptomail',
    ],
],

'default' => env('MAIL_MAILER', 'smtp'),
```

Send via specific mailer:

```php
// Send via ZeptoMail
Mail::mailer('zeptomail')->to('user@example.com')->send(new WelcomeEmail($user));

// Send via SMTP
Mail::mailer('smtp')->to('user@example.com')->send(new WelcomeEmail($user));
```

### Testing Email in Development

```php
// In routes/web.php or a controller
Route::get('/test-email', function () {
    try {
        Mail::raw('This is a test email from ZeptoMail', function ($message) {
            $message->to('test@example.com')
                    ->subject('Test Email');
        });

        return 'Email sent successfully!';
    } catch (\Exception $e) {
        return 'Failed to send email: ' . $e->getMessage();
    }
});
```

### Sending Markdown Emails

```php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->markdown('emails.orders.confirmation')
                    ->subject('Order Confirmation #' . $this->order->number);
    }
}
```

### Notification via Email

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class InvoicePaid extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Invoice Paid')
                    ->line('Your invoice has been paid!')
                    ->action('View Invoice', url('/invoices'))
                    ->line('Thank you for your business!');
    }
}

// Send notification
$user->notify(new InvoicePaid($invoice));
```

## Best Practices

### 1. Always Use Try-Catch for Critical Emails

```php
try {
    Mail::to($user->email)->send(new CriticalNotification($data));
} catch (\Exception $e) {
    Log::critical('Failed to send critical email', [
        'user' => $user->id,
        'error' => $e->getMessage(),
    ]);
    // Implement fallback mechanism
}
```

### 2. Queue Non-Critical Emails

```php
// Instead of:
Mail::to($user->email)->send(new NewsletterEmail());

// Use:
Mail::to($user->email)->queue(new NewsletterEmail());
```

### 3. Use Meaningful Subject Lines

```php
// Bad
$message->subject('Notification');

// Good
$message->subject('Your order #' . $order->id . ' has been shipped');
```

### 4. Validate Email Addresses

```php
use Illuminate\Support\Facades\Validator;

$validator = Validator::make(['email' => $email], [
    'email' => 'required|email',
]);

if ($validator->fails()) {
    // Handle invalid email
    return;
}

Mail::to($email)->send(new WelcomeEmail($user));
```

### 5. Log All Email Activities

```php
Log::info('Sending welcome email', ['user' => $user->email]);
Mail::to($user->email)->send(new WelcomeEmail($user));
Log::info('Welcome email sent successfully', ['user' => $user->email]);
```
