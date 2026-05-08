document.addEventListener('DOMContentLoaded', function() {
    const agreeRulesCheckbox = document.getElementById('agree-rules');
    const rulesNextBtn = document.getElementById('rules-next-btn');

    if (agreeRulesCheckbox) {
        agreeRulesCheckbox.addEventListener('change', function() {
            rulesNextBtn.disabled = !this.checked;
        });
    }
});

function openJoinModal() {
    document.getElementById('join-group-modal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeJoinModal() {
    document.getElementById('join-group-modal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    // Reset steps
    goToRules();
}

function goToQuestions() {
    document.getElementById('join-step-rules').classList.add('hidden');
    document.getElementById('join-step-questions').classList.remove('hidden');
}

function goToRules() {
    document.getElementById('join-step-questions').classList.add('hidden');
    document.getElementById('join-step-rules').classList.remove('hidden');
}

function submitJoinRequest() {
    const form = document.getElementById('join-questions-form');
    const formData = new FormData(form);
    const slug = window.location.pathname.split('/').pop();

    fetch(`/api/groups/${slug}/join`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            answers: Array.from(formData.entries()).map(([key, value]) => ({
                question_id: key.match(/\d+/)[0],
                answer: value
            }))
        })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        window.location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}
