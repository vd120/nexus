@extends('layouts.app')

@section('title', __('messages.discover_communities_title'))

@section('content')
<div class="discover-page">
    {{-- Header --}}
    <div class="discover-header">
        <div class="text" style="display: flex; align-items: flex-start; gap: 12px;">
            <a href="javascript:history.back()" class="btn back-btn" style="width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--surface); border: 1px solid var(--border); color: var(--text); padding: 0; min-width: 44px; margin-top: 5px;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title">{{ __('messages.communities_header') }}</h1>
                <p class="page-subtitle">{{ __('messages.communities_subtitle') }}</p>
            </div>
        </div>
        <button class="btn approve create-btn" onclick="openCreateGroupModal()">
            <i class="fas fa-plus"></i> {{ __('messages.create_new') }}
        </button>
    </div>

    {{-- My Communities (Horizontal Scroll) --}}
    @if($myGroups->count() > 0)
    <section class="section">
        <h2 class="section-title">{{ __('messages.your_communities') }}</h2>
        <div class="horizontal-scroll-container">
            <div class="horizontal-scroll">
                @foreach($myGroups as $group)
                    <a href="{{ route('communities.show', $group->slug) }}" class="mini-card">
                        <div class="mini-content-alt">
                            <img src="{{ $group->avatar_url }}" alt="" class="mini-avatar-alt">
                            <div class="mini-info">
                                <strong>{{ $group->name }}</strong>
                                <span>{{ number_format($group->members_count) }} {{ __('messages.members_label') }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Discovery Grid --}}
    <section class="section">
        <div class="discovery-header">
            <h2 class="section-title">{{ __('messages.discover') }}</h2>
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="community-search" placeholder="{{ __('messages.search_communities') }}">
            </div>
        </div>
        
        <div class="discovery-grid" id="groups-grid">
            @forelse($groups as $group)
                <a href="{{ route('communities.show', $group->slug) }}" class="discovery-card">
                    <div class="card-body-alt">
                        <img src="{{ $group->avatar_url }}" alt="" class="card-avatar-alt">
                        <div class="card-text">
                            <h3>{{ $group->name }}</h3>
                            <p>{{ Str::limit($group->description, 80) }}</p>
                        </div>
                        <div class="card-footer">
                            <span class="member-tag"><i class="fas fa-users"></i> {{ number_format($group->members_count) }}</span>
                            @if(in_array($group->id, $joinedIds))
                                <span class="joined-badge"><i class="fas fa-check-circle"></i> {{ __('messages.joined') }}</span>
                            @else
                                <span class="join-hint">{{ __('messages.enter') }} <i class="fas fa-arrow-right"></i></span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="empty-state-card">
                    <div class="empty-icon"><i class="fas fa-search"></i></div>
                    <h3>{{ __('messages.no_communities_found') }}</h3>
                    <p>{{ __('messages.no_communities_desc') }}</p>
                </div>
            @endforelse
        </div>
    </section>

    <div class="pagination">
        {{ $groups->links() }}
    </div>
</div>

{{-- Create Modal --}}
<div id="createGroupModal" class="nexus-modal-overlay" style="display: none;" onclick="closeCreateGroupModal(event)">
    <div class="nexus-modal-card" onclick="event.stopPropagation()">
        <div class="nexus-modal-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h3>{{ __('messages.create_community_title') }}</h3>
                    <p>{{ __('messages.create_community_subtitle') }}</p>
                </div>
            </div>
            <button class="close-btn" onclick="closeCreateGroupModal()"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('communities.store') }}" method="POST">
            @csrf
            <div class="nexus-modal-body">
                <div class="nexus-form-group">
                    <label>{{ __('messages.community_name') }}</label>
                    <div class="input-wrapper">
                        <i class="fas fa-signature"></i>
                        <input type="text" name="name" required placeholder="{{ __('messages.community_name_placeholder') }}">
                    </div>
                </div>
                <div class="nexus-form-group">
                    <label>{{ __('messages.group_description') }}</label>
                    <div class="input-wrapper textarea-wrapper">
                        <i class="fas fa-align-left"></i>
                        <textarea name="description" required rows="3" placeholder="{{ __('messages.description_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="grid-2-nexus">
                    <div class="nexus-form-group">
                        <label>{{ __('messages.privacy_level_label') }}</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <select name="privacy_level">
                                <option value="public">{{ __('messages.public_desc') }}</option>
                                <option value="private">{{ __('messages.private_desc') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="nexus-form-group">
                        <label>{{ __('messages.posting_rights') }}</label>
                        <div class="input-wrapper">
                            <i class="fas fa-pen-nib"></i>
                            <select name="posting_permission">
                                <option value="anyone">{{ __('messages.anyone_can_post') }}</option>
                                <option value="admins_only">{{ __('messages.admins_only_post') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="nexus-modal-footer">
                <button type="button" class="btn-cancel" onclick="closeCreateGroupModal()">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn-submit">
                    <span>{{ __('messages.create_community_title') }}</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .discover-page { max-width: 1000px; margin: 0 auto; padding: 40px 24px; overflow-x: hidden; }
    
    .discover-header { 
        display: grid; 
        grid-template-columns: 1fr auto; 
        align-items: end; 
        margin-bottom: 48px; 
        gap: 24px; 
    }
    .page-title { font-size: 42px; font-weight: 900; color: var(--text); margin-bottom: 8px; letter-spacing: -1.5px; line-height: 1; }
    .page-subtitle { color: var(--text-muted); font-size: 17px; font-weight: 500; }
    .create-btn { 
        height: 52px; 
        padding: 0 28px !important; 
        border-radius: 16px !important;
        font-weight: 700 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }

    .section { margin-bottom: 48px; }
    .section-title { font-size: 22px; font-weight: 800; color: var(--text); margin-bottom: 24px; letter-spacing: -0.5px; }

    .horizontal-scroll-container { position: relative; margin: 0 -20px; padding: 0 20px; }
    .horizontal-scroll { display: flex; gap: 20px; overflow-x: auto; padding: 10px 0 24px; scrollbar-width: none; }
    .horizontal-scroll::-webkit-scrollbar { display: none; }
    
    .mini-card { flex: 0 0 240px; background: var(--surface); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; text-decoration: none; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .mini-card:hover { border-color: var(--community-accent); transform: translateY(-5px); background: var(--surface-hover); }
    
    .mini-content-alt { display: flex; align-items: center; gap: 14px; padding: 16px; }
    .mini-avatar-alt { width: 52px; height: 52px; border-radius: 14px; background: var(--surface-hover); object-fit: cover; }
    .mini-info strong { display: block; font-size: 15px; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; margin-bottom: 2px; }
    .mini-info span { font-size: 12px; color: var(--text-muted); font-weight: 700; }

    .discovery-header { 
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        margin-bottom: 32px; 
        gap: 24px; 
    }
    .search-wrap { position: relative; width: 360px; }
    .search-wrap i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 16px; }
    .search-wrap input { width: 100%; background: var(--surface); border: 1px solid var(--border); padding: 12px 20px 12px 52px; border-radius: 16px; color: var(--text); font-size: 16px; font-weight: 500; outline: none; transition: 0.3s; height: 52px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .search-wrap input:focus { border-color: var(--primary); background: var(--surface); box-shadow: 0 0 0 4px rgba(94, 96, 206, 0.15); }

    .discovery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .discovery-card { background: var(--surface); border: 1px solid var(--border); border-radius: 24px; overflow: hidden; text-decoration: none; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; }
    .discovery-card:hover { border-color: var(--community-accent); background: var(--surface-hover); transform: translateY(-5px); }
    
    .card-body-alt { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .card-avatar-alt { width: 64px; height: 64px; border-radius: 18px; object-fit: cover; background: var(--surface-hover); }
    .card-text h3 { font-size: 20px; font-weight: 800; color: var(--text); margin-bottom: 6px; letter-spacing: -0.5px; }
    .card-text p { font-size: 14px; color: var(--text-muted); line-height: 1.5; font-weight: 500; }
    
    .card-footer { border-top: 1px solid var(--border); padding-top: 16px; margin-top: 8px; display: flex; justify-content: space-between; align-items: center; }
    .member-tag { font-size: 13px; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
    .member-tag i { color: var(--community-accent); }
    .join-hint { font-size: 13px; font-weight: 800; color: var(--community-accent); opacity: 0; transform: translateX(-10px); transition: 0.3s; }
    .discovery-card:hover .join-hint { opacity: 1; transform: translateX(0); }

    .joined-badge { font-size: 13px; font-weight: 800; color: var(--success); display: flex; align-items: center; gap: 6px; }
    .joined-badge i { font-size: 15px; }

    .empty-state-card { grid-column: 1 / -1; text-align: center; padding: 100px 40px; background: var(--surface); border-radius: 32px; border: 2px dashed var(--border); }

    .grid-2-nexus { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

    /* Nexus Premium Modal System */
    .nexus-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: nexusFadeIn 0.3s ease;
    }

    .nexus-modal-card {
        background: rgba(22, 22, 22, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 28px;
        width: 100%;
        max-width: 520px;
        box-shadow: 0 32px 64px rgba(0, 0, 0, 0.4);
        overflow: hidden;
        animation: nexusSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes nexusFadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes nexusSlideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

    .nexus-modal-header {
        padding: 28px 32px;
        background: linear-gradient(to bottom, rgba(255, 255, 255, 0.03), transparent);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .header-content { display: flex; gap: 20px; align-items: center; }
    .header-icon {
        width: 48px; height: 48px; border-radius: 14px;
        background: var(--primary-glow); color: var(--primary);
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
    }
    .nexus-modal-header h3 { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 4px; }
    .nexus-modal-header p { font-size: 14px; color: var(--text-muted); font-weight: 500; }

    .close-btn {
        background: rgba(255, 255, 255, 0.05); border: none; color: #fff;
        width: 32px; height: 32px; border-radius: 10px; cursor: pointer;
        transition: 0.2s;
    }
    .close-btn:hover { background: rgba(255, 255, 255, 0.1); transform: rotate(90deg); }

    .nexus-modal-body { padding: 32px; display: flex; flex-direction: column; gap: 24px; }
    .nexus-form-group { display: flex; flex-direction: column; gap: 10px; }
    .nexus-form-group label { font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

    .input-wrapper {
        position: relative;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        transition: 0.2s;
    }
    .input-wrapper:focus-within {
        border-color: var(--primary);
        background: rgba(255, 255, 255, 0.05);
        box-shadow: 0 0 0 4px rgba(94, 96, 206, 0.15);
    }

    .input-wrapper i {
        position: absolute; left: 16px; top: 18px;
        font-size: 14px; color: var(--text-muted);
    }
    .textarea-wrapper i { top: 18px; }

    .input-wrapper input,
    .input-wrapper textarea,
    .input-wrapper select {
        width: 100%; background: transparent; border: none;
        padding: 16px 16px 16px 44px; color: #fff; font-size: 15px;
        font-weight: 500; outline: none;
    }
    .input-wrapper select { appearance: none; cursor: pointer; }
    .input-wrapper textarea { resize: none; }

    .nexus-modal-footer {
        padding: 24px 32px;
        background: rgba(255, 255, 255, 0.02);
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        display: flex; justify-content: flex-end; gap: 16px;
    }

    .btn-cancel {
        background: transparent; border: none; color: var(--text-muted);
        font-weight: 700; font-size: 14px; cursor: pointer; padding: 0 16px;
    }
    .btn-cancel:hover { color: #fff; }

    .btn-submit {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border: none; border-radius: 14px; color: #fff;
        padding: 14px 28px; font-weight: 800; font-size: 14px;
        cursor: pointer; display: flex; align-items: center; gap: 12px;
        box-shadow: 0 12px 24px rgba(94, 96, 206, 0.3);
        transition: 0.3s;
    }
    .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 16px 32px rgba(94, 96, 206, 0.4); }

    @media (max-width: 850px) {
        .discover-page { padding: 30px 20px; }
        .discover-header { grid-template-columns: 1fr; align-items: flex-start; gap: 20px; margin-bottom: 32px; }
        .page-title { font-size: 32px; }
        .create-btn { width: 100% !important; justify-content: center; height: 48px; }
        
        .discovery-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
        .search-wrap { width: 240px; }
        
        .discovery-grid { grid-template-columns: 1fr; gap: 16px; }
    }

    @media (max-width: 640px) {
        .discover-page { padding: 20px 16px; }
        .page-title { font-size: 26px; }
        .page-subtitle { font-size: 14px; }
        .back-btn { width: 36px !important; height: 36px !important; min-width: 36px !important; margin-top: 2px !important; }
        .back-btn i { font-size: 14px; }
        
        .section { margin-bottom: 32px; }
        .discovery-header { display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 12px !important; margin-bottom: 20px !important; }
        .section-title { font-size: 18px; margin-bottom: 0 !important; white-space: nowrap; }
        
        .search-wrap { width: auto !important; flex: 1 !important; max-width: 180px !important; }
        .search-wrap input { height: 40px !important; font-size: 13px !important; padding: 8px 12px 8px 38px !important; }
        .search-wrap i { left: 14px !important; font-size: 13px !important; }
        
        .mini-card { flex: 0 0 190px; }
        .mini-content-alt { padding: 12px; gap: 10px; }
        .mini-avatar-alt { width: 44px; height: 44px; border-radius: 12px; }
        .mini-info strong { font-size: 14px; }
        
        .card-body-alt { padding: 16px; gap: 12px; }
        .card-avatar-alt { width: 52px; height: 52px; border-radius: 14px; }
        .card-text h3 { font-size: 18px; }
        .card-text p { font-size: 13px; }
        
        .search-wrap input { height: 46px; font-size: 15px; padding-left: 48px; }
        .search-wrap i { left: 16px; }

        .join-hint { 
            opacity: 1 !important; 
            transform: none !important; 
            background: rgba(94, 96, 206, 0.1); 
            padding: 6px 12px; 
            border-radius: 10px; 
            font-size: 12px !important; 
        }

        .nexus-modal-overlay { padding: 0; align-items: flex-end; }
        .nexus-modal-card { 
            border-radius: 32px 32px 0 0; 
            margin: 0; 
            width: 100%; 
            max-width: none;
            max-height: 90vh;
            overflow-y: auto;
        }
        .grid-2-nexus { grid-template-columns: 1fr; gap: 16px; }
        .nexus-modal-header { padding: 20px; }
        .header-content { gap: 12px; }
        .header-icon { width: 40px; height: 40px; font-size: 16px; }
        .nexus-modal-header h3 { font-size: 18px; }
        
        .nexus-modal-body { padding: 20px; gap: 16px; }
        .input-wrapper input, .input-wrapper textarea, .input-wrapper select { padding: 14px 14px 14px 44px; }
        
        .nexus-modal-footer { padding: 16px 20px 40px; flex-direction: column-reverse; gap: 12px; }
        .btn-submit { width: 100%; justify-content: center; height: 52px; }
        .btn-cancel { height: 44px; width: 100%; }
    }
</style>

<script>
    function openCreateGroupModal() {
        document.getElementById('createGroupModal').style.display = 'flex';
    }

    function closeCreateGroupModal() {
        document.getElementById('createGroupModal').style.display = 'none';
    }

    document.getElementById('community-search')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.discovery-card');
        
        cards.forEach(card => {
            const name = card.querySelector('h3').textContent.toLowerCase();
            const desc = card.querySelector('p').textContent.toLowerCase();
            if (name.includes(query) || desc.includes(query)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>
@endsection
