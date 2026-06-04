@extends('layouts.app')

@section('title', __('posts.post_analytics'))

@push('styles')
<style>
.analytics-page {
    max-width: 720px;
    margin: 2rem auto;
    padding: 0 1rem;
}

/* Back button — styled card pill matching the post detail page pattern */
.analytics-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.analytics-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 15px;
    font-weight: 600;
    padding: 10px 16px;
    border-radius: 12px;
    background: var(--surface);
    border: 1px solid var(--border);
    transition: all 0.3s ease;
}

.analytics-back-link:hover {
    background: var(--surface-hover);
    color: var(--text);
    transform: translateX(-4px);
}

.analytics-back-link i {
    font-size: .875rem;
    width: auto;
    margin: 0;
}

.analytics-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
}

/* Stats grid */
.analytics-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: .75rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}

[data-theme="light"] .stat-card {
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

.stat-card i {
    font-size: 1.25rem;
    opacity: .55;
    margin-bottom: .5rem;
    display: flex;
    justify-content: center;
    width: 100%;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1;
}

.stat-label {
    font-size: .78rem;
    opacity: .55;
    margin-top: .25rem;
}

/* Chart card */
.analytics-chart-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}

[data-theme="light"] .analytics-chart-card {
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

.chart-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 1.25rem;
    color: var(--text-muted);
}

/* Responsive */
@media (max-width: 640px) {
    .analytics-page {
        margin: 1rem auto;
        padding: 0 .75rem;
    }

    .analytics-header {
        flex-direction: column;
        align-items: flex-start;
        gap: .5rem;
    }

    .analytics-back-link {
        font-size: 14px;
        padding: 8px 14px;
    }

    .analytics-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: .5rem;
    }

    .stat-card {
        padding: 1rem .75rem;
    }

    .stat-value {
        font-size: 1.25rem;
    }

    .analytics-chart-card {
        padding: 1rem;
    }

    .chart-title {
        font-size: .875rem;
        margin-bottom: 1rem;
    }
}

@media (max-width: 380px) {
    .analytics-stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: .375rem;
    }

    .stat-card {
        padding: .75rem .5rem;
    }

    .stat-value {
        font-size: 1.1rem;
    }
}

/* Poll analytics */
.analytics-poll-section {
    margin-top: 1.5rem;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
[data-theme="light"] .analytics-poll-section {
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.analytics-poll-section .chart-title {
    display: flex;
    align-items: center;
    gap: 8px;
}
.poll-analytics-meta {
    font-size: .8rem;
    opacity: .55;
    margin: 0 0 1.25rem;
}
.poll-analytics-card {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}
.poll-analytics-card:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.poll-analytics-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}
.poll-analytics-option-text {
    font-weight: 600;
    font-size: .875rem;
    flex: 1;
}
.poll-analytics-pct {
    font-weight: 700;
    font-size: .875rem;
    color: var(--primary);
}
.poll-analytics-count {
    font-size: .78rem;
    opacity: .55;
}
.poll-analytics-bar-bg {
    height: 6px;
    background: var(--border-color);
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 10px;
}
.poll-analytics-bar {
    height: 100%;
    background: var(--primary);
    border-radius: 999px;
    transition: width .4s ease;
    opacity: .7;
}
.poll-analytics-voters {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.poll-analytics-voter {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px 4px 4px;
    border-radius: 999px;
    background: var(--surface);
    border: 1px solid var(--border);
    text-decoration: none;
    color: var(--text);
    font-size: .78rem;
    transition: background .15s;
}
.poll-analytics-voter:hover {
    background: var(--surface-hover);
}
.poll-analytics-avatar {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    object-fit: cover;
}
.poll-analytics-name {
    font-weight: 500;
}
</style>
@endpush

@section('content')
<div class="analytics-page">
    <div class="analytics-header">
        <a href="{{ route('posts.show', $post) }}" class="analytics-back-link">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            {{ __('posts.back_to_post') }}
        </a>
        <h1 class="analytics-title">{{ __('posts.post_analytics') }}</h1>
    </div>

    {{-- Stats grid --}}
    <div class="analytics-stats-grid">
        @foreach([
            ['icon' => 'fa-eye',        'label' => __('posts.stat_views'),      'value' => number_format($totalViews)],
            ['icon' => 'fa-heart',      'label' => __('posts.stat_likes'),      'value' => number_format($totalLikes)],
            ['icon' => 'fa-comment',    'label' => __('posts.stat_comments'),   'value' => number_format($totalComments)],
            ['icon' => 'fa-bookmark',   'label' => __('posts.stat_saves'),      'value' => number_format($totalSaves)],
            ['icon' => 'fa-chart-line', 'label' => __('posts.stat_engagement'), 'value' => $engagementRate . '%'],
        ] as $stat)
        <div class="stat-card">
            <i class="fas {{ $stat['icon'] }}" aria-hidden="true"></i>
            <div class="stat-value">{{ $stat['value'] }}</div>
            <div class="stat-label">{{ $stat['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Views over time chart --}}
    <div class="analytics-chart-card">
        <h2 class="chart-title">{{ __('posts.views_last_30_days') }}</h2>
        <canvas id="views-chart" height="120"></canvas>
    </div>

    {{-- Poll analytics --}}
    @if($pollData)
    <div class="analytics-poll-section">
        <h2 class="chart-title"><i class="fas fa-poll" aria-hidden="true"></i> {{ $pollData['question'] }}</h2>
        <p class="poll-analytics-meta">{{ $pollData['total_votes'] }} {{ trans_choice('posts.poll_vote', $pollData['total_votes']) }}</p>
        @foreach($pollData['options'] as $option)
        <div class="poll-analytics-card">
            <div class="poll-analytics-header">
                <span class="poll-analytics-option-text">{{ $option['text'] }}</span>
                <span class="poll-analytics-pct">{{ $option['percentage'] }}%</span>
                <span class="poll-analytics-count">({{ $option['votes_count'] }})</span>
            </div>
            <div class="poll-analytics-bar-bg">
                <div class="poll-analytics-bar" style="width:{{ $option['percentage'] }}%"></div>
            </div>
            @if(count($option['voters']) > 0)
            <div class="poll-analytics-voters">
                @foreach($option['voters'] as $voter)
                <a href="{{ route('users.show', $voter['username']) }}" class="poll-analytics-voter">
                    <img src="{{ $voter['avatar'] ?? asset('images/default-avatar.svg') }}" alt="" class="poll-analytics-avatar" loading="lazy">
                    <span class="poll-analytics-name">{{ $voter['username'] }}</span>
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const viewsData = @json($viewsByDay);

// Fill in missing days with 0
const labels = [], counts = [];
for (let i = 29; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    const key = d.toISOString().slice(0, 10);
    labels.push(key.slice(5)); // MM-DD
    counts.push(viewsData[key] || 0);
}

const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
const tickColor = isDark ? 'rgba(255,255,255,.4)' : 'rgba(0,0,0,.35)';
const gridColor = isDark ? 'rgba(255,255,255,.05)' : 'rgba(0,0,0,.08)';

const ctx = document.getElementById('views-chart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Views',
            data: counts,
            backgroundColor: isDark ? 'rgba(99,102,241,.5)' : 'rgba(99,102,241,.35)',
            borderColor: 'rgba(99,102,241,1)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, color: tickColor }, grid: { color: gridColor } },
            x: { ticks: { color: tickColor, maxRotation: 45 }, grid: { display: false } }
        }
    }
});
</script>
@endpush
