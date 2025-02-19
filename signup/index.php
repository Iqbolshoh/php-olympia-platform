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

    $first_name = $query->validate($_POST['first_name']);
    $last_name = $query->validate($_POST['last_name']);
    $email = $query->validate(strtolower($_POST['email']));
    $username = $query->validate(strtolower($_POST['username']));
    $password = $query->hashPassword($_POST['password']);
    $role = 'user'; // default role is 'user'

    $data = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'username' => $username,
        'password' => $password,
        'role' => $role,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $user = $query->insert('users', $data);

    if (!empty($user)) {
        $user_id = $query->select('users', 'id', 'username = ?', [$username], 's')[0]['id'];

        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;

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
            'user_id' => $user_id,
            'device_name' => get_user_info(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'last_activity' => date('Y-m-d H:i:s'),
            'session_token' => session_id()
        ]);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $redirectPath = SITE_PATH . ROLES[$_SESSION['role']];
        ?>
        <script>
            window.onload = function () { Swal.fire({ icon: 'success', title: 'Registration successfu', timer: 1500, showConfirmButton: false }).then(() => { window.location.href = '<?= $redirectPath; ?>'; }); };
        </script>
        <?php
    } else {
        ?>
        <script>
            window.onload = function () { Swal.fire({ icon: 'error', title: 'Oops...', text: 'Registration failed. Please try again.', showConfirmButton: true }); };
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
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../src/css/login_signup.css">
</head>

<body>
    <div class="form-container">
        <h1>Sign Up</h1>
        <form id="signupForm" method="POST">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required maxlength="30">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required maxlength="30">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required maxlength="100">
                <small id="email-message"></small>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required maxlength="30">
                <small id="username-message"></small>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required maxlength="255">
                    <button type="button" id="toggle-password" class="password-toggle">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small id="password-message"></small>
            </div>
            <div class="form-group">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            </div>
            <div class="form-group">
                <button type="submit" name="submit" id="submit">Sign Up</button>
            </div>
        </form>
        <div class="text-center">
            <p>Already have an account? <a href="../login/">Login</a></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const emailField = document.getElementById('email');
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            const emailMessage = document.getElementById('email-message');
            const usernameMessage = document.getElementById('username-message');
            const passwordMessage = document.getElementById('password-message');
            const submitButton = document.getElementById('submit');
            const togglePassword = document.getElementById('toggle-password');

            let emailAvailable = false;
            let usernameAvailable = false;

            function validateEmailFormat(email) {
                return /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/.test(email);
            }

            function validateUsernameFormat(username) {
                return /^[a-zA-Z0-9_]{3,30}$/.test(username);
            }

            function validatePassword() {
                if (passwordField.value.length < 8) {
                    passwordMessage.textContent = 'Password must be at least 8 characters long!';
                    return false;
                }
                passwordMessage.textContent = '';
                return true;
            }

            function checkAvailability(type, value, messageElement, callback) {
                if (!value) return;

                fetch('check_availability.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `${type}=${encodeURIComponent(value)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            messageElement.textContent = `This ${type} is already taken!`;
                            callback(false);
                        } else {
                            messageElement.textContent = '';
                            callback(true);
                        }
                        updateSubmitButtonState();
                    });
            }

            function updateSubmitButtonState() {
                submitButton.disabled = !(emailAvailable && usernameAvailable && validatePassword());
            }

            emailField.addEventListener('input', function () {
                if (!validateEmailFormat(this.value)) {
                    emailMessage.textContent = 'Invalid email format!';
                    emailAvailable = false;
                    updateSubmitButtonState();
                    return;
                }
                checkAvailability('email', this.value, emailMessage, status => {
                    emailAvailable = status;
                });
            });

            usernameField.addEventListener('input', function () {
                if (!validateUsernameFormat(this.value)) {
                    usernameMessage.textContent = 'Username must be 3-30 characters long and contain only letters, numbers, and underscores!';
                    usernameAvailable = false;
                    updateSubmitButtonState();
                    return;
                }
                checkAvailability('username', this.value, usernameMessage, status => {
                    usernameAvailable = status;
                });
            });

            passwordField.addEventListener('input', function () {
                validatePassword();
                updateSubmitButtonState();
            });

            togglePassword.addEventListener('click', function () {
                passwordField.type = passwordField.type === 'password' ? 'text' : 'password';
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>

</html>