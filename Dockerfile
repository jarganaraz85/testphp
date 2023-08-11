# Use the official PHP image as the base image
FROM php:7.4-apache

# Copy your PHP script into the container
COPY index.php /var/www/html/

# Expose port 80 for Apache
EXPOSE 80