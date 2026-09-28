#!/bin/bash

set -Eeuo pipefail

[ -f versions.json ] # run "versions.sh" first

jqt='.jq-template.awk'
if [ -n "${BASHBREW_SCRIPTS:-}" ]; then
	jqt="$BASHBREW_SCRIPTS/jq-template.awk"
elif [ "$BASH_SOURCE" -nt "$jqt" ]; then
	# https://github.com/docker-library/bashbrew/blob/master/scripts/jq-template.awk
	wget -qO "$jqt" 'https://github.com/docker-library/bashbrew/raw/9f6a35772ac863a0241f147c820354e4008edf38/scripts/jq-template.awk'
fi

versions="$(jq -r 'keys | map(@sh) | join(" ")' versions.json)"
eval "set -- $versions"

generated_warning() {
	cat <<-EOH
		#
		# NOTE: THIS DOCKERFILE IS GENERATED VIA "apply-templates.sh"
		#
		# PLEASE DO NOT EDIT IT DIRECTLY.
		#

	EOH
}

for version; do
	export version

	rm -rf "$version/"

	phpVersion="$(jq -r '.[env.version].phpVersion' versions.json)"
	export phpVersion

	variantList="$(jq -r '.[env.version].variantList | map(@sh) | join(" ")' versions.json)"
	eval "variantList=( $variantList )"

	for variant in "${variantList[@]}"; do
		export variant

		dir="$version/$variant"
		mkdir -p "$dir"

		distribution="$(jq -r '.[env.version].distributions[env.variant]' versions.json)"
		export distribution

		template="$(jq -r '.[env.version].templates[env.variant]' versions.json)"

		{
			generated_warning
			gawk -f "$jqt" Dockerfile-$template.template
		} > "$dir/Dockerfile"

		cp docker-*.sh "$dir"/
		cp entrypoint-utils.sh "$dir"/
	done
done
