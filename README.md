🛠️ Technologies Used

- Backend: PHP 8.1+ with Symfony 6.1
- Database: MySQL 8.0
- ORM: Doctrine 3.5
- Templating: Twig 3.0
- Frontend: HTML5, CSS3 (CSS variables), Vanilla JavaScript
- Security: Symfony Security Bundle (CSRF, auto-hashing)

📋 Prerequisites

Before starting, make sure you have installed:
- PHP >= 8.1 with the following extensions:
- `ext-ctype`
- `ext-iconv`
- `pdo_mysql`
- Composer (PHP dependency manager)
- MySQL >= 8.0

📁 Project Structure

mediconnect/
├── bin/ # Symfony Scripts (console)
├── config/ # Configuration Symfony
│ ├── packages/ # Bundle configuration
│ └── routes/ # Route definitions
├── migrations/ # Database migrations
├── public/ # Public files (entry point)
│ ├── css/ # Stylesheets
│ ├── js/ # JavaScript scripts
│ ├── images/ # Static images
│ └── uploads/ # Uploaded files (certificates)
├── src/ # Application source code
│ ├── Command/ # CLI commands
│ ├── Controller/ # Controllers
│ ├── Entity/ # Doctrine Entities
│ ├── Form/ # Symfony Forms
│ ├── Repository/ # Doctrine Repositories
│ ├── Security/ # Authenticators
│ └── Service/ # Business Services
├── templates/ # Twig Templates
│ ├── admin/ # Administrator Templates
│ ├── doctor/ # Doctor Templates
│ ├── patient/ # Patient Templates
│ └── security/ # Login/Register
├── tests/ # Tests (PHPUnit)
├── var/ # Cache, logs (generated)
├── vendor/ # Composer dependencies (generated)
├── .env.example # Configuration template
├── composer.json # PHP dependencies
└── README.md # This file

📷 Screenshots 

![Homepage](images/accueil.jpg)
"Application Homepage"
![Registration](images/registerpatient.jpg)
"Register page"
![Connexion](images/login.jpg)
"login page"
---> see more in the directory images/
