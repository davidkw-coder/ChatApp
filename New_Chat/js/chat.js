/**
 * Chat functionality for ChatApp
 * Handles real-time messaging using polling
 */

document.addEventListener("DOMContentLoaded", () => {
  // Check if user is logged in
  requireAuth()

  // Get DOM elements
  const chatMessages = document.getElementById("chat-messages")
  const messageForm = document.getElementById("message-form")
  const messageInput = document.getElementById("message-input")
  const activeUsersList = document.getElementById("active-users")

  // Get user data
  const userData = JSON.parse(localStorage.getItem("chatapp_user") || "{}")

  // Update user info in sidebar
  document.getElementById("user-name").textContent = userData.username || "User"
  if (userData.avatar) {
    document.getElementById("user-avatar").src = userData.avatar
  }

  // Message polling interval (in milliseconds)
  const POLLING_INTERVAL = 3000
  let lastMessageId = 0

  // Load initial messages
  loadMessages()

  // Start polling for new messages
  const messageInterval = setInterval(loadMessages, POLLING_INTERVAL)

  // Load active users
  loadActiveUsers()

  // Start polling for active users
  const userInterval = setInterval(loadActiveUsers, 10000)

  // Clean up intervals when page is unloaded
  window.addEventListener("beforeunload", () => {
    clearInterval(messageInterval)
    clearInterval(userInterval)

    // Update user status to offline
    apiRequest("users.php", "POST", {
      action: "update_status",
      status: "offline",
    }).catch((err) => console.error("Error updating status:", err))
  })

  // Handle message submission
  if (messageForm) {
    messageForm.addEventListener("submit", async (e) => {
      e.preventDefault()

      const message = messageInput.value.trim()
      if (!message) return

      // Clear input
      messageInput.value = ""

      try {
        // Send message to server
        await apiRequest("messages.php", "POST", {
          action: "send_message",
          message,
        })

        // Load latest messages (including the one just sent)
        loadMessages()
      } catch (error) {
        console.error("Error sending message:", error)
        // Add error message to chat
        addSystemMessage("Failed to send message. Please try again.")
      }
    })
  }

  // Load messages from server
  async function loadMessages() {
    try {
      const response = await apiRequest(`messages.php?last_id=${lastMessageId}`)

      if (response.success && response.messages.length > 0) {
        // Update last message ID
        const newMessages = response.messages
        lastMessageId = newMessages[newMessages.length - 1].id

        // Add messages to chat
        newMessages.forEach((message) => {
          addMessageToChat(message)
        })

        // Scroll to bottom if user was already at bottom
        const isAtBottom = chatMessages.scrollHeight - chatMessages.clientHeight <= chatMessages.scrollTop + 100
        if (isAtBottom) {
          chatMessages.scrollTop = chatMessages.scrollHeight
        }
      }
    } catch (error) {
      console.error("Error loading messages:", error)
    }
  }

  // Load active users
  async function loadActiveUsers() {
    try {
      const response = await apiRequest("users.php?action=active_users")

      if (response.success) {
        // Clear current list
        activeUsersList.innerHTML = ""

        // Add users to list
        response.users.forEach((user) => {
          const userItem = document.createElement("li")

          userItem.innerHTML = `
                        <div class="user-avatar">
                            <img src="${user.avatar || "/placeholder.svg?height=32&width=32"}" alt="${user.username}">
                        </div>
                        <div class="user-info">
                            <span class="user-name">${user.username}</span>
                            <span class="status ${user.status}">${user.status}</span>
                        </div>
                    `

          activeUsersList.appendChild(userItem)
        })

        // If no users, show message
        if (response.users.length === 0) {
          const noUsers = document.createElement("li")
          noUsers.className = "loading"
          noUsers.textContent = "No active users"
          activeUsersList.appendChild(noUsers)
        }
      }
    } catch (error) {
      console.error("Error loading active users:", error)

      // Show error message
      activeUsersList.innerHTML = '<li class="loading">Failed to load users</li>'
    }
  }

  // Add message to chat
  function addMessageToChat(message) {
    // Check if message already exists
    if (document.querySelector(`[data-message-id="${message.id}"]`)) {
      return
    }

    const messageElement = document.createElement("div")
    const isCurrentUser = message.user_id === userData.id

    messageElement.className = `message-bubble ${isCurrentUser ? "sent" : "received"}`
    messageElement.dataset.messageId = message.id

    messageElement.innerHTML = `
            <div class="message-content">${message.message}</div>
            <div class="message-info">
                <span class="message-sender">${message.username}</span>
                <span class="message-time">${formatDate(message.created_at)}</span>
            </div>
        `

    chatMessages.appendChild(messageElement)
  }

  // Add system message
  function addSystemMessage(text) {
    const messageElement = document.createElement("div")
    messageElement.className = "message-center"
    messageElement.textContent = text

    chatMessages.appendChild(messageElement)
    chatMessages.scrollTop = chatMessages.scrollHeight
  }
})

// Mock functions for requireAuth, apiRequest, and formatDate
function requireAuth() {
  // Replace with actual authentication logic
  console.log("Authentication check")
}

async function apiRequest(url, method = "GET", data = null) {
  // Replace with actual API request logic
  console.log(`API Request: ${method} ${url}`, data)

  // Simulate a successful response
  return new Promise((resolve) => {
    setTimeout(() => {
      const response = { success: true }
      if (url.startsWith("messages.php")) {
        if (method === "POST" && data && data.action === "send_message") {
          response.messages = [
            {
              id: Math.floor(Math.random() * 1000),
              user_id: 1,
              username: "Test User",
              message: data.message,
              created_at: new Date().toISOString(),
            },
          ]
        } else {
          response.messages = [
            {
              id: 1,
              user_id: 1,
              username: "Test User",
              message: "Test Message",
              created_at: new Date().toISOString(),
            },
          ]
        }
      } else if (url.startsWith("users.php")) {
        response.users = [
          {
            id: 1,
            username: "Test User",
            avatar: "/placeholder.svg?height=32&width=32",
            status: "online",
          },
        ]
      }
      resolve(response)
    }, 500)
  })
}

function formatDate(dateString) {
  const date = new Date(dateString)
  const options = { year: "numeric", month: "long", day: "numeric", hour: "numeric", minute: "numeric" }
  return date.toLocaleDateString(undefined, options)
}

