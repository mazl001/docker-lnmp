FROM nginx:1.9.0

ADD nginx.conf /etc/nginx/nginx.conf
ADD sites-enabled/* /etc/nginx/sites-enabled/
RUN mkdir -p /www/htdocs && mkdir -p /www/log/nginx
RUN chown -R www-data.www-data /www/htdocs /www/log
