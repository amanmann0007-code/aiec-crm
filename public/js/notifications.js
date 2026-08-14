(function () {
    const badge = document.getElementById('notif-badge');
    const list = document.getElementById('notif-list');
    const markAllBtn = document.getElementById('notif-mark-all');
    const bellBtn = document.getElementById('notif-bell-btn');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!badge || !list) {
        return;
    }

    const indexUrl = bellBtn.dataset.notificationsUrl;
    const readAllUrl = bellBtn.dataset.readAllUrl;
    let latestNotificationId = 0;
    let unreadCount = 0;
    let activeLoadRequest = null;
    let pollTimer = null;
    let hasLoadedNotifications = false;
    let knownNotificationIds = new Set();
    let audioContext = null;
    const pollIntervalMs = 5000;

    function renderNotifications(data) {
        const nextUnreadCount = data.unread_count || 0;
        const nextNotificationId = data.last_id || latestNotificationId;
        const newNotifications = data.notifications.filter((notification) => !knownNotificationIds.has(notification.id));
        const dueReminders = newNotifications.filter((notification) => notification.reminder_due);
        const hasNewUnreadNotification = hasLoadedNotifications
            && newNotifications.some((notification) => !notification.is_read);

        unreadCount = nextUnreadCount;
        latestNotificationId = nextNotificationId;

        if (hasNewUnreadNotification && !dueReminders.length) {
            playNotificationSound();
        }

        if (dueReminders.length) {
            showReminderPopup(dueReminders[0]);
        }

        knownNotificationIds = new Set(data.notifications.map((notification) => notification.id));

        hasLoadedNotifications = true;

        if (unreadCount > 0) {
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }

        if (!data.notifications.length) {
            list.innerHTML = '<div class="text-center text-muted py-4">No notifications</div>';
            return;
        }

        list.innerHTML = data.notifications.map((n) => `
            <a href="${n.url || '#'}" class="notification-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}" data-url="${n.url || ''}" data-read-url="${n.read_url || ''}">
                <div class="fw-semibold">${escapeHtml(n.title)}</div>
                <div class="small text-muted">${escapeHtml(n.message)}</div>
                <div class="small text-secondary mt-1">${escapeHtml(n.created_at)}</div>
            </a>
        `).join('');

        list.querySelectorAll('.notification-item').forEach((item) => {
            item.addEventListener('click', (e) => {
                const id = item.dataset.id;
                const url = item.dataset.url;
                const readUrl = item.dataset.readUrl;
                if (!id) {
                    return;
                }

                if (readUrl) {
                    fetch(readUrl, {
                        method: 'POST',
                        keepalive: Boolean(url),
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }).catch(() => {});
                }

                if (url) {
                    return;
                }

                e.preventDefault();
                fetch(readUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).finally(loadNotifications);
            });
        });
    }

    function showReminderPopup(notification) {
        const modalElement = document.getElementById('notification-reminder-modal');
        if (!modalElement || typeof bootstrap === 'undefined') {
            return;
        }

        const shownKey = `aiec.reminder.shown.${notification.id}`;
        if (sessionStorage.getItem(shownKey) === '1') {
            return;
        }
        sessionStorage.setItem(shownKey, '1');

        const title = document.getElementById('notification-reminder-title');
        const message = document.getElementById('notification-reminder-message');
        const open = document.getElementById('notification-reminder-open');
        if (title) title.textContent = notification.title || 'Reminder';
        if (message) message.textContent = notification.message || 'A customer reminder is due.';
        if (open) {
            open.href = notification.url || '#';
            open.onclick = () => {
                if (notification.read_url) {
                    fetch(notification.read_url, {
                        method: 'POST',
                        keepalive: true,
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }).catch(() => {});
                }
            };
        }

        playNotificationSound();
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function getAudioContext() {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            return null;
        }

        if (!audioContext) {
            audioContext = new AudioContextClass();
        }

        return audioContext;
    }

    function unlockNotificationSound() {
        const context = getAudioContext();
        if (context && context.state === 'suspended') {
            context.resume().catch(() => {});
        }
    }

    function playNotificationSound() {
        const context = getAudioContext();
        if (!context) {
            return;
        }

        if (context.state === 'suspended') {
            context.resume().catch(() => {});
        }

        const now = context.currentTime;
        const gain = context.createGain();
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.22, now + 0.01);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.22);
        gain.connect(context.destination);

        [880, 1320].forEach((frequency, index) => {
            const oscillator = context.createOscillator();
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(frequency, now + (index * 0.05));
            oscillator.connect(gain);
            oscillator.start(now + (index * 0.05));
            oscillator.stop(now + 0.24);
        });
    }

    function loadNotifications() {
        if (activeLoadRequest) {
            activeLoadRequest.abort();
        }

        activeLoadRequest = new AbortController();

        return fetch(indexUrl, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: activeLoadRequest.signal,
        })
            .then((r) => r.json())
            .then(renderNotifications)
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    list.innerHTML = '<div class="text-center text-danger py-4">Could not load</div>';
                }
            })
            .finally(() => {
                activeLoadRequest = null;
            });
    }

    function startPolling() {
        clearInterval(pollTimer);
        pollTimer = setInterval(() => {
            if (document.visibilityState === 'visible') {
                loadNotifications();
            }
        }, pollIntervalMs);
    }

    markAllBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fetch(readAllUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(() => loadNotifications());
    });

    bellBtn?.addEventListener('show.bs.dropdown', loadNotifications);
    window.addEventListener('click', unlockNotificationSound, { once: true });
    window.addEventListener('keydown', unlockNotificationSound, { once: true });
    window.addEventListener('load', () => {
        setTimeout(() => {
            loadNotifications();
            startPolling();
        }, 500);
    });
    window.addEventListener('beforeunload', () => {
        if (activeLoadRequest) {
            activeLoadRequest.abort();
        }
        clearInterval(pollTimer);
    });
})();
