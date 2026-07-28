<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


# 🎓 Alumni Network - API Documentation

This section provides technical documentation for the custom APIs developed for the **Alumni Network** project.

---

## 🔐 Authentication & Password Management Module (v1)

This module handles user authentication, password recovery (forgot password), and password modification for authenticated users. All endpoints are prefixed with `/api/v1/auth`.

### 📌 API Reference Summary

| Method | Endpoint                       | Description                                               | Auth Required          |
| :----- | :----------------------------- | :-------------------------------------------------------- | :--------------------- |
| `POST` | `/api/v1/auth/forgot-password` | Sends a password reset link to the user's email address   | ❌ No                   |
| `POST` | `/api/v1/auth/reset-password`  | Resets the user password using a valid token              | ❌ No                   |
| `POST` | `/api/v1/auth/change-password` | Changes the password for the currently authenticated user | ✅ Yes (`Bearer Token`) |

---

### 📝 Endpoints Detail & Payloads

#### 1. Forgot Password
Sends an email containing a secure password reset link with a unique token to the user's registered email address.

* **URL:** `/api/v1/auth/forgot-password`
* **Method:** `POST`
* **Headers:** `Accept: application/json`
* **Request Body (JSON):**
```json
{
    "email": "student@example.com"
}
2. Reset Password
Resets the user's old password to a new one using the token received in the email link.

URL: /api/v1/auth/reset-password

Method: POST

Headers: Accept: application/json

Request Body (JSON):

JSON
{
    "token": "4a82b0e7f8c12...",
    "email": "student@example.com",
    "password": "new_password_123",
    "password_confirmation": "new_password_123"
}
💡 Note for Frontend Developers:

The reset link generated in AppServiceProvider points to http://localhost:3000/reset-password?token={token}&email={email}. Please extract the token and email query parameters from the URL and include them in this payload.

3. Change Password
Allows an authenticated user to change their account password after verifying their current password.

URL: /api/v1/auth/change-password

Method: POST

Headers:

HTTP
Authorization: Bearer <your_sanctum_access_token>
Accept: application/json
Request Body (JSON):

JSON
{
    "current_password": "old_password_123",
    "password": "new_password_456",
    "password_confirmation": "new_password_456"
}
⚙️ Environment Configuration (.env)
To enable SMTP email delivery for the password reset feature in your local development environment, configure the following variables in your .env file:

Code snippet
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD=your_16_digit_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your_gmail@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
