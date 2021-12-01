FROM mysql:5.6

ADD my.cnf /etc/mysql/my.cnf
ADD conf.d/* /etc/mysql/conf.d/

# Write Permission
RUN chown mysql.mysql /var/run/mysqld/

#EXPOSE 3306
