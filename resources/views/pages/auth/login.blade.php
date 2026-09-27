<div class="login-page">

    <div class="login-wrap">

        {{-- Brand --}}
        <div class="brand">
            <div class="brand-icon">
                <i class="bi bi-building"></i>
            </div>

            <h1>หอพักสุขสบาย</h1>
            <p>ระบบจัดการหอพัก</p>
        </div>


        {{-- Account --}}
        <div class="accounts">

            <button type="button" class="account active" onclick="selectAccount('admin', this)">
                <b>👑 Admin</b>
                <strong>admin@dormitory.th</strong>
                <small>ผู้ดูแลระบบ</small>
            </button>

            <button type="button" class="account" onclick="selectAccount('user01', this)">
                <b>🏠 ผู้เช่า</b>
                <strong>TN-003</strong>
                <small>นาย ชัยนันท์</small>
            </button>

        </div>


        {{-- Login --}}
        <div class="login-card">

            <h2>เข้าสู่ระบบ</h2>

            <p class="subtitle">
                กรุณาเลือกบัญชีด้านบน หรือกรอกข้อมูลด้วยตนเอง
            </p>

            @if ($errors->any())
            <div class="error">
                <i class="bi bi-exclamation-circle"></i>
                {{ $errors->first() }}
            </div>
            @endif


            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                {{-- Username --}}
                <label for="u_username">
                    User ID หรือ Username
                </label>

                <div class="input">
                    <i class="bi bi-person"></i>

                    <input id="u_username" name="u_username" value="{{ old('u_username') }}"
                        placeholder="กรอกชื่อผู้ใช้" autocomplete="username" required autofocus>
                </div>


                {{-- Password --}}
                <label for="password">
                    รหัสผ่าน
                </label>

                <div class="input">
                    <i class="bi bi-lock"></i>

                    <input id="password" name="password" type="password" placeholder="รหัสผ่าน"
                        autocomplete="current-password" required>

                    <button type="button" onclick="togglePassword()">
                        <i id="eye" class="bi bi-eye"></i>
                    </button>
                </div>


                {{-- Remember --}}
                <label class="remember">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Remember Me</span>
                </label>


                <button class="login-btn" type="submit">
                    เข้าสู่ระบบ
                </button>

            </form>


            <div class="demo">
                รหัสผ่านบัญชี:
                <code>1234</code>
            </div>

        </div>


        <div class="footer">
            หอพักสุขสบาย · Dormitory Management System V1.0
        </div>

    </div>

</div>


{{-- Bootstrap Icons --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<style>
html,
body {
    margin: 0 !important;
    padding: 0 !important;
    width: 100%;
    min-height: 100%;
    background: #162d4a;
}

.login-page {
    min-height: 100vh;
    width: 100%;
    margin: 0;
    padding: 35px 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    box-sizing: border-box;
    background:
        linear-gradient(#ffffff0d 1px, transparent 1px),
        linear-gradient(90deg, #ffffff0d 1px, transparent 1px),
        #162d4a;
    background-size: 28px 28px;
}

.login-wrap {
    width: 430px;
}

.brand {
    margin-bottom: 28px;
    text-align: center;
    color: #fff;
}

.brand-icon {
    width: 62px;
    height: 62px;
    margin: 0 auto 15px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: #2d7ff9;
    font-size: 29px;
}

.brand h1 {
    margin: 0;
    font-size: 23px;
}

.brand p {
    margin: 5px 0 0;
    color: #8fb3df;
    font-size: 12px;
}


/* Account */

.accounts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 15px;
}

.account {
    min-height: 70px;
    padding: 10px 13px;
    border: 1px solid #3972b9;
    border-radius: 12px;
    background: #214e82;
    color: #fff;
    text-align: left;
    cursor: pointer;
}

.account.active {
    border-color: #e0a93b;
    background: #414845;
}

.account b,
.account strong,
.account small {
    display: block;
}

.account b {
    font-size: 11px;
}

.account strong {
    font-size: 11px;
}

.account small {
    margin-top: 2px;
    color: #9bb9dc;
    font-size: 9px;
}


/* Login */
.login-card {
    padding: 24px;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 12px 30px #071a2d55;
}

.login-card h2 {
    margin: 0;
    color: #162d4a;
    font-size: 18px;
}

.subtitle {
    margin: 5px 0 25px;
    color: #98a7bb;
    font-size: 10px;
}

.login-card label:not(.remember) {
    display: block;
    margin-bottom: 7px;
    color: #334a67;
    font-size: 11px;
    font-weight: 600;
}


/* Input */
.input {
    position: relative;
    margin-bottom: 17px;
}

.input>i {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: #8da3bf;
}

.input input {
    width: 100%;
    height: 43px;
    padding: 0 40px;
    border: 1px solid #cbd8e8;
    border-radius: 8px;
    outline: none;
    background: #fff;
    color: #334a67;
    font-size: 12px;
    box-sizing: border-box;
}

.input input:focus {
    border-color: #2864e8;
    box-shadow: 0 0 0 3px #2864e81a;
}

.input input::placeholder {
    color: #9aa9bb;
}

.input button {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: none;
    color: #8da3bf;
    cursor: pointer;
}


/* Remember */
.remember {
    display: flex !important;
    align-items: center;
    gap: 7px;
    margin: -2px 0 18px !important;
    color: #62748b !important;
    font-size: 10px !important;
    cursor: pointer;
}

.remember input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: #2864e8;
    cursor: pointer;
}


/* Button */
.login-btn {
    width: 100%;
    height: 40px;
    border: 0;
    border-radius: 7px;
    background: #234673;
    color: #fff;
    font-weight: 700;
    cursor: pointer;
}

.login-btn:hover {
    background: #1c3c63;
}


/* Error */
.error {
    margin-bottom: 15px;
    padding: 9px 11px;
    border-radius: 7px;
    background: #fff1f2;
    color: #b91c1c;
    font-size: 10px;
}


/* Footer */
.demo {
    margin-top: 20px;
    text-align: center;
    color: #9aa9bb;
    font-size: 9px;
}

.demo code {
    padding: 3px 6px;
    margin-left: 3px;
    border-radius: 3px;
    background: #f1f5f9;
    color: #8ca0b9;
}

.footer {
    margin-top: 24px;
    text-align: center;
    color: #4e86bd;
    font-size: 9px;
}
</style>


<script>
function selectAccount(username, button) {

    document.getElementById('u_username').value = username;

    document.querySelectorAll('.account')
        .forEach(x => x.classList.remove('active'));

    button.classList.add('active');
}


function togglePassword() {

    const input = document.getElementById('password');
    const icon = document.getElementById('eye');

    const show = input.type === 'password';

    input.type = show ? 'text' : 'password';

    icon.className = show ?
        'bi bi-eye-slash' :
        'bi bi-eye';
}
</script>