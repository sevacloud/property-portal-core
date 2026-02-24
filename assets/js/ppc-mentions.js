document.addEventListener('DOMContentLoaded', function() {
    const commentTextarea = document.getElementById('comment');
    if (!commentTextarea) return;

    let mentionDropdown = null;
    let users = [];
    let selectedIndex = -1;

    // Fetch users for mentions
    async function fetchUsers() {
        try {
            const response = await fetch(ppcMentions.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'ppc_get_users_for_mentions',
                    nonce: ppcMentions.nonce
                })
            });
            const data = await response.json();
            if (data.success) users = data.data;
        } catch (error) {
            console.error('Failed to fetch users:', error);
        }
    }

    // Create mention dropdown
    function createDropdown() {
        mentionDropdown = document.createElement('div');
        mentionDropdown.className = 'ppc-mention-dropdown';
        commentTextarea.parentNode.style.position = 'relative';
        commentTextarea.parentNode.appendChild(mentionDropdown);
    }

    // Show mention dropdown
    function showMentions(query) {
        if (!mentionDropdown) createDropdown();
        
        const filtered = users.filter(user => 
            user.display_name.toLowerCase().includes(query.toLowerCase())
        );

        if (filtered.length === 0) {
            hideMentions();
            return;
        }

        mentionDropdown.innerHTML = '';
        filtered.forEach((user, index) => {
            const item = document.createElement('div');
            item.className = 'ppc-mention-item';
            item.textContent = user.display_name;
            item.dataset.username = user.user_login;
            item.addEventListener('click', () => insertMention(user));
            mentionDropdown.appendChild(item);
        });

        positionDropdown();
        mentionDropdown.style.display = 'block';
        selectedIndex = -1;
    }

    // Hide mention dropdown
    function hideMentions() {
        if (mentionDropdown) {
            mentionDropdown.style.display = 'none';
        }
        selectedIndex = -1;
    }

    // Position dropdown near cursor
    function positionDropdown() {
        const rect = commentTextarea.getBoundingClientRect();
        mentionDropdown.style.left = '0px';
        mentionDropdown.style.top = (commentTextarea.offsetHeight + 5) + 'px';
        mentionDropdown.style.width = Math.min(300, commentTextarea.offsetWidth) + 'px';
    }

    // Insert mention into textarea
    function insertMention(user) {
        const value = commentTextarea.value;
        const cursorPos = commentTextarea.selectionStart;
        const atIndex = value.lastIndexOf('@', cursorPos - 1);
        
        const before = value.substring(0, atIndex);
        const after = value.substring(cursorPos);
        const mention = `@${user.user_login} `;
        
        commentTextarea.value = before + mention + after;
        commentTextarea.focus();
        commentTextarea.setSelectionRange(atIndex + mention.length, atIndex + mention.length);
        
        hideMentions();
    }

    // Handle keyboard navigation
    function handleKeyNavigation(e) {
        if (!mentionDropdown || mentionDropdown.style.display === 'none') return;
        
        const items = mentionDropdown.querySelectorAll('.ppc-mention-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelection(items);
        } else if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            const user = users.find(u => u.user_login === items[selectedIndex].dataset.username);
            if (user) insertMention(user);
        } else if (e.key === 'Escape') {
            hideMentions();
        }
    }

    // Update visual selection
    function updateSelection(items) {
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === selectedIndex);
        });
    }

    // Main input handler
    commentTextarea.addEventListener('input', function(e) {
        const value = this.value;
        const cursorPos = this.selectionStart;
        const textBeforeCursor = value.substring(0, cursorPos);
        const atIndex = textBeforeCursor.lastIndexOf('@');
        
        if (atIndex >= 0) {
            const query = textBeforeCursor.substring(atIndex + 1);
            if (query.length >= 0 && !query.includes(' ')) {
                showMentions(query);
                return;
            }
        }
        
        hideMentions();
    });

    commentTextarea.addEventListener('keydown', handleKeyNavigation);
    document.addEventListener('click', function(e) {
        if (!mentionDropdown || mentionDropdown.contains(e.target) || e.target === commentTextarea) return;
        hideMentions();
    });

    // Initialize
    fetchUsers();
});