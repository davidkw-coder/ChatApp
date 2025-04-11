/**
 * Authentication related JavaScript for ChatApp
 * Handles login, registration, and profile management
 */

// Declare variables that are assumed to be globally available
const API_URL = "/api/" // Or wherever your API is located
function redirectIfLoggedIn() {
  // Check if the user is logged in (e.g., check for a token in localStorage)
  if (localStorage.getItem("chatapp_token")) {
    // Redirect to the chat page or another appropriate page
    window.location.href = "chat.html"
  }
}

async function apiRequest(endpoint, method, data) {
  const url = API_URL + endpoint
  const headers = {
    "Content-Type": "application/json",
  }

  if (localStorage.getItem("chatapp_token")) {
    headers["Authorization"] = "Bearer " + localStorage.getItem("chatapp_token")
  }

  const options = {
    method: method,
    headers: headers,
    body: data ? JSON.stringify(data) : null,
  }

  const response = await fetch(url, options)

  if (!response.ok) {
    const message = await response.text()
    throw new Error(message || `HTTP error! status: ${response.status}`)
  }

  return await response.json()
}

function hideError(elementId) {
  const element = document.getElementById(elementId)
  if (element) {
    element.style.display = "none"
  }
}

function showError(elementId, message) {
  const element = document.getElementById(elementId)
  if (element) {
    element.textContent = message
    element.className = "error"
    element.style.display = "block"
  }
}

document.addEventListener("DOMContentLoaded", () => {
  // Redirect if already logged in
  redirectIfLoggedIn()

  // Handle login form submission
  const loginForm = document.getElementById("login-form")
  if (loginForm) {
    loginForm.addEventListener("submit", async (e) => {
      e.preventDefault()

      const username = document.getElementById("username").value
      const password = document.getElementById("password").value

      try {
        hideError("error-message")

        const response = await apiRequest("auth.php", "POST", {
          action: "login",
          username,
          password,
        })

        if (response.success) {
          // Store token and user data
          localStorage.setItem("chatapp_token", response.token)
          localStorage.setItem("chatapp_user", JSON.stringify(response.user))

          // Redirect to chat
          window.location.href = "chat.html"
        }
      } catch (error) {
        showError("error-message", error.message || "Login failed. Please check your credentials.")
      }
    })
  }

  // Handle registration form submission
  const registerForm = document.getElementById("register-form")
  if (registerForm) {
    registerForm.addEventListener("submit", async (e) => {
      e.preventDefault()

      const username = document.getElementById("username").value
      const email = document.getElementById("email").value
      const password = document.getElementById("password").value
      const confirmPassword = document.getElementById("confirm-password").value

      // Validate passwords match
      if (password !== confirmPassword) {
        showError("error-message", "Passwords do not match")
        return
      }

      try {
        hideError("error-message")

        const response = await apiRequest("auth.php", "POST", {
          action: "register",
          username,
          email,
          password,
        })

        if (response.success) {
          // Store token and user data
          localStorage.setItem("chatapp_token", response.token)
          localStorage.setItem("chatapp_user", JSON.stringify(response.user))

          // Redirect to chat
          window.location.href = "chat.html"
        }
      } catch (error) {
        showError("error-message", error.message || "Registration failed. Please try again.")
      }
    })
  }

  // Handle profile form
  const profileForm = document.getElementById("profile-form")
  if (profileForm) {
    // Load user data
    const userData = JSON.parse(localStorage.getItem("chatapp_user") || "{}")

    // Fill form with user data
    document.getElementById("username").value = userData.username || ""
    document.getElementById("email").value = userData.email || ""
    document.getElementById("bio").value = userData.bio || ""

    // Show avatar if available
    if (userData.avatar) {
      document.getElementById("current-avatar").src = userData.avatar
    }

    // Handle profile update
    profileForm.addEventListener("submit", async (e) => {
      e.preventDefault()

      const username = document.getElementById("username").value
      const email = document.getElementById("email").value
      const bio = document.getElementById("bio").value

      try {
        const response = await apiRequest("users.php", "PUT", {
          action: "update_profile",
          username,
          email,
          bio,
        })

        if (response.success) {
          // Update stored user data
          userData.username = username
          userData.email = email
          userData.bio = bio
          localStorage.setItem("chatapp_user", JSON.stringify(userData))

          // Show success message
          const messageElement = document.getElementById("profile-message")
          messageElement.textContent = "Profile updated successfully"
          messageElement.className = "message success"
          messageElement.style.display = "block"

          // Hide message after 3 seconds
          setTimeout(() => {
            messageElement.style.display = "none"
          }, 3000)
        }
      } catch (error) {
        const messageElement = document.getElementById("profile-message")
        messageElement.textContent = error.message || "Failed to update profile"
        messageElement.className = "message error"
        messageElement.style.display = "block"
      }
    })

    // Handle password change
    const passwordForm = document.getElementById("password-form")
    if (passwordForm) {
      passwordForm.addEventListener("submit", async (e) => {
        e.preventDefault()

        const currentPassword = document.getElementById("current-password").value
        const newPassword = document.getElementById("new-password").value
        const confirmNewPassword = document.getElementById("confirm-new-password").value

        // Validate passwords match
        if (newPassword !== confirmNewPassword) {
          const messageElement = document.getElementById("profile-message")
          messageElement.textContent = "New passwords do not match"
          messageElement.className = "message error"
          messageElement.style.display = "block"
          return
        }

        try {
          const response = await apiRequest("auth.php", "PUT", {
            action: "change_password",
            current_password: currentPassword,
            new_password: newPassword,
          })

          if (response.success) {
            // Show success message
            const messageElement = document.getElementById("profile-message")
            messageElement.textContent = "Password changed successfully"
            messageElement.className = "message success"
            messageElement.style.display = "block"

            // Clear form
            passwordForm.reset()

            // Hide message after 3 seconds
            setTimeout(() => {
              messageElement.style.display = "none"
            }, 3000)
          }
        } catch (error) {
          const messageElement = document.getElementById("profile-message")
          messageElement.textContent = error.message || "Failed to change password"
          messageElement.className = "message error"
          messageElement.style.display = "block"
        }
      })
    }

    // Handle avatar upload
    const avatarUpload = document.getElementById("avatar-upload")
    if (avatarUpload) {
      avatarUpload.addEventListener("change", async (e) => {
        const file = e.target.files[0]
        if (!file) return

        // Validate file type
        if (!file.type.match("image.*")) {
          const messageElement = document.getElementById("profile-message")
          messageElement.textContent = "Please select an image file"
          messageElement.className = "message error"
          messageElement.style.display = "block"
          return
        }

        // Create form data for file upload
        const formData = new FormData()
        formData.append("avatar", file)
        formData.append("action", "upload_avatar")

        try {
          // Custom fetch for file upload
          const response = await fetch(API_URL + "users.php", {
            method: "POST",
            headers: {
              Authorization: "Bearer " + localStorage.getItem("chatapp_token"),
            },
            body: formData,
          })

          const result = await response.json()

          if (!response.ok) {
            throw new Error(result.message || "Something went wrong")
          }

          if (result.success) {
            // Update avatar preview
            document.getElementById("current-avatar").src = result.avatar_url

            // Update stored user data
            userData.avatar = result.avatar_url
            localStorage.setItem("chatapp_user", JSON.stringify(userData))

            // Show success message
            const messageElement = document.getElementById("profile-message")
            messageElement.textContent = "Avatar updated successfully"
            messageElement.className = "message success"
            messageElement.style.display = "block"

            // Hide message after 3 seconds
            setTimeout(() => {
              messageElement.style.display = "none"
            }, 3000)
          }
        } catch (error) {
          const messageElement = document.getElementById("profile-message")
          messageElement.textContent = error.message || "Failed to upload avatar"
          messageElement.className = "message error"
          messageElement.style.display = "block"
        }
      })
    }
  }
})

