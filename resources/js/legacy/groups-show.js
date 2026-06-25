/* Groups Show Functions */

(function () {
    "use strict";

    window.copyInviteLink = function () {
        const input = document.getElementById("inviteLink");
        if (!input) return;
        input.select();
        document.execCommand("copy");

        const btn = document.querySelector(".copy-btn");
        if (btn) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
            }, 2000);
        }
    };

    window.showAddMemberModal = function () {
        const modal = document.getElementById("addMemberModal");
        if (modal) modal.style.display = "flex";
    };

    window.hideAddMemberModal = function () {
        const modal = document.getElementById("addMemberModal");
        if (modal) modal.style.display = "none";
    };

    (function () {
        const modal = document.getElementById("addMemberModal");
        if (modal) {
            modal.addEventListener("click", function (e) {
                if (e.target === this) hideAddMemberModal();
            });
        }
    })();

    window.searchFriends = function (query) {
        const options = document.querySelectorAll(
            "#friendsList .friend-option",
        );
        const q = query.toLowerCase();
        options.forEach((option) => {
            const name =
                option
                    .querySelector(".friend-info span")
                    ?.textContent.toLowerCase() || "";
            option.style.display = name.includes(q) ? "flex" : "none";
        });
    };

    window.showQuickInviteModal = function () {
        const modal = document.getElementById("quickInviteModal");
        if (modal) modal.style.display = "flex";
    };

    window.hideQuickInviteModal = function () {
        const modal = document.getElementById("quickInviteModal");
        if (modal) modal.style.display = "none";
    };

    (function () {
        const modal = document.getElementById("quickInviteModal");
        if (modal) {
            modal.addEventListener("click", function (e) {
                if (e.target === this) hideQuickInviteModal();
            });
        }
    })();

    window.searchInviteFriends = function (query) {
        const options = document.querySelectorAll(
            "#inviteFriendsList .friend-option",
        );
        const q = query.toLowerCase();
        options.forEach((option) => {
            const name =
                option
                    .querySelector(".friend-info span")
                    ?.textContent.toLowerCase() || "";
            option.style.display = name.includes(q) ? "flex" : "none";
        });
    };
})();
