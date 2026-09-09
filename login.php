<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? ''); $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) { $error = 'Username and password are required!'; }
    else {
        $stmt = $conn->prepare('SELECT id, password, role, full_name FROM users WHERE username = ? AND status = "active"');
        $stmt->bind_param('s', $username); $stmt->execute(); $result = $stmt->get_result();
        if ($result->num_rows === 1) { $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id']=$row['id']; $_SESSION['username']=$username; $_SESSION['role']=$row['role']; $_SESSION['full_name']=$row['full_name'];
                logAudit($conn,$row['id'],'LOGIN','user',$row['id']);
                if ($row['role']==='ADMIN') header('Location: admin/dashboard.php'); elseif ($row['role']==='CASHIER') header('Location: cashier/dashboard.php'); else header('Location: customer/booking.php'); exit();
            } else $error='Invalid password!';
        } else $error='User not found!'; $stmt->close();
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Sign in | SafeWay Transport</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link rel="stylesheet" href="assets/css/style.css"><link rel="stylesheet" href="assets/css/modern.css"></head>
<body><main class="auth-container"><div class="auth-box"><div class="text-center mb-4"><div class="feature-icon mx-auto mb-3" style="width:72px;height:72px;font-size:1.8rem"><i class="fas fa-bus"></i></div><h2 class="mb-1">Welcome back</h2><p class="mb-0">Sign in to manage your SafeWay journey.</p></div>
<?php if($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-circle-exclamation"></i> <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-circle-check"></i> <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<form method="POST" novalidate><div class="mb-3"><label for="username" class="form-label"><i class="fas fa-user"></i> Username</label><input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required autofocus></div><div class="mb-4"><label for="password" class="form-label"><i class="fas fa-lock"></i> Password</label><input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required></div><button type="submit" class="btn btn-primary w-100"><i class="fas fa-arrow-right-to-bracket me-2"></i>Sign in</button></form>
<div class="text-center mt-4"><a href="customer/booking.php" class="fw-semibold">Continue to booking</a><span class="mx-2 text-secondary">·</span><a href="index.php" class="text-secondary">Home</a></div></div></main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script><script src="assets/js/main.js"></script></body></html>