#!/usr/bin/env bash
set -e

PROJECT=${PWD##*/}
DB_NAME="wp_${PROJECT/-/_}"
GIT_URL="https://gitlab.ledevsimple.ca/wp-sites/${PROJECT}.git"
GIT_ORIGIN="ssh://git@gitlab.ledevsimple.ca:222/wp-sites/${PROJECT}.git"

# Rename project
sed -i -e "s/wp-boilerplate/${PROJECT}/g" .cpanel.yml .gitignore composer.json

# Initialize git repository
git init
git remote add gitlab ${GIT_ORIGIN}
git fetch gitlab || error_code=$?
if [ "${error_code-0}" -eq 0 ]; then
  git checkout main -f
  rm -rf wp-content/mu-plugins/*
  rm -rf wp-content/plugins/*
  rm -rf wp-content/themes/*
  git reset --hard
  composer install
else
  git remote remove gitlab
  git add -A
  git commit -am 'chore: initial wp-boilerplate project'
fi

# Create database
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME}"

# Download / configure / install WordPress
wp core download --force --skip-content
wp core config --dbname=${DB_NAME}

# Done
echo "Site ready at https://${PROJECT}.${TLD-ledevsimple.ca}"
