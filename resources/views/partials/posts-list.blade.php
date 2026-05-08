@forelse($posts as $post)
    @include('partials.post', ['post' => $post, 'hideGroupContext' => $hideGroupContext ?? false])
@empty
    @if(!isset($skipEmpty) || !$skipEmpty)
    <div class="empty-state">
        <i class="fas fa-newspaper"></i>
        <h3>{{ __('messages.no_posts_yet') }}</h3>
        <p>{{ __('messages.be_first_to_post') }}</p>
    </div>
    @endif
@endforelse
