<div class="member-item" id="member-{{ $member->user_id }}" style="display: flex; align-items: center; padding: 16px; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; transition: all 0.2s; position: relative; overflow: visible;">
    <img src="{{ $member->user->avatar_url }}" alt="" style="width: 44px; height: 44px; border-radius: 50%; margin-right: 16px;">
    <div style="flex: 1;">
        <div style="font-weight: 700;">{{ $member->user->username }}</div>
        <div style="font-size: 13px; color: var(--text-muted);">
            {{ $member->role == 'admin' ? __('chat.administrator') : __('chat.member') }}
            @if($member->user_id == $group->creator_id) • {{ __('chat.owner') }} @endif
        </div>
    </div>
    
    @if($group->isAdmin(auth()->user()) && $member->user_id != auth()->id())
        <div class="member-actions" style="position: relative; overflow: visible;">
            <button class="btn-icon" onclick="toggleMemberDropdown(event, '{{ $member->user_id }}')" style="width: 32px; height: 32px; border-radius: 8px; background: var(--surface-hover); border: none; color: var(--text); cursor: pointer;">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <ul class="member-dropdown-menu" id="dropdown-{{ $member->user_id }}">
                @if($member->role == 'member')
                    <li>
                        <form action="{{ route('groups.members.role', [$group, $member->user_id]) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="role" value="admin">
                            <button type="submit" class="dropdown-item">{{ __('chat.promote_to_admin') }}</button>
                        </form>
                    </li>
                @elseif($member->user_id != $group->creator_id)
                    <li>
                        <form action="{{ route('groups.members.role', [$group, $member->user_id]) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="role" value="member">
                            <button type="submit" class="dropdown-item">{{ __('chat.dismiss_as_admin') }}</button>
                        </form>
                    </li>
                @endif
                <li><hr style="border: 0; border-top: 1px solid var(--border); margin: 8px 0;"></li>
                <li>
                    <form action="{{ route('groups.members.remove', [$group, $member->user_id]) }}" method="POST" onsubmit="return confirm('{{ __('chat.confirm_remove_member') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger" style="color: var(--danger);">{{ __('chat.remove_from_group') }}</button>
                    </form>
                </li>
            </ul>
        </div>
    @endif
</div>
