#! /usr/bin/python
# Coding: utf-8


import json

from data.github import clone_or_pull
from data.github import get_modified_files

from data.model import update_repo


def upload_repo(data):
    """
        Clones (or pull) a repository, analyse it, upload it in db, et return all files
        potentially impacted by the modifications that have been done between two releases.
        @data is a hashmap with following format :
        {
            repository_name: '...',
            repository_url: '...',
            composer: 0|1,
            extensions: [...],
            no_extension_files: 0|1,
            directories_to_ignore: [...],
            files_to_ignore: [...]
        }
    """
    try:
        repo_name = data['repository_name']
        repository_url = data['repository_url']
        composer = bool(data['composer'])
        extensions = data['extensions']
        no_extension_files = data['no_extension_files']
        directories_to_ignore = data['directories_to_ignore']
        files_to_ignore = data['files_to_ignore']
    except KeyError as e:
        return json.dumps({'error': 'Incorrect data'})


    logs = "Get repository from Github : \n"
    log, success = clone_or_pull(data)
    logs += log + "\n\n"
    if not success:
        return logs

    logs += "Upload repository in database : \n"
    log, success = update_repo(data)
    logs += log + "\n\n"
    if not success:
        return logs

    return json.dumps(logs)
