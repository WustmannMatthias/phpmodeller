#! /usr/bin/python
# Coding: utf-8


import json
import os

from common.settings import *

import common.database as database
import common.exec as exec
import modeller.repository as repository


ARGUMENT_SEPARATOR = ','

def prepare_list_arguments(arguments):
    """Split a string around the argument separator and trim every part """
    arguments = arguments.split(',')
    output = list()

    for argument in arguments:
        output.append(argument.strip())
    return output



def upload_repo(data):
    """
        Uploads a repository into the database.
        !!! If the repository already exists, it will not be first deleted !!!
        @data is a hashmap, it has to contains:
            - repository_name (str)
            - extensions (list)
            - no_extension_files (0 or 1)
            - directories_to_ignore (list)
            - files_to_ignore (list)
    """
    try:
        repo_name = data['repository_name']
        extensions = prepare_list_arguments(data['extensions'])
        no_extension_files = bool(data['no_extension_files'])
        directories_to_ignore = prepare_list_arguments(data['directories_to_ignore'])
        files_to_ignore = prepare_list_arguments(data['files_to_ignore'])
    except KeyError as e:
        return str(e), False

    repo_path = os.path.abspath(os.path.join(STORAGE_DIRECTORY, repo_name))
    if not os.path.isdir(repo_path):
        return f"Repository {repo_name} doesn't exists in storage directory.", False

    repo = repository.Repository(path=repo_path, extensions=extensions, no_extension_files=no_extension_files, directories_to_ignore=directories_to_ignore, files_to_ignore=files_to_ignore)
    repo.build_filepath_list()
    repo.parse()

    driver = database.build_driver()

    print("Uploading files...")
    result, success = repo.upload_files(driver)
    if not success:
        driver.close()
        print(json.dumps(result))
        return json.dumps(result), False

    print("Building map...")
    result, success = repo.build_map(driver)
    driver.close()
    if not success:
        print(json.dumps(result))
        return json.dumps(result), False

    return '\n'.join(repo.logs), True



def delete_repo_from_db(repository_name):
    """
        Fully deletes a repository, all its files and classes from the database
        @param repository_name is the name of the repository
    """
    repo_path = os.path.abspath(os.path.join(STORAGE_DIRECTORY, repository_name))

    repo = repository.Repository(path=repo_path)
    driver = database.build_driver()
    print("Deleting repo from db...")
    result, success = repo.delete_from_db(driver)
    driver.close()

    if not success:
        return result, False
    return f"Repository {repository_name} was successfully deleted from db.", True



def update_repo(data):
    """Calls the delete_repo_from_db and upload_repo functions"""
    try:
        repo_name = data['repository_name']
    except KeyError as e:
        return str(e), False

    logs = str()

    log, success = delete_repo_from_db(repo_name)
    logs += log
    if not success:
        return logs, False

    log, success = upload_repo(data)
    logs += log
    if not success:
        return logs, False

    logs += f"\nRepository {repo_name} was successfully uploaded in db."
    return logs, True



def clean_mess():
    """
        This function simply runs a query removing all nodes that aren't related to any repository
        any more.
    """
    pass
