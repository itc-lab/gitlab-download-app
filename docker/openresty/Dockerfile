FROM openresty/openresty:1.19.9.1-5-alpine
RUN apk add --no-cache curl perl dnsmasq && opm install zmartzone/lua-resty-openidc
CMD ["/bin/ash", "-c", "dnsmasq && /usr/local/openresty/bin/openresty -g 'daemon off;'"]
EXPOSE 80 443
