/* Comments Functions */

(function () {
    "use strict";

    window.toggleReplyForm = function (commentId) {
        const form = document.getElementById("reply-form-" + commentId);
        if (form) {
            form.style.display =
                form.style.display === "none" ? "block" : "none";
            if (form.style.display === "block") {
                form.querySelector("textarea").focus();
            }
        }
    };

    window.toggleNestedReplies = function (commentId, show) {
        const hiddenReplies = document.getElementById(
            "hidden-replies-" + commentId,
        );
        const parentComment = document.querySelector(
            '[data-comment-id="' + commentId + '"]',
        );
        if (!parentComment) return;

        const showMoreBtn = parentComment.querySelector(".show-more-replies");
        const showRepliesAlways = parentComment.querySelector(
            ".show-replies-always",
        );

        if (hiddenReplies) {
            hiddenReplies.style.display = show ? "block" : "none";
        }

        if (showMoreBtn) showMoreBtn.style.display = "none";
        if (showRepliesAlways)
            showRepliesAlways.style.display = show ? "none" : "block";
    };

    // Robust Auto-expand and highlight comment from URL hash
    window.handleTargetedComment = function () {
        const hash = window.location.hash;
        if (hash && hash.startsWith("#comment-")) {
            const commentId = hash.replace("#comment-", "");

            // Attempt to find the comment
            const findAndShow = () => {
                const commentEl = document.getElementById(
                    "comment-" + commentId,
                );

                if (commentEl) {
                    console.log(
                        "[Comments] Found targeted comment:",
                        commentId,
                    );

                    // Find and open all hidden parent containers
                    let parent = commentEl.parentElement;
                    while (parent && parent !== document.body) {
                        // 1. Check for hidden top-level comments section
                        if (parent.classList.contains("hidden-comments")) {
                            const postId = parent.id.replace(
                                "hidden-comments-",
                                "",
                            );
                            console.log(
                                "[Comments] Revealing hidden comments for post:",
                                postId,
                            );
                            if (window.toggleComments) {
                                window.toggleComments(postId, true);
                            } else {
                                parent.style.display = "block";
                            }
                        }

                        // 2. Check for hidden nested replies section
                        if (parent.classList.contains("hidden-replies")) {
                            const parentCommentId = parent.id.replace(
                                "hidden-replies-",
                                "",
                            );
                            console.log(
                                "[Comments] Revealing hidden replies for comment:",
                                parentCommentId,
                            );
                            if (window.toggleNestedReplies) {
                                window.toggleNestedReplies(
                                    parentCommentId,
                                    true,
                                );
                            } else {
                                parent.style.display = "block";
                            }
                        }

                        parent = parent.parentElement;
                    }

                    // Scroll and highlight
                    setTimeout(() => {
                        commentEl.scrollIntoView({
                            behavior: "smooth",
                            block: "center",
                        });

                        // Add highlight class
                        commentEl.classList.remove("highlight-comment"); // Reset if already there
                        void commentEl.offsetWidth; // Trigger reflow
                        commentEl.classList.add("highlight-comment");

                        // Cleanup highlight class after animation
                        setTimeout(() => {
                            commentEl.classList.remove("highlight-comment");
                        }, 5000);
                    }, 500);

                    return true;
                }
                return false;
            };

            // Execute immediately
            if (!findAndShow()) {
                // If not found (e.g. still loading), retry a few times
                let retries = 0;
                const interval = setInterval(() => {
                    retries++;
                    if (findAndShow() || retries > 10) {
                        clearInterval(interval);
                    }
                }, 300);
            }
        }
    };

    // Listen for both page load and hash changes (same-page navigation)
    window.addEventListener("load", window.handleTargetedComment);
    window.addEventListener("hashchange", window.handleTargetedComment);
})();
