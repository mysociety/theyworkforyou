FROM mysocietyorg/debian:buster

# Apache.
RUN apt-get -qq update && apt-get -qq install \
      apache2 \
      libapache2-mod-php \
    --no-install-recommends && \
    rm -r /var/lib/apt/lists/*

# Build dependencies that weren't in `conf/packages`
RUN apt-get -qq update && apt-get -qq install \
      bundler \
      gcc \
      libc6-dev \
      libffi-dev \
      make \
      php-xml \
      php-zip \
      ruby-dev \
      unzip \
    --no-install-recommends && \
    rm -r /var/lib/apt/lists/*

# `conf/packages` - do last, so changes to runtime dependencies
# don't invalidate caches for the above.
COPY conf/packages /tmp/packages
RUN  apt-get update -qq && \
      xargs -a /tmp/packages apt-get install -qq --no-install-recommends && \
      rm -r /var/lib/apt/lists/*

# Apache - enable some modules redirect output to STDOUT/STDERR
RUN /usr/sbin/a2enmod expires rewrite && \
      ln -sfT /proc/self/fd/2 /var/log/apache2/error.log && \
      ln -sfT /proc/self/fd/1 /var/log/apache2/access.log && \
      ln -sfT /proc/self/fd/1 /var/log/apache2/other_vhosts_access.log

# Bind mount your working copy here
WORKDIR /twfy

# Apache will run on port 80, so expose it
EXPOSE 80
