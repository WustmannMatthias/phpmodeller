#! /usr/bin/python
# Coding: utf-8


import configparser
import os

# Get user settings
settings = configparser.ConfigParser()
settings.read(os.path.dirname(os.path.abspath(__file__)) + '/../settings')


GENERAL_SETTINGS = settings['GENERAL SETTINGS']
STORAGE_DIRECTORY = os.path.abspath(GENERAL_SETTINGS['STORAGE_DIRECTORY'])


DATABASE_SETTINGS = settings['DATABASE SETTINGS']
DATABASE_URL = DATABASE_SETTINGS['DATABASE_URL']
DATABASE_PORT = DATABASE_SETTINGS['DATABASE_PORT']
USERNAME = DATABASE_SETTINGS['USERNAME']
PASSWORD = DATABASE_SETTINGS['PASSWORD']
PROTOCOL = DATABASE_SETTINGS['PROTOCOL'].lower()


API_SETTINGS = settings['API SETTINGS']
API_URL = API_SETTINGS['API_URL']
API_PORT = API_SETTINGS['API_PORT']
DEBUG = bool(API_SETTINGS['DEBUG'])
