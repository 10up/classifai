workflow "Build Release" {
  resolves = ["WordPress Plugin Deploy"]
  on = "push"
}

# Filter for master branch
action "master" {
    uses = "actions/bin/filter@master"
    args = "branch master"
}

action "Install" {
  needs = "master"
  uses = "actions/npm@master"
  args = "install"
}

action "Build" {
  needs = "Install"
  uses = "actions/npm@master"
  args = "run build"
}

action "Release to Stable" {
  needs = ["Build"]
  uses = "./github/action-release/"
  secrets = ["GITHUB_TOKEN"]
}
