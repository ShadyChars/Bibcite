FROM php
RUN apt-get update && apt-get -y install \
    git
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions zip intl
ENTRYPOINT [ "bash" ]