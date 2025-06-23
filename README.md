# PHP Dashboard Application

A comprehensive vanilla PHP dashboard application with full CRUD operations, search, pagination, and image upload functionality.

## Features

- **Dashboard Overview**: Statistics cards and recent orders display
- **User Management**: Complete CRUD operations for users with password hashing
- **Category Management**: Categories with automatic slug generation
- **Product Management**: Products with image upload, category relationships
- **Order Management**: Orders with user relationships and status tracking
- **Slug Management**: URL-friendly slugs for products and categories
- **Search & Filter**: Advanced search and filtering across all modules
- **Pagination**: Efficient pagination for large datasets
- **Responsive Design**: Bootstrap-based responsive UI
- **Image Upload**: Secure image upload with validation

## Database Schema

The application uses 5 related tables:
- `users`: User information and authentication
- `categories`: Product categories with slugs
- `products`: Products with images, prices, and category relationships
- `orders`: Customer orders with user relationships
- `slugs`: URL-friendly slugs for SEO

## Installation

1. **Database Setup**:
   - Create a MySQL database named `dashboard_db`
   - Import the schema from `database/schema.sql`
   - Update database credentials in `config/database.php`

2. **File Permissions**:
   - Create an `uploads/` directory in the root
   - Set write permissions: `chmod 755 uploads/`

3. **Web Server**:
   - Place files in your web server directory
   - Ensure PHP 7.4+ with PDO MySQL extension

## File Structure

\`\`\`
├── config/
│   └── database.php          # Database configuration
├── includes/
│   └── functions.php         # Helper functions
├── database/
│   └── schema.sql           # Database schema and sample data
├── uploads/                 # Image upload directory
├── index.php               # Dashboard homepage
├── users.php               # User management
├── categories.php          # Category management
├── products.php            # Product management
├── orders.php              # Order management
└── slugs.php               # Slug management
\`\`\`

## Key Features

### CRUD Operations
- Create, Read, Update, Delete for all entities
- Form validation and error handling
- Modal-based forms for better UX

### Search & Filtering
- Real-time search across all modules
- Advanced filtering by categories, status, etc.
- Persistent search parameters in pagination

### Image Upload
- Secure file upload with type validation
- Automatic thumbnail generation
- File size limits and error handling

### Relationships
- Foreign key relationships between tables
- Cascading operations where appropriate
- Join queries for efficient data retrieval

### Security
- Password hashing for user accounts
- SQL injection prevention with prepared statements
- File upload security measures
- XSS protection with htmlspecialchars()

## Usage

1. **Dashboard**: View overview statistics and recent activity
2. **Users**: Manage user accounts and authentication
3. **Categories**: Create and organize product categories
4. **Products**: Add products with images and category assignments
5. **Orders**: Track customer orders and status updates
6. **Slugs**: Manage SEO-friendly URLs for products and categories

## Database Configuration

Update `config/database.php` with your database credentials:

\`\`\`php
private $host = 'localhost';
private $db_name = 'dashboard_db';
private $username = 'your_username';
private $password = 'your_password';
\`\`\`

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- GD extension for image processing
- PDO MySQL extension

## License

This project is open source and available under the MIT License.
