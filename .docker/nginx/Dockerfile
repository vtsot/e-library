FROM nginx:mainline-alpine

ARG DUMB_INIT_VERSION=1.2.5
ENV DUMB_INIT_VERSION=${DUMB_INIT_VERSION}

ADD https://github.com/Yelp/dumb-init/releases/download/v${DUMB_INIT_VERSION}/dumb-init_${DUMB_INIT_VERSION}_x86_64 /usr/local/bin/dumb-init
RUN chmod +x /usr/local/bin/dumb-init

COPY ./config/nginx.conf /etc/nginx/nginx.conf
COPY ./config/conf.d/default.conf /etc/nginx/conf.d/default.conf

ENTRYPOINT ["dumb-init", "--rewrite", "15:3"]
CMD ["nginx", "-g", "daemon off;"]

WORKDIR /var/www/html
