const openChats = new Map();

function openMiniChat(slug, id, name, avatar) {
    if (openChats.has(id)) {
        const box = document.querySelector(`.mini-chat-box[data-conv-id="${id}"]`);
        if (box) {
            box.classList.remove('minimized');
        }
        return;
    }

    if (openChats.size >= 3) {
        const oldestId = openChats.keys().next().value;
        closeMiniChat(oldestId);
    }

    const chatHtml = `
        <div class="mini-chat-box" data-conv-id="${id}" data-conv-slug="${slug}">
            <div class="mini-chat-header" onclick="toggleMiniChat(${id})">
                <img src="${avatar}" class="mini-chat-avatar">
                <span class="mini-chat-name">${name}</span>
                <div class="mini-chat-actions">
                    <button onclick="event.stopPropagation(); closeMiniChat(${id})"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <iframe src="/chat/${slug}" class="mini-chat-iframe"></iframe>
        </div>
    `;

    document.getElementById('mini-chat-container').insertAdjacentHTML('afterbegin', chatHtml);
    openChats.set(id, { slug, name, avatar });
}

function toggleMiniChat(id) {
    const box = document.querySelector(`.mini-chat-box[data-conv-id="${id}"]`);
    if (box) box.classList.toggle('minimized');
}

function closeMiniChat(id) {
    const box = document.querySelector(`.mini-chat-box[data-conv-id="${id}"]`);
    if (box) box.remove();
    openChats.delete(id);
}
