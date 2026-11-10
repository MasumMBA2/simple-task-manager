# Simple Task Manager

A small task manager built with plain PHP, SQLite, and CSS. No framework or package installation is required.

## Features

- Add tasks
- Mark tasks as complete or incomplete
- Delete tasks
- Automatically save tasks in an SQLite database
- Responsive layout
- Basic CSRF protection and safe output escaping

## Requirements

- PHP 8.1 or newer
- PHP PDO SQLite extension enabled
- Web server (Apache, Nginx, or PHP's built-in server)

## Run locally

1. Clone or download this repository.
2. Open a terminal in the project folder.
3. Start the PHP development server:

   ```bash
   php -S localhost:8000
   ```

4. Open <http://localhost:8000> in your browser.

The app creates `data/tasks.sqlite` automatically on first run. Make sure PHP can write to the project folder.

## Upload to GitHub

Create an empty repository on GitHub, then run these commands from this project folder:

```bash
git init
git add .
git commit -m "Initial commit: simple task manager"
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/YOUR-REPOSITORY.git
git push -u origin main
```

Replace the remote URL with your own repository URL.

## Deploy

Upload the project files to a PHP hosting account with PHP PDO SQLite enabled. Ensure the `data` directory is writable by PHP. For public production hosting, place the SQLite database outside the public web root where possible, or configure the web server to deny all access to the `data` directory.

## License

MIT. See [LICENSE](LICENSE).
