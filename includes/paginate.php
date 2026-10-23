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


function paginates($total, $limit, $page, $baseUrl)
{
    if ($total <= $limit) {
        return '';
    }

    $totalPages = ceil($total / $limit);
    $page = max(1, min($page, $totalPages));

    $html = '<ul class="pagination">';

    // First + Prev
    if ($page > 1) {
        $html .= '<li><a href="'.$baseUrl.'&page=1">First</a></li>';
        $html .= '<li><a href="'.$baseUrl.'&page='.($page-1).'">Previous</a></li>';
    }

    // Page numbers
    $start = max(1, $page - 4);
    $end   = min($totalPages, $page + 4);

    if ($start > 1) {
        $html .= '<li class="disabled"><span>...</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i == $page) {
            $html .= '<li class="active"><span>'.$i.'</span></li>';
        } else {
            $html .= '<li><a href="'.$baseUrl.'&page='.$i.'">'.$i.'</a></li>';
        }
    }

    if ($end < $totalPages) {
        $html .= '<li class="disabled"><span>...</span></li>';
    }

    // Next + Last
    if ($page < $totalPages) {
        $html .= '<li><a href="'.$baseUrl.'&page='.($page+1).'">Next</a></li>';
        $html .= '<li><a href="'.$baseUrl.'&page='.$totalPages.'">Last</a></li>';
    }

    $html .= '</ul>';

    return $html;
}
?>
