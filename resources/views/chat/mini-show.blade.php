<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/chat-show.css') }}">
    <style>
        body { background: var(--surface, #111b21); padding: 0; margin: 0; }
        .chat-messages { height: 260px; overflow-y: auto; background: var(--bg, #111b21); padding: 10px; }
        .mini-chat-input-area {
            padding: 8px;
            border-top: 1px solid var(--border, #2f3b43);
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--surface, #202c33);
        }
        #mini-input {
            flex: 1;
            background: var(--bg, #111b21);
            border-radius: 20px;
            padding: 6px 12px;
            border: none;
            color: var(--text, #e9edef);
            outline: none;
        }
        button { background: var(--primary, #00a884); color: white; border: none; padding: 6px 12px; border-radius: 20px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="chat-messages">
        @include('chat.partials.messages', ['messages' => $messages])
    </div>
    <div class="mini-chat-input-area">
        <input type="text" id="mini-input" placeholder="{{ __('chat.type_a_message') }}">
        <button onclick="sendMessage()">{{ __('chat.send') }}</button>
    </div>
    <script>
        function sendMessage() {
            const input = document.getElementById('mini-input');
            const content = input.value.trim();
            if (!content) return;
            
            // Re-use logic or fetch from parent
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ content })
            }).then(() => { input.value = ''; });
        }
    </script>
</body>
</html>
