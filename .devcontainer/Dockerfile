FROM mcr.microsoft.com/devcontainers/php:1-8.2
# Remove broken Yarn apt source that ships in the base image (invalid GPG key).
RUN rm -f /etc/apt/sources.list.d/yarn.list
