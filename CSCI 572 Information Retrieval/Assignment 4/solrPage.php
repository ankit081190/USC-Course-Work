<?php

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
$sort=isset($_REQUEST['indexType']) ? $_REQUEST['indexType'] : false;
$hashmap = array();
if (($handle = fopen("/home/ankit/solr-6.5.0/NYTimesData/mapNYTimesDataFile.csv", "r")) !== FALSE) {
  while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    $hashmap[$data[0]] = $data[1]; 
  }
  fclose($handle);
}


if ($query)
{
  
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  require_once('/home/ankit/solr-6.5.0/solr-php-client/Apache/Solr/Service.php');

  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/nytimes');

  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($quer);
    echo "<script type='text/javascript'>
	window.alert('$query')
	</script>";

  }

  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try
  {
    $additionalParameters =array('sort'=>'pageRankFile desc');
   // foreach ($additionalParameters as $key => $value) {
   //	echo "Key: $key; Value: $value\n";
   //	}
	
    if ($sort =='lucene'){
	$results = $solr->search($query, 0, $limit);
    }
    else{
	$results = $solr->search($query, 0, $limit, $additionalParameters);
    }
  }
  catch (Exception $e)
  {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

?>
<html>
  	<head>
    		<title>PHP Solr Client Example</title>
  	</head>
  	<body>
    	<form  accept-charset="utf-8" method="get">
      		<label for="q">Search:</label>
		<input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/><br>
                <input type="radio" id="indexType" name="indexType" value="lucene" <?php if((isset($_REQUEST['indexType']) && $_REQUEST['indexType'] == 'lucene')) echo ' checked="checked"';?> > Lucene
                <input type="radio" id="indexType" name="indexType" value="pageRank desc" <?php if((isset($_REQUEST['indexType']) && $_REQUEST['indexType'] == 'pageRank desc')) echo ' checked="checked"';?> > PageRank<br>
      		<input type="submit"/>
    	</form>
<?php

// display results
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    <ol>
<?php
  // iterate result documents
  foreach ($results->response->docs as $doc)
  {
?>
      <li>
	<?php if($doc->og_url == null) {
		$end = end((explode('/', $doc->id)));
		$doc->og_url = $hashmap[$end];
	}
	?>
	<a href="<?php echo htmlspecialchars($doc->og_url, ENT_NOQUOTES, 'utf-8'); ?>"><?php echo htmlspecialchars($doc->title, ENT_NOQUOTES, 'utf-8'); ?></a> &nbsp&nbsp&nbsp <a href="<?php echo htmlspecialchars($doc->og_url, ENT_NOQUOTES, 'utf-8'); ?>"> [<?php echo htmlspecialchars($doc->id, ENT_NOQUOTES, 'utf-8'); ?>]</a><br>

	<a  href="<?php echo htmlspecialchars($doc->og_url, ENT_NOQUOTES, 'utf-8'); ?>" style="color: green; font-size=9px;"><?php echo htmlspecialchars($doc->og_url, ENT_NOQUOTES, 'utf-8'); ?></a><br>

	<?php echo htmlspecialchars($doc->description, ENT_NOQUOTES, 'utf-8'); ?><br>

<!--        <table style="border: 1px solid black; text-align: left"> 
<?php
    // iterate document fields / values
    foreach ($doc as $field => $value)
    {
?>
          <tr>
            <th><?php echo htmlspecialchars($field, ENT_NOQUOTES, 'utf-8'); ?></th>
            <td><?php echo htmlspecialchars($value, ENT_NOQUOTES, 'utf-8'); ?></td>
          </tr>
<?php
    }
?>
        </table>
-->
      </li>
<?php
  }
?>
    </ol>
<?php
}
?>
	</body>
</html>
