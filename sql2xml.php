<?PHP

class DB {

	public function Select($file, $where = "")
	{
		$file = XMLDIR.$file.".xml";
		if(!file_exists($file)) return false;
		
		$xml = simplexml_load_file($file);
		
		for($i = 0; $i < count($xml->item); $i ++)
			foreach($xml->item[$i] as $key => $val)
				$allRecs[$i][$key] = (string)$val;
				
		if(!$where) return $allRecs;	
		
		if(substr_count($where, " LIMIT ") == 1)
		{
			$arrLimit = explode(" LIMIT ", $where);
			$arrAnd   = explode(" AND ", $arrLimit[0]);
			$arrComma = explode(",", $arrLimit[1]);
			
			for($i = 0; $i < count($arrAnd); $i ++)
				$arrLike[$i] = explode(" LIKE ", $arrAnd[$i]);
			
			for($i = 0; $i < count($arrLike); $i ++)		
				$array[trim($arrLike[$i][0])] = trim($arrLike[$i][1]);
		}
		else
		{
			$arrAnd = explode(" AND ", $where);
			
			for($i = 0; $i < count($arrAnd); $i ++)
				$arrLike[$i] = explode(" LIKE ", $arrAnd[$i]);
			
			for($i = 0; $i < count($arrLike); $i ++)		
				$array[trim($arrLike[$i][0])] = trim($arrLike[$i][1]);
		}
			
		for($i = 0; $i < count($allRecs); $i ++)
		{
			foreach($allRecs[$i] as $key => $val)
				foreach($array as $key2 => $val2)
					if($key == $key2 and $val == $val2)
						$count ++;
						
			if($count == count($array))
				$resArr[] = $allRecs[$i];
				
			$count = 0;
		}

		if(substr_count($where, " LIMIT ") == 1)
		{
			$start = trim($arrComma[0]);
			$newsNum = trim($arrComma[1]);
			$afterStart = count($resArr) - $start;
			
			if($start < 1) $start = 1;
			if($newsNum < 1) $newsNum = 1;
		
			if($newsNum > $afterStart)
				$newsNum = $afterStart + 1;
				
			if($start > count($resArr))
			{
				$start = count($resArr);
				$newsNum = 1;
			}
				
			for($i = 0; $i < $newsNum; $i ++)
			{
				foreach($resArr[$start - 1] as $key => $val)
					$resArr2[$i][$key] = $val;
					
				$start++;
			}
			
			return $resArr2;
		}

		return $resArr;
	}
	
	public function Insert($file, $data)
	{
		if(!$file or !$data) return false;
		
		if(!file_exists(XMLDIR.$file.".xml"))
		{
			array_unshift($data, "");
			foreach($data as $key => $val)
			{
				if($key === 0)
				{
					$key = 'id';
					$val = 1;
				}
				$resArr[$key] = $val;
			}
			
			$dom = new DomDocument('1.0'); 
			$items = $dom->appendChild($dom->createElement($file));
			$item = $items->appendChild($dom->createElement('item'));
			
			foreach($resArr as $key => $val)
				$item->appendChild($dom->createElement($key, $val));
				
			// $dom->formatOutput = true;	
			$dom->save(XMLDIR.$file.".xml");
			
			return true;
		}

		$dom = new DomDocument();
		
		// $dom->formatOutput = true;
		// $dom->preserveWhiteSpace = false;
		
		$dom->load(XMLDIR.$file.".xml");
		
		$item = $dom->createElement("item"); 
		
		$allRecs = $this->Select($file);
		$lastArr = array_pop($allRecs);
		$lastId = $lastArr['id'];

		array_unshift($data, "");
		foreach($data as $key => $val)
		{
			if($key === 0)
			{
				$key = 'id';
				$val = $lastId + 1;
			}
			
			$resArr[$key] = $val;
		}
		
		if(count($resArr) != count($lastArr)) return false;
		
		foreach($resArr as $key => $val)
			$item->appendChild($dom->createElement($key, $val));
		
		$dom->getElementsByTagName($file)->item(0)->appendChild($item);
		$dom->save(XMLDIR.$file.".xml");
		
	}
	
	public function Update($file, $data, $where)
	{
		if(!file_exists(XMLDIR.$file.".xml") or !$where or !$data) return false;
		
		$arr = explode(" LIKE ", $where);
		$id = trim($arr[1]);
		
		$allRecs = $this->Select($file);
		
		for($i = 0; $i < count($allRecs); $i ++)
			foreach($allRecs[$i] as $key => $val)
			{
				if($allRecs[$i]['id'] == $id) 
					$array[$i] = $data;
				else 
					$array[$i][$key] = $val;
			}

		if($allRecs != $array)
		{
			unlink(XMLDIR.$file.".xml");
			
			for($i = 0; $i < count($array); $i++)
				$this->Insert($file, $array[$i]);
		}
	}
	
	public function Delete($file, $where)
	{
		if(!file_exists(XMLDIR.$file.".xml") or !$where) return false;
		
		$arr = explode(" LIKE ", $where);
		$id = trim($arr[1]);
		
		$allRecs = $this->Select($file);
		if(!$allRecs) return false;

		$cnt = 0;
		for($i = 0; $i < count($allRecs); $i ++)
		{
			foreach($allRecs[$i] as $key => $val)
			{
				if($val == $id) $cnt--;
				if($key == 'id') continue;
				if($id == 1 and count($allRecs) == 1)
					return unlink(XMLDIR.$file.".xml");

				if($allRecs[$i]['id'] != $id)
					$array[$cnt][$key] = $val;
			}
			$cnt++;
		}		
		
		if($allRecs and $array and $allRecs != $array)
		{
			unlink(XMLDIR.$file.".xml");
			
			for($i = 0; $i < count($array); $i++)
				$this->Insert($file, $array[$i]);
		}
	}

}


?>