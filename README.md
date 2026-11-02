# Multi-Agent AI Platform / Studio

A lightweight, robust, and production-ready **PHP multi-agent AI orchestration platform** built with modern vanilla PHP (MVC pattern), custom routing, MySQL/PDO, and a responsive light-theme UI powered by native CSS and JavaScript.

---

## 📋 Table of Contents
1. [Purpose & Overview](#purpose--overview)
2. [Key Features](#key-features)
3. [Prerequisites & System Requirements](#prerequisites--system-requirements)
4. [Deployment & Installation Guide](#deployment--installation-guide)
5. [Super Admin Configuration & Password Reset](#super-admin-configuration--password-reset)
6. [Directory Structure](#directory-structure)

---

## 🎯 Purpose & Overview

The **Multi-Agent AI Platform** enables teams and developers to build, configure, test, and orchestrate specialized autonomous AI agents in sequential or iterative multi-agent pipelines. It features secure credential management (AES-256-GCM encryption for API keys), granular role-based access control (RBAC) down to individual screen navigation permissions, and dynamic model management across multiple AI providers (Google Gemini, OpenAI, Anthropic Claude, Groq, and Ollama/Local endpoints).

---

## ✨ Features

* **Multi-Agent Pipeline Workbench:** Chain multiple specialized AI agents together. The output of Agent #1 automatically cascades as the input prompt to Agent #2, supporting both fixed-count and indeterminate (condition-based) iterative loops.
* **Knowledge Base Document Injector:** Upload reference files (`.txt`, `.md`, `.csv`, `.json`) or paste text directly into the workbench to automatically inject contextual knowledge into agent prompts.
* **AI Provider & Model Management:** Configure arbitrary backend API endpoints and manage active vs. deprecated lifecycle statuses per model.
* **Secure API Key Vault:** Personal user API keys are encrypted using `AES-256-GCM` with masking and reveal controls.
* **Granular Role-Based Access Control (RBAC):** Manage roles and toggle screen permissions (Agents, API Keys, Workbench, Providers, Models, Users, Roles) via an interactive master-detail matrix.
* **Modern UI/UX:** Responsive off-canvas navigation sidebar, live markdown rendering with syntax-highlighted code blocks, and copy-to-clipboard deliverable outputs.

---

## ⚙️ Prerequisites & System Requirements

* **Web Server:** Apache (with `mod_rewrite` enabled) or Nginx.
* **PHP:** Version **8.1 or higher** with the following extensions enabled:
  * `pdo`, `pdo_mysql`
  * `openssl` (required for AES-256-GCM credential encryption)
  * `curl` (required for LLM API integrations)
  * `mbstring`
* **Database:** MySQL 5.7+ or MariaDB 10.3+.
* **API Keys:** At least one active API key from a supported provider (Google Gemini, OpenAI, Anthropic, or Groq) if you intend to run live generation pipelines.

---

## 🚀 Deployment & Installation Guide

### Step 1: Upload Files
Upload all project files to your web server document root (e.g., `/public_html/` or a dedicated subdomain folder). Ensure that your web server points directly to the project root where `index.php` and `.htaccess` reside.

### Step 2: Configure Environment & Database
1. Copy the example configuration file:
   ```bash
   cp config/config.example.php config/config.php
   ```
2. Open `config/config.php` and update your MySQL database connection credentials:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   ```
3. Set a secure application encryption key (used for encrypting user API keys):
   ```php
   define('APP_ENCRYPTION_KEY', 'base64:' . base64_encode('your-secure-random-32-byte-string-here!!'));
   ```

### Step 3: Import Database Schema & Seed Data
Import the SQL schema file located at `database/01_db_creation.sql` into your MySQL database using phpMyAdmin, cPanel, or the MySQL command line:
```bash
mysql -u your_database_user -p your_database_name < database/01_db_creation.sql
```
This automatically sets up all required tables (`users`, `roles`, `screens`, `role_screens`, `providers`, `models`, `agents`, `user_api_keys`, `workbench_documents`, `system_info`), grants default permissions, and seeds the default **SuperAdmin** account.

### Step 4: Verify URL Rewriting & Apache Permissions
The `.htaccess` file handles URL rewriting to route all requests through `index.php` while blocking direct web access to sensitive files (`.env`, `config.php`, `.sqlite`, `.log`) and database folders. Ensure your Apache server allows `.htaccess` overrides (`AllowOverride All`).

---

## 🔐 Changing the SuperAdmin Name and Password

The default database seed creates an initial administrator account with the following default credentials:
* **Email / Username:** `admin@example.com` / `superadmin`
* **Default Password:** `Admin123!`

For production security, you should change these credentials immediately after your first login.

### Method A: Via the Web UI (Recommended)
1. Log in to the platform using `admin@example.com` and password `Admin123!`.
2. Navigate to the **Users** menu (`/users`) in the sidebar (requires SuperAdmin role).
3. Locate the **Root / Super Administrator** account (`superadmin`) and click the **Edit User** icon.
4. Update the **Full Name**, **Email Address**, or enter a **New Password** (minimum 6 characters).
5. Click **Save Changes**.

### Method B: Directly via Database SQL
If you are locked out or prefer database-level provisioning, you can generate a new Bcrypt password hash in PHP and update the database directly:

1. Run a quick PHP snippet or online utility to generate a secure hash for your new password (e.g., `MySecurePassword99!`):
   ```php
   echo password_hash('MySecurePassword99!', PASSWORD_BCRYPT);
   ```
2. Execute the following SQL query on your database:
   ```sql
   UPDATE users 
   SET 
       username = 'your_new_admin_username',
       email = 'your_new_admin@example.com',
       full_name = 'Your Administrator Name',
       password_hash = '$2y$10$YOUR_GENERATED_BCRYPT_HASH_HERE'
   WHERE id = 1;
   ```

---

## 📁 Directory Structure

```text
├── .gitignore
├── .htaccess                 # Apache routing & security rules
├── index.php                 # Front controller & lightweight HTTP router
├── config/
│   ├── config.php            # Active configuration (created from template)
│   └── config.example.php    # Configuration template
├── database/
│   └── 01_db_creation.sql    # Complete MySQL schema & initial seed data
├── src/
│   ├── Core/                 # Autoloader & Database PDO connection singleton
│   ├── Controllers/          # Auth, Agent, Workbench, Key, Provider, Model, User, Role controllers
│   ├── Repositories/         # Database data access layer repositories
│   └── Services/             # Encryption service (AES-256-GCM)
├── views/
│   ├── layouts/              # Header, sidebar, footer partial components
│   ├── agents/               # AI Agents Studio views & modals
│   ├── keys/                 # Personal API key vault views & modals
│   ├── models/               # AI models management view
│   ├── providers/            # AI provider endpoint configuration view
│   ├── workbench/            # Multi-agent execution pipeline & document KB view
│   ├── users/                # User management & account control views
│   ├── roles.php             # Master-detail RBAC screen permission matrix
│   └── login.php             # Authentication sign-in screen
└── assets/
    ├── css/                  # Professional light theme stylesheet
    └── js/                   # UI interactivity & modal scripts
```