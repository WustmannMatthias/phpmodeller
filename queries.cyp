//All files including a given file (depth max : 10)
MATCH (repo:Repository)-[:FILE]->(startFile:File) 
WHERE repo.name = 'type_test_repo'
AND startFile.path = 'type_test_repo/frames/stuffs/item.php'

MATCH (startFile)<-[:INCLUDE|REQUIRE*0..10]-(endFile:File)
RETURN DISTINCT endFile.path AS impacted_file


//All files using a class declared by a given file
MATCH (repo:Repository)-[:FILE]->(startFile:File)<-[:IS_DECLARED_BY]-(class:Class)
WHERE repo.name = 'type_test_repo'
AND startFile.path = 'type_test_repo/Products/Product1.php'

MATCH (class)<-[:USE]-(endFile:File)
RETURN DISTINCT endFile.path AS impacted_file


//All files impacted by a given file
MATCH (repo:Repository)-[:FILE]->(startFile:File)
WHERE repo.name = 'type_test_repo'
AND startFile.path = 'type_test_repo/data/variables.inc'

MATCH (startFile)<-[:IS_DECLARED_BY|:USES|:INCLUDES|:REQUIRES*0..10]-(endFile:File)
RETURN DISTINCT endFile.path AS impacted_file
ORDER BY impacted_file ASC


//See repercusions (graphic)
MATCH (repo:Repository)-[:FILE]->(startFile:File)
WHERE repo.name = 'type_test_repo'
AND startFile.path = 'type_test_repo/Products/Product1.php'

MATCH (startFile)<-[:IS_DECLARED_BY|:USE|:INCLUDE|:REQUIRE*0..10]-(endFile)
RETURN DISTINCT endFile.path AS impacted_file
