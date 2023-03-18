# Use an official Ubuntu as a parent image
FROM ubuntu:latest

# Set the timezone and disable interactive prompts
ENV TZ=America/New_York \
    DEBIAN_FRONTEND=noninteractive

# Install Apache, PHP and other required packages
RUN apt-get update && \
    apt-get install -y apache2 php libapache2-mod-php && \
    rm -rf /var/lib/apt/lists/*

# Copy all files from the host to the container
COPY . /var/www/html/

# Expose port 80 for HTTP traffic
EXPOSE 80

# Start Apache in the foreground when the container starts
CMD ["apache2ctl", "-D", "FOREGROUND"]
