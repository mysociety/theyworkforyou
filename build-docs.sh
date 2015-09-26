#!/bin/bash
set -e # exit with nonzero exit code if anything fails

# install graphviz to generate diagrams
sudo apt-get update -qq
sudo apt-get install -qq graphviz

# clear and re-create the out directory
rm -rf www/docs/docs || exit 0;
mkdir www/docs/docs;

# run our compile script, discussed above
./vendor/bin/phpdoc

# go to the out directory and create a *new* Git repo
cd www/docs/docs
git init

# inside this git repo we'll pretend to be a new user
git config user.name "Travis CI"
git config user.email "travis@mysociety.org"

# The first and only commit to this new Git repo contains all the
# files present with the commit message "Deploy documentation to GitHub Pages".
git add .
git commit -m "Deploy documentation to GitHub Pages"

# Force push from the current repo's master branch to the remote
# repo's gh-pages branch. (All previous history on the gh-pages branch
# will be lost, since we are overwriting it.) We redirect any output to
# /dev/null to hide any sensitive credential data that might otherwise be exposed.
git push --force --quiet "https://${GH_TOKEN}@github.com/mysociety/theyworkforyou.git" master:gh-pages > /dev/null 2>&1
