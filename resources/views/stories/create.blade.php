@extends('layouts.app')

@section('title', __('messages.create_story'))

@section('content')
@if(session('success'))
    <script>
        window.runOnPageLoad( function() {
            showToast('{{ session('success') }}', 'success');
        });
    </script>
@endif

@push('styles')
<link rel="stylesheet" href="{{ asset('css/stories-create.css') }}">
@endpush

<div class="create-story-page">
    <div class="page-header">
        <h1>{{ __('messages.create_story') }}</h1>
        <a href="{{ route('stories.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i>
            {{ __('messages.cancel') }}
        </a>
    </div>

    {{-- Story Type Selector --}}
    <div class="story-type-selector">
        <button type="button" class="story-type-btn active" data-type="media" onclick="switchStoryType('media')">
            <i class="fas fa-camera"></i>
            <span>{{ __('messages.photo_video_story') }}</span>
        </button>
        <button type="button" class="story-type-btn" data-type="text" onclick="switchStoryType('text')">
            <i class="fas fa-font"></i>
            <span>{{ __('messages.text_story') }}</span>
        </button>
    </div>

    {{-- Media Story Form --}}
    <div id="media-story-form">
    <div class="create-story-form">
        <form action="{{ route('stories.store') }}" method="POST" enctype="multipart/form-data" id="story-form">
            @csrf
            <input type="hidden" name="text_only" id="text-only" value="0">

            <div class="upload-section" id="media-upload-section">
                <label for="media" class="upload-label">
                    <div class="upload-placeholder" id="upload-placeholder">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>{{ __('messages.click_to_upload') }}</span>
                        <small>{{ __('messages.max_50mb') }}</small>
                    </div>
                    <input type="file" id="media" name="media" accept="image/*,video/*" onchange="previewMedia(this)">
                </label>

                <div class="media-preview" id="media-preview" style="display: none;">
                    <img id="image-preview" src="" alt="{{ __('messages.story') }}" style="display: none;">
                    <div id="video-container" style="display: none;">
                        <video id="video-preview" controls></video>
                        <div class="video-trimmer" id="video-trimmer">
                            <div class="trim-info">
                                <span>{{ __('messages.trim_video') }}</span>
                                <span id="trim-duration">0s</span>
                            </div>
                            <div class="trim-controls">
                                <div class="trim-input-group">
                                    <label>{{ __('messages.trim_start') }}</label>
                                    <input type="range" id="trim-start" min="0" max="60" step="0.1" value="0" oninput="updateTrimRange()">
                                    <span id="trim-start-time">0:00</span>
                                </div>
                                <div class="trim-input-group">
                                    <label>{{ __('messages.trim_end') }}</label>
                                    <input type="range" id="trim-end" min="0" max="60" step="0.1" value="60" oninput="updateTrimRange()">
                                    <span id="trim-end-time">1:00</span>
                                </div>
                            </div>
                            <div class="trim-preview">
                                <button type="button" class="btn-trim-preview" onclick="previewTrim()">
                                    <i class="fas fa-play"></i> {{ __('messages.preview_trim') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="remove-media" onclick="removeMedia()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="content">{{ __('messages.caption_optional') }}</label>
                <textarea name="content" id="content" rows="3" maxlength="5000" placeholder="{{ __('messages.add_caption') }}"></textarea>
                <span class="char-count" id="char-count">0/280</span>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary" id="submit-btn" disabled>
                    <i class="fas fa-paper-plane"></i>
                    {{ __('messages.post_story') }}
                </button>
            </div>
        </form>
    </div>
    </div>

    {{-- Text Story Form --}}
    <div id="text-story-form" style="display: none;">
    <div class="create-story-form">
        <form action="{{ route('stories.store') }}" method="POST" id="text-story-form-element">
            @csrf
            <input type="hidden" name="text_only" id="text-only-input" value="1">

            <div class="text-story-container">
                <div class="text-story-preview" id="text-story-preview">
                    <div class="text-story-content" id="text-story-preview-content">
                        {{ __('messages.text_story_preview_placeholder') }}
                    </div>
                </div>
                
                {{-- Background Color Selector --}}
                <div class="bg-color-selector">
                    <label style="font-size: 13px; font-weight: 500; color: var(--text-color); margin-bottom: 8px; display: block;">
                        {{ __('messages.choose_background_color') }}
                    </label>
                    <div class="color-options">
                        <button type="button" class="color-btn active" data-color="linear-gradient(135deg, #667eea 0%, #764ba2 100%)" onclick="changeTextStoryBackground(this)" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></button>
                        <button type="button" class="color-btn" data-color="linear-gradient(135deg, #f093fb 0%, #f5576c 100%)" onclick="changeTextStoryBackground(this)" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);"></button>
                        <button type="button" class="color-btn" data-color="linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)" onclick="changeTextStoryBackground(this)" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);"></button>
                        <button type="button" class="color-btn" data-color="linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)" onclick="changeTextStoryBackground(this)" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);"></button>
                        <button type="button" class="color-btn" data-color="linear-gradient(135deg, #fa709a 0%, #fee140 100%)" onclick="changeTextStoryBackground(this)" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);"></button>
                        <button type="button" class="color-btn" data-color="linear-gradient(135deg, #30cfd0 0%, #330867 100%)" onclick="changeTextStoryBackground(this)" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);"></button>
                        <button type="button" class="color-btn" data-color="linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)" onclick="changeTextStoryBackground(this)" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);"></button>
                        <button type="button" class="color-btn" data-color="linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)" onclick="changeTextStoryBackground(this)" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);"></button>
                        <button type="button" class="color-btn" data-color="linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)" onclick="changeTextStoryBackground(this)" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);"></button>
                        <button type="button" class="color-btn" data-color="linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%)" onclick="changeTextStoryBackground(this)" style="background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);"></button>
                    </div>
                </div>
                
                <input type="hidden" name="bg_color" id="bg-color" value="linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                
                <div class="form-group">
                    <label for="text-content">{{ __('messages.your_text_story') }}</label>
                    <textarea
                        name="content"
                        id="text-content"
                        rows="6"
                        maxlength="500"
                        required
                        placeholder="{{ __('messages.text_story_input_placeholder') }}"
                        oninput="updateTextStoryPreview(this.value)"
                    ></textarea>
                    <span class="char-count" id="text-char-count">0/500</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary" id="text-submit-btn" disabled>
                    <i class="fas fa-paper-plane"></i>
                    {{ __('messages.post_story') }}
                </button>
            </div>
        </form>
    </div>
    </div>
</div>

<script>
let videoDuration = 0;

function previewMedia(input) {
    const preview = document.getElementById('media-preview');
    const placeholder = document.getElementById('upload-placeholder');
    const imagePreview = document.getElementById('image-preview');
    const videoContainer = document.getElementById('video-container');
    const submitBtn = document.getElementById('submit-btn');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileType = file.type;

        placeholder.style.display = 'none';
        preview.style.display = 'block';

        if (fileType.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                videoContainer.style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else if (fileType.startsWith('video/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const videoPreview = document.getElementById('video-preview');
                videoPreview.src = e.target.result;
                videoContainer.style.display = 'block';
                imagePreview.style.display = 'none';
                
                // Get video duration when metadata loads
                videoPreview.onloadedmetadata = function() {
                    videoDuration = videoPreview.duration;
                    initVideoTrimmer(videoDuration);
                };
            };
            reader.readAsDataURL(file);
        }

        submitBtn.disabled = false;
    }
}

function initVideoTrimmer(duration) {
    const maxTrim = 60; // max 60 seconds
    const actualDuration = Math.min(duration, maxTrim);
    
    videoDuration = actualDuration;
    
    const trimStart = document.getElementById('trim-start');
    const trimEnd = document.getElementById('trim-end');
    const trimDuration = document.getElementById('trim-duration');
    const trimStartTime = document.getElementById('trim-start-time');
    const trimEndTime = document.getElementById('trim-end-time');
    
    // Update max values based on video duration
    trimStart.max = actualDuration;
    trimEnd.max = actualDuration;
    
    // Reset to max 60 seconds or video duration
    if (actualDuration > 60) {
        trimEnd.value = 60;
    } else {
        trimEnd.value = actualDuration;
    }
    trimStart.value = 0;
    
    updateTrimRange();
}

function updateTrimRange() {
    const trimStart = document.getElementById('trim-start');
    const trimEnd = document.getElementById('trim-end');
    const trimDuration = document.getElementById('trim-duration');
    const trimStartTime = document.getElementById('trim-start-time');
    const trimEndTime = document.getElementById('trim-end-time');
    
    let start = parseFloat(trimStart.value);
    let end = parseFloat(trimEnd.value);
    
    // Ensure start is before end
    if (start >= end) {
        start = end - 1;
        trimStart.value = start;
    }
    
    // Ensure end is at most 60 seconds after start
    if (end - start > 60) {
        end = start + 60;
        trimEnd.value = end;
    }
    
    const duration = end - start;
    trimDuration.textContent = duration.toFixed(1) + 's';
    trimStartTime.textContent = formatTime(start);
    trimEndTime.textContent = formatTime(end);
}

function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return mins + ':' + (secs < 10 ? '0' : '') + secs;
}

function previewTrim() {
    const videoPreview = document.getElementById('video-preview');
    const start = parseFloat(document.getElementById('trim-start').value);
    const end = parseFloat(document.getElementById('trim-end').value);
    
    videoPreview.currentTime = start;
    videoPreview.play();
    
    // Stop at end time
    const checkTime = setInterval(function() {
        if (videoPreview.currentTime >= end) {
            videoPreview.pause();
            videoPreview.currentTime = start;
            clearInterval(checkTime);
        }
    }, 100);
}

// Add hidden fields for trim values
document.getElementById('story-form').addEventListener('submit', function(e) {
    const videoContainer = document.getElementById('video-container');
    if (videoContainer.style.display !== 'none') {
        // Video is being uploaded - add trim values
        const trimStart = document.createElement('input');
        trimStart.type = 'hidden';
        trimStart.name = 'trim_start';
        trimStart.value = document.getElementById('trim-start').value;
        
        const trimEnd = document.createElement('input');
        trimEnd.type = 'hidden';
        trimEnd.name = 'trim_end';
        trimEnd.value = document.getElementById('trim-end').value;
        
        this.appendChild(trimStart);
        this.appendChild(trimEnd);
    }
});

function removeMedia() {
    const input = document.getElementById('media');
    const preview = document.getElementById('media-preview');
    const placeholder = document.getElementById('upload-placeholder');
    const imagePreview = document.getElementById('image-preview');
    const videoContainer = document.getElementById('video-container');
    const submitBtn = document.getElementById('submit-btn');

    input.value = '';
    preview.style.display = 'none';
    placeholder.style.display = 'block';
    imagePreview.src = '';
    document.getElementById('video-preview').src = '';
    videoContainer.style.display = 'none';
    submitBtn.disabled = true;
    videoDuration = 0;
}

// Character counter
document.getElementById('content').addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('char-count').textContent = count + '/280';
});

// Switch between media and text story
function switchStoryType(type) {
    const mediaForm = document.getElementById('media-story-form');
    const textForm = document.getElementById('text-story-form');
    const buttons = document.querySelectorAll('.story-type-btn');
    const textOnlyInput = document.getElementById('text-only');
    const textOnlyInput2 = document.getElementById('text-only-input');

    buttons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.type === type) {
            btn.classList.add('active');
        }
    });

    if (type === 'text') {
        mediaForm.style.display = 'none';
        textForm.style.display = 'block';
        if (textOnlyInput) textOnlyInput.value = '1';
        if (textOnlyInput2) textOnlyInput2.value = '1';
    } else {
        mediaForm.style.display = 'block';
        textForm.style.display = 'none';
        if (textOnlyInput) textOnlyInput.value = '0';
        if (textOnlyInput2) textOnlyInput2.value = '0';
    }
}

// Update text story preview
function updateTextStoryPreview(text) {
    const preview = document.getElementById('text-story-preview-content');
    const charCount = document.getElementById('text-char-count');
    const submitBtn = document.getElementById('text-submit-btn');
    const placeholder = "{{ __('messages.text_story_preview_placeholder') }}";

    if (text.trim()) {
        preview.textContent = text;
        preview.style.opacity = '1';
        submitBtn.disabled = false;
    } else {
        preview.textContent = placeholder;
        preview.style.opacity = '0.5';
        submitBtn.disabled = true;
    }

    charCount.textContent = text.length + '/500';
}

// Change text story background color
function changeTextStoryBackground(btn) {
    const preview = document.getElementById('text-story-preview');
    const colorInput = document.getElementById('bg-color');
    const color = btn.dataset.color;
    
    // Update preview background
    preview.style.background = color;
    
    // Update hidden input value
    colorInput.value = color;
    
    // Update active button
    document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// Form validation for media stories
document.getElementById('story-form').addEventListener('submit', function(e) {
    const textOnly = document.getElementById('text-only').value;
    const mediaInput = document.getElementById('media');
    
    // If it's a media story (not text-only), require media file
    if (textOnly === '0' && (!mediaInput.files || !mediaInput.files[0])) {
        e.preventDefault();
        showToast('Please upload a photo or video', 'error');
        return false;
    }
});
</script>
@endsection
