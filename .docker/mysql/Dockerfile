FROM mysql:5.7

COPY ./config/* /etc/mysql/conf.d/
RUN chmod 600 /etc/mysql/conf.d/*

# override parent CMD to avoid ERROR 2059 (HY000): Authentication plugin 'caching_sha2_password' cannot be loaded
CMD ["mysqld", "--default-authentication-plugin=mysql_native_password", "--explicit_defaults_for_timestamp=on", "--skip-name-resolve=ON", "--character-set-server=utf8mb4", "--max_allowed_packet=64M", "--sql-mode="]
