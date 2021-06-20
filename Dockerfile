FROM alpine:3.14

WORKDIR /twfy

RUN apk add wget php7-dev php7-cli php7-common memcached

RUN apk add php7-apache2 apache2 apache2-proxy php7-fpm php7-opcache php7-phar php7-pdo php7-pdo_mysql php7-ctype php7-pecl-imagick php7-curl php7-json php7-memcache php7-mbstring php7-ctype

RUN apk add ruby-bundler ruby-dev libffi-dev build-base

RUN apk add perl-error perl-yaml perl-dbi perl-file-slurp perl-html-parser perl-json perl-xml-twig perl-dbd-mysql  py3-lxml py3-mysqlclient xapian-bindings xapian-bindings-perl xapian-bindings-php7

# This should be a mount from host filesystem

COPY . .

RUN cp conf/httpd.vagrant /etc/apache2/conf.d/twfy.conf

RUN sed -i 's@^#LoadModule rewrite_module modules/mod_rewrite\.so@LoadModule rewrite_module modules/mod_rewrite.so@' /etc/apache2/httpd.conf
RUN sed -i 's@^#LoadModule expires_module modules/mod_expires\.so@LoadModule expires_module modules/mod_expires.so@' /etc/apache2/httpd.conf
RUN sed -i 's@^#LoadModule deflate_module modules/mod_deflate\.so@LoadModule deflate_module modules/mod_deflate.so@' /etc/apache2/httpd.conf
RUN sed -i 's@^;extension=xapian.so@extension=xapian.so@' /etc/php7/conf.d/xapian.ini

RUN sed -r \
    -e 's!^(.*"OPTION_TWFY_DB_HOST", *)"[^"]*"!'"\\1'twfy-db'!" \
    -e 's!^(.*"OPTION_TWFY_DB_USER", *)"[^"]*"!'"\\1'twfy'!" \
    -e 's!^(.*"OPTION_TWFY_DB_PASS", *)"[^"]*"!'"\\1'middlesecret'!" \
    -e 's!^(.*"OPTION_TWFY_DB_NAME", *)"[^"]*"!'"\\1'theyworkforyou'!" \
    -e 's!^(.*"OPTION_MEMCACHED_HOST", *)'"''!\\1'memcache'!" \
    -e 's!^(.*"BASEDIR", *)"[^"]*"!'"\\1'/twfy/www/docs'!" \
    -e 's!^(.*"DOMAIN", *)"[^"]*"!'"\\1'$HOST'!" \
    -e 's!^(.*"COOKIEDOMAIN", *)"[^"]*"!'"\\1'$HOST'!" \
    -e 's!^(.*"XAPIANDB", *)'"''!\\1'$DIRECTORY/searchdb'!" \
    conf/general-example > conf/general

RUN php composer.phar install --no-dev --optimize-autoloader

RUN bundle install --deployment --binstubs "vendor/bundle-bin"

WORKDIR /twfy/www/docs/style

RUN bundle exec compass compile

EXPOSE 80

CMD  [ "/usr/sbin/httpd", "-D", "FOREGROUND"]
