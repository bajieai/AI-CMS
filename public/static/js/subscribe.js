/**
 * 邮件订阅前端 JS - V2.9.18 D-3
 */
window.doSubscribe = function() {
    var email = document.getElementById('subscribeEmail').value.trim();
    var msgDiv = document.getElementById('subscribeMsg');

    if (!email) {
        msgDiv.innerHTML = '<span class="text-danger">请输入邮箱地址</span>';
        return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        msgDiv.innerHTML = '<span class="text-danger">邮箱格式不正确</span>';
        return;
    }

    var btn = document.querySelector('#subscribeForm button');
    btn.disabled = true;
    btn.textContent = '提交中...';
    msgDiv.innerHTML = '';

    fetch('/api/subscribe/submit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(email) + '&source=footer'
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.code === 0) {
            msgDiv.innerHTML = '<span class="text-success">✅ ' + res.msg + '</span>';
            document.getElementById('subscribeEmail').value = '';
        } else {
            msgDiv.innerHTML = '<span class="text-danger">' + res.msg + '</span>';
        }
    })
    .catch(function() {
        msgDiv.innerHTML = '<span class="text-danger">网络错误，请重试</span>';
    })
    .finally(function() {
        btn.disabled = false;
        btn.textContent = '订阅';
    });
};
