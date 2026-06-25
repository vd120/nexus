function filterByTopic(topicId) {
    const slug = window.location.pathname.split("/").pop();
    const feedList = document.getElementById("group-feed-list");

    // UI Feedback
    document.querySelectorAll(".topic-pill").forEach((el) => {
        el.classList.remove("bg-blue-600", "text-white", "shadow-blue-600/20");
        el.classList.add("bg-gray-800", "text-gray-400", "border-gray-700");
    });

    // Fetch filtered feed
    const url = new URL(`/api/groups/${slug}`, window.location.origin);
    if (topicId) url.searchParams.append("topic_id", topicId);

    fetch(url, {
        headers: {
            Accept: "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            // Here we would ideally re-render the posts.
            // For now, reload with the query param for simplicity in this prototype.
            window.location.search = topicId ? `topic_id=${topicId}` : "";
        });
}
