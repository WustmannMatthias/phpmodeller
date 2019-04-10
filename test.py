#! /usr/bin/python
# Coding: utf-8


import json

from phpmodeller.repository import Repository
import phpmodeller.database as database


def main():
    repo_path = "/var/www/html/type_test_repo"
    #repo_path = "/var/www/html/X"
    repo = Repository(path=repo_path, directories_to_ignore=[".git"], files_to_ignore=[])
    repo.build_filepath_list()
    repo.parse()

    driver = database.build_driver()
    result, success = repo.upload_files(driver)
    if not success:
        print(result)

    result, success = repo.build_map(driver)
    if not success:
        print(result)
    driver.close()






if __name__ == '__main__':
    main()
    exit()
