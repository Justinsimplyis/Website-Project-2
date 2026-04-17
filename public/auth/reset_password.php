<?php
include 'C:/Users/User/Documents/GitHub/Website-Project-2/database/db_connection.php';

$message = "";
$toastClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    If(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $message = "Invalid email format";
        $toastClass = "bg-warning";
    }
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    if ($password === $confirmPassword) {
        // Prepare and execute
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);

        if($stmt->execute()){
            if($stmt->affected_rows > 0){
                header("Location: login.php?reset=success");
                exit();

            }else{
                $message = "No account found with that email";
                $toastClass = "bg-warning";
            }
        }

        $stmt->close();
    } else {
        $message = "Passwords do not match";
        $toastClass = "bg-warning";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" 
          content="width=device-width, 
                   initial-scale=1.0">
    <link href=
"https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href=
"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <link rel="shortcut icon" href=
"https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src=
"https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src=
"https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Reset Password</title>
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

<body>
    <div class="container p-5 d-flex flex-column align-items-center">
        <?php if ($message): ?>
            <div class="toast align-items-center text-white border-0" role="alert"
          aria-live="assertive" aria-atomic="true"
                style="background-color: <?php echo $toastClass === 'bg-success' ? 
                '#28a745' : ($toastClass === 'bg-danger' ? '#dc3545' :
                ($toastClass === 'bg-warning' ? '#ffc107' : '')); ?>">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $message; ?>
                    </div>
                    <button type="button" class="btn-close 
                    btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        <form action="" method="post" class="form-control mt-5 p-4"
            style="height:auto; width:380px; box-shadow: rgba(60, 64, 67, 0.3) 
            0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
            <div class="row">
                <i class="fa fa-user-circle-o fa-3x mt-1 mb-2" 
          style="text-align: center; color: green;"></i>
                <h5 class="text-center p-4" style="font-weight: 700;">
          Change Your Password</h5>
          <p class="text-center" style="color: gray; font-size: 14px;"> Enter your email and new password to reset your password.</p>
          
            </div>
            <div class="col-mb-3 position-relative">
                <label for="email"><i class="fa fa-envelope"></i> Email</label>
                <input type="text" name="email" id="email" 
                  class="form-control" required>
                <span id="email-check" class="position-absolute"
                    style="right: 10px; top: 50%; transform: translateY(-50%);"></span>
            </div>
            <div class="col mb-3 mt-3">
                <label for="password"><i class="fa fa-lock"></i> Password</label>
                <div class="password-container">
                    <input type="password" name="password"
                      id="password" class="form-control" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password', 'toggleIcon1')">
                        <i class="fa fa-eye-slash" id="toggleIcon1"></i>
                    </button>
                </div>
                <div class="strength-meter" id="strengthMeter">
                    <div class="strength-meter-fill"></div>
                </div>
                <div class="strength-text" id="strengthText"></div>
            </div>
            <div class="col mb-3 mt-3">
                <label for="confirm_password"><i class="fa fa-lock"></i> Confirm Password</label>
                <div class="password-container">
                    <input type="password" name="confirm_password" 
                      id="confirm_password" class="form-control" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password', 'toggleIcon2')">
                        <i class="fa fa-eye-slash" id="toggleIcon2"></i>
                    </button>
                </div>
            </div>
            <div class="col mb-3 mt-3">
                <button type="submit" class="btn bg-dark" 
                  style="font-weight: 600; color:white;">
                  Reset Password</button>
            </div>
            <div class="col mb-2 mt-4">
                <p class="text-center" style="font-weight: 600;
                color: navy;"><a href="./register.php"
                        style="text-decoration: none;">
                  Create Account</a> OR <a href="./login.php"
                        style="text-decoration: none;">Login</a></p>
            </div>
        </form>
        <footer class="mt-5">
            <p class="text-center" style="color: gray;">&copy; 2026 Your Company. All rights reserved. Created by: Justin Plaatjies</p>
        </footer>
    </div>
    <script>
        $(document).ready(function () {
            $('#email').on('blur', function () {
                var email = $(this).val();
                if (email) {
                    $.ajax({
                        url: '/public/check_email.php',
                        type: 'POST',
                        data: { email: email },
                        success: function (response) {
                            if (response == 'exists') {
                                $('#email-check').html('<i class="fa fa-check text-success"></i>');
                            } else {
                                $('#email-check').html('<i class="fa fa-times text-danger"></i>');
                            }
                        }
                    });
                } else {
                    $('#email-check').html('');
                }
            });

            let toastElList = [].slice.call(document.querySelectorAll('.toast'))
            let toastList = toastElList.map(function (toastEl) {
                return new bootstrap.Toast(toastEl, { delay: 3000 });
            });
            toastList.forEach(toast => toast.show());
        });
         // Password Toggle Visibility (Modified to accept multiple fields)
        function togglePasswordVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
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
            
            strengthMeter.className = 'strength-meter';
            strengthText.textContent = '';
            
            if (password.length === 0) {
                return;
            }

            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            if (/[a-z]/.test(password)) strength++; 
            if (/[A-Z]/.test(password)) strength++; 
            if (/[0-9]/.test(password)) strength++; 
            if (/[^a-zA-Z0-9]/.test(password)) strength++; 
            
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