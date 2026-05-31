<div id="join-group-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content join-modal-box">
        <div class="modal-header">
            <h3>Join {{ $group->name }}</h3>
            <button class="close-modal" onclick="closeJoinModal()">&times;</button>
        </div>
        
        <div class="modal-body p-6">
            <!-- Step 1: Rules -->
            <div id="join-step-rules" class="join-step active">
                <h4 class="step-title">Review Community Rules</h4>
                <div class="rules-scroll-area custom-scrollbar">
                    @forelse($group->rules as $rule)
                        <div class="rule-card">
                            <span class="rule-number">{{ $loop->iteration }}</span>
                            <div class="rule-content">
                                <p class="rule-name">{{ $rule->title }}</p>
                                <p class="rule-description">{{ $rule->description }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="empty-rules">
                            <i class="fas fa-info-circle"></i>
                            <p>This community hasn't set any specific rules yet. Please be respectful!</p>
                        </div>
                    @endforelse
                </div>

                <div class="agreement-check">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="agree-rules">
                        <span class="checkmark"></span>
                        <span class="label-text">I have read and agree to follow these community rules.</span>
                    </label>
                </div>

                <div class="step-footer">
                    <button id="rules-next-btn" onclick="goToQuestions()" class="btn btn-primary w-full" disabled>
                        Next Step
                    </button>
                </div>
            </div>

            <!-- Step 2: Questions -->
            <div id="join-step-questions" class="join-step">
                <h4 class="step-title">Membership Questions</h4>
                <p class="step-subtitle">The admins would like to know a bit more about you before approving your request.</p>
                
                <form id="join-questions-form" class="questions-form">
                    @foreach($group->questions as $question)
                        <div class="form-group">
                            <label class="question-label">{{ $question->question }}</label>
                            <textarea name="answers[{{ $question->id }}]" required rows="3" placeholder="Type your answer here..."></textarea>
                        </div>
                    @endforeach
                </form>

                <div class="step-footer">
                    <button onclick="goToRules()" class="btn btn-ghost">Back</button>
                    <button onclick="submitJoinRequest()" class="btn btn-primary flex-grow">Submit Request</button>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
    function openJoinModal() {
        const modal = document.getElementById('join-group-modal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeJoinModal() {
        const modal = document.getElementById('join-group-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    function goToQuestions() {
        @if($group->questions->isEmpty())
            submitJoinRequest();
        @else
            document.getElementById('join-step-rules').classList.remove('active');
            document.getElementById('join-step-questions').classList.add('active');
        @endif
    }

    function goToRules() {
        document.getElementById('join-step-questions').classList.remove('active');
        document.getElementById('join-step-rules').classList.add('active');
    }

    document.getElementById('agree-rules')?.addEventListener('change', function() {
        const nextBtn = document.getElementById('rules-next-btn');
        if (nextBtn) nextBtn.disabled = !this.checked;
    });

    async function submitJoinRequest() {
        const form = document.getElementById('join-questions-form');
        const formData = new FormData(form);
        const slug = '{{ $group->slug }}';
        
        // Format answers for the controller
        const answers = [];
        formData.forEach((value, key) => {
            if (key.startsWith('answers[')) {
                const questionId = key.match(/\[(\d+)\]/)[1];
                answers.push({
                    question_id: questionId,
                    answer: value
                });
            }
        });

        try {
            const response = await fetch(`/communities/${slug}/join`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ answers: answers })
            });
            
            const data = await response.json();
            if (response.ok || response.status === 202) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Error joining community');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Something went wrong');
        }
    }
</script>
@endpush

