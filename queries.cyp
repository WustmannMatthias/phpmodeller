Find all non non declared Namespaces : 

MATCH (n:Namespace)
WHERE NOT EXISTS (n.inVendor)
AND NOT EXISTS ( (n)<-[:DECLARES]-(:File) )
RETURN n.name as namespace



Find all files that use some non-declared namespaces : 

MATCH (n:Namespace)
WHERE NOT EXISTS (n.inVendor)
AND NOT EXISTS ( (n)<-[:DECLARES]-(:File) )
MATCH (f:File)<-[use:IS_USED_BY]-(n)
RETURN f.path as file, n.name as namespace, use.class as classes




Verify that all IS_USED_BY relations are valid (just for testing) : 

MATCH p = ()-[r:IS_USED_BY]-()
WHERE NOT EXISTS (r.class)
RETURN p





Find how many lines of code are loaded by a file (including this file) :

MATCH (n:File {name: "Node.php"})<-[:IS_REQUIRED_IN|:IS_INCLUDED_IN*0..]-(f:File) RETURN sum(f.loc)
as included_lines


Find how many lines of code are loaded by a file (excluding this file) :

MATCH (n:File {name: "Node.php"})<-[:IS_REQUIRED_IN|:IS_INCLUDED_IN*1..]-(f:File) RETURN sum(f.loc)
as included_lines






Find all features affected by a file :

MATCH p = (file:File {path: "$path"})-[:IS_INCLUDED_IN|:IS_REQUIRED_IN|:IS_USED_BY|:IMPACTS|:DECLARES*0..]->(feature:Feature) RETURN DISTINCT feature.name AS feature



Find files affecting a feature : 

MATCH (file:File)-[:IS_INCLUDED_IN|:IS_REQUIRED_IN|:IS_USED_BY|:IMPACTS|:DECLARES*0..]->(feature:Feature {name:"$feature" }) RETURN DISTINCT file.path AS file



!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
Find all features to test when iteration is frozen :
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

MATCH (p:Project {name: '$project'}) 
MATCH (i:Iteration {name: '$iteration'})-[:IS_ITERATION_OF]->(p)
MATCH (files:File)-[:BELONGS_TO]->(i)
MATCH (files)-[:IS_INCLUDED_IN|:IS_REQUIRED_IN|:IS_USED_BY|:IMPACTS|:DECLARES*0..]->(feature:Feature)
WITH feature
ORDER BY feature.name ASC 
RETURN DISTINCT feature.name AS feature



!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
!!! GET ALL FILES IMPACTED BY A GIVEN FILE !!!
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

MATCH (file:File)-[:IS_INCLUDED_IN|:IS_REQUIRED_IN|:IS_USED_BY|:DECLARES*1..10]->(f:File)
WHERE file.repository = '$repo'
AND file.path = '$path'
RETURN DISTINCT f.path as file






Get which Projects use a service

MATCH (s:Service)<-[dp:DEPENDS_ON]-(p:Project)
WHERE s.name = 'fei/api-client'
RETURN p.name as project, dp.version as used_version 
ORDER BY project ASC


Get which services are used by a project

MATCH (s:Service)<-[dp:DEPENDS_ON]-(p:Project)
WHERE p.name = 'alerts'
RETURN s.name as service, dp.version as used_version 
ORDER BY service ASC






