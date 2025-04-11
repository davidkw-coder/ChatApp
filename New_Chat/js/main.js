/**
 * Main JavaScript file for ChatApp
 * Contains utility functions and global variables
 */

// API URL - change this to your server URL
const API_URL = "http://localhost/chatapp/"

// Check if user is logged in
function isLoggedIn() {
  return localStorage.getItem("chatapp_token") !== null
}

// Redirect if not logged in
function requireAuth() {
  if (
    !isLoggedIn() &&
    !window.location.pathname.includes("login.html") &&
    !window.location.pathname.includes("register.html") &&
    !window.location.pathname.includes("index.html")
  ) {
    window.location.href = "login.html"
  }
}

// Redirect if already logged in
function redirectIfLoggedIn() {
  if (
    isLoggedIn() &&
    (window.location.pathname.includes("login.html") || window.location.pathname.includes("register.html"))
  ) {
    window.location.href = "chat.html"
  }
}

// Format date for messages
function formatDate(date) {
  return new Date(date).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
}

// Show error message
function showError(elementId, message) {
  const errorElement = document.getElementById(elementId)
  if (errorElement) {
    errorElement.textContent = message
    errorElement.style.display = "block"
  }
}

// Hide error message
function hideError(elementId) {
  const errorElement = document.getElementById(elementId)
  if (errorElement) {
    errorElement.style.display = "none"
  }
}

// Make API request
async function apiRequest(endpoint, method = "GET", data = null) {
  const url = API_URL + endpoint
  const options = {
    method,
    headers: {
      "Content-Type": "application/json",
    },
  }

  // Add auth token if available
  if (isLoggedIn()) {
    options.headers["Authorization"] = "Bearer " + localStorage.getItem("chatapp_token")
  }

  // Add body data for POST, PUT requests
  if (data && (method === "POST" || method === "PUT")) {
    options.body = JSON.stringify(data)
  }

  try {
    const response = await fetch(url, options)
    const result = await response.json()

    if (!response.ok) {
      throw new Error(result.message || "Something went wrong")
    }

    return result
  } catch (error) {
    console.error("API Request Error:", error)
    throw error
  }
}

// Check auth status on page load
document.addEventListener("DOMContentLoaded", () => {
  // Check if page requires authentication
  if (!window.location.pathname.includes("index.html")) {
    requireAuth()
  }

  // Add logout functionality
  const logoutBtn = document.getElementById("logout-btn")
  if (logoutBtn) {
    logoutBtn.addEventListener("click", () => {
      localStorage.removeItem("chatapp_token")
      localStorage.removeItem("chatapp_user")
      window.location.href = "login.html"
    })
  }
})

