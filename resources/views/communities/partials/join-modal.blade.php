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

<style>
    .join-step {
        display: none;
    }

    .join-step.active {
        display: block;
    }

    .step-title {
        font-size: 24px;
        font-weight: 800;
        margin-bottom: 8px;
        letter-spacing: -1px;
    }

    .step-subtitle {
        color: var(--text-muted);
        font-size: 14px;
        margin-bottom: 24px;
        line-height: 1.5;
    }

    .rules-scroll-area {
        max-height: 350px;
        overflow-y: auto;
        margin-bottom: 24px;
        padding-right: 8px;
    }

    .rule-card {
        display: flex;
        gap: 16px;
        padding: 16px;
        background: var(--surface-hover);
        border: 1px solid var(--border);
        border-radius: 16px;
        margin-bottom: 12px;
        transition: 0.2s;
    }

    .rule-card:hover {
        border-color: var(--primary);
    }

    .rule-number {
        width: 28px;
        height: 28px;
        background: var(--primary);
        color: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 13px;
        flex-shrink: 0;
    }

    .rule-name {
        font-weight: 700;
        font-size: 15px;
        margin-bottom: 4px;
        display: block;
    }

    .rule-description {
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.5;
    }

    .agreement-check {
        padding: 20px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        margin-bottom: 24px;
    }

    .custom-checkbox {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
    }

    .custom-checkbox input {
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        color: white;
        font-size: 12px;
        transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .custom-checkbox input:checked + .checkmark::after {
        transform: translate(-50%, -50%) scale(1);
    }

    .label-text {
        font-size: 15px;
        font-weight: 700;
        color: var(--text);
    }

    .btn-primary-lg {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        border: none;
        padding: 18px 32px;
        border-radius: 18px;
        font-weight: 800;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 20px var(--primary-glow);
    }

    .btn-primary-lg:hover:not(:disabled) {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 15px 30px var(--primary-glow);
        filter: brightness(1.1);
    }

    .btn-primary-lg:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        filter: grayscale(1);
        box-shadow: none;
    }

    .btn-secondary-lg {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--border);
        color: var(--text);
        padding: 18px 32px;
        border-radius: 18px;
        font-weight: 800;
        font-size: 16px;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-secondary-lg:hover {
        background: var(--surface-hover);
        border-color: var(--text-muted);
    }

    .step-footer.split {
        display: flex;
        gap: 16px;
        margin-top: 40px;
    }

    .questions-form .form-group {
        margin-bottom: 24px;
    }

    .question-label {
        display: block;
        font-weight: 800;
        margin-bottom: 12px;
        font-size: 15px;
        color: var(--text);
    }

    .questions-form textarea {
        width: 100%;
        background: var(--bg);
        border: 2px solid var(--border);
        color: var(--text);
        padding: 16px 20px;
        border-radius: 18px;
        outline: none;
        resize: none;
        font-size: 16px;
        font-weight: 500;
        transition: 0.3s;
    }

    .questions-form textarea:focus {
        border-color: var(--primary);
        background: rgba(255, 255, 255, 0.02);
    }

    .empty-rules {
        text-align: center;
        padding: 64px 32px;
        color: var(--text-muted);
        background: rgba(255, 255, 255, 0.02);
        border: 2px dashed var(--border);
        border-radius: 24px;
    }

    .empty-rules i {
        font-size: 48px;
        margin-bottom: 20px;
        background: linear-gradient(135deg, var(--text-muted) 0%, var(--primary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        opacity: 0.4;
    }

    .empty-rules p {
        font-size: 16px;
        font-weight: 600;
    }

</style>

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

