#! /usr/bin/python
# Coding: utf-8

import phpmodeller.codefile as codefile
import phpmodeller.database as database

import os
import json

from neo4j import GraphDatabase


class Repository:
    """
        Represents a whole repository, and all its files
        @param path is the absolute local path to the directory of the repository
        @param extensions is the list of the extensions to keep
        @param no_extension_files (boolean) determine if files without any suffix must be kept
        @param directories_to_ignore is a list of directory to exclude from the repository. The list must
            contains path, relative to the root of the repository (@param path)
        @param files_to_ignore is a list of file to exclude from the repository. The list must
            contains path, relative to the root of the repository (@param path)

        Once the object instanciated, the method build_filepath_list allows to scan the repository to keep
            only the wished files.
        Once the repository analysed, the method parse allows to parse each file.
    """
    def __init__(self, path, extensions=['php'], no_extension_files=False, directories_to_ignore=[], files_to_ignore=[]):
        self.path = path
        self.extensions = extensions
        self.no_extension_files = no_extension_files
        self.directories_to_ignore = directories_to_ignore
        self.files_to_ignore = files_to_ignore

        self.repo_name = self.extract_repo_name()

        self.filepath_list = list()
        self.codefile_list = list()



    def extract_repo_name(self):
        """Get the name of the repository"""
        return os.path.basename(self.path)

    @staticmethod
    def path_from_repo(path, repo_name, include_repo_name=True):
        """Get the path starting from the root of the repository, including or not the name of the repository"""
        endpath = path.split(os.path.sep + repo_name + os.path.sep)[-1]
        if include_repo_name:
            return os.path.join(repo_name, endpath)
        else:
            return endpath



    def build_filepath_list(self):
        """Build a list containing every single file of the repository"""
        if not os.path.isdir(self.path):
            raise RepositoryScanException(self.repo_name)

        filepath_list = list()
        for dirpath, dirnames, filenames in os.walk(self.path):

            dirpath_from_repo = Repository.path_from_repo(dirpath, self.repo_name, include_repo_name=False)
            if dirpath_from_repo in self.directories_to_ignore:
                continue

            for file in filenames:
                ext = os.path.splitext(file)[1]
                if not ext and not self.no_extension_files:
                    continue
                if ext[1:] not in self.extensions:
                    continue
                filepath = os.path.join(dirpath, file)
                path_from_repo = Repository.path_from_repo(filepath, self.repo_name, include_repo_name=False)
                if path_from_repo in self.files_to_ignore:
                    continue
                filepath_list.append(filepath)
        self.filepath_list = filepath_list

        codefile_list = list()
        for filepath in filepath_list:
            codefile_list.append(codefile.CodeFile(filepath, self.repo_name))
        self.codefile_list = codefile_list


    class RepositoryScanException(Exception):
        def __init__(self, repository):
            self.msg = f"Can't load Repository at path {repository}"
            super(RepositoryScanException, self).__init__(self.msg)

        def __str__(self):
            return self.msg



    def parse(self):
        """Simply calls the parse() method of each file of the repository"""
        for codefile in self.codefile_list:
            codefile.parse()



    def upload_files(self, driver):
        """
            Upload each file individually
            @return is a (result, success) tuple.
            If success == False, result worth the error message
        """
        query = "CREATE (repository:Repository {"
        query += f"name: '{self.repo_name}'"
        query += "}) "
        for codefile in self.codefile_list:
            query += codefile.build_upload_query(repository_node='repository')
        return database.run_query(driver, query)


    def build_map(self, driver):
        """
            Draw the map of the repository
        """
        query = f"MATCH (repository:Repository) \
                    WHERE repository.name = '{self.repo_name}' "

        for codefile in self.codefile_list:
            query += codefile.build_includes_query(repository_node='repository')
            query += codefile.build_uses_query(repository_node='repository')
        return database.run_query(driver, query)



def main():
    repo = Repository('/var/www/html/type_test_repo', directories_to_ignore="type_test_repo/Products", files_to_ignore="type_test_repo/Vehicules/vehicule_parts/Wheels.php")
    repo.build_filepath_list()
    print(json.dumps(repo.filepath_list))


if __name__ == '__main__':
    main()
    exit()
