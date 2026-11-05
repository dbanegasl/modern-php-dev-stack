#!/bin/sh
# Genera el certificado SSL autofirmado de desarrollo (server.crt / server.key).
# Uso:   sh docker/ssl/generate.sh [--force] [--out DIR] [HOST_O_IP ...]
# Ej.:   sh docker/ssl/generate.sh 192.168.1.50 dev.local
# localhost y 127.0.0.1 siempre se incluyen como SAN; los argumentos añaden más.
# Los archivos generados están en .gitignore y nunca deben commitearse.
set -eu

OUT="$(dirname "$0")"
FORCE=0
SANS="DNS:localhost,IP:127.0.0.1"

while [ $# -gt 0 ]; do
  case "$1" in
    --force) FORCE=1 ;;
    --out)   shift; OUT="$1" ;;
    *)
      if echo "$1" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$'; then SANS="$SANS,IP:$1"
      else SANS="$SANS,DNS:$1"; fi ;;
  esac
  shift
done

if [ -e "$OUT/server.crt" ] || [ -e "$OUT/server.key" ]; then
  if [ "$FORCE" -ne 1 ]; then
    echo "Ya existe un certificado en $OUT. Usa --force para sobrescribirlo." >&2
    exit 1
  fi
fi

openssl req -x509 -nodes -newkey rsa:2048 -days 3650 \
  -keyout "$OUT/server.key" -out "$OUT/server.crt" \
  -subj "/C=US/ST=State/L=City/O=Development/OU=Local/CN=localhost" \
  -addext "subjectAltName=$SANS"
chmod 644 "$OUT/server.crt" "$OUT/server.key"   # los lee el usuario no-root de nginx/apache
echo "Certificado generado en $OUT (SAN: $SANS)"
