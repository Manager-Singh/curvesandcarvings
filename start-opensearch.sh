#!/usr/bin/env bash
# Start local OpenSearch for Magento catalog search
set -euo pipefail

CONTAINER="magento-opensearch"

if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
  echo "OpenSearch already running."
  exit 0
fi

if docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
  echo "Starting existing container: ${CONTAINER}"
  docker start "${CONTAINER}"
else
  echo "Creating OpenSearch container: ${CONTAINER}"
  docker run -d --name "${CONTAINER}" \
    --restart unless-stopped \
    -p 9200:9200 -p 9600:9600 \
    -e "discovery.type=single-node" \
    -e "DISABLE_SECURITY_PLUGIN=true" \
    -e "OPENSEARCH_JAVA_OPTS=-Xms512m -Xmx512m" \
    opensearchproject/opensearch:2.11.1
fi

for i in $(seq 1 30); do
  if curl -sf "http://127.0.0.1:9200" >/dev/null 2>&1; then
    echo "OpenSearch ready at http://127.0.0.1:9200"
    exit 0
  fi
  sleep 2
done

echo "OpenSearch did not become ready in time." >&2
exit 1
