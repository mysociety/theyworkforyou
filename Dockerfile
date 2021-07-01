FROM mysocietyorg/debian:buster

# This includes everything from `conf/packages`, plus some extras
# needed for building the composer and bundler packages
RUN apt-get -qq update && apt-get -qq install \
      apache2 \
      bundler \
      gcc \
      libapache2-mod-php \
      libc6-dev \
      libdbd-mysql-perl \
      libemail-localdelivery-perl \
      liberror-perl \
      libffi-dev \
      libfile-slurp-unicode-perl \
      libhtml-parser-perl \
      libmagickcore-6.q16-3-extra \
      libjson-perl \
      libjson-xs-perl \
      libmailtools-perl \
      libmime-tools-perl \
      libsearch-xapian-perl \
      libxml-rss-perl \
      libxml-twig-perl \
      libyaml-perl \
      make \
      php-cli \
      php-curl \
      php-imagick \
      php-json \
      php-mbstring \
      php-memcache \
      php-mysql \
      php-xml \
      php-zip \
      php7-xapian \
      python-lxml \
      python-mysqldb \
      ruby-dev \
      snarf \
      unzip \
    --no-install-recommends && rm -r /var/lib/apt/lists/*

# Apache - enable some modules redirect output to STDOUT/STDERR
RUN /usr/sbin/a2enmod expires rewrite && \
      ln -sfT /proc/self/fd/2 /var/log/apache2/error.log && \
      ln -sfT /proc/self/fd/1 /var/log/apache2/access.log && \
      ln -sfT /proc/self/fd/1 /var/log/apache2/other_vhosts_access.log

# Bind mount your working copy here
WORKDIR /twfy

# Apache will run on port 80, so expose it
EXPOSE 80
