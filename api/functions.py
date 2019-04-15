#! /usr/bin/python
# Coding: utf-8


import json

from data.github import clone_or_pull
from data.github import get_modified_files

from data.model import update_repo

from common.database import build_driver
from common.database import run_query


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
        return json.logs(logs)

    logs += "Upload repository in database : \n"
    log, success = update_repo(data)
    logs += log + "\n\n"
    if not success:
        return json.dumps(logs)

    return json.dumps(logs)




def get_features(repository, begin, end):
    """
        For a given repository, get all files impacted by the modifications
        of the code between 2 releases/tags.
    """
    modified_files, success = get_modified_files(repository, begin, end)
    if not success:
        return json.dumps({'error': f'couldn\'t get files modified between {begin} and {end}.' })
    query = f"MATCH (repo:Repository)-[:FILE]->(startFile:File) \
                WHERE repo.name = '{repository}' \
                AND startFile.path IN {str(modified_files)} \
                \
                MATCH (startFile)<-[:IS_DECLARED_BY|:USES|:INCLUDES|:REQUIRES*0..10]-(endFile:File) \
                RETURN DISTINCT endFile.path AS impacted_file \
                ORDER BY impacted_file ASC"
    driver = build_driver()
    result, success = run_query(driver, query)
    driver.close()
    if not success:
        return json.dumps(result)

    output = list()
    for record in result.records():
        output.append(record['impacted_file'])

    return json.dumps('\n'.join(output))


def get_repos():
    """Return the list of the repositories modelled in the db"""
    query = "MATCH (repo:Repository) RETURN repo.name AS repo"
    driver = build_driver()
    result, success = run_query(driver, query)
    driver.close()
    if not success:
        return json.dumps(result)

    output = list()
    for record in result.records():
        output.append(record['repo'])

    return json.dumps(output)
