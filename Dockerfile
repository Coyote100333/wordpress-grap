# Using the 'DESIRED_' prefix to avoid confusion with environment variables of the same name.
ARG DESIRED_WP_VERSION
ARG DESIRED_PHP_VERSION
ARG OFFICIAL_WORDPRESS_DOCKER_IMAGE="wordpress:${DESIRED_WP_VERSION}-php${DESIRED_PHP_VERSION}-apache"


# -----------------STAGE--------------------
# Sets timezone to UTC and installs XDebug for PHP 7.X.
FROM ${OFFICIAL_WORDPRESS_DOCKER_IMAGE} as wordpress-utc-xdebug

RUN echo 'date.timezone = "UTC"' > /usr/local/etc/php/conf.d/timezone.ini \
  && if echo "${PHP_VERSION}" | grep '^7.'; then pecl install xdebug; docker-php-ext-enable xdebug; fi


# -----------------STAGE--------------------
# Installs dependencies to run a Wordpress+wp-graphql SUT (system-under-test)
FROM wordpress-utc-xdebug as wordpress-wp-graphql-sut

# Install wp-cli, pdo_mysql, xdebug (for PHP 7.X), PHP composer
RUN curl -O 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar' \
  && chmod +x wp-cli.phar \
  && mv wp-cli.phar /usr/local/bin/wp \
  && apt-get update -y \
  && apt-get install --no-install-recommends -y mysql-client subversion \
  && rm -rf /var/lib/apt/lists/* \
  && docker-php-ext-install pdo_mysql \
  && curl -Ls 'https://raw.githubusercontent.com/composer/getcomposer.org/d3e09029468023aa4e9dcd165e9b6f43df0a9999/web/installer' | php -- --quiet \
  && chmod +x composer.phar \
  && mv composer.phar /usr/local/bin/composer


ENV PROJECT_DIR=/usr/src/wordpress/wp-content/plugins/wp-graphql/ \
  PRISTINE_WP_DIR=/usr/src/wordpress/ \
  WP_TEST_CORE_DIR=/tmp/wordpress/ \
  WP_TESTS_DIR=/tmp/wordpress-tests-lib/ \
  WP_TESTS_TAG=tags/$WORDPRESS_VERSION

# Install WP test framework
RUN mkdir -p "${WP_TESTS_DIR}" \
  && svn co --quiet "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/" "${WP_TESTS_DIR}/includes" \
  && svn co --quiet "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/" "${WP_TESTS_DIR}/data" \
  && curl -Lsv "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php" > "${WP_TESTS_DIR}/wp-tests-config.php" \
  && chown -R 'www-data:www-data' "${WP_TESTS_DIR}"

RUN mkdir "${PROJECT_DIR}"

# First copy the files needed for PHP composer install so that the Docker build only re-executes the install when those
# files change.
COPY --chown='www-data:www-data' composer.json composer.lock "${PROJECT_DIR}"/
COPY --chown='www-data:www-data' src/ "${PROJECT_DIR}/src/"
COPY --chown='www-data:www-data' vendor/ "${PROJECT_DIR}/vendor/"

# Run PHP Composer install so that Codeception dependencies are available
USER www-data
RUN cd "${PROJECT_DIR}" \
  && composer install

# Copy in all other files from repo, but preserve the files used by/modified by composer install.
USER root
COPY --chown='www-data:www-data' . /tmp/project/
RUN rm -rf /tmp/project/composer.* /tmp/project/vendor \
  && cp -a /tmp/project/* "${PROJECT_DIR}" \
  && rm -rf /tmp/project

# Install code coverage support
RUN curl -L 'https://raw.github.com/Codeception/c3/2.0/c3.php' > "${PROJECT_DIR}/c3.php"

# Make sure www-data is the owner of all project files
RUN chown -R 'www-data:www-data' "${PROJECT_DIR}"

# Copy WordPress files to test core directory and add the db.php for WP tests
RUN cp -a "${PRISTINE_WP_DIR}" "${WP_TEST_CORE_DIR}"

RUN curl -Ls 'https://raw.github.com/markoheijnen/wp-mysqli/master/db.php' > "${WP_TEST_CORE_DIR}/wp-content/db.php"


# -----------------STAGE--------------------
# Installs dependencies to run unit tests and integration tests against the Wordpress+wp-graphql SUT (system-under-test)
FROM wordpress-wp-graphql-sut as wordpress-wp-graphql-tester

# Copy docker-entrypoints to a directory that's already in the environment PATH
COPY docker-entrypoint.tests.sh /usr/local/bin/

WORKDIR /tmp/wordpress/wp-content/plugins/wp-graphql

ENTRYPOINT [ "docker-entrypoint.tests.sh" ]
