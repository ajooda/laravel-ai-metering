# Security Policy

## Supported Versions

We actively support the following versions with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

Security updates are provided for the latest minor version. We recommend keeping your installation up to date.

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

### How to Report

If you discover a security vulnerability in this package, please report it through one of the following channels:

1. **Email** (Preferred): Send an email to [abdalhadijouda@gmail.com](mailto:abdalhadijouda@gmail.com)
   - Use a descriptive subject line: `[SECURITY] Brief description`
   - Include details about the vulnerability
   - Include steps to reproduce (if applicable)

2. **GitHub Security Advisories**: If you have a GitHub account, you can use [GitHub's Security Advisory feature](https://github.com/ajooda/laravel-ai-metering/security/advisories/new)

### What to Include

When reporting a vulnerability, please include:

- Description of the vulnerability
- Steps to reproduce (if applicable)
- Potential impact
- Suggested fix (if you have one)
- Your contact information (optional, for follow-up questions)

### Response Time

We aim to:

- **Acknowledge** your report within 48 hours
- **Provide an initial assessment** within 7 days
- **Keep you informed** of our progress
- **Resolve critical vulnerabilities** as quickly as possible

### Disclosure Policy

- We will work with you to understand and resolve the issue
- We will credit you for the discovery (unless you prefer to remain anonymous)
- We will publish a security advisory once the issue is resolved
- We will coordinate public disclosure with you

## What is Considered a Security Vulnerability?

Security vulnerabilities include, but are not limited to:

- **SQL Injection**: Vulnerabilities that allow SQL injection
- **Cross-Site Scripting (XSS)**: XSS vulnerabilities in user-facing components
- **Authentication/Authorization Bypass**: Issues that allow unauthorized access
- **Sensitive Data Exposure**: Exposure of sensitive information (API keys, tokens, etc.)
- **Insecure Direct Object References**: Access to data without proper authorization
- **Race Conditions**: Security issues related to concurrent access
- **Insecure Deserialization**: Vulnerabilities in data deserialization
- **Insufficient Logging**: Security events not being logged properly

### What is NOT Considered a Security Vulnerability?

The following are typically not considered security vulnerabilities:

- **Feature requests**: Missing features or functionality
- **Performance issues**: Slow queries or performance problems (unless they lead to DoS)
- **Best practice suggestions**: Code quality improvements
- **Documentation issues**: Missing or unclear documentation
- **Non-exploitable issues**: Issues that cannot be exploited in practice
- **Issues in dependencies**: Report to the dependency maintainer first

## Security Best Practices

When using this package, please follow these security best practices:

1. **Keep the package updated**: Always use the latest version
2. **Secure your API keys**: Never commit API keys to version control
3. **Use environment variables**: Store sensitive configuration in `.env`
4. **Validate input**: Always validate user input before passing to the package (feature names, metadata, etc.)
5. **Use HTTPS**: Always use HTTPS in production
6. **Review logs**: Regularly review logs for suspicious activity (enable `AI_METERING_LOGGING_ENABLED`)
7. **Limit access**: Use proper authentication and authorization for routes using the `ai.quota` middleware
8. **Enable security features**: Configure security settings in `config/ai-metering.php`:
   - Enable feature name validation (`validate_feature_names`)
   - Enable metadata sanitization (`sanitize_metadata`)
   - Enable race condition prevention (`prevent_race_conditions`)
9. **Protect sensitive data**: Be mindful of metadata stored in usage records (GDPR compliance)
10. **Monitor usage**: Regularly review usage patterns for anomalies

## Security Updates

Security updates will be:

- Released as patch versions (e.g., 1.0.0 → 1.0.1)
- Documented in [CHANGELOG.md](CHANGELOG.md)
- Announced via GitHub releases
- Tagged with security labels

## Thank You

We appreciate your help in keeping this package secure. Security researchers who report valid vulnerabilities will be credited in our security advisories (unless they prefer to remain anonymous).
