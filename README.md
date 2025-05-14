# 💬 ChatApp

A simple real-time chat application built using **PHP**, **HTML**, **CSS**, and **JavaScript**. This project allows users to communicate with each other in real-time via a clean and responsive user interface.

## 🚀 Features

- User login and registration system
- Real-time chat using AJAX and PHP
- Responsive design for mobile and desktop
- Online/offline user status
- Message timestamp and auto-scroll
- Basic security measures (input sanitization)

## 🛠️ Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla JS)
- **Backend**: PHP (Procedural or OOP)
- **Database**: MySQL
- **AJAX**: For asynchronous message sending/receiving

## 📂 Project Structure

chatapp/
│
├── assets/ # CSS, JS, Images
│ ├── css/
│ ├── js/
│
├── includes/ # PHP logic (DB, sessions, etc.)
│ ├── db.php
│ ├── login.php
│ ├── register.php
│ ├── logout.php
│
├── chat/ # Chat page and messaging logic
│ ├── index.php
│ ├── sendMessage.php
│ ├── fetchMessages.php
│
├── index.php # Landing/Login Page
├── register.php # Registration Page
├── dashboard.php # Redirected page after login
├── .env # (Optional) for DB credentials
└── README.md

bash
Copy
Edit

## ⚙️ Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/chatapp.git
   cd chatapp
Set Up the Database

Import the chatapp.sql file (if provided) into your MySQL server.

Update your database credentials in includes/db.php.

Run on Localhost

Use tools like XAMPP, WAMP, or Laragon.

Place the project folder inside the htdocs directory.

Start Apache and MySQL, then access via http://localhost/chatapp.

🧪 Usage
Register a new account or log in with an existing one.

Start chatting with available users.

Messages appear instantly thanks to AJAX polling.

🔒 Security Notes
Input fields are sanitized to prevent XSS.

PHP sessions are used for authentication.

It's recommended to implement CSRF protection and secure password hashing (e.g., password_hash()).

💡 Future Improvements
WebSocket integration for true real-time communication

File/image sharing

Group chats

Emojis and message reactions

Dark mode support

🤝 Contributing
Pull requests are welcome! If you have suggestions for improvements, feel free to fork the repo and submit a PR.

📄 License
This project is open-source and available under the MIT License.

vbnet
Copy
Edit

Let me know if you'd like to include a logo, screenshots, or link to a live demo.
