#! /usr/bin/python
# Coding: utf-8


import json
import os

from common.settings import *
import common.exec as exec



def clone_or_pull(data):
    """
        This function clones the repository in the storage directory (see settings).
        If the repository already exists in the storage directory, the lastest version will
        be pulled.
        @data is a hashmap, it has to contain repository_name, repository_url,
        and /or composer (1 or 0)
    """
    try:
        repo_name = data['repository_name']
    except KeyError as e:
        return str(e), False

    if repo_name in os.listdir(STORAGE_DIRECTORY):
        return pull(data)
    return clone(data)


def pull(data):
    """
        Pulls an existing repository at its latest version in the storage directory.
        @param data is a hashmap, it has to contains at list repository_name, and can contain composer (0 or 1)
        @return is a tuple (result_message, success)
    """
    try:
        repo_name = data['repository_name']
    except KeyError as e:
        return str(e), False

    log = list()

    if os.path.isdir(os.path.join(STORAGE_DIRECTORY, repo_name)):
        os.chdir(os.path.join(STORAGE_DIRECTORY, repo_name))
    else:
        return f"No repository {repo_name} found in the storage directory."

    result, success = exec.exec(['git', 'pull'])
    if success: log.extend(result)
    else: return result, False

    if 'composer' in data.keys() and data['composer'] == 1 or data['composer'] == "1":
        result, success = exec.exec(['composer', '-n', 'install'])
        if success: log.extend(result)
        else: return result, False

    return '\n'.join(log), True


def clone(data):
    """
        Clones a repository in the storage directory.
        @param data is a hashmap, it has to contain at least repository_url and can contain composer (0 or 1)
        @return is a tuple (result_message, success)
    """
    try:
        repo_url = data['repository_url']
        repo_name = data['repository_name']
    except KeyError as e:
        return str(e), False

    log = list()

    os.chdir(STORAGE_DIRECTORY)
    result, success = exec.exec(['git', 'clone', repo_url])
    if success: log.extend(result)
    else: return result, False

    if 'composer' in data.keys() and bool(data['composer']) == True:
        os.chdir(os.path.join(STORAGE_DIRECTORY, repo_name))
        result, success = exec.exec(['composer', '-n', 'install'])
        if success: log.extend(result)
        else: return result, False

    return '\n'.join(log), True


def get_modified_files(repo_name, release_begin, release_end):
    """Returns the list of the files modified between two releases"""
    if os.path.isdir(os.path.join(STORAGE_DIRECTORY, repo_name)):
        os.chdir(os.path.join(STORAGE_DIRECTORY, repo_name))
    else:
        return f"No repository {repo_name} found in the storage directory.", False

    modified_files = list()
    commit_list, success = exec.exec(['git', 'log', release_begin + '..' + release_end, '--oneline'])
    if not success:
        return "Error while trying to get commit list", False
    for item in commit_list:
        commit = item.split(' ')[0].strip()
        if not commit:
            continue
        command = ['git', 'diff-tree', '--no-commit-id', '--name-only', '-r', commit]
        current_commit_modified_files, success = exec.exec(command)
        if not success:
            return f"Error while trying to get modified files in commit {commit}", False
        modified_files.extend(current_commit_modified_files[:-1])

    #Add repository prefix
    output = list()
    for file in list(set(modified_files)):
        output.append(os.path.join(repo_name, file))

    return output, True


def main():
    pass

if __name__ == '__main__':
    main()
    exit()
