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

git config --global user.email "10upbot+github@10up.com"
git config --global user.name "10upbot on GitHub"

git remote set-url origin https://x-access-token:$GITHUB_TOKEN@github.com/10up/classifai.git

# Use worktree to check out into a subfolder so we don't have lingering files hanging out
# This assumes there's already a stable branch!
git worktree add -B stable release origin/stable

# Backup the .git folder
cp -a release/.git "$TMP/"

# Copy from clean copy into release folder
# The --delete flag will delete anything in destination that no longer exists in source
rsync -r "$GITHUB_WORKSPACE/" release/ --exclude-from=".github/action-release/rsync-filter.txt" --delete

# Put .git folder back
cp -a "$TMP/.git" release/

# Commit everything
cd release
git commit -am "Committing latest production-ready version of master"
git push origin stable

echo "✓ All set!"
