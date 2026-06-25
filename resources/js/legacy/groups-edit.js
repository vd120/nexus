/* Groups Edit Functions */

(function () {
    "use strict";

    let searchTimeout;

    window.searchUsers = function (query) {
        clearTimeout(searchTimeout);
        const results = document.getElementById("searchResults");

        if (query.length < 2) {
            if (results) results.innerHTML = "";
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch("/search?q=" + encodeURIComponent(query), {
                headers: { Accept: "application/json" },
            })
                .then((r) => r.json())
                .then((data) => {
                    if (results) {
                        if (data.users && data.users.length > 0) {
                            results.innerHTML = data.users
                                .map(
                                    (user) =>
                                        '<div class="search-result-item" onclick="addMember(' +
                                        window.currentGroupId +
                                        ", " +
                                        user.id +
                                        ')">' +
                                        "<span>" +
                                        user.name +
                                        "</span></div>",
                                )
                                .join("");
                        } else {
                            results.innerHTML =
                                '<div class="search-result-item">No users found</div>';
                        }
                    }
                })
                .catch(() => {
                    if (results) results.innerHTML = "";
                });
        }, 300);
    };

    window.addMember = function (groupId, userId) {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";

        fetch("/messaging-groups/" + groupId + "/members", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: JSON.stringify({ members: [userId] }),
        })
            .then((r) => r.json())
            .then((data) => {
                if (data.success) {
                    const membersList = document.getElementById("membersList");
                    if (membersList && data.member_html) {
                        membersList.insertAdjacentHTML(
                            "beforeend",
                            data.member_html,
                        );
                    }
                    const results = document.getElementById("searchResults");
                    if (results) results.innerHTML = "";
                    showToast("Member added", "success");
                } else {
                    const t =
                        window.chatTranslations ||
                        window.groupTranslations ||
                        {};
                    showToast(
                        data.message ||
                            t.failed_to_add_member ||
                            "Failed to add member",
                        "error",
                    );
                }
            })
            .catch(() => {
                const t =
                    window.chatTranslations || window.groupTranslations || {};
                showToast(
                    t.error_adding_member || "Error adding member",
                    "error",
                );
            });
    };

    window.removeMember = function (groupId, userId) {
        if (!confirm("Remove this member from the group?")) return;

        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";

        fetch("/messaging-groups/" + groupId + "/members/" + userId, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
        })
            .then((r) => r.json())
            .then((data) => {
                if (data.success) {
                    const memberCard = document.getElementById(
                        `member-${userId}`,
                    );
                    if (memberCard) {
                        memberCard.remove();
                    }
                    showToast("Member removed", "success");
                } else {
                    const t =
                        window.chatTranslations ||
                        window.groupTranslations ||
                        {};
                    showToast(
                        t.failed_to_remove_member || "Failed to remove member",
                        "error",
                    );
                }
            })
            .catch(() => {
                const t =
                    window.chatTranslations || window.groupTranslations || {};
                showToast(
                    t.error_removing_member || "Error removing member",
                    "error",
                );
            });
    };
})();
