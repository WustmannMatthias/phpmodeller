#! /usr/bin/python
# Coding: utf-8

import phpmodeller.repository as repository

import os
import re
import json
import hashlib

from neo4j import GraphDatabase



class CodeFile:
    def __init__(self, path, repo_name):
        self.path = path
        self.repo_name = repo_name
        self.size = self.pick_up_size()
        self.loc = self.pick_up_loc()

        self.current_namespace = '\\'
        self.variables = dict()
        self.includes = list()
        self.classes = list()
        self.uses = list()

    def pick_up_loc(self):
        """Returns number of lines of code into file"""
        with open(self.path, 'r') as fh:
            return len(fh.readlines())

    def pick_up_size(self):
        """Returns size of the file (in bytes)"""
        return os.path.getsize(self.path)



    def parse(self):
        """
            Parse entire file : store files included, classes, variables,
            current namespaces and uses clauses
        """
        in_comment = False
        with open(self.path, 'r') as fh:
            lines = fh.read().split('\n')

        line_counter = 0
        for line in lines:
            line_counter += 1
            line = line.strip() # LINE IS ALREADY TRIMED IN EVERY OTHER METHODS
            # comment handling
            if line.startswith('//'): continue
            if line.startswith('/*'): in_comment = True
            if in_comment:
                if '*/' not in line: continue
                in_comment = False

            self.parse_namespace(line, line_counter)
            self.parse_variables(line, line_counter)
            self.parse_includes(line, line_counter)
            self.parse_classes(line, line_counter)
            self.parse_uses(line, line_counter)




    #####################################################################
    ############################# NAMESPACES ############################
    #####################################################################

    def parse_namespace(self, line, line_count):
        """Parse namespaces and store the current namespace in self.current_namespace """
        regex = r"namespace\s+(?P<name>[a-zA-Z0-9_\\]+)\s*(\{|\;)"
        match = re.match(regex, line)
        if not match:
            return
        namespace = match.group('name')
        if namespace.startswith('\\') and len(namespace) > 1:
            namespace = namespace[1:]
        self.current_namespace = namespace



    #####################################################################
    ############################# VARIABLES #############################
    #####################################################################

    def parse_variables(self, line, line_count):
        """Parse every variable found in the file and store them in self.variables"""
        regex = r"^(?P<variable>\$[a-zA-Z0-9_]+)\s*=\s*[\"\']?(?P<value>[^\"\'\(\)]+)[\"\']?;"
        matches = re.match(regex, line)
        if not matches:
            return
        variable = matches.group('variable')
        value = matches.group('value')
        self.variables[variable] = value


    class VariableNotFoundException(Exception):
        def __init__(self, file, line_number, line, variable):
            self.msg = f'File {file} line {line_number} : tried to solve variable "{variable}" in line {line}, but declaration was either not found or not understood.'
            super(VariableNotFoundException, self).__init__(self.msg)

        def __str__(self):
            return self.msg



    #####################################################################
    ######################## INCLUDES / REQUIRE #########################
    #####################################################################

    def parse_includes(self, line, line_counter):
        """
            Check for presence of "include" or "require" clauses in the given line,
            and store their parsed and rebuilt argument in self.includes
        """
        regex = r"(?P<clause>((require)|(include)))(?P<once>(_once)?)\s*\(?\s*(?P<argument>[-_ A-Za-z0-9\$\.\"'\/\s\[\]]+)\s*\)?\s*;"
        matches = re.match(regex, line)
        if not matches:
            return
        clause = matches.group('clause')
        argument = matches.group('argument')
        if matches.group('once'): once = True
        else: once = False

        path = self.parse_include_argument(argument, line_counter)
        include = Include(repository.Repository.path_from_repo(path, self.repo_name), clause, once, repository.Repository.path_from_repo(self.path, self.repo_name), line_counter)
        self.includes.append(include)


    def parse_include_argument(self, argument, line_counter):
        """Takes the argument of a include or require clause, and rebuild the absolute path"""
        argument = self.replace_rel_path(argument, line_counter)
        argument = self.replace_magic_constant(argument, line_counter)
        argument = self.replace_variable(argument, line_counter)
        argument = self.remove_unnecessary(argument, line_counter)
        path = self.build_include_path(argument, line_counter)
        return path


    def replace_rel_path(self, line, line_counter):
        """If $_SERVER['REL_PATH'] found in line, replace it with its value"""
        regex = r"(?P<relpath>\$_SERVER\s*\[\s*[\\]?[\"\']REL_PATH[\\]?[\"\']\s*\])"
        matches = re.match(regex, line)
        if not matches:
            return line
        before_repo_name = self.path.split(self.repo_name)[0]
        return line.replace(matches.group('relpath'), '"' + before_repo_name + '"')


    def replace_magic_constant(self, line, line_counter):
        """If __DIR__ or dirname(__FILE__) found in line, replace it with its value"""
        regex = "(?P<magicconstant>(__DIR__)|(dirname\s*\(\s*__FILE__\s*\)\s*))"
        matches = re.match(regex, line)
        if not matches:
            return line
        current_dir = os.path.dirname(self.path)
        return line.replace(matches.group('magicconstant'), '"' + current_dir + '"')


    def replace_variable(self, line, line_counter):
        """If variables in line, try to replace them with their value"""
        regex = r"(\$[a-zA-Z0-9_]+)+"
        for variable in re.findall(regex, line):
            if variable in self.variables.keys():
                line = line.replace(variable, f'"{self.variables[variable]}"')
            else:
                raise VariableNotFoundException(repository.Repository.path_from_repo(self.path, self.repo_name), line_counter, line, variable)
        return line


    def remove_unnecessary(self, line, line_counter):
        """
            Removes everything that doesn't belongs to the path, like the quotes/double-quotes
            or the concatenation operators
        """
        inner = False
        path = str()
        for car in line:
            if car in ['"', '"']:
                inner = not inner
                continue
            if inner: path += car
        return path


    def build_include_path(self, path, line_counter):
        """
            Tries to build the absolute path by replacing .. and . by the right directories,
            and raises exc
        """
        if os.path.isabs(path):
            if not os.path.isfile(path):
                raise DependencyNotFoundException(repository.Repository.path_from_repo(self.path, self.repo_name), line_counter, path)
        else:
            current_dir = os.path.dirname(self.path)
            max_depth_allowed = 20
            while not os.path.isfile(os.path.join(current_dir, path)):
                if max_depth_allowed <= 0:
                    raise AbsolutePathReconstructionException(repository.Repository.path_from_repo(self.path, self.repo_name), line_counter, path)
                current_dir = self.remove_last_dir_in_path(current_dir)
                max_depth_allowed -= 1
            path = os.path.join(current_dir, path)
        return os.path.normpath(path)


    def remove_last_dir_in_path(self, path):
        """Ex : sth/other/dir -> sth/other"""
        return os.path.sep.join(path.split(os.path.sep)[:-1])


    class AbsolutePathReconstructionException(Exception):
        def __init__(self, file, line_number, path):
            self.msg = f'File {file} line {line_number} : tried to rebuild path "{path}", but the file probably doesn\'t exist."'
            super(AbsolutePathReconstructionException, self).__init__(self.msg)

        def __str__(self):
            return self.msg


    class DependencyNotFoundException(Exception):
        def __init__(self, file, line_number, path):
            self.msg = f'File {file} line {line_number} : included file "{path}" doesn\'t exists."'
            super(DependencyNotFoundException, self).__init__(self.msg)

        def __str__(self):
            return self.msg



    #####################################################################
    ############################# CLASSES ###############################
    #####################################################################

    def parse_classes(self, line, line_counter):
        """Parse classes and store Class objects in self.classes"""
        regex = r"class\s(?P<name>[a-zA-Z0-9_]+)"
        match = re.match(regex, line)
        if not match:
            return
        classname = match.group('name')

        if classname.startswith('\\'):
            namespace, classname = '\\', classname[1:]
        else:
            namespace, classname = self.current_namespace, classname
        class_object = Class(namespace, classname, repository.Repository.path_from_repo(self.path, self.repo_name), line_counter)
        self.classes.append(class_object)



    #####################################################################
    ############################### USES ################################
    #####################################################################

    def parse_uses(self, line, line_counter):
        """Parse uses clauses and store Class objects in self.uses"""
        regex = r"use\s+(?P<argument>[a-zA-Z0-9_\\]+)\s*"
        match = re.match(regex, line)
        if not match:
            return
        argument = match.group('argument')

        splited_argument = argument.split('\\')
        namespace = '\\'.join(splited_argument[:-1])
        classname = splited_argument[-1]

        class_object = Class(namespace, classname, repository.Repository.path_from_repo(self.path, self.repo_name), line_counter)
        self.uses.append(class_object)



    #####################################################################
    ############################# UPLOAD ################################
    #####################################################################

    def prepare_node_id(self, path):
        """Get the blake2 hash of the path """
        hasher = hashlib.md5()
        hasher.update(bytes(path, encoding='utf-8'))
        hash = hasher.hexdigest()
        return f"file{hash}"


    def build_upload_query(self, repository_node):
        """Build a query uploading a node for the codefile"""
        path_from_repo = repository.Repository.path_from_repo(self.path, self.repo_name)
        basename = os.path.basename(self.path)
        file_id = self.prepare_node_id(path_from_repo)

        query = f"CREATE ({repository_node})-[:FILE]->"
        query += "(" + file_id + ":File {"
        query += f"path: '{path_from_repo}', "
        query += f"name: '{basename}', "
        query += f"size: {self.size}, "
        query += f"loc: {self.loc}"
        query += "}) "

        for c in self.classes:
            query += c.build_class_query(file_id)

        return query



    def build_includes_query(self, repository_node):
        """build a query searching include/require dependencies and drawing them in the db"""
        path_from_repo = repository.Repository.path_from_repo(self.path, self.repo_name)
        file_id = self.prepare_node_id(path_from_repo)

        query = f"WITH {repository_node} "
        query += f"MATCH ({repository_node})-[:FILE]->({file_id}:File) \
                    WHERE {file_id}.path = '{path_from_repo}' "
        for include in self.includes:
            query += include.build_include_query(repository_node, file_id)
        return query


    def build_uses_query(self, repository_node):
        """build a query searching use dependencies and drawing them in the db"""
        path_from_repo = repository.Repository.path_from_repo(self.path, self.repo_name)
        file_id = self.prepare_node_id(path_from_repo)

        query = f"WITH {repository_node} "
        query += f"MATCH ({repository_node})-[:FILE]->({file_id}:File) \
                    WHERE {file_id}.path = '{path_from_repo}' "
        for c in self.uses:
            query += c.build_use_query(repository_node, file_id)
        return query





class Include:
    """
        Represents a dependency of a php file, related to it through a include[_once]
        or require[_once] clause.
    """

    RELATIONSHIPS = {
        'include': 'IS_INCLUDED_IN',
        'require': 'IS_REQUIRED_IN'
    }


    def __init__(self, path, type, once, parent, inclusion_line):
        if type not in Include.RELATIONSHIPS.keys():
            raise WrongIncludeType(parent, inclusion_line, type)

        self.path = path
        self.type = type
        self.once = once
        self.parent = parent
        self.inclusion_line = inclusion_line


    def prepare_node_id(self, path):
        """Get the blake2 hash of the path """
        hasher = hashlib.md5()
        hasher.update(bytes(path, encoding='utf-8'))
        hash = hasher.hexdigest()
        return f"include{hash}"


    def build_include_query(self, repository_node, includer_node):
        """
            Build a query searching a node, and drawing a relation between it and
            the given node.
        """
        file_id = self.prepare_node_id(self.path)
        query = f"MERGE ({repository_node})-[:FILE]->({file_id}"
        query += ":File {"
        query += f"path: '{self.path}'"
        query += "}) "
        query += f"MERGE ({file_id})-[:{Include.RELATIONSHIPS[self.type]}"
        if self.once:
            query += "{once: 1}"
        query += f"]->({includer_node}) "
        return query


    class WrongIncludeType(Exception):
        def __init__(self, parent, inclusion_line, type):
            self.msg = f"Incorrect dependency relation found in {parent} on line {inclusion_line} : {type}"
            super(WrongIncludeType, self).__init__(self.msg)

        def __str__(self):
            return self.msg





class Class:
    """Represents a class found in a php file"""
    def __init__(self, namespace, classname, parent, inclusion_line):
        self.namespace = namespace
        self.classname = classname
        self.parent = parent
        self.inclusion_line = inclusion_line


    def double_backslashes(self, string):
        return string.replace('\\', '\\\\')


    def prepare_class_id(self, namespace, classname):
        """Get the blake2 hash of the path """
        hasher = hashlib.md5()
        hasher.update(bytes(namespace, encoding='utf-8'))
        hasher.update(bytes(classname, encoding='utf-8'))
        hash = hasher.hexdigest()
        return f"class{hash}"


    def build_class_query(self, file_node):
        """Create a node for the class and relate it to the given file node"""
        query = "CREATE (" + file_node + ")-[:DECLARES]->(:Class {"
        query += f"namespace: '{self.double_backslashes(self.namespace)}', "
        query += f"class: '{self.classname}'"
        query += "}) "
        return query


    def build_use_query(self, repository_node, file_node):
        """
            Build a query searching a node, and drawing a relation between it and
            the given node.
        """
        class_id = self.prepare_class_id(self.namespace, self.classname)
        query = f"MERGE ({repository_node})-[:FILE]->(:File)-[:DECLARES]-({class_id}"
        query += ":Class{"
        query += f"namespace: '{self.double_backslashes(self.namespace)}', "
        query += f"class: '{self.classname}' "
        query += "}) "
        query += f"MERGE ({file_node})-[:USE]->({class_id})"
        return query
