FROM php:8.2-fpm-alpine

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Command-injection labs need real `ping` and `nslookup` binaries
# reachable from PHP (see webhook_test.php and dashboard/admin/diagnostics.php).
RUN apk add --no-cache iputils bind-tools

COPY php.ini /usr/local/etc/php/conf.d/99-jobhunt.ini

WORKDIR /var/www/html

RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads

# CTF flag — only reachable if you achieve RCE (e.g. via the command-injection
# chain in webhook_test.php or dashboard/admin/diagnostics.php).
# After exploitation try:  ls /   →  cat /flag_rce.txt
RUN echo 'FLAG{RCE_www_data_jobhunt_pwned_2026}' > /flag_rce.txt && chmod 644 /flag_rce.txt

EXPOSE 9000
