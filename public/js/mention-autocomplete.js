/**
 * @mention autocomplete for remark textareas.
 */
function initMentionAutocomplete(textarea, options) {
    const searchUrl = options.searchUrl;
    const container = textarea.closest('.mention-wrap');
    const dropdown = container.querySelector('.mention-dropdown');
    const taggedContainer = container.querySelector('.tagged-user-ids');

    let mentionStart = -1;
    let activeIndex = 0;
    let results = [];
    let debounceTimer = null;
    let activeRequest = null;
    const cache = new Map();

    function hideDropdown() {
        dropdown.classList.add('d-none');
        dropdown.innerHTML = '';
        results = [];
        activeIndex = 0;
        mentionStart = -1;
    }

    function getMentionQuery() {
        const pos = textarea.selectionStart;
        const text = textarea.value.slice(0, pos);
        const at = text.lastIndexOf('@');
        if (at === -1) {
            return null;
        }
        const between = text.slice(at + 1);
        if (between.includes('\n') || between.length > 40) {
            return null;
        }
        mentionStart = at;
        return between;
    }

    function renderDropdown(items) {
        results = items;
        activeIndex = 0;
        if (!items.length) {
            dropdown.innerHTML = '<div class="mention-item text-muted">No users found</div>';
            dropdown.classList.remove('d-none');
            return;
        }
        dropdown.innerHTML = items.map((user, i) => `
            <button type="button" class="mention-item ${i === 0 ? 'active' : ''}" data-index="${i}">
                <strong>${escapeHtml(user.name)}</strong>
                <small class="text-muted ms-1">${escapeHtml(user.role)}</small>
            </button>
        `).join('');
        dropdown.classList.remove('d-none');
        dropdown.querySelectorAll('.mention-item').forEach((btn) => {
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectUser(results[parseInt(btn.dataset.index, 10)]);
            });
        });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function addTaggedUserId(id) {
        const existing = taggedContainer.querySelector(`input[value="${id}"]`);
        if (existing) {
            return;
        }
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'tagged_user_ids[]';
        input.value = id;
        taggedContainer.appendChild(input);
    }

    function selectUser(user) {
        const pos = textarea.selectionStart;
        const before = textarea.value.slice(0, mentionStart);
        const after = textarea.value.slice(pos);
        const insert = '@' + user.name + ' ';
        textarea.value = before + insert + after;
        const newPos = (before + insert).length;
        textarea.setSelectionRange(newPos, newPos);
        textarea.focus();
        addTaggedUserId(user.id);
        hideDropdown();
    }

    function fetchUsers(query) {
        const cacheKey = (query || '').toLowerCase();
        if (cache.has(cacheKey)) {
            renderDropdown(cache.get(cacheKey));
            return;
        }

        if (activeRequest) {
            activeRequest.abort();
        }

        const request = new AbortController();
        activeRequest = request;
        const separator = searchUrl.includes('?') ? '&' : '?';
        const url = searchUrl + (query ? separator + 'q=' + encodeURIComponent(query) : '');
        fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: request.signal,
        })
            .then((r) => r.json())
            .then((data) => {
                cache.set(cacheKey, data);
                renderDropdown(data);
            })
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    hideDropdown();
                }
            })
            .finally(() => {
                if (activeRequest === request) {
                    activeRequest = null;
                }
            });
    }

    textarea.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = getMentionQuery();
        if (query === null) {
            hideDropdown();
            return;
        }
        debounceTimer = setTimeout(() => fetchUsers(query), query === '' ? 0 : 120);
    });

    textarea.addEventListener('keydown', (e) => {
        if (dropdown.classList.contains('d-none') || !results.length) {
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, results.length - 1);
            updateActiveItem();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            updateActiveItem();
        } else if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            selectUser(results[activeIndex]);
        } else if (e.key === 'Escape') {
            hideDropdown();
        }
    });

    function updateActiveItem() {
        dropdown.querySelectorAll('.mention-item').forEach((el, i) => {
            el.classList.toggle('active', i === activeIndex);
        });
    }

    textarea.addEventListener('blur', () => {
        setTimeout(hideDropdown, 150);
    });
}
