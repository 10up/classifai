#! /bin/bash
set -x
set -e

if [ "$UPDATE_RESOURCES" != "true" ]; then
    echo "Skip updating resources. UPDATE_RESOURCES not set to true"
    exit 0
fi

test_prefix=""
if [ "$TRAVIS_BRANCH" != "master" ]; then
    test_prefix=test-
fi

git submodule foreach git pull origin master --ff-only
bin/uaparser ua-parser:convert uap-core/regexes.yaml

if [[ ! `git status --porcelain` ]]; then
    echo "No resource update necessary"
else
    ./vendor/bin/phpunit

    git config --global user.email travis@travis-ci.org
    git config --global user.name "Travis CI"

    git remote add upstream https://${GITHUB_TOKEN}@github.com/ua-parser/uap-php.git
    git fetch upstream --tags

    git status

    git commit -m "Automatic resource update" uap-core resources/regexes.php
    git push upstream ${TRAVIS_BRANCH}

    new_version=$test_prefix`git tag | sort --version-sort | tail -n 1 | awk -F. -v OFS=. 'NF==1{print ++$NF}; NF>1{if(length($NF+1)>length($NF))$(NF-1)++; $NF=sprintf("%0*d", length($NF), ($NF+1)%(10^length($NF))); print}'`

    git tag $new_version
    git push upstream $new_version

    echo "$new_version published"
fi
