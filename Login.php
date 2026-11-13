
<?php
require_once "db.php";
session_start();

$error = "";

if(isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE email=?"
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if($user && password_verify($password,$user['password'])) {

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];

        header("Location: dashboard.php");
        exit;

    } else {
        $error = "Invalid email or password.";
    }
}

require "header.php";
?>

<div class="auth-card">

    <h1>Login</h1>
    <p>Welcome to HeartMonitor.</p>

    <p class="error">
        <?= htmlspecialchars($error) ?>
    </p>

    <form method="POST">

        <label>Email Address</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit" name="login">
            Login
        </button>

    </form>

    <p>Don't have an account?
        <a href="register.php">Register</a>
    </p>

</div>

<?php require "footer.php"; ?>
