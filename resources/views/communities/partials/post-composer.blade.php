<div class="create-post community-composer">
    @php
        $userMember = auth()->user() ? $group->members()->where('user_id', auth()->id())->first() : null;
        $anonName = __('messages.anonymous_participant');
    @endphp
    <div class="create-post-header">
        <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="create-post-avatar" id="comp-avatar">
        <div style="flex: 1; display: flex; align-items: center; gap: 8px;">
            <span class="create-post-author" id="comp-username">{{ auth()->user()->username }}</span>
            
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
    
    <textarea id="post-content" placeholder="{{ __('messages.whats_on_your_mind') }}" dir="auto" style="margin-top: 12px;"></textarea>
    
    <div class="post-actions">
        <div class="post-actions-left">
            <label for="comp-media" class="post-action-btn" style="cursor: pointer;">
                <i class="fas fa-image" style="color: #45bd62;"></i> <span>{{ __('messages.media') }}</span>
            </label>
            <input type="file" id="comp-media" accept="image/*,video/*" multiple style="display: none;" onchange="previewCommunityMedia(this)">
        </div>
        <button type="button" class="btn btn-primary" id="submit-comp-btn" onclick="submitCommunityPost()">
            {{ __('messages.post') }}
        </button>
    </div>

    <div id="comp-media-preview" style="display: none; margin-top: 12px;">
        <div id="comp-media-previews" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
    </div>

    <input type="hidden" id="comp-is-anon" value="0">
    <input type="hidden" id="comp-group-id" value="{{ $group->id }}">
</div>


<script>
    let commUploadedFiles = [];
    let isCommAnon = false;

    function toggleCommunityAnon(silent = false) {
        isCommAnon = !isCommAnon;
        const btn = document.getElementById('anon-toggle');
        const avatar = document.getElementById('comp-avatar');
        const username = document.getElementById('comp-username');
        const input = document.getElementById('comp-is-anon');

        if (isCommAnon) {
            btn.classList.add('active');
            avatar.src = 'https://ui-avatars.com/api/?name=Anon&background=374151&color=9ca3af';
            username.innerText = '{{ $anonName }}';
            input.value = '1';
            if (!silent && typeof showToast === 'function') showToast('{{ __("messages.anonymous_mode_enabled") }}', 'info');
        } else {
            btn.classList.remove('active');
            avatar.src = '{{ auth()->user()->avatar_url }}';
            username.innerText = '{{ auth()->user()->username }}';
            input.value = '0';
            if (!silent && typeof showToast === 'function') showToast('{{ __("messages.anonymous_mode_disabled") }}', 'info');
        }
    }

    function previewCommunityMedia(input) {
        if (!input.files || input.files.length === 0) return;
        Array.from(input.files).forEach(file => commUploadedFiles.push(file));
        renderCommMediaPreviews();
    }

    function renderCommMediaPreviews() {
        const container = document.getElementById('comp-media-preview');
        const previews = document.getElementById('comp-media-previews');
        previews.innerHTML = '';
        
        if (commUploadedFiles.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'block';
        commUploadedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 10px; overflow: hidden;';
                
                let content = file.type.startsWith('image/') 
                    ? `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`
                    : `<div style="width:100%;height:100%;background:#000;display:flex;align-items:center;justify-content:center;"><i class="fas fa-video text-white"></i></div>`;
                
                div.innerHTML = content + `<button onclick="removeCommMedia(${index})" style="position:absolute;top:2px;right:2px;background:rgba(0,0,0,0.6);color:white;border:none;border-radius:50%;width:20px;height:20px;font-size:10px;cursor:pointer;"><i class="fas fa-times"></i></button>`;
                previews.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    function removeCommMedia(index) {
        commUploadedFiles.splice(index, 1);
        renderCommMediaPreviews();
    }

    async function submitCommunityPost() {
        const content = document.getElementById('post-content').value.trim();
        const isAnon = document.getElementById('comp-is-anon').value;
        const groupId = document.getElementById('comp-group-id').value;
        const topicId = document.getElementById('composer-topic')?.value || '';
        const btn = document.getElementById('submit-comp-btn');

        if (!content && commUploadedFiles.length === 0) {
            showToast('{{ __("messages.please_enter_content_or_media") }}', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const formData = new FormData();
        formData.append('content', content);
        formData.append('is_anonymous', isAnon);
        formData.append('social_group_id', groupId);
        formData.append('social_group_topic_id', topicId);
        commUploadedFiles.forEach((file, i) => formData.append(`media[${i}]`, file));

        try {
            const response = await fetch('/posts', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                showToast(data.message, 'success');
                
                if (data.post_html) {
                    const feedList = document.querySelector('.feed-posts-list');
                    if (feedList) {
                        // Remove empty state if it exists
                        const emptyState = feedList.querySelector('.empty-state');
                        if (emptyState) emptyState.remove();

                        // Prepend the new post
                        feedList.insertAdjacentHTML('afterbegin', data.post_html);

                        // Initialize post components to resolve owner/admin dropdown permissions and state
                        if (window.initializePostComponents && data.post && data.post.id) {
                            const newPostEl = document.getElementById(`post-${data.post.id}`);
                            if (newPostEl) {
                                window.initializePostComponents(newPostEl);
                            }
                        }
                        
                        // Clear composer
                        document.getElementById('post-content').value = '';
                        commUploadedFiles = [];
                        renderCommMediaPreviews();
                        if (isCommAnon) toggleCommunityAnon(true); // Reset anon silenty
                        
                        // Reset button
                        btn.disabled = false;
                        btn.innerHTML = '{{ __("messages.post") }}';
                        
                        // Scroll to new post smoothly
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
            showToast('{{ __("messages.error_creating_post") }}', 'error');
            btn.disabled = false;
            btn.innerHTML = '{{ __("messages.post") }}';
        }
    }
</script>
