export function setBadge(count) {
    if (!('setAppBadge' in navigator)) return;
    try {
        if (!count || count === 0) {
            navigator.clearAppBadge().catch(() => {});
        } else {
            navigator.setAppBadge(count).catch(() => {});
        }
    } catch (e) {}
}
