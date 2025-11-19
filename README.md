# 📜 Usage Guide

This guide covers how to register handlers in your main script, assuming you have instantiated your bot: `$bot = new Telegram('...');`.

## 1\. Defining Handlers

All handler functions are automatically bound to the `$bot` instance, meaning you can use **`$this`** inside them to access all bot methods (like `reply`, `sendMessage`, `editMessageText`, etc.).

### 1.1 Command Handlers (`/start`, `/help`)

Use `command(string $cmd, callable $handler)` to listen for commands.

```php
// /start command
$bot->command('/start', function($message) {
    // $message is the incoming Telegram message object
    
    // Use the simplified reply method
    $this->reply('Hello! Type "info" (any case) for bot details.'); 
});
```

### 1.2 Exact Text Handlers

These match the entire text of a message exactly.

| Method | Case Sensitivity | Example Usage |
| :--- | :--- | :--- |
| `hear()` | **Case-Sensitive** | `$bot->hear('secret key', function($message) { ... });` |
| `hearCaseInsensitive()` | **Case-Insensitive** | `$bot->hearCaseInsensitive('info', function($message) { ... });` |

**Example:**

```php
// Case-insensitive match for the word "info"
$bot->hearCaseInsensitive('info', function($message) {
    // $message is the incoming Telegram message object
    $this->reply('I am a simple PHP bot by Captan.', [
        'parse_mode' => 'Markdown',
        'reply_to_message_id' => $message->message_id
    ]);
});
```

### 1.3 Handling Specific Update Types

Use `on(string $type, callable $handler)` to catch updates other than simple messages/commands (like `callback_query`, `inline_query`, etc.).

```php
// Handle an incoming button press
$bot->on('callback_query', function($query) {
    // $query is the incoming CallbackQuery object
    
    $data = $query->data; 
    
    // Call a standard API method (answerCallbackQuery) using $this
    $this->answerCallbackQuery([
        'callback_query_id' => $query->id,
        'text' => "Received: " . $data,
        'show_alert' => true
    ]);
});
```

-----

## 2\. Predefined Variables & Methods

### 2.1 The `$this` Object (The Bot)

Inside any handler, `$this` refers to the `Telegram` bot instance.

| Method | Purpose | Example |
| :--- | :--- | :--- |
| **`$this->reply(...)`** | **Primary reply method.** Sends a text message to the current chat ID. | `$this->reply('Done.', ['disable_notification' => true]);` |
| **`$this->chatId()`** | Returns the ID of the current chat, regardless of update type (message, query, etc.). | `$id = $this->chatId();` |
| **`$this->API_method(...)`** | All other Telegram API methods (e.g., `sendMessage`, `editMessageText`) can be called directly. | `$this->editMessageText(['chat_id' => ..., 'message_id' => ..., 'text' => 'New text']);` |

### 2.2 Handler Arguments

The argument passed to your handler function is the **specific update object** related to the handler type:

| Handler Type | Argument Received (Example Variable) | Telegram Object |
| :--- | :--- | :--- |
| `command()` | `$message` | `Update->message` |
| `hear()`/`hearCaseInsensitive()` | `$message` | `Update->message` |
| `on('callback_query')` | `$query` | `Update->callback_query` |
| `on('pre_checkout_query')` | `$query` | `Update->pre_checkout_query` |
| `on('inline_query')` | `$inline_query` | `Update->inline_query` |

-----

## 3\. Final Step

**Always end your script** by telling the bot to process the incoming webhook:

```php
// Run all registered handlers
$bot->run();
```

## Example Boot
```php
<?php
require_once 'Telegram.php';

$bot = new Telegram('123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZ');

// Commands: $this is the bot object, $message is the argument
$bot->command('/start', function($message) {
    $this->reply('Hello! 👋');
});

// Case-sensitive exact text
$bot->hear('hi bot', function($message) {
    $this->reply('Hi exactly!');
});

// Case-insensitive exact text
$bot->hearCaseInsensitive('Hi Bot', function($message) {
    $this->reply('Hi (any case)!', ['parse_mode' => 'Markdown']);
});

// Pre-checkout: $this is the bot object, $query is the argument
$bot->on('pre_checkout_query', function($query) {
    // You must still use the full method name for non-reply methods
    $this->answerPreCheckoutQuery([
        'pre_checkout_query_id' => $query->id,
        'ok' => false,
        'error_message' => 'Out of stock'
    ]);
});

// Run all handlers
$bot->run();
```
