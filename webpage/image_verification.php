<?php

$cwd[__FILE__] = __FILE__;
if (is_link($cwd[__FILE__])) $cwd[__FILE__] = readlink($cwd[__FILE__]);
$cwd[__FILE__] = dirname($cwd[__FILE__]);

require_once($cwd[__FILE__] . '/../../citizen_science_grid/my_query.php');

$result = query_wildlife_video_db("select t1.* from test_image_observations as t1 inner join images as t2 on t1.image_id = t2.id where t2.views >= t2.needed_views and t2.verified <> 1;");

$images = array();
$id = -1;
$i = array();

while ($row = $result->fetch_assoc()) {
	$current_id = $row['image_id'];
	error_log("Current id: " . $current_id);
	$i[] = $row;
	if ($id != $current_id  && $id != -1) {
		$images[] = $i;
		$i = array();
	}
	$id = $current_id;
}
$images[] = $i;


foreach ($images as $image) {
	error_log("Current image " . $image[0]['image_id']);
	$num_of_verified = 1;
	$verified_users = array();
	$image_verified = false;

	for ($i = 0; $i < count($image); $i++) {
		//error_log("The image id is " . $image[$i]['image_id']);
		for ($j = $i + 1; $j < count($image); ++$j) {
			if ($image[$i]['nothing_here'] == 1) {
				if ($image[$j]['nothing_here'] == 1) {
					$num_of_verified++;
					$verified_users[] = $image[$j]['user_id'];
				}
			} else {
				if ($image[$j]['nothing_here'] == 0 && abs(($image[$i]['top'] - $image[$j]['top'])) <= 10 && abs(($image[$i]['left_side'] - $image[$j]['left_side'])) <= 10 &&
					abs(($image[$i]['height'] - $image[$j]['height']))  <= 20 && abs(($image[$i]['width'] - $image[$j]['width'])) <= 20) 
					if ($image[$i]['species_id'] == $image[$i]['species_id']) {
						$num_of_verified++;
						$verified_users[] = $image[$j]['user_id'];

					}
			}
		}
		if ($num_of_verified > 1) $verified_users[] = $image[$i]['user_id'];
		if ($num_of_verified == 2) break;
		elseif ($num_of_verified > 2) {
			$image_verified = true;
			break;
		}
	}

	if ($image_verified) {
		error_log("The image " . $image[0]['image_id'] . " was verified!");
		/*query_wildlife_video_db("update images set verified = 1 where id = " . $image[0]['image_id']);
		foreach($verified_users as $user) {
			query_wildlife_db("update test_image_observations set verified = 1 where user_id = $user and image_id = " . $image[0]['image_id']);
		}*/
	} else {
		error_log("The image " . $image[0]['image_id'] . " was not verified");
		//query_wildlife_video_db("update images set needed_views = needed_views + 1 where id = " . $image[0]['image_id']);
	}

	
}
?>
