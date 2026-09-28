#!/bin/bash

set -Eeuo pipefail

defaultVariant="apache"

self="$(basename "$BASH_SOURCE")"
cd "$(dirname "$(readlink -f "$BASH_SOURCE")")"

[ -f versions.json ] # run "versions.sh" first

# get the most recent commit which modified any of "$@"
fileCommit() {
	git log -1 --format='format:%H' HEAD -- "$@"
}

# get the most recent commit which modified "$1/Dockerfile" or any file COPY'd from "$1/Dockerfile"
dirCommit() {
	local dir="$1"; shift
	(
		cd "$dir"
		fileCommit \
			Dockerfile \
			$(git show HEAD:./Dockerfile | awk '
				toupper($1) == "COPY" {
					for (i = 2; i < NF; i++) {
						print $i
					}
				}
			')
	)
}

# prints "$2$1$3$1...$N"
join() {
	local sep="$1"; shift
	local out; printf -v out "${sep//%/%%}%s" "$@"
	echo "${out#$sep}"
}

cat <<-EOH
# this file is generated via https://github.com/espocrm/espocrm-docker/blob/$(fileCommit "$self")/$self

Maintainers: Taras Machyshyn <docker@espocrm.com> (@tmachyshyn)
GitRepo: https://github.com/espocrm/espocrm-docker.git
EOH

versions="$(jq -r 'keys | map(@sh) | join(" ")' versions.json)"
eval "set -- $versions"

for version; do
	export version

	variantList="$(jq -r '.[env.version].variantList | map(@sh) | join(" ")' versions.json)"
	eval "variantList=( $variantList )"

	fullVersion="$(jq -r '.[env.version].version' versions.json)"

	for variant in "${variantList[@]}"; do
		export variant

		dir="$version/$variant"
		[ -f "$dir/Dockerfile" ] || continue

		distribution="$(jq -r '.[env.version].distributions[env.variant]' versions.json)"

		declare -a tags=("$variant" "$distribution" "$fullVersion-$variant" "$fullVersion-$distribution")

		tags+=("$(expr "$fullVersion" : '\([0-9]*.[0-9]*\)')-$variant")
		tags+=("$(expr "$fullVersion" : '\([0-9]*.[0-9]*\)')-$distribution")

		tags+=("$(expr "$fullVersion" : '\([0-9]*\)')-$variant")
		tags+=("$(expr "$fullVersion" : '\([0-9]*\)')-$distribution")

		if [ "$defaultVariant" == "$variant" ]; then
			tags+=("latest")
			tags+=("$fullVersion")
			tags+=("$(expr "$fullVersion" : '\([0-9]*.[0-9]*\)')")
			tags+=("$(expr "$fullVersion" : '\([0-9]*\)')")
		fi

		echo
		cat <<-EOE
			Tags: $(join ', ' "${tags[@]}")
			Architectures: $(jq -r '.[env.version].architectures[env.variant]' versions.json)
			GitCommit: $(dirCommit "$dir")
			Directory: $dir
		EOE

	done
done
