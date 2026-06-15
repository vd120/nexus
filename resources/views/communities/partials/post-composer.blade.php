<div class="create-post community-composer" id="comm-composer">
    @php
        $userMember = auth()->user() ? $group->members()->where('user_id', auth()->id())->first() : null;
        $anonName = __('messages.anonymous_participant');
    @endphp

    {{-- Pill (collapsed default) --}}
    <button type="button" class="composer-pill composer-pill-btn" id="comm-composer-pill"
            aria-label="{{ __('messages.whats_on_your_mind') }}"
            aria-expanded="false" aria-controls="comm-composer-expanded">
        <img src="{{ auth()->user()->avatar_url }}" alt="" class="avatar" id="comp-avatar-pill">
        <span class="prompt">{{ __('messages.whats_on_your_mind') }}</span>
        <span class="pill-actions">
            <span class="pill-icon-btn" aria-hidden="true"><i class="fas fa-image"></i></span>
            @if($group->allow_anonymous_posts)
                <span class="pill-icon-btn" aria-hidden="true"><i class="fas fa-user-secret"></i></span>
            @endif
        </span>
    </button>

    {{-- Expanded editor --}}
    <div class="composer-expanded" id="comm-composer-expanded">
        <div class="create-post-header">
            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="create-post-avatar" id="comp-avatar">
            <div class="create-post-author-info">
                <span class="create-post-author" id="comp-username">{{ auth()->user()->name ?: auth()->user()->username }}</span>
                <span class="create-post-handle">{{ '@' . auth()->user()->username }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                @if($group->topics->count() > 0)
                    <select id="composer-topic" class="composer-select-pill">
                        <option value="">{{ __('messages.select_topic') }}</option>
                        @foreach($group->topics as $topic)
                            <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                        @endforeach
                    </select>
                @endif
                @if($group->allow_anonymous_posts)
                    <button type="button" class="composer-pill-btn" id="anon-toggle" onclick="toggleCommunityAnon()">
                        <i class="fas fa-user-secret"></i> <span>{{ __('messages.anonymous') }}</span>
                    </button>
                @endif
            </div>
        </div>

        <label for="post-content" class="visually-hidden">{{ __('messages.whats_on_your_mind') }}</label>
        <textarea id="post-content" placeholder="{{ __('messages.whats_on_your_mind') }}" dir="auto"></textarea>

        <div class="post-actions">
            <div class="post-actions-left">
                <label for="comp-media" class="post-action-btn" style="cursor:pointer;">
                    <i class="fas fa-image" aria-hidden="true"></i> <span>{{ __('messages.media') }}</span>
                </label>
                <input type="file" id="comp-media" accept="image/*,video/*" multiple style="display:none;" onchange="previewCommunityMedia(this)">
                <button type="button" class="post-action-btn" id="comm-poll-toggle-btn" onclick="toggleCommPoll()" title="{{ __('posts.add_poll') }}" aria-label="{{ __('posts.add_poll') }}">
                    <i class="fas fa-poll" aria-hidden="true"></i> <span>{{ __('posts.add_poll') }}</span>
                </button>
                <button type="button" class="post-action-btn sensitive-pill" id="comm-sensitive-toggle" onclick="toggleCommSensitive()" aria-label="{{ __('posts.mark_as_sensitive') }}">
                    <i class="fas fa-eye-slash" aria-hidden="true"></i>
                    <span>{{ __('posts.mark_as_sensitive') }}</span>
                </button>
            </div>
            <button type="button" class="btn btn-primary" id="submit-comp-btn" onclick="submitCommunityPost()">
                {{ __('messages.post') }}
            </button>
        </div>

        <input type="hidden" id="comp-is-sensitive" value="0">
        <input type="hidden" id="comp-is-anon" value="0">
        <input type="hidden" id="comp-group-id" value="{{ $group->id }}">

        {{-- Poll builder --}}
        <div id="comm-poll-builder"
            data-label-option="{{ __('posts.poll_option_placeholder') }}"
            data-label-max="{{ __('posts.poll_max_options') }}"
            style="display:none;margin-top:12px;padding:12px;border:1px solid var(--border-color,#2a2a3e);border-radius:8px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <strong style="font-size:.875rem;">{{ __('posts.poll_add_title') }}</strong>
                <button type="button" onclick="toggleCommPoll()" aria-label="{{ __('auth.close') }}" style="background:none;border:1px solid var(--border-color,#2a2a3e);border-radius:6px;cursor:pointer;color:inherit;opacity:.7;font-size:1rem;width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;">&times;</button>
            </div>
            <input type="text" id="comm-poll-question" placeholder="{{ __('posts.poll_question_placeholder') }}" maxlength="255" style="width:100%;box-sizing:border-box;padding:.5rem .75rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;font-size:.875rem;margin-bottom:8px;">
            <div id="comm-poll-options-list">
                <div class="poll-opt-row" style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                    <input type="text" class="poll-opt-input" placeholder="{{ __('posts.poll_option_placeholder', ['n' => 1]) }}" maxlength="255" style="flex:1;box-sizing:border-box;padding:.5rem .75rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;font-size:.875rem;">
                    <button type="button" onclick="removeCommPollOption(this)" style="visibility:hidden;flex-shrink:0;background:none;border:1px solid var(--border-color,#2a2a3e);border-radius:6px;cursor:pointer;color:inherit;opacity:.6;font-size:.875rem;width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;">&times;</button>
                </div>
                <div class="poll-opt-row" style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                    <input type="text" class="poll-opt-input" placeholder="{{ __('posts.poll_option_placeholder', ['n' => 2]) }}" maxlength="255" style="flex:1;box-sizing:border-box;padding:.5rem .75rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;font-size:.875rem;">
                    <button type="button" onclick="removeCommPollOption(this)" style="visibility:hidden;flex-shrink:0;background:none;border:1px solid var(--border-color,#2a2a3e);border-radius:6px;cursor:pointer;color:inherit;opacity:.6;font-size:.875rem;width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;">&times;</button>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;flex-wrap:wrap;gap:8px;">
                <button type="button" id="comm-add-poll-option" onclick="addCommPollOption()" style="font-size:.8rem;background:none;border:1px dashed var(--primary,#8b5cf6);border-radius:6px;cursor:pointer;color:var(--primary,#8b5cf6);padding:.3rem .65rem;">{{ __('posts.poll_add_option') }}</button>
                <div style="display:flex;align-items:center;gap:6px;">
                    <label for="comm-poll-expiry" style="font-size:.8rem;opacity:.7;">{{ __('posts.poll_ends_label') }}</label>
                    <select id="comm-poll-expiry" style="font-size:.8rem;padding:.25rem .5rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;">
                        <option value="">{{ __('posts.poll_never') }}</option>
                        <option value="1d">{{ __('posts.poll_1d') }}</option>
                        <option value="3d">{{ __('posts.poll_3d') }}</option>
                        <option value="7d">{{ __('posts.poll_7d') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="comp-media-preview" style="display:none;margin-top:12px;">
            <div id="comp-media-previews" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
        </div>
        <div id="comm-upload-progress" style="display:none;margin-top:8px;">
            <div style="height:4px;background:rgba(139,92,246,0.15);border-radius:2px;overflow:hidden;">
                <div id="comm-upload-progress-fill" style="height:100%;width:0%;background:var(--primary,#8b5cf6);transition:width 0.2s ease;border-radius:2px;"></div>
            </div>
            <span id="comm-upload-progress-text" style="font-size:11px;color:var(--text-muted,#6b7280);float:right;margin-top:3px;">0%</span>
        </div>
    </div>
</div>


<script>
    let commUploadedFiles = [];
    let isCommAnon = false;

    (function () {
        const composer = document.getElementById('comm-composer');
        const pill = document.getElementById('comm-composer-pill');
        if (!composer || !pill) return;
        pill.addEventListener('click', function () {
            if (composer.classList.contains('is-open')) return;
            composer.classList.add('is-open');
            pill.setAttribute('aria-expanded', 'true');
            setTimeout(() => document.getElementById('post-content')?.focus(), 50);
        });
    })();

    function toggleCommunityAnon(silent = false) {
        isCommAnon = !isCommAnon;
        const btn = document.getElementById('anon-toggle');
        const avatar = document.getElementById('comp-avatar');
        const avatarPill = document.getElementById('comp-avatar-pill');
        const username = document.getElementById('comp-username');
        const input = document.getElementById('comp-is-anon');

        if (isCommAnon) {
            btn?.classList.add('active');
            if (avatar) avatar.src = 'https://ui-avatars.com/api/?name=Anon&background=374151&color=9ca3af';
            if (avatarPill) avatarPill.src = 'https://ui-avatars.com/api/?name=Anon&background=374151&color=9ca3af';
            if (username) username.innerText = '{{ $anonName }}';
            if (input) input.value = '1';
            if (!silent && typeof showToast === 'function') showToast('{{ __("messages.anonymous_mode_enabled") }}', 'info');
        } else {
            btn?.classList.remove('active');
            if (avatar) avatar.src = '{{ auth()->user()->avatar_url }}';
            if (avatarPill) avatarPill.src = '{{ auth()->user()->avatar_url }}';
            if (username) username.innerText = '{{ auth()->user()->name ?: auth()->user()->username }}';
            if (input) input.value = '0';
            if (!silent && typeof showToast === 'function') showToast('{{ __("messages.anonymous_mode_disabled") }}', 'info');
        }
    }

    // ── Poll builder ──
    function toggleCommPoll() {
        const builder = document.getElementById('comm-poll-builder');
        const btn = document.getElementById('comm-poll-toggle-btn');
        const isVisible = builder.style.display !== 'none';
        builder.style.display = isVisible ? 'none' : 'block';
        btn.style.opacity = isVisible ? '1' : '';
        btn.style.color = isVisible ? '' : 'var(--accent,#6366f1)';
    }

    function addCommPollOption() {
        const list = document.getElementById('comm-poll-options-list');
        const builder = document.getElementById('comm-poll-builder');
        const count = list.querySelectorAll('.poll-opt-input').length;
        const maxMsg = builder?.dataset.labelMax || 'Maximum 4 poll options.';
        const optionLabel = builder?.dataset.labelOption || 'Option :n';
        if (count >= 4) { if (window.showToast) showToast(maxMsg, 'info'); return; }

        const row = document.createElement('div');
        row.className = 'poll-opt-row';
        row.style.cssText = 'display:flex;align-items:center;gap:6px;margin-bottom:6px;';

        const inp = document.createElement('input');
        inp.type = 'text';
        inp.className = 'poll-opt-input';
        inp.placeholder = optionLabel.replace(':n', count + 1);
        inp.maxLength = 255;
        inp.style.cssText = 'flex:1;box-sizing:border-box;padding:.5rem .75rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;font-size:.875rem;';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.innerHTML = '&times;';
        btn.setAttribute('onclick', 'removeCommPollOption(this)');
        btn.style.cssText = 'flex-shrink:0;background:none;border:1px solid var(--border-color,#2a2a3e);border-radius:6px;cursor:pointer;color:inherit;opacity:.6;font-size:.875rem;width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;';

        row.appendChild(inp);
        row.appendChild(btn);
        list.appendChild(row);

        refreshCommPollDeleteBtns();
        if (count + 1 >= 4) document.getElementById('comm-add-poll-option').style.display = 'none';
        inp.focus();
    }

    function removeCommPollOption(btn) {
        const list = document.getElementById('comm-poll-options-list');
        if (list.querySelectorAll('.poll-opt-input').length <= 2) return;
        btn.closest('.poll-opt-row').remove();
        refreshCommPollDeleteBtns();
        document.getElementById('comm-add-poll-option').style.display = '';
        const builder = document.getElementById('comm-poll-builder');
        const optionLabel = builder?.dataset.labelOption || 'Option :n';
        list.querySelectorAll('.poll-opt-input').forEach((inp, i) => {
            if (!inp.value) inp.placeholder = optionLabel.replace(':n', i + 1);
        });
    }

    function refreshCommPollDeleteBtns() {
        const list = document.getElementById('comm-poll-options-list');
        const rows = list.querySelectorAll('.poll-opt-row');
        const canDelete = rows.length > 2;
        rows.forEach(row => {
            const btn = row.querySelector('button');
            if (btn) btn.style.visibility = canDelete ? 'visible' : 'hidden';
        });
    }

    function resetCommPoll() {
        const builder = document.getElementById('comm-poll-builder');
        if (builder) builder.style.display = 'none';
        const btn = document.getElementById('comm-poll-toggle-btn');
        if (btn) { btn.style.opacity = '1'; btn.style.color = ''; }
        document.getElementById('comm-poll-question').value = '';
        document.getElementById('comm-add-poll-option').style.display = '';
        const list = document.getElementById('comm-poll-options-list');
        list.innerHTML =
            '<div class="poll-opt-row" style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">' +
                '<input type="text" class="poll-opt-input" placeholder="{{ __('posts.poll_option_placeholder', ['n' => 1]) }}" maxlength="255" style="flex:1;box-sizing:border-box;padding:.5rem .75rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;font-size:.875rem;">' +
                '<button type="button" onclick="removeCommPollOption(this)" style="visibility:hidden;flex-shrink:0;background:none;border:1px solid var(--border-color,#2a2a3e);border-radius:6px;cursor:pointer;color:inherit;opacity:.6;font-size:.875rem;width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;">&times;</button>' +
            '</div>' +
            '<div class="poll-opt-row" style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">' +
                '<input type="text" class="poll-opt-input" placeholder="{{ __('posts.poll_option_placeholder', ['n' => 2]) }}" maxlength="255" style="flex:1;box-sizing:border-box;padding:.5rem .75rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;font-size:.875rem;">' +
                '<button type="button" onclick="removeCommPollOption(this)" style="visibility:hidden;flex-shrink:0;background:none;border:1px solid var(--border-color,#2a2a3e);border-radius:6px;cursor:pointer;color:inherit;opacity:.6;font-size:.875rem;width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;">&times;</button>' +
            '</div>';
    }

    // ── Sensitive toggle ──
    function toggleCommSensitive() {
        const input = document.getElementById('comp-is-sensitive');
        const btn = document.getElementById('comm-sensitive-toggle');
        if (input.value === '0') {
            input.value = '1';
            btn.classList.add('sensitive-pill--on');
            if (window.showToast) showToast('Post will be marked as sensitive content.', 'info');
        } else {
            input.value = '0';
            btn.classList.remove('sensitive-pill--on');
        }
    }

    // ── Media ──
    function compressImageFile(file) {
        if (!file.type.startsWith('image/') || file.type === 'image/gif') return Promise.resolve(file);
        if (file.size < 150 * 1024) return Promise.resolve(file);
        return new Promise(function(resolve) {
            var img = new Image();
            var url = URL.createObjectURL(file);
            img.onload = function() {
                URL.revokeObjectURL(url);
                var w = img.naturalWidth, h = img.naturalHeight;
                if (w > 1280 || h > 1280) {
                    if (w >= h) { h = Math.round(h * 1280 / w); w = 1280; }
                    else { w = Math.round(w * 1280 / h); h = 1280; }
                }
                var canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                canvas.toBlob(function(blob) {
                    if (!blob || blob.size >= file.size) { resolve(file); return; }
                    resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', 0.78);
            };
            img.onerror = function() { URL.revokeObjectURL(url); resolve(file); };
            img.src = url;
        });
    }

    async function previewCommunityMedia(input) {
        if (!input.files || input.files.length === 0) return;
        for (var i = 0; i < input.files.length; i++) {
            commUploadedFiles.push(await compressImageFile(input.files[i]));
        }
        renderCommMediaPreviews();
    }

    function renderCommMediaPreviews() {
        const container = document.getElementById('comp-media-preview');
        const previews = document.getElementById('comp-media-previews');
        previews.innerHTML = '';
        if (commUploadedFiles.length === 0) { container.style.display = 'none'; return; }
        container.style.display = 'block';
        commUploadedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.style.cssText = 'position:relative;width:80px;height:80px;border-radius:10px;overflow:hidden;';
                let content = file.type.startsWith('image/')
                    ? `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`
                    : `<div style="width:100%;height:100%;background:#000;display:flex;align-items:center;justify-content:center;"><i class="fas fa-video" style="color:white;"></i></div>`;
                div.innerHTML = content + `<button onclick="removeCommMedia(${index})" style="position:absolute;top:2px;right:2px;background:rgba(0,0,0,.6);color:white;border:none;border-radius:50%;width:20px;height:20px;font-size:10px;cursor:pointer;"><i class="fas fa-times"></i></button>`;
                previews.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    function removeCommMedia(index) {
        commUploadedFiles.splice(index, 1);
        renderCommMediaPreviews();
    }

    // ── Submit ──
    async function submitCommunityPost() {
        const content = document.getElementById('post-content').value.trim();
        const isAnon = document.getElementById('comp-is-anon').value;
        const isSensitive = document.getElementById('comp-is-sensitive').value;
        const groupId = document.getElementById('comp-group-id').value;
        const topicId = document.getElementById('composer-topic')?.value || '';
        const pollBuilder = document.getElementById('comm-poll-builder');
        const hasPoll = pollBuilder && pollBuilder.style.display !== 'none';
        const btn = document.getElementById('submit-comp-btn');

        if (!content && commUploadedFiles.length === 0 && !hasPoll) {
            showToast('{{ __("messages.please_enter_content_or_media") }}', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const formData = new FormData();
        formData.append('content', content);
        formData.append('is_anonymous', isAnon);
        formData.append('is_sensitive', isSensitive);
        formData.append('social_group_id', groupId);
        formData.append('social_group_topic_id', topicId);

        // Append poll data if builder is open
        if (hasPoll) {
            const pollQ = document.getElementById('comm-poll-question')?.value.trim();
            if (pollQ) formData.append('poll_question', pollQ);
            document.querySelectorAll('#comm-poll-options-list .poll-opt-input').forEach((inp, i) => {
                const val = inp.value.trim();
                if (val) formData.append(`poll_options[${i}]`, val);
            });
            const expiry = document.getElementById('comm-poll-expiry')?.value;
            if (expiry) {
                const days = parseInt(expiry);
                const expiresAt = new Date(Date.now() + days * 86400000).toISOString().slice(0, 19).replace('T', ' ');
                formData.append('poll_expires_at', expiresAt);
            }
        }

        commUploadedFiles.forEach((file, i) => formData.append(`media[${i}]`, file));

        try {
            const {ok, data} = await new Promise(function(resolve) {
                var xhr = new XMLHttpRequest();
                var progressBar = document.getElementById('comm-upload-progress');
                var progressFill = document.getElementById('comm-upload-progress-fill');
                var progressText = document.getElementById('comm-upload-progress-text');
                xhr.upload.onprogress = function(e) {
                    if (!e.lengthComputable || !progressBar) return;
                    var pct = Math.round(e.loaded / e.total * 100);
                    progressBar.style.display = 'block';
                    if (progressFill) progressFill.style.width = pct + '%';
                    if (progressText) progressText.textContent = pct + '%';
                };
                xhr.onload = function() {
                    if (progressBar) progressBar.style.display = 'none';
                    try { resolve({ok: xhr.status >= 200 && xhr.status < 300, data: JSON.parse(xhr.responseText)}); }
                    catch(e) { resolve({ok: false, data: {}}); }
                };
                xhr.onerror = function() {
                    if (progressBar) progressBar.style.display = 'none';
                    resolve({ok: false, data: {}});
                };
                xhr.open('POST', '/posts');
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);
            });
            if (ok && data.success) {
                showToast(data.message, 'success');
                if (data.post_html) {
                    const feedList = document.querySelector('.feed-posts-list');
                    if (feedList) {
                        const emptyState = feedList.querySelector('.empty-state');
                        if (emptyState) emptyState.remove();
                        feedList.insertAdjacentHTML('afterbegin', data.post_html);
                        if (window.initializePostComponents && data.post && data.post.id) {
                            const newPostEl = document.getElementById(`post-${data.post.id}`);
                            if (newPostEl) window.initializePostComponents(newPostEl);
                        }
                        // Reset everything
                        const composer = document.getElementById('comm-composer');
                        const pill = document.getElementById('comm-composer-pill');
                        if (composer) composer.classList.remove('is-open');
                        if (pill) pill.setAttribute('aria-expanded', 'false');
                        document.getElementById('post-content').value = '';
                        document.getElementById('comp-is-sensitive').value = '0';
                        document.getElementById('comm-sensitive-toggle')?.classList.remove('sensitive-pill--on');
                        commUploadedFiles = [];
                        renderCommMediaPreviews();
                        resetCommPoll();
                        if (isCommAnon) toggleCommunityAnon(true);
                        btn.disabled = false;
                        btn.innerHTML = '{{ __("messages.post") }}';
                        window.scrollTo({ top: feedList.offsetTop - 100, behavior: 'smooth' });
                    } else {
                        window.location.reload();
                    }
                } else {
                    window.location.reload();
                }
            } else {
                showToast(data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '{{ __("messages.post") }}';
            }
        } catch (error) {
            const pb = document.getElementById('comm-upload-progress');
            if (pb) pb.style.display = 'none';
            showToast('{{ __("messages.error_creating_post") }}', 'error');
            btn.disabled = false;
            btn.innerHTML = '{{ __("messages.post") }}';
        }
    }
</script>
