#! /usr/bin/python
# Coding: utf-8


import json

from lib.repository import Repository
from lib.codefile import CodeFile, Include


def main():
    repo_path = "/var/www/html/type_test_repo"
    #repo_path = "/var/www/html/X"
    repo = Repository(path=repo_path, directories_to_ignore=[".git", "Products"], files_to_ignore=["Vehicules/vehicule_parts/Wheels.php"])
    repo.build_filepath_list()

    for file in repo.filepath_list:
        codefile = CodeFile(file, repo.repo_name)
        print(codefile.path)
        codefile.parse()
        print(codefile.current_namespace)
        for c in codefile.uses:
            print(c.namespace, c.classname)
        print()






if __name__ == '__main__':
    main()
    exit()
