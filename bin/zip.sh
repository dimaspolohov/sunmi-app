#!/usr/bin/env bash

# Exit if any command fails.
set -e

# Change to the expected directory.
DIR=$(pwd)
BUILD_DIR="$DIR/build/plugin-name"

# Enable nicer messaging for build status.
BLUE_BOLD='\033[1;34m'
GREEN_BOLD='\033[1;32m'
RED_BOLD='\033[1;31m'
YELLOW_BOLD='\033[1;33m'
COLOR_RESET='\033[0m'
error() {
  echo -e "\n${RED_BOLD}$1${COLOR_RESET}\n"
}
status() {
  echo -e "\n${BLUE_BOLD}$1${COLOR_RESET}\n"
}
success() {
  echo -e "\n${GREEN_BOLD}$1${COLOR_RESET}\n"
}
warning() {
  echo -e "\n${YELLOW_BOLD}$1${COLOR_RESET}\n"
}

status "💃 Time to build 🕺"

# remove the build directory if exists and create one
rm -rf "$DIR/build"
mkdir -p "$BUILD_DIR"

# Install composer dependencies.
status "Installing composer DEV dependencies... 📦"
composer install

# Install npm dependencies.
if [ -d "./assets" ]; then
  cd ./assets

  if [ ! -d "./node_modules" ]; then
    status "Installing npm dependencies... 📦"
    npm ci --ignore-scripts
  fi

  status "Generating build... 👷‍♀️"
  npm run build
  cd ..
else
  status "Assets dir not found. Npm dependencies skipped"
fi

#status "Creating language files"
composer run-script make-pot
composer run-script update-po
composer run-script make-mo
composer run-script make-mo-json

# Install composer dependencies.
status "Installing composer PROD dependencies... 📦"
composer install --optimize-autoloader --no-dev -q

# Copy all files
status "Copying files... ✌️"
FILES=(src vendor templates languages wordpress-plugin-template.php main-class-shortcut.php)

for file in ${FILES[@]}; do
  if [ -d "./$file" ] || [ -f "./$file" ];
  then
    cp -R $file $BUILD_DIR
  else
    warning "$file not found"
  fi
done

if [ -d "./assets" ]; then
  mkdir -p "$BUILD_DIR/assets/dist"
  cp -R assets/dist "$BUILD_DIR/assets"
fi

# go one up, to the build dir
if command -v zip; then
  status "Creating archive... 🎁"

  cd ./build
  zip -r -q plugin-name.zip plugin-name

  # remove the source directory
  #rm -rf ./plugin-name
else
  warning "zip command not found. Create archive by yourself ./build/plugin-name"
fi

success "Done. You've built plugin! 🎉 "
