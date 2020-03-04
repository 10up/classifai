#!/bin/bash

set -eou pipefail

# IMPORTANT: while secrets are encrypted and not viewable in the GitHub UI,
# they are by necessity provided as plaintext in the context of the Action,
# so do not echo or use debug mode unless you want your secrets exposed!
if [[ -z "$GITHUB_TOKEN" ]]; then
	echo "Set the GITHUB_TOKEN env variable"
	exit 1
fi

TMP="/github/tmp"
mkdir $TMP

# I think we are already here but just in case
cd "$GITHUB_WORKSPACE"

echo "ℹ︎ Configuring git"
git config --global user.email "10upbot+github@10up.com"
git config --global user.name "10upbot on GitHub"

git remote set-url origin "https://x-access-token:$GITHUB_TOKEN@github.com/$GITHUB_REPOSITORY.git"

echo "ℹ︎ Getting stable branch"
# Fetch everything
git fetch
# Use worktree to check out into a subfolder so we don't have lingering files hanging out
# This assumes there's already a stable branch!
git worktree add -B stable release origin/stable

echo "ℹ︎ Copying files"
# Backup the .git folder
cp -a release/.git "$TMP/"

# Copy from clean copy into release folder
# The --delete flag will delete anything in destination that no longer exists in source
rsync -r "$GITHUB_WORKSPACE/" release/ --exclude-from=".github/action-release/rsync-filter.txt" --delete

# Put .git folder back
cp -a "$TMP/.git" release/

echo "ℹ︎ Committing files"
cd release

# Explicit add command because -a doesn't pick up new files
git add .

# Skipping pre-commit hook because it's not installed in stable
git commit -m "Committing built version of $GITHUB_SHA" --no-verify
git push origin stable --no-verify

echo "✓ All set!"
