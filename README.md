# PHP Forum Project

## Overview
This is a simple PHP-based forum that allows users to make posts with a WYSIWYG editor. The forum supports content + account moderation via admin accounts, message posting, and an admin contact system using PHPMailer for email notifications.

## Features
- User authentication (login/register/logout)
- Post creation and deletion (deletion by admins)
- Admin contact form using PHPMailer
- Database-driven content storage
- SMTP + tinyMCE configuration via `.env` file

## Installation

### Requirements
- Docker & Docker Compose

### Setup
1. Clone the repository:
   ```bash
   git clone https://github.com/Marc3usz/internship-php
   cd internship-php
   ```
2. Copy the `.env.example` file and rename it to `.env`, then update the values:
   ```bash
   cp .env.example .env
   ```
3. Build images + start the Docker container in detached mode (via GUI or via cmd):
   ```bash
   docker-compose up --build -d
   ``` 
4. Access the forum at `http://localhost`

## SMTP Configuration
To enable email notifications, update your `.env` file with your SMTP settings:
```env
SMTP_HOST=smtp.example.com
SMTP_USER=your_email@example.com
SMTP_PASS=your_password
SMTP_PORT=587 # default TLS port, app supports TLS emails
```

## Usage
- Register an account and log in
- Create and view forum posts
- Contact admins via the contact form

## Create admin accounts
1. create an account normally via the register page reachable from the login page
2. log out of account and log into the default admin account
3. grant the new account admin privileges

NOTE: Admin accounts can only revoke privileges or delete younger admin accounts (older account => more reputable [in theory]). This prevents a malicious actor from deleting all other admin accounts and having full reign over the forum. Due to this, the default admin account cannot be deleted via the website (requires manual deletion via SQL). 


