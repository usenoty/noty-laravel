# Noty Laravel

[![Latest Version](https://img.shields.io/packagist/v/usenoty/noty-laravel.svg)](https://packagist.org/packages/usenoty/noty-laravel)
[![License](https://img.shields.io/packagist/l/usenoty/noty-laravel.svg)](https://packagist.org/packages/usenoty/noty-laravel)
[![Tests](https://github.com/noty/noty-laravel/workflows/Tests/badge.svg)](https://github.com/noty/noty-laravel/actions)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%20|%208.2%20|%208.3-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-10.x%20|%2011.x%20|%2012.x-red.svg)](https://laravel.com)

A **non-blocking** notification channel for Laravel that sends events to Noty API. Perfect for tracking user activities, application events, and telemetry data without impacting your app's performance.

## ✨ Features

- 🚀 **Non-Blocking**: Uses async HTTP requests that don't slow down your app
- 🛡️ **Fail-Silent**: Never breaks your app, even if the tracking service is down
- ⚡ **Auto-Flush**: Automatically sends pending events after response is sent
- 🎯 **Simple API**: Just like Sentry - use `captureEvent()` or Laravel notifications
- 💎 **Fluent Builder**: Type-safe `NotyMessage` class for elegant event building
- 🔄 **Queue Support**: Optional queue-based transport for high-volume applications
- 📊 **Rich Events**: Support for actions, tags, attachments, and emojis

## 📦 Installation

Install via Composer:

```bash
composer require usenoty/noty-laravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Noty\Laravel\Providers\NotyServiceProvider"
```

## ⚙️ Configuration

Set your Noty API credentials in `.env`:

```env
# API Configuration
NOTY_DSN=http://localhost:3020
NOTY_TOKEN=p7362mdlvmixjbvle01q7oeooqhfzq33

# Define your channels (named channels or channel IDs)
NOTY_DEFAULT_CHANNEL=general
NOTY_CHANNEL_GENERAL=channel_Arys9gID0J0HKv
NOTY_CHANNEL_AUTH=channel_xyz123auth
NOTY_CHANNEL_PAYMENTS=channel_abc456pay

# Transport (http or queue)
NOTY_TRANSPORT=http
NOTY_DEFAULT_PRIORITY=MEDIUM
```

## 🚀 Quick Start

### Method 1: Using NotyMessage (Recommended)

The `NotyMessage` class provides a fluent, type-safe API:

```php
use Noty\Laravel\NotyMessage;

NotyMessage::create('Order Created')
    ->message('Order #1234 has been created')
    ->channel('orders')
    ->priority(NotyMessage::PRIORITY_HIGH)
    ->action('View Order', route('orders.show', 1234), true)
    ->tag('order_id', 1234)
    ->tag('amount', 99.99)
    ->emoji('🛒')
    ->send();
```

### Method 2: Using Laravel Notifications

Create a notification class:

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;

class UserLoggedIn extends Notification
{
    public function __construct(private $user) {}
    
    public function via($notifiable)
    {
        return ['noty'];
    }
    
    public function toNoty($notifiable)
    {
        return [
            'title' => 'User logged in: ' . $this->user->email,
            'message' => 'User logged in from IP: ' . request()->ip(),
            'channel' => 'auth',
            'priority' => 'HIGH',
            'actions' => [
                [
                    'name' => 'View Profile',
                    'url' => route('users.show', $this->user),
                    'browser' => true
                ]
            ],
            'tags' => [
                'ip_address' => request()->ip(),
                'user_id' => $this->user->id,
            ]
        ];
    }
}
```

Send the notification:

```php
$user->notify(new UserLoggedIn($user));
```

### Method 3: Using captureEvent (Like Sentry)

```php
use Noty\Laravel\Facades\Noty;

Noty::captureEvent([
    'title' => 'Payment Received',
    'message' => 'Payment of $100.00 received',
    'channel' => 'payments',
    'priority' => 'HIGH',
    'actions' => [
        [
            'name' => 'View Order',
            'url' => route('orders.show', $order->id),
            'browser' => true
        ]
    ],
    'tags' => [
        'order_id' => $order->id,
        'amount' => 100.00,
        'currency' => 'USD',
    ]
]);
```

Or using the helper function:

```php
noty()->captureEvent([
    'title' => 'User Registered',
    'channel' => 'auth',
]);
```

## 📋 NotyMessage API Reference

### Basic Usage

```php
use Noty\Laravel\NotyMessage;

// Simple notification
NotyMessage::create('Order Created')
    ->channel('orders')
    ->send();

// With message and priority
NotyMessage::create('Payment Received')
    ->message('Payment of $100.00 received')
    ->channel('payments')
    ->priority(NotyMessage::PRIORITY_HIGH)
    ->send();
```

### Priority Constants

```php
NotyMessage::PRIORITY_HIGH    // High priority
NotyMessage::PRIORITY_MEDIUM  // Medium priority (default)
NotyMessage::PRIORITY_LOW     // Low priority
```

### Actions

```php
// Single action
NotyMessage::create('Order #123')
    ->action('View Order', route('orders.show', 123), true)
    ->send();

// Multiple actions
NotyMessage::create('Order #123')
    ->action('View Order', route('orders.show', 123), true)
    ->action('Download Invoice', route('orders.invoice', 123))
    ->send();

// Actions array
NotyMessage::create('Order #123')
    ->actions([
        ['name' => 'View Order', 'url' => route('orders.show', 123), 'browser' => true],
        ['name' => 'Download Invoice', 'url' => route('orders.invoice', 123)]
    ])
    ->send();
```

### Tags

```php
// Single tag
NotyMessage::create('User Login')
    ->tag('user_id', $user->id)
    ->send();

// Multiple tags
NotyMessage::create('User Login')
    ->tag('user_id', $user->id)
    ->tag('ip_address', request()->ip())
    ->tags(['browser' => 'Chrome', 'os' => 'MacOS'])
    ->send();
```

### Emojis

```php
NotyMessage::create('New Event')
    ->emoji('🎉')
    ->message('A new event has been created')
    ->send();
```

## 🔧 Channel Configuration

### Named Channels (Recommended)

Define channel names in `.env`:

```env
NOTY_CHANNEL_AUTH=channel_Arys9gID0J0HKv
NOTY_CHANNEL_PAYMENTS=channel_abc123xyz
NOTY_CHANNEL_ORDERS=channel_def456uvw
```

Use in code:

```php
NotyMessage::create('Event')
    ->channel('auth') // Uses channel_Arys9gID0J0HKv
    ->send();
```

### Direct Channel IDs

```php
NotyMessage::create('Event')
    ->channel('channel_Arys9gID0J0HKv')
    ->send();
```

## 🚀 Transport Options

### HTTP Transport (Default)

Events are sent asynchronously via HTTP after the response is sent to the user.

```env
NOTY_TRANSPORT=http
```

### Queue Transport

For high-volume applications, use queue transport:

```env
NOTY_TRANSPORT=queue
NOTY_QUEUE_CONNECTION=redis
NOTY_QUEUE_NAME=noty-events
NOTY_QUEUE_BATCH_SIZE=10
```

## 📊 Event Data Structure

Events are sent to the Noty API with the following structure:

```json
{
  "channel": "channel_Arys9gID0J0HKv",
  "title": "User logged in: user@example.com",
  "message": "User logged in from IP: 172.38.21.134",
  "priority": "HIGH",
  "actions": [
    {
      "name": "View Profile",
      "url": "https://app.example.com/users/123",
      "browser": true
    }
  ],
  "attachments": [],
  "tags": {
    "ip_address": "172.38.21.134",
    "user_email": "user@example.com"
  }
}
```

## 🔄 How It Works

1. **Non-Blocking**: When you call `captureEvent()`, the event is queued but not sent immediately
2. **Async Requests**: HTTP requests are made asynchronously using Guzzle promises
3. **Auto-Flush**: After Laravel sends the response, pending events are flushed in the background
4. **Fail-Silent**: If the Noty API is unreachable, your app continues working normally

```
Request → Your App Logic → Response to User → Flush Events (async)
                ↓
          captureEvent() stores promise
```

## 📝 Complete Example

```php
use Noty\Laravel\Facades\Noty;

// In a controller
public function login(Request $request)
{
    $user = Auth::user();
    
    // Using NotyMessage (recommended)
    NotyMessage::create('User Login')
        ->message($user->email . ' logged in')
        ->channel('auth')
        ->priority(NotyMessage::PRIORITY_HIGH)
        ->action('View Profile', route('users.show', $user), true)
        ->tag('ip', request()->ip())
        ->tag('user_id', $user->id)
        ->emoji('🔐')
        ->send();
    
    return redirect('/dashboard');
}
```

## 🎯 When to Use Each Method

- **NotyMessage**: Best for most cases - type-safe, fluent, and readable
- **Laravel Notifications**: Best for reusable notifications sent to multiple users
- **captureEvent**: Best for simple, one-off events or custom builders

## 📋 Requirements

- **PHP**: 8.1, 8.2, or 8.3
- **Laravel**: 10.x, 11.x, or 12.x
- **Guzzle**: 7.x

## 🔄 Supported Versions

| Laravel | PHP  | Status |
|---------|------|--------|
| 12.x    | 8.2+ | ✅ Active |
| 11.x    | 8.2+ | ✅ Active |
| 10.x    | 8.1+ | ✅ Active |
| 9.x     | 8.0+ | ❌ Unsupported |
| 8.x     | 7.3+ | ❌ Unsupported |

## 📄 License

MIT License

---

Made with ❤️ for Laravel developers