<?php

/**
 * @author Dodit Suprianto
 * Email: d0dit@yahoo.com
 * Website: http://doditsuprianto.com, http://goiklan.co.nr
 * Website: http://www.meozit.com, http://easyads.co.nr
 * 
 * CSSPagination is a pagination class which combines with Cascading Style Sheet for good looking style; 
 * CSSPagination has main function to split all records before they will be loaded into one website, 
 * to be several records in one page (you can determine how many records in one page). 
 * So, if you want to jump at the other page, you can choose once of them. 
 * CSSPagination is easy to use and good looking. I try to throw the complexity code. 
 * The most important is, that you can change the CSS code to make it suitable with your own page style.
 */


class CSSPagination
{
	private $totalrows;
	private $rowsperpage;
	private $website;
	private $page;
	private $sql;
		
	public function __construct($sql, $rowsperpage, $website)
	{
		$this->sql = $sql;
		$this->website = $website;
		$this->rowsperpage = $rowsperpage;
	}
	
	public function setPage($page)
	{
		if (!$page) { $this->page=1; } else  { $this->page = $page; }
	}
	
	public function getLimit()
	{
		return ($this->page - 1) * $this->rowsperpage;
	}
	
	private function getTotalRows()
	{
		global $db;

		$result = @mysqli_query($db,$this->sql) or die ("query failed!");	
		$this->totalrows = mysqli_num_rows($result);
	}
	
	private function getLastPage()
	{
		return ceil($this->totalrows / $this->rowsperpage);
	}
	
	public function showPage()
	{
		$this->getTotalRows();
			
		$pagination = "";
		$last = $this->getLastPage();
		$lpm1 = $this->getLastPage() - 1;
		$page = $this->page;
		$prev = $this->page - 1;
		$next = $this->page + 1;
			$margin=(isset($_REQUEST['margin']));
            $padding=(isset($_REQUEST['padding']));
		$pagination .= "<ul class=\"pagination pagination-circle\">";
		if($margin || $padding)
		{

			$pagination .= " style=\"";
			if($margin)
				$pagination .= "margin: $margin;";
			if($padding)
				$pagination .= "padding: $padding;";
			$pagination .= "\"";
		}
		$pagination .= "";
		
		if ($this->getLastPage() > 1)
		{
			
			if ($page > 1) 
			$pagination .= "<li class=\"page-item page-indicator\"><a class=\"page-link\" href=$this->website&page=$prev><i class=\"la la-angle-left\"></i></a></a>";
		else
			$pagination .= "<li class=\"page-item page-indicator\"><a class=\"page-link\"><i class=\"la la-angle-left\"></i></a></a>";
		
			
			if ($this->getLastPage() < 9)
			{	
				for ($counter = 1; $counter <= $this->getLastPage(); $counter++)
				{
					if ($counter == $page)
						$pagination .= "<li class=\"page-item active\"><a class=\"page-link\">".$counter."</a></li>";
					else
						$pagination .= "<li class=\"page-item\"><a class=\"page-link\"  href=$this->website&page=$counter>".$counter."</a></li>";					
				}
			}
			elseif($this->getLastPage() >= 9)
			{
				if($page < 4)		
				{
					for ($counter = 1; $counter < 6; $counter++)
					{
						if ($counter == $page)
							$pagination .= "<li class=\"page-item active\"><a class=\"page-link\">".$counter."</a></li>";
						else
							$pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=$this->website&page=$counter>".$counter."</a></li>";					
					}
					$pagination .= "<li style='color:#fff; font-weight:bold; font-size: 16px;'>...</li>";
					$pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=$this->website&page=$lpm1>".$lpm1."</a></li>";
					$pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=$this->website&page=$last>".$last."</a></li>";		
				}
				elseif($last - 3 > $page && $page > 1)
				{
					$pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=$this->website&page=1>1</a></li>";
					$pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=$this->website&page=2>2</a></li>";
					$pagination .= "...";
					for ($counter = $page - 1; $counter <= $page + 1; $counter++)
					{
						if ($counter == $page)
							$pagination .= "<li class=\"active\">".$counter."</li>";
						else
							$pagination .= "<li class=\"page-item\"><a  class=\"page-link\" href=$this->website&page=$counter>".$counter."</a></li>";					
					}
					$pagination .= "...";
					$pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=$this->website&page=$lpm1>$lpm1</a></li>";
					$pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=$this->website&page=$last>".$last."</a></li>";		
				}
				else
				{
					$pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=$this->website&page=1>1</a></li>";
					$pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=$this->website&page=2>2</a></li>";
					$pagination .= "...";
					for ($counter = $last - 4; $counter <= $last; $counter++)
					{
						if ($counter == $page)
							$pagination .= "<li class=\"active\">".$counter."</li>";
						else
							$pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=$this->website&page=$counter>".$counter."</a></li>";					
					}
				}
			}
		
		if ($page < $counter - 1) 
			$pagination .= "<li class=\"page-item page-indicator\"><a class=\"page-link\" href=$this->website&page=$next><i class=\"la la-angle-right\"></i></a></li>";
		else
			$pagination .= "<li class=\"page-item page-indicator\">
			<a class=\"page-link\">
				<i class=\"la la-angle-right\"></i></a>
		</li>";
		$pagination .= "</ul>\n";			
		}	
					
		return $pagination;
	}
}
?>