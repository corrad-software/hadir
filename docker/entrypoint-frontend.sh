#!/bin/sh
set -e

if [ -z "$API_UPSTREAM" ]; then
    echo "ERROR: API_UPSTREAM is required (e.g. http://<api-internal-host>:80)"
    exit 1
fi

export API_UPSTREAM
envsubst '${API_UPSTREAM}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

echo "Frontend nginx proxying API requests to ${API_UPSTREAM}"

exec nginx -g "daemon off;"
