# ! /usr/bin/python
# Coding: utf-8

import os
import flask
import configparser
import json

from flask import make_response
from flask import request
from flask import abort

from flask import redirect, url_for

from flask_cors import CORS


import common.database as database
from common.settings import *

import api.functions as functions





def instanciates_api(name):
	"""
		instanciate a Flask object and
		- set the debug parameter to the value specified in the root settings file
		- allow Cross Origin Ressource Sharing
		Then, returns it
	"""
	api = flask.Flask(name)
	api.config['DEBUG'] = DEBUG
	CORS(api)
	return api


def get_mime_type(f):
	"""Just try to pick up the mime_type corresponding to the given format and return it"""
	formats = {
		'default'	: 'text/plain',
		'json'		: 'application/json',
		'ini'		: 'text/plain',
		'conf'		: 'text/plain',
		'html'		: 'text/html',
		'text'		: 'text/plain'
	}
	try:
		return formats[f]
	except KeyError:
		return formats['default']





""" Instanciate API object """
api = instanciates_api(__name__)




"""
	Error handling
"""
@api.errorhandler(404)
def not_found(error):
	response = make_response(json.dumps({'error': 'Not found'}), 404)
	response.mimetype = get_mime_type('json')
	return response

@api.errorhandler(400)
def not_found(error):
	response = make_response((
		"<h1>Bad Request</h1>"
		"If you've sent json data with POST method, please make sure to precise following content type in the HTTP Header : \"Content-Type: application/json; charset=UTF-8\". <br>"
		"Please also make sure that your data is correctly formated. "
	), 400)
	response.mimetype = get_mime_type('html')
	return response



"""
	ROUTES
"""
# Model a project
@api.route('/api/v1.0/model', methods=['POST'])
def model():
	data = request.get_json()
	if data:
		response = make_response(functions.upload_repo(data))
		response.mimetype = get_mime_type('json')
		return response
	abort(400)

# Get features for project
@api.route('/api/v1.0/features/<repository>', methods=['GET'])
def get_features(repository):
	response = make_response(functions.get_features(repository))
	response.mimetype = get_mime_type('json')
	return response
	abort(400)







def main():
	""" Lauch API """
	api.run(host=API_URL, port=API_PORT)

if __name__ == '__main__':
	main()
