<?php
include 'C:/Users/User/Documents/GitHub/Website-Project-2/database/db_connection.php';

$message = "";
$toastClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $checkEmailStmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $checkEmailStmt->bind_param("s", $email);
    $checkEmailStmt->execute();
    $checkEmailStmt->store_result();

    if ($checkEmailStmt->num_rows > 0) {
        $message = "Email ID already exists";
        $toastClass = "#007bff"; // Primary color
    } else {
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);

        if ($stmt->execute()) {
            header("Location: login.php?registered=success");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
            $toastClass = "#dc3545"; // Danger color
        }

        $stmt->close();
    }

    $checkEmailStmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href=
"https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href=
"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <link rel="shortcut icon" href=
"https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src=
"https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <title>Registration</title>
</head>
<style>
        .password-container {
            position: relative;
        }
        .password-container input {
            padding-right: 45px;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            font-size: 16px;
            z-index: 10;
            background: none;
            border: none;
            padding: 0;
        }
        .toggle-password:hover {
            color: #495057;
        }
        .strength-meter {
            height: 5px;
            border-radius: 5px;
            margin-top: 8px;
            background-color: #e9ecef;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .strength-meter-fill {
            height: 100%;
            border-radius: 5px;
            width: 0%;
            transition: all 0.3s ease;
        }
        .strength-text {
            font-size: 12px;
            margin-top: 4px;
            font-weight: 500;
        }
        .strength-weak .strength-meter-fill {
            width: 25%;
            background-color: #dc3545;
        }
        .strength-weak .strength-text {
            color: #dc3545;
        }
        .strength-fair .strength-meter-fill {
            width: 50%;
            background-color: #ffc107;
        }
        .strength-fair .strength-text {
            color: #856404;
        }
        .strength-good .strength-meter-fill {
            width: 75%;
            background-color: #17a2b8;
        }
        .strength-good .strength-text {
            color: #0c5460;
        }
        .strength-strong .strength-meter-fill {
            width: 100%;
            background-color: #28a745;
        }
        .strength-strong .strength-text {
            color: #155724;
        }
    </style>

<body class="bg-light">
    <div class="container p-5 d-flex flex-column align-items-center">
        <?php if ($message): ?>
            <div class="toast align-items-center text-white border-0" 
          role="alert" aria-live="assertive" aria-atomic="true"
                style="background-color: <?php echo $toastClass; ?>;">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $message; ?>
                    </div>
                    <button type="button" class="btn-close
                    btn-close-white me-2 m-auto" 
                          data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        <form method="post" class="form-control mt-5 p-4"
            style="height:auto; width:380px;
            box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px,
            rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
            <div class="row text-center">
                <i class="fa fa-user-circle-o fa-3x mt-1 mb-2" style="color: green;"></i>
                <h5 class="p-4" style="font-weight: 700;">Create Your Account</h5>
                <p class="text-center" style="font-weight: 600; color: navy;">Join us and get started!</p>
            </div>
            <div class="mb-2">
                <label for="username"><i 
                  class="fa fa-user"></i> User Name</label>
                <input type="text" name="username" id="username"
                  class="form-control" required>
            </div>
            <div class="mb-2 mt-2">
                <label for="email"><i 
                  class="fa fa-envelope"></i> Email</label>
                <input type="text" name="email" id="email"
                  class="form-control" required>
            </div>
            <div class="mb-2 mt-2">
                <label for="password"><i class="fa fa-lock"></i> Password</label>
                <div class="password-container">
                    <input type="password" name="password" id="password"
                      class="form-control" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">
                        <i class="fa fa-eye-slash" id="toggleIcon"></i>
                    </button>
                </div>
                <div class="strength-meter" id="strengthMeter">
                    <div class="strength-meter-fill"></div>
                </div>
                <div class="strength-text" id="strengthText"></div>
            </div>
            <div class="mb-2 mt-3">
                <button type="submit" 
                  class="btn btn-success
                bg-success" style="font-weight: 600;">Create
                    Account</button>
            </div>
            <div class="mb-2 mt-4">
                <p class="text-center" style="font-weight: 600; 
                color: navy;">Already  have an Account <a href="./login.php"
                        style="text-decoration: none;">Login</a></p>
            </div>
        </form>
        <footer class="mt-4">
            <p class="text-center" style="font-weight: 600; color: navy;">&copy; 2024 Your Company. All rights reserved. Created by: Justin Plaatjies</p>
        </footer>
    </div>
    <script>
        let toastElList = [].slice.call(document.querySelectorAll('.toast'))
        let toastList = toastElList.map(function (toastEl) {
            return new bootstrap.Toast(toastEl, { delay: 3000 });
        });
        toastList.forEach(toast => toast.show());
        // Password Toggle Visibility
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }

        // Password Strength Meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.getElementById('strengthMeter');
            const strengthText = document.getElementById('strengthText');
            
            // Reset classes
            strengthMeter.className = 'strength-meter';
            strengthText.textContent = '';
            
            if (password.length === 0) {
                return;
            }

            // Calculate strength
            let strength = 0;
            
            // Length checks
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Character variety checks
            if (/[a-z]/.test(password)) strength++; // lowercase
            if (/[A-Z]/.test(password)) strength++; // uppercase
            if (/[0-9]/.test(password)) strength++; // numbers
            if (/[^a-zA-Z0-9]/.test(password)) strength++; // special characters
            
            // Determine strength level
            let strengthLevel, strengthLabel;
            
            if (strength <= 2) {
                strengthLevel = 'weak';
                strengthLabel = 'Weak';
            } else if (strength <= 4) {
                strengthLevel = 'fair';
                strengthLabel = 'Fair';
            } else if (strength <= 5) {
                strengthLevel = 'good';
                strengthLabel = 'Good';
            } else {
                strengthLevel = 'strong';
                strengthLabel = 'Strong';
            }
            
            strengthMeter.classList.add('strength-' + strengthLevel);
            strengthText.textContent = 'Password Strength: ' + strengthLabel;
        });
    </script>
</body>

</html>