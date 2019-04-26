workflow "Build Release" {
  resolves = ["Release to Stable"]
  on = "push"
}

# Filter for master branch
action "master" {
    uses = "actions/bin/filter@master"
    args = "branch develop"
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
