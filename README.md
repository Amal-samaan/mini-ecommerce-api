Mini E-Commerce API 
-About the Project :
This project is a simple E-Commerce API developed using PHP and MySQL. It is part of my backend training
The project allows users to register and log in, view products, and create orders. It also has an admin role that can manage products and update order statuses

-Technologies Used
PHP
MySQL
XAMPP
Composer
Firebase JWT
(I chose PHP because I am learning backend development using PHP. I used MySQL to store the users, products, and orders data. I used XAMPP to run the project locally, Composer to manage PHP libraries, and Firebase JWT to handle user authentication)


-Project Features:
Authentication
Register a new user.
Login using email and password.
Use JWT token for authentication.

Products
View all products.
View one product by ID.
Add a new product.
Update product information.
Delete a product.

Orders
Create a new order.
View my orders.
View all orders as an admin.
Update order status as an admin.
View order details
Cancel a pending order

API Testing:
I used REST Client in VS Code to test the API requests and check the responses

-How to Run the Project:
Open XAMPP Control Panel.
Start Apache and MySQL.
Make sure the project folder is inside the htdocs folder.
Open phpMyAdmin and import the SQL file into the project database.
Check the database connection settings in config/database.php.
Open the project using VS Code and test the API requests using REST Client

*Authentication:
POST /api/auth/register — Register a new user.
POST /api/auth/login — Login.

Products:
GET /api/products — Get all products.
GET /api/products/{id} — Get one product.
POST /api/products/create.php — Create a product.
PUT / PATCH /api/products/{id} — Update a product.
DELETE /api/products/{id} — Delete a product

Orders:
POST /api/orders/create.php — Create a new order.
GET /api/orders/my_orders.php — Get the logged-in user's orders.
GET /api/orders/details.php?id={id} — Get order details.
PATCH /api/orders/cancel.php — Cancel an order.
GET /api/admin/orders.php — Get all orders (Admin only).
PATCH /api/admin/orders/{id}/status — Update order status (Admin only)

Authentication Token:
Some API endpoints require authentication.
After logging in, the user receives a JWT token.
The token must be sent in the Authorization header:
Authorization: Bearer YOUR_TOKEN
Admin endpoints require a token belonging to an admin user.