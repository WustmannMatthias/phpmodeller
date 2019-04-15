#! /usr/bin/python
# Coding: utf-8


import json

from modeller.repository import Repository
import common.database as database
import data.github as github


def main():

    repo_path = "/var/www/html/Pricer2016Q2"
    #repo_path = "/var/www/html/X"
    #repo_path = "/var/www/html/type_test_repo"
    repo = Repository(path=repo_path, extensions=['php', 'inc'], directories_to_ignore=[".git", "tests", "TOOLS", "vendor"], files_to_ignore=[])
    repo.build_filepath_list()
    repo.parse()


    driver = database.build_driver()
    repo.upload_files(driver)

    result, success = repo.build_map(driver)
    if not success:
        print(json.dumps(result, indent=4))
    driver.close()

    """

    data = {
        'repository_name': 'e4p-core',
        'repository_url': 'git@github.com:flash-global/e4p-core.git',
        'composer': 1
    }
    result = github.clone_or_pull(data)
    print(result)
    """

    
if __name__ == '__main__':
    main()
    exit()
