#! /usr/bin/python
# Coding: utf-8


from phpmodeller.settings import *
from neo4j import GraphDatabase
import neobolt



def build_driver():
    """
        Just returns a neo4j.GraphDatabase.driver object, to query the database.
        Don't forget to close !
    """
    return GraphDatabase.driver(PROTOCOL + '://' + DATABASE_URL + ':' + DATABASE_PORT, auth=(USERNAME, PASSWORD))



def run_query(driver, query):
    """
        Runs a cypher query
        @return is a tuple (result, success)
        result is either the result of the query or an error message
        if success == True, result is the result of the query, if success == False, it's the error msg.
    """
    try:
        with driver.session() as session:
            result = session.run(query)
            return result, True
    except neobolt.exceptions.CypherSyntaxError as e:
        return str(e), False
