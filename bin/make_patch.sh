#!/bin/bash

function usage() {
echo -e "${0} <base branch|base hash> <comparison branch|comparison hash> <tag>"
}


if [[ $# -ne 3 ]]; then
	usage
	exit 1
fi

BASE=${1}
COMPARISON=${2}
TAG=${3}

git diff --name-only ${BASE}...${COMPARISON} | grep -v 'README.md\|tests\|bin' | xargs git diff ${BASE}...${COMPARISON} > ${TAG}.diff
