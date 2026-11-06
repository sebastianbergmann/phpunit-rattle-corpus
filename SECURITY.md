# Security Policy

If you believe you have found a security vulnerability in `phpunit/rattle-corpus`, please report it to me through coordinated disclosure.

Use my [PGP key](https://sebastian-bergmann.de/gpg.asc) for encrypted email, for example when your report includes proof-of-concept exploits against third-party systems.

**Please do not report security vulnerabilities through public GitHub issues, discussions, or pull requests.**

`phpunit/rattle-corpus` writes files to the cache directory that it is configured with, with the privileges of the user that runs PHPUnit.
