
<?php
require_once "db.php";
session_start();

$error = "";

if(isset($_POST['register'])) {

    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if(strlen($password) < 8) {
        $error = "Password must have at least 8 characters.";
    }
    elseif($password != $confirm) {
        $error = "Passwords do not match.";
    }
    else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users(full_name,email,password)
                 VALUES(?,?,?)"
            );

            $stmt->execute([$name,$email,$hash]);

            header("Location: login.php");
            exit;

        } catch(PDOException $e) {
            $error = "Email already exists.";
        }
    }
}

require "header.php";
?>

<div class="auth-card">
    <h1>Create Account</h1>
    <p>Register to access HeartMonitor.</p>

    <p class="error">
        <?= htmlspecialchars($error) ?>
    </p>

    <form method="POST">

        <label>Full Name</label>
        <input type="text" name="full_name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" minlength="8" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" minlength="8" required>

        <button type="submit" name="register">
            Register
        </button>

    </form>

    <p>Already have an account?
        <a href="login.php">Login</a>
    </p>
</div>

<?php require "footer.php"; ?>
