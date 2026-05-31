@extends('layouts.app')

@section('title', __('hashtags.hashtags'))

@push('styles')
@vite('resources/css/hashtag-index.css')
@endpush

@section('content')
<div class="hashtags-index-page">
    {{-- Header --}}
    <div class="page-header">
        <a href="{{ route('home') }}" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1>{{ __('hashtags.trending') }}</h1>
            <p>{{ __('hashtags.trending_subtitle') }}</p>
        </div>
    </div>

    {{-- Search Box --}}
    <div class="search-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="hashtag-search" placeholder="{{ __('hashtags.search_placeholder') }}" onkeyup="filterHashtags()">
            <button type="button" class="search-clear" onclick="clearSearch()" style="display: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    {{-- Top 5 Hashtags --}}
    @if($topHashtags->count() > 0)
    <div class="top-hashtags-section">
        <h2 class="section-title">
            <i class="fas fa-fire"></i> {{ __('hashtags.top_hashtags') }}
        </h2>
        <div class="hashtags-grid top-grid">
            @foreach($topHashtags as $hashtag)
            <a href="{{ route('hashtags.show', $hashtag->slug) }}" class="hashtag-card {{ $loop->index < 3 ? 'top-' . ($loop->index + 1) : '' }}">
                <div class="hashtag-card-header">
                    @if($loop->index < 3)
                    <span class="rank-badge">{{ $loop->index + 1 }}</span>
                    @endif
                    <i class="fas fa-hashtag"></i>
                    <span class="hashtag-name">{{ $hashtag->name }}</span>
                </div>
                <div class="hashtag-card-stats">
                    <div class="stat">
                        <i class="fas fa-file-alt"></i>
                        <span>{{ __('hashtags.posts_count', ['count' => $hashtag->usage_count]) }}</span>
                    </div>
                </div>
                <div class="hashtag-popularity">
                    <div class="popularity-bar" style="width: {{ min(100, ($hashtag->usage_count / $topHashtags->first()->usage_count) * 100) }}%"></div>
                </div>
            </a>
            @endforeach
        </div>

        {{-- View All Button --}}
        <div class="view-all-section">
            <button type="button" class="btn btn-primary btn-lg" onclick="toggleAllHashtags()">
                <i class="fas fa-list"></i> {{ __('hashtags.view_all_hashtags') }}
            </button>
        </div>
    </div>

    {{-- All Hashtags (Hidden by default) --}}
    @if($allHashtags->count() > 0)
    <div id="all-hashtags-section" class="all-hashtags-section" style="display: none;">
        <h2 class="section-title">
            <i class="fas fa-hashtag"></i> {{ __('hashtags.all_hashtags') }}
        </h2>
        <div class="hashtags-grid">
            @foreach($allHashtags as $hashtag)
            <a href="{{ route('hashtags.show', $hashtag->slug) }}" class="hashtag-card">
                <div class="hashtag-card-header">
                    <i class="fas fa-hashtag"></i>
                    <span class="hashtag-name">{{ $hashtag->name }}</span>
                </div>
                <div class="hashtag-card-stats">
                    <div class="stat">
                        <i class="fas fa-file-alt"></i>
                        <span>{{ __('hashtags.posts_count', ['count' => $hashtag->usage_count]) }}</span>
                    </div>
                </div>
                <div class="hashtag-popularity">
                    <div class="popularity-bar" style="width: {{ min(100, ($hashtag->usage_count / $allHashtags->first()->usage_count) * 100) }}%"></div>
                </div>
            </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pagination-section">
            {{ $allHashtags->links() }}
        </div>

        {{-- Close Button --}}
        <div class="view-all-section">
            <button type="button" class="btn btn-secondary btn-lg" onclick="toggleAllHashtags()">
                <i class="fas fa-times"></i> {{ __('hashtags.close') }}
            </button>
        </div>
    </div>
    @endif
    @else
    <div class="empty-state">
        <i class="fas fa-hashtag"></i>
        <h3>{{ __('hashtags.no_hashtags') }}</h3>
        <p>{{ __('hashtags.no_hashtags_message') }}</p>
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="fas fa-home"></i> {{ __('hashtags.browse_posts') }}
        </a>
    </div>
    @endif
</div>

<script>
function toggleAllHashtags() {
    const allSection = document.getElementById('all-hashtags-section');
    if (allSection) {
        const isHidden = allSection.style.display === 'none';
        allSection.style.display = isHidden ? 'block' : 'none';
        if (isHidden) {
            allSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}

function filterHashtags() {
    const searchInput = document.getElementById('hashtag-search');
    const filter = searchInput.value.toLowerCase();
    const cards = document.querySelectorAll('.hashtag-card');
    let visibleCount = 0;

    // Show/hide search clear button
    const clearBtn = document.querySelector('.search-clear');
    if (clearBtn) {
        clearBtn.style.display = filter ? 'flex' : 'none';
    }

    cards.forEach(card => {
        const hashtagName = card.querySelector('.hashtag-name').textContent.toLowerCase();
        if (hashtagName.includes(filter)) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Show/hide section titles based on visible cards
    const topSection = document.querySelector('.top-hashtags-section');
    const allSection = document.getElementById('all-hashtags-section');
    
    if (topSection) {
        const topCards = topSection.querySelectorAll('.hashtag-card:not([style*="display: none"])');
        topSection.style.display = topCards.length > 0 ? '' : 'none';
    }
    
    if (allSection) {
        const allCards = allSection.querySelectorAll('.hashtag-card:not([style*="display: none"])');
        allSection.style.display = allCards.length > 0 ? '' : 'none';
    }
}

function clearSearch() {
    const searchInput = document.getElementById('hashtag-search');
    if (searchInput) {
        searchInput.value = '';
        filterHashtags();
        searchInput.focus();
    }
}
</script>
@endsection
