#!/usr/bin/env bash

#script variables
pluginName="MeteorAdyen";
declare -a blacklistFiles=(".gitignore" "gitignore" ".DS_Store" ".git" "__MACOSX" ".zip" ".tar" ".tar.gz" ".phar" ".php_cs.dist" "phpstan.neon" "grumphp.yml" "build.sh" "build-5.3.7.sh")

commit=$1
if [ -z ${commit} ]; then
    commit=$(git tag --sort=-creatordate | head -1)
    if [ -z ${commit} ]; then
        commit="master";
    fi
fi

# Remove old release
rm -rf $pluginName-*.zip

# Build new release
mkdir -p $pluginName
git archive ${commit} | tar -x -C $pluginName
composer install --no-dev -n -o -d $pluginName

# Remove blacklisted  files
for i in "${blacklistFiles[@]}"
do
 ( find ./$pluginName -name $i ) | xargs rm -r
done

( find ./$pluginName -type d -name 'scripts' && find . -type d -empty && find ./$pluginName -type f -name 'Jenkinsfile-*') | xargs rm -r


sed -i '' 's/minVersion=\"5.6.0\"/minVersion=\"5.3.7\"/' ./$pluginName/plugin.xml

# Create zip with tagged name
zip -r $pluginName-${commit}-[sw5.3.7].zip $pluginName

# Remove tmp folder
rm -rf $pluginName