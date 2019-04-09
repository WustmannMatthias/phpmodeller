#! /usr/bin/python
# Coding: utf-8


from lib.settings import *

import os
import re
import json
import ntpath


class CodeFile:
    def __init__(self, path, repo_name):
        self.path = path
        self.repo_name = repo_name
        self.size = self.pick_up_size()
        self.loc = self.pick_up_loc()

        self.variables = dict()
        self.files_included = list()
        self.classes_used = list()

    def pick_up_loc(self):
        """Returns number of lines of code into file"""
        with open(self.path, 'r') as fh:
            return len(fh.readlines())

    def pick_up_size(self):
        """Returns size of the file (in bytes)"""
        return os.path.getsize(self.path)




    def parse(self):
        """
            Parse entire file : store files included and classes used
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

            self.parse_variables(line, line_counter)
            self.parse_includes(line, line_counter)
            #self.parse_classes(line, line_counter)
            #self.parse_uses(line, line_counter)



    def parse_variables(self, line, line_count):
        """Parse every variable found in the file and store them in self.variables"""
        regex = "^(?P<variable>\$[a-zA-Z0-9_]+)\s*=\s*[\"\']?(?P<value>[^\"\'\(\)]+)[\"\']?;"
        matches = re.match(regex, line)
        if not matches:
            return
        variable = matches.group('variable')
        value = matches.group('value')
        self.variables[variable] = value




    def parse_includes(self, line, line_counter):
        """
            Check for presence of "include" or "require" clauses in the given line,
            and store their parsed and rebuilt argument in self.files_included
        """
        regex = "(?P<clause>((require)|(include)))(?P<once>(_once)?)\s+(?P<argument>[-_ A-Za-z0-9\$\.\"'\/\s\[\]]+);"
        matches = re.match(regex, line)
        if not matches:
            return
        clause = matches.group('clause')
        argument = matches.group('argument')
        if matches.group('once'): once = True
        else: once = False

        path = self.parse_include_argument(argument, line_counter)
        include = Include(path, clause, once, self.path, line_counter)
        self.files_included.append(include)


    def parse_include_argument(self, argument, line_counter):
        """Takes the argument of a include or require clause, and rebuild the absolute path"""
        argument = self.replace_rel_path(argument, line_counter)
        argument = self.replace_magic_constant(argument, line_counter)
        argument = self.replace_variable(argument, line_counter)

        return argument


    def replace_rel_path(self, line, line_counter):
        """If $_SERVER['REL_PATH'] found in line, replace it with its value"""
        regex = "(?P<relpath>\$_SERVER\s*\[\s*[\\]?[\"\']REL_PATH[\\]?[\"\']\s*\])"
        matches = re.match(regex, line)
        if not matches:
            return line
        before_repo_name = self.path.split(self.repo_name)[0]
        return line.replace(matches.group('relpath'), before_repo_name)


    def replace_magic_constant(self, line, line_counter):
        """If __DIR__ or dirname(__FILE__) found in line, replace it with its value"""
        regex = "(?P<magicconstant>(__DIR__)|(dirname\s*\(\s*__FILE__\s*\)\s*))"
        matches = re.match(regex, line)
        if not matches:
            return line
        current_dir = ntpath.dirname(self.path)
        return line.replace(matches.group('magicconstant'), current_dir)


    def replace_variable(self, line, line_counter):
        """If variables in line, try to replace them with their value"""
        regex = "(\$[a-zA-Z0-9_]+)+"
        for variable in re.findall(regex, line):
            if variable in self.variables.keys():
                line = line.replace(variable, self.variables[variable])
            else:
                raise VariableNotFoundException(Repository.path_from_repo(self.path, self.repo_name), line, line_counter, variable)
        return line





class VariableNotFoundException(Exception):
    def __init__(self, file, line, line_number, variable):
        self.msg = f'File {file} line {line_number} : tried to solve variable "{variable}" in line {line}, but declaration was either not found or not understood.'
        super(VariableNotFoundException, self).__init__(self.msg)

    def __str__(self):
        return self.msg





class Include:
    """
        Represents a dependency of a php file, related to it through a include[_once]
        or require[_once] clause."""

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


    def get_relationship(self):
        return Dependency.RELATIONSHIPS[self.type]



class WrongIncludeType(Exception):
    def __init__(self, parent, inclusion_line, type):
        self.msg = f"Incorrect dependency relation found in {parent} on line {inclusion_line} : {type}"
        super(WrongIncludeType, self).__init__(self.msg)

    def __str__(self):
        return self.msg







class CodeFileUploader:
    pass
