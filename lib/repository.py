#! /usr/bin/python
# Coding: utf-8


from lib.settings import *

import os
import json
from lib.codefile import CodeFile


class Repository:
    """
        Represents a whole repository, and all its files
    """
    def __init__(self, path, extensions=['php'], no_extension_files=False, directories_to_ignore=[], files_to_ignore=[]):
        self.path = path
        self.extensions = extensions
        self.no_extension_files = no_extension_files
        self.directories_to_ignore = directories_to_ignore
        self.files_to_ignore = files_to_ignore

        self.repo_name = self.extract_repo_name()
        self.filepath_list = list()



    def extract_repo_name(self):
        """Get the name of the repository"""
        return os.path.basename(self.path)

    @staticmethod
    def path_from_repo(path, repo_name):
        endpath = path.split(repo_name)[-1]
        return os.path.join(repo_name, endpath)


    def build_filepath_list(self):
        """Build a list containing every single file of the repository"""
        if not os.path.isdir(self.path):
            raise RepositoryScanException(self.repo_name)

        filepath_list = list()
        for dirpath, dirnames, filenames in os.walk(self.path):

            dirpath_from_repo = Repository.path_from_repo(dirpath, self.repo_name)
            if dirpath_from_repo in self.directories_to_ignore:
                continue

            for file in filenames:
                ext = os.path.splitext(file)[1]
                if not ext and not self.no_extension_files:
                    continue
                if ext[1:] not in self.extensions:
                    continue
                filepath = os.path.join(dirpath, file)
                path_from_repo = Repository.path_from_repo(filepath, self.repo_name)
                if path_from_repo in self.files_to_ignore:
                    continue
                filepath_list.append(filepath)
        self.filepath_list = filepath_list




class RepositoryScanException(Exception):
    def __init__(self, repository):
        self.msg = f"Can't load Repository at path {repository}"
        super(RepositoryScanException, self).__init__(self.msg)

    def __str__(self):
        return self.msg




def main():
    repo = Repository('/var/www/html/type_test_repo', directories_to_ignore="type_test_repo/Products", files_to_ignore="type_test_repo/Vehicules/vehicule_parts/Wheels.php")
    repo.build_filepath_list()
    print(json.dumps(repo.filepath_list))


if __name__ == '__main__':
    main()
    exit()
