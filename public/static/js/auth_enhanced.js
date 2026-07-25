/**
 * 注册登录增强 JS - V2.9.18 U-2
 */
(function() {
    // 密码强度检查
    function checkPwdStrength(pwd) {
        var score = 0;
        if (pwd.length >= 8) score++;
        if (/[a-zA-Z]/.test(pwd) && /\d/.test(pwd)) score++;
        if (/[^a-zA-Z0-9]/.test(pwd)) score++;
        return score;
    }

    var pwdInput = document.querySelector('#registerPwd, input[name="password"]');
    var strengthBar = document.querySelector('#pwdStrength');
    if (pwdInput && strengthBar) {
        pwdInput.addEventListener('input', function() {
            var s = checkPwdStrength(this.value);
            var colors = ['', '#dc3545', '#ffc107', '#28a745'];
            var texts = ['', '弱', '中', '强'];
            strengthBar.style.width = (s * 33) + '%';
            strengthBar.style.backgroundColor = colors[s] || '#ddd';
            strengthBar.textContent = texts[s] || '';
        });
    }

    // 邮箱验证码倒计时
    var sendCodeBtn = document.querySelector('#sendCodeBtn');
    if (sendCodeBtn) {
        var timer = null, countdown = 0;
        sendCodeBtn.addEventListener('click', function() {
            if (countdown > 0) return;
            var email = document.querySelector('#registerEmail').value;
            if (!email) { alert('请输入邮箱'); return; }

            fetch('/api/auth/register/send_code', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            }).then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.code === 0) {
                    countdown = 60;
                    sendCodeBtn.disabled = true;
                    timer = setInterval(function() {
                        countdown--;
                        sendCodeBtn.textContent = countdown + 's后重发';
                        if (countdown <= 0) {
                            clearInterval(timer);
                            sendCodeBtn.disabled = false;
                            sendCodeBtn.textContent = '获取验证码';
                        }
                    }, 1000);
                } else {
                    alert(res.msg);
                }
            });
        });
    }
})();
