#!/bin/bash
set -e # exit with nonzero exit code if anything fails

# clear and re-create the out directory
rm -rf docs || exit 0
mkdir docs

# run our compile script, discussed above
./vendor/bin/phpdoc

git config user.name "GitHub Actions"
git config user.email "github@localhost"

git checkout --orphan gh-pages
git reset
git add docs
git commit -m "Deploy documentation to GitHub Pages"

# Force push to the remote. (All previous history on the gh-pages branch
# will be lost, since we are overwriting it.)
git push --force origin gh-pages
