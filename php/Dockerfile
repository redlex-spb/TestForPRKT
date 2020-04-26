FROM php:7.4-cli

#FROM php:7.2.2-apache
# Install YAML extension
#RUN apt-get update -y
#RUN apt-get install libyaml-dev -y
#RUN  pecl install yaml && echo "extension=yaml.so" > /usr/local/etc/php/conf.d/ext-yaml.ini && docker-php-ext-enable yaml

ADD https://raw.githubusercontent.com/mlocati/docker-php-extension-installer/master/install-php-extensions /usr/local/bin/
RUN chmod uga+x /usr/local/bin/install-php-extensions && sync && \
        install-php-extensions yaml

WORKDIR /var/www