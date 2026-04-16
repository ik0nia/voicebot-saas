FROM node:20-alpine AS base

WORKDIR /app

# Install deps first — separate layer so package.json-only changes
# don't invalidate the node_modules cache.
COPY package.json package-lock.json* ./
RUN npm install --omit=dev --no-audit --no-fund

COPY src ./src

# Drop root for defence-in-depth; the node image's built-in `node`
# user has no shell and no privileges.
USER node

ENV NODE_ENV=production
ENV PORT=8080

EXPOSE 8080

# SIGTERM-aware — index.js handles graceful shutdown so `docker stop`
# lets active calls finish (up to 30s) instead of dropping them.
CMD ["node", "src/index.js"]
