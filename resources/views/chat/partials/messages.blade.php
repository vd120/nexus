@php
    $lastDate = null;
    $unreadDividerShown = false;
@endphp
@forelse($messages as $message)
    @php
        $msgDate = $message->created_at->format('Y-m-d');
        $showDate = ($lastDate !== $msgDate);
        $lastDate = $msgDate;
    @endphp

    @if(!$unreadDividerShown && !$message->is_mine && !$message->isReadByUser(auth()->id()) && $message->type !== 'system' && $message->content !== 'system_cleared')
        <div class="chat-date-divider unread-divider">
            <span dir="auto">{{ __('chat.unread_messages') }}</span>
        </div>
        @php $unreadDividerShown = true; @endphp
    @endif
    
    @if($showDate)
        <div class="chat-date-divider" data-date="{{ $msgDate }}">
            <span dir="auto">
                @if($message->created_at->isToday())
                    {{ __('messages.today') }}
                @elseif($message->created_at->isYesterday())
                    {{ __('messages.yesterday') }}
                @else
                    {{ $message->created_at->format('d/m/Y') }}
                @endif
            </span>
        </div>
    @endif
    
    @if($message->type === 'system' || ($message->type === 'text' && $message->content === 'system_cleared'))
        <div class="system-message">
            <span class="system-text" dir="auto">
                @if($message->content === 'system_cleared')
                    {{ $message->is_mine ? __('chat.you_cleared_the_chat') : __('chat.cleared_the_chat', ['user' => $message->sender->username ?? 'User']) }}
                @else
                    {{ $message->content }}
                @endif
            </span>
            <span class="system-time">{{ $message->created_at->format('h:i a') }}</span>
        </div>
    @else
        <div class="message {{ $message->is_mine ? 'own' : 'other' }}" id="message-{{ $message->id }}">
            <div class="message-bubble">
                <div class="message-content">
                    <span class="text" dir="auto">{{ $message->content }}</span>
                </div>
                <div class="message-time">
                    {{ $message->created_at->format('h:i a') }}
                    @if($message->is_mine)
                        <i class="fas {{ $message->isReadByUser() ? 'fa-check-double' : 'fa-check' }}"></i>
                    @endif
                </div>
            </div>
        </div>
    @endif
@empty
    <div class="no-messages">
        <i class="fas fa-comments"></i>
        <p>{{ __('chat.no_messages') }}</p>
    </div>
@endforelse
