# Sigwin Ariadne

Find a common thread in your Glabyrinth.

## What is it

It's meant to allow common patterns, configuration, metadata across many different GitHub / Gitlab repos.

Currently supported:

1. GitHub
2. Gitlab

## Setup

Have a file called `ariadne.yaml` similar to this: 

```yaml
# ./ariadne.yaml
-
    type: gitlab
    name: My Gitlab
    auth:
        type: http_token
        # create your own: https://gitlab.com/-/profile/personal_access_tokens
        token: glpat-my-t0k3n
    parameters:
        # membership: true
        owned: true
-
    type: github
    name: My GitHub
    auth:
        type: access_token_header
        # create your own: https://github.com/settings/tokens
        token: ghp_my-t0k3n
    parameters:
        organizations: true
```

When you run `bin/ariadne test` in the same dir as the config file, it will tell you how the target platfom sees you and what repos you have access to:

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
