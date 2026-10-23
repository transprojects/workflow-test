<?php
function paginate($start,$limit,$total,$filePath,$otherParams) {
	global $lang;
   
   	$allPages = ceil($total/$limit);

	$currentPage = floor($start/$limit) + 1;

	$pagination = "";
	if ($allPages>10) {
		$maxPages = ($allPages>6) ? 9 : $allPages;

		if ($allPages>9) {
			if ($currentPage>=1&&$currentPage<=$allPages) {
				$pagination .= ($currentPage>4) ? "<li><a> ... </a></li>" : " ";

				$minPages = ($currentPage>4) ? $currentPage : 5;
				$maxPages = ($currentPage<$allPages-4) ? $currentPage : $allPages - 4;

				for($i=$minPages-4; $i<$maxPages+5; $i++) {
					$pagination .= ($i == $currentPage) ? "<li class='active'><span class='current'>".$i."</span></li>" : "<li><a href=\"".$filePath."&start=".(($i-1)*$limit).$otherParams."\" >".$i."</a></li>";
				}
				$pagination .= ($currentPage<$allPages-4) ? "<li><a> ... </a></li>" : " ";
			} else {
				$pagination .= "<li><a> ... </a></li>";
			}
		}
	} else {
		for($i=1; $i<$allPages+1; $i++) {
			$pagination .= ($i==$currentPage) ? "<li class='active'><span class='current'>".$i."</span></li>" : "<li><a href=\"".$filePath."&start=".(($i-1)*$limit).$otherParams."\">".$i."</a></li>";
		}
	}

	if ($currentPage>1) $pagination = "<li><a href=\"".$filePath."&start=0".$otherParams."\">First</a></li><li><a href=\"".$filePath."&start=".(($currentPage-2)*$limit).$otherParams."\" >Previous</a> </li>".$pagination;
	if ($currentPage<$allPages) $pagination .= "<li><a href=\"".$filePath."&start=".($currentPage*$limit).$otherParams."\">Next</a></li><li><a href=\"".$filePath."&start=".(($allPages-1)*$limit).$otherParams."\" >Last</a></li>";

	echo $pagination;
}
?>