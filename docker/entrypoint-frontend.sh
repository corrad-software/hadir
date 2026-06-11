#!/bin/sh
set -e

if [ -z "$API_UPSTREAM" ]; then
    echo "ERROR: API_UPSTREAM is required (e.g. http://<api-internal-host>:80)"
    exit 1
fi

api_host=$(echo "$API_UPSTREAM" | sed -E 's#https?://##' | cut -d: -f1 | cut -d/ -f1)

echo "Waiting for API host '${api_host}' to be resolvable..."
i=0
max=90
while [ "$i" -lt "$max" ]; do
    if nslookup "$api_host" 127.0.0.11 >/dev/null 2>&1; then
        echo "API host '${api_host}' is resolvable."
        break
    fi
    i=$((i + 1))
    sleep 1
done

if [ "$i" -eq "$max" ]; then
    echo "WARNING: API host '${api_host}' not resolvable after ${max}s."
    echo "Ensure Frontend and API are on the same Coolify Docker network."
    echo "Coolify: Frontend → Advanced → Connect To Predefined Network (same as API)."
fi

export API_UPSTREAM
envsubst '${API_UPSTREAM}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

echo "Frontend nginx proxying API requests to ${API_UPSTREAM}"

exec nginx -g "daemon off;"
