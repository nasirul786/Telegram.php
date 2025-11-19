<?php // Telegram.php

class Telegram {
    private string $token;
    public object $update;
    private array $commandHandlers = [];
    private array $hearHandlers = [];

    public function __construct(string $token) {
        $this->token = $token;
        $raw = file_get_contents('php://input');
        $this->update = json_decode($raw) ?: (object)[];
    }

    public function __call($method, $args) {
        $params = $args[0] ?? [];
        $url = "https://api.telegram.org/bot{$this->token}/{$method}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result);
    }

    // New simplified reply method
    public function reply(string $text, array $options = []) {
        $chatId = $this->chatId();
        
        // Ensure we have a chat ID before trying to send
        if (!$chatId) {
            // Log or handle error if chatId is null
            return false;
        }

        $params = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $options);

        // Uses __call to execute the sendMessage API method
        return $this->sendMessage($params);
    }
    
    // Register command (/start, /help)
    public function command(string $cmd, callable $handler) {
        $this->commandHandlers[$cmd] = $handler;
    }

    // Exact match, case-sensitive
    public function hear(string $text, callable $handler) {
        $this->hearHandlers[] = ['text' => $text, 'case_sensitive' => true, 'handler' => $handler];
    }

    // Exact match, case-insensitive
    public function hearCaseInsensitive(string $text, callable $handler) {
        $this->hearHandlers[] = ['text' => $text, 'case_sensitive' => false, 'handler' => $handler];
    }

    // Any update type
    public function on(string $type, callable $handler) {
        if (isset($this->update->{$type})) {
            // Bind handler to 'this' for 'on' method as well
            if ($handler instanceof Closure) {
                $handler = $handler->bindTo($this, $this);
            }
            $handler($this->update->{$type}); // Pass only the update data
        }
    }

    public function chatId() {
        return $this->update->message->chat->id
            ?? $this->update->callback_query?->message?->chat?->id
            ?? $this->update->pre_checkout_query?->from?->id ?? null;
    }

    public function run() {
        $message = $this->update->message ?? null;

        // Handle commands
        if ($message && isset($message->entities)) {
            foreach ($message->entities as $entity) {
                if ($entity->type === 'bot_command') {
                    $cmdText = substr($message->text, $entity->offset, $entity->length);
                    $cmd = explode(' ', $cmdText)[0];
                    $cmd = strtolower($cmd);

                    foreach ($this->commandHandlers as $registeredCmd => $handler) {
                        if (strtolower($registeredCmd) === $cmd) {
                            // **CRITICAL CHANGE: Bind closure to $this**
                            if ($handler instanceof Closure) {
                                $handler = $handler->bindTo($this, $this);
                            }
                            $handler($message); // Pass only $message
                            exit;
                        }
                    }
                }
            }
        }

        // Handle hear / HEAR
        if ($message && $message->text ?? false) {
            $text = $message->text;
            foreach ($this->hearHandlers as $h) {
                $match = $h['case_sensitive']
                    ? ($text === $h['text'])
                    : (strcasecmp($text, $h['text']) === 0);

                if ($match) {
                    // **CRITICAL CHANGE: Bind closure to $this**
                    $handler = $h['handler'];
                    if ($handler instanceof Closure) {
                        $handler = $handler->bindTo($this, $this);
                    }
                    $handler($message); // Pass only $message
                    exit;
                }
            }
        }
    }
}
