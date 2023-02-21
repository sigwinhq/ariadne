# Sigwin Ariadne

Find a common thread in your Glabyrinth.

## What is it

It's meant to allow common patterns, configuration, metadata across many GitHub / Gitlab repos.

Currently supported:

1. GitHub
2. Gitlab

## Setup

Have a file called `ariadne.yaml` similar to this: 

```yaml
# ./ariadne.yaml
profiles:
-
    type: gitlab
    name: My Gitlab
    client:
        auth:
            # default, can be omitted
            type: http_token
            # create your own: https://gitlab.com/-/profile/personal_access_tokens
            token: glpat-my-t0k3n
        # default, can be omitted
        options:
            # membership: true
            owned: true
    templates:
    -
        name: My Org Project 1 repos
        filter:
            path: match('^my-org/project1')
    -
        name: My Org Project 2 repos
        filter:
            path: match('^my-org/project2')
-
    type: github
    name: My Github
    client:
        auth:
            # default, can be omitted
            type: access_token_header
            # create your own: https://github.com/settings/tokens
            token: ghp_my-t0k3n
        # default, can be omitted
        options:
            organizations: true
    templates:
    -
        name: My Org OSS private source repos
        filter:
            path: match('^my-org')
            visibility: private # public/private
            type: source        # source/fork
    -
        name: My Org OSS public forks
        filter:
            path: match('^my-org')
            visibility: public
            type: fork
```

When you run `bin/ariadne test` in the same dir as the config file, it will tell you how the target platform sees you and what repos you have access to:

```
$ bin/ariadne test

Sigwin Ariadne
==============

My Gitlab
-------------

 ------------- ------------- 
  API Version   15.9.0-pre   
  User          dkarlovi     
  Repos         dkarlovi: 2  
                sigwin: 42   
 ------------- ------------- 

My GitHub
-------------

 ------------- ------------------- 
  API Version   v3                 
  User          dkarlovi           
  Repos         some-org: 1      
                some-secret-org: 1  
                dkarlovi: 28       
                sigwinhq: 30       
 ------------- ------------------- 
```
