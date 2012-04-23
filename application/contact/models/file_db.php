<?php
class file_db
{
	//private variables


	var $named = array();
	var $filehandle;

	var $buffsize = 4096;

	var $cols;
	var $length;
	var $TRANS;


	//public variables

	var $limiter = ",";
	var $filename;
	var $record = array();
	var $EOF;
	var $BOF;
	var $index;

	//private functions

	function get_line()
	{
		//Read 1 line from selected file
		$buff = fgetcsv ($this->filehandle, $this->buffsize, $this->limiter);
		return $buff;
	}

	function get_record()
	{
		//Get 1 Recordset from selected file, defined by the cols
		$tmpRec = fgetcsv ($this->filehandle, $this->buffsize, $this->limiter);

		for ($i = 0; $i < $this->cols; $i++)
		{
			$this->record[$this->named[$i]]= $tmpRec[$i];
		}

		$this->index++;
	}

	function count_cols()
	{
		//If first line read, then this functions returns the number of cols
		$this->cols = count ($this->named);
		return $this->cols;
	}

	function get_length()
	{
		//Returns the length of the recordset
		//!!! Importand after lenght was called $this->index points to 0
		$tmpL = 0;

		rewind($this->filehandle);

		while (!feof($this->filehandle))
		{
			$this->get_line();
			$tmpL++;
		}
		rewind($this->filehandle);

		$this->length=$tmpL-1;

	}

	//public functions

	function open_db()
	{
		//Open selected file, get length, cols, and name the cols, return first recordset

		$this->index = 0;

		$this->filehandle = fopen($this->filename, "r+");
		if ($this->filehandle)
		{
			$this->get_length();
			$this->named = $this->get_line();
			$this->count_cols();
			$this->get_record();

			return 1;
		}
		else
		{
			return 0;
		}
	}

	function move_next()
	{
			if ($this->index <= $this->length)
			{

				$this->BOF = false;
				$this->EOF = false;

				if ($this->index == $this->length)
				{
					$this->EOF = true;
				}

				$this->get_record();

			}
	}

	function move_prev()
	{
		$tmpPointer = $this->index;


			if ($tmpPointer > 1)
			{
				//Move to the beginning

				$this->move_first();
				for ($i = 1; $i < ($tmpPointer-1);$i++)
				{
					$this->get_record();
				}
				$this->BOF = false;
				$this->EOF = false;

			}
			else
			{
				$this->BOF = true;
				$this->EOF = false;
			}
	}

	function move_first()
	{
		rewind($this->filehandle);
		$this->get_line();
		$this->index=0;
		$this->get_record();
		$this->BOF = true;
		$this->EOF = false;
	}

	function move_last()
	{
		$this->move_first();
		while ($this->index != $this->length)
		{
			$this->get_record();
		}
		$this->EOF = true;
		$this->BOF = false;
	}


	function close_db()
	{
		fclose($this->filehandle);
	}

	function update()
	{
		rewind($this->filehandle);
		$tmpP = $this->index;
		$tmp = fopen("temp.txt", "a+");

		for ($i = 0; $i < $tmpP; $i++)
		{
			fwrite($tmp, fgets($this->filehandle, $this->buffsize));
		}
			$tmpStr = $this->record[$this->named[0]];
			for ($i = 1; $i < $this->cols;$i++)
			{
				$tmpStr .= $this->limiter.$this->record[$this->named[$i]];
			}

			if ($tmpP+1 <= $this->length)
			{
				$tmpStr.="\n";
			}

			fgets($this->filehandle, $this->buffsize);
			fwrite($tmp, $tmpStr);


		for ($i = $tmpP+1; $i <= $this->length; $i++)
		{
			fwrite($tmp, fgets($this->filehandle, $this->buffsize));
		}

		fclose($this->filehandle);
		unlink($this->filename);

		fclose($tmp);
		rename("./temp.txt", $this->filename);


		$this->filehandle = fopen($this->filename, "r+");

		//Setzen des Index an die richtige Stelle
		for ($i = 0; $i <= $tmpP-1; $i++)
		{
			fgets($this->filehandle, $this->buffsize);
		}
		$this->get_record();
		$this->index = $tmpP;

		$this->TRANS = "";



	}

	function add_new()
	{
		//Add_new ist called at last
		for ($i = 0; $i < $this->cols; $i++)
		{
			$this->record[$this->named[$i]]="";
		}
		$this->move_last();
		$tmpStr = "\n";

		for ($i = 0 ; $i < $this->cols-1; $i++)
		{
			$tmpStr.=" ".$this->limiter." ";
		}

		fwrite($this->filehandle,$tmpStr);
		$this->index++;
		$this->length = $this->index;
		$this->TRANS = "ADD";

	}

	function delete()
	{
		rewind($this->filehandle);
		$tmpP = $this->index;
		$tmp = fopen("temp.txt", "a+");

		for ($i = 0; $i < $tmpP-1; $i++)
		{
			fwrite($tmp, fgets($this->filehandle, $this->buffsize));
		}

		if ($tmpP != $this->length)
		{

			fwrite($tmp, fgets($this->filehandle, $this->buffsize));
			//NOP
			fgets($this->filehandle, $this->buffsize);
			//NOP

			for ($i = $tmpP+1; $i <= $this->length; $i++)
			{
				fwrite($tmp, fgets($this->filehandle, $this->buffsize));
			}
		}
		else {
			$tmpS = trim(fgets($this->filehandle, $this->buffsize));
			fwrite($tmp, $tmpS);
		}

		fclose($this->filehandle);
		unlink($this->filename);

		fclose($tmp);
		rename("./temp.txt", $this->filename);


		$this->filehandle = fopen($this->filename, "r");

		//Setzen des Index an die richtige Stelle
		for ($i = 0; $i <= $tmpP-1; $i++)
		{
			fgets($this->filehandle, $this->buffsize);
		}
		$this->get_record();
		$this->index = $tmpP;
	}
}
?>