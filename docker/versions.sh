#!/bin/bash

set -Eeuo pipefail

cd "$(dirname "$(readlink -f "$BASH_SOURCE")")"

if [ -n "${1-}" ]; then
	latestRelease=$(curl -s "https://s.espocrm.com/release/info/?version=$1")
else
	latestRelease=$(curl -s "https://s.espocrm.com/release/latest")
fi

version=$(echo $latestRelease | jq -r '.version')
downloadUrl=$(echo $latestRelease | jq -r '.package')
sha256=$(echo $latestRelease | jq -r '.packageSha256')

json="$(jq \
	--arg version "$version" \
	--arg downloadUrl "$downloadUrl" \
	--arg sha256 "$sha256" \
	'.latest.version = $version
	| .latest.downloadUrl = $downloadUrl
	| .latest.sha256 = $sha256' versions.json)"

jq <<<"$json" -S . > versions.json
