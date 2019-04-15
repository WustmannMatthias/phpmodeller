#! /usr/bin/python
# Coding: utf-8

import subprocess


def exec(command):
    """
        @param command is a externale program. It is a list, each argument is a element of that list.
        @return is a tuple (result, success)
    """
    try:
        result = subprocess.run(command, check=True, stdout=subprocess.PIPE)
        return result.stdout.decode('utf-8').split('\n'), True
    except subprocess.CalledProcessError as e:
        return str(e), False
