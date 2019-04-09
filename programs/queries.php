<?php
	$queries = array('1' => //Get all projects depending on a given package
							array("query" => "MATCH (pck:Package)<-[dp:DEPENDS_ON]-(p:Project)
												WHERE pck.name IN \$package
												RETURN p.name as project, dp.version as used_version 
												ORDER BY project ASC",
								"params" => array("package" => "list"), 
								"return" => array("project", "used_version")
							),

					'2' => //Get all packages required by a given physical server
							array("query" => "MATCH (pck:Package)<-[dp:DEPENDS_ON]-(p:Project)
												WHERE p.name IN \$project
												RETURN pck.name as package, dp.version as used_version 
												ORDER BY package ASC",
								"params" => array("project" => "list"),
								"return" => array("package", "used_version")
							),
					
					'3' => //Get all files dépending of a given file
							array("query" => "MATCH (file:File)-[:IS_INCLUDED_IN|:IS_REQUIRED_IN|:IS_USED_BY|:DECLARES*1..10]->(f:File)
												WHERE file.repository = '\$repo'
												AND file.path = '\$path'
												RETURN DISTINCT f.path as file",
								"params" => array("repo" => "string", "path" => "string"),
								"return" => array("file")
							)
					);
					
?>
