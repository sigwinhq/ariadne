# Sigwin Ariadne

Find the common thread in your Git repos labyrinth.

## What is it

It's meant to allow common patterns, configuration, metadata across many GitHub / Gitlab repos.

Currently supported:

1. GitHub
2. Gitlab

# Configuration

## Configuration file location

The configuration file is in YAML format and will be searched for in the following locations:

1. `./`
2. [`$XDG_CONFIG_HOME/ariadne/`](https://specifications.freedesktop.org/basedir-spec/basedir-spec-latest.html)
3. `$HOME/.config/ariadne/`

It can be named `ariadne.yaml`, `ariadne.yml`, `ariadne.yaml.dist`, `ariadne.yml.dist`.

## Example

Have a configuration file similar to this: 

```yaml
profiles:
    Gitlab:
        type: gitlab
        client:
            # optional, defaults to gitlab.com
            url: https://my-gitlab.example.com
            auth:
                # create your own: https://gitlab.com/-/profile/personal_access_tokens
                token: glpat-my-t0k3n
            options:
                owned: true
                membership: true
        templates:
            Private:
                filter:
                    # the prefix @= is required to tell Ariadne this is an expression
                    path: "@=match('^sigwin/')"
                target:
                    attribute:
                        merge_method: ff
                        squash_option: always
                        squash_commit_template: "%{title} (%{reference})"

                        merge_requests_enabled: true
                        remove_source_branch_after_merge: true
                        only_allow_merge_if_pipeline_succeeds: true
                        only_allow_merge_if_all_discussions_are_resolved: true

                        releases_access_level: enabled

                        allow_merge_on_skipped_pipeline: false
                        container_registry_enabled: false
                        service_desk_enabled: false
                        lfs_enabled: false
                        issues_enabled: false
                        wiki_enabled: false
                        snippets_enabled: false
                        packages_enabled: false

                        monitor_access_level: disabled
                        pages_access_level: disabled
                        forking_access_level: disabled
                        analytics_access_level: disabled
                        security_and_compliance_access_level: disabled
                        environments_access_level: disabled
                        feature_flags_access_level: disabled
                        infrastructure_access_level: disabled
            Yassg Compat, Gitlab Pages:
                filter:
                    path: "@=match('^sigwin/')"
                    topics: [ yassg-compat, website, gitlab-pages ]
                target:
                    attribute:
                        lfs_enabled: true
                        pages_access_level: public
            Kubernetes Apps:
                filter:
                    path: "@=match('^sigwin/')"
                    topics: kubernetes
                target:
                    attribute:
                        container_registry_enabled: true
                        infrastructure_access_level: private
                        environments_access_level: private
            Docker enabled:
                filter:
                    path: "@=match('^sigwin/')"
                    topics: docker
                target:
                    attribute:
                        container_registry_enabled: true
    Github:
        type: github
        client:
            # optional, defaults to github.com
            url: https://my-github.example.com
            auth:
                # create your own: https://github.com/settings/tokens
                token: ghp_my-t0k3n
        templates:
            Sigwin OSS:
                filter:
                    # the prefix @= is required to tell Ariadne this is an expression
                    path: "@=match('^sigwinhq/')"
                    visibility: public
                    type: source
                target:
                    attribute:
                        has_discussions: false
                        has_downloads: false
                        has_issues: true
                        has_pages: false
                        has_projects: false
                        has_wiki: false
            Forks:
                filter:
                    type: fork
                target:
                    attribute:
                        has_issues: false
            Xezilaires Gitsplit repos:
                filter:
                    # everything except sigwinhq/xezilaires-dev
                    path: "@=match('^sigwinhq/xezilaires(?!-dev)')"
                    visibility: public
                    type: source
                target:
                    attribute:
                        has_issues: false
```

# Operations

## Summary

When you run `ariadne summary` in the same dir as the config file, it will tell you how the target platform sees you and what repos you have access to:

```
$ ariadne summary
                  _             _
     /\          (_)           | |
    /  \    _ __  _   __ _   __| | _ __    ___
   / /\ \  | '__|| | / _` | / _` || '_ \  / _ \
  / ____ \ | |   | || (_| || (_| || | | ||  __/
 /_/    \_\|_|   |_| \__,_| \__,_||_| |_| \___|

 ! [NOTE] Using config: /home/dkarlovi/.config/ariadne/ariadne.yaml

Gitlab
======

 -------------- -------------------------------
  API Version    16.0.0-pre
  API User       dkarlovi
  Repositories   dkarlovi: 2
                 sigwin: 96
  Templates      Private: 96
                 Yassg Compat, Gitlab Pages: 5
                 Kubernetes Apps: 24
                 Docker enabled: 21
 -------------- -------------------------------

Github
======

 -------------- ------------------------------
  API Version    v3
  API User       dkarlovi
  Repositories   dkarlovi: 93
                 sigwinhq: 34
  Templates      Sigwin OSS: 17
                 Forks: 74
                 Xezilaires Gitsplit repos: 4
 -------------- ------------------------------
```

### Summary verbosity

Making the summary more verbose will provide these details

- `-v` lists all templates and the repos they match, optionally naming
other templates they match next to them, if they match multiple templates
- `-vv` also lists all the found repos and what templates they match
(or a warning of they match none)

## Diff

**Diff is read-only**, no changes are made to the repos.

```diff
$ ariadne diff

(...)

Github
======

sigwinhq/reddit-client
-   description = "PHP Reddit SDK, autogenerated by OpenAPI Generator"
+   description = "Updating desc from Ariadne"

```

## Apply

Apply is a diff with an option to write the changes. It will ask to
apply the changes after showing the diff.

```diff
$ ariadne apply

(...)

Github
======

sigwinhq/reddit-client
-   description = "PHP Reddit SDK, autogenerated by OpenAPI Generator"
+   description = "Updating desc from Ariadne"

 Update these repos? (yes/no) [yes]:
 > yes

 [INFO] Updating 1 repos

 [OK] Completed.

```
