# POS_System

Advanced Full-Stack POS System

A high-performance, secure Point of Sale (POS) solution engineered to streamline retail operations, automate inventory management, and provide deep sales insights. This project serves as a comprehensive demonstration of Relational Database Management (RDBMS), Real-time Inventory Synchronization, and Secure PHP Engineering.

Key Achievements

1.Twelve Table Relational Schema: The system is built on a deeply normalized architecture including tables for Products, Stock Logs, Transaction Items, and Tax Configurations. This ensures 100 percent data integrity and ACID compliance.

2.Enterprise Grade Security: All user accounts are protected via SHA256 hashing. The application uses Prepared Statements for all database interactions to defend against SQL Injection. Access is managed through granular Role-Based Access Control for Admins, Managers, and Cashiers.

3.Professional UI/UX: The design utilizes a Slate and Cobalt visual theme featuring Poppins typography and responsive grid layouts optimized for fast-paced retail environments.

The System Engine and Business Logic

The system is defined by its ability to handle complex retail rules with zero latency:

1.Real Time Inventory Synchronization: The application automatically deducts stock levels at the moment of checkout and triggers low-stock alerts when quantities fall below defined thresholds. Every movement is logged in an Audit Trail for loss prevention.

2.Intelligent Billing Engine: The software provides automated calculation of multi-tier taxes and dynamic discounts. It generates digital receipts and logs unique transaction hashes while supporting void transaction logic with automatic inventory restoration.

3.Management Portals: The Cashier Interface is optimized for rapid item entry, while the Admin Dashboard provides deep-dive analytics into revenue trends and employee performance.

Technical Stack

1.Backend: PHP 8.x with Manual Environment Configuration.

2.Database: MariaDB / MySQL with a Master-Slave ready schema.

3.Security: SHA256 Hashing and Session Hijacking Protection.

4.Presentation: Tailwind CSS and Font Awesome.

5.Analytics: Chart.js for interactive visualizations.

Getting Started

1. Prerequisites

You must have Apache 2.4 configured with the PHP Module, MariaDB 10.x, and PHP 8.x Thread Safe installed on your system.

2. Manual Installation

Clone the repository into your htdocs folder:
git clone https://www.google.com/search?q=https://github.com/yourusername/advanced-pos-system.git

Configure your httpd.conf to link the environment:
LoadModule php_module "C:/WebDev/php/php8apache2_4.dll"
PHPIniDir "C:/WebDev/php"

Create the database named pos_db and import the schema.sql file.

3. Test Credentials

The Admin URL is located at http://localhost:8888/pos_system/login.php.
Use the email admin@pos-tech.com with the password POSManager#2025.

Sales Analytics

The system tracks high-moving items and peak sales hours using Chart.js. This allows managers to optimize staffing and inventory procurement based on historical data.
