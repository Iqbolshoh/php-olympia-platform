<?php
session_start();

include '../config.php';
$query = new Database();

if (!empty($_SESSION['loggedin']) && isset(ROLES[$_SESSION['role']])) {
    header("Location: " . SITE_PATH . ROLES[$_SESSION['role']]);
    exit;
}

if (!empty($_COOKIE['username']) && !empty($_COOKIE['session_token']) && session_id() !== $_COOKIE['session_token']) {
    session_write_close();
    session_id($_COOKIE['session_token']);
    session_start();
}

if (!empty($_COOKIE['username'])) {
    $username = $_COOKIE['username'];
    $user = $query->select('users', 'id, role', "username = ?", [$username], 's')[0] ?? null;

    if (!empty($user)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'];

        $active_sessions = $query->select("active_sessions", "*", "session_token = ?", [session_id()], "s");

        if (!empty($active_sessions)) {
            $query->update(
                "active_sessions",
                ['last_activity' => date('Y-m-d H:i:s')],
                "session_token = ?",
                [$session_token],
                "s"
            );
        }

        if (isset(ROLES[$user['role']])) {
            header("Location: " . SITE_PATH . ROLES[$_SESSION['role']]);
            exit;
        }
    }
}

$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

function get_user_info()
{
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $devices = [
        'iPhone' => 'iPhone',
        'iPad' => 'iPad',
        'Macintosh|Mac OS X' => 'Mac',
        'Windows NT 10.0' => 'Windows 10 PC',
        'Windows NT 6.3' => 'Windows 8.1 PC',
        'Windows NT 6.2' => 'Windows 8 PC',
        'Windows NT 6.1' => 'Windows 7 PC',
        'Android' => 'Android Phone',
        'Linux' => 'Linux Device',
    ];

    foreach ($devices as $regex => $device) {
        if (preg_match("/$regex/i", $user_agent)) {
            return $device;
        }
    }

    return "Unknown Device";
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['submit']) &&
    isset($_POST['csrf_token']) &&
    isset($_SESSION['csrf_token']) &&
    hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {

    $username = strtolower(trim($_POST['username']));
    $password = $query->hashPassword($_POST['password']);
    $user = $query->select('users', '*', "username = ? AND password = ?", [$username, $password], 'ss')[0] ?? null;

    if (!empty($user)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        $cookies = [
            'username' => $username,
            'session_token' => session_id()
        ];

        foreach ($cookies as $name => $value) {
            setcookie($name, $value, [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }

        $query->insert('active_sessions', [
            'user_id' => $user['id'],
            'device_name' => get_user_info(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'last_activity' => date('Y-m-d H:i:s'),
            'session_token' => session_id()
        ]);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $redirectPath = SITE_PATH . ROLES[$_SESSION['role']];
        ?>
        <script>
            window.onload = function () { Swal.fire({ icon: 'success', title: 'Login successful', timer: 1500, showConfirmButton: false }).then(() => { window.location.href = '<?= $redirectPath; ?>'; }); };
        </script>
        <?php
    } else {
        ?>
        <script>
            window.onload = function () { Swal.fire({ icon: 'error', title: 'Oops...', text: 'Login or password is incorrect', showConfirmButton: true }); };
        </script>
        <?php
    }
} elseif (isset($_POST['submit'])) {
    ?>
    <script>
        window.onload = function () { Swal.fire({ icon: 'error', title: 'Invalid CSRF Token', text: 'Please refresh the page and try again.', showConfirmButton: true }); };
    </script>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../src/css/login_signup.css">
</head>

<body>
    <div class="form-container">
        <h1>Login</h1>
        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required maxlength="30">
                <small id="username-error" style="color: red;"></small>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required maxlength="255">
                    <button type="button" id="toggle-password" class="password-toggle">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small id="password-message" style="color: red;"></small>
            </div>
            <div class="form-group">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
            </div>
            <div class="form-group">
                <button type="submit" name="submit" id="submit">Login</button>
            </div>
        </form>
        <div class="text-center">
            <p>Don't have an account? <a href="../signup/">Sign Up</a></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const usernameField = document.getElementById('username');
        const passwordField = document.getElementById('password');
        const usernameError = document.getElementById('username-error');
        const passwordMessage = document.getElementById('password-message');
        const submitButton = document.getElementById('submit');

        function validateUsername() {
            const username = usernameField.value;
            const usernamePattern = /^[a-zA-Z0-9_]+$/;
            if (!usernamePattern.test(username)) {
                usernameError.textContent = "Username can only contain letters, numbers, and underscores!";
                submitButton.disabled = true;
            } else {
                usernameError.textContent = "";
                submitButton.disabled = false;
            }
        }

        function validatePassword() {
            const password = passwordField.value;
            if (password.length < 8) {
                passwordMessage.textContent = 'Password must be at least 8 characters long!';
                submitButton.disabled = true;
            } else {
                passwordMessage.textContent = '';
                submitButton.disabled = false;
            }
        }

        usernameField.addEventListener('input', validateUsername);
        passwordField.addEventListener('input', validatePassword);

        document.getElementById('toggle-password').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            const toggleIcon = this.querySelector('i');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        document.getElementById('loginForm').addEventListener('submit', function (event) {
            validateUsername();
            validatePassword();

            if (usernameError.textContent || passwordMessage.textContent) {
                event.preventDefault();
            }
        });
    </script>
</body>

</html>