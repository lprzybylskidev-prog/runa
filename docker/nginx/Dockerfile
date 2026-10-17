FROM nginx:1.31.7-alpine
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY public/ /workspace/public/
USER nginx
ENTRYPOINT ["nginx", "-g", "daemon off;"]
HEALTHCHECK --interval=10s --timeout=3s --retries=5 CMD wget -qO- http://127.0.0.1:8080/api/health || exit 1
