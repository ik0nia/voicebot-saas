FROM node:20-alpine AS node
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci
COPY resources/ resources/
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

FROM nginx:alpine
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public/ /var/www/html/public/
COPY --from=node /app/public/build /var/www/html/public/build/
