/**
 * 通知铃铛 JS - V2.9.18 U-3
 */
(function() {
    var pollTimer;

    function initBell() {
        // 铃铛点击
        document.getElementById('bellBtn').addEventListener('click', function(e) {
            e.preventDefault();
            var dd = document.getElementById('notifyDropdown');
            var visible = dd.style.display === 'block';
            dd.style.display = visible ? 'none' : 'block';
            if (!visible) loadNotifications();
        });

        // 点击外部关闭
        document.addEventListener('click', function(e) {
            var bell = document.getElementById('notifyBell');
            if (bell && !bell.contains(e.target)) {
                document.getElementById('notifyDropdown').style.display = 'none';
            }
        });

        // 轮询未读数
        pollUnreadCount();
        pollTimer = setInterval(pollUnreadCount, 30000); // 30s
    }

    function pollUnreadCount() {
        fetch('/api/v1/notify/unread_count', {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            var badge = document.getElementById('notifyBadge');
            var count = res.data?.count || 0;
            if (count > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = count > 99 ? '99+' : count;
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(function() {});
    }

    function loadNotifications() {
        fetch('/api/v1/notify/list?page=1&limit=10', {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            var list = res.data?.data || [];
            var html = '';
            if (list.length === 0) {
                html = '<li class="text-center text-muted py-3">暂无通知</li>';
            } else {
                list.forEach(function(item) {
                    var cls = item.is_read ? '' : 'unread';
                    html += '<li class="' + cls + '" onclick="markNotifyRead(' + item.id + ', \'' + (item.link || '') + '\')">';
                    html += '<div>' + item.title + '</div>';
                    html += '<div class="notify-time">' + (item.create_time || '') + '</div>';
                    html += '</li>';
                });
            }
            document.getElementById('notifyList').innerHTML = html;
        });
    }

    window.markNotifyRead = function(id, link) {
        fetch('/api/v1/notify/read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(function() {
            pollUnreadCount();
            if (link) window.location.href = link;
        });
    };

    window.markAllRead = function() {
        fetch('/api/v1/notify/read_all', { method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(function() {
            pollUnreadCount();
            loadNotifications();
        });
    };

    if (document.getElementById('notifyBell')) {
        initBell();
    }
})();
