#!/usr/bin/env bash
# Script tạo chứng chỉ SSL tự ký cho Inventory System
set -e

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
echo "Đang tạo chứng chỉ SSL tự ký tại $DIR..."

openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout "$DIR/server.key" \
    -out "$DIR/server.crt" \
    -config "$DIR/openssl.cnf"

echo "Tạo chứng chỉ SSL thành công:"
echo " - Key: $DIR/server.key"
echo " - Cert: $DIR/server.crt"
