ARG PHP_VERSION=8.4.27
ARG CONFIG_DIR=config          # A/B: web=config (vuln), webpatched=config-patched (8.4.28 upload_progress-default-off semantics)
FROM php:${PHP_VERSION}-apache

ARG CONFIG_DIR    # re-declare inside stage to inherit build-arg (else expands empty)
RUN a2dismod mpm_event && a2enmod mpm_prefork

COPY config/zz-portal.conf /etc/apache2/conf-enabled/
COPY ${CONFIG_DIR}/99-portal.ini /usr/local/etc/php/conf.d/
COPY app/ /var/www/html/

RUN mkdir -p /var/www/html/uploads /var/www/html/reports \
    && chown -R www-data:www-data /var/www/html

# ptrace harness: SIGKILL a prefork child between pwrite() and ftruncate()
COPY zap /usr/local/bin/zap
RUN chmod 0755 /usr/local/bin/zap

EXPOSE 80
