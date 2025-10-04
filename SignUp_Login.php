<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login/Signup Form</title>
  <style>
    .back-button {
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 1000;
      background: rgba(255, 255, 255, 0.15);
      color: #000;
      padding: 10px 18px;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 500;
      text-decoration: none;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      backdrop-filter: blur(10px);
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
    }

    .back-button i {
      margin-right: 8px;
    }

    .back-button:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: translateX(-2px);
    }
  </style>
  <link rel="stylesheet" href="SignUp_Login.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
</head>
<body>
  <a href="maindashboard.php" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>
  <div class="container">
    <!-- Login Form -->
    <div class="form-box login">
      <form id="loginForm" action="login.php" method="POST" onsubmit="return validateCaptcha(event)">
        <h1>Login</h1>
        <div class="role-box">
          <label for="role">Role</label>
          <select id="role" name="role" required>
            <option value="instructor">Instructor</option>
            <option value="student">Student</option>
          </select>
        </div>
        <div class="input-box">
          <input type="text" name="username" placeholder="Username" required>
          <i class='bx bxs-user'></i>
        </div>
        <div class="input-box">
          <input type="password" name="password" placeholder="Password" required>
          <i class='bx bxs-lock-alt'></i>
        </div>
        <div class="captcha-box">
          <input type="text" id="captcha-code" disabled>
          <button type="button" onclick="generateCaptcha()">
            <i class="fa-solid fa-repeat"></i>
          </button>
        </div>
        <div class="input-box">
          <input type="text" id="captcha-input" placeholder="Enter CAPTCHA" required>
        </div>
        <button type="submit" class="btn">Login</button>
      </form>
    </div>

    <!-- Registration Form -->
    <div class="form-box register">
      <form id="registerForm">
        <h1>Registration</h1>
        <div class="role-box">
          <label for="register-role">Register As</label>
          <select id="register-role" name="role" required>
            <option value="">Select Role</option>
            <option value="instructor">Instructor</option>
            <option value="student">Student</option>
          </select>
        </div>
        <div class="input-box">
          <input type="text" id="username" name="username" placeholder="Username" required>
          <i class='bx bxs-user'></i>
        </div>
        <div class="input-box">
          <input type="email" id="email" name="email" placeholder="Email" required>
          <i class='bx bxs-envelope'></i>
        </div>
        <div class="input-box">
          <input type="password" id="password" name="password" placeholder="Password" required>
          <i class='bx bxs-lock-alt'></i>
        </div>

        <input type="hidden" name="otp_verified" id="otp_verified" value="false" />

        <button type="button" class="btn" id="sendOtpBtn">Send OTP</button>
        <p id="message"></p>
        <div class="input-box">
          <input type="text" id="otp" placeholder="Enter OTP" required>
        </div>
        <div class="button-container">
          <button type="button" class="btn" id="verifyOtpBtn">Verify OTP</button>
          <button type="submit" class="btn">Register</button>
        </div>
      </form>
    </div>

    <div class="toggle-box">
      <div class="toggle-panel toggle-left">
        <h1>Hello, Welcome!</h1>
        <p>Don't have an account?</p>
        <button class="btn register-btn" onclick="document.querySelector('.container').classList.add('active')">Register</button>
      </div>
      <div class="toggle-panel toggle-right">
        <h1>Welcome Back!</h1>
        <p>Already have an account?</p>
        <button class="btn login-btn" onclick="document.querySelector('.container').classList.remove('active')">Login</button>
      </div>
    </div>
  </div>

  <script>
    function generateCaptcha() {
      const captcha = Math.random().toString(36).substring(2, 8).toUpperCase();
      document.getElementById("captcha-code").value = captcha;
      sessionStorage.setItem("captcha", captcha);
    }

    function validateCaptcha(event) {
      const input = document.getElementById("captcha-input").value.toUpperCase();
      const actual = document.getElementById("captcha-code").value;
      if (input !== actual) {
        alert("Invalid CAPTCHA!");
        event.preventDefault();
        return false;
      }
      return true;
    }


    document.addEventListener("DOMContentLoaded", generateCaptcha);
  </script>
  <script src="register.js"></script>
</body>
</html>
