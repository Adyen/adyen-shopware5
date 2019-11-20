#!/usr/bin/env bash

commit=$1
if [ -z ${commit} ]; then
    commit=$(git tag --sort=-creatordate | head -1)
    if [ -z ${commit} ]; then
        commit="master";
    fi
fi

# Remove old release
rm -rf MeteorAdyen-*.zip

# Build new release
mkdir -p MeteorAdyen
git archive ${commit} | tar -x -C MeteorAdyen
composer install --no-dev -n -o -d MeteorAdyen
( find ./MeteorAdyen -type d -name ".git" && find ./MeteorAdyen -name ".gitignore" && find ./MeteorAdyen -name ".gitmodules" ) | xargs rm -r
( find ./MeteorAdyen -type d -name 'scripts' && find ./MeteorAdyen -type f -name 'Jenkinsfile-*') | xargs rm -r
zip -r MeteorAdyen-${commit}.zip MeteorAdyen

# Remove tmp folder
rm -rf MeteorAdyen