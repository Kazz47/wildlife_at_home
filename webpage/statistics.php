<?php

//git push origin master (sends changes to server proper)
//git commit -a [filename] ('saves' files)

//chdir("/projects/wildlife/html/user"); //Only for testing

$cwd[__FILE__] = __FILE__;
if (is_link($cwd[__FILE__])) $cwd[__FILE__] = readlink($cwd[__FILE__]);
$cwd[__FILE__] = dirname($cwd[__FILE__]);

require_once($cwd[__FILE__] . "/../../citizen_science_grid/header.php");
require_once($cwd[__FILE__] . "/../../citizen_science_grid/navbar.php");
require_once($cwd[__FILE__] . "/../../citizen_science_grid/footer.php");
require_once($cwd[__FILE__] . "/../../citizen_science_grid/my_query.php");
require_once($cwd[__FILE__] . "/../../citizen_science_grid/user.php");

print_header("Wildlife@Home", "", "wildlife");
print_navbar("Projects: Wildlife@Home", "Wildlife@Home");

//require ('/../../../home/tdesell/wildlife_at_home/mustache.php/src/Mustache/Autoloader.php');
//Mustache_Autoloader::register();

try
{
	$user = csg_get_user();
	$is_special = csg_is_special_user($user, true);
	
}
catch(Exception $e)
{
	echo "Error: " . $e->getMessage();
}

//$bootstrap_scripts = file_get_contents("/../../../home/tdesell/wildlife_at_home/webpage/bootstrap_scripts.html");

echo "
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html>
<head>
        <meta charset='utf-8'>
        <title>Wildlife@Home: Expert Observations Statistics</title>

        <link rel='alternate' type='application/rss+xml' title='Wildlife@Home RSS 2.0' href='/rss_main.php'>
        <link rel='icon' href='wildlife_favicon_grouewjn3.png' type='image/x-icon'>
        <link rel='shortcut icon' href='wildlife_favicon_grouewjn3.png' type='image/x-icon'>
		
		<style type=\"text/css\">
			#statbuttons {margin-top: 60px; text-align: center;}
			#nestbuttons {margin-top: 10px; text-align: center;}
			.bcontainer {margin-top: 30px;}
			#perdaystats, #durationstats, #eventstimestats {margin-left: auto; margin-right: auto; margin-bottom: 10px; padding: 20px; width: 75%}
			.datatable {display: table;}
				#perdaydtcol1 {display: table-column;}
				#perdaydtcol2 {display: table-column;}
					#perdaydata {display: table-cell; width: 50%; vertical-align: top;}
					#perdaygraphcon {display: table-cell; width: 50%; vertical-align: top;}
						#perdaygraph {border: 1px solid #000000; float: right;}
							#perdaygraph div {max-width: 50%;}
				.tableoutputouter {display: block;}
			#one, #two, #three, #loczero, #locone, #loctwo, #locthree {width: auto; border: 1px solid #000000;}
			#one:hover, #two:hover, #three:hover, #loczero:hover, #locone:hover, #loctwo:hover, #locthree:hover {background-color: #f0f0f0; cursor: pointer;}
		</style>
		
		<script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"></script>
		<script>
		google.load('visualization', '1.0', {'packages':['corechart']});
		
		function fetchStats(species, nest)
		{
			var data = 'action=goforit&species=' + species;
			
			if(nest != 0)
			{
				data = data + '&nestsite=' + nest;
			}
			
			$.ajax({
				type: 'POST',
				url: 'statback2.php',
				data: data,
				success: function(data){
					$('.bcontainer').html(data);
					if(species != 1)
					{
						$('#nestbuttons').hide();
					}
					else
					{
						$('#nestbuttons').show();
					}
				}
			});
		}
		
		function numtimeschart(keyval, species) //This makes the graph for the data set: number of recess events/day. Called by statback.php.
		{
			var data = new google.visualization.DataTable(); 
			data.addColumn('string', 'Times a Day');
			data.addColumn('number', 'All Sites');
			data.addRows(keyval); //Note: basestring generated by appropriate function in statback.php.
			
			var title = 'Number of Recess Events Per Day';
			var width = '600';
			
			if(species == 1)
			{
				title = title + ' (Sharp-Tailed Grouse)';
			}
			else if(species == 2)
			{
				title = title + ' (Least Tern)';
				width = '600';
			}
			else if(species == 3)
			{
				title = title + ' (Piping Plover)';
			}
			
			var options = {'title':title, 'vAxis': {title: 'Number of Days Observed'}, 'hAxis': {title: 'Number in Incubation Recesses Per Day'}, 'bar': {groupwidth: '10%'}, 'width':width, 'height':300};
			//Instantiating and drawing chart
			var chart = new google.visualization.ColumnChart(document.getElementById('perdaygraph'));
			chart.draw(data, options);
		}
		
		function showTable(number)
		{
			var temp = '#tbloutputinner' + number;
			var temp2 = '#but' + number;
			
			if($(temp).is(':visible'))
			{
				$(temp).hide();
				$(temp2).val('Show');
			}
			else
			{
				$(temp).show();
				$(temp2).val('Hide');
			}
		}
		
		</script>

        $bootstrap_scripts

  
";
echo "</head><body>";
/*$active_items = array(
                    'home' => '',
                    'watch_video' => 'active',
                    'message_boards' => '',
                    'preferences' => '',
                    'about_wildlife' => '',
                    'community' => ''
                );

print_navbar($active_items);*/

if(/*!empty($user) && $is_special*/ true)
{
	require_once("statback2.php");
	
	echo "<div id=\"statbuttons\">View statistics for: <br /><button class=\"btn\" name=\"one\" id=\"one\" onclick=\"fetchStats(1, 0); return false;\" style=\"border-radius: 5px 0px 0px 5px; border-right: 0px;\">Sharp-Tailed Grouse</button><button class=\"btn\" name=\"two\" id=\"two\" onclick=\"fetchStats(2, 0); return false;\" style=\"border-right: 0px; border-radius: 0px 0px 0px 0px;\">Least Tern</button><button class=\"btn\" name=\"three\" id=\"three\" onclick=\"fetchStats(3, 0); return false;\" style=\"border-radius: 0px 5px 5px 0px;\">Piping Plover</button></div>";
	
	echo "<div id=\"nestbuttons\">Nesting Site: <br /><button class=\"btn\" name=\"loczero\" id=\"loczero\" onclick=\"fetchStats(1, 0)\" style=\"border-radius: 5px 0px 0px 5px; border-right: none;\">All</button><button class=\"btn\" name=\"locone\" id=\"locone\" onclick=\"fetchStats(1, 1); return false;\" style=\"border-radius: 0px 0px 0px 0px; border-right: none;\">Belden</button><button class=\"btn\" name=\"loctwo\" id=\"loctwo\" onclick=\"fetchStats(1, 2); return false;\" style=\"border-radius: 0px 0px 0px 0px; border-right: none;\">Blaisdell</button><button class=\"btn\" name=\"locthree\" id=\"locthree\" onclick=\"fetchStats(1, 3); return false;\" style=\"border-radius: 0px 5px 5px 0px;\">Lostwood</button></div>";
	
	echo "<br /><br />";
	echo "<div class=\"bcontainer\">";
	
	//Default species is Sharp-Tailed Grouse
	$species = 1;

	outputStats($species, 0);

	echo "</div>";
}
else
{
	"You do not have the permissions to view this page!";
}


echo "</body></html>";
?>
