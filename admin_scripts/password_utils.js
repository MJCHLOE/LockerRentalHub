/**
 * Utility functions for password field handling
 */

// Toggle visibility for Current Password
function toggleCurrentPasswordVisibility() {
  var input = document.getElementById("currentPassword");
  if (input) {
      input.type = input.type === "password" ? "text" : "password";
  }
}

// Toggle visibility for New Password
function togglePasswordVisibility() {
  var passwordInput = document.getElementById("addUserPassword");
  if (passwordInput.type === "password") {
    passwordInput.type = "text";
  } else {
    passwordInput.type = "password";
  }
}

// Toggle visibility for Confirm Password
function toggleConfirmPasswordVisibility() {
  var input = document.getElementById("confirmPassword");
  if (input) {
      input.type = input.type === "password" ? "text" : "password";
  }
}