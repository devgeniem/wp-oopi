FROM php:7.4-cli

MAINTAINER devgeniem

WORKDIR /usr/src/app

# Install pip and pywatch.
RUN \
    apt update && \
    apt install -y python-pip && \
    pip install -U pip && \
    pip install pywatch

# Install xdebug
RUN \

    # Install Xdebug 3
    (echo '' | pecl install xdebug) && \

    # Remove cache and tmp files
    rm -rf /var/cache/apk/* && \
    rm -rf /tmp/*

CMD bash -C '/usr/src/app/docker/php/cmd.sh';'bash'
